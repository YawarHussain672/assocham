<?php

namespace App\Http\Controllers;

use App\Models\Grievance;
use App\Support\Roles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Grievance Redressal — first Phase 4 (Governance) module (see project
 * memory: assocham-portal-spec, erp-laravel-phase4-governance). Workflow
 * (deck p.25): Ticket Raised (any user) -> Routed (HOD -> Admin Head ->
 * CFO -> SG) -> Under Review (tracked status) -> Resolved.
 *
 * No polymorphic `approvals` row here — the routing is a single mutable
 * `current_owner_role` field walked forward by Grievance::nextOwnerRole()
 * (built in Phase 0, unused until now), not a set of parallel approval
 * steps. "Escalate" only the current owner can do (moves ownership
 * forward); "resolve" any of the four governance roles can do at any
 * point, per the deck ("Any of HOD / Admin Head / CFO / SG can mark
 * resolved") — not just whoever currently holds it.
 */
class GrievanceController extends Controller
{
    private const GOVERNANCE_ROLES = [Roles::HOD, Roles::ADMIN_HEAD, Roles::CFO, Roles::SG];

    private const CATEGORIES = ['Technical Issue', 'Operational Delay', 'Budget Discrepancy', 'Vendor Dispute'];

    public function index(): View
    {
        $grievances = Grievance::with(['raisedBy', 'resolvedBy'])->latest()->get();

        return view('pages.grievance', ['grievances' => $grievances, 'categories' => self::CATEGORIES]);
    }

    public function create(): View
    {
        return view('pages.grievance-create', ['categories' => self::CATEGORIES]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subject_category' => ['required', 'in:'.implode(',', self::CATEGORIES)],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $grievance = Grievance::create([
            'ticket_number' => $this->nextTicketNumber(),
            'subject_category' => $data['subject_category'],
            'subject' => $data['subject'],
            'description' => $data['description'] ?? null,
            'raised_by' => $request->user()->id,
            'current_owner_role' => Roles::HOD,
            'status' => 'under_review',
        ]);

        return redirect()->route('grievance')->with('status', "{$grievance->ticket_number} raised — routed to HOD.");
    }

    public function show(Grievance $grievance): View
    {
        $grievance->load(['raisedBy', 'resolvedBy']);

        return view('pages.grievance-show', [
            'grievance' => $grievance,
            'canEscalate' => $grievance->status === 'under_review' && request()->user()->role === $grievance->current_owner_role,
            'canResolve' => $grievance->status === 'under_review' && in_array(request()->user()->role, self::GOVERNANCE_ROLES, true),
        ]);
    }

    public function escalate(Request $request, Grievance $grievance): RedirectResponse
    {
        abort_unless($grievance->status === 'under_review', 422, 'This ticket is already resolved.');
        abort_unless($request->user()->role === $grievance->current_owner_role, 403, 'Only the current owner can escalate this ticket.');

        $next = $grievance->nextOwnerRole();
        abort_unless($next, 422, 'This ticket is already at the final escalation level (SG) — resolve it instead.');

        $grievance->update(['current_owner_role' => $next]);

        return back()->with('status', "{$grievance->ticket_number} escalated to ".Roles::label($next).'.');
    }

    public function resolve(Request $request, Grievance $grievance): RedirectResponse
    {
        abort_unless($grievance->status === 'under_review', 422, 'This ticket is already resolved.');
        abort_unless(in_array($request->user()->role, self::GOVERNANCE_ROLES, true), 403, 'Only HOD, Admin Head, CFO or SG may resolve grievances.');

        $grievance->update([
            'status' => 'resolved',
            'resolved_by' => $request->user()->id,
            'resolved_at' => now(),
        ]);

        return back()->with('status', "{$grievance->ticket_number} marked resolved.");
    }

    private function nextTicketNumber(): string
    {
        do {
            $number = 'GRV-'.random_int(1000, 9999);
        } while (Grievance::where('ticket_number', $number)->exists());

        return $number;
    }
}
