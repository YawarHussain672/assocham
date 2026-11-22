<?php

namespace App\Http\Controllers;

use App\Models\DepartmentBudget;
use App\Models\ItemCatalog;
use App\Models\ProjectBudget;
use App\Models\PurchaseRequisition;
use App\Support\Roles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Checks & Balances + Department Budget Master — governance visibility and
 * department-wise budget limit maintenance.
 *
 * View access matches `module:checks-balances` (Admin Head, CFO, SG).
 * Budget Master mutations (create, update limit, remove unused entry) are
 * restricted strictly to Head Admin (Roles::ADMIN_HEAD).
 */
class ChecksBalancesController extends Controller
{
    public function index(Request $request): View
    {
        $currentFy = DepartmentBudget::currentFiscalYear();
        $requestedFy = trim($request->string('fy')->toString());
        $fiscalYear = preg_match('/^FY\d{2}-\d{2}$/', $requestedFy) ? $requestedFy : $currentFy;

        $fiscalYears = DepartmentBudget::query()
            ->distinct()
            ->pluck('fiscal_year')
            ->push($currentFy)
            ->push($fiscalYear)
            ->unique()
            ->sortDesc()
            ->values();

        $budgets = DepartmentBudget::where('fiscal_year', $fiscalYear)
            ->orderBy('department')
            ->get();

        $prDepartments = PurchaseRequisition::query()
            ->select('department')
            ->distinct()
            ->pluck('department')
            ->all();

        $budgets->each(function (DepartmentBudget $budget) use ($prDepartments) {
            $hasPrs = in_array($budget->department, $prDepartments, true);
            $budget->setAttribute('can_delete', (float) $budget->utilised_amount <= 0 && !$hasPrs);
        });

        $projectBudgets = ProjectBudget::with('purchaseRequisitions')
            ->where('fiscal_year', $fiscalYear)
            ->orderBy('department')
            ->orderBy('project_name')
            ->get();

        if ($request->query('view') === 'items') {
            return redirect()->route('item-catalogs.index');
        }

        $projectDepartments = $budgets->pluck('department')->merge($projectBudgets->pluck('department'))->unique()->sort()->values();

        $itemCatalogs = collect();
        $catalogCategories = collect();
        $catalogCounts = [
            'total' => 0,
            'opex' => 0,
            'capex' => 0,
            'copex' => 0,
            'active' => 0,
        ];

        $viewParam = $request->query('view');
        $activeView = $viewParam === 'project' ? 'project' : 'department';

        return view('pages.checks-balances', [
            'budgets' => $budgets,
            'projectBudgets' => $projectBudgets,
            'projectDepartments' => $projectDepartments,
            'nearLimitBudgets' => $budgets->filter->isNearLimit()->values(),
            'nearLimitProjects' => $projectBudgets->filter->isNearLimit()->values(),
            'itemCatalogs' => $itemCatalogs,
            'catalogCategories' => $catalogCategories,
            'catalogCounts' => $catalogCounts,
            'fiscalYear' => $fiscalYear,
            'currentFy' => $currentFy,
            'fiscalYears' => $fiscalYears,
            'activeView' => $activeView,
            'canManageBudgets' => $request->user()?->role === Roles::ADMIN_HEAD,
        ]);
    }

    public function storeBudget(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->role === Roles::ADMIN_HEAD, 403, 'Only the Head Admin can manage department budget limits.');

