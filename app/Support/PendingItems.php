<?php

namespace App\Support;

use App\Models\Approval;
use App\Models\ExceptionRequest;
use App\Models\Grievance;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Cross-module "what's waiting on me right now" aggregation — originally
 * built inline in DashboardController (Phase 5's real Dashboard), extracted
 * here once NotificationsController needed the identical query. Single
 * source of truth for both the Dashboard's "Items Awaiting My Approval"
 * panel, the Notifications page, and the sidebar/topbar badge counts.
 *
 * Queries the polymorphic `approvals` table directly and keeps only the
 * lowest pending level per parent record (the one actually actionable —
 * mirrors every controller's own currentApprovalFor() logic), then merges
 * in Grievances/Exceptions, which route by current_owner_role/
 * approver_queue rather than the approvals table.
 */
class PendingItems
{
    /** approvable_type (FQCN) => [route name, number column, short label, nav module id] */
    private const APPROVABLE_MAP = [
        \App\Models\Vendor::class => ['empanelment.show', 'vendor_code', 'Vendor Empanelment', 'empanelment'],
        \App\Models\PurchaseRequisition::class => ['requisition.show', 'pr_number', 'Purchase Requisition', 'requisition'],
        \App\Models\CostComparativeStatement::class => ['ccs.show', 'ccs_number', 'Cost Comparative Statement', 'ccs'],
        \App\Models\PurchaseOrder::class => ['po.show', 'po_number', 'Purchase Order', 'po'],
        \App\Models\Grn::class => ['grn.show', 'grn_number', 'Goods Receipt (GRN)', 'grn'],
        \App\Models\Trf::class => ['trf.show', 'trf_number', 'Travel Requisition', 'trf'],
        \App\Models\Reimbursement::class => ['reimb.show', 'claim_number', 'Reimbursement Claim', 'reimb'],
    ];

    /**
     * @return Collection<int, array{label: string, number: string, url: string, module: string}>
     */
    public static function for(User $user): Collection
    {
        $items = collect();

        Approval::where('status', 'pending')
            ->with('approvable')
            ->get()
            ->groupBy(fn (Approval $a) => $a->approvable_type.':'.$a->approvable_id)
            ->each(function (Collection $group) use (&$items, $user) {
                $lowest = $group->sortBy('level')->first();
                if (! $lowest->approvable || ! in_array($user->role, explode(',', $lowest->role_required), true)) {
                    return;
                }

                [$route, $numberColumn, $label, $module] = self::APPROVABLE_MAP[$lowest->approvable_type]
                    ?? [null, null, class_basename($lowest->approvable_type), null];
                if (! $route) {
                    return;
                }

                $items->push([
                    'label' => $label,
                    'number' => $lowest->approvable->{$numberColumn},
                    'url' => route($route, $lowest->approvable),
                    'module' => $module,
                ]);
            });

        if (Roles::canAccessModule($user->role, 'grievance')) {
            Grievance::where('status', 'under_review')->where('current_owner_role', $user->role)->get()
                ->each(fn (Grievance $g) => $items->push([
                    'label' => 'Grievance',
                    'number' => $g->ticket_number,
                    'url' => route('grievance.show', $g),
                    'module' => 'grievance',
                ]));
        }

        if (Roles::canAccessModule($user->role, 'exceptions')) {
            ExceptionRequest::where('status', 'pending')->where('approver_queue', $user->role)->get()
                ->each(fn (ExceptionRequest $e) => $items->push([
                    'label' => 'Exception',
                    'number' => '#'.$e->id,
                    'url' => route('exceptions.show', $e),
                    'module' => 'exceptions',
                ]));
        }

        return $items->values();
    }
}
