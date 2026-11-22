<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\Attachment;
use App\Models\Reimbursement;
use App\Models\ReimbursementItem;
use App\Models\Trf;
use App\Support\Roles;
use App\Support\StorageHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Reimbursement & Advance Settlement — third Phase 3 module (see project
 * memory: erp-laravel-phase3-travel). Workflow (deck p.18): Select TRF
 * (auto-filled) -> Submit Claim -> CFO Approval (standard route) -> SG
 * Approval (if exception).
 *
 * Routing is an interpretive synthesis of the workflow diagram (p.18) and
 * the permission matrix (p.7) + Governance Authority Matrix reference
 * screenshot (p.43), which together describe THREE routing_flag cases
 * (already anticipated in the Phase 0 schema comment, unused until now):
 *   - 'standard': the claimant is a regular employee claiming against
 *     their own approved TRF -> CFO approves.
 *   - 'asg_ch_claim': the claimant IS an ASG (deck: "reimbursements filed
 *     by ASGs/CHs") -> routed to SG instead, since an ASG can't be the
 *     approver of their own expense claim. We have no "CH" role in the
 *     10-role spec, so only ASG1/ASG2 trigger this.
 *   - 'exception': off-TRF claims (no linked TRF) -> deck's Governance
 *     Authority Matrix: "Secretary General (SG) Override; bypasses TRF
 *     links; reason justification mandatory" -> routed to SG, `reason`
 *     required (schema column added in migration 2026_08_07_000022 — the
 *     original schema had no free-text field for this).
 *
 * Also implements the deck's other explicit Reimbursement rule: employees
 * with a settlement pending >30 days past their trip's travel_end are
 * blocked from raising a *new* TRF (Trf::findOverdueSettlementFor(),
 * checked in TrfController::store()) — this module is what lets them
 * clear that block.
 */
class ReimbursementController extends Controller
{
    public const EXPENSE_CATEGORIES = [
        'Hotel / Accommodation',
        'Local Conveyance / Cab',
        'Meals & Per Diem',
        'Air / Rail Ticket',
        'Visa & Forex',
        'Conference / Event Fee',
        'Other Official Expense',
    ];

    public function index(): View
    {
        $user = request()->user();
        $reimbursements = Reimbursement::with(['trf', 'employee', 'items', 'attachments', 'approvals'])->latest()->get();
        $pendingSettlementTrfs = $this->eligibleCompletedTrfsFor($user->id);

        $actionableIds = $reimbursements
            ->filter(fn (Reimbursement $r) => $this->currentApprovalFor($r, $user) !== null)
            ->pluck('id')
            ->all();

        return view('pages.reimb', [
            'reimbursements' => $reimbursements,
            'pendingSettlementTrfs' => $pendingSettlementTrfs,
            'actionableIds' => $actionableIds,
        ]);
    }

    public function create(): View
    {
        $userId = request()->user()->id;
        $completedTrfs = $this->eligibleCompletedTrfsFor($userId);

        $today = now()->startOfDay();
        $upcomingTrfs = Trf::where('employee_id', $userId)
            ->where('status', 'approved')
            ->whereDoesntHave('reimbursements', fn ($q) => $q->whereIn('status', ['pending_cfo', 'pending_sg', 'approved']))
            ->get()
            ->filter(function (Trf $t) use ($today) {
                $endDate = $t->travel_end ?? $t->travel_start;

                return $endDate && $endDate->copy()->startOfDay()->gt($today);
            })
            ->values();

        return view('pages.reimb-create', [
            'trfs' => $completedTrfs,
            'upcomingTrfs' => $upcomingTrfs,
            'categories' => self::EXPENSE_CATEGORIES,
        ]);
    }

    /**
     * Completed approved TRFs belonging to the user that have no pending or approved claim yet.
     */
    private function eligibleCompletedTrfsFor(int $userId)
    {
        $today = now()->startOfDay();

        return Trf::where('employee_id', $userId)
            ->where('status', 'approved')
            ->whereDoesntHave('reimbursements', fn ($q) => $q->whereIn('status', ['pending_cfo', 'pending_sg', 'approved']))
            ->with('flightLogs')
            ->orderByDesc('travel_end')
            ->get()
            ->filter(function (Trf $t) use ($today) {
                $endDate = $t->travel_end ?? $t->travel_start;

                return ! $endDate || $endDate->copy()->startOfDay()->lte($today);
            })
            ->values();
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'trf_id' => ['nullable', 'exists:trfs,id'],
            'claim_amount' => ['nullable', 'numeric', 'min:0'],
            'advance_amount' => ['nullable', 'numeric', 'min:0'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'receipts' => ['nullable', 'array'],
            'receipts.*' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'items' => ['nullable', 'array'],
            'items.*.expense_date' => ['required_with:items.*.amount', 'nullable', 'date'],
            'items.*.category' => ['required_with:items.*.amount', 'nullable', 'string', 'max:80'],
            'items.*.description' => ['nullable', 'string', 'max:255'],
            'items.*.amount' => ['required_with:items.*.expense_date', 'nullable', 'numeric', 'min:0.01'],
            'items.*.bill' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $rawItems = $data['items'] ?? [];
        $validItems = [];
        foreach ($rawItems as $idx => $itemRow) {
            if (! empty($itemRow['expense_date']) || ! empty($itemRow['category']) || ! empty($itemRow['amount'])) {
                if (empty($itemRow['expense_date']) || empty($itemRow['category']) || empty($itemRow['amount'])) {
                    return back()->withErrors([
                        'items' => 'Each itemised expense row must include a date, category, and amount.',
                    ])->withInput();
                }
                $validItems[$idx] = $itemRow;
            }
        }

        if (empty($validItems) && ! isset($data['claim_amount'])) {
            return back()->withErrors([
                'claim_amount' => 'Please enter at least one itemised expense or a total claim amount.',
            ])->withInput();
        }

        $totalClaimAmount = ! empty($validItems)
            ? round((float) collect($validItems)->sum(fn ($r) => (float) $r['amount']), 2)
            : (float) $data['claim_amount'];

        $trf = ! empty($data['trf_id']) ? Trf::find($data['trf_id']) : null;

        if ($trf) {
            abort_unless($trf->employee_id === $request->user()->id, 403, 'You can only claim reimbursement for your own travel.');
            abort_unless($trf->status === 'approved', 422, 'Reimbursement can only be claimed against an approved TRF.');

            $endDate = $trf->travel_end ?? $trf->travel_start;
            if ($endDate && $endDate->copy()->startOfDay()->gt(now()->startOfDay())) {
                return back()->withErrors([
                    'trf_id' => "Reimbursement can only be filed once travel for {$trf->trf_number} is completed (ends {$endDate->format('d M Y')}).",
                ])->withInput();
            }

            $existingClaim = $trf->reimbursements()
                ->whereIn('status', ['pending_cfo', 'pending_sg', 'approved'])
                ->first();
            if ($existingClaim) {
                return back()->withErrors([
                    'trf_id' => "A settlement claim ({$existingClaim->claim_number}) is already active or settled for {$trf->trf_number}.",
                ])->withInput();
            }
        } elseif (empty($data['reason'])) {
            return back()->withErrors(['reason' => 'A reason is mandatory for an off-TRF claim (SG override).'])->withInput();
        }

        $claimant = $request->user();
        $advance = $trf
            ? max((float) ($trf->requested_advance ?? 0), (float) ($data['advance_amount'] ?? 0))
            : (float) ($data['advance_amount'] ?? 0);
        $balance = round($totalClaimAmount - $advance, 2);

        if (! $trf) {
            $routingFlag = 'exception';
            $approverRole = Roles::SG;
        } elseif (in_array($claimant->role, [Roles::ASG1, Roles::ASG2, Roles::CFO], true)) {
            $routingFlag = 'asg_ch_claim';
            $approverRole = Roles::SG;
        } else {
            $routingFlag = 'standard';
            $approverRole = Roles::CFO;
        }

        $reimbursement = Reimbursement::create([
            'claim_number' => $this->nextClaimNumber(),
            'trf_id' => $trf?->id,
            'employee_id' => $claimant->id,
            'claim_amount' => $totalClaimAmount,
            'advance_amount' => $advance,
            'balance_amount' => $balance,
            'routing_flag' => $routingFlag,
            'reason' => $data['reason'] ?? null,
            'status' => $approverRole === Roles::SG ? 'pending_sg' : 'pending_cfo',
            'submitted_at' => now(),
        ]);

        foreach ($validItems as $idx => $itemRow) {
            $billPath = null;
            $billDisk = 'public';
            $billName = null;
            if ($request->hasFile("items.{$idx}.bill")) {
                $billFile = $request->file("items.{$idx}.bill");
                $stored = StorageHelper::storeUploadedFile($billFile, 'reimbursement-bills');
                $billPath = $stored['path'];
                $billDisk = $stored['disk'];
                $billName = $billFile->getClientOriginalName();
            }

            $reimbursement->items()->create([
                'expense_date' => $itemRow['expense_date'],
                'category' => $itemRow['category'],
                'description' => $itemRow['description'] ?? null,
                'amount' => $itemRow['amount'],
                'bill_path' => $billPath,
                'bill_disk' => $billDisk,
                'bill_name' => $billName,
            ]);
        }

        foreach ($request->file('receipts', []) as $file) {
            $stored = StorageHelper::storeUploadedFile($file, 'reimbursement-receipts');
            $reimbursement->attachments()->create([
                'file_path' => $stored['path'],
                'disk' => $stored['disk'],
                'original_name' => $file->getClientOriginalName(),
                'uploaded_by' => $claimant->id,
            ]);
        }

        $reimbursement->approvals()->create(['level' => 1, 'role_required' => $approverRole, 'status' => 'pending']);

        if ($trf) {
            $trf->writeAuditEntry(
                'settlement_submitted',
                "Settlement claim {$reimbursement->claim_number} (₹" . number_format((float) $reimbursement->claim_amount, 2) . ") submitted by {$claimant->name}."
            );
        }

        return redirect()->route('reimb')
            ->with('status', "{$reimbursement->claim_number} submitted — awaiting ".Roles::label($approverRole).' approval.');
    }

    public function show(Reimbursement $reimb): View
    {
        $reimb->load([
            'trf.flightLogs.vendor',
            'employee',
            'items',
            'attachments.uploader',
            'grievances.raisedBy',
            'approvals' => fn ($q) => $q->orderBy('level')->with('approver'),
        ]);

        return view('pages.reimb-show', [
            'reimbursement' => $reimb,
            'pendingApproval' => $this->currentApprovalFor($reimb, request()->user()),
        ]);
    }

    public function viewItemBill(Reimbursement $reimb, ReimbursementItem $item)
    {
        abort_unless((int) $item->reimbursement_id === $reimb->id && $item->bill_path, 404);

        $disk = $item->bill_disk ?: 'public';
        if ($disk === 's3') {
            return redirect(StorageHelper::url($item->bill_path, 's3'));
        }

        abort_unless(Storage::disk('public')->exists($item->bill_path), 404, 'Bill attachment not found.');

        return response()->file(Storage::disk('public')->path($item->bill_path));
    }

    public function viewAttachment(Reimbursement $reimb, Attachment $attachment)
    {
        abort_unless(
            $attachment->attachable_type === Reimbursement::class && (int) $attachment->attachable_id === $reimb->id,
            404
        );

        $disk = $attachment->disk ?: 'public';
        if ($disk === 's3') {
            return redirect(StorageHelper::url($attachment->file_path, 's3'));
        }

        abort_unless(Storage::disk('public')->exists($attachment->file_path), 404, 'Receipt file not found.');

        return response()->file(Storage::disk('public')->path($attachment->file_path));
    }

    public function approve(Request $request, Reimbursement $reimb): RedirectResponse
    {
        $approval = $this->currentApprovalFor($reimb, $request->user());
        abort_unless($approval, 403, 'No pending approval step on this claim matches your role.');

        $approval->update([
            'approver_id' => $request->user()->id,
            'status' => 'approved',
            'acted_at' => now(),
            'comments' => $request->input('comments'),
        ]);

        $reimb->update(['status' => 'approved']);

        if ($reimb->trf) {
            $reimb->trf->writeAuditEntry(
                'settlement_approved',
                "Settlement claim {$reimb->claim_number} approved by {$request->user()->name} — TRF reconciled."
            );
        }

        $settledNote = $reimb->trf
            ? " {$reimb->trf->trf_number} reconciled and any overdue advance travel block released."
            : '';

        return back()->with('status', "{$reimb->claim_number} approved & settled.{$settledNote}");
    }

    public function hold(Request $request, Reimbursement $reimb): RedirectResponse
    {
        $request->validate(['comments' => ['required', 'string', 'max:1000']]);

        $approval = $this->currentApprovalFor($reimb, $request->user());
        abort_unless($approval, 403, 'No pending approval step on this claim matches your role.');

        $approval->update([
            'approver_id' => $request->user()->id,
            'status' => 'on_hold',
            'acted_at' => now(),
            'comments' => $request->input('comments'),
        ]);

        $reimb->update(['status' => 'on_hold']);

        if ($reimb->trf) {
            $reimb->trf->writeAuditEntry(
                'settlement_on_hold',
                "Settlement claim {$reimb->claim_number} placed on hold by {$request->user()->name}: {$request->input('comments')}"
            );
        }

        return back()->with('status', "{$reimb->claim_number} has been placed on hold.");
    }

    public function reject(Request $request, Reimbursement $reimb): RedirectResponse
    {
        $request->validate(['comments' => ['required', 'string']]);

        $approval = $this->currentApprovalFor($reimb, $request->user());
        abort_unless($approval, 403, 'No pending approval step on this claim matches your role.');

        $approval->update([
            'approver_id' => $request->user()->id,
            'status' => 'rejected',
            'acted_at' => now(),
            'comments' => $request->input('comments'),
        ]);

        $reimb->update(['status' => 'rejected']);

        if ($reimb->trf) {
            $reimb->trf->writeAuditEntry(
                'settlement_rejected',
                "Settlement claim {$reimb->claim_number} declined by {$request->user()->name}: {$request->input('comments')}"
            );
        }

        return back()->with('status', "{$reimb->claim_number} rejected.");
    }

    private function currentApprovalFor(Reimbursement $reimb, $user): ?Approval
    {
        if (! $user || in_array($reimb->status, ['rejected', 'approved'], true)) {
            return null;
        }

        // Segregation of Duties: claimant cannot approve or reject their own reimbursement claim
        if ((int) $reimb->employee_id === (int) $user->id) {
            return null;
        }

        $next = $reimb->relationLoaded('approvals')
            ? $reimb->approvals->whereIn('status', ['pending', 'on_hold'])->sortBy('level')->first()
            : $reimb->approvals()->whereIn('status', ['pending', 'on_hold'])->orderBy('level')->first();

        if (! $next) {
            return null;
        }

        $canAdminOverride = $user->role === Roles::ADMIN_HEAD && (bool) config('app.allow_admin_approval_override', true);
        if ($next->role_required === $user->role || $canAdminOverride) {
            return $next;
        }

        return null;
    }

    private function nextClaimNumber(): string
    {
        do {
            $number = 'REIM-'.random_int(1000, 9999);
        } while (Reimbursement::where('claim_number', $number)->exists());

        return $number;
    }
}