        $data = $request->validate([
            'department' => ['required', 'string', 'max:100'],
            'fiscal_year' => ['required', 'string', 'regex:/^FY\d{2}-\d{2}$/'],
            'allocated_amount' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'gst_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $department = trim($data['department']);
        $fiscalYear = $data['fiscal_year'];

        $duplicate = DepartmentBudget::whereRaw('LOWER(department) = ?', [mb_strtolower($department)])
            ->where('fiscal_year', $fiscalYear)
            ->exists();

        if ($duplicate) {
            return back()
                ->withErrors(['department' => "A budget limit for {$department} ({$fiscalYear}) already exists. Use Edit Limit to update it."])
                ->withInput()
                ->with('budget_modal', 'create');
        }

        $budget = DepartmentBudget::create([
            'department' => $department,
            'fiscal_year' => $fiscalYear,
            'allocated_amount' => $data['allocated_amount'],
            'utilised_amount' => 0,
            'gst_rate' => $data['gst_rate'] ?? 18.00,
        ]);

        return redirect()
            ->route('checks-balances', ['fy' => $budget->fiscal_year])
            ->with('status', "Department budget limit created for {$budget->department} ({$budget->fiscal_year}): ₹".number_format((float) $budget->allocated_amount, 2).'.');
    }

    public function updateBudget(Request $request, DepartmentBudget $budget): RedirectResponse
    {
        abort_unless($request->user()?->role === Roles::ADMIN_HEAD, 403, 'Only the Head Admin can manage department budget limits.');

        $data = $request->validate([
            'allocated_amount' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'gst_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $newLimit = round((float) $data['allocated_amount'], 2);
        $utilised = round((float) $budget->utilised_amount, 2);

        if ($newLimit < $utilised) {
            return back()
                ->withErrors([
                    'allocated_amount' => "Budget limit for {$budget->department} cannot be less than already utilised spend (₹".number_format($utilised, 2).').',
                ])
                ->withInput()
                ->with('budget_modal', 'edit')
                ->with('edit_budget_id', $budget->id);
        }

        $oldLimit = (float) $budget->allocated_amount;
        $wasNearLimit = $budget->isNearLimit();
        $budget->update([
            'allocated_amount' => $newLimit,
            'gst_rate' => $data['gst_rate'] ?? $budget->gst_rate,
        ]);

        $budget->refresh();
        $redirect = redirect()
            ->route('checks-balances', ['fy' => $budget->fiscal_year])
            ->with('status', "Updated {$budget->department} ({$budget->fiscal_year}) budget limit from ₹".number_format($oldLimit, 2).' to ₹'.number_format($newLimit, 2).'.');

        if ($budget->isNearLimit()) {
            if (! $wasNearLimit) {
                $budget->writeAuditEntry('threshold_alert', $budget->alertMessage().' Trigger: Limit revision.');
            }
            $redirect->with('warning', $budget->alertMessage());
        }

        return $redirect;
    }

    public function destroyBudget(Request $request, DepartmentBudget $budget): RedirectResponse
    {
        abort_unless($request->user()?->role === Roles::ADMIN_HEAD, 403, 'Only the Head Admin can manage department budget limits.');

        if ((float) $budget->utilised_amount > 0 || PurchaseRequisition::where('department', $budget->department)->exists()) {
            return back()->with('error', "Cannot remove {$budget->department} ({$budget->fiscal_year}) because spend or purchase requisitions already reference it.");
        }

        $label = "{$budget->department} ({$budget->fiscal_year})";
        $fy = $budget->fiscal_year;
        $budget->delete();

        return redirect()
            ->route('checks-balances', ['fy' => $fy])
            ->with('status', "Removed unused budget entry for {$label}.");
    }

    public function storeProjectBudget(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->role === Roles::ADMIN_HEAD, 403, 'Only the Head Admin can manage project budget limits.');

        $data = $request->validate([
            'project_name' => ['required', 'string', 'max:150'],
            'department' => ['required', 'string', 'max:100'],
            'council' => ['nullable', 'string', 'max:100'],
            'cluster_head' => ['nullable', 'string', 'max:100'],
            'hod' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:50'],
            'location' => ['nullable', 'string', 'max:100'],
            'event_month' => ['nullable', 'string', 'max:50'],
            'fiscal_year' => ['required', 'string', 'regex:/^FY\d{2}-\d{2}$/'],
            'allocated_amount' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
        ]);

        $projectName = trim($data['project_name']);
        $fiscalYear = $data['fiscal_year'];

        $duplicate = ProjectBudget::whereRaw('LOWER(project_name) = ?', [mb_strtolower($projectName)])
            ->where('fiscal_year', $fiscalYear)
            ->exists();

        if ($duplicate) {
            return back()
                ->withErrors(['project_name' => "A project budget limit for {$projectName} ({$fiscalYear}) already exists."])
                ->withInput()
                ->with('view', 'project');
        }

        $projectBudget = ProjectBudget::create([
            'project_name' => $projectName,
            'department' => trim($data['department']),
            'council' => $data['council'] ?? null,
            'cluster_head' => $data['cluster_head'] ?? null,
            'hod' => $data['hod'] ?? null,
            'category' => $data['category'] ?? null,
            'location' => $data['location'] ?? null,
            'event_month' => $data['event_month'] ?? null,
            'fiscal_year' => $fiscalYear,
            'allocated_amount' => $data['allocated_amount'],
            'utilised_amount' => 0,
        ]);

        return redirect()
            ->route('checks-balances', ['fy' => $projectBudget->fiscal_year, 'view' => 'project'])
            ->with('status', "Project budget limit created for {$projectBudget->project_name} ({$projectBudget->fiscal_year}): ₹".number_format((float) $projectBudget->allocated_amount, 2).'.');
    }

    public function updateProjectBudget(Request $request, ProjectBudget $projectBudget): RedirectResponse
    {
        abort_unless($request->user()?->role === Roles::ADMIN_HEAD, 403, 'Only the Head Admin can manage project budget limits.');

        $data = $request->validate([
            'allocated_amount' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'council' => ['nullable', 'string', 'max:100'],
            'cluster_head' => ['nullable', 'string', 'max:100'],
            'hod' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:50'],
            'location' => ['nullable', 'string', 'max:100'],
            'event_month' => ['nullable', 'string', 'max:50'],
        ]);

        $newLimit = round((float) $data['allocated_amount'], 2);
        $utilised = round((float) $projectBudget->utilised_amount, 2);

        if ($newLimit < $utilised) {
            return back()
                ->withErrors([
                    'allocated_amount' => "Budget limit for {$projectBudget->project_name} cannot be less than already utilised spend (₹".number_format($utilised, 2).').',
                ])
                ->withInput()
                ->with('view', 'project');
        }

        $oldLimit = (float) $projectBudget->allocated_amount;
        $updatePayload = [
            'allocated_amount' => $newLimit,
        ];

        foreach (['council', 'cluster_head', 'hod', 'category', 'location', 'event_month'] as $col) {
            if ($request->has($col)) {
                $updatePayload[$col] = $data[$col];
            }
        }

        $projectBudget->update($updatePayload);

        return redirect()
            ->route('checks-balances', ['fy' => $projectBudget->fiscal_year, 'view' => 'project'])
            ->with('status', "Updated {$projectBudget->project_name} ({$projectBudget->fiscal_year}) budget limit from ₹".number_format($oldLimit, 2).' to ₹'.number_format($newLimit, 2).'.');
    }

    public function destroyProjectBudget(Request $request, ProjectBudget $projectBudget): RedirectResponse
    {
        abort_unless($request->user()?->role === Roles::ADMIN_HEAD, 403, 'Only the Head Admin can remove project budget limits.');

        if ((float) $projectBudget->utilised_amount > 0 || $projectBudget->purchaseRequisitions()->exists()) {
            return back()
                ->with('error', "Cannot remove {$projectBudget->project_name} ({$projectBudget->fiscal_year}) budget because spend or purchase requisitions already reference it.")
                ->with('view', 'project');
        }

        $name = $projectBudget->project_name;
        $fy = $projectBudget->fiscal_year;
        $projectBudget->delete();

        return redirect()
            ->route('checks-balances', ['fy' => $fy, 'view' => 'project'])
            ->with('status', "Removed {$name} ({$fy}) project budget limit.");
    }
}
