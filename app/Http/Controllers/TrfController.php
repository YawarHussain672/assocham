<?php

namespace App\Http\Controllers;

use App\Mail\BoardingPassSharedMail;
use App\Models\Approval;
use App\Models\Attachment;
use App\Models\BookingMailTransaction;
use App\Models\Trf;
use App\Models\User;
use App\Services\ExcelReader;
use App\Support\Roles;
use App\Support\StorageHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Travel Requisition Form — first Phase 3 (Travel) module (see project
 * memory: assocham-portal-spec, and a not-yet-written Phase 3 memory file
 * to add once this lands). Workflow (deck p.16): Create TRF (Employee /
 * Admin on behalf of) -> ASG Approval (domestic, final) -> SG Approval
 * (international / direct reports) -> Approved (or pushed back).
 *
 * Two interpretive choices, both documented in App\Support\Roles:
 *  - "Employee" isn't one of the 10 named logins, so any authenticated
 *    role may create a TRF (see Roles::MODULES_FOR_ALL).
 *  - Domestic vs international is read as two *alternate* single-step
 *    paths (ASG-only, or SG-only), not a two-level chain where SG comes
 *    after ASG — matching Trf::requiresSgApproval()'s plain boolean and
 *    the deck's "ASG approval only" phrasing for domestic.
 *  - ASG 1 and ASG 2 are genuinely interchangeable for this single step
 *    (unlike Basu/Ambika's distinct PR levels) — the approval row's
 *    role_required is "asg1,asg2" and either can act on it first.
 *
 * "Pushback" uses the approvals table's dedicated pushed_back status
 * (built in Phase 0, unused until now) rather than a hard rejection —
 * matching the deck's "returns to requester with comments instead of an
 * outright rejection." No resubmit/edit flow exists yet (known gap, same
 * class as PO's unbuilt "edit resets to pending").
 */
class TrfController extends Controller
{
    public function index(Request $request): View
    {
        $allTrfs = Trf::with(['employee', 'approvals', 'reimbursements'])->latest()->get();

        $employees = $allTrfs
            ->pluck('employee')
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values();

        $departments = $allTrfs
            ->map(fn (Trf $t) => $t->department ?: 'General')
            ->unique()
            ->sort()
            ->values();

        $destinations = $allTrfs
            ->pluck('route')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $query = Trf::with(['employee', 'approvals', 'reimbursements'])->latest();

        if ($request->filled('employee_id') && $request->input('employee_id') !== 'all') {
            $query->where('employee_id', $request->integer('employee_id'));
        }

        if ($request->filled('department') && $request->input('department') !== 'all') {
            $dept = $request->input('department');
            $query->whereHas('employee', function ($q) use ($dept) {
                if ($dept === 'General') {
                    $q->whereNull('department')->orWhere('department', 'General');
                } else {
                    $q->where('department', $dept);
                }
            });
        }

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('destination')) {
            $dest = trim((string) $request->input('destination'));
            $query->where(function ($q) use ($dest) {
                $q->where('route', 'like', "%{$dest}%")
                    ->orWhere('destinations', 'like', "%{$dest}%");
            });
        }

        if ($request->filled('date_from')) {
            $dateFrom = $request->input('date_from');
            $query->where(function ($q) use ($dateFrom) {
                $q->whereDate('travel_end', '>=', $dateFrom)
                    ->orWhere(function ($q2) use ($dateFrom) {
                        $q2->whereNull('travel_end')->whereDate('travel_start', '>=', $dateFrom);
                    });
            });
        }

        if ($request->filled('date_to')) {
            $dateTo = $request->input('date_to');
            $query->whereDate('travel_start', '<=', $dateTo);
        }

        if ($request->filled('search')) {
            $term = trim((string) $request->input('search'));
            $query->where(function ($q) use ($term) {
                $q->where('trf_number', 'like', "%{$term}%")
                    ->orWhere('route', 'like', "%{$term}%")
                    ->orWhere('purpose', 'like', "%{$term}%")
                    ->orWhereHas('employee', fn ($eq) => $eq->where('name', 'like', "%{$term}%")->orWhere('department', 'like', "%{$term}%"));
            });
        }

        $trfs = $query->get();
        $selfOverdueTrf = $request->user() ? Trf::findOverdueSettlementFor($request->user()->id) : null;

        return view('pages.trf', [
            'trfs' => $trfs,
            'employees' => $employees,
            'departments' => $departments,
            'destinations' => $destinations,
            'totalAllTrfs' => $allTrfs->count(),
            'selfOverdueTrf' => $selfOverdueTrf,
        ]);
    }

    public const DOMESTIC_CITIES = [
        'New Delhi (DEL)',
        'Mumbai (BOM)',
        'Bengaluru (BLR)',
        'Chennai (MAA)',
        'Hyderabad (HYD)',
        'Kolkata (CCU)',
        'Pune (PNQ)',
        'Ahmedabad (AMD)',
        'Jaipur (JAI)',
        'Chandigarh (IXC)',
        'Lucknow (LKO)',
        'Kochi (COK)',
        'Thiruvananthapuram (TRV)',
        'Goa (GOI)',
        'Bhubaneswar (BBI)',
        'Guwahati (GAU)',
        'Indore (IDR)',
        'Nagpur (NAG)',
        'Patna (PAT)',
        'Bhopal (BHO)',
        'Surat (STV)',
        'Vadodara (BDQ)',
        'Visakhapatnam (VTZ)',
        'Coimbatore (CJB)',
        'Dehradun (DED)',
    ];

    public const INTERNATIONAL_CITIES = [
        'Singapore (SIN)',
        'Dubai (DXB)',
        'London (LHR)',
        'New York (JFK)',
        'Frankfurt (FRA)',
        'Paris (CDG)',
        'Tokyo (HND)',
        'Doha (DOH)',
        'Abu Dhabi (AUH)',
        'Bangkok (BKK)',
        'Kuala Lumpur (KUL)',
        'Hong Kong (HKG)',
        'Zurich (ZRH)',
        'Geneva (GVA)',
        'Brussels (BRU)',
        'Toronto (YYZ)',
        'Washington D.C. (IAD)',
        'Sydney (SYD)',
        'Seoul (ICN)',
        'Riyadh (RUH)',
    ];

    public const TRAVEL_MODES = [
        'Flight',
        'Train',
        'Road / Cab',
        'Multi-modal',
    ];

    public function create(): View
    {
        $overdueByEmployee = Trf::overdueSettlementsByEmployee();
        $overdueDetails = $overdueByEmployee->mapWithKeys(function (Trf $overdue, int $empId) {
            return [$empId => $this->formatOverduePayload($overdue)];
        })->all();
        $currentUserId = request()->user()?->id;

        return view('pages.trf-create', [
            'users' => User::where('is_active', true)->orderBy('name')->get(),
            'domesticCities' => self::DOMESTIC_CITIES,
            'internationalCities' => self::INTERNATIONAL_CITIES,
            'travelModes' => self::TRAVEL_MODES,
            'overdueDetails' => $overdueDetails,
            'selfOverdue' => $currentUserId ? ($overdueDetails[$currentUserId] ?? null) : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $resolved = $this->validateAndResolvePayload($request);
        if ($resolved instanceof RedirectResponse) {
            return $resolved;
        }

        $isInternational = $resolved['is_international'];

        $trf = Trf::create([
            'trf_number' => $this->nextTrfNumber(),
            'employee_id' => $resolved['employee_id'],
            'booking_type' => $resolved['booking_type'],
            'purpose' => $resolved['purpose'],
            'route' => $resolved['route'],
            'travel_mode' => $resolved['travel_mode'],
            'destinations' => $resolved['destinations'],
            'is_international' => $isInternational,
            'travel_start' => $resolved['travel_start'],
            'travel_end' => $resolved['travel_end'],
            'insurance' => $resolved['insurance'],
            'visa_required' => $resolved['visa_required'],
            'visa_details' => $resolved['visa_details'],
            'accommodation' => $resolved['accommodation'],
            'accommodation_details' => $resolved['accommodation_details'],
            'requested_advance' => $resolved['requested_advance'],
            'status' => $isInternational ? 'pending_sg' : 'pending_asg',
        ]);

        $trf->approvals()->create([
            'level' => 1,
            'role_required' => $isInternational ? Roles::SG : Roles::ASG1.','.Roles::ASG2,
            'status' => 'pending',
        ]);

        return redirect()->route('trf')
            ->with('status', "TRF {$trf->trf_number} submitted — awaiting ".($isInternational ? 'SG' : 'ASG').' approval.');
    }

    public function edit(Trf $trf): View
    {
        $user = request()->user();
        $canEdit = ($user?->id === $trf->employee_id) || ($user?->role === Roles::ADMIN_HEAD);
        abort_unless($canEdit, 403, 'Only the requester or Admin Head can edit this travel requisition.');
        abort_if($trf->status === 'approved', 403, 'Approved travel requisitions cannot be edited.');

        $trf->load(['employee', 'approvals.approver']);

        // Parse existing route into From, intermediate stops, and To cities
        $parts = preg_split('/\s*(?:→|->|\s-\s)\s*/', trim((string) $trf->route));
        $parts = array_values(array_filter(array_map('trim', $parts)));
        $fromCity = $parts[0] ?? '';
        $toCity = count($parts) > 1 ? $parts[count($parts) - 1] : '';
        $extraStops = count($parts) > 2 ? array_slice($parts, 1, -1) : [];

        $latestPushback = $trf->approvals()
            ->where('status', 'pushed_back')
            ->with('approver')
            ->latest('id')
            ->first();

        return view('pages.trf-edit', [
            'trf' => $trf,
            'users' => User::where('is_active', true)->orderBy('name')->get(),
            'domesticCities' => self::DOMESTIC_CITIES,
            'internationalCities' => self::INTERNATIONAL_CITIES,
            'travelModes' => self::TRAVEL_MODES,
            'fromCity' => $fromCity,
            'toCity' => $toCity,
            'extraStops' => $extraStops,
            'latestPushback' => $latestPushback,
        ]);
    }

    public function update(Request $request, Trf $trf): RedirectResponse
    {
        $user = $request->user();
        $canEdit = ($user?->id === $trf->employee_id) || ($user?->role === Roles::ADMIN_HEAD);
        abort_unless($canEdit, 403, 'Only the requester or Admin Head can edit and resubmit this travel requisition.');
        abort_if($trf->status === 'approved', 403, 'Approved travel requisitions cannot be modified.');

        $resolved = $this->validateAndResolvePayload($request, $trf);
        if ($resolved instanceof RedirectResponse) {
            return $resolved;
        }

        $wasPushedBack = $trf->status === 'pushed_back';
        $isInternational = $resolved['is_international'];

        $trf->update([
            'employee_id' => $resolved['employee_id'],
            'booking_type' => $resolved['booking_type'],
            'purpose' => $resolved['purpose'],
            'route' => $resolved['route'],
            'travel_mode' => $resolved['travel_mode'],
            'destinations' => $resolved['destinations'],
            'is_international' => $isInternational,
            'travel_start' => $resolved['travel_start'],
            'travel_end' => $resolved['travel_end'],
            'insurance' => $resolved['insurance'],
            'visa_required' => $resolved['visa_required'],
            'visa_details' => $resolved['visa_details'],
            'accommodation' => $resolved['accommodation'],
            'accommodation_details' => $resolved['accommodation_details'],
            'requested_advance' => $resolved['requested_advance'],
            'status' => $isInternational ? 'pending_sg' : 'pending_asg',
        ]);

        // Keep historical pushed_back approval records for audit visibility, and queue a fresh pending step
        $trf->approvals()->where('status', 'pending')->delete();
        $trf->approvals()->create([
            'level' => 1,
            'role_required' => $isInternational ? Roles::SG : Roles::ASG1.','.Roles::ASG2,
            'status' => 'pending',
        ]);

        $resubmitNotes = trim((string) $request->input('resubmit_notes', ''));
        $auditAction = $wasPushedBack ? 'resubmitted' : 'updated';
        $auditDesc = $wasPushedBack
            ? "TRF {$trf->trf_number} revised and resubmitted by {$user->name} for " . ($isInternational ? 'SG' : 'ASG') . ' review.'
            : "TRF {$trf->trf_number} updated by {$user->name}.";
        if ($resubmitNotes !== '') {
            $auditDesc .= " Revision notes: {$resubmitNotes}";
        }
        $trf->writeAuditEntry($auditAction, $auditDesc);

        return redirect()->route('trf.show', $trf)
            ->with('status', "TRF {$trf->trf_number} " . ($wasPushedBack ? 'revised and resubmitted' : 'updated') . ' — awaiting ' . ($isInternational ? 'SG' : 'ASG') . ' approval.');
    }

    /**
     * Validate TRF fields and resolve city route, international flag, and overdue settlement check.
     *
     * @return array<string, mixed>|RedirectResponse
     */
    private function validateAndResolvePayload(Request $request, ?Trf $existingTrf = null): array|RedirectResponse
    {
        $data = $request->validate([
            'booking_type' => ['required', 'in:self,on_behalf'],
            'employee_id' => ['nullable', 'required_if:booking_type,on_behalf', 'exists:users,id'],
            'purpose' => ['nullable', 'string', 'max:255'],
            'travel_mode' => ['nullable', 'string', 'max:60'],
            'from_city' => ['nullable', 'string', 'max:120'],
            'to_city' => ['nullable', 'string', 'max:120'],
            'destinations' => ['nullable', 'string', 'max:500'],
            'route' => ['nullable', 'string', 'max:255'],
            'is_international' => ['nullable', 'boolean'],
            'travel_start' => ['nullable', 'date'],
            'travel_end' => ['nullable', 'date', 'after_or_equal:travel_start'],
            'insurance' => ['nullable', 'boolean'],
            'visa_required' => ['nullable', 'boolean'],
            'visa_details' => ['nullable', 'string', 'max:255'],
            'accommodation' => ['nullable', 'boolean'],
            'accommodation_details' => ['nullable', 'string', 'max:255'],
            'requested_advance' => ['nullable', 'numeric', 'min:0'],
        ]);

        $fromCity = trim((string) ($data['from_city'] ?? ''));
        $toCity = trim((string) ($data['to_city'] ?? ''));
        $destinations = trim((string) ($data['destinations'] ?? ''));
        $extraStops = collect();

        if ($fromCity !== '' || $toCity !== '') {
            if ($fromCity === '' || $toCity === '') {
                return back()->withErrors([
                    'route' => 'Please select both Departure (From) and Destination (To) cities.',
                ])->withInput();
            }
            if (strcasecmp($fromCity, $toCity) === 0) {
                return back()->withErrors([
                    'to_city' => 'Destination (To City) must be different from Departure (From City).',
                ])->withInput();
            }

            $extraStops = collect(preg_split('/\s*,\s*/', $destinations))
                ->map(fn ($s) => trim((string) $s))
                ->filter()
                ->reject(fn ($s) => strcasecmp($s, $fromCity) === 0 || strcasecmp($s, $toCity) === 0)
                ->unique()
                ->values();

            $routeParts = array_merge([$fromCity], $extraStops->all(), [$toCity]);
            $route = implode(' → ', $routeParts);

            $allDestinations = collect(array_merge([$toCity], $extraStops->all()))
                ->unique()
                ->values()
                ->implode(', ');
            $destinations = $allDestinations;
        } else {
            $route = trim((string) ($data['route'] ?? ''));
            if ($route === '') {
                return back()->withErrors([
                    'from_city' => 'Please select From and To cities for the travel itinerary route.',
                ])->withInput();
            }
            if ($destinations === '') {
                $destinations = $route;
            }
        }

        if ($existingTrf && $data['booking_type'] === 'self' && $existingTrf->employee_id !== $request->user()->id && $request->user()->role === Roles::ADMIN_HEAD) {
            $employeeId = $existingTrf->employee_id;
            $bookingType = $existingTrf->booking_type;
        } else {
            $employeeId = ($data['booking_type'] === 'on_behalf' && ! empty($data['employee_id']))
                ? (int) $data['employee_id']
                : $request->user()->id;
            $bookingType = $data['booking_type'];
        }

        if ($bookingType === 'on_behalf') {
            $targetEmployee = User::find($employeeId);
            if (! $targetEmployee || ! $targetEmployee->isActive()) {
                return back()->withErrors([
                    'employee_id' => 'Travel requisitions cannot be raised on behalf of a deactivated employee.',
                ])->withInput();
            }
        }

        $overdue = Trf::findOverdueSettlementFor($employeeId);
        if ($overdue && (! $existingTrf || $overdue->id !== $existingTrf->id)) {
            $employeeName = $overdue->employee?->name ?? 'Employee';
            $refDate = $overdue->settlementReferenceDate();
            $dateText = $refDate ? $refDate->format('d M Y') : 'unknown date';
            $advText = ((float) $overdue->requested_advance > 0)
                ? ' (₹' . number_format((float) $overdue->requested_advance, 2) . ')'
                : '';

            return back()->withErrors([
                'employee_id' => "{$employeeName} has an unsettled travel advance{$advText} from {$overdue->trf_number} (ended {$dateText}, over 30 days ago). Reconcile it via Reimbursement before raising a new TRF.",
            ])->withInput();
        }

        $hasIntlCity = collect(array_filter([$fromCity, $toCity, ...$extraStops->all()]))
            ->contains(fn ($city) => in_array($city, self::INTERNATIONAL_CITIES, true));
        $isInternational = $request->boolean('is_international') || $hasIntlCity || $this->isInternationalRoute(null, $route);
        $accommodationNeeded = $request->boolean('accommodation');
        $visaRequired = $request->boolean('visa_required');

        return [
            'employee_id' => $employeeId,
            'booking_type' => $bookingType,
            'purpose' => $data['purpose'] ?? null,
            'route' => $route,
            'travel_mode' => ! empty($data['travel_mode']) ? $data['travel_mode'] : 'Flight',
            'destinations' => $destinations ?: null,
            'is_international' => $isInternational,
            'travel_start' => $data['travel_start'] ?? null,
            'travel_end' => $data['travel_end'] ?? null,
            'insurance' => $request->boolean('insurance'),
            'visa_required' => $visaRequired,
            'visa_details' => $visaRequired ? ($data['visa_details'] ?? null) : null,
            'accommodation' => $accommodationNeeded,
            'accommodation_details' => $accommodationNeeded ? ($data['accommodation_details'] ?? null) : null,
            'requested_advance' => $data['requested_advance'] ?? 0,
        ];
    }

    /**
     * Download Excel or CSV template for Bulk Travel Requisition import.
     */
    public function downloadTemplate(Request $request): Response
    {
        $headers = [
            'EMPLOYEE (OPTIONAL - LEAVE BLANK FOR SELF)',
            'ROUTE (FROM - TO)',
            'PURPOSE',
            'TRAVEL MODE (Flight / Train / Road / Multi-modal)',
            'START DATE (YYYY-MM-DD)',
            'END DATE (YYYY-MM-DD)',
            'ADVANCE (₹)',
            'INTERNATIONAL (YES / NO)',
            'ACCOMMODATION (YES / NO)',
        ];

        $sampleRows = [
            [
                '', // Leave blank for Self (or enter colleague name/email for on-behalf booking)
                'New Delhi (DEL) → Mumbai (BOM)',
                'Annual Banking & Finance Leadership Summit',
                'Flight',
                now()->addDays(7)->format('Y-m-d'),
                now()->addDays(9)->format('Y-m-d'),
                15000,
                'No',
                'Yes',
            ],
        ];

        $format = strtolower($request->query('format', 'xlsx'));

        if ($format === 'csv') {
            $handle = fopen('php://memory', 'r+');
            fputcsv($handle, $headers);
            foreach ($sampleRows as $row) {
                fputcsv($handle, $row);
            }
            rewind($handle);
            $csv = stream_get_contents($handle);
            fclose($handle);

            return response($csv, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="TRF_Bulk_Import_Template.csv"',
            ]);
        }

        $xlsxContent = ExcelReader::createXlsx(
            headers: $headers,
            rows: $sampleRows
        );

        return response($xlsxContent, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="TRF_Bulk_Import_Template.xlsx"',
        ]);
    }

    /**
     * Import Travel Requisitions from uploaded Excel (.xlsx) / CSV file or pasted text.
     */
    public function importExcel(Request $request): RedirectResponse
    {
        $request->validate([
            'csv_data' => ['nullable', 'string'],
            'file' => ['nullable', 'file', 'mimes:csv,txt,xlsx', 'max:10240'],
        ]);

        $employeeId = $request->user()->id;

        $rawRows = [];
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $rawRows = ExcelReader::parseRows($file->getRealPath(), $file->getClientOriginalExtension());
        } elseif ($request->filled('csv_data')) {
            $lines = preg_split('/\r\n|\r|\n/', trim($request->input('csv_data')));
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if ($trimmed === '') continue;
                $rawRows[] = str_getcsv($trimmed);
            }
        } else {
            return back()->withErrors(['file' => 'Please select an Excel or CSV file to import.'])->withInput();
        }

        if (empty($rawRows)) {
            return back()->withErrors(['file' => 'The uploaded file contains no data rows.'])->withInput();
        }

        // Header mapping
        $headerMap = [
            'employee' => null,
            'department' => null,
            'route' => null,
            'purpose' => null,
            'travel_mode' => null,
            'travel_start' => null,
            'travel_end' => null,
            'advance' => null,
            'international' => null,
            'accommodation' => null,
        ];
        $hasHeader = false;

        $firstRow = $rawRows[0];
        foreach ($firstRow as $idx => $cell) {
            $colName = strtolower(trim((string)$cell));
            if (str_contains($colName, 'employee') || str_contains($colName, 'traveler') || str_contains($colName, 'staff') || str_contains($colName, 'user')) {
                $headerMap['employee'] = $idx;
                $hasHeader = true;
            } elseif (str_contains($colName, 'department') || str_contains($colName, 'dept')) {
                $headerMap['department'] = $idx;
                $hasHeader = true;
            } elseif (str_contains($colName, 'route') || str_contains($colName, 'itinerary') || str_contains($colName, 'from') || str_contains($colName, 'destination')) {
                $headerMap['route'] = $idx;
                $hasHeader = true;
            } elseif (str_contains($colName, 'purpose') || str_contains($colName, 'reason') || str_contains($colName, 'description')) {
                $headerMap['purpose'] = $idx;
                $hasHeader = true;
            } elseif (str_contains($colName, 'mode')) {
                $headerMap['travel_mode'] = $idx;
                $hasHeader = true;
            } elseif (str_contains($colName, 'start') || str_contains($colName, 'depart')) {
                $headerMap['travel_start'] = $idx;
                $hasHeader = true;
            } elseif (str_contains($colName, 'end') || str_contains($colName, 'return')) {
                $headerMap['travel_end'] = $idx;
                $hasHeader = true;
            } elseif (str_contains($colName, 'advance') || str_contains($colName, 'amount')) {
                $headerMap['advance'] = $idx;
                $hasHeader = true;
            } elseif (str_contains($colName, 'international') || str_contains($colName, 'intl')) {
                $headerMap['international'] = $idx;
                $hasHeader = true;
            } elseif (str_contains($colName, 'stay') || str_contains($colName, 'hotel') || str_contains($colName, 'accommodation')) {
                $headerMap['accommodation'] = $idx;
                $hasHeader = true;
            }
        }

        $startIndex = $hasHeader ? 1 : 0;
        $createdTrfs = [];
        $rejectedRows = [];

        for ($i = $startIndex; $i < count($rawRows); $i++) {
            $cols = $rawRows[$i];
            if (empty(array_filter($cols, fn ($v) => trim((string)$v) !== ''))) {
                continue;
            }

            // Extract values according to header map or positional heuristics
            $route = '';
            $purpose = 'Official Business Travel';
            $mode = 'Flight';
            $rawStart = null;
            $rawEnd = null;
            $rawAdvance = 0.0;
            $rawInternational = null;
            $accommodation = true;
            $targetUserId = $employeeId;
            $bookingType = 'self';

            if ($hasHeader && $headerMap['route'] !== null) {
                // Check if an explicit traveling employee was specified (e.g. by Admin or EA)
                if ($headerMap['employee'] !== null && !empty($cols[$headerMap['employee']])) {
                    $empVal = trim((string)$cols[$headerMap['employee']]);
                    if (!in_array(strtolower($empVal), ['self', '(leave blank for self)', 'leave blank for self', 'my own travel', 'own travel', '—', '-'], true)) {
                        $matchedUser = User::where('email', $empVal)
                            ->orWhere('id', $empVal)
                            ->orWhere('name', 'like', "%{$empVal}%")
                            ->first();
                        if ($matchedUser) {
                            $targetUserId = $matchedUser->id;
                            $bookingType = ($targetUserId === $employeeId) ? 'self' : 'on_behalf';
                        } else {
                            $rejectedRows[] = 'Row ' . ($i + 1) . ": Traveling employee '{$empVal}' was not found in employee directory.";
                            continue;
                        }
                    }
                }

                $route = trim((string)($cols[$headerMap['route']] ?? ''));
                if ($headerMap['purpose'] !== null && !empty($cols[$headerMap['purpose']])) {
                    $purpose = trim((string)$cols[$headerMap['purpose']]);
                }
                if ($headerMap['travel_mode'] !== null && !empty($cols[$headerMap['travel_mode']])) {
                    $mode = trim((string)$cols[$headerMap['travel_mode']]);
                }
                if ($headerMap['travel_start'] !== null) {
                    $rawStart = $cols[$headerMap['travel_start']] ?? null;
                }
                if ($headerMap['travel_end'] !== null) {
                    $rawEnd = $cols[$headerMap['travel_end']] ?? null;
                }
                if ($headerMap['advance'] !== null) {
                    $rawAdvance = $cols[$headerMap['advance']] ?? 0;
                }
                if ($headerMap['international'] !== null) {
                    $rawInternational = $cols[$headerMap['international']] ?? null;
                }
                if ($headerMap['accommodation'] !== null && isset($cols[$headerMap['accommodation']])) {
                    $accommodation = !preg_match('/no|false|0/i', trim((string)$cols[$headerMap['accommodation']]));
                }
            } else {
                // Positional heuristic: check if col 2 is travel mode or date
                $route = trim((string)($cols[0] ?? ''));
                $purpose = isset($cols[1]) && trim((string)$cols[1]) !== '' ? trim((string)$cols[1]) : 'Official Business Travel';

                $col2 = trim((string)($cols[2] ?? ''));
                if (in_array(strtolower($col2), ['flight', 'train', 'road', 'cab', 'road / cab', 'multi-modal'])) {
                    $mode = $col2;
                    $rawStart = $cols[3] ?? null;
                    $rawEnd = $cols[4] ?? null;
                    $rawAdvance = $cols[5] ?? 0;
                    $rawInternational = $cols[6] ?? null;
                    $accommodation = isset($cols[7]) ? !preg_match('/no|false|0/i', trim((string)$cols[7])) : true;
                } else {
                    $mode = 'Flight';
                    $rawStart = $cols[2] ?? null;
                    $rawEnd = $cols[3] ?? null;
                    $rawAdvance = $cols[4] ?? 0;
                    $rawInternational = $cols[5] ?? null;
                    $accommodation = isset($cols[6]) ? !preg_match('/no|false|0/i', trim((string)$cols[6])) : true;
                }
            }

            if ($route === '') {
                $rejectedRows[] = 'Row ' . ($i + 1) . ': Route / Destination is required.';
                continue;
            }

            $targetOverdue = Trf::findOverdueSettlementFor($targetUserId);
            if ($targetOverdue) {
                $empName = $targetOverdue->employee?->name ?? 'Employee';
                $refDate = $targetOverdue->settlementReferenceDate();
                $dateText = $refDate ? $refDate->format('d M Y') : 'unknown date';
                $advText = ((float) $targetOverdue->requested_advance > 0)
                    ? ' (₹' . number_format((float) $targetOverdue->requested_advance, 2) . ')'
                    : '';
                $rejectedRows[] = 'Row ' . ($i + 1) . ": {$empName} has an unsettled travel advance{$advText} from {$targetOverdue->trf_number} (ended {$dateText}, over 30 days ago). Reconcile via Reimbursement first.";
                continue;
            }

            $travelMode = $this->normalizeTravelMode($mode);
            $startDate = $this->parseDateValue($rawStart, now()->addDays(7)->toDateString());
            $endDate = $this->parseDateValue($rawEnd, date('Y-m-d', strtotime($startDate . ' +2 days')));
            if ($endDate < $startDate) {
                $endDate = $startDate;
            }

            $advance = $this->parseAdvanceValue($rawAdvance);
            $isInternational = $this->isInternationalRoute($rawInternational, $route);

            $trf = Trf::create([
                'trf_number' => $this->nextTrfNumber(),
                'employee_id' => $targetUserId,
                'booking_type' => $bookingType,
                'purpose' => $purpose,
                'route' => $route,
                'travel_mode' => $travelMode,
                'destinations' => $route,
                'is_international' => $isInternational,
                'travel_start' => $startDate,
                'travel_end' => $endDate,
                'insurance' => $isInternational,
                'visa_required' => $isInternational,
                'accommodation' => $accommodation,
                'requested_advance' => $advance,
                'status' => $isInternational ? 'pending_sg' : 'pending_asg',
            ]);

            $trf->approvals()->create([
                'level' => 1,
                'role_required' => $isInternational ? Roles::SG : Roles::ASG1.','.Roles::ASG2,
                'status' => 'pending',
            ]);

            $trf->writeAuditEntry('created', "TRF {$trf->trf_number} ({$route}) imported via bulk Excel/CSV.");
            $trf->load('employee');
            $createdTrfs[] = $trf;
        }

        if (empty($createdTrfs)) {
            $msg = !empty($rejectedRows)
                ? 'No TRFs could be imported: ' . implode(' ', $rejectedRows)
                : 'No valid travel requests could be parsed from the file. Please download the template and review format.';
            return back()->withErrors(['file' => $msg])->withInput();
        }

        $summary = [
            'total_rows' => count($rawRows) - ($hasHeader ? 1 : 0),
            'entered_count' => count($createdTrfs),
            'rejected_count' => count($rejectedRows),
            'created_trfs' => array_map(function ($trf) {
                return [
                    'id' => $trf->id,
                    'trf_number' => $trf->trf_number,
                    'employee_name' => $trf->employee?->name ?? '—',
                    'department' => $trf->employee?->department ?? 'General',
                    'route' => $trf->route,
                    'travel_mode' => $trf->travel_mode,
                    'travel_dates' => $trf->travel_start->format('d M') . ' – ' . $trf->travel_end->format('d M Y'),
                    'advance' => $trf->requested_advance > 0 ? '₹' . number_format($trf->requested_advance, 2) : '—',
                    'is_international' => (bool)$trf->is_international,
                    'clearance' => $trf->is_international ? 'Pending SG' : 'Pending ASG',
                ];
            }, $createdTrfs),
            'rejections' => $rejectedRows,
        ];

        $redirect = redirect()->route('trf')
            ->with('status', count($createdTrfs) . ' Travel Requisition' . (count($createdTrfs) > 1 ? 's' : '') . ' successfully imported and routed for approval.')
            ->with('import_summary', $summary);

        if (!empty($rejectedRows)) {
            $redirect->withErrors(['import_errors' => $rejectedRows]);
        }

        return $redirect;
    }

    public function show(Trf $trf): View
    {
        $trf->load([
            'employee',
            'approvals' => fn ($q) => $q->orderBy('level')->with('approver'),
            'flightLogs.vendor',
            'reimbursements',
            'evaluations.vendor',
            'boardingPasses.uploader',
            'mailTransactions.flightLog',
            'mailTransactions.sender',
            'auditLogs' => fn ($q) => $q->latest('id')->with('user'),
        ]);

        $user = request()->user();
        $canEdit = ($user?->id === $trf->employee_id || $user?->role === Roles::ADMIN_HEAD)
            && $trf->status !== 'approved';
        $canManageBoardingPasses = $user?->id === $trf->employee_id || $user?->role === Roles::ADMIN_HEAD;
        $latestPushback = $trf->approvals
            ->where('status', 'pushed_back')
            ->sortByDesc('id')
            ->first();

        return view('pages.trf-show', [
            'trf' => $trf,
            'pendingApproval' => $this->currentApprovalFor($trf, $user),
            'canEdit' => $canEdit,
            'canManageBoardingPasses' => $canManageBoardingPasses,
            'latestPushback' => $latestPushback,
        ]);
    }

    public function uploadBoardingPasses(Request $request, Trf $trf): RedirectResponse
    {
        $user = $request->user();
        $authorized = ($user?->id === $trf->employee_id) || ($user?->role === Roles::ADMIN_HEAD);
        abort_unless($authorized, 403, 'Only the travelling employee or Admin Head can upload boarding passes.');
        abort_unless($trf->status === 'approved', 422, 'Boarding passes can only be stored on an approved TRF.');

        $request->validate([
            'boarding_passes' => ['required', 'array', 'min:1'],
            'boarding_passes.*' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'share_with_traveller' => ['nullable', 'boolean'],
        ]);

        $uploaded = collect();
        foreach ($request->file('boarding_passes', []) as $file) {
            $stored = StorageHelper::storeUploadedFile($file, 'boarding-passes');
            $attachment = $trf->boardingPasses()->create([
                'file_path' => $stored['path'],
                'disk' => $stored['disk'],
                'original_name' => $file->getClientOriginalName(),
                'uploaded_by' => $user->id,
            ]);
            $uploaded->push($attachment);
        }

        $names = $uploaded->pluck('original_name')->filter()->implode(', ');
        $trf->writeAuditEntry(
            'boarding_pass_uploaded',
            "{$uploaded->count()} boarding pass(es) stored on {$trf->trf_number} by {$user->name}" . ($names ? " ({$names})" : '') . '.'
        );

        $statusMsg = "{$uploaded->count()} boarding " . ($uploaded->count() > 1 ? 'passes' : 'pass') . " uploaded and stored on {$trf->trf_number}.";

        if ($request->boolean('share_with_traveller')) {
            $mailTx = $this->dispatchBoardingPassEmail($trf, $uploaded, $user);
            if ($mailTx && $mailTx->status === 'sent') {
                $statusMsg .= " Shared with {$mailTx->recipient_email} via email.";
            } elseif ($mailTx) {
                $statusMsg .= " Email delivery to {$mailTx->recipient_email} was logged as failed.";
            }
        }

        return back()->with('status', $statusMsg);
    }

    public function shareBoardingPasses(Request $request, Trf $trf): RedirectResponse
    {
        $user = $request->user();
        $authorized = ($user?->id === $trf->employee_id) || ($user?->role === Roles::ADMIN_HEAD);
        abort_unless($authorized, 403, 'Only the travelling employee or Admin Head can share boarding passes.');
        abort_unless($trf->status === 'approved', 422, 'Boarding passes can only be shared for an approved TRF.');

        $attachmentId = $request->integer('attachment_id');
        $passes = $attachmentId > 0
            ? $trf->boardingPasses()->whereKey($attachmentId)->get()
            : $trf->boardingPasses()->get();

        if ($passes->isEmpty()) {
            return back()->withErrors(['boarding_passes' => 'No boarding passes stored on this TRF to share.']);
        }

        $mailTx = $this->dispatchBoardingPassEmail($trf, $passes, $user);
        if (! $mailTx) {
            return back()->withErrors(['boarding_passes' => 'Traveller email address is unavailable on this TRF.']);
        }

        if ($mailTx->status === 'sent') {
            return back()->with('status', "Boarding pass(es) emailed to {$mailTx->recipient_email} and archived on {$trf->trf_number}.");
        }

        return back()->withErrors([
            'boarding_passes' => 'Boarding pass email archived as failed' . ($mailTx->error_message ? ": {$mailTx->error_message}" : '.'),
        ]);
    }

    public function viewBoardingPass(Trf $trf, Attachment $attachment)
    {
        abort_unless(
            $attachment->attachable_type === Trf::class && (int) $attachment->attachable_id === $trf->id,
            404
        );

        $disk = $attachment->disk ?: 'public';
        if ($disk === 's3') {
            return redirect(StorageHelper::url($attachment->file_path, 's3'));
        }

        abort_unless(Storage::disk('public')->exists($attachment->file_path), 404, 'Boarding pass file not found.');

        return response()->file(Storage::disk('public')->path($attachment->file_path));
    }

    public function deleteBoardingPass(Request $request, Trf $trf, Attachment $attachment): RedirectResponse
    {
        $user = $request->user();
        $authorized = ($user?->id === $trf->employee_id) || ($user?->role === Roles::ADMIN_HEAD);
        abort_unless($authorized, 403, 'Unauthorized to remove boarding pass.');
        abort_unless(
            $attachment->attachable_type === Trf::class && (int) $attachment->attachable_id === $trf->id,
            404
        );

        $disk = $attachment->disk ?: 'public';
        if (Storage::disk($disk)->exists($attachment->file_path)) {
            Storage::disk($disk)->delete($attachment->file_path);
        }

        $fileName = $attachment->original_name ?: basename($attachment->file_path);
        $attachment->delete();
        $trf->writeAuditEntry('boarding_pass_removed', "Boarding pass '{$fileName}' removed from {$trf->trf_number} by {$user->name}.");

        return back()->with('status', "Removed boarding pass {$fileName}.");
    }

    /**
     * @param  Collection<int, Attachment>  $passes
     */
    private function dispatchBoardingPassEmail(Trf $trf, Collection $passes, ?User $actor): ?BookingMailTransaction
    {
        $trf->loadMissing('employee');
        $traveller = $trf->employee;
        if (! $traveller || empty($traveller->email)) {
            return null;
        }

        $subject = "Boarding Pass & Travel Documents — {$trf->trf_number} ({$trf->route})";
        $bodyHtml = view('emails.boarding-pass-shared', [
            'trf' => $trf,
            'boardingPasses' => $passes,
        ])->render();

        $status = 'sent';
        $errorMessage = null;
        try {
            Mail::to($traveller->email)->send(new BoardingPassSharedMail($trf, $passes));
        } catch (\Throwable $e) {
            $status = 'failed';
            $errorMessage = $e->getMessage();
            Log::error("Failed to send boarding pass email for {$trf->trf_number} to {$traveller->email}: " . $e->getMessage());
        }

        $tx = BookingMailTransaction::create([
            'trf_id' => $trf->id,
            'flight_log_id' => null,
            'mail_type' => 'boarding_pass',
            'recipient_email' => $traveller->email,
            'recipient_name' => $traveller->name,
            'subject' => $subject,
            'body_html' => $bodyHtml,
            'attachment_path' => $passes->first()?->file_path,
            'status' => $status,
            'error_message' => $errorMessage,
            'sent_by' => $actor?->id,
            'sent_at' => now(),
        ]);

        $auditAction = $status === 'sent' ? 'boarding_pass_emailed' : 'boarding_pass_email_failed';
        $auditDetail = $status === 'sent'
            ? "Boarding pass(es) for {$trf->trf_number} emailed to {$traveller->email}."
            : "Attempted to email boarding pass(es) for {$trf->trf_number} to {$traveller->email} but failed: {$errorMessage}";
        $trf->writeAuditEntry($auditAction, $auditDetail);

        return $tx;
    }

    public function approve(Request $request, Trf $trf): RedirectResponse
    {
        $approval = $this->currentApprovalFor($trf, $request->user());
        abort_unless($approval, 403, 'No pending approval step on this TRF matches your role.');

        $approval->update([
            'approver_id' => $request->user()->id,
            'status' => 'approved',
            'acted_at' => now(),
            'comments' => $request->input('comments'),
        ]);

        $trf->update(['status' => 'approved']);
        $trf->writeAuditEntry(
            'approved',
            "TRF {$trf->trf_number} endorsed by {$request->user()->name} (" . Roles::label($request->user()->role) . ")."
        );

        return back()->with('status', "TRF {$trf->trf_number} approved.");
    }

    public function hold(Request $request, Trf $trf): RedirectResponse
    {
        $request->validate(['comments' => ['required', 'string', 'max:1000']]);

        $approval = $this->currentApprovalFor($trf, $request->user());
        abort_unless($approval, 403, 'No pending approval step on this TRF matches your role.');

        $approval->update([
            'approver_id' => $request->user()->id,
            'status' => 'on_hold',
            'acted_at' => now(),
            'comments' => $request->input('comments'),
        ]);

        $trf->update(['status' => 'on_hold']);
        $trf->writeAuditEntry(
            'on_hold',
            "TRF {$trf->trf_number} placed on hold by {$request->user()->name} (" . Roles::label($request->user()->role) . "): {$request->input('comments')}"
        );

        return back()->with('status', "TRF {$trf->trf_number} has been placed on hold.");
    }

    public function reject(Request $request, Trf $trf): RedirectResponse
    {
        $request->validate(['comments' => ['required', 'string']]);

        $approval = $this->currentApprovalFor($trf, $request->user());
        abort_unless($approval, 403, 'No pending approval step on this TRF matches your role.');

        $approval->update([
            'approver_id' => $request->user()->id,
            'status' => 'rejected',
            'acted_at' => now(),
            'comments' => $request->input('comments'),
        ]);

        $trf->update(['status' => 'rejected']);
        $trf->writeAuditEntry(
            'rejected',
            "TRF {$trf->trf_number} rejected by {$request->user()->name} (" . Roles::label($request->user()->role) . "): {$request->input('comments')}"
        );

        return back()->with('status', "TRF {$trf->trf_number} rejected.");
    }

    public function pushback(Request $request, Trf $trf): RedirectResponse
    {
        $request->validate(['comments' => ['required', 'string']]);

        $approval = $this->currentApprovalFor($trf, $request->user());
        abort_unless($approval, 403, 'No pending approval step on this TRF matches your role.');

        $approval->update([
            'approver_id' => $request->user()->id,
            'status' => 'pushed_back',
            'acted_at' => now(),
            'comments' => $request->input('comments'),
        ]);

        $trf->update(['status' => 'pushed_back']);
        $trf->writeAuditEntry(
            'pushed_back',
            "TRF {$trf->trf_number} pushed back by {$request->user()->name} (" . Roles::label($request->user()->role) . "): {$request->input('comments')}"
        );

        return back()->with('status', "TRF {$trf->trf_number} pushed back with review comments.");
    }

    private function currentApprovalFor(Trf $trf, $user): ?Approval
    {
        // See VendorController::currentApprovalFor() for why this guard
        // exists — TRF has no reject(), only pushback(), but the same
        // "later state shouldn't be actionable" principle applies. Not
        // currently exploitable with TRF's single approval level, kept
        // consistent regardless.
        if (in_array($trf->status, ['pushed_back', 'rejected', 'approved'], true)) {
            return null;
        }

        // Segregation of Duties: an employee cannot approve or push back their own TRF
        if ($user && (int) $trf->employee_id === (int) $user->id) {
            return null;
        }

        $next = $trf->approvals()->whereIn('status', ['pending', 'on_hold'])->orderBy('level')->first();
        if (! $next) {
            return null;
        }

        $canAdminOverride = $user->role === Roles::ADMIN_HEAD && (bool) config('app.allow_admin_approval_override', true);
        if (in_array($user->role, explode(',', $next->role_required), true) || $canAdminOverride) {
            return $next;
        }

        return null;
    }

    private function nextTrfNumber(): string
    {
        do {
            $number = 'TRF-'.random_int(1000, 9999);
        } while (Trf::where('trf_number', $number)->exists());

        return $number;
    }

    private function parseDateValue($val, string $fallback): string
    {
        if (empty($val)) {
            return $fallback;
        }
        $val = trim((string)$val);
        if (is_numeric($val) && (float)$val > 30000 && (float)$val < 65000) {
            return date('Y-m-d', strtotime('1899-12-30 +' . (int)$val . ' days'));
        }
        $parsed = strtotime($val);
        if ($parsed !== false && $parsed > 0) {
            return date('Y-m-d', $parsed);
        }
        if (preg_match('/^(\d{1,2})[\/\.-](\d{1,2})[\/\.-](\d{4})$/', $val, $m)) {
            $ts = strtotime("{$m[3]}-{$m[2]}-{$m[1]}");
            if ($ts !== false) {
                return date('Y-m-d', $ts);
            }
        }
        return $fallback;
    }

    private function normalizeTravelMode(?string $mode): string
    {
        if (empty($mode)) {
            return 'Flight';
        }
        $m = strtolower(trim($mode));
        if (str_contains($m, 'train') || str_contains($m, 'rail')) {
            return 'Train';
        }
        if (str_contains($m, 'road') || str_contains($m, 'cab') || str_contains($m, 'car') || str_contains($m, 'taxi') || str_contains($m, 'bus')) {
            return 'Road / Cab';
        }
        if (str_contains($m, 'multi')) {
            return 'Multi-modal';
        }
        return 'Flight';
    }

    private function isInternationalRoute(?string $isInternationalVal, string $route): bool
    {
        if (!empty($isInternationalVal) && preg_match('/yes|true|1|intl|y/i', trim($isInternationalVal))) {
            return true;
        }
        return (bool) preg_match('/(dubai|dxb|singapore|sin|london|lhr|new york|jfk|frankfurt|fra|paris|cdg|tokyo|hnd|doha|doh|abu dhabi|auh|bangkok|bkk|kuala lumpur|kul|hong kong|hkg|zurich|zrh|geneva|gva|brussels|bru|toronto|yyz|washington|iad|sydney|syd|seoul|icn|riyadh|ruh|international)/i', $route);
    }

    private function parseAdvanceValue($val): float
    {
        if (empty($val)) {
            return 0.0;
        }
        $clean = preg_replace('/[^\d\.]/', '', (string)$val);
        return is_numeric($clean) ? max(0.0, (float)$clean) : 0.0;
    }

    /**
     * Format overdue settlement details for UI banners and dynamic booking-type checks.
     *
     * @return array<string, mixed>
     */
    private function formatOverduePayload(Trf $overdue): array
    {
        $latestClaim = $overdue->reimbursements->sortByDesc('id')->first();
        $reimbStatusText = match ($latestClaim?->status) {
            'pending_cfo' => "Claim {$latestClaim->claim_number} is pending CFO approval (not yet reconciled).",
            'pending_sg' => "Claim {$latestClaim->claim_number} is pending SG approval (not yet reconciled).",
            'rejected' => "Previous claim {$latestClaim->claim_number} was rejected — submit a new settlement claim.",
            default => 'No reimbursement claim has been filed yet.',
        };

        $refDate = $overdue->settlementReferenceDate();

        return [
            'trf_id' => $overdue->id,
            'trf_number' => $overdue->trf_number,
            'employee_id' => $overdue->employee_id,
            'employee_name' => $overdue->employee?->name ?? 'Employee',
            'route' => $overdue->route ?: 'Route not specified',
            'travel_end' => $refDate ? $refDate->format('d M Y') : '—',
            'days_overdue' => $overdue->overdueDays(),
            'requested_advance' => (float) $overdue->requested_advance,
            'advance_formatted' => (float) $overdue->requested_advance > 0
                ? '₹' . number_format((float) $overdue->requested_advance, 2)
                : 'Settlement Pending (₹0.00 advance)',
            'reimb_status_text' => $reimbStatusText,
            'trf_url' => route('trf.show', $overdue),
            'reimb_url' => route('reimb.create', ['trf_id' => $overdue->id]),
        ];
    }
}
