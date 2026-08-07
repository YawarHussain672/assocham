<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\DepartmentBudget;
use App\Support\PendingItems;
use App\Support\Roles;
use Illuminate\View\View;

/**
 * Dashboard — Phase 5 (Polish). Replaces the original static-mockup
 * dashboard (bar chart / donut chart / activity list, all driven by mock
 * public/js/data.js arrays via initDashboardPage()) with real data now
 * that every module behind it exists (Phases 2–4).
 *
 * Centerpiece is "Items Awaiting My Approval" — see App\Support\PendingItems
 * for the actual cross-module aggregation (extracted there once
 * NotificationsController needed the identical query).
 *
 * The department budget summary is only shown to CFO/SG, matching
 * Checks & Balances' own access restriction — the dashboard is open to
 * every role, so it must not leak budget visibility beyond what that
 * page's own gate already allows.
 */
class DashboardController extends Controller
{
    public function index(): View
    {
        $user = request()->user();

        return view('pages.index', [
            'pendingItems' => PendingItems::for($user),
            'recentActivity' => AuditLog::with('user')->latest('created_at')->limit(10)->get(),
            'budgets' => Roles::canAccessModule($user->role, 'checks-balances')
                ? DepartmentBudget::where('fiscal_year', DepartmentBudget::currentFiscalYear())->orderBy('department')->get()
                : null,
        ]);
    }
}
