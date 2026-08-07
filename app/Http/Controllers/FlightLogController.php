<?php

namespace App\Http\Controllers;

use App\Models\FlightLog;
use App\Models\Trf;
use App\Models\Vendor;
use App\Support\Roles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $logs = FlightLog::with(['trf.employee', 'vendor'])->latest()->get();

        return view('pages.flight', [
            'logs' => $logs,
            'canCreate' => request()->user()->role === Roles::ADMIN_HEAD,
        ]);
    }

    public function create(): View
    {
        abort_unless(request()->user()->role === Roles::ADMIN_HEAD, 403, 'Only the Travel Admin Representative logs flight & boarding costs.');

        return view('pages.flight-create', [
            'trfs' => Trf::where('status', 'approved')->with('employee')->get(),
            'vendors' => Vendor::empanelled()->orderBy('company_name')->get(),
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

        $path = $request->hasFile('proof')
            ? $request->file('proof')->store('flight-proofs', 'public')
            : null;

        $log = FlightLog::create([
            'ref' => $this->nextRef(),
            'trf_id' => $trf->id,
            'type' => $data['type'],
            'vendor_id' => $data['vendor_id'] ?? null,
            'cost' => $data['cost'],
            'file_path' => $path,
            'uploaded_by' => $request->user()->id,
        ]);

        return redirect()->route('flight')
            ->with('status', "{$log->ref} logged against {$trf->trf_number} — ready for reimbursement matching.");
    }

    public function show(FlightLog $flightLog): View
    {
        $flightLog->load(['trf.employee', 'vendor', 'uploader']);

        return view('pages.flight-show', ['log' => $flightLog]);
    }

    private function nextRef(): string
    {
        do {
            $ref = 'FLT-'.random_int(1000, 9999);
        } while (FlightLog::where('ref', $ref)->exists());

        return $ref;
    }
}
