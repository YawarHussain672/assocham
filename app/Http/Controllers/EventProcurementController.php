<?php

namespace App\Http\Controllers;

use App\Models\DepartmentBudget;
use App\Models\ProjectBudget;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Support\Roles;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use ZipArchive;

class EventProcurementController extends Controller
{
    /**
     * Event-Wise PR & PO Management Index
     */
    public function index(Request $request): View
    {
        $currentFy = DepartmentBudget::currentFiscalYear();
        $requestedFy = trim($request->string('fy')->toString());
        $fiscalYear = preg_match('/^FY\d{2}-\d{2}$/', $requestedFy) ? $requestedFy : $currentFy;

        $fiscalYears = ProjectBudget::query()
            ->distinct()
            ->pluck('fiscal_year')
            ->push($currentFy)
            ->push($fiscalYear)
            ->unique()
            ->sortDesc()
            ->values();

        $events = ProjectBudget::with([
            'purchaseRequisitions.items',
            'purchaseRequisitions.creator',
            'purchaseRequisitions.purchaseOrders.vendor',
            'purchaseRequisitions.purchaseOrders.approvals',
        ])
        ->where('fiscal_year', $fiscalYear)
        ->orderBy('department')
        ->orderBy('project_name')
        ->get();

        // Calculate summary statistics
        $totalEvents = $events->count();
        $totalAllocated = (float) $events->sum('allocated_amount');
        $totalUtilised = (float) $events->sum('utilised_amount');

        $allPrCount = 0;
        $allPoCount = 0;
        $allPoSpend = 0.0;

        foreach ($events as $event) {
            $prs = $this->getEventPrs($event);
            $pos = $this->getEventPos($event);
            $event->setAttribute('event_prs', $prs);
            $event->setAttribute('event_pos', $pos);
            $event->setAttribute('pr_count', $prs->count());
            $event->setAttribute('po_count', $pos->count());
            $event->setAttribute('po_spend', (float) $pos->where('status', 'approved')->sum('amount_incl_gst'));

            $allPrCount += $prs->count();
            $allPoCount += $pos->count();
            $allPoSpend += (float) $pos->where('status', 'approved')->sum('amount_incl_gst');
        }

        $departments = $events->pluck('department')->unique()->sort()->values();

        return view('pages.events-index', [
            'events' => $events,
            'departments' => $departments,
            'fiscalYear' => $fiscalYear,
            'fiscalYears' => $fiscalYears,
            'currentFy' => $currentFy,
            'totalEvents' => $totalEvents,
            'totalAllocated' => $totalAllocated,
            'totalUtilised' => $totalUtilised,
            'allPrCount' => $allPrCount,
            'allPoCount' => $allPoCount,
            'allPoSpend' => $allPoSpend,
            'selectedDepartment' => $request->query('department', 'all'),
        ]);
    }

    /**
     * Show dedicated dossier for a single event / project
     */
    public function show(ProjectBudget $event): View
    {
        $event->loadMissing([
            'purchaseRequisitions.items',
            'purchaseRequisitions.approvals',
            'purchaseRequisitions.creator',
            'purchaseRequisitions.purchaseOrders.vendor',
            'purchaseRequisitions.purchaseOrders.approvals',
        ]);

        $prs = $this->getEventPrs($event);
        $pos = $this->getEventPos($event);

        $totalPrCost = (float) $prs->where('status', '!=', 'rejected')->sum(fn ($pr) => $pr->estimatedCostInclGst());
        $totalPoSpend = (float) $pos->where('status', 'approved')->sum('amount_incl_gst');

        return view('pages.events-show', [
            'event' => $event,
            'prs' => $prs,
            'pos' => $pos,
            'totalPrCost' => $totalPrCost,
            'totalPoSpend' => $totalPoSpend,
        ]);
    }

