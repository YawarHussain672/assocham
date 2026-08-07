<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\DepartmentBudget;
use App\Models\PurchaseRequisition;
use App\Models\PurchaseRequisitionItem;
use App\Support\Roles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Purchase Requisition — second Phase 2 module, same shape as
 * VendorController (see project memory: erp-laravel-phase2-procurement).
 *
 * Workflow (deck p.10): Create (PR Sr. Executive) -> Basu (L1) -> Ambika
 * (L2) -> CFO (L3) -> SG (L4, final). Adds a real GST-inclusive budget
 * check against `department_budgets` on submission — the deck's "system
 * blocks creation... only CFO can override, via a logged Budget Exception"
 * is enforced as a hard block here since Exception Handling (Phase 4)
 * doesn't exist yet; see the memory file for that scope note.
 */
class PurchaseRequisitionController extends Controller
{
    private const CHAIN = [
        1 => Roles::PR_APPROVER_L1, // Basu
        2 => Roles::PR_APPROVER_L2, // Ambika
        3 => Roles::CFO,
        4 => Roles::SG,
    ];

    private const STATUS_FOR_LEVEL = [
        1 => 'pending_l1',
        2 => 'pending_l2',
        3 => 'pending_l3',
        4 => 'pending_l4',
    ];

    public function index(): View
    {
        $prs = PurchaseRequisition::with('items')->latest()->get();

        return view('pages.requisition', [
            'prs' => $prs,
            'canCreate' => request()->user()->role === Roles::PR_SR_EXEC,
        ]);
    }

    public function create(): View
    {
        abort_unless(request()->user()->role === Roles::PR_SR_EXEC, 403, 'Only the PR Sr. Executive creates requisitions.');

        return view('pages.requisition-create', [
            'departments' => DepartmentBudget::orderBy('department')->pluck('department'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->role === Roles::PR_SR_EXEC, 403, 'Only the PR Sr. Executive creates requisitions.');

        $data = $request->validate([
            'department' => ['required', 'string'],
            'required_by' => ['nullable', 'date'],
            'sole_source' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_name' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'items.*.gst_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $items = collect($data['items'])
            ->filter(fn ($i) => ! empty($i['item_name']) && $i['unit_cost'] !== null && $i['unit_cost'] !== '')
            ->map(fn ($i) => new PurchaseRequisitionItem([
                'item_name' => $i['item_name'],
                'quantity' => $i['quantity'] ?? 1,
                'unit' => $i['unit'] ?? null,
                'unit_cost' => $i['unit_cost'],
                'gst_percent' => $i['gst_percent'] ?? 18,
            ]));

        if ($items->isEmpty()) {
            return back()->withErrors(['items' => 'Add at least one item with a name and unit cost.'])->withInput();
        }

        $totalInclGst = $items->sum(fn (PurchaseRequisitionItem $item) => $item->lineTotalInclGst());

        $budget = DepartmentBudget::firstOrCreate(
            ['department' => $data['department'], 'fiscal_year' => DepartmentBudget::currentFiscalYear()],
            ['allocated_amount' => 0, 'utilised_amount' => 0, 'gst_rate' => 18]
        );

        $remaining = (float) $budget->allocated_amount - (float) $budget->utilised_amount;
        if ($totalInclGst > $remaining) {
            return back()->withErrors([
                'budget' => 'This PR (₹'.number_format($totalInclGst, 2).", GST-incl.) would exceed {$data['department']}'s remaining ".
                    DepartmentBudget::currentFiscalYear().' budget (₹'.number_format($remaining, 2).' of ₹'.number_format((float) $budget->allocated_amount, 2).' left). Proceeding requires a Budget Exception — file one from Governance → Exception Handling for a CFO override, then resubmit this PR.',
            ])->withInput();
        }

        $pr = PurchaseRequisition::create([
            'pr_number' => $this->nextPrNumber(),
            'department' => $data['department'],
            'required_by' => $data['required_by'] ?? null,
            'sole_source' => $request->boolean('sole_source'),
            'status' => self::STATUS_FOR_LEVEL[1],
            'current_level' => 1,
            'created_by' => $request->user()->id,
        ]);

        $items->each(fn (PurchaseRequisitionItem $item) => $item->status = 'pending');
        $pr->items()->saveMany($items);

        foreach (self::CHAIN as $level => $role) {
            $pr->approvals()->create(['level' => $level, 'role_required' => $role, 'status' => 'pending']);
        }

        $budget->increment('utilised_amount', $totalInclGst);

        return redirect()->route('requisition')
            ->with('status', "PR {$pr->pr_number} submitted — awaiting L1 approval (Basu).");
    }

    public function show(PurchaseRequisition $requisition): View
    {
        $requisition->load(['items', 'approvals' => fn ($q) => $q->orderBy('level'), 'creator']);

        return view('pages.requisition-show', [
            'pr' => $requisition,
            'pendingApproval' => $this->currentApprovalFor($requisition, request()->user()),
        ]);
    }

    public function approve(Request $request, PurchaseRequisition $requisition): RedirectResponse
    {
        $approval = $this->currentApprovalFor($requisition, $request->user());
        abort_unless($approval, 403, 'No pending approval step on this PR matches your role.');

        $approval->update([
            'approver_id' => $request->user()->id,
            'status' => 'approved',
            'acted_at' => now(),
            'comments' => $request->input('comments'),
        ]);

        $next = $requisition->approvals()->where('status', 'pending')->orderBy('level')->first();
        $requisition->update([
            'status' => $next ? self::STATUS_FOR_LEVEL[$next->level] : 'approved',
            'current_level' => $next ? $next->level : 4,
        ]);

        $message = $next
            ? 'Approved. Now awaiting '.Roles::label($next->role_required).'.'
            : "PR {$requisition->pr_number} is fully approved (L4 — SG).";

        return back()->with('status', $message);
    }

    public function reject(Request $request, PurchaseRequisition $requisition): RedirectResponse
    {
        $request->validate(['comments' => ['required', 'string']]);

        $approval = $this->currentApprovalFor($requisition, $request->user());
        abort_unless($approval, 403, 'No pending approval step on this PR matches your role.');

        $approval->update([
            'approver_id' => $request->user()->id,
            'status' => 'rejected',
            'acted_at' => now(),
            'comments' => $request->input('comments'),
        ]);

        $requisition->update(['status' => 'rejected']);

        // Release the budget reserved at submission time — see Checks & Balances
        // in the deck: rejected spend shouldn't keep counting against the department.
        $requisition->load('items');
        $budget = DepartmentBudget::where('department', $requisition->department)
            ->where('fiscal_year', DepartmentBudget::currentFiscalYear())
            ->first();
        $budget?->decrement('utilised_amount', $requisition->estimatedCostInclGst());

        return back()->with('status', "PR {$requisition->pr_number} rejected — reserved budget released.");
    }

    private function currentApprovalFor(PurchaseRequisition $pr, $user): ?Approval
    {
        // See VendorController::currentApprovalFor() for why this guard
        // exists — a rejection at any level must be terminal.
        if ($pr->status === 'rejected') {
            return null;
        }

        $next = $pr->approvals()->where('status', 'pending')->orderBy('level')->first();

        return ($next && $next->role_required === $user->role) ? $next : null;
    }

    private function nextPrNumber(): string
    {
        do {
            $number = 'PR/'.DepartmentBudget::currentFiscalYear().'/'.now()->format('Ymd').'-'.random_int(1000, 9999);
        } while (PurchaseRequisition::where('pr_number', $number)->exists());

        return $number;
    }
}
