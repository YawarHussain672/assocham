<?php

namespace App\Http\Controllers;

use App\Models\FlightLog;
use App\Models\Trf;
use App\Models\Vendor;
use App\Models\VendorEvaluation;
use App\Support\Roles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Travel Vendor Evaluation — fourth and final Phase 3 (Travel) module.
 * Workflow: Trip Completed (TRF approved + travel ended) -> Rate Vendor
 * per TRF (Cost, Timeliness, Service Quality, Responsiveness, Accuracy)
 * -> Store History & Update Vendor Rating -> Inform Future Bookings.
 */
class VendorEvaluationController extends Controller
{
    public function index(): View
    {
        $evaluations = VendorEvaluation::with(['trf.employee', 'vendor', 'rater'])->latest()->get();

        $leaderboard = $evaluations->groupBy('vendor_id')
            ->filter(fn ($group) => $group->first()?->vendor !== null)
            ->map(function ($group) {
                $chronological = $group->sortBy(fn (VendorEvaluation $e) => optional($e->trf?->travel_end)->timestamp ?? $e->id)->values();

                return [
                    'vendor' => $group->first()->vendor,
                    'count' => $group->count(),
                    'average' => round($group->avg(fn (VendorEvaluation $e) => $e->averageRating()), 1),
                    'cost_avg' => round($group->avg('cost_score'), 1),
                    'time_avg' => round($group->avg('time_score'), 1),
                    'quality_avg' => round($group->avg('quality_score'), 1),
                    'resp_avg' => round($group->avg('responsiveness_score'), 1),
                    'trend' => $this->computeTrend($chronological),
                ];
            })
            ->sortByDesc('average')
            ->values();

        $canCreate = request()->user()->role === Roles::ADMIN_HEAD;

        return view('pages.traveleval', [
            'evaluations' => $evaluations,
            'leaderboard' => $leaderboard,
            'pendingRows' => $canCreate ? $this->pendingEvaluationRows() : collect(),
            'canCreate' => $canCreate,
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->role === Roles::ADMIN_HEAD, 403, 'Only the Admin Head rates travel vendors.');

        $rows = $this->pendingEvaluationRows();
        $preselectedTrfId = old('trf_id', $request->query('trf_id'));
        $preselectedVendorId = old('vendor_id', $request->query('vendor_id'));

        $preselectedCombo = ($preselectedTrfId && $preselectedVendorId)
            ? "{$preselectedTrfId}:{$preselectedVendorId}"
            : '';

        if (! $preselectedCombo && $preselectedTrfId) {
            $matchedRow = $rows->first(fn ($r) => (string) $r['trf']->id === (string) $preselectedTrfId);
            if ($matchedRow && $matchedRow['vendors']->isNotEmpty()) {
                $firstVendorId = $matchedRow['vendors']->first()->id;
                $preselectedCombo = "{$preselectedTrfId}:{$firstVendorId}";
                $preselectedVendorId = $firstVendorId;
            }
        }

        return view('pages.traveleval-create', [
            'rows' => $rows,
            'preselectedCombo' => $preselectedCombo,
            'preselectedTrfId' => $preselectedTrfId,
            'preselectedVendorId' => $preselectedVendorId,
        ]);
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
            'accuracy_score' => ['nullable', 'integer', 'min:1', 'max:5'],
            'comments' => ['nullable', 'string', 'max:500'],
        ]);

        $trf = Trf::with('flightLogs')->findOrFail($data['trf_id']);
        $endDate = $trf->travel_end ?? $trf->travel_start;

        abort_unless(
            $trf->status === 'approved' && $endDate && $endDate->copy()->startOfDay()->lte(now()->startOfDay()),
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

        $accuracy = $data['accuracy_score'] ?? (int) round((
            $data['cost_score'] + $data['time_score'] + $data['quality_score'] + $data['responsiveness_score']
        ) / 4);

        $evaluation = VendorEvaluation::create([
            'trf_id' => $trf->id,
            'vendor_id' => $data['vendor_id'],
            'cost_score' => $data['cost_score'],
            'time_score' => $data['time_score'],
            'quality_score' => $data['quality_score'],
            'responsiveness_score' => $data['responsiveness_score'],
            'accuracy_score' => $accuracy,
            'comments' => $data['comments'] ?? null,
            'rated_by' => $request->user()->id,
        ]);

        $evaluation->load('vendor');

        // Refresh the vendor's overall rating across all recorded evaluations
        if ($evaluation->vendor) {
            $allForVendor = VendorEvaluation::where('vendor_id', $evaluation->vendor_id)->get();
            $overallAvg = round($allForVendor->avg(fn (VendorEvaluation $e) => $e->averageRating()), 1);
            $evaluation->vendor->update(['rating' => $overallAvg]);
        }

        $trf->writeAuditEntry(
            'vendor_evaluated',
            "Rated {$evaluation->vendor->company_name} ({$evaluation->averageRating()}/5) for completed trip {$trf->trf_number} by {$request->user()->name}."
        );

        return redirect()->route('traveleval')
            ->with('status', "Rated {$evaluation->vendor->company_name} for {$trf->trf_number} — average {$evaluation->averageRating()}/5.");
    }

