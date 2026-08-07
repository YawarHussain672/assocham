<?php

namespace App\Http\Controllers;

use App\Models\Trf;
use App\Models\VendorEvaluation;
use App\Support\Roles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Travel Vendor Evaluation — fourth and final Phase 3 (Travel) module, and
 * the last module in the entire application (see project memory:
 * erp-laravel-phase3-travel). Workflow (deck p.19): Trip Completed (TRF
 * closed) -> Rate Vendor (5-parameter scoring) -> Store History (per
 * vendor, per TRF) -> Inform Selection (future bookings). No approval
 * chain — a direct rating action by Admin Head, same shape as Flight &
 * Boarding.
 *
 * "Trip Completed / TRF closed" has no dedicated status in this schema
 * (Trf statuses are pending_asg/pending_sg/approved/pushed_back) — read as
 * "approved AND travel_end has passed", the closest literal match.
 *
 * "Rate Vendor" needs a vendor to rate; the natural link is Flight &
 * Boarding's flight_logs.vendor_id (the vendor actually booked for that
 * trip) — so eligible (TRF, vendor) pairs are exactly the vendors logged
 * against a completed TRF's flight_logs, not yet rated for that TRF. This
 * chains directly off the Flight & Boarding module built earlier.
 */
class VendorEvaluationController extends Controller
{
    public function index(): View
    {
        $evaluations = VendorEvaluation::with(['trf.employee', 'vendor', 'rater'])->latest()->get();

        $leaderboard = $evaluations->groupBy('vendor_id')
            ->map(function ($group) {
                return [
                    'vendor' => $group->first()->vendor,
                    'count' => $group->count(),
                    'average' => round($group->avg(fn (VendorEvaluation $e) => $e->averageRating()), 1),
                ];
            })
            ->sortByDesc('average')
            ->values();

        return view('pages.traveleval', [
            'evaluations' => $evaluations,
            'leaderboard' => $leaderboard,
            'canCreate' => request()->user()->role === Roles::ADMIN_HEAD,
        ]);
    }

    public function create(): View
    {
        abort_unless(request()->user()->role === Roles::ADMIN_HEAD, 403, 'Only the Admin Head rates travel vendors.');

        $rows = Trf::where('status', 'approved')
            ->where('travel_end', '<', now())
            ->with(['employee', 'flightLogs.vendor', 'evaluations'])
            ->get()
            ->map(function (Trf $trf) {
                $ratedVendorIds = $trf->evaluations->pluck('vendor_id');

                return [
                    'trf' => $trf,
                    'vendors' => $trf->flightLogs->pluck('vendor')->filter()->unique('id')
                        ->reject(fn ($v) => $ratedVendorIds->contains($v->id))->values(),
                ];
            })
            ->filter(fn ($row) => $row['vendors']->isNotEmpty())
            ->values();

        return view('pages.traveleval-create', ['rows' => $rows]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->role === Roles::ADMIN_HEAD, 403, 'Only the Admin Head rates travel vendors.');

        $data = $request->validate([
            'trf_id' => ['required', 'exists:trfs,id'],
            'vendor_id' => ['required', 'exists:vendors,id'],
            'cost_score' => ['required', 'integer', 'min:1', 'max:5'],
            'time_score' => ['required', 'integer', 'min:1', 'max:5'],
            'quality_score' => ['required', 'integer', 'min:1', 'max:5'],
            'responsiveness_score' => ['required', 'integer', 'min:1', 'max:5'],
            'accuracy_score' => ['required', 'integer', 'min:1', 'max:5'],
            'comments' => ['nullable', 'string', 'max:500'],
        ]);

        $trf = Trf::with('flightLogs')->findOrFail($data['trf_id']);
        abort_unless(
            $trf->status === 'approved' && $trf->travel_end && $trf->travel_end->lt(now()),
            422,
            'Only completed trips (approved, travel ended) can be rated.'
        );
        abort_unless(
            $trf->flightLogs->pluck('vendor_id')->contains((int) $data['vendor_id']),
            422,
            'This vendor has no logged booking against this TRF.'
        );
        abort_if(
            VendorEvaluation::where('trf_id', $trf->id)->where('vendor_id', $data['vendor_id'])->exists(),
            422,
            'This vendor has already been rated for this TRF.'
        );

        $evaluation = VendorEvaluation::create([
            'trf_id' => $trf->id,
            'vendor_id' => $data['vendor_id'],
            'cost_score' => $data['cost_score'],
            'time_score' => $data['time_score'],
            'quality_score' => $data['quality_score'],
            'responsiveness_score' => $data['responsiveness_score'],
            'accuracy_score' => $data['accuracy_score'],
            'comments' => $data['comments'] ?? null,
            'rated_by' => $request->user()->id,
        ]);

        return redirect()->route('traveleval')
            ->with('status', "Rated {$evaluation->vendor->company_name} for {$trf->trf_number} — average {$evaluation->averageRating()}/5.");
    }

    public function show(VendorEvaluation $evaluation): View
    {
        $evaluation->load(['trf.employee', 'vendor', 'rater']);

        return view('pages.traveleval-show', ['evaluation' => $evaluation]);
    }
}
