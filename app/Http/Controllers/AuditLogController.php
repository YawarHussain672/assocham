<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\View\View;

/**
 * Audit Trail — second Phase 4 (Governance) module (see project memory:
 * erp-laravel-phase4-governance). Unlike every other module, there's
 * nothing to build on the write side: every model using the
 * LogsAuditTrail trait (Vendor, PurchaseRequisition, Rfq,
 * CostComparativeStatement, PurchaseOrder, Grn, Trf, FlightLog,
 * Reimbursement, VendorEvaluation, ExceptionRequest, Grievance) has been
 * writing to `audit_logs` automatically since Phase 0. This controller is
 * purely a read-only view over data that already exists — "System" is the
 * creator per the deck (p.26), and "All Roles (view)" is already how
 * `audit` sits in Roles::MODULES_FOR_ALL.
 */
class AuditLogController extends Controller
{
    public function index(): View
    {
        $logs = AuditLog::with('user')->latest('created_at')->limit(200)->get();

        return view('pages.audit', ['logs' => $logs]);
    }
}