    public function show(VendorEvaluation $evaluation): View
    {
        $evaluation->load(['trf.employee', 'vendor', 'rater']);

        return view('pages.traveleval-show', ['evaluation' => $evaluation]);
    }

    public function vendorHistory(Vendor $vendor): View
    {
        $chronological = VendorEvaluation::where('vendor_id', $vendor->id)
            ->with(['trf.employee', 'rater'])
            ->get()
            ->sortBy(fn (VendorEvaluation $e) => optional($e->trf?->travel_end)->timestamp ?? optional($e->created_at)->timestamp ?? $e->id)
            ->values();

        $count = $chronological->count();
        $average = $count > 0
            ? round($chronological->avg(fn (VendorEvaluation $e) => $e->averageRating()), 1)
            : 0.0;
        $trend = $this->computeTrend($chronological);

        $bookings = FlightLog::where('vendor_id', $vendor->id)->get();
        $totalSpend = (float) $bookings->sum('cost');

        $criteriaAverages = [
            'cost' => $count > 0 ? round($chronological->avg('cost_score'), 1) : 0.0,
            'time' => $count > 0 ? round($chronological->avg('time_score'), 1) : 0.0,
            'quality' => $count > 0 ? round($chronological->avg('quality_score'), 1) : 0.0,
            'responsiveness' => $count > 0 ? round($chronological->avg('responsiveness_score'), 1) : 0.0,
            'accuracy' => $count > 0 ? round($chronological->avg('accuracy_score'), 1) : 0.0,
        ];

        $chartSeries = $chronological->map(function (VendorEvaluation $e) {
            return [
                'label' => $e->trf?->trf_number ?? ('Eval #' . $e->id),
                'date' => optional($e->trf?->travel_end ?? $e->created_at)->format('d M Y') ?? '—',
                'score' => $e->averageRating(),
                'cost' => $e->cost_score,
                'time' => $e->time_score,
                'quality' => $e->quality_score,
                'responsiveness' => $e->responsiveness_score,
            ];
        })->values();

        return view('pages.traveleval-history', [
            'vendor' => $vendor,
            'evaluations' => $chronological->reverse()->values(),
            'count' => $count,
            'average' => $average,
            'trend' => $trend,
            'totalSpend' => $totalSpend,
            'bookingsCount' => $bookings->count(),
            'criteriaAverages' => $criteriaAverages,
            'chartSeries' => $chartSeries,
        ]);
    }

    /**
     * Compute score trend by comparing the most recent trip evaluation against the prior baseline average.
     *
     * @param  Collection<int, VendorEvaluation>  $chronological
     * @return array{label: string, delta: float, formatted_delta: string, bg: string, fg: string, icon: string}
     */
    private function computeTrend(Collection $chronological): array
    {
        if ($chronological->count() < 2) {
            return [
                'label' => 'Baseline',
                'delta' => 0.0,
                'formatted_delta' => '0.0',
                'bg' => '#EEF0F3',
                'fg' => '#475569',
                'icon' => 'bi-dash-lg',
            ];
        }

        $latest = $chronological->last()->averageRating();
        $prior = $chronological->slice(0, -1);
        $priorAvg = $prior->avg(fn (VendorEvaluation $e) => $e->averageRating());
        $delta = round($latest - $priorAvg, 1);

        if ($delta >= 0.1) {
            return [
                'label' => 'Improving',
                'delta' => $delta,
                'formatted_delta' => '+' . number_format($delta, 1),
                'bg' => '#E6F3EC',
                'fg' => '#2E7D4F',
                'icon' => 'bi-graph-up-arrow',
            ];
        }

        if ($delta <= -0.1) {
            return [
                'label' => 'Declining',
                'delta' => $delta,
                'formatted_delta' => number_format($delta, 1),
                'bg' => '#FBE7E9',
                'fg' => '#B0263A',
                'icon' => 'bi-graph-down-arrow',
            ];
        }

        return [
            'label' => 'Stable',
            'delta' => 0.0,
            'formatted_delta' => '0.0',
            'bg' => '#E6F0FB',
            'fg' => '#1E5FA8',
            'icon' => 'bi-arrow-right',
        ];
    }

    /**
     * Completed approved TRFs that have at least one booked travel vendor not yet rated for that TRF.
     *
     * @return Collection<int, array{trf: Trf, vendors: Collection}>
     */
    private function pendingEvaluationRows(): Collection
    {
        $today = now()->startOfDay();

        return Trf::where('status', 'approved')
            ->with(['employee', 'flightLogs.vendor', 'evaluations'])
            ->latest('travel_end')
            ->get()
            ->filter(function (Trf $trf) use ($today) {
                $endDate = $trf->travel_end ?? $trf->travel_start;

                return $endDate && $endDate->copy()->startOfDay()->lte($today);
            })
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
    }
}
