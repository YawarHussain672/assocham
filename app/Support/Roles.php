<?php

namespace App\Support;

use App\Models\User;

/**
 * The ten role logins from the ASSOCHAM Admin & Procurement Portal spec
 * (see project memory: assocham-portal-spec). Central place for role
 * slugs so Phase 1 (access control) and later phases can gate on these
 * instead of re-typing string literals everywhere.
 */
class Roles
{
    public const PR_SR_EXEC = 'pr_sr_exec';
    public const BUYER = 'buyer';
    public const ADMIN_HEAD = 'admin_head';
    public const PR_APPROVER_L1 = 'pr_approver_l1'; // DSG in the deck ("Basu")
    public const PR_APPROVER_L2 = 'pr_approver_l2'; // Head of States in the deck ("Ambika")
    public const DSG = self::PR_APPROVER_L1;
    public const HEAD_OF_STATES = self::PR_APPROVER_L2;
    public const ASG1 = 'asg1';
    public const ASG2 = 'asg2';
    public const CFO = 'cfo';
    public const SG = 'sg';
    public const HOD = 'hod';

    /**
     * @return array<string, string> role slug => display label
     */
    public static function labels(): array
    {
        return [
            self::PR_SR_EXEC => 'PR & GRN Creator',
            self::BUYER => 'Buyer',
            self::ADMIN_HEAD => 'Admin Head',
            self::PR_APPROVER_L1 => 'DSG — Deputy Secretary General (Basu)',
            self::PR_APPROVER_L2 => 'Head of States (Ambika)',
            self::ASG1 => 'ASG 1',
            self::ASG2 => 'ASG 2',
            self::CFO => 'CFO',
            self::SG => 'Secretary General',
            self::HOD => 'CH / HOD',
        ];
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return array_keys(self::labels());
    }

    public static function label(string $role): string
    {
        return self::labels()[$role] ?? match ($role) {
            'membership_ops' => 'Membership Operations',
            'b2b_ops' => 'B2B Networking',
            default => \Illuminate\Support\Str::headline($role),
        };
    }

    /**
     * Renders a comma-joined role_required value (e.g. "asg1,asg2") as a
     * human label ("ASG 1 or ASG 2"). Used where an approval step can be
     * filled by more than one interchangeable role — see TrfController,
     * which is the first module needing this (ASG 1 and ASG 2 are
     * genuinely interchangeable for TRF approval, unlike e.g. Basu/Ambika
     * on PRs, who are distinct fixed levels).
     */
    public static function labelAny(string $commaJoinedRoles): string
    {
        return implode(' or ', array_map(self::label(...), explode(',', $commaJoinedRoles)));
    }

    /**
     * Module ids a role sees/may access, on top of MODULES_FOR_ALL below.
     * "Module id" = the shell.js NAV_SECTIONS id = the routes/web.php route
     * name (dashboard/onboarding/empanelment/requisition/rfq/ccs/po/grn/
     * trf/flight/reimb/traveleval/notifications/audit/grievance).
     *
     * Synthesized from the deck's per-role dashboard screenshots (p.6) and
     * the permission matrix (p.7); the deck doesn't give a screenshot for
     * every one of the 10 roles, so PR_APPROVER_L1/L2, ASG1/ASG2 and HOD
     * are inferred from their described duties. Adjust here if product
     * feedback says otherwise — this is Phase 1's first pass, not gospel.
     *
     * @return array<string, list<string>>
     */
    private const MODULE_ACCESS = [
        self::PR_SR_EXEC => ['requisition', 'grn'],
        self::BUYER => ['onboarding', 'empanelment', 'item-catalogs', 'rfq', 'po'],
        self::ADMIN_HEAD => [
            'onboarding',
            'empanelment',
            'requisition',
            'item-catalogs',
            'rfq',
            'po',
            'grn',
            'events',
            'trf',
            'flight',
            'reimb',
            'traveleval',
            'checks-balances',
            'annual-budget',
            'exceptions',
            'grievance',
            'audit',
            'notifications',
            'users',
        ],
        self::PR_APPROVER_L1 => ['requisition'],
        self::PR_APPROVER_L2 => ['requisition'],
        self::ASG1 => ['onboarding', 'empanelment', 'requisition'],
        self::ASG2 => ['onboarding', 'empanelment', 'requisition'],
        self::CFO => ['onboarding', 'empanelment', 'requisition', 'po', 'events', 'checks-balances', 'annual-budget', 'exceptions'],
        self::SG => ['requisition', 'po', 'events', 'checks-balances', 'annual-budget', 'exceptions'],
        self::HOD => ['grn', 'requisition', 'annual-budget'],
    ];

