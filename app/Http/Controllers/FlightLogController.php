<?php

namespace App\Http\Controllers;

use App\Mail\FlightBookingConfirmedMail;
use App\Models\BookingMailTransaction;
use App\Models\FlightLog;
use App\Models\Trf;
use App\Models\User;
use App\Models\Vendor;
use App\Support\Roles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/**
 * Flight & Boarding Logistics — second Phase 3 module (see project
 * memory: erp-laravel-phase3-travel). Workflow (deck p.17): Approved TRF
 * (prerequisite) -> Upload Proof -> Map Cost (against TRF no.) -> Ready to
 * Settle (feeds Reimbursement). No approval chain — this module is a
 * logging step, not a decision step.
 *
 * "CREATED / INITIATED BY: Travel Admin Rep." isn't one of the 10 named
 * logins; modeled as Roles::ADMIN_HEAD (same interpretive choice already
 * made in Phase 1's module-access map, which gave Admin Head `flight`
 * access before this controller existed).
 *
 * First module with a real file upload — 'proof' stores to the `public`
 * disk (storage/app/public, symlinked to public/storage). Everywhere else
 * in the app, the `attachments` table has existed since Phase 0 but
 * nothing has written to it; this bypasses that table and writes directly
 * to flight_logs.file_path since the schema already models it that way
 * (one proof per flight log, not a polymorphic collection).
 */
class FlightLogController extends Controller
{
    public function index(): View
    {
        $logs = FlightLog::with(['trf.employee', 'trf.reimbursements', 'vendor', 'mailTransactions'])->latest()->get();

        return view('pages.flight', [
            'logs' => $logs,
            'canCreate' => request()->user()->role === Roles::ADMIN_HEAD,
        ]);
    }

