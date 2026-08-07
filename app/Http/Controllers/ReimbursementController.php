<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\Reimbursement;
use App\Models\Trf;
use App\Support\Roles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
    public function index(): View
    {
        $reimbursements = Reimbursement::with(['trf', 'employee'])->latest()->get();

        return view('pages.reimb', ['reimbursements' => $reimbursements]);
    }

    public function create(): View
    {
        return view('pages.reimb-create', [
            'trfs' => Trf::where('employee_id', request()->user()->id)->where('status', 'approved')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'trf_id' => ['nullable', 'exists:trfs,id'],
            'claim_amount' => ['required', 'numeric', 'min:0'],
            'advance_amount' => ['nullable', 'numeric', 'min:0'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $trf = ! empty($data['trf_id']) ? Trf::find($data['trf_id']) : null;

        if ($trf) {
            abort_unless($trf->employee_id === $request->user()->id, 403, 'You can only claim reimbursement for your own travel.');
            abort_unless($trf->status === 'approved', 422, 'Reimbursement can only be claimed against an approved TRF.');
        } elseif (empty($data['reason'])) {
            return back()->withErrors(['reason' => 'A reason is mandatory for an off-TRF claim (SG override).'])->withInput();
        }

        $claimant = $request->user();
        $advance = $data['advance_amount'] ?? (float) ($trf->requested_advance ?? 0);
        $balance = $data['claim_amount'] - $advance;

        if (! $trf) {
            $routingFlag = 'exception';
            $approverRole = Roles::SG;
        } elseif (in_array($claimant->role, [Roles::ASG1, Roles::ASG2], true)) {
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
            'claim_amount' => $data['claim_amount'],
            'advance_amount' => $advance,
            'balance_amount' => $balance,
            'routing_flag' => $routingFlag,
            'reason' => $data['reason'] ?? null,
            'status' => $approverRole === Roles::SG ? 'pending_sg' : 'pending_cfo',
            'submitted_at' => now(),
        ]);

        $reimbursement->approvals()->create(['level' => 1, 'role_required' => $approverRole, 'status' => 'pending']);

        return redirect()->route('reimb')
            ->with('status', "{$reimbursement->claim_number} submitted — awaiting ".Roles::label($approverRole).' approval.');
    }

    public function show(Reimbursement $reimb): View
    {
        $reimb->load(['trf', 'employee', 'approvals' => fn ($q) => $q->orderBy('level')]);

        return view('pages.reimb-show', [
            'reimbursement' => $reimb,
            'pendingApproval' => $this->currentApprovalFor($reimb, request()->user()),
        ]);
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

        return back()->with('status', "{$reimb->claim_number} approved.");
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

        return back()->with('status', "{$reimb->claim_number} rejected.");
    }

    private function currentApprovalFor(Reimbursement $reimb, $user): ?Approval
    {
        // See VendorController::currentApprovalFor() for why this guard
        // exists (not currently exploitable with Reimbursement's single
        // approval level, kept consistent regardless).
        if ($reimb->status === 'rejected') {
            return null;
        }

        $next = $reimb->approvals()->where('status', 'pending')->orderBy('level')->first();

        return ($next && $next->role_required === $user->role) ? $next : null;
    }

    private function nextClaimNumber(): string
    {
        do {
            $number = 'REIM-'.random_int(1000, 9999);
        } while (Reimbursement::where('claim_number', $number)->exists());

        return $number;
    }
}