    /**
     * Every authenticated role gets these regardless of their row above:
     * their own dashboard, notifications, the (view-only) audit trail, and
     * grievances (deck: "CREATED / INITIATED BY: Any User"). `trf` and
     * `reimb` are here too — "Employee" (deck p.16/p.18's TRF/claim
     * creator) isn't one of the 10 named logins, and everyone in the org
     * can travel and file their own expense claim, so both are open to any
     * authenticated role rather than gated to a specific one. Approval is
     * still restricted (ASG1/ASG2 domestic / SG international for TRF; CFO
     * standard / SG for ASG-filed or off-TRF claims for reimbursement) —
     * that's enforced by the approval-step check in each controller, not
     * by this module-level list.
     *
     * @var list<string>
     */
    private const MODULES_FOR_ALL = ['dashboard', 'notifications', 'audit', 'grievance', 'trf', 'reimb'];

    /**
     * Exact sidebar menu items per login from Slide 6 ("Separate Login, Separate Menu").
     * Each role only sees the navigation links and modules within their direct mandate.
     * All unauthorized sections and modules are completely excluded from that admin's sidebar.
     *
     * @var array<string, list<string>>
     */
    private const SIDEBAR_MODULES = [
        self::PR_SR_EXEC => [
            'dashboard',
            'requisition',
            'grn',
            'grievance',
            'notifications',
        ],
        self::BUYER => [
            'dashboard',
            'onboarding',
            'empanelment',
            'item-catalogs',
            'rfq',
            'po',
            'grievance',
            'notifications',
        ],
        self::ADMIN_HEAD => [
            'dashboard',
            'onboarding',
            'empanelment',
            'requisition',
            'item-catalogs',
            'rfq',
            'po',
            'grn',
            'events',
            'trf',
            'flight',
            'reimb',
            'checks-balances',
            'annual-budget',
            'grievance',
            'audit',
            'notifications',
            'users',
        ],
        self::PR_APPROVER_L1 => [
            'dashboard',
            'requisition',
            'grievance',
            'notifications',
        ],
        self::PR_APPROVER_L2 => [
            'dashboard',
            'requisition',
            'grievance',
            'notifications',
        ],
        self::ASG1 => [
            'dashboard',
            'empanelment',
            'requisition',
            'trf',
            'grievance',
            'notifications',
        ],
        self::ASG2 => [
            'dashboard',
            'empanelment',
            'requisition',
            'trf',
            'grievance',
            'notifications',
        ],
        self::CFO => [
            'dashboard',
            'empanelment',
            'requisition',
            'po',
            'events',
            'reimb',
            'checks-balances',
            'annual-budget',
            'audit',
            'grievance',
            'notifications',
        ],
        self::SG => [
            'dashboard',
            'requisition',
            'po',
            'events',
            'trf',
            'reimb',
            'checks-balances',
            'annual-budget',
            'audit',
            'grievance',
            'notifications',
        ],
        self::HOD => [
            'dashboard',
            'annual-budget',
            'grn',
            'reimb',
            'grievance',
            'notifications',
        ],
    ];

    /**
     * @return list<string> module ids to display in the sidebar for this role
     */
    public static function sidebarModulesFor(?string $role): array
    {
        return self::SIDEBAR_MODULES[$role] ?? ['dashboard', 'trf', 'reimb', 'grievance', 'notifications'];
    }

    /**
     * @return list<string> module ids this role may access
     */
    public static function modulesFor(?string $role): array
    {
        $roleSpecific = self::MODULE_ACCESS[$role] ?? [];

        return array_values(array_unique([...self::MODULES_FOR_ALL, ...$roleSpecific]));
    }

    public static function canAccessModule(?string $role, string $module): bool
    {
        return in_array($module, self::modulesFor($role), true);
    }

    /**
     * Effective sidebar modules for an authenticated user.
     * Uses custom `allowed_modules` when explicitly configured by Head Admin,
     * falling back cleanly to role defaults when `allowed_modules` is null.
     *
     * @return list<string>
     */
    public static function sidebarModulesForUser(?User $user): array
    {
        if (! $user) {
            return ['dashboard', 'notifications'];
        }

        if (is_array($user->allowed_modules)) {
            $valid = array_values(array_intersect(
                self::selectableModuleIds(),
                $user->allowed_modules
            ));

            return array_values(array_unique(['dashboard', ...$valid]));
        }

        return self::sidebarModulesFor($user->role);
    }

    /**
     * Effective route/module authorization check for an authenticated user.
     */
    public static function canAccessModuleForUser(?User $user, string $module): bool
    {
        if (! $user) {
            return false;
        }

        if ($module === 'dashboard') {
            return true;
        }

        if (is_array($user->allowed_modules)) {
            return in_array($module, $user->allowed_modules, true);
        }

        return self::canAccessModule($user->role, $module);
    }