    public function create(): View
    {
        abort_unless(request()->user()->role === Roles::ADMIN_HEAD, 403, 'Only the Travel Admin Representative logs flight & boarding costs.');

        $vendors = Vendor::empanelled()
            ->with('evaluations')
            ->get()
            ->map(function (Vendor $vendor) {
                $evals = $vendor->evaluations;
                $evalCount = $evals->count();
                $effectiveRating = $evalCount > 0
                    ? round($evals->avg(fn ($e) => $e->averageRating()), 1)
                    : ($vendor->rating !== null ? round((float) $vendor->rating, 1) : null);

                $tier = match (true) {
                    $effectiveRating === null => ['label' => 'Unrated', 'key' => 'unrated'],
                    $effectiveRating >= 4.5 => ['label' => 'Preferred', 'key' => 'preferred'],
                    $effectiveRating >= 3.5 => ['label' => 'Satisfactory', 'key' => 'satisfactory'],
                    $effectiveRating >= 3.0 => ['label' => 'Caution', 'key' => 'caution'],
                    default => ['label' => 'Low Performing', 'key' => 'low'],
                };

                $criteria = $evalCount > 0 ? [
                    'Cost' => round($evals->avg('cost_score'), 1),
                    'Timeliness' => round($evals->avg('time_score'), 1),
                    'Service Quality' => round($evals->avg('quality_score'), 1),
                    'Responsiveness' => round($evals->avg('responsiveness_score'), 1),
                ] : [];

                $weakest = [];
                if ($criteria && $effectiveRating !== null && $effectiveRating < 3.5) {
                    $minScore = min($criteria);
                    $weakest = array_keys(array_filter($criteria, fn ($score) => $score <= min(3.0, $minScore + 0.2)));
                }

                $vendor->setAttribute('effective_rating', $effectiveRating);
                $vendor->setAttribute('evaluation_count', $evalCount);
                $vendor->setAttribute('performance_tier', $tier['label']);
                $vendor->setAttribute('performance_tier_key', $tier['key']);
                $vendor->setAttribute('criteria_summary', $criteria);
                $vendor->setAttribute('weak_areas', $weakest);

                return $vendor;
            })
            ->sort(function (Vendor $a, Vendor $b) {
                $groupA = $a->evaluation_count > 0 ? 2 : ($a->effective_rating !== null ? 1 : 0);
                $groupB = $b->evaluation_count > 0 ? 2 : ($b->effective_rating !== null ? 1 : 0);
                if ($groupA !== $groupB) {
                    return $groupB <=> $groupA;
                }

                $ratingA = $a->effective_rating ?? -1;
                $ratingB = $b->effective_rating ?? -1;
                if ($ratingA !== $ratingB) {
                    return $ratingB <=> $ratingA;
                }
                if ($a->evaluation_count !== $b->evaluation_count) {
                    return $b->evaluation_count <=> $a->evaluation_count;
                }

                return strcmp($a->company_name, $b->company_name);
            })
            ->values();

        $vendorInsights = $vendors->mapWithKeys(fn (Vendor $vendor) => [
            (string) $vendor->id => [
                'id' => $vendor->id,
                'company_name' => $vendor->company_name,
                'vendor_code' => $vendor->vendor_code,
                'rating' => $vendor->effective_rating,
                'trips' => $vendor->evaluation_count,
                'tier' => $vendor->performance_tier,
                'tier_key' => $vendor->performance_tier_key,
                'cost' => $vendor->criteria_summary['Cost'] ?? null,
                'time' => $vendor->criteria_summary['Timeliness'] ?? null,
                'quality' => $vendor->criteria_summary['Service Quality'] ?? null,
                'responsiveness' => $vendor->criteria_summary['Responsiveness'] ?? null,
                'weak_areas' => $vendor->weak_areas,
                'history_url' => route('traveleval.vendor-history', $vendor),
            ],
        ]);

        $trfs = Trf::where('status', 'approved')->with('employee')->get();
        $trfSummaries = $trfs->mapWithKeys(fn (Trf $trf) => [
            (string) $trf->id => [
                'trf_number' => $trf->trf_number,
                'employee' => $trf->employee?->name,
                'route' => $trf->route ?: 'Route not specified',
                'mode' => $trf->travel_mode ?: 'Travel',
                'scope' => $trf->is_international ? 'International' : 'Domestic',
                'dates' => $trf->travel_start && $trf->travel_end
                    ? $trf->travel_start->format('d M') . ' – ' . $trf->travel_end->format('d M Y')
                    : null,
                'purpose' => $trf->purpose,
                'url' => route('trf.show', $trf),
            ],
        ]);

        return view('pages.flight-create', [
            'trfs' => $trfs,
            'trfSummaries' => $trfSummaries,
            'vendors' => $vendors,
            'vendorInsights' => $vendorInsights,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->role === Roles::ADMIN_HEAD, 403, 'Only the Travel Admin Representative logs flight & boarding costs.');

        $data = $request->validate([
            'trf_id' => ['required', 'exists:trfs,id'],
            'type' => ['required', 'string', 'max:50'],
            'vendor_id' => ['nullable', 'exists:vendors,id'],
            'cost' => ['required', 'numeric', 'min:0'],
            'proof' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $trf = Trf::findOrFail($data['trf_id']);
        abort_unless($trf->status === 'approved', 422, 'Costs can only be mapped against an approved TRF.');

        if (! empty($data['vendor_id'])) {
            $selectedVendor = Vendor::findOrFail($data['vendor_id']);
            abort_if($selectedVendor->isBlacklisted(), 422, 'Bookings cannot be logged against a blacklisted vendor.');
        }

        $path = null;
        if ($request->hasFile('proof')) {
            $stored = \App\Support\StorageHelper::storeUploadedFile($request->file('proof'), 'flight-proofs');
            $path = $stored['path'];
        }

        $log = FlightLog::create([
            'ref' => $this->nextRef(),
            'trf_id' => $trf->id,
            'type' => $data['type'],
            'vendor_id' => $data['vendor_id'] ?? null,
            'cost' => $data['cost'],
            'file_path' => $path,
            'uploaded_by' => $request->user()->id,
        ]);

        $mailTx = $this->dispatchConfirmationEmail($log, $request->user());
        $mailNote = $mailTx && $mailTx->status === 'sent'
            ? " Confirmation email sent to {$mailTx->recipient_email} and archived on {$trf->trf_number}."
            : ($mailTx ? " Confirmation email delivery logged as failed ({$mailTx->recipient_email})." : '');

        return redirect()->route('flight')
            ->with('status', "{$log->ref} logged against {$trf->trf_number} — ready for reimbursement matching.{$mailNote}");
    }

    public function show(FlightLog $flightLog): View
    {
        $flightLog->load(['trf.employee', 'trf.reimbursements', 'trf.flightLogs', 'trf.evaluations', 'vendor', 'uploader', 'mailTransactions.sender']);

        return view('pages.flight-show', ['log' => $flightLog]);
    }

    public function resendEmail(Request $request, FlightLog $flightLog): RedirectResponse
    {
        abort_unless($request->user()->role === Roles::ADMIN_HEAD, 403, 'Only the Travel Admin Representative can resend booking confirmation emails.');

        $mailTx = $this->dispatchConfirmationEmail($flightLog, $request->user(), true);
        if ($mailTx && $mailTx->status === 'sent') {
            return back()->with('status', "Booking confirmation email resent to {$mailTx->recipient_email} and stored against {$flightLog->trf->trf_number}.");
        }

        return back()->withErrors([
            'email' => 'Could not deliver booking confirmation email' . ($mailTx?->error_message ? ": {$mailTx->error_message}" : '.'),
        ]);
    }

    private function dispatchConfirmationEmail(FlightLog $log, ?User $actor, bool $isResend = false): ?BookingMailTransaction
    {
        $log->loadMissing(['trf.employee', 'vendor', 'uploader']);
        $trf = $log->trf;
        $traveller = $trf?->employee;
        if (! $trf || ! $traveller || empty($traveller->email)) {
            return null;
        }

        $subject = "Travel Booking Confirmation ({$log->ref}) — {$log->type} for {$trf->trf_number}";
        $bodyHtml = view('emails.flight-booking-confirmed', ['flightLog' => $log])->render();
        $status = 'sent';
        $errorMessage = null;

        try {
            Mail::to($traveller->email)->send(new FlightBookingConfirmedMail($log));
        } catch (\Throwable $e) {
            $status = 'failed';
            $errorMessage = $e->getMessage();
            Log::error("Failed to send booking confirmation mail for {$log->ref} to {$traveller->email}: " . $e->getMessage());
        }

        $tx = BookingMailTransaction::create([
            'trf_id' => $trf->id,
            'flight_log_id' => $log->id,
            'recipient_email' => $traveller->email,
            'recipient_name' => $traveller->name,
            'subject' => $subject,
            'body_html' => $bodyHtml,
            'attachment_path' => $log->file_path,
            'status' => $status,
            'error_message' => $errorMessage,
            'sent_by' => $actor?->id,
            'sent_at' => now(),
        ]);

        $verb = $isResend ? 'resent' : 'sent';
        $action = $status === 'sent' ? 'booking_email_sent' : 'booking_email_failed';
        $detail = $status === 'sent'
            ? "Booking confirmation email for {$log->ref} ({$log->type}) {$verb} to {$traveller->email}."
            : "Booking confirmation email for {$log->ref} failed to send to {$traveller->email}: {$errorMessage}";

        $trf->writeAuditEntry($action, $detail);
        $log->writeAuditEntry($action, $detail);

        return $tx;
    }

    public function proof(FlightLog $flightLog)
    {
        abort_unless($flightLog->file_path, 404, 'No proof document attached to this transaction.');

        if (\App\Support\StorageHelper::hasS3Credentials()) {
            return redirect(\App\Support\StorageHelper::url($flightLog->file_path, 's3'));
        }

        abort_unless(\Illuminate\Support\Facades\Storage::disk('public')->exists($flightLog->file_path), 404, 'Proof file not found.');

        return response()->file(\Illuminate\Support\Facades\Storage::disk('public')->path($flightLog->file_path));
    }

    private function nextRef(): string
    {
        do {
            $ref = 'FLT-'.random_int(1000, 9999);
        } while (FlightLog::where('ref', $ref)->exists());

        return $ref;
    }
}
