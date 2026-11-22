<?php

namespace App\Http\Controllers;

use App\Models\DepartmentBudget;
use App\Models\GrievanceUpdate;
use App\Support\PendingItems;
use App\Support\Roles;
use Illuminate\View\View;

/**
 * Notifications — Action Center for pending approvals, proactive 90% budget
 * alerts for governance roles, and step-by-step grievance update alerts for
 * the ticket raiser.
 */
class NotificationsController extends Controller
{
    public function index(): View
    {
        $user = request()->user();
        $hasBudgetAccess = Roles::canAccessModuleForUser($user, 'checks-balances');
        $budgetAlerts = $hasBudgetAccess
            ? DepartmentBudget::where('fiscal_year', DepartmentBudget::currentFiscalYear())
                ->orderBy('department')
                ->get()
                ->filter->isNearLimit()
                ->values()
            : collect();

        $grievanceUpdates = GrievanceUpdate::with(['grievance', 'actor'])
            ->whereHas('grievance', fn ($q) => $q->where('raised_by', $user->id))
            ->whereNull('read_at')
            ->latest()
            ->get();

        return view('pages.notifications', [
            'items' => PendingItems::for($user),
            'reviewedItems' => PendingItems::reviewedWithin48Hours($user),
            'budgetAlerts' => $budgetAlerts,
            'grievanceUpdates' => $grievanceUpdates,
        ]);
    }
}
