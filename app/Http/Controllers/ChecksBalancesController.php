<?php

namespace App\Http\Controllers;

use App\Models\DepartmentBudget;
use Illuminate\View\View;

/**
 * Checks & Balances — third Phase 4 (Governance) module (see project
 * memory: erp-laravel-phase4-governance). Unlike the deck (p.23), which
 * frames this as a workflow (Request Raised -> Budget Check -> Quantity
 * Check -> Block/Override), the actual budget-check *enforcement* already
 * lives in PurchaseRequisitionController (built in Phase 2, before this
 * page existed) — reserving/releasing department budget on PR submit/
 * reject. This page is the read-only visibility layer the deck's
 * screenshot (p.43) shows: department-by-department GST-inclusive
 * utilisation with a 90% critical threshold. "Only CFO can override" is
 * implemented via Exception Handling's budget_exceed type, not here — see
 * that controller for the actual override mechanism.
 *
 * No sidebar item existed for this in the inherited prototype (shell.js)
 * — added as a new nav entry now that it's a real page. Restricted to
 * CFO/SG per the deck's "ROLES INVOLVED: System, CFO, SG" (p.23).
 */
class ChecksBalancesController extends Controller
{
    public function index(): View
    {
        $fiscalYear = DepartmentBudget::currentFiscalYear();
        $budgets = DepartmentBudget::where('fiscal_year', $fiscalYear)->orderBy('department')->get();

        return view('pages.checks-balances', [
            'budgets' => $budgets,
            'fiscalYear' => $fiscalYear,
        ]);
    }
}
