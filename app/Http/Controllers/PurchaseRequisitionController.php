<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\DepartmentBudget;
use App\Models\ItemCatalog;
use App\Models\ProjectBudget;
use App\Models\PurchaseRequisition;
use App\Models\PurchaseRequisitionItem;
use App\Services\ExcelReader;
use App\Support\Roles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
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

    /**
     * Builds the approval chain dynamically based on OPEX/COPEX classification and financial value.
     * Rule:
     * - For COPEX items, even if the amount is below ₹5 lakh, the PR must still go through SG approval.
     * - For OPEX items, if the amount is below ₹5 lakh (500,000), approval concludes at CFO (Level 3).
     * - For OPEX items >= ₹5 lakh, PR escalates to SG approval (Level 4).
     */
    public static function buildApprovalChain(bool $isCopex, float $totalInclGst, ?string $approverL1 = null, ?string $approverL2 = null): array
    {
        $chain = [
            1 => ($approverL1 && in_array($approverL1, [Roles::DSG, Roles::HEAD_OF_STATES, Roles::ASG1, Roles::ASG2, Roles::PR_APPROVER_L1], true))
                ? $approverL1
                : self::CHAIN[1],
            2 => ($approverL2 && in_array($approverL2, [Roles::DSG, Roles::HEAD_OF_STATES, Roles::ASG1, Roles::ASG2, Roles::PR_APPROVER_L2], true))
                ? $approverL2
                : self::CHAIN[2],
            3 => Roles::CFO,
        ];

        // COPEX always mandates SG approval regardless of value; OPEX mandates SG only when >= ₹5,00,000
        if ($isCopex || $totalInclGst >= 500000.0) {
            $chain[4] = Roles::SG;
        }

        return $chain;
    }

    public static function resolveItemExpenseType(array $itemData, ?ItemCatalog $catalogItem = null): ?string
    {
        if (! empty($itemData['expense_type']) && in_array(strtolower($itemData['expense_type']), ['opex', 'copex', 'capex'], true)) {
            return strtolower($itemData['expense_type']);
        }

        if ($catalogItem && ! empty($catalogItem->expense_type)) {
            return strtolower($catalogItem->expense_type);
        }

        $itemName = trim((string) ($itemData['item_name'] ?? ''));
        if ($itemName !== '') {
            $matched = ItemCatalog::where('name', $itemName)->first();
            if ($matched && ! empty($matched->expense_type)) {
                return strtolower($matched->expense_type);
            }
        }

        return null;
    }

    public function index(): View
    {
        $prs = PurchaseRequisition::with(['items', 'approvals', 'creator', 'projectBudget'])->latest()->get();

        $departments = DepartmentBudget::orderBy('department')
            ->pluck('department')
            ->merge($prs->pluck('department'))
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $projectBudgets = ProjectBudget::where('fiscal_year', DepartmentBudget::currentFiscalYear())
            ->orderBy('project_name')
            ->get();

        return view('pages.requisition', [
            'prs' => $prs,
            'departments' => $departments,
            'projectBudgets' => $projectBudgets,
            'canCreate' => in_array(request()->user()->role, [Roles::PR_SR_EXEC, Roles::ADMIN_HEAD], true),
        ]);
    }

    public function create(): View
    {
        abort_unless(in_array(request()->user()->role, [Roles::PR_SR_EXEC, Roles::ADMIN_HEAD], true), 403, 'Only the PR Sr. Executive or Admin Head creates requisitions.');

        $budgets = DepartmentBudget::where('fiscal_year', DepartmentBudget::currentFiscalYear())
            ->get()
            ->keyBy('department')
            ->map(fn ($b) => [
                'allocated' => (float) $b->allocated_amount,
                'utilised' => (float) $b->utilised_amount,
                'available' => max(0, (float) $b->allocated_amount - (float) $b->utilised_amount),
            ]);

        $projectBudgets = ProjectBudget::where('fiscal_year', DepartmentBudget::currentFiscalYear())
            ->orderBy('department')
            ->orderBy('project_name')
            ->get();

        $itemCatalogs = ItemCatalog::active()
            ->orderBy('expense_type')
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        return view('pages.requisition-create', [
            'departments' => DepartmentBudget::orderBy('department')->pluck('department')->unique(),
            'departmentBudgets' => $budgets,
            'projectBudgets' => $projectBudgets,
            'itemCatalogs' => $itemCatalogs,
        ]);
    }

    public function edit(PurchaseRequisition $requisition): View
    {
        $user = request()->user();
        $isApproverInChain = $requisition->approvals()->where('role_required', $user?->role)->exists()
            || in_array($user?->role, [Roles::PR_APPROVER_L1, Roles::PR_APPROVER_L2, Roles::CFO, Roles::SG, Roles::ASG1, Roles::ASG2, Roles::DSG, Roles::HEAD_OF_STATES], true);
        $canEdit = in_array($user?->role, [Roles::PR_SR_EXEC, Roles::ADMIN_HEAD], true)
            || ($user?->id === $requisition->created_by)
            || $isApproverInChain;

        abort_unless($canEdit, 403, 'You do not have permission to modify this requisition.');
        abort_if($requisition->status === 'approved', 403, 'Approved requisitions cannot be modified.');

        $requisition->load(['items', 'versions.user', 'creator', 'projectBudget']);

        $budgets = DepartmentBudget::where('fiscal_year', DepartmentBudget::currentFiscalYear())
            ->get()
            ->keyBy('department')
            ->map(fn ($b) => [
                'allocated' => (float) $b->allocated_amount,
                'utilised' => (float) $b->utilised_amount,
                'available' => max(0, (float) $b->allocated_amount - (float) $b->utilised_amount),
            ]);

        $projectBudgets = ProjectBudget::where('fiscal_year', DepartmentBudget::currentFiscalYear())
            ->orderBy('department')
            ->orderBy('project_name')
            ->get();

        $itemCatalogs = ItemCatalog::active()
            ->orderBy('expense_type')
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        return view('pages.requisition-edit', [
            'pr' => $requisition,
            'departments' => DepartmentBudget::orderBy('department')->pluck('department')->unique(),
            'departmentBudgets' => $budgets,
            'projectBudgets' => $projectBudgets,
            'itemCatalogs' => $itemCatalogs,
        ]);
    }

    public function update(Request $request, PurchaseRequisition $requisition): RedirectResponse
    {
        $user = $request->user();
        $isApproverInChain = $requisition->approvals()->where('role_required', $user?->role)->exists()
            || in_array($user?->role, [Roles::PR_APPROVER_L1, Roles::PR_APPROVER_L2, Roles::CFO, Roles::SG, Roles::ASG1, Roles::ASG2, Roles::DSG, Roles::HEAD_OF_STATES], true);
        $canEdit = in_array($user?->role, [Roles::PR_SR_EXEC, Roles::ADMIN_HEAD], true)
            || ($user?->id === $requisition->created_by)
            || $isApproverInChain;

        abort_unless($canEdit, 403, 'You do not have permission to modify this requisition.');
        abort_if($requisition->status === 'approved', 403, 'Approved requisitions cannot be modified.');

        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'event_name' => ['nullable', 'string', 'max:255'],
            'project_budget_id' => ['nullable', 'exists:project_budgets,id'],
            'department' => ['required', 'string'],
            'region' => ['nullable', 'string', 'max:255'],
            'required_by' => ['nullable', 'date'],
            'sole_source' => ['nullable', 'boolean'],
            'expense_type' => ['nullable', 'string', 'in:opex,copex,capex'],
            'description' => ['nullable', 'string', 'max:2000'],
            'special_instructions' => ['nullable', 'string', 'max:2000'],
            'resubmit_notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_name' => ['required', 'string', 'max:255'],
            'items.*.expense_type' => ['nullable', 'string', 'in:opex,copex,capex'],
            'items.*.item_catalog_id' => ['nullable', 'exists:item_catalogs,id'],
            'items.*.description' => ['nullable', 'string', 'max:2000'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'items.*.gst_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $catalogIds = collect($data['items'])->pluck('item_catalog_id')->filter()->unique()->all();
        $catalogItems = ! empty($catalogIds) ? ItemCatalog::whereIn('id', $catalogIds)->get()->keyBy('id') : collect();
        $names = collect($data['items'])->pluck('item_name')->filter()->unique()->all();
        $catalogByName = ! empty($names) ? ItemCatalog::whereIn('name', $names)->get()->keyBy('name') : collect();

        $newItems = collect($data['items'])
            ->filter(fn ($i) => ! empty($i['item_name']))
            ->map(function ($i) use ($catalogItems, $catalogByName) {
                $cat = ! empty($i['item_catalog_id']) ? ($catalogItems[$i['item_catalog_id']] ?? null) : null;
                if (! $cat && ! empty($i['item_name'])) {
                    $cat = $catalogByName[$i['item_name']] ?? null;
                }

                $expenseType = self::resolveItemExpenseType($i, $cat);
                $unitCost = (isset($i['unit_cost']) && $i['unit_cost'] !== '' && $i['unit_cost'] !== null)
                    ? (float) $i['unit_cost']
                    : ($cat ? (float) $cat->fixed_price : 0.00);

                $unit = ! empty($i['unit']) ? $i['unit'] : ($cat?->unit ?? 'Units');
                $gst = (isset($i['gst_percent']) && $i['gst_percent'] !== '' && $i['gst_percent'] !== null)
                    ? (float) $i['gst_percent']
                    : ($cat ? (float) $cat->gst_percent : 18.00);

                $desc = ! empty($i['description']) ? $i['description'] : ($cat?->description ?? null);

                return new PurchaseRequisitionItem([
                    'item_name' => $i['item_name'],
                    'description' => $desc,
                    'quantity' => $i['quantity'] ?? 1,
                    'unit' => $unit,
                    'unit_cost' => $unitCost,
                    'gst_percent' => $gst,
                    'expense_type' => $expenseType,
                    'item_catalog_id' => $cat?->id,
                    'status' => 'pending',
                ]);
            });

        if ($newItems->isEmpty()) {
            return back()->withErrors(['items' => 'Add at least one item with a name.'])->withInput();
        }

        $newTotal = round((float) $newItems->sum(fn ($i) => $i->lineTotalInclGst()), 2);

        $rawPrExp = strtolower((string) ($request->input('expense_type') ?? ''));
        $isCopex = in_array($rawPrExp, ['capex', 'copex'], true) || $newItems->contains(fn ($it) => $it->isCopex());
        if ($isCopex) {
            $overallExpenseType = in_array($rawPrExp, ['capex', 'copex'], true) ? $rawPrExp : ($newItems->first(fn ($it) => $it->isCopex())?->expense_type ?? 'capex');
        } else {
            $overallExpenseType = $rawPrExp === 'opex' ? 'opex' : ($newItems->contains(fn ($it) => $it->expense_type === 'opex') ? 'opex' : null);
        }

        $chain = self::buildApprovalChain(
            isCopex: $isCopex,
            totalInclGst: $newTotal
        );

        $oldDepartment = $requisition->department;
        $newDepartment = $data['department'];
        $oldProjectId = $requisition->project_budget_id;
        $newProjectId = ! empty($data['project_budget_id']) ? (int) $data['project_budget_id'] : null;
        $oldReserved = $requisition->status === 'rejected' ? 0.0 : round($requisition->estimatedCostInclGst(true), 2);
        $isResubmission = ($requisition->status === 'rejected');
        $budgetError = null;
        $alertWarning = null;

        DB::transaction(function () use (
            $request,
            $requisition,
            $data,
            $newItems,
            $newTotal,
            $oldDepartment,
            $newDepartment,
            $oldProjectId,
            $newProjectId,
            $oldReserved,
            $isResubmission,
            $user,
            $isApproverInChain,
            $chain,
            $overallExpenseType,
            &$budgetError,
            &$alertWarning
        ) {
            if ($oldDepartment === $newDepartment) {
                $budget = DepartmentBudget::lockOrCreate($newDepartment);
                $deltaBudget = round($newTotal - $oldReserved, 2);

                if ($deltaBudget > 0 && ! $budget->hasHeadroomFor($deltaBudget)) {
                    $budgetError = 'Adjusted PR total (₹'.number_format($newTotal, 2).") exceeds {$newDepartment}'s remaining budget (₹".number_format($budget->remainingAmount(), 2).').';

                    return;
                }
            } else {
                // Lock old & new department rows deterministically to prevent deadlocks
                $newBudget = DepartmentBudget::lockOrCreate($newDepartment);
                $oldBudget = DepartmentBudget::lockOrCreate($oldDepartment);

                if (! $newBudget->hasHeadroomFor($newTotal)) {
                    $budgetError = 'Adjusted PR total (₹'.number_format($newTotal, 2).") exceeds {$newDepartment}'s remaining budget (₹".number_format($newBudget->remainingAmount(), 2).').';

                    return;
                }
            }

            $oldProjBudget = $oldProjectId ? ProjectBudget::whereKey($oldProjectId)->lockForUpdate()->first() : null;
            $newProjBudget = $newProjectId ? ProjectBudget::whereKey($newProjectId)->lockForUpdate()->first() : null;

            if ($newProjBudget && strcasecmp(trim((string) $newProjBudget->department), trim((string) $newDepartment)) !== 0) {
                $budgetError = "Selected project '{$newProjBudget->project_name}' belongs to {$newProjBudget->department}, not {$newDepartment}. Please pick a project under {$newDepartment} or switch the department.";

                return;
            }

            if ($oldProjectId === $newProjectId) {
                if ($newProjBudget) {
                    $deltaProj = round($newTotal - $oldReserved, 2);
                    if ($deltaProj > 0 && ! $newProjBudget->hasHeadroomFor($deltaProj)) {
                        $budgetError = "Adjusted PR total (₹" . number_format($newTotal, 2) . ") exceeds project '{$newProjBudget->project_name}' remaining budget (₹" . number_format($newProjBudget->remainingAmount(), 2) . ").";
                        return;
                    }
                }
            } else {
                if ($newProjBudget && ! $newProjBudget->hasHeadroomFor($newTotal)) {
                    $budgetError = "Adjusted PR total (₹" . number_format($newTotal, 2) . ") exceeds project '{$newProjBudget->project_name}' remaining budget (₹" . number_format($newProjBudget->remainingAmount(), 2) . ").";
                    return;
                }
            }

            $eventName = $newProjBudget ? $newProjBudget->project_name : ($data['event_name'] ?? null);

            // Update PR fields
            $requisition->update([
                'title' => $data['title'] ?? null,
                'event_name' => $eventName,
                'project_budget_id' => $newProjectId,
                'department' => $newDepartment,
                'region' => $data['region'] ?? null,
                'required_by' => $data['required_by'] ?? null,
                'sole_source' => $request->boolean('sole_source'),
                'expense_type' => $overallExpenseType,
                'description' => $data['description'] ?? null,
                'special_instructions' => $data['special_instructions'] ?? null,
                'status' => self::STATUS_FOR_LEVEL[1],
                'current_level' => 1,
            ]);

            // Replace items
            $requisition->items()->delete();
            $requisition->items()->saveMany($newItems);

            // Reset approvals back to L1
            $requisition->approvals()->delete();
            foreach ($chain as $level => $role) {
                $requisition->approvals()->create(['level' => $level, 'role_required' => $role, 'status' => 'pending']);
            }

            if ($oldDepartment === $newDepartment) {
                $deltaBudget = round($newTotal - $oldReserved, 2);
                if ($deltaBudget != 0.0) {
                    $alertWarning = $budget->applyDelta($deltaBudget, "PR {$requisition->pr_number}");
                }
            } else {
                if ($oldReserved > 0) {
                    $oldBudget->applyDelta(-$oldReserved);
                }
                $alertWarning = $newBudget->applyDelta($newTotal, "PR {$requisition->pr_number}");
            }

            // Apply project delta
            if ($oldProjectId === $newProjectId) {
                if ($newProjBudget) {
                    $deltaProj = round($newTotal - $oldReserved, 2);
                    if ($deltaProj != 0.0) {
                        $projWarning = $newProjBudget->applyDelta($deltaProj, "PR {$requisition->pr_number}");
                        if ($projWarning && ! $alertWarning) {
                            $alertWarning = $projWarning;
                        }
                    }
                }
            } else {
                if ($oldProjBudget && $oldReserved > 0) {
                    $oldProjBudget->applyDelta(-$oldReserved);
                }
                if ($newProjBudget) {
                    $projWarning = $newProjBudget->applyDelta($newTotal, "PR {$requisition->pr_number}");
                    if ($projWarning && ! $alertWarning) {
                        $alertWarning = $projWarning;
                    }
                }
            }

            $isUpperApprover = $isApproverInChain && ! in_array($user->role, [Roles::PR_SR_EXEC]);
            $event = $isResubmission ? 'resubmitted' : ($isUpperApprover ? 'approver_modified' : 'edited');
            $title = $isResubmission ? 'Re-submitted for Approval' : ($isUpperApprover ? 'Modified by Approver & Re-sent' : 'Requisition Modified');
            $summary = $isResubmission
                ? "Corrected and re-submitted to Level 1 approval with {$newItems->count()} item(s), new budget total: ₹" . number_format($newTotal, 2)
                : ($isUpperApprover
                    ? "Upper hierarchy approver ({$user->name} - " . Roles::label($user->role) . ") modified requisition details/items and re-sent for Level 1 approval. New total: ₹" . number_format($newTotal, 2)
                    : "Requisition details and line items updated ({$newItems->count()} items, total ₹" . number_format($newTotal, 2) . ")");

            $requisition->recordVersion(
                event: $event,
                actionTitle: $title,
                changeSummary: $summary,
                comments: $request->input('resubmit_notes'),
                changes: [
                    'department' => $requisition->department,
                    'title' => $requisition->title,
                    'event_name' => $requisition->event_name,
                    'project_budget_id' => $requisition->project_budget_id,
                    'region' => $requisition->region,
                    'items_count' => $newItems->count(),
                    'total_cost' => $newTotal,
                ]
            );
        });

        if ($budgetError !== null) {
            return back()->withErrors(['budget' => $budgetError])->withInput();
        }

        $redirect = redirect()->route('requisition.show', $requisition)
            ->with('status', "PR {$requisition->pr_number} successfully updated and re-sent to Level 1 (" . Roles::label(self::CHAIN[1]) . ") for review.");

        if ($alertWarning !== null) {
            $redirect->with('warning', $alertWarning);
        }

        return $redirect;
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, [Roles::PR_SR_EXEC, Roles::ADMIN_HEAD], true), 403, 'Only the PR Sr. Executive or Admin Head creates requisitions.');

        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'event_name' => ['nullable', 'string', 'max:255'],
            'project_budget_id' => ['nullable', 'exists:project_budgets,id'],
            'department' => ['required', 'string'],
            'region' => ['nullable', 'string', 'max:255'],
            'required_by' => ['nullable', 'date'],
            'sole_source' => ['nullable', 'boolean'],
            'expense_type' => ['nullable', 'string', 'in:opex,copex,capex'],
            'description' => ['nullable', 'string', 'max:2000'],
            'special_instructions' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_name' => ['required', 'string', 'max:255'],
            'items.*.expense_type' => ['nullable', 'string', 'in:opex,copex,capex'],
            'items.*.item_catalog_id' => ['nullable', 'exists:item_catalogs,id'],
            'items.*.description' => ['nullable', 'string', 'max:2000'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'items.*.gst_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ], [
            'items.*.item_name.required' => 'The Item Name field is mandatory for every PR line item.',
        ]);

        $catalogIds = collect($data['items'])->pluck('item_catalog_id')->filter()->unique()->all();
        $catalogItems = ! empty($catalogIds) ? ItemCatalog::whereIn('id', $catalogIds)->get()->keyBy('id') : collect();
        $names = collect($data['items'])->pluck('item_name')->filter()->unique()->all();
        $catalogByName = ! empty($names) ? ItemCatalog::whereIn('name', $names)->get()->keyBy('name') : collect();

        $items = collect($data['items'])
            ->filter(fn ($i) => ! empty($i['item_name']))
            ->map(function ($i) use ($catalogItems, $catalogByName) {
                $cat = ! empty($i['item_catalog_id']) ? ($catalogItems[$i['item_catalog_id']] ?? null) : null;
                if (! $cat && ! empty($i['item_name'])) {
                    $cat = $catalogByName[$i['item_name']] ?? null;
                }

                $expenseType = self::resolveItemExpenseType($i, $cat);
                $unitCost = (isset($i['unit_cost']) && $i['unit_cost'] !== '' && $i['unit_cost'] !== null)
                    ? (float) $i['unit_cost']
                    : ($cat ? (float) $cat->fixed_price : 0.00);

                $unit = ! empty($i['unit']) ? $i['unit'] : ($cat?->unit ?? 'Units');
                $gst = (isset($i['gst_percent']) && $i['gst_percent'] !== '' && $i['gst_percent'] !== null)
                    ? (float) $i['gst_percent']
                    : ($cat ? (float) $cat->gst_percent : 18.00);

                $desc = ! empty($i['description']) ? $i['description'] : ($cat?->description ?? null);

                return new PurchaseRequisitionItem([
                    'item_name' => $i['item_name'],
                    'description' => $desc,
                    'quantity' => $i['quantity'] ?? 1,
                    'unit' => $unit,
                    'unit_cost' => $unitCost,
                    'gst_percent' => $gst,
                    'expense_type' => $expenseType,
                    'item_catalog_id' => $cat?->id,
                    'status' => 'pending',
                ]);
            });

        if ($items->isEmpty()) {
            return back()->withErrors(['items' => 'Add at least one item with a name.'])->withInput();
        }

        $totalInclGst = round((float) $items->sum(fn (PurchaseRequisitionItem $item) => $item->lineTotalInclGst()), 2);

        $rawPrExp = strtolower((string) ($request->input('expense_type') ?? ''));
        $isCopex = in_array($rawPrExp, ['capex', 'copex'], true) || $items->contains(fn ($it) => $it->isCopex());
        if ($isCopex) {
            $overallExpenseType = in_array($rawPrExp, ['capex', 'copex'], true) ? $rawPrExp : ($items->first(fn ($it) => $it->isCopex())?->expense_type ?? 'capex');
        } else {
            $overallExpenseType = $rawPrExp === 'opex' ? 'opex' : ($items->contains(fn ($it) => $it->expense_type === 'opex') ? 'opex' : null);
        }

        $chain = self::buildApprovalChain(
            isCopex: $isCopex,
            totalInclGst: $totalInclGst,
            approverL1: $request->input('approver_l1'),
            approverL2: $request->input('approver_l2')
        );

        $budgetError = null;
        $alertWarning = null;
        $pr = null;

        DB::transaction(function () use ($request, $data, $items, $totalInclGst, $chain, $overallExpenseType, &$budgetError, &$alertWarning, &$pr) {
            $budget = DepartmentBudget::lockOrCreate($data['department']);
            $remaining = $budget->remainingAmount();

            if (! $budget->hasHeadroomFor($totalInclGst)) {
                $budgetError = 'This PR (₹'.number_format($totalInclGst, 2).", GST-incl.) would exceed {$data['department']}'s remaining ".
                    DepartmentBudget::currentFiscalYear().' budget (₹'.number_format($remaining, 2).' of ₹'.number_format((float) $budget->allocated_amount, 2).' left). Proceeding requires a Budget Exception — file one from Governance → Exception Handling for a CFO override, then resubmit this PR.';

                return;
            }

            $projectBudget = null;
            if (! empty($data['project_budget_id'])) {
                $projectBudget = ProjectBudget::whereKey($data['project_budget_id'])->lockForUpdate()->first();
                if ($projectBudget && strcasecmp(trim((string) $projectBudget->department), trim((string) $data['department'])) !== 0) {
                    $budgetError = "Selected project '{$projectBudget->project_name}' belongs to {$projectBudget->department}, not {$data['department']}. Please pick a project under {$data['department']} or switch the department.";

                    return;
                }
                if ($projectBudget && ! $projectBudget->hasHeadroomFor($totalInclGst)) {
                    $projRemaining = $projectBudget->remainingAmount();
                    $budgetError = "This PR (₹" . number_format($totalInclGst, 2) . ", GST-incl.) would exceed project '{$projectBudget->project_name}' available headroom (₹" . number_format($projRemaining, 2) . " of ₹" . number_format((float) $projectBudget->allocated_amount, 2) . " left).";
                    return;
                }
            }

            $eventName = $data['event_name'] ?? null;
            if ($projectBudget && empty($eventName)) {
                $eventName = $projectBudget->project_name;
            }

            $pr = PurchaseRequisition::create([
                'pr_number' => $this->nextPrNumber(),
                'title' => $data['title'] ?? null,
                'event_name' => $eventName,
                'project_budget_id' => $projectBudget?->id,
                'department' => $data['department'],
                'region' => $data['region'] ?? null,
                'required_by' => $data['required_by'] ?? null,
                'sole_source' => $request->boolean('sole_source'),
                'expense_type' => $overallExpenseType,
                'description' => $data['description'] ?? null,
                'special_instructions' => $data['special_instructions'] ?? null,
                'status' => self::STATUS_FOR_LEVEL[1],
                'current_level' => 1,
                'created_by' => $request->user()->id,
            ]);

            $items->each(fn (PurchaseRequisitionItem $item) => $item->status = 'pending');
            $pr->items()->saveMany($items);

            $pr->approvals()->delete();
            foreach ($chain as $level => $role) {
                $pr->approvals()->create(['level' => $level, 'role_required' => $role, 'status' => 'pending']);
            }

            $alertWarning = $budget->applyDelta($totalInclGst, "PR {$pr->pr_number}");
            if ($projectBudget) {
                $projWarning = $projectBudget->applyDelta($totalInclGst, "PR {$pr->pr_number}");
                if ($projWarning && ! $alertWarning) {
                    $alertWarning = $projWarning;
                }
            }

            // Record initial version 1 (Submission)
            $pr->recordVersion(
                event: 'created',
                actionTitle: 'Initial PR Submission (v1)',
                changeSummary: 'Requisition submitted with ' . $items->count() . ' line item(s) totalling ₹' . number_format($totalInclGst, 2) . ($projectBudget ? " against project: {$projectBudget->project_name}" : ''),
                comments: $pr->description,
                changes: [
                    'department' => $pr->department,
                    'title' => $pr->title,
                    'event_name' => $pr->event_name,
                    'project_budget_id' => $pr->project_budget_id,
                    'region' => $pr->region,
                    'items_count' => $items->count(),
                    'total_cost' => $totalInclGst,
                ]
            );
        });

        if ($budgetError !== null) {
            return back()->withErrors(['budget' => $budgetError])->withInput();
        }

        $redirect = redirect()->route('requisition')
            ->with('status', "PR {$pr->pr_number} submitted — awaiting " . Roles::label($chain[1]) . " review.");

        if ($alertWarning !== null) {
            $redirect->with('warning', $alertWarning);
        }

        return $redirect;
    }

    private function splitItemNames(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }

        if (str_contains($raw, "\n")) {
            $parts = preg_split('/\r\n|\r|\n/', $raw);
        } elseif (str_contains($raw, ';')) {
            $parts = explode(';', $raw);
        } elseif (str_contains($raw, ',')) {
            $parts = explode(',', $raw);
        } else {
            $parts = [$raw];
        }

        $cleaned = [];
        foreach ($parts as $p) {
            $t = trim($p, " \t\n\r\0\x0B;,");
            if ($t !== '') {
                $cleaned[] = $t;
            }
        }

        return ! empty($cleaned) ? $cleaned : [$raw];
    }

    private function splitSpecs(string $raw, int $expectedCount): array
    {
        $raw = trim($raw);
        if ($raw === '' || $expectedCount <= 1) {
            return array_fill(0, max(1, $expectedCount), $raw);
        }

        if (str_contains($raw, "\n")) {
            $parts = preg_split('/\r\n|\r|\n/', $raw);
        } elseif (str_contains($raw, ';')) {
            $parts = explode(';', $raw);
        } elseif (str_contains($raw, '|')) {
            $parts = explode('|', $raw);
        } else {
            $parts = [$raw];
        }

        $cleaned = array_values(array_filter(array_map(fn ($p) => trim($p, " \t\n\r\0\x0B;,"), $parts), fn ($p) => $p !== ''));
        if (count($cleaned) === $expectedCount) {
            return $cleaned;
        }

        return array_fill(0, $expectedCount, $raw);
    }

    private function splitNumbers(string $raw, int $expectedCount, float|int $default = 0, bool $divideIfSingle = false): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return array_fill(0, $expectedCount, $default);
        }

        $parts = [];
        if (str_contains($raw, "\n")) {
            $parts = preg_split('/\r\n|\r|\n/', $raw);
        } elseif (str_contains($raw, ';')) {
            $parts = explode(';', $raw);
        } elseif (str_contains($raw, '|')) {
            $parts = explode('|', $raw);
        } elseif (str_contains($raw, ',') && ! preg_match('/^\d{1,3}(,\d{3})+(\.\d+)?$/', $raw)) {
            $parts = explode(',', $raw);
        } else {
            $parts = [$raw];
        }

        $cleaned = [];
        foreach ($parts as $p) {
            $numOnly = str_replace([',', ' ', '₹', '$', '%'], '', $p);
            if (is_numeric($numOnly)) {
                $cleaned[] = (float)$numOnly;
            }
        }

        if (count($cleaned) === $expectedCount) {
            return $cleaned;
        }

        if (count($cleaned) === 1) {
            $single = $cleaned[0];
            if ($divideIfSingle && $expectedCount > 1) {
                return array_fill(0, $expectedCount, round($single / $expectedCount, 2));
            }
            return array_fill(0, $expectedCount, $single);
        }

        return array_fill(0, $expectedCount, $default);
    }

    private function splitUnits(string $raw, int $expectedCount, string $default = 'Units'): array
    {
        $raw = trim($raw);
        if ($raw === '' || $expectedCount <= 1) {
            return array_fill(0, $expectedCount, $raw ?: $default);
        }

        if (str_contains($raw, "\n")) {
            $parts = preg_split('/\r\n|\r|\n/', $raw);
        } elseif (str_contains($raw, ';')) {
            $parts = explode(';', $raw);
        } elseif (str_contains($raw, '|')) {
            $parts = explode('|', $raw);
        } elseif (str_contains($raw, ',')) {
            $parts = explode(',', $raw);
        } else {
            $parts = [$raw];
        }

        $cleaned = array_values(array_filter(array_map(fn ($p) => trim($p, " \t\n\r\0\x0B;,"), $parts), fn ($p) => $p !== ''));
        if (count($cleaned) === $expectedCount) {
            return $cleaned;
        }

        return array_fill(0, $expectedCount, $raw ?: $default);
    }

    private function normalizeStatus(string $raw): ?string
    {
        $val = strtolower(trim($raw));
        if ($val === '') {
            return 'pending_l1';
        }

        return match ($val) {
            'pending', 'pending l1', 'pending_l1', 'l1' => 'pending_l1',
            'pending l2', 'pending_l2', 'l2' => 'pending_l2',
            'pending l3', 'pending_l3', 'l3' => 'pending_l3',
            'pending l4', 'pending_l4', 'l4' => 'pending_l4',
            'approved' => 'approved',
            'rejected' => 'rejected',
            'draft' => 'draft',
            'partially approved', 'partially_approved' => 'partially_approved',
            default => null,
        };
    }

    private function extractColumnMap(array $headerRow): ?array
    {
        $map = [];
        foreach ($headerRow as $idx => $header) {
            $clean = strtolower(trim(preg_replace('/[^a-z0-9]/i', '', (string)$header)));
            if (in_array($clean, ['prno', 'prnumber', 'prnum'], true)) {
                $map['pr_number'] = $idx;
            } elseif (in_array($clean, ['prtitle', 'title', 'subject'], true)) {
                $map['title'] = $idx;
            } elseif (in_array($clean, ['itemnames', 'itemname', 'item', 'items', 'material'], true)) {
                $map['item_name'] = $idx;
            } elseif (in_array($clean, ['department', 'dept'], true)) {
                $map['department'] = $idx;
            } elseif (in_array($clean, ['category', 'sector'], true)) {
                $map['category'] = $idx;
            } elseif (in_array($clean, ['unitcost', 'estcost', 'cost', 'price', 'rate', 'amount'], true)) {
                $map['cost'] = $idx;
            } elseif (in_array($clean, ['requiredby', 'requiredbydate', 'requireddate', 'neededby'], true)) {
                $map['required_by'] = $idx;
            } elseif (in_array($clean, ['status', 'approvalstatus'], true)) {
                $map['status'] = $idx;
            } elseif (in_array($clean, ['quantity', 'qty'], true)) {
                $map['quantity'] = $idx;
            } elseif (in_array($clean, ['unit', 'uom'], true)) {
                $map['unit'] = $idx;
            } elseif (in_array($clean, ['gst', 'gstpercent', 'tax', 'taxpercent', 'gstrate', 'gstpercentage', 'taxrate'], true)) {
                $map['gst'] = $idx;
            } elseif (in_array($clean, ['specs', 'descriptionspecs', 'specsdescription', 'itemdescription', 'itemspece', 'itemspecs', 'specification', 'specifications', 'scope', 'itemdesc'], true)) {
                $map['specs'] = $idx;
            } elseif (in_array($clean, ['purpose', 'requisitiondescription', 'prdescription', 'prpurpose', 'requisitionpurpose', 'description', 'justification', 'context', 'objective'], true)) {
                $map['purpose'] = $idx;
            } elseif (in_array($clean, ['solesource', 'proprietary', 'singlevendor', 'sole'], true)) {
                $map['sole_source'] = $idx;
            } elseif (in_array($clean, ['event', 'eventname', 'project', 'projectname', 'eventproject', 'projectbudget', 'eventprojectname'], true)) {
                $map['project_name'] = $idx;
            }
        }
        return (! empty($map) && isset($map['item_name'])) ? $map : null;
    }

    public function downloadTemplate(Request $request): Response
    {
        // Production schema covering every single field of Create Purchase Requisition:
        // PR NO. | PR TITLE | DEPARTMENT | ITEM NAME(S) | QTY | UNIT | UNIT COST | GST% | DESCRIPTION / SPECS | REQUIRED BY | PURPOSE | SOLE SOURCE | STATUS
        $headers = [
            'PR NO.', 'PR TITLE', 'DEPARTMENT', 'ITEM NAME(S)', 'QTY', 'UNIT', 'UNIT COST', 'GST%',
            'DESCRIPTION / SPECS', 'REQUIRED BY', 'PURPOSE', 'SOLE SOURCE', 'STATUS',
        ];

        // Generate a unique, auto-sequenced PR number to show in the sample row
        $autoNumber = $this->nextPrNumber();

        $sampleItems = [
            ['Dell Latitude 5540 Laptop', 2, 'Units', 85000, 18, 'Intel Core i7-1370P 14-Core, 32GB DDR5, 1TB NVMe Gen4 SSD, 15.6" FHD IPS, 3-Yr ProSupport NBD Warranty'],
            ['Dell UltraSharp 27 4K Monitor', 2, 'Units', 42000, 18, 'Dell UltraSharp U2723QE 27" 4K UHD (3840x2160) IPS Black, 90W USB-C PD Hub, RJ45 Ethernet, Height-Adjust Stand'],
            ['Logitech MX Master 3S Mouse', 2, 'Units', 8500, 18, 'Logitech MX Master 3S Ergonomic Performance Wireless Mouse, 8K DPI Sensor, Quiet Clicks, USB-C Charging'],
        ];

        // Multi-line detailed sample demonstrating multiple items, quantities, units, costs, and individual specs
        // (kept for CSV export which uses the flat 13-column format)
        $sampleData = [
            [
                $autoNumber,
                'IT Infrastructure Refresh',
                'IT',
                'Dell Latitude 5540 Laptop',
                2,
                'Units',
                85000,
                18,
                'Intel Core i7-1370P 14-Core, 32GB DDR5, 1TB NVMe Gen4 SSD, 15.6" FHD IPS, 3-Yr ProSupport NBD Warranty',
                now()->addDays(14)->format('Y-m-d'),
                'Q4 engineering workstation and dual-display peripheral refresh under departmental IT budget',
                'No',
                'Pending',
            ],
            [
                $autoNumber,
                'IT Infrastructure Refresh',
                'IT',
                'Dell UltraSharp 27 4K Monitor',
                2,
                'Units',
                42000,
                18,
                'Dell UltraSharp U2723QE 27" 4K UHD (3840x2160) IPS Black, 90W USB-C PD Hub, RJ45 Ethernet, Height-Adjust Stand',
                now()->addDays(14)->format('Y-m-d'),
                'Q4 engineering workstation and dual-display peripheral refresh under departmental IT budget',
                'No',
                'Pending',
            ],
            [
                $autoNumber,
                'IT Infrastructure Refresh',
                'IT',
                'Logitech MX Master 3S Mouse',
                2,
                'Units',
                8500,
                18,
                'Logitech MX Master 3S Ergonomic Performance Wireless Mouse, 8K DPI Sensor, Quiet Clicks, USB-C Charging',
                now()->addDays(14)->format('Y-m-d'),
                'Q4 engineering workstation and dual-display peripheral refresh under departmental IT budget',
                'No',
                'Pending',
            ],
        ];

        $format = strtolower($request->query('format', 'xlsx'));

        if ($format === 'csv') {
            $handle = fopen('php://memory', 'w+');
            fputcsv($handle, $headers);
            foreach ($sampleData as $row) {
                fputcsv($handle, $row);
            }
            rewind($handle);
            $csv = stream_get_contents($handle);
            fclose($handle);

            return response($csv, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="PR_Bulk_Import_Template.csv"',
            ]);
        }

        $xlsxContent = ExcelReader::createFormXlsx(
            prNumber: $autoNumber,
            sampleItems: $sampleItems,
            prTitle: 'IT Infrastructure Refresh',
            department: 'IT',
            requiredBy: now()->addDays(14)->format('Y-m-d'),
            purpose: 'Q4 engineering workstation and dual-display peripheral refresh under departmental IT budget',
            soleSource: 'No',
            status: 'Pending'
        );

        return response($xlsxContent, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="PR_Bulk_Import_Template.xlsx"',
        ]);
    }

    /**
     * Parse an uploaded Excel / CSV file asynchronously and return row-level
     * validation + structured items to immediately populate the PR Create page.
     */
    public function parseExcel(Request $request): JsonResponse
    {
        abort_unless(in_array($request->user()->role, [Roles::PR_SR_EXEC, Roles::ADMIN_HEAD], true), 403, 'Only the PR Sr. Executive or Admin Head creates requisitions.');

        $request->validate([
            'file' => ['nullable', 'file', 'mimes:csv,txt,xlsx', 'max:10240'],
            'csv_data' => ['nullable', 'string'],
        ]);

        $rows = [];
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $ext = strtolower($file->getClientOriginalExtension());
            $rows = ExcelReader::parseRows($file->getRealPath(), $ext);
        } elseif ($request->filled('csv_data')) {
            $lines = preg_split('/\r\n|\r|\n/', trim($request->input('csv_data')));
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if ($trimmed === '') continue;
                $rows[] = str_getcsv($trimmed);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Please select an Excel or CSV file to import.',
            ], 422);
        }

        if (empty($rows)) {
            return response()->json([
                'success' => false,
                'message' => 'The uploaded file contains no data rows.',
            ], 422);
        }

        $detectedDept = null;
        $detectedTitle = null;
        $detectedRequiredBy = null;
        $entered = [];
        $rejections = [];

        $colMap = isset($rows[0]) ? $this->extractColumnMap($rows[0]) : null;
        $hasHeader = $colMap !== null;

        foreach ($rows as $index => $cols) {
            $rowNum = $index + 1;

            if ($index === 0 && $hasHeader) {
                continue;
            }

            if (empty(array_filter($cols, fn ($v) => trim((string)$v) !== ''))) {
                continue;
            }

            $prNumber = '';
            $prTitle = '';
            $itemName = '';
            $rowDept = '';
            $category = '';
            $costRaw = '';
            $reqByRaw = '';
            $statusRaw = '';
            $qtyRaw = '1';
            $unit = 'Units';
            $gstRaw = '18';

            if ($colMap) {
                $prNumber = isset($colMap['pr_number']) ? trim((string)($cols[$colMap['pr_number']] ?? '')) : '';
                $prTitle = isset($colMap['title']) ? trim((string)($cols[$colMap['title']] ?? '')) : '';
                $itemName = isset($colMap['item_name']) ? trim((string)($cols[$colMap['item_name']] ?? '')) : '';
                $rowDept = isset($colMap['department']) ? trim((string)($cols[$colMap['department']] ?? '')) : ($request->input('department') ?: 'Operations');
                $category = isset($colMap['category']) ? trim((string)($cols[$colMap['category']] ?? '')) : '';
                $specsRaw = isset($colMap['specs']) ? trim((string)($cols[$colMap['specs']] ?? '')) : $category;
                $costRaw = isset($colMap['cost']) ? trim((string)($cols[$colMap['cost']] ?? '')) : '';
                $reqByRaw = isset($colMap['required_by']) ? trim((string)($cols[$colMap['required_by']] ?? '')) : '';
                $statusRaw = isset($colMap['status']) ? trim((string)($cols[$colMap['status']] ?? '')) : '';
                $qtyRaw = isset($colMap['quantity']) ? trim((string)($cols[$colMap['quantity']] ?? '1')) : '1';
                $unit = isset($colMap['unit']) ? trim((string)($cols[$colMap['unit']] ?? 'Units')) : 'Units';
                $gstRaw = isset($colMap['gst']) ? trim((string)($cols[$colMap['gst']] ?? '18')) : '18';
            } elseif (count($cols) >= 8) {
                // Positional 8 or 9 column format
                $prNumber = trim((string)($cols[0] ?? ''));
                $prTitle = trim((string)($cols[1] ?? ''));
                $itemName = trim((string)($cols[2] ?? ''));
                $rowDept = trim((string)($cols[3] ?? ''));
                $category = trim((string)($cols[4] ?? ''));
                $costRaw = trim((string)($cols[5] ?? ''));
                if (count($cols) >= 9) {
                    $gstRaw = trim((string)($cols[6] ?? '18'));
                    $reqByRaw = trim((string)($cols[7] ?? ''));
                    $statusRaw = trim((string)($cols[8] ?? ''));
                } else {
                    $gstRaw = '18';
                    $reqByRaw = trim((string)($cols[6] ?? ''));
                    $statusRaw = trim((string)($cols[7] ?? ''));
                }
            } else {
                // Positional legacy 5-column format: Item Name | Qty | Unit | Cost | GST
                $itemName = trim((string)($cols[0] ?? ''));
                $qtyRaw = trim((string)($cols[1] ?? '1'));
                $unit = trim((string)($cols[2] ?? 'Units')) ?: 'Units';
                $costRaw = trim((string)($cols[3] ?? ''));
                $gstRaw = trim((string)($cols[4] ?? '18'));
                $rowDept = $request->input('department') ?: 'Operations';
            }

            if ($rowDept && ! $detectedDept) {
                $detectedDept = $rowDept;
            }
            if ($prTitle && ! $detectedTitle) {
                $detectedTitle = $prTitle;
            }
            if ($reqByRaw && ! $detectedRequiredBy && strtotime($reqByRaw)) {
                $detectedRequiredBy = date('Y-m-d', strtotime($reqByRaw));
            }

            $cleanCost = preg_replace('/[^0-9.]/', '', $costRaw);
            $cleanQty = preg_replace('/[^0-9]/', '', $qtyRaw) ?: '1';
            $rawGstNoPercent = str_replace(['%', ' ', '₹'], '', $gstRaw);
            $cleanGst = (is_numeric($rawGstNoPercent) && (float)$rawGstNoPercent >= 0) ? (float)$rawGstNoPercent : 18.0;

            $errorsInRow = [];

            if ($itemName === '') {
                $errorsInRow[] = 'ITEM NAME(S) is missing or empty';
            }

            if ($rowDept === '') {
                $errorsInRow[] = 'DEPARTMENT is missing';
            }

            if ($cleanCost !== '' && (! is_numeric($cleanCost) || (float)$cleanCost < 0)) {
                $errorsInRow[] = 'EST. COST must be a positive number';
            }

            if (! empty($errorsInRow)) {
                $rejections[] = [
                    'row' => $rowNum,
                    'pr_number' => $prNumber ?: '—',
                    'item' => $itemName ?: '—',
                    'reason' => implode('; ', $errorsInRow),
                ];
                continue;
            }

            $itemNames = $this->splitItemNames($itemName);
            $itemCount = count($itemNames);
            $parsedNumericCost = (is_numeric($cleanCost) && (float)$cleanCost >= 0) ? (float)$cleanCost : 0.00;
            $perItemCost = $itemCount > 1 ? round($parsedNumericCost / $itemCount, 2) : $parsedNumericCost;
            $splitSpecs = $this->splitSpecs($specsRaw ?? '', $itemCount);

            foreach ($itemNames as $i => $subName) {
                $entered[] = [
                    'item_name' => $subName,
                    'quantity' => (int)$cleanQty,
                    'unit' => $unit,
                    'unit_cost' => $perItemCost,
                    'gst_percent' => (float)$cleanGst,
                    'description' => $splitSpecs[$i] ?: ($category ? "Category: {$category}" : ''),
                ];
            }
        }

        $totalDataRows = count($rows) - ($hasHeader ? 1 : 0);

        return response()->json([
            'success' => true,
            'total_rows' => $totalDataRows,
            'entered_count' => count($entered),
            'rejected_count' => count($rejections),
            'department' => $detectedDept,
            'title' => $detectedTitle,
            'required_by' => $detectedRequiredBy,
            'entered' => $entered,
            'rejections' => $rejections,
        ]);
    }

    public function importExcel(Request $request): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, [Roles::PR_SR_EXEC, Roles::ADMIN_HEAD], true), 403, 'Only the PR Sr. Executive or Admin Head creates requisitions.');

        $request->validate([
            'department' => ['nullable', 'string'],
            'required_by' => ['nullable', 'date'],
            'title' => ['nullable', 'string', 'max:255'],
            'csv_data' => ['nullable', 'string'],
            'file' => ['nullable', 'file', 'mimes:csv,txt,xlsx', 'max:10240'],
        ]);

        $rows = [];

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $ext = strtolower($file->getClientOriginalExtension());
            $rows = ExcelReader::parseRows($file->getRealPath(), $ext);
        } elseif ($request->filled('csv_data')) {
            $lines = preg_split('/\r\n|\r|\n/', trim($request->input('csv_data')));
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if ($trimmed === '') continue;
                $rows[] = str_getcsv($trimmed);
            }
        } else {
            return back()->withErrors(['file' => 'Please upload an Excel or CSV file.'])->withInput();
        }

        if (empty($rows)) {
            return back()->withErrors(['file' => 'The uploaded file contains no data rows.'])->withInput();
        }

        $colMap = isset($rows[0]) ? $this->extractColumnMap($rows[0]) : null;
        $hasHeader = $colMap !== null;

        $validEntries = [];
        $rejections = [];

        foreach ($rows as $index => $cols) {
            $rowNum = $index + 1;

            if ($index === 0 && $hasHeader) {
                continue;
            }

            if (empty(array_filter($cols, fn ($v) => trim((string)$v) !== ''))) {
                continue;
            }

            $prNumber = '';
            $prTitle = '';
            $itemName = '';
            $rowDept = '';
            $category = '';
            $costRaw = '';
            $reqByRaw = '';
            $statusRaw = '';
            $qtyRaw = '1';
            $unit = 'Units';
            $gstRaw = '18';

            $is8ColFormat = false;

            if ($colMap) {
                $is8ColFormat = isset($colMap['pr_number']) || isset($colMap['category']) || isset($colMap['status']) || isset($colMap['specs']);
                $prNumber = isset($colMap['pr_number']) ? trim((string)($cols[$colMap['pr_number']] ?? '')) : '';
                $prTitle = isset($colMap['title']) ? trim((string)($cols[$colMap['title']] ?? '')) : '';
                $itemName = isset($colMap['item_name']) ? trim((string)($cols[$colMap['item_name']] ?? '')) : '';
                $rowDept = isset($colMap['department']) ? trim((string)($cols[$colMap['department']] ?? '')) : ($request->input('department') ?: '');
                $category = isset($colMap['category']) ? trim((string)($cols[$colMap['category']] ?? '')) : '';
                $specsRaw = isset($colMap['specs']) ? trim((string)($cols[$colMap['specs']] ?? '')) : $category;
                $purposeRaw = isset($colMap['purpose']) ? trim((string)($cols[$colMap['purpose']] ?? '')) : '';
                $soleSourceRaw = isset($colMap['sole_source']) ? trim((string)($cols[$colMap['sole_source']] ?? '')) : '';
                $costRaw = isset($colMap['cost']) ? trim((string)($cols[$colMap['cost']] ?? '')) : '';
                $reqByRaw = isset($colMap['required_by']) ? trim((string)($cols[$colMap['required_by']] ?? '')) : '';
                $statusRaw = isset($colMap['status']) ? trim((string)($cols[$colMap['status']] ?? '')) : '';
                $qtyRaw = isset($colMap['quantity']) ? trim((string)($cols[$colMap['quantity']] ?? '1')) : '1';
                $unit = isset($colMap['unit']) ? trim((string)($cols[$colMap['unit']] ?? 'Units')) : 'Units';
                $gstRaw = isset($colMap['gst']) ? trim((string)($cols[$colMap['gst']] ?? '18')) : '18';
                $projectNameRaw = isset($colMap['project_name']) ? trim((string)($cols[$colMap['project_name']] ?? '')) : '';
            } elseif (count($cols) >= 8) {
                $is8ColFormat = true;
                $prNumber = trim((string)($cols[0] ?? ''));
                $prTitle = trim((string)($cols[1] ?? ''));
                $rowDept = trim((string)($cols[2] ?? ''));
                $itemName = trim((string)($cols[3] ?? ''));
                $qtyRaw = trim((string)($cols[4] ?? '1'));
                $unit = trim((string)($cols[5] ?? 'Units')) ?: 'Units';
                $costRaw = trim((string)($cols[6] ?? ''));
                $gstRaw = trim((string)($cols[7] ?? '18'));
                $specsRaw = trim((string)($cols[8] ?? ''));
                $reqByRaw = trim((string)($cols[9] ?? ''));
                $purposeRaw = trim((string)($cols[10] ?? ''));
                $soleSourceRaw = trim((string)($cols[11] ?? ''));
                $statusRaw = trim((string)($cols[12] ?? ''));
                $projectNameRaw = trim((string)($cols[13] ?? ''));
                $category = $specsRaw;
            } else {
                // Legacy 5-column positional: Item Name | Qty | Unit | Cost | GST
                $itemName = trim((string)($cols[0] ?? ''));
                $qtyRaw = trim((string)($cols[1] ?? '1'));
                $unit = trim((string)($cols[2] ?? 'Units')) ?: 'Units';
                $costRaw = trim((string)($cols[3] ?? ''));
                $gstRaw = trim((string)($cols[4] ?? '18'));
                $rowDept = $request->input('department') ?: 'Operations';
                $specsRaw = '';
                $purposeRaw = '';
                $soleSourceRaw = '';
                $projectNameRaw = '';
            }

            if ($rowDept === '' && ! $is8ColFormat) {
                $rowDept = $request->input('department') ?: 'Operations';
            }

            $rawCostNoComma = str_replace([',', ' ', '₹', '$'], '', $costRaw);
            $cleanCost = is_numeric($rawCostNoComma) ? (float)$rawCostNoComma : ($costRaw === '' ? 0.00 : null);
            $subItemNames = $this->splitItemNames($itemName);
            $subItemCount = count($subItemNames);
            $parsedRowCosts = $this->splitNumbers($costRaw, $subItemCount, 0, true);
            $allCostsPositive = ! empty($parsedRowCosts) && ! collect($parsedRowCosts)->contains(fn ($c) => $c < 0);
            if ($cleanCost === null && $allCostsPositive) {
                $cleanCost = array_sum($parsedRowCosts);
            }
            $cleanQty = preg_replace('/[^0-9]/', '', $qtyRaw) ?: '1';
            $rawGstNoPercent = str_replace(['%', ' ', '₹'], '', $gstRaw);
            $cleanGst = (is_numeric($rawGstNoPercent) && (float)$rawGstNoPercent >= 0) ? (float)$rawGstNoPercent : 18.0;
            $isSoleSource = in_array(strtolower($soleSourceRaw), ['yes', 'true', '1', 'y', 'checked'], true);

            $errorsInRow = [];

            if ($is8ColFormat) {
                // Production-level check: core required columns must be present and valid
                if ($prTitle === '') {
                    $errorsInRow[] = 'PR TITLE is missing or empty';
                }
                if ($itemName === '') {
                    $errorsInRow[] = 'ITEM NAME(S) is missing or empty';
                }
                if ($rowDept === '') {
                    $errorsInRow[] = 'DEPARTMENT is missing or empty';
                }
                if ($costRaw !== '' && ($cleanCost === null || $cleanCost < 0)) {
                    $errorsInRow[] = 'EST. COST must be a positive number';
                }
                if ($reqByRaw === '' || ! strtotime($reqByRaw)) {
                    $errorsInRow[] = 'REQUIRED BY date is missing or invalid (expected YYYY-MM-DD)';
                }
                if ($statusRaw === '') {
                    $errorsInRow[] = 'STATUS is missing or empty';
                } elseif ($this->normalizeStatus($statusRaw) === null) {
                    $errorsInRow[] = "STATUS '{$statusRaw}' is invalid (allowed: Pending, Approved, Rejected, Draft)";
                }
            } else {
                // Legacy format checks
                if ($itemName === '') {
                    $errorsInRow[] = 'ITEM NAME(S) is missing or empty';
                }
                if ($rowDept === '') {
                    $errorsInRow[] = 'DEPARTMENT is missing';
                }
                if ($costRaw !== '' && ($cleanCost === null || $cleanCost < 0)) {
                    $errorsInRow[] = 'EST. COST must be a positive number';
                }
            }

            if (! empty($errorsInRow)) {
                $rejections[] = [
                    'row' => $rowNum,
                    'pr_number' => $prNumber ?: '—',
                    'item' => $itemName ?: '—',
                    'reason' => implode('; ', $errorsInRow),
                ];
                continue;
            }

            $validEntries[] = [
                'row' => $rowNum,
                'pr_number' => $prNumber,
                'title' => $prTitle ?: ($request->input('title') ?: ($itemName . ' Procurement')),
                'item_name' => $itemName,
                'department' => $rowDept,
                'category' => $category ?: $specsRaw,
                'specs' => $specsRaw ?: $category,
                'purpose' => $purposeRaw,
                'sole_source' => $isSoleSource,
                'project_name' => $projectNameRaw,
                'quantity' => (int)$cleanQty,
                'qty_raw' => $qtyRaw,
                'unit' => $unit,
                'unit_cost' => (float)$cleanCost,
                'cost_raw' => $costRaw,
                'gst_percent' => (float)$cleanGst,
                'gst_raw' => $gstRaw,
                'required_by' => ($reqByRaw && strtotime($reqByRaw)) ? date('Y-m-d', strtotime($reqByRaw)) : ($request->input('required_by') ?: now()->addDays(14)->toDateString()),
                'status' => $statusRaw,
            ];
        }

        $totalDataRows = count($rows) - ($hasHeader ? 1 : 0);

        if (empty($validEntries)) {
            $summary = [
                'total_rows' => $totalDataRows,
                'entered_count' => 0,
                'rejected_count' => count($rejections),
                'pr_number' => null,
                'pr_id' => null,
                'rejections' => $rejections,
            ];

            return redirect()->route('requisition')
                ->with('import_summary', $summary)
                ->withErrors([
                    'file' => 'All ' . count($rejections) . ' row(s) failed validation. No lines were imported.',
                    'import_errors' => array_map(fn ($r) => "Row {$r['row']}: {$r['reason']}", array_slice($rejections, 0, 10)),
                ])->withInput();
        }

        // Group valid entries by PR: either by pr_number if present, or by title+department
        $grouped = [];
        foreach ($validEntries as $entry) {
            $key = $entry['pr_number'] !== '' ? $entry['pr_number'] : ($entry['department'] . '|' . $entry['title']);
            $grouped[$key][] = $entry;
        }

        $enteredCount = 0;
        $createdPrs = [];
        $thresholdWarnings = [];

        foreach ($grouped as $groupKey => $items) {
            $firstItem = $items[0];
            $dept = $firstItem['department'];
            $reqBy = $firstItem['required_by'];
            $title = $firstItem['title'];
            $specifiedPrNumber = $firstItem['pr_number'];
            $statusRaw = $firstItem['status'] ?? '';
            $purpose = $firstItem['purpose'] ?? null;
            $soleSource = (bool)($firstItem['sole_source'] ?? false);
            $eventProjectName = trim((string)($firstItem['project_name'] ?? ''));
            $targetStatus = $this->normalizeStatus($statusRaw) ?: 'pending_l1';

            // Verify department consistency across rows for the same PR NO
            $conflicting = false;
            foreach ($items as $it) {
                if ($it['department'] !== $dept) {
                    $conflicting = true;
                    break;
                }
            }
            if ($conflicting) {
                foreach ($items as $it) {
                    $rejections[] = [
                        'row' => $it['row'],
                        'pr_number' => $specifiedPrNumber ?: '—',
                        'item' => $it['item_name'],
                        'reason' => "Conflicting departments specified for PR {$specifiedPrNumber}",
                    ];
                }
                continue;
            }

            $groupTotalInclGst = collect($items)->sum(function ($it) {
                $subNames = $this->splitItemNames($it['item_name']);
                $subCount = count($subNames);
                $subCosts = $this->splitNumbers($it['cost_raw'] ?? '', $subCount, (float)$it['unit_cost'], true);
                $subQtys = $this->splitNumbers($it['qty_raw'] ?? '', $subCount, (int)$it['quantity'], false);
                $subGsts = $this->splitNumbers($it['gst_raw'] ?? '', $subCount, (float)$it['gst_percent'], false);

                $lineSum = 0.0;
                for ($i = 0; $i < $subCount; $i++) {
                    $base = (float)$subCosts[$i] * (int)$subQtys[$i];
                    $lineSum += $base * (1 + ((float)$subGsts[$i] / 100));
                }
                return $lineSum;
            });

            $groupPr = DB::transaction(function () use (
                $request,
                $items,
                $dept,
                $reqBy,
                $title,
                $specifiedPrNumber,
                $purpose,
                $soleSource,
                $eventProjectName,
                $targetStatus,
                $groupTotalInclGst,
                &$enteredCount,
                &$rejections,
                &$thresholdWarnings
            ) {
                $budget = DepartmentBudget::lockOrCreate($dept);

                if (! $budget->hasHeadroomFor((float) $groupTotalInclGst)) {
                    $remainingBudget = $budget->remainingAmount();
                    foreach ($items as $it) {
                        $rejections[] = [
                            'row' => $it['row'],
                            'pr_number' => $specifiedPrNumber ?: '—',
                            'item' => $it['item_name'],
                            'reason' => "PR {$specifiedPrNumber} exceeds {$dept}'s remaining budget (₹" . number_format($remainingBudget, 2) . ")",
                        ];
                    }

                    return null;
                }

                $projectBudget = null;
                if ($eventProjectName !== '') {
                    $projectBudget = ProjectBudget::where('fiscal_year', DepartmentBudget::currentFiscalYear())
                        ->whereRaw('LOWER(project_name) = ?', [mb_strtolower($eventProjectName)])
                        ->lockForUpdate()
                        ->first();

                    if ($projectBudget && strcasecmp(trim((string) $projectBudget->department), trim((string) $dept)) !== 0) {
                        foreach ($items as $it) {
                            $rejections[] = [
                                'row' => $it['row'],
                                'pr_number' => $specifiedPrNumber ?: '—',
                                'item' => $it['item_name'],
                                'reason' => "Project '{$projectBudget->project_name}' belongs to {$projectBudget->department}, not {$dept}",
                            ];
                        }

                        return null;
                    }

                    if ($projectBudget && ! $projectBudget->hasHeadroomFor((float) $groupTotalInclGst)) {
                        $projRemaining = $projectBudget->remainingAmount();
                        foreach ($items as $it) {
                            $rejections[] = [
                                'row' => $it['row'],
                                'pr_number' => $specifiedPrNumber ?: '—',
                                'item' => $it['item_name'],
                                'reason' => "PR {$specifiedPrNumber} exceeds project '{$projectBudget->project_name}' remaining budget (₹" . number_format($projRemaining, 2) . ")",
                            ];
                        }

                        return null;
                    }
                }

                // Fetch existing PR by unique pr_number or create new PR
                $pr = null;
                if ($specifiedPrNumber !== '') {
                    $pr = PurchaseRequisition::where('pr_number', $specifiedPrNumber)->first();
                }

                if ($pr) {
                    // Update existing PR and its status
                    $updateFields = [
                        'title' => $title,
                        'department' => $dept,
                        'required_by' => $reqBy,
                        'status' => $targetStatus,
                    ];
                    if (! empty($purpose)) {
                        $updateFields['description'] = $purpose;
                    }
                    if ($soleSource) {
                        $updateFields['sole_source'] = $soleSource;
                    }

                    if ($targetStatus === 'approved') {
                        $updateFields['current_level'] = 4;
                        $pr->approvals()->update([
                            'status' => 'approved',
                            'acted_at' => now(),
                            'approver_id' => $request->user()->id,
                        ]);
                    } elseif ($targetStatus === 'rejected') {
                        $updateFields['current_level'] = 1;
                        $pr->approvals()->update(['status' => 'rejected']);
                    } elseif (str_starts_with($targetStatus, 'pending')) {
                        $level = (int) filter_var($targetStatus, FILTER_SANITIZE_NUMBER_INT) ?: 1;
                        $updateFields['current_level'] = $level;
                    }

                    $pr->update($updateFields);

                    foreach ($items as $it) {
                        $subNames = $this->splitItemNames($it['item_name']);
                        $subCount = count($subNames);
                        $subCosts = $this->splitNumbers($it['cost_raw'] ?? '', $subCount, (float)$it['unit_cost'], true);
                        $subQtys = $this->splitNumbers($it['qty_raw'] ?? '', $subCount, (int)$it['quantity'], false);
                        $subUnits = $this->splitUnits($it['unit'] ?? 'Units', $subCount, 'Units');
                        $subGsts = $this->splitNumbers($it['gst_raw'] ?? '', $subCount, (float)$it['gst_percent'], false);
                        $subSpecs = $this->splitSpecs($it['specs'] ?? '', $subCount);

                        for ($i = 0; $i < $subCount; $i++) {
                            $pr->items()->create([
                                'item_name' => $subNames[$i],
                                'description' => $subSpecs[$i] ?: null,
                                'quantity' => max(1, (int)$subQtys[$i]),
                                'unit' => $subUnits[$i] ?: 'Units',
                                'unit_cost' => (float)$subCosts[$i],
                                'gst_percent' => max(0, (float)$subGsts[$i]),
                                'status' => in_array($targetStatus, ['approved', 'rejected'], true) ? $targetStatus : 'pending',
                            ]);
                            $enteredCount++;
                        }
                    }

                    if ($warningMsg = $budget->applyDelta((float) $groupTotalInclGst, "PR {$pr->pr_number}")) {
                        $thresholdWarnings[$budget->department] = $warningMsg;
                    }
                    if ($projectBudget) {
                        $pr->update([
                            'project_budget_id' => $projectBudget->id,
                            'event_name' => $projectBudget->project_name,
                        ]);
                        if ($projWarn = $projectBudget->applyDelta((float) $groupTotalInclGst, "PR {$pr->pr_number}")) {
                            $thresholdWarnings['project:' . $projectBudget->id] = $projWarn;
                        }
                    } elseif ($eventProjectName !== '' && empty($pr->event_name)) {
                        $pr->update(['event_name' => $eventProjectName]);
                    }
                    $pr->writeAuditEntry('updated', "PR {$pr->pr_number} updated via Excel bulk import: status set to {$targetStatus}, added line items.");
                } else {
                    // Create new PR with the specified unique PR NO
                    $prNum = $specifiedPrNumber !== '' ? $specifiedPrNumber : $this->nextPrNumber();
                    $initialLevel = str_starts_with($targetStatus, 'pending')
                        ? ((int) filter_var($targetStatus, FILTER_SANITIZE_NUMBER_INT) ?: 1)
                        : ($targetStatus === 'approved' ? 4 : 1);

                    $pr = PurchaseRequisition::create([
                        'pr_number' => $prNum,
                        'title' => $title,
                        'event_name' => $projectBudget?->project_name ?: ($eventProjectName !== '' ? $eventProjectName : null),
                        'project_budget_id' => $projectBudget?->id,
                        'department' => $dept,
                        'required_by' => $reqBy,
                        'description' => $purpose ?: null,
                        'sole_source' => $soleSource,
                        'status' => $targetStatus,
                        'current_level' => $initialLevel,
                        'created_by' => $request->user()->id,
                    ]);

                    foreach ($items as $it) {
                        $subNames = $this->splitItemNames($it['item_name']);
                        $subCount = count($subNames);
                        $subCosts = $this->splitNumbers($it['cost_raw'] ?? '', $subCount, (float)$it['unit_cost'], true);
                        $subQtys = $this->splitNumbers($it['qty_raw'] ?? '', $subCount, (int)$it['quantity'], false);
                        $subUnits = $this->splitUnits($it['unit'] ?? 'Units', $subCount, 'Units');
                        $subGsts = $this->splitNumbers($it['gst_raw'] ?? '', $subCount, (float)$it['gst_percent'], false);
                        $subSpecs = $this->splitSpecs($it['specs'] ?? '', $subCount);

                        for ($i = 0; $i < $subCount; $i++) {
                            $itemExpType = self::resolveItemExpenseType(['item_name' => $subNames[$i]]);
                            $pr->items()->create([
                                'item_name' => $subNames[$i],
                                'description' => $subSpecs[$i] ?: null,
                                'quantity' => max(1, (int)$subQtys[$i]),
                                'unit' => $subUnits[$i] ?: 'Units',
                                'unit_cost' => (float)$subCosts[$i],
                                'gst_percent' => max(0, (float)$subGsts[$i]),
                                'expense_type' => $itemExpType,
                                'status' => in_array($targetStatus, ['approved', 'rejected'], true) ? $targetStatus : 'pending',
                            ]);
                            $enteredCount++;
                        }
                    }

                    $pr->load('items');
                    $isCopex = $pr->items->contains(fn ($it) => $it->isCopex());
                    $chain = self::buildApprovalChain($isCopex, (float) $groupTotalInclGst);
                    $pr->update(['expense_type' => $isCopex ? 'capex' : 'opex']);

                    $pr->approvals()->delete();
                    foreach ($chain as $level => $role) {
                        $isApproved = ($targetStatus === 'approved' || ($targetStatus !== 'rejected' && $level < $initialLevel));
                        $pr->approvals()->create([
                            'level' => $level,
                            'role_required' => $role,
                            'status' => $isApproved ? 'approved' : 'pending',
                            'approver_id' => $isApproved ? $request->user()->id : null,
                            'acted_at' => $isApproved ? now() : null,
                        ]);
                    }

                    if ($warningMsg = $budget->applyDelta((float) $groupTotalInclGst, "PR {$pr->pr_number}")) {
                        $thresholdWarnings[$budget->department] = $warningMsg;
                    }
                    if ($projectBudget) {
                        if ($projWarn = $projectBudget->applyDelta((float) $groupTotalInclGst, "PR {$pr->pr_number}")) {
                            $thresholdWarnings['project:' . $projectBudget->id] = $projWarn;
                        }
                    }

                    $pr->writeAuditEntry('created', "PR {$pr->pr_number} imported from Excel with " . count($items) . " line items; status: {$targetStatus}.");

                    $pr->recordVersion(
                        event: 'created',
                        actionTitle: 'Initial PR Submission via Excel Import (v1)',
                        changeSummary: 'Requisition imported with ' . count($items) . ' line item(s) totalling ₹' . number_format($groupTotalInclGst, 2),
                        comments: 'Imported from Excel',
                        changes: [
                            'department' => $pr->department,
                            'status' => $pr->status,
                            'items_count' => count($items),
                            'total_cost' => $groupTotalInclGst,
                        ]
                    );
                }

                return $pr;
            });

            if ($groupPr === null) {
                continue;
            }

            $createdPrs[] = $groupPr;
        }

        $lastPr = end($createdPrs) ?: null;

        $summary = [
            'total_rows' => $totalDataRows,
            'entered_count' => $enteredCount,
            'rejected_count' => count($rejections),
            'pr_number' => $lastPr?->pr_number,
            'pr_id' => $lastPr?->id,
            'rejections' => $rejections,
        ];

        $redirect = redirect()->route('requisition')
            ->with('import_summary', $summary)
            ->with('status', $enteredCount > 0
                ? "Excel import completed: {$enteredCount} rows entered into PR system" . (count($rejections) > 0 ? " (" . count($rejections) . " rows skipped)." : ".")
                : "Excel import failed: 0 rows entered.");

        if (! empty($thresholdWarnings)) {
            $redirect->with('warning', implode(' ', array_values($thresholdWarnings)));
        }

        return $redirect;
    }

    public function show(PurchaseRequisition $requisition): View
    {
        $requisition->load([
            'items',
            'approvals' => fn ($q) => $q->orderBy('level')->with('approver'),
            'creator',
            'rfq',
            'versions.user',
        ]);

        return view('pages.requisition-show', [
            'pr' => $requisition,
            'pendingApproval' => $this->currentApprovalFor($requisition, request()->user()),
        ]);
    }

    public function approve(Request $request, PurchaseRequisition $requisition): RedirectResponse
    {
        $approval = $this->currentApprovalFor($requisition, $request->user());
        abort_unless($approval, 403, 'No pending approval step on this PR matches your role.');

        $requisition->load('items');

        // Line-item approval: approver can specify decisions for individual items
        // line_decisions: array of [item_id => 'approved' | 'rejected']
        $lineDecisions = $request->input('line_decisions', []);
        $rejectedReleasedCost = 0.0;
        $itemsDecisionSummary = [];

        if (is_array($lineDecisions) && ! empty($lineDecisions)) {
            foreach ($requisition->items as $item) {
                if (isset($lineDecisions[$item->id])) {
                    $decision = $lineDecisions[$item->id] === 'rejected' ? 'rejected' : 'approved';
                    // If newly transitioning to rejected, release its reserved budget
                    if ($decision === 'rejected' && $item->status !== 'rejected') {
                        $rejectedReleasedCost += $item->lineTotalInclGst();
                    }
                    $item->update(['status' => $decision]);
                    $itemsDecisionSummary[] = "{$item->item_name} (" . ucfirst($decision) . ')';
                }
            }
        } else {
            // Default: if no line items explicitly rejected, items remain approved or pending
            foreach ($requisition->items as $item) {
                if ($item->status === 'pending') {
                    $item->update(['status' => 'approved']);
                }
            }
        }

        // Release budget for any rejected lines immediately (clamped >= 0 under lock)
        if ($rejectedReleasedCost > 0) {
            DB::transaction(function () use ($requisition, $rejectedReleasedCost) {
                $budget = DepartmentBudget::where('department', $requisition->department)
                    ->where('fiscal_year', DepartmentBudget::currentFiscalYear())
                    ->lockForUpdate()
                    ->first();
                $budget?->applyDelta(-$rejectedReleasedCost);

                if ($requisition->project_budget_id) {
                    $projBudget = ProjectBudget::whereKey($requisition->project_budget_id)->lockForUpdate()->first();
                    $projBudget?->applyDelta(-$rejectedReleasedCost);
                }
            });
        }

        // Check if all lines were rejected
        $requisition->refresh();
        $approvedItemsCount = $requisition->items->where('status', 'approved')->count();
        $allLinesRejected = ($requisition->items->count() > 0 && $approvedItemsCount === 0);

        if ($allLinesRejected) {
            // All items were rejected -> entire PR is rejected
            $approval->update([
                'approver_id' => $request->user()->id,
                'status' => 'rejected',
                'acted_at' => now(),
                'comments' => $request->input('comments', 'All line items were rejected by approver.'),
            ]);
            $requisition->update(['status' => 'rejected']);

            $stageName = self::CHAIN[$approval->level] ? Roles::label(self::CHAIN[$approval->level]) : 'L' . $approval->level;
            $requisition->recordVersion(
                event: 'rejected',
                actionTitle: "Declined at Level {$approval->level} ({$stageName})",
                changeSummary: "All line items rejected by {$request->user()->name} (" . Roles::label($request->user()->role) . "). Reserved budget released.",
                comments: $request->input('comments'),
                changes: ['status' => 'rejected']
            );

            return back()->with('status', "PR {$requisition->pr_number} rejected — all line items were declined and budget released.");
        }

        $approval->update([
            'approver_id' => $request->user()->id,
            'status' => 'approved',
            'acted_at' => now(),
            'comments' => $request->input('comments'),
        ]);

        $next = $requisition->approvals()->whereIn('status', ['pending', 'on_hold'])->where('id', '!=', $approval->id)->orderBy('level')->first();
        $hasRejectedLines = $requisition->items->where('status', 'rejected')->count() > 0;
        
        $finalStatus = $hasRejectedLines ? 'partially_approved' : 'approved';
        $requisition->update([
            'status' => $next ? self::STATUS_FOR_LEVEL[$next->level] : $finalStatus,
            'current_level' => $next ? $next->level : $approval->level,
        ]);

        $stageName = self::CHAIN[$approval->level] ? Roles::label(self::CHAIN[$approval->level]) : 'L' . $approval->level;
        $summarySuffix = $next ? ". Advanced to Level {$next->level}." : ($hasRejectedLines ? ". Final clearance granted with partial line approval ({$approvedItemsCount} approved line items moving to RFQ)." : ". All approval stages fully cleared.");
        if (! empty($itemsDecisionSummary)) {
            $summarySuffix .= " Decisions: " . implode(', ', $itemsDecisionSummary) . '.';
        }

        $requisition->recordVersion(
            event: 'approved',
            actionTitle: "Endorsed at Level {$approval->level} ({$stageName})" . ($hasRejectedLines ? ' (Partial Approval)' : ''),
            changeSummary: "Stage {$approval->level} approved by {$request->user()->name} (" . Roles::label($request->user()->role) . ")" . $summarySuffix,
            comments: $request->input('comments'),
            changes: [
                'level' => $approval->level,
                'role' => $approval->role_required,
                'status' => $requisition->status,
                'approved_items_count' => $approvedItemsCount,
            ]
        );

        $message = $next
            ? 'Approved. Now awaiting '.Roles::label($next->role_required).'.'
            : "PR {$requisition->pr_number} is " . ($hasRejectedLines ? 'partially approved' : 'fully approved') . " and RFQ created for approved lines.";

        return back()->with('status', $message);
    }

    public function hold(Request $request, PurchaseRequisition $requisition): RedirectResponse
    {
        $request->validate(['comments' => ['required', 'string', 'max:1000']]);

        $approval = $this->currentApprovalFor($requisition, $request->user());
        abort_unless($approval, 403, 'No pending approval step on this PR matches your role.');

        $approval->update([
            'approver_id' => $request->user()->id,
            'status' => 'on_hold',
            'acted_at' => now(),
            'comments' => $request->input('comments'),
        ]);

        $requisition->update(['status' => 'on_hold']);

        $stageName = isset(self::CHAIN[$approval->level]) && self::CHAIN[$approval->level] ? Roles::label(self::CHAIN[$approval->level]) : 'L' . $approval->level;
        $requisition->recordVersion(
            event: 'on_hold',
            actionTitle: "Placed On Hold at Level {$approval->level} ({$stageName})",
            changeSummary: "Stage {$approval->level} placed on hold by {$request->user()->name} (" . Roles::label($request->user()->role) . "): {$request->input('comments')}",
            comments: $request->input('comments'),
            changes: [
                'level' => $approval->level,
                'role' => $approval->role_required,
                'status' => 'on_hold',
            ]
        );

        return back()->with('status', "PR {$requisition->pr_number} has been placed on hold.");
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
        // in the deck: rejected spend shouldn't keep counting against the department/project.
        $requisition->load('items');
        DB::transaction(function () use ($requisition) {
            $budget = DepartmentBudget::where('department', $requisition->department)
                ->where('fiscal_year', DepartmentBudget::currentFiscalYear())
                ->lockForUpdate()
                ->first();
            $budget?->applyDelta(-$requisition->estimatedCostInclGst());

            if ($requisition->project_budget_id) {
                $projBudget = ProjectBudget::whereKey($requisition->project_budget_id)->lockForUpdate()->first();
                $projBudget?->applyDelta(-$requisition->estimatedCostInclGst());
            }
        });

        $stageName = self::CHAIN[$approval->level] ? Roles::label(self::CHAIN[$approval->level]) : 'L' . $approval->level;
        $requisition->recordVersion(
            event: 'rejected',
            actionTitle: "Declined at Level {$approval->level} ({$stageName})",
            changeSummary: "Requisition declined by {$request->user()->name} (" . Roles::label($request->user()->role) . "). Reserved budget of ₹" . number_format($requisition->estimatedCostInclGst(), 2) . " released.",
            comments: $request->input('comments'),
            changes: [
                'level' => $approval->level,
                'role' => $approval->role_required,
                'status' => 'rejected',
                'reason' => $request->input('comments'),
            ]
        );

        return back()->with('status', "PR {$requisition->pr_number} rejected — reserved budget released.");
    }

    private function currentApprovalFor(PurchaseRequisition $pr, $user): ?Approval
    {
        return $pr->currentApprovalFor($user);
    }

    private function nextPrNumber(): string
    {
        do {
            $number = 'PR/'.DepartmentBudget::currentFiscalYear().'/'.now()->format('Ymd').'-'.random_int(1000, 9999);
        } while (PurchaseRequisition::where('pr_number', $number)->exists());

        return $number;
    }

    /**
     * Download or view Purchase Requisition PDF
     */
    public function downloadPdf(Request $request, PurchaseRequisition $requisition)
    {
        $requisition->loadMissing(['items', 'approvals.approver', 'creator', 'purchaseOrders.vendor', 'projectBudget']);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.purchase-requisition', [
            'pr' => $requisition,
        ])->setPaper('a4', 'portrait')
          ->setOptions([
              'isRemoteEnabled' => true,
              'dpi' => 120,
              'defaultFont' => 'DejaVu Sans',
          ]);

        $safeNum = str_replace(['/', '\\'], '-', $requisition->pr_number);
        $filename = "{$safeNum}.pdf";

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
