<?php

namespace App\Support;

use App\Models\Approval;
use App\Models\ExceptionRequest;
use App\Models\Grievance;
use App\Models\GrievanceUpdate;
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
    /** Reviewed notifications stay visible for 48 hours before auto-clearing; pending items never expire. */
    public const REVIEWED_CLEAR_HOURS = 48;

    /** approvable_type (FQCN) => [route name, number column, short label, nav module id] */
    private const APPROVABLE_MAP = [
        \App\Models\Vendor::class => ['empanelment.show', 'vendor_code', 'Vendor Empanelment', 'empanelment'],
        \App\Models\PurchaseRequisition::class => ['requisition.show', 'pr_number', 'Purchase Requisition', 'requisition'],
        \App\Models\PurchaseOrder::class => ['po.show', 'po_number', 'Purchase Order', 'po'],
        \App\Models\Grn::class => ['grn.show', 'grn_number', 'Goods Receipt (GRN)', 'grn'],
        \App\Models\Trf::class => ['trf.show', 'trf_number', 'Travel Requisition', 'trf'],
        \App\Models\Reimbursement::class => ['reimb.show', 'claim_number', 'Reimbursement Claim', 'reimb'],
        \App\Models\AnnualBudget::class => ['annual-budget.show', 'budget_number', 'Annual Budget Proposal', 'annual-budget'],
    ];

    /**
     * @return Collection<int, array{label: string, number: string, url: string, module: string}>
     */
    public static function for(User $user): Collection
    {
        $items = collect();

        Approval::whereIn('status', ['pending', 'on_hold'])
            ->with('approvable')
            ->get()
            ->groupBy(fn (Approval $a) => $a->approvable_type.':'.$a->approvable_id)
            ->each(function (Collection $group) use (&$items, $user) {
                $lowest = $group->whereIn('status', ['pending', 'on_hold'])->sortBy('level')->first();
                if (! $lowest || ! $lowest->approvable) {
                    return;
                }

                $canAdminOverride = $user->role === Roles::ADMIN_HEAD && (bool) config('app.allow_admin_approval_override', true);

                // Department-scoped HOD routing for GRN approvals (single-HOD remains org-wide automatically).
                if ($lowest->approvable instanceof \App\Models\Grn && $lowest->role_required === Roles::HOD) {
                    $grnDepartment = DepartmentRouting::grnDepartment($lowest->approvable);
                    $isAllowed = DepartmentRouting::canHodActForDepartment($user, $grnDepartment)
                        || ($user->role === Roles::ADMIN_HEAD && ($canAdminOverride || ! DepartmentRouting::departmentHasActiveHod($grnDepartment)));
                } else {
                    $isAllowed = in_array($user->role, explode(',', $lowest->role_required), true) || $canAdminOverride;
                }

                if (! $isAllowed) {
                    return;
                }

                // Segregation of Duties: exclude self-owned TRFs & Reimbursements from an approver's queue
                if ($lowest->approvable instanceof \App\Models\Trf && (int) $lowest->approvable->employee_id === (int) $user->id) {
                    return;
                }
                if ($lowest->approvable instanceof \App\Models\Reimbursement && (int) $lowest->approvable->employee_id === (int) $user->id) {
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

        if (Roles::canAccessModuleForUser($user, 'grievance')) {
            $gQuery = Grievance::with('raisedBy')->where('status', 'under_review');
            if ($user->role !== Roles::ADMIN_HEAD) {
                $gQuery->where('current_owner_role', $user->role);
            }
            $gQuery->get()
                ->filter(function (Grievance $g) use ($user) {
                    if ($user->role === Roles::HOD) {
                        return DepartmentRouting::canHodActForDepartment($user, $g->raisedBy?->department);
                    }

                    return true;
                })
                ->each(fn (Grievance $g) => $items->push([
                    'label' => 'Grievance',
                    'number' => $g->ticket_number,
                    'url' => route('grievance.show', $g),
                    'module' => 'grievance',
                ]));
        }

        if (Roles::canAccessModuleForUser($user, 'exceptions')) {
            $eQuery = ExceptionRequest::whereIn('status', ['pending', 'on_hold']);
            $canAdminOverrideExc = $user->role === Roles::ADMIN_HEAD && (bool) config('app.allow_admin_approval_override', true);
            if (! $canAdminOverrideExc) {
                $eQuery->where('approver_queue', $user->role);
            }
            $eQuery->get()
                ->each(fn (ExceptionRequest $e) => $items->push([
                    'label' => 'Exception',
                    'number' => '#'.$e->id,
                    'url' => route('exceptions.show', $e),
                    'module' => 'exceptions',
                ]));
        }

        return $items->values();
    }

    /**
     * Notifications that this user has already reviewed/acted on within the last 48 hours.
     * Unreviewed/pending notifications never auto-clear — only reviewed ones clear after 48h.
     *
     * @return Collection<int, array{label: string, number: string, url: string, module: string, decision: string, reviewed_at: \Illuminate\Support\Carbon, clears_at: \Illuminate\Support\Carbon}>
     */
    public static function reviewedWithin48Hours(User $user): Collection
    {
        $cutoff = now()->subHours(self::REVIEWED_CLEAR_HOURS);
        $reviewed = collect();

        Approval::where('approver_id', $user->id)
            ->where('status', '!=', 'pending')
            ->whereNotNull('acted_at')
            ->where('acted_at', '>=', $cutoff)
            ->with('approvable')
            ->latest('acted_at')
            ->get()
            ->each(function (Approval $approval) use (&$reviewed) {
                if (! $approval->approvable) {
                    return;
                }

                [$route, $numberColumn, $label, $module] = self::APPROVABLE_MAP[$approval->approvable_type]
                    ?? [null, null, class_basename($approval->approvable_type), null];
                if (! $route) {
                    return;
                }

                $reviewed->push([
                    'label' => $label,
                    'number' => $approval->approvable->{$numberColumn},
                    'url' => route($route, $approval->approvable),
                    'module' => $module,
                    'decision' => ucfirst(str_replace('_', ' ', $approval->status)),
                    'reviewed_at' => $approval->acted_at,
                    'clears_at' => $approval->acted_at->copy()->addHours(self::REVIEWED_CLEAR_HOURS),
                ]);
            });

        if (Roles::canAccessModuleForUser($user, 'exceptions')) {
            ExceptionRequest::where('decided_by', $user->id)
                ->where('status', '!=', 'pending')
                ->whereNotNull('decided_at')
                ->where('decided_at', '>=', $cutoff)
                ->latest('decided_at')
                ->get()
                ->each(fn (ExceptionRequest $e) => $reviewed->push([
                    'label' => 'Exception',
                    'number' => '#'.$e->id,
                    'url' => route('exceptions.show', $e),
                    'module' => 'exceptions',
                    'decision' => ucfirst($e->status),
                    'reviewed_at' => $e->decided_at,
                    'clears_at' => $e->decided_at->copy()->addHours(self::REVIEWED_CLEAR_HOURS),
                ]));
        }

        if (Roles::canAccessModuleForUser($user, 'grievance')) {
            GrievanceUpdate::with('grievance')
                ->whereHas('grievance', fn ($q) => $q->where('raised_by', $user->id))
                ->whereNotNull('read_at')
                ->where('read_at', '>=', $cutoff)
                ->latest('read_at')
                ->get()
                ->each(function (GrievanceUpdate $u) use (&$reviewed) {
                    if (! $u->grievance) {
                        return;
                    }

                    $reviewed->push([
                        'label' => 'Grievance Update',
                        'number' => $u->grievance->ticket_number,
                        'url' => route('grievance.show', $u->grievance),
                        'module' => 'grievance',
                        'decision' => 'Reviewed',
                        'reviewed_at' => $u->read_at,
                        'clears_at' => $u->read_at->copy()->addHours(self::REVIEWED_CLEAR_HOURS),
                    ]);
                });
        }

        return $reviewed
            ->sortByDesc(fn (array $row) => $row['reviewed_at']?->timestamp ?? 0)
            ->values();
    }
}
