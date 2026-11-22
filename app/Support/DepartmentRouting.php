<?php

namespace App\Support;

use App\Models\Grn;
use App\Models\Rfq;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Single source of truth for real-world Single & Multi-HOD / Multi-Buyer routing.
 *
 * HOD Routing Rules (GRN Approvals & L1 Grievances):
 * - Single active HOD in org -> acts as org-wide HOD across all departments.
 * - Multiple active HODs -> strictly department-scoped:
 *     1) Active HOD(s) whose `department` matches the record's department (case-insensitive).
 *     2) If no HOD matches that department, fallback to active HOD(s) assigned to a global
 *        fallback department (`General`, `Administration`, or blank).
 *     3) If still none exist, return empty — preventing cross-department leakage while
 *        allowing Admin Head to act as the safe fallback so nothing ever deadlocks.
 *
 * Buyer Routing Rules (Auto-RFQ POC assignment):
 * - Single active Buyer -> assigned directly.
 * - Multiple active Buyers -> prefers active Buyer(s) matching the PR's department;
 *   when multiple candidates exist in the pool, picks the least-loaded Buyer by
 *   open RFQ count for fair workload distribution. Deactivated Buyers are never picked.
 */
class DepartmentRouting
{
    /** Departments treated as org-wide fallback when no department-specific HOD exists. */
    private const GLOBAL_HOD_DEPARTMENTS = ['', 'general', 'administration'];

    public static function normalizeDepartment(?string $department): string
    {
        return mb_strtolower(trim((string) $department));
    }

    /**
     * @return Collection<int, User>
     */
    public static function eligibleHodsForDepartment(?string $department): Collection
    {
        $activeHods = User::where('role', Roles::HOD)
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        if ($activeHods->isEmpty()) {
            return collect();
        }

        // Single-HOD organization: sole active HOD handles all departments seamlessly.
        if ($activeHods->count() === 1) {
            return $activeHods;
        }

        $target = self::normalizeDepartment($department);

        if ($target !== '') {
            $matched = $activeHods
                ->filter(fn (User $hod) => self::normalizeDepartment($hod->department) === $target)
                ->values();

            if ($matched->isNotEmpty()) {
                return $matched;
            }
        }

        // Fallback to any org-wide HOD (General / Administration / blank) before Admin Head.
        $fallback = $activeHods
            ->filter(fn (User $hod) => in_array(self::normalizeDepartment($hod->department), self::GLOBAL_HOD_DEPARTMENTS, true))
            ->values();

        return $fallback;
    }

    public static function canHodActForDepartment(?User $user, ?string $department): bool
    {
        if (! $user || $user->role !== Roles::HOD || ! $user->isActive()) {
            return false;
        }

        return self::eligibleHodsForDepartment($department)->contains('id', $user->id);
    }

    public static function departmentHasActiveHod(?string $department): bool
    {
        return self::eligibleHodsForDepartment($department)->isNotEmpty();
    }

    public static function grnDepartment(Grn $grn): ?string
    {
        $grn->loadMissing(['purchaseRequisition', 'purchaseOrder.purchaseRequisition', 'creator']);

        return $grn->purchaseRequisition?->department
            ?: $grn->purchaseOrder?->purchaseRequisition?->department
            ?: $grn->creator?->department;
    }

    /**
     * Selects the best active Buyer to own an auto-created RFQ (supports both single and multiple buyers).
     */
    public static function selectBuyerForDepartment(?string $department, ?User $fallback = null): ?User
    {
        $activeBuyers = User::where('role', Roles::BUYER)
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        if ($activeBuyers->isEmpty()) {
            return $fallback;
        }

        if ($activeBuyers->count() === 1) {
            return $activeBuyers->first();
        }

        $target = self::normalizeDepartment($department);
        $pool = $target !== ''
            ? $activeBuyers->filter(fn (User $b) => self::normalizeDepartment($b->department) === $target)->values()
            : collect();

        if ($pool->isEmpty()) {
            $pool = $activeBuyers;
        }

        if ($pool->count() === 1) {
            return $pool->first();
        }

        // Balance across multiple eligible buyers by fewest active/open RFQs.
        $openCounts = Rfq::query()
            ->whereIn('poc_user_id', $pool->pluck('id'))
            ->whereIn('status', ['draft', 'dispatched', 'quotes_received'])
            ->selectRaw('poc_user_id, COUNT(*) as open_count')
            ->groupBy('poc_user_id')
            ->pluck('open_count', 'poc_user_id');

        return $pool
            ->sortBy(fn (User $b) => [(int) ($openCounts[$b->id] ?? 0), $b->id])
            ->first();
    }

    /**
     * All active Buyers (plus Admin Head fallback option if needed) for RFQ ownership assignment.
     *
     * @return Collection<int, User>
     */
    public static function activeBuyers(): Collection
    {
        return User::where('role', Roles::BUYER)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }
}