    /**
     * Download Consolidated PDF Dossier for an event (Summary + All PRs + All POs)
     */
    public function downloadPdf(ProjectBudget $event)
    {
        $prs = $this->getEventPrs($event);
        $pos = $this->getEventPos($event);

        $pdf = Pdf::loadView('pdf.event-procurement-dossier', [
            'event' => $event,
            'prs' => $prs,
            'pos' => $pos,
        ])->setPaper('a4', 'portrait')
          ->setOptions([
              'isRemoteEnabled' => true,
              'dpi' => 120,
              'defaultFont' => 'DejaVu Sans',
          ]);

        $safeName = \Illuminate\Support\Str::slug($event->project_name);
        $filename = "Event-Dossier-{$safeName}-{$event->fiscal_year}.pdf";

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Download ZIP Archive containing all individual PR and PO PDF files for an event
     */
    public function downloadZip(ProjectBudget $event)
    {
        $prs = $this->getEventPrs($event);
        $pos = $this->getEventPos($event);

        $zip = new ZipArchive();
        $safeName = \Illuminate\Support\Str::slug($event->project_name);
        $tempZipPath = storage_path("app/Event-Dossier-{$event->id}-" . time() . ".zip");

        if ($zip->open($tempZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'Could not create ZIP archive on server.');
        }

        // 1. Generate & Add Consolidated Event Summary PDF
        $summaryPdf = Pdf::loadView('pdf.event-procurement-dossier', [
            'event' => $event,
            'prs' => $prs,
            'pos' => $pos,
        ])->setPaper('a4', 'portrait')->output();

        $zip->addFromString("00_Event_Summary_Dossier.pdf", $summaryPdf);

        // 2. Add All Purchase Requisition PDFs
        foreach ($prs as $pr) {
            $prPdf = Pdf::loadView('pdf.purchase-requisition', [
                'pr' => $pr,
            ])->setPaper('a4', 'portrait')->output();

            $safePrNum = str_replace(['/', '\\'], '-', $pr->pr_number);
            $zip->addFromString("PRs/{$safePrNum}.pdf", $prPdf);
        }

        // 3. Add All Purchase Order PDFs
        foreach ($pos as $po) {
            $poPdf = Pdf::loadView('pdf.purchase-order', [
                'po' => $po,
                'items' => $po->purchaseRequisition?->items ?? collect(),
            ])->setPaper('a4', 'portrait')->output();

            $safePoNum = str_replace(['/', '\\'], '-', $po->po_number);
            $zip->addFromString("POs/{$safePoNum}.pdf", $poPdf);
        }

        $zip->close();

        return response()->download($tempZipPath, "Event-Procurement-{$safeName}.zip", [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Download comprehensive Event PR & PO Excel / CSV Sheet
     */
    public function downloadSheet(ProjectBudget $event)
    {
        $prs = $this->getEventPrs($event);
        $pos = $this->getEventPos($event);

        $headers = [
            'Record Type', 'Document Number', 'Event / Project', 'Department',
            'Vendor / Creator', 'Status', 'Date', 'Amount (Incl GST)', 'Remarks / Items'
        ];

        $handle = fopen('php://memory', 'w+');
        // UTF-8 BOM for Excel compatibility
        fputs($handle, "\xEF\xBB\xBF");
        fputcsv($handle, $headers);

        foreach ($prs as $pr) {
            $itemsSummary = $pr->items->map(fn($i) => "{$i->item_name} (x{$i->quantity})")->join('; ');
            fputcsv($handle, [
                'Purchase Requisition (PR)',
                $pr->pr_number,
                $event->project_name,
                $pr->department,
                $pr->creator?->name ?? 'System',
                ucfirst(str_replace('_', ' ', $pr->status)),
                $pr->created_at->format('Y-m-d'),
                number_format($pr->estimatedCostInclGst(), 2, '.', ''),
                $itemsSummary ?: ($pr->title ?: '—'),
            ]);
        }

        foreach ($pos as $po) {
            fputcsv($handle, [
                'Purchase Order (PO)',
                $po->po_number,
                $event->project_name,
                $po->purchaseRequisition?->department ?? $event->department,
                $po->vendor?->company_name ?? '—',
                ucfirst(str_replace('_', ' ', $po->status)),
                $po->created_at->format('Y-m-d'),
                number_format((float) $po->amount_incl_gst, 2, '.', ''),
                "Ref PR: " . ($po->purchaseRequisition?->pr_number ?? '—'),
            ]);
        }

        rewind($handle);
        $csvContent = stream_get_contents($handle);
        fclose($handle);

        $safeName = \Illuminate\Support\Str::slug($event->project_name);
        return response($csvContent, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"Event-Procurement-{$safeName}.csv\"",
        ]);
    }

    /**
     * Remove an event (Project Budget)
     */
    public function destroy(Request $request, ProjectBudget $event): RedirectResponse
    {
        abort_unless(in_array($request->user()?->role, [Roles::ADMIN_HEAD, Roles::SG, Roles::CFO], true), 403, 'Unauthorized to delete events.');

        $prs = $this->getEventPrs($event);
        $pos = $this->getEventPos($event);

        if ((float) $event->utilised_amount > 0 || $prs->isNotEmpty() || $pos->isNotEmpty()) {
            return back()->with('error', "Cannot delete '{$event->project_name}' because requisitions, orders, or budget spend are linked to it.");
        }

        $name = $event->project_name;
        $event->delete();

        return back()->with('status', "Event '{$name}' has been successfully deleted.");
    }

    /**
     * Resolves all PRs matching this project budget by ID or exact project/event name
     */
    private function getEventPrs(ProjectBudget $event)
    {
        return PurchaseRequisition::with(['items', 'approvals', 'creator', 'purchaseOrders.vendor'])
            ->where(function ($q) use ($event) {
                $q->where('project_budget_id', $event->id)
                  ->orWhere('event_name', $event->project_name);
            })
            ->latest()
            ->get();
    }

    /**
     * Resolves all POs linked to PRs of this event or carrying this event_name
     */
    private function getEventPos(ProjectBudget $event)
    {
        $prIds = PurchaseRequisition::where(function ($q) use ($event) {
                $q->where('project_budget_id', $event->id)
                  ->orWhere('event_name', $event->project_name);
            })->pluck('id');

        return PurchaseOrder::with(['vendor', 'purchaseRequisition.items', 'approvals'])
            ->where(function ($q) use ($prIds, $event) {
                $q->whereIn('purchase_requisition_id', $prIds)
                  ->orWhere('event_name', $event->project_name);
            })
            ->latest()
            ->get();
    }
}
