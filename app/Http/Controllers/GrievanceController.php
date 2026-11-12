<?php

namespace App\Http\Controllers;

use App\Mail\GrievanceStatusUpdatedMail;
use App\Models\Attachment;
use App\Models\Grievance;
use App\Models\GrievanceUpdate;
use App\Models\Reimbursement;
use App\Models\User;
use App\Support\Roles;
use App\Support\StorageHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Grievance Redressal — open to every authenticated role (Roles::MODULES_FOR_ALL)
 * so any employee can raise a ticket (including reimbursement & advance settlement
 * disputes), track step-by-step owner responses/escalations/closure, and receive
 * in-app + email notifications at each step, with per-user ticket privacy.
 */
class GrievanceController extends Controller
{
    private const GOVERNANCE_ROLES = [Roles::HOD, Roles::ADMIN_HEAD, Roles::CFO, Roles::SG];

    public const CATEGORY_SETTLEMENT = 'Reimbursement & Advance Settlement';

    private const CATEGORIES = [
        self::CATEGORY_SETTLEMENT,
        'Technical Issue',
        'Operational Delay',
        'Budget Discrepancy',
        'Vendor Dispute',
    ];

    public function index(Request $request): View
    {
        $user = $request->user();
        $isGovernance = $this->isGovernanceHandler($user->role);
        $activeCategory = $request->string('category')->toString();

        $baseQuery = Grievance::query()
            ->when(! $isGovernance, fn ($q) => $q->where('raised_by', $user->id));

        $allScopedCount = (clone $baseQuery)->count();
        $query = (clone $baseQuery)
            ->with(['raisedBy', 'resolvedBy', 'reimbursement', 'updates.actor'])
            ->latest();

        if ($activeCategory && in_array($activeCategory, self::CATEGORIES, true)) {
            $query->where('subject_category', $activeCategory);
        } else {
            $activeCategory = '';
        }

        $grievances = $query->get();
        $grievances->each(function (Grievance $g) use ($user) {
            $unread = (int) $g->raised_by === (int) $user->id
                ? $g->updates->whereNull('read_at')->count()
                : 0;
            $g->setAttribute('unread_updates_count', $unread);
            $g->setAttribute('latest_update', $g->updates->last());
        });

        return view('pages.grievance', [
            'grievances' => $grievances,
            'allGrievancesCount' => $allScopedCount,
            'categories' => self::CATEGORIES,
            'activeCategory' => $activeCategory,
            'isGovernanceView' => $isGovernance,
        ]);
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        $isGovernance = $this->isGovernanceHandler($user->role);
        $requestedClaimId = $request->input('reimbursement_id');

        $claims = Reimbursement::with(['employee', 'trf'])
            ->where(function ($q) use ($user, $isGovernance, $requestedClaimId) {
                $q->where('employee_id', $user->id);
                if ($isGovernance && $requestedClaimId) {
                    $q->orWhere('id', $requestedClaimId);
                }
            })
            ->latest()
            ->get();

        $selectedClaimId = old('reimbursement_id', $requestedClaimId);
        $selectedCategory = old('subject_category', $request->input('category'));
        if (! $selectedCategory && $selectedClaimId) {
            $selectedCategory = self::CATEGORY_SETTLEMENT;
        }

        $claimSummaries = $claims->mapWithKeys(fn (Reimbursement $claim) => [
            (string) $claim->id => [
                'id' => $claim->id,
                'claim_number' => $claim->claim_number,
                'employee' => $claim->employee?->name ?? 'Claimant',
                'trf_number' => $claim->trf?->trf_number ?? 'Off-TRF Claim',
                'claim_amount' => (float) $claim->claim_amount,
                'advance_amount' => (float) $claim->advance_amount,
                'balance_amount' => (float) $claim->balance_amount,
                'status' => ucfirst(str_replace('_', ' ', $claim->status)),
                'url' => route('reimb.show', $claim),
            ],
        ]);

        return view('pages.grievance-create', [
            'categories' => self::CATEGORIES,
            'claims' => $claims,
            'claimSummaries' => $claimSummaries,
            'selectedClaimId' => $selectedClaimId ? (string) $selectedClaimId : '',
            'selectedCategory' => in_array($selectedCategory, self::CATEGORIES, true) ? $selectedCategory : '',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subject_category' => ['required', 'in:'.implode(',', self::CATEGORIES)],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'reimbursement_id' => ['nullable', 'integer', 'exists:reimbursements,id'],
            'proof' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $user = $request->user();
        $reimbursement = ! empty($data['reimbursement_id']) ? Reimbursement::find($data['reimbursement_id']) : null;

        if ($reimbursement && ! $this->isGovernanceHandler($user->role)) {
            abort_unless(
                (int) $reimbursement->employee_id === (int) $user->id,
                403,
                'You can only link grievances to your own reimbursement or advance claims.'
            );
        }

        $grievance = Grievance::create([
            'ticket_number' => $this->nextTicketNumber(),
            'subject_category' => $data['subject_category'],
            'subject' => $data['subject'],
            'description' => $data['description'],
            'reimbursement_id' => $reimbursement?->id,
            'raised_by' => $user->id,
            'current_owner_role' => Roles::HOD,
            'status' => 'under_review',
        ]);

        if ($request->hasFile('proof')) {
            $file = $request->file('proof');
            $stored = StorageHelper::storeUploadedFile($file, 'attachments/grievances');
            $grievance->attachments()->create([
                'file_path' => $stored['path'],
                'disk' => $stored['disk'],
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
                'uploaded_by' => $user->id,
            ]);
        }

        $this->recordStepUpdate(
            grievance: $grievance,
            actorId: $user->id,
            action: 'filed',
            message: 'Grievance raised and assigned to '.Roles::label(Roles::HOD).' for initial review.',
            markReadForRaiser: true,
            notifyAssignedOwner: true,
        );

        $suffix = $reimbursement ? " (linked to {$reimbursement->claim_number})" : '';

        return redirect()->route('grievance')
            ->with('status', "{$grievance->ticket_number} raised{$suffix} — routed to HOD.");
    }

    public function show(Grievance $grievance): View
    {
        $user = request()->user();
        abort_unless(
            $this->canViewGrievance($user, $grievance),
            403,
            'You can only view grievance tickets that you raised unless you are a grievance handler.'
        );

        if ((int) $user->id === (int) $grievance->raised_by) {
            $grievance->updates()->whereNull('read_at')->update(['read_at' => now()]);
        }

        $grievance->load([
            'raisedBy',
            'resolvedBy',
            'reimbursement.trf',
            'reimbursement.employee',
            'attachments.uploader',
            'updates.actor',
        ]);

        $isAssignedOrAdmin = $user->role === $grievance->current_owner_role || $user->role === Roles::ADMIN_HEAD;

        return view('pages.grievance-show', [
            'grievance' => $grievance,
            'canRespond' => $grievance->status === 'under_review' && $isAssignedOrAdmin,
            'canEscalate' => $grievance->status === 'under_review' && $isAssignedOrAdmin && $grievance->nextOwnerRole() !== null,
            'canResolve' => $grievance->status === 'under_review' && $this->isGovernanceHandler($user->role),
        ]);
    }

    public function showAttachment(Grievance $grievance, Attachment $attachment)
    {
        abort_unless(
            $this->canViewGrievance(request()->user(), $grievance),
            403,
            'You are not authorized to access attachments on this grievance.'
        );

        abort_unless(
            $attachment->attachable_type === Grievance::class && (int) $attachment->attachable_id === (int) $grievance->id,
            404
        );

        $disk = $attachment->disk ?: 'public';
        if ($disk === 's3') {
            return redirect(StorageHelper::url($attachment->file_path, 's3'));
        }

        abort_unless(Storage::disk('public')->exists($attachment->file_path), 404, 'Attachment not found.');

        return response()->file(Storage::disk('public')->path($attachment->file_path));
    }

    public function respond(Request $request, Grievance $grievance): RedirectResponse
    {
        abort_unless($grievance->status === 'under_review', 422, 'Resolved tickets cannot accept new responses.');
        abort_unless(
            $request->user()->role === $grievance->current_owner_role || $request->user()->role === Roles::ADMIN_HEAD,
            403,
            'Only the currently assigned owner or Admin Head may respond to this grievance.'
        );

        $data = $request->validate([
            'response_message' => ['required', 'string', 'max:2000'],
        ]);

        $this->recordStepUpdate(
            grievance: $grievance,
            actorId: $request->user()->id,
            action: 'responded',
            message: $data['response_message'],
            markReadForRaiser: false,
            notifyAssignedOwner: false,
        );

        $grievance->writeAuditEntry('responded', "{$grievance->ticket_number}: ".$data['response_message']);

        return back()->with('status', "Response posted on {$grievance->ticket_number} and the ticket raiser has been notified.");
    }

    public function escalate(Request $request, Grievance $grievance): RedirectResponse
    {
        abort_unless($grievance->status === 'under_review', 422, 'This ticket is already resolved.');
        abort_unless(
            $request->user()->role === $grievance->current_owner_role || $request->user()->role === Roles::ADMIN_HEAD,
            403,
            'Only the current owner or Admin Head can escalate this ticket.'
        );

        $data = $request->validate([
            'escalation_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $next = $grievance->nextOwnerRole();
        abort_unless($next, 422, 'This ticket is already at the final escalation level (SG) — resolve it instead.');

        $fromLabel = Roles::label($grievance->current_owner_role);
        $grievance->update(['current_owner_role' => $next]);
        $nextLabel = Roles::label($next);

        $note = trim((string) ($data['escalation_notes'] ?? ''));
        $message = $note !== ''
            ? "Escalated from {$fromLabel} to {$nextLabel}. Note: {$note}"
            : "Escalated from {$fromLabel} to {$nextLabel} for further review.";

        $this->recordStepUpdate(
            grievance: $grievance->fresh(),
            actorId: $request->user()->id,
            action: 'escalated',
            message: $message,
            markReadForRaiser: false,
            notifyAssignedOwner: true,
        );

        return back()->with('status', "{$grievance->ticket_number} escalated to {$nextLabel}.");
    }

    public function resolve(Request $request, Grievance $grievance): RedirectResponse
    {
        abort_unless($grievance->status === 'under_review', 422, 'This ticket is already resolved.');
        abort_unless($this->isGovernanceHandler($request->user()->role), 403, 'Only HOD, Admin Head, CFO or SG may resolve grievances.');

        $data = $request->validate([
            'resolution_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $notes = trim((string) ($data['resolution_notes'] ?? ''));

        $grievance->update([
            'status' => 'resolved',
            'resolved_by' => $request->user()->id,
            'resolved_at' => now(),
            'resolution_notes' => $notes !== '' ? $notes : null,
        ]);

        $this->recordStepUpdate(
            grievance: $grievance->fresh(),
            actorId: $request->user()->id,
            action: 'resolved',
            message: $notes !== '' ? $notes : 'Ticket marked resolved and closed by '.Roles::label($request->user()->role).'.',
            markReadForRaiser: false,
            notifyAssignedOwner: false,
        );

        return back()->with('status', "{$grievance->ticket_number} marked resolved.");
    }

    private function recordStepUpdate(
        Grievance $grievance,
        ?int $actorId,
        string $action,
        string $message,
        bool $markReadForRaiser,
        bool $notifyAssignedOwner,
    ): GrievanceUpdate {
        $update = $grievance->updates()->create([
            'user_id' => $actorId,
            'action' => $action,
            'owner_role' => $grievance->current_owner_role,
            'status' => $grievance->status,
            'message' => $message,
            'read_at' => $markReadForRaiser ? now() : null,
        ]);

        $grievance->loadMissing(['raisedBy', 'resolvedBy', 'reimbursement']);

        if ($grievance->raisedBy?->email) {
            try {
                Mail::to($grievance->raisedBy->email)
                    ->send(new GrievanceStatusUpdatedMail($grievance, $update, 'raiser'));
            } catch (\Throwable $e) {
                Log::error("Failed sending grievance update email for {$grievance->ticket_number}: ".$e->getMessage());
            }
        }

        if ($notifyAssignedOwner) {
            User::where('role', $grievance->current_owner_role)
                ->whereNotNull('email')
                ->where('id', '!=', (int) $grievance->raised_by)
                ->get()
                ->each(function (User $owner) use ($grievance, $update) {
                    try {
                        Mail::to($owner->email)
                            ->send(new GrievanceStatusUpdatedMail($grievance, $update, 'handler'));
                    } catch (\Throwable $e) {
                        Log::error("Failed sending handler email for {$grievance->ticket_number} to {$owner->email}: ".$e->getMessage());
                    }
                });
        }

        return $update;
    }

    private function isGovernanceHandler(?string $role): bool
    {
        return in_array($role, self::GOVERNANCE_ROLES, true);
    }

    private function canViewGrievance($user, Grievance $grievance): bool
    {
        return $user && ((int) $grievance->raised_by === (int) $user->id || $this->isGovernanceHandler($user->role));
    }

    private function nextTicketNumber(): string
    {
        do {
            $number = 'GRV-'.random_int(1000, 9999);
        } while (Grievance::where('ticket_number', $number)->exists());

        return $number;
    }
}
