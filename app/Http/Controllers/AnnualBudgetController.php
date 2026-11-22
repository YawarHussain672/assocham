<?php

namespace App\Http\Controllers;

use App\Models\AnnualBudget;
use App\Models\AnnualBudgetItem;
use App\Models\Approval;
use App\Models\AuditLog;
use App\Models\DepartmentBudget;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AnnualBudgetController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user, 401);
        abort_if($user->role === Roles::ADMIN_HEAD, 403, 'The Annual Budget module is reserved for HODs, CFO, and Secretary General.');

        $currentFy = DepartmentBudget::currentFiscalYear();
        $selectedFy = trim((string) $request->query('fy', ''));
        $selectedDept = trim((string) $request->query('dept', ''));
        $selectedStatus = trim((string) $request->query('status', ''));
        $search = trim((string) $request->query('q', ''));

        $query = AnnualBudget::with(['submitter', 'departmentBudget'])
            ->latest('id');

        // HODs view their own department's budget proposals
        if ($user->role === Roles::HOD && $user->department) {
            $query->where('department', $user->department);
        }

        if ($selectedFy !== '') {
            $query->where('fiscal_year', $selectedFy);
        }

        if ($selectedDept !== '') {
            $query->where('department', $selectedDept);
        }

        if ($selectedStatus !== '') {
            $query->where('status', $selectedStatus);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('budget_number', 'like', "%{$search}%")
                    ->orWhere('department', 'like', "%{$search}%")
                    ->orWhere('justification', 'like', "%{$search}%");
            });
        }

        $budgets = $query->paginate(15)->withQueryString();

        // Statistics query
        $statsBase = AnnualBudget::query();
        if ($user->role === Roles::HOD && $user->department) {
            $statsBase->where('department', $user->department);
        }

        $allProposals = $statsBase->get(['id', 'status', 'proposed_amount', 'approved_amount']);
        $stats = [
            'total' => $allProposals->count(),
            'pending_cfo' => $allProposals->where('status', AnnualBudget::STATUS_PENDING_CFO)->count(),
            'pending_sg' => $allProposals->where('status', AnnualBudget::STATUS_PENDING_SG)->count(),
            'approved' => $allProposals->where('status', AnnualBudget::STATUS_APPROVED)->count(),
            'rejected' => $allProposals->where('status', AnnualBudget::STATUS_REJECTED)->count(),
            'total_proposed' => (float) $allProposals->sum('proposed_amount'),
            'total_approved' => (float) $allProposals->where('status', AnnualBudget::STATUS_APPROVED)->sum(fn ($b) => $b->approved_amount ?: $b->proposed_amount),
        ];

        $fiscalYears = AnnualBudget::distinct()
            ->pluck('fiscal_year')
            ->push($currentFy)
            ->push($this->nextFiscalYear($currentFy))
            ->unique()
            ->sortDesc()
            ->values();

        $departments = $this->availableDepartments();

        return view('pages.annual-budget-index', [
            'budgets' => $budgets,
            'stats' => $stats,
            'currentFy' => $currentFy,
            'fiscalYears' => $fiscalYears,
            'departments' => $departments,
            'filters' => [
                'fy' => $selectedFy,
                'dept' => $selectedDept,
                'status' => $selectedStatus,
                'q' => $search,
            ],
            'canSubmit' => $user->role === Roles::HOD,
        ]);
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        abort_unless($user?->role === Roles::HOD, 403, 'Only Department HODs can initiate annual budget submissions.');

        $currentFy = DepartmentBudget::currentFiscalYear();
        $nextFy = $this->nextFiscalYear($currentFy);
        $fiscalYears = collect([$currentFy, $nextFy, $this->nextFiscalYear($nextFy)]);

        $departments = $this->availableDepartments();
        $defaultDepartment = $user->department ?: ($departments->first() ?? 'Information Technology');

        // Check if an existing approved budget exists for this dept & next FY
        $existingBudgets = DepartmentBudget::whereIn('fiscal_year', $fiscalYears)->get()->keyBy(fn ($b) => $b->department.'_'.$b->fiscal_year);

        return view('pages.annual-budget-create', [
            'currentFy' => $currentFy,
            'nextFy' => $nextFy,
            'fiscalYears' => $fiscalYears,
            'departments' => $departments,
            'defaultDepartment' => $defaultDepartment,
            'existingBudgets' => $existingBudgets,
            'isHod' => $user->role === Roles::HOD,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user?->role === Roles::HOD, 403, 'Only Department HODs can submit annual budgets.');

        $validated = $request->validate([
            'department' => ['required', 'string', 'max:100'],
            'fiscal_year' => ['required', 'string', 'regex:/^FY\d{2}-\d{2}$/'],
            'proposed_amount' => ['required', 'numeric', 'min:1', 'max:999999999999.99'],
            'justification' => ['required', 'string', 'max:2000'],
            'items' => ['nullable', 'array'],
            'items.*.category' => ['nullable', 'string', 'max:100'],
            'items.*.title' => ['nullable', 'string', 'max:255'],
            'items.*.amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
        ]);

        $department = trim($validated['department']);
        $fiscalYear = trim($validated['fiscal_year']);
        $proposedAmount = round((float) $validated['proposed_amount'], 2);

        // Enforce HOD can only submit for their own department if department is assigned
        if ($user->role === Roles::HOD && $user->department && strcasecmp($user->department, $department) !== 0) {
            return back()->withInput()->withErrors(['department' => "As HOD of {$user->department}, you may only submit annual budgets for your department."]);
        }

        $budgetNumber = $this->nextBudgetNumber($fiscalYear, $department);

        $annualBudget = DB::transaction(function () use ($validated, $user, $department, $fiscalYear, $proposedAmount, $budgetNumber) {
            $budget = AnnualBudget::create([
                'budget_number' => $budgetNumber,
                'department' => $department,
                'fiscal_year' => $fiscalYear,
                'submitted_by' => $user->id,
                'proposed_amount' => $proposedAmount,
                'justification' => trim($validated['justification']),
                'status' => AnnualBudget::STATUS_PENDING_CFO,
                'current_approval_level' => 1,
            ]);

            // Save line items if provided
            if (! empty($validated['items']) && is_array($validated['items'])) {
                foreach ($validated['items'] as $item) {
                    $itemTitle = trim((string) ($item['title'] ?? ''));
                    $itemAmt = (float) ($item['amount'] ?? 0);
                    if ($itemTitle !== '' && $itemAmt > 0) {
                        AnnualBudgetItem::create([
                            'annual_budget_id' => $budget->id,
                            'category' => trim((string) ($item['category'] ?? 'General')),
                            'title' => $itemTitle,
                            'amount' => $itemAmt,
                            'notes' => trim((string) ($item['notes'] ?? '')),
                        ]);
                    }
                }
            }

            // Create 2-level Approval: Level 1 = CFO, Level 2 = SG
            $budget->approvals()->create([
                'level' => 1,
                'role_required' => Roles::CFO,
                'status' => Approval::STATUS_PENDING,
            ]);

            $budget->approvals()->create([
                'level' => 2,
                'role_required' => Roles::SG,
                'status' => Approval::STATUS_PENDING,
            ]);

            $budget->writeAuditEntry(
                'submitted',
                sprintf(
                    'Annual budget proposal %s of ₹%s submitted by %s (%s) for %s (%s). Routed to CFO for Level 1 endorsement.',
                    $budget->budget_number,
                    number_format($proposedAmount, 2),
                    $user->name,
                    Roles::label($user->role),
                    $budget->department,
                    $budget->fiscal_year
                )
            );

            return $budget;
        });

        return redirect()
            ->route('annual-budget.show', $annualBudget)
            ->with('status', "Annual budget proposal {$annualBudget->budget_number} submitted successfully. It has been routed to the CFO for Level 1 review and endorsement.");
    }

    public function show(AnnualBudget $annualBudget): View
    {
        $annualBudget->load(['submitter', 'departmentBudget', 'items', 'approvals.approver']);

        $user = request()->user();
        abort_unless($user, 401);

        // HOD can view proposals for their department; CFO, SG, Admin Head can view all
        if ($user->role === Roles::HOD && $user->department && strcasecmp($user->department, $annualBudget->department) !== 0) {
            abort(403, 'You are not authorized to view budget proposals for other departments.');
        }

        $pendingApproval = $annualBudget->currentApprovalFor($user);

        // Fetch audit logs for this budget
        $auditLogs = AuditLog::where('module', 'AnnualBudget')
            ->where(function ($q) use ($annualBudget) {
                $q->where('reference', $annualBudget->budget_number)
                    ->orWhere('detail', 'like', "%{$annualBudget->budget_number}%");
            })
            ->latest('id')
            ->get();

        // Current active department budget limit for comparison
        $activeDeptBudget = DepartmentBudget::where('department', $annualBudget->department)
            ->where('fiscal_year', $annualBudget->fiscal_year)
            ->first();

        return view('pages.annual-budget-show', [
            'budget' => $annualBudget,
            'pendingApproval' => $pendingApproval,
            'auditLogs' => $auditLogs,
            'activeDeptBudget' => $activeDeptBudget,
            'canEdit' => $annualBudget->canBeEditedBy($user),
        ]);
    }

    public function approve(Request $request, AnnualBudget $annualBudget): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $approval = $annualBudget->currentApprovalFor($user);
        abort_unless($approval, 403, 'No pending approval step on this Annual Budget matches your role.');

        $validated = $request->validate([
            'comments' => ['nullable', 'string', 'max:1000'],
            'approved_amount' => ['nullable', 'numeric', 'min:1', 'max:999999999999.99'],
        ]);

        $comments = trim((string) ($validated['comments'] ?? ''));

        if ($approval->level === 1) {
            // Level 1: CFO Endorsement
            DB::transaction(function () use ($approval, $annualBudget, $user, $comments, $validated) {
                $approval->update([
                    'approver_id' => $user->id,
                    'status' => Approval::STATUS_APPROVED,
                    'acted_at' => now(),
                    'comments' => $comments ?: 'Endorsed and recommended for final approval.',
                ]);

                $annualBudget->update([
                    'cfo_approved_at' => now(),
                    'cfo_remarks' => $comments ?: 'Endorsed and recommended for final approval.',
                    'status' => AnnualBudget::STATUS_PENDING_SG,
                    'current_approval_level' => 2,
                    'approved_amount' => ! empty($validated['approved_amount']) ? round((float) $validated['approved_amount'], 2) : $annualBudget->proposed_amount,
                ]);

                $annualBudget->writeAuditEntry(
                    'cfo_endorsed',
                    sprintf(
                        'Annual budget %s endorsed by CFO %s (Proposed: ₹%s). Advanced to Secretary General (SG) for final sanction.',
                        $annualBudget->budget_number,
                        $user->name,
                        number_format((float) $annualBudget->proposed_amount, 2)
                    )
                );
            });

            return redirect()
                ->route('annual-budget.show', $annualBudget)
                ->with('status', "Annual budget {$annualBudget->budget_number} endorsed by CFO. It is now awaiting final institutional sanction from the Secretary General (SG).");
        }

        if ($approval->level === 2) {
            // Level 2: SG Final Approval & Allocation Finalization
            DB::transaction(function () use ($approval, $annualBudget, $user, $comments, $validated) {
                $approval->update([
                    'approver_id' => $user->id,
                    'status' => Approval::STATUS_APPROVED,
                    'acted_at' => now(),
                    'comments' => $comments ?: 'Final institutional sanction granted.',
                ]);

                $finalAmount = ! empty($validated['approved_amount'])
                    ? round((float) $validated['approved_amount'], 2)
                    : ($annualBudget->approved_amount ?: $annualBudget->proposed_amount);

                // Find or create active DepartmentBudget and update allocation
                $deptBudget = DepartmentBudget::firstOrCreate(
                    [
                        'department' => $annualBudget->department,
                        'fiscal_year' => $annualBudget->fiscal_year,
                    ],
                    [
                        'allocated_amount' => 0,
                        'utilised_amount' => 0,
                        'gst_rate' => 18.00,
                    ]
                );

                $oldAllocation = (float) $deptBudget->allocated_amount;
                $deptBudget->update(['allocated_amount' => $finalAmount]);

                $annualBudget->update([
                    'sg_approved_at' => now(),
                    'sg_remarks' => $comments ?: 'Final institutional sanction granted.',
                    'approved_amount' => $finalAmount,
                    'status' => AnnualBudget::STATUS_APPROVED,
                    'finalized_at' => now(),
                    'department_budget_id' => $deptBudget->id,
                ]);

                $annualBudget->writeAuditEntry(
                    'finalized',
                    sprintf(
                        'Annual budget %s finalized and approved by Secretary General %s. Department budget for %s (%s) set to ₹%s (Previous: ₹%s).',
                        $annualBudget->budget_number,
                        $user->name,
                        $annualBudget->department,
                        $annualBudget->fiscal_year,
                        number_format($finalAmount, 2),
                        number_format($oldAllocation, 2)
                    )
                );
            });

            return redirect()
                ->route('annual-budget.show', $annualBudget)
                ->with('status', "Annual budget {$annualBudget->budget_number} has been finalized and approved by the Secretary General. Active department budget allocation is now updated in the system.");
        }

        return back()->with('error', 'Unknown approval level.');
    }

    public function hold(Request $request, AnnualBudget $annualBudget): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $approval = $annualBudget->currentApprovalFor($user);
        abort_unless($approval, 403, 'No pending approval step on this Annual Budget matches your role.');

        $validated = $request->validate([
            'comments' => ['required', 'string', 'max:1000'],
        ]);

        $comments = trim($validated['comments']);

        DB::transaction(function () use ($approval, $annualBudget, $user, $comments) {
            $approval->update([
                'approver_id' => $user->id,
                'status' => Approval::STATUS_ON_HOLD,
                'acted_at' => now(),
                'comments' => $comments,
            ]);

            $annualBudget->update(['status' => AnnualBudget::STATUS_ON_HOLD]);

            $annualBudget->writeAuditEntry(
                'on_hold',
                "Annual budget {$annualBudget->budget_number} placed on hold by {$user->name} (" . Roles::label($user->role) . "): {$comments}"
            );
        });

        return redirect()
            ->route('annual-budget.show', $annualBudget)
            ->with('status', "Annual budget proposal {$annualBudget->budget_number} has been placed on hold.");
    }

    public function reject(Request $request, AnnualBudget $annualBudget): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $approval = $annualBudget->currentApprovalFor($user);
        abort_unless($approval, 403, 'No pending approval step on this Annual Budget matches your role.');

        $validated = $request->validate([
            'comments' => ['required', 'string', 'max:1000'],
        ]);

        $comments = trim($validated['comments']);

        DB::transaction(function () use ($approval, $annualBudget, $user, $comments) {
            $approval->update([
                'approver_id' => $user->id,
                'status' => Approval::STATUS_REJECTED,
                'acted_at' => now(),
                'comments' => $comments,
            ]);

            $annualBudget->update(['status' => AnnualBudget::STATUS_REJECTED]);

            $annualBudget->writeAuditEntry(
                'rejected',
                "Annual budget {$annualBudget->budget_number} rejected by {$user->name} (" . Roles::label($user->role) . "): {$comments}"
            );
        });

        return redirect()
            ->route('annual-budget.show', $annualBudget)
            ->with('status', "Annual budget proposal {$annualBudget->budget_number} has been rejected.");
    }

    public function edit(AnnualBudget $annualBudget): View
    {
        $user = request()->user();
        abort_unless($user, 401);
        abort_unless($annualBudget->canBeEditedBy($user), 403, 'This annual budget proposal cannot be edited.');

        $annualBudget->load('items');
        $currentFy = DepartmentBudget::currentFiscalYear();
        $fiscalYears = collect([$currentFy, $this->nextFiscalYear($currentFy)]);
        $departments = $this->availableDepartments();

        return view('pages.annual-budget-edit', [
            'budget' => $annualBudget,
            'fiscalYears' => $fiscalYears,
            'departments' => $departments,
            'isHod' => $user->role === Roles::HOD,
        ]);
    }

    public function update(Request $request, AnnualBudget $annualBudget): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user, 401);
        abort_unless($annualBudget->canBeEditedBy($user), 403, 'This annual budget proposal cannot be edited.');

        $validated = $request->validate([
            'proposed_amount' => ['required', 'numeric', 'min:1', 'max:999999999999.99'],
            'justification' => ['required', 'string', 'max:2000'],
            'items' => ['nullable', 'array'],
            'items.*.category' => ['nullable', 'string', 'max:100'],
            'items.*.title' => ['nullable', 'string', 'max:255'],
            'items.*.amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
        ]);

        $proposedAmount = round((float) $validated['proposed_amount'], 2);

        DB::transaction(function () use ($validated, $annualBudget, $user, $proposedAmount) {
            $annualBudget->update([
                'proposed_amount' => $proposedAmount,
                'justification' => trim($validated['justification']),
                'status' => AnnualBudget::STATUS_PENDING_CFO,
                'current_approval_level' => 1,
                'cfo_approved_at' => null,
                'cfo_remarks' => null,
                'sg_approved_at' => null,
                'sg_remarks' => null,
            ]);

            // Re-sync items
            $annualBudget->items()->delete();
            if (! empty($validated['items']) && is_array($validated['items'])) {
                foreach ($validated['items'] as $item) {
                    $itemTitle = trim((string) ($item['title'] ?? ''));
                    $itemAmt = (float) ($item['amount'] ?? 0);
                    if ($itemTitle !== '' && $itemAmt > 0) {
                        AnnualBudgetItem::create([
                            'annual_budget_id' => $annualBudget->id,
                            'category' => trim((string) ($item['category'] ?? 'General')),
                            'title' => $itemTitle,
                            'amount' => $itemAmt,
                            'notes' => trim((string) ($item['notes'] ?? '')),
                        ]);
                    }
                }
            }

            // Reset approvals to pending
            $annualBudget->approvals()->update([
                'status' => Approval::STATUS_PENDING,
                'approver_id' => null,
                'acted_at' => null,
                'comments' => null,
            ]);

            $annualBudget->writeAuditEntry(
                'resubmitted',
                "Annual budget {$annualBudget->budget_number} revised and resubmitted by {$user->name} (" . Roles::label($user->role) . ") for ₹" . number_format($proposedAmount, 2) . ". Reset to Level 1 CFO review."
            );
        });

        return redirect()
            ->route('annual-budget.show', $annualBudget)
            ->with('status', "Annual budget {$annualBudget->budget_number} revised and resubmitted to CFO for Level 1 endorsement.");
    }

    private function availableDepartments()
    {
        return DepartmentBudget::distinct()
            ->pluck('department')
            ->merge(User::whereNotNull('department')->pluck('department'))
            ->filter()
            ->unique()
            ->sort()
            ->values();
    }

    private function nextFiscalYear(string $fy): string
    {
        if (preg_match('/^FY(\d{2})-(\d{2})$/', $fy, $matches)) {
            $start = (int) $matches[1] + 1;
            $end = (int) $matches[2] + 1;

            return sprintf('FY%02d-%02d', $start, $end);
        }

        return 'FY27-28';
    }

    private function nextBudgetNumber(string $fiscalYear, string $department): string
    {
        $deptCode = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $department), 0, 4));
        if ($deptCode === '') {
            $deptCode = 'DEPT';
        }

        $fyShort = str_replace('FY', '', $fiscalYear);
        $count = AnnualBudget::where('fiscal_year', $fiscalYear)->count() + 1;

        do {
            $number = sprintf('AB-%s-%s-%03d', $fyShort, $deptCode, $count);
            $count++;
        } while (AnnualBudget::where('budget_number', $number)->exists());

        return $number;
    }
}
