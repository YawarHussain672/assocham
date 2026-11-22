<?php

namespace App\Http\Controllers;

use App\Models\ExceptionRequest;
use App\Models\Grn;
use App\Models\PurchaseOrder;
use App\Support\Roles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Exception Handling — fourth Phase 4 (Governance) module, and the last
 * module built in this application (see project memory:
 * erp-laravel-phase4-governance). Workflow (deck p.24): Exception Filed
 * (with justification) -> SG Review -> Approve/Reject -> Action Applied.
 *
 * No sidebar item existed for this in the inherited prototype — added as
 * a new nav entry, same as Checks & Balances. Filing restricted to Admin
 * Head per the deck's "ROLES INVOLVED: Admin Head, SG" (p.24), even
 * though the header line also says "Admin Head / Requester" — opening
 * filing to literally any role wasn't taken up, since the deck's own
 * explicit role list for this module names only Admin Head.
 *
 * Approver routing is per exception_type, not a single fixed role — this
 * reconciles two different parts of the deck: the summary text (p.24)
 * only names SG, but the reference screenshot (p.44) shows a real
 * "Approver Queue" column varying between SG and CFO per row (budget
 * exceed -> CFO, matching Checks & Balances' "Only CFO can override" for
 * budget), which is the more concrete, followed source here.
 *
 * "Action Applied" is real mechanical follow-through for the two
 * unambiguous types (po_force_close, grn_reversal) — the other three
 * (single_quotation, budget_exceed, bypass_empanelment_check) are
 * logged/decided but don't reach back to mutate another record, since
 * retrofitting that would require deeper changes to already-built
 * controllers (documented limitation, same class as other modules').
 */
class ExceptionController extends Controller
{
    private const TYPES = ['single_quotation', 'budget_exceed', 'po_force_close', 'grn_reversal', 'bypass_empanelment_check'];

    private const APPROVER_FOR_TYPE = [
        'single_quotation' => Roles::SG,
        'budget_exceed' => Roles::CFO,
        'po_force_close' => Roles::SG,
        'grn_reversal' => Roles::SG,
        'bypass_empanelment_check' => Roles::SG,
    ];

    public function index(): View
    {
        $exceptions = ExceptionRequest::with(['requester', 'decidedBy'])->latest()->get();

        return view('pages.exceptions', [
            'exceptions' => $exceptions,
            'canCreate' => request()->user()->role === Roles::ADMIN_HEAD,
        ]);
    }

    public function create(): View
    {
        abort_unless(request()->user()->role === Roles::ADMIN_HEAD, 403, 'Only the Admin Head files exceptions.');

        return view('pages.exceptions-create', [
            'types' => self::TYPES,
            'purchaseOrders' => PurchaseOrder::where('status', 'pending')->with('vendor')->get(),
            'grns' => Grn::where('status', 'approved')->with('purchaseOrder')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->role === Roles::ADMIN_HEAD, 403, 'Only the Admin Head files exceptions.');

        $data = $request->validate([
            'exception_type' => ['required', 'in:'.implode(',', self::TYPES)],
            'reference_type' => ['nullable', 'in:purchase_orders,grns'],
            'reference_id' => ['nullable', 'integer'],
            'justification' => ['required', 'string', 'max:2000'],
        ]);

        $referenceType = $data['reference_type'] ?? null;
        $referenceId = $data['reference_id'] ?? null;

        if ($data['exception_type'] === 'po_force_close') {
            $targetPo = $referenceId ? PurchaseOrder::find($referenceId) : null;
            if (! $targetPo || $targetPo->status !== 'pending') {
                return back()
                    ->withErrors(['reference_id' => 'Please select a valid pending Purchase Order to force close.'])
                    ->withInput();
            }
            $referenceType = 'purchase_orders';
        } elseif ($data['exception_type'] === 'grn_reversal') {
            $targetGrn = $referenceId ? Grn::find($referenceId) : null;
            if (! $targetGrn || $targetGrn->status !== 'approved') {
                return back()
                    ->withErrors(['reference_id' => 'Please select a valid approved GRN to reverse.'])
                    ->withInput();
            }
            $referenceType = 'grns';
        }

        $exception = ExceptionRequest::create([
            'exception_type' => $data['exception_type'],
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'justification' => $data['justification'],
            'requested_by' => $request->user()->id,
            'approver_queue' => self::APPROVER_FOR_TYPE[$data['exception_type']],
            'status' => 'pending',
        ]);

        return redirect()->route('exceptions')
            ->with('status', "Exception filed — awaiting ".Roles::label($exception->approver_queue).' approval.');
    }

    public function show(ExceptionRequest $exception): View
    {
        $exception->load(['requester', 'decidedBy']);

        $canAdminOverride = request()->user()->role === Roles::ADMIN_HEAD && (bool) config('app.allow_admin_approval_override', true);

        return view('pages.exceptions-show', [
            'exception' => $exception,
            'canDecide' => in_array($exception->status, ['pending', 'on_hold'], true) && (request()->user()->role === $exception->approver_queue || $canAdminOverride),
        ]);
    }

    public function hold(Request $request, ExceptionRequest $exception): RedirectResponse
    {
        abort_unless(in_array($exception->status, ['pending', 'on_hold'], true), 422, 'This exception has already been decided.');
        $canAdminOverride = $request->user()->role === Roles::ADMIN_HEAD && (bool) config('app.allow_admin_approval_override', true);
        abort_unless(
            $request->user()->role === $exception->approver_queue || $canAdminOverride,
            403,
            'No pending decision on this exception matches your role.'
        );

        $request->validate(['comments' => ['required', 'string', 'max:1000']]);

        $exception->update([
            'status' => 'on_hold',
            'decided_by' => $request->user()->id,
            'decided_at' => now(),
            'notes' => $request->input('comments'),
        ]);

        return back()->with('status', 'Exception request placed on hold.');
    }

    public function approve(Request $request, ExceptionRequest $exception): RedirectResponse
    {
        abort_unless(in_array($exception->status, ['pending', 'on_hold'], true), 422, 'This exception has already been decided.');
        $canAdminOverride = $request->user()->role === Roles::ADMIN_HEAD && (bool) config('app.allow_admin_approval_override', true);
        abort_unless(
            $request->user()->role === $exception->approver_queue || $canAdminOverride,
            403,
            'No pending decision on this exception matches your role.'
        );

        $exception->update(['status' => 'approved', 'decided_by' => $request->user()->id, 'decided_at' => now()]);

        $actionNote = $this->applyAction($exception);

        return back()->with('status', 'Exception approved.'.($actionNote ? ' '.$actionNote : ''));
    }

    public function reject(Request $request, ExceptionRequest $exception): RedirectResponse
    {
        abort_unless(in_array($exception->status, ['pending', 'on_hold'], true), 422, 'This exception has already been decided.');
        $canAdminOverride = $request->user()->role === Roles::ADMIN_HEAD && (bool) config('app.allow_admin_approval_override', true);
        abort_unless(
            $request->user()->role === $exception->approver_queue || $canAdminOverride,
            403,
            'No pending decision on this exception matches your role.'
        );

        $exception->update(['status' => 'rejected', 'decided_by' => $request->user()->id, 'decided_at' => now()]);

        return back()->with('status', 'Exception rejected.');
    }

    /** @return string|null a human note describing what was actually done, for the flash message */
    private function applyAction(ExceptionRequest $exception): ?string
    {
        if ($exception->exception_type === 'po_force_close' && $exception->reference_type === 'purchase_orders' && $exception->reference_id) {
            $po = PurchaseOrder::find($exception->reference_id);
            if ($po) {
                $po->approvals()->where('status', 'pending')->update([
                    'status' => 'approved',
                    'comments' => "Auto-approved via Exception #{$exception->id} (PO Force Close).",
                ]);
                $po->update(['status' => 'approved']);

                return "{$po->po_number} force-closed and issued.";
            }
        }

        if ($exception->exception_type === 'grn_reversal' && $exception->reference_type === 'grns' && $exception->reference_id) {
            $grn = Grn::find($exception->reference_id);
            if ($grn) {
                $grn->update(['status' => 'rejected']);

                return "{$grn->grn_number} reversed — its quantity no longer counts as received.";
            }
        }

        return null;
    }
}