    /**
     * Catalog of assignable modules grouped by sidebar section for the
     * Head Admin employee creation / module access matrix.
     *
     * @return array<string, list<array{id: string, label: string, hint: string}>>
     */
    public static function moduleCatalog(): array
    {
        return [
            'Procurement' => [
                ['id' => 'onboarding', 'label' => 'Vendor Onboarding', 'hint' => 'Invite and onboard new vendors'],
                ['id' => 'empanelment', 'label' => 'Empaneled Vendors', 'hint' => 'View & manage approved vendor directory'],
                ['id' => 'requisition', 'label' => 'Purchase Requisitions (PR)', 'hint' => 'Raise & approve purchase requisitions'],
                ['id' => 'rfq', 'label' => 'RFQ & Comparison (CCS)', 'hint' => 'Issue RFQs & evaluate comparative quotes'],
                ['id' => 'po', 'label' => 'Purchase Orders (PO)', 'hint' => 'Create & track purchase orders'],
                ['id' => 'grn', 'label' => 'Goods Receipt (GRN)', 'hint' => 'Verify & record delivered goods'],
            ],
            'Travel & Logistics' => [
                ['id' => 'trf', 'label' => 'Travel Requests (TRF)', 'hint' => 'Submit & manage travel authorizations'],
                ['id' => 'flight', 'label' => 'Flight Booking Logs', 'hint' => 'Log flight bookings & travel proofs'],
                ['id' => 'reimb', 'label' => 'Reimbursements & Advances', 'hint' => 'File & settle expense claims'],
                ['id' => 'traveleval', 'label' => 'Travel Agency Eval', 'hint' => 'Score & review travel agency performance'],
            ],
            'Governance & Controls' => [
                ['id' => 'checks-balances', 'label' => 'Checks & Balances', 'hint' => 'Department budget controls & limits'],
                ['id' => 'annual-budget', 'label' => 'Annual Budget', 'hint' => 'Submit & approve annual department budgets'],
                ['id' => 'events', 'label' => 'Event-Wise PR & PO', 'hint' => 'Track budgets, PRs and POs per event/project'],
                ['id' => 'item-catalogs', 'label' => 'Item & Price Master', 'hint' => 'Predefine items, fixed prices & OPEX/COPEX'],
                ['id' => 'exceptions', 'label' => 'Exceptions & Deviations', 'hint' => 'View & manage exception approvals'],
                ['id' => 'audit', 'label' => 'Audit Trail', 'hint' => 'Inspect immutable system audit logs'],
            ],
            'System & Support' => [
                ['id' => 'grievance', 'label' => 'Grievance Redressal', 'hint' => 'Raise or handle grievance tickets'],
                ['id' => 'notifications', 'label' => 'Notifications', 'hint' => 'Action center alerts & pending items'],
                ['id' => 'users', 'label' => 'User & Access Management', 'hint' => 'Add employees & configure module access'],
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function selectableModuleIds(): array
    {
        $ids = [];
        foreach (self::moduleCatalog() as $items) {
            foreach ($items as $item) {
                $ids[] = $item['id'];
            }
        }

        return $ids;
    }

    /**
     * Default selectable module list per role (used for UI presets).
     *
     * @return array<string, list<string>>
     */
    public static function roleDefaultsMap(): array
    {
        $map = [];
        foreach (self::all() as $role) {
            $defaults = array_values(array_diff(self::sidebarModulesFor($role), ['dashboard']));
            $map[$role] = $defaults;
        }

        return $map;
    }

    /**
     * Maps a real (10-role) login to the closest of the legacy 6 mock
     * "preview" roles in public/js/data.js's ROLES array — pr_exec, buyer,
     * admin_head, asg_ch, cfo, sg. That mock system drives the prototype's
     * placeholder dashboard content/approve-reject buttons and predates
     * real auth; it isn't being torn out mid-build (see project memory:
     * erp-laravel-phase1-access-control), so on login we just seed it to
     * whichever mock role reads closest, purely so the demo content isn't
     * randomly mismatched from who actually signed in.
     */
    public static function previewRoleFor(?string $role): string
    {
        return match ($role) {
            self::PR_SR_EXEC => 'pr_exec',
            self::BUYER => 'buyer',
            self::ADMIN_HEAD, self::HOD => 'admin_head',
            self::PR_APPROVER_L1, self::PR_APPROVER_L2, self::ASG1, self::ASG2 => 'asg_ch',
            self::CFO => 'cfo',
            self::SG => 'sg',
            default => 'buyer',
        };
    }
}
