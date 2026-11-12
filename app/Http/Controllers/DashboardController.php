<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\DepartmentBudget;
use App\Models\Grn;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\PurchaseRequisitionItem;
use App\Models\Reimbursement;
use App\Models\Rfq;
use App\Models\Trf;
use App\Models\Vendor;
use App\Support\PendingItems;
use App\Support\Roles;
use Illuminate\View\View;

/**
 * Dashboard — Data-driven analytics engine.
 * Computes live operational, procurement, budget, and workflow metrics
 * across all system entities.
 */
class DashboardController extends Controller
{
    public function index(): View
    {
        $user = request()->user();
        $fiscalYear = DepartmentBudget::currentFiscalYear();

        // 1. Pending Approvals for current user
        $pendingItems = PendingItems::for($user);

        // 2. Budget Analytics
        $hasBudgetAccess = Roles::canAccessModuleForUser($user, 'checks-balances');
        $budgets = DepartmentBudget::where('fiscal_year', $fiscalYear)
            ->orderByDesc('allocated_amount')
            ->get();

        $totalAllocated = (float) $budgets->sum('allocated_amount');
        $totalUtilised = (float) $budgets->sum('utilised_amount');
        $totalRemaining = max(0, $totalAllocated - $totalUtilised);
        $overallUtilisation = $totalAllocated > 0 ? round(($totalUtilised / $totalAllocated) * 100, 1) : 0.0;
        $nearLimitCount = $budgets->filter->isNearLimit()->count();

        // 3. Procurement Pipeline Analytics
        $totalPrs = PurchaseRequisition::count();
        $approvedPrs = PurchaseRequisition::where('status', 'approved')->count();
        $pendingPrs = PurchaseRequisition::where('status', 'like', 'pending%')->count();
        $totalLineItems = PurchaseRequisitionItem::count();

        $totalRfqs = Rfq::count();
        $totalPos = PurchaseOrder::count();
        $approvedPos = PurchaseOrder::where('status', 'approved')->count();
        $totalPoSpend = (float) PurchaseOrder::where('status', 'approved')->sum('amount_incl_gst');

        $totalVendors = Vendor::count();
        $approvedVendors = Vendor::where('status', 'approved')->count();

        $totalTrfs = Trf::count();
        $approvedTrfs = Trf::where('status', 'approved')->count();
        $totalReimbursements = Reimbursement::count();
        $totalReimbClaimed = (float) Reimbursement::where('status', 'approved')->sum('claim_amount');

        $totalWorkflows = $totalPrs + $totalPos + $totalTrfs + $totalReimbursements;

        // 4. Department Requisition Distribution
        $prDepartmentCounts = PurchaseRequisition::selectRaw('department, count(*) as count')
            ->groupBy('department')
            ->pluck('count', 'department');

        // 5. Audit Activity Analytics
        $recentActivity = AuditLog::with('user')->latest('created_at')->limit(8)->get();
        $auditModuleCounts = AuditLog::selectRaw('module, count(*) as count')
            ->groupBy('module')
            ->orderByDesc('count')
            ->limit(6)
            ->get();
        $totalAuditLogs = AuditLog::count();

        return view('pages.index', [
            'pendingItems' => $pendingItems,
            'recentActivity' => $recentActivity,
            'hasBudgetAccess' => $hasBudgetAccess,
            'budgets' => $hasBudgetAccess ? $budgets : null,
            'nearLimitBudgets' => $hasBudgetAccess ? $budgets->filter->isNearLimit()->values() : collect(),
            'allBudgets' => $budgets,
            'totalAllocated' => $totalAllocated,
            'totalUtilised' => $totalUtilised,
            'totalRemaining' => $totalRemaining,
            'overallUtilisation' => $overallUtilisation,
            'nearLimitCount' => $nearLimitCount,
            'fiscalYear' => $fiscalYear,
            'totalPrs' => $totalPrs,
            'approvedPrs' => $approvedPrs,
            'pendingPrs' => $pendingPrs,
            'totalLineItems' => $totalLineItems,
            'totalRfqs' => $totalRfqs,
            'totalPos' => $totalPos,
            'approvedPos' => $approvedPos,
            'totalPoSpend' => $totalPoSpend,
            'totalVendors' => $totalVendors,
            'approvedVendors' => $approvedVendors,
            'totalTrfs' => $totalTrfs,
            'approvedTrfs' => $approvedTrfs,
            'totalReimbursements' => $totalReimbursements,
            'totalReimbClaimed' => $totalReimbClaimed,
            'totalWorkflows' => $totalWorkflows,
            'prDepartmentCounts' => $prDepartmentCounts,
            'auditModuleCounts' => $auditModuleCounts,
            'totalAuditLogs' => $totalAuditLogs,
        ]);
    }
}
