<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\Trf;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
    public function index(): View
    {
        $trfs = Trf::with(['employee', 'approvals'])->latest()->get();

        return view('pages.trf', ['trfs' => $trfs]);
    }

    public function create(): View
    {
        return view('pages.trf-create', ['users' => User::orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'booking_type' => ['required', 'in:self,on_behalf'],
            'employee_id' => ['nullable', 'exists:users,id'],
            'purpose' => ['nullable', 'string', 'max:255'],
            'route' => ['nullable', 'string', 'max:255'],
            'is_international' => ['nullable', 'boolean'],
            'travel_start' => ['nullable', 'date'],
            'travel_end' => ['nullable', 'date', 'after_or_equal:travel_start'],
            'insurance' => ['nullable', 'boolean'],
            'accommodation' => ['nullable', 'boolean'],
            'requested_advance' => ['nullable', 'numeric', 'min:0'],
        ]);

        $employeeId = ($data['booking_type'] === 'on_behalf' && ! empty($data['employee_id']))
            ? $data['employee_id']
            : $request->user()->id;

        $overdue = Trf::findOverdueSettlementFor($employeeId);
        if ($overdue) {
            $employeeName = $overdue->employee->name;

            return back()->withErrors([
                'employee_id' => "{$employeeName} has an unsettled travel advance from {$overdue->trf_number} (ended {$overdue->travel_end->format('d M Y')}, over 30 days ago). Settle it via Reimbursement before raising a new TRF.",
            ])->withInput();
        }

        $isInternational = $request->boolean('is_international');

        $trf = Trf::create([
            'trf_number' => $this->nextTrfNumber(),
            'employee_id' => $employeeId,
            'booking_type' => $data['booking_type'],
            'purpose' => $data['purpose'] ?? null,
            'route' => $data['route'] ?? null,
            'is_international' => $isInternational,
            'travel_start' => $data['travel_start'] ?? null,
            'travel_end' => $data['travel_end'] ?? null,
            'insurance' => $request->boolean('insurance'),
            'accommodation' => $request->boolean('accommodation'),
            'requested_advance' => $data['requested_advance'] ?? 0,
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

    public function importExcel(Request $request): RedirectResponse
    {
        $request->validate([
            'csv_data' => ['nullable', 'string'],
            'file' => ['nullable', 'file', 'mimes:csv,txt,xlsx', 'max:5120'],
        ]);

        $rawText = '';
        if ($request->hasFile('file')) {
            $rawText = file_get_contents($request->file('file')->getRealPath());
        } elseif ($request->filled('csv_data')) {
            $rawText = $request->input('csv_data');
        } else {
            return back()->withErrors(['file' => 'Please provide either a CSV file or paste tabular travel data.'])->withInput();
        }

        $lines = preg_split('/\r\n|\r|\n/', trim($rawText));
        $createdTrfs = [];
        $employeeId = $request->user()->id;

        $overdue = Trf::findOverdueSettlementFor($employeeId);
        if ($overdue) {
            return back()->withErrors([
                'file' => "You have an unsettled travel advance from {$overdue->trf_number} (ended {$overdue->travel_end->format('d M Y')}, over 30 days ago). Settle it via Reimbursement before importing new TRFs.",
            ])->withInput();
        }

        foreach ($lines as $idx => $line) {
            $trimmed = trim($line);
            if ($trimmed === '') continue;
            $cols = str_getcsv($trimmed);
            // Skip header
            if ($idx === 0 && preg_match('/route|purpose|destination/i', $cols[0] ?? '')) {
                continue;
            }
            if (!empty($cols[0])) {
                $route = trim($cols[0]);
                $purpose = isset($cols[1]) ? trim($cols[1]) : 'Business Travel';
                $start = isset($cols[2]) && strtotime($cols[2]) ? date('Y-m-d', strtotime($cols[2])) : now()->addDays(7)->toDateString();
                $end = isset($cols[3]) && strtotime($cols[3]) ? date('Y-m-d', strtotime($cols[3])) : now()->addDays(10)->toDateString();
                $advance = isset($cols[4]) && is_numeric($cols[4]) ? (float)$cols[4] : 0.0;
                $isInternational = isset($cols[5]) && preg_match('/yes|true|1|intl/i', $cols[5]);

                $trf = Trf::create([
                    'trf_number' => $this->nextTrfNumber(),
                    'employee_id' => $employeeId,
                    'booking_type' => 'self',
                    'purpose' => $purpose,
                    'route' => $route,
                    'is_international' => $isInternational,
                    'travel_start' => $start,
                    'travel_end' => $end,
                    'insurance' => $isInternational,
                    'accommodation' => true,
                    'requested_advance' => $advance,
                    'status' => $isInternational ? 'pending_sg' : 'pending_asg',
                ]);

                $trf->approvals()->create([
                    'level' => 1,
                    'role_required' => $isInternational ? Roles::SG : Roles::ASG1.','.Roles::ASG2,
                    'status' => 'pending',
                ]);

                $trf->writeAuditEntry('created', "TRF {$trf->trf_number} ({$route}) imported via bulk Excel/CSV.");
                $createdTrfs[] = $trf;
            }
        }

        if (empty($createdTrfs)) {
            return back()->withErrors(['file' => 'No valid travel requests could be parsed. Expected format: Route, Purpose, Start Date, End Date, Advance, International (yes/no)'])->withInput();
        }

        return redirect()->route('trf')
            ->with('status', count($createdTrfs) . " Travel Requisitions successfully imported and routed for approval.");
    }

    public function show(Trf $trf): View
    {
        $trf->load([
            'employee',
            'approvals' => fn ($q) => $q->orderBy('level')->with('approver'),
            'flightLogs.vendor',
            'reimbursements',
        ]);

        return view('pages.trf-show', [
            'trf' => $trf,
            'pendingApproval' => $this->currentApprovalFor($trf, request()->user()),
        ]);
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

        return back()->with('status', "TRF {$trf->trf_number} approved.");
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

        return back()->with('status', "TRF {$trf->trf_number} pushed back with review comments.");
    }

    private function currentApprovalFor(Trf $trf, $user): ?Approval
    {
        // See VendorController::currentApprovalFor() for why this guard
        // exists — TRF has no reject(), only pushback(), but the same
        // "later state shouldn't be actionable" principle applies. Not
        // currently exploitable with TRF's single approval level, kept
        // consistent regardless.
        if ($trf->status === 'pushed_back') {
            return null;
        }

        $next = $trf->approvals()->where('status', 'pending')->orderBy('level')->first();
        if (! $next) {
            return null;
        }

        return in_array($user->role, explode(',', $next->role_required), true) ? $next : null;
    }

    private function nextTrfNumber(): string
    {
        do {
            $number = 'TRF-'.random_int(1000, 9999);
        } while (Trf::where('trf_number', $number)->exists());

        return $number;
    }
}
