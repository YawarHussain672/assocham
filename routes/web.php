<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ChecksBalancesController;
use App\Http\Controllers\CostComparativeStatementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExceptionController;
use App\Http\Controllers\FlightLogController;
use App\Http\Controllers\GrievanceController;
use App\Http\Controllers\GrnController;
use App\Http\Controllers\NotificationsController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\PurchaseRequisitionController;
use App\Http\Controllers\ReimbursementController;
use App\Http\Controllers\RfqController;
use App\Http\Controllers\TrfController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\VendorEvaluationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ASSOCHAM Procurement ERP — routes
|--------------------------------------------------------------------------
| Lifted from the original static HTML mockup, then migrated off .html
| URLs. Every page route is named to match window.APP_ROUTES built in
| layouts/app.blade.php, which js/shell.js's sidebar/topbar resolve
| through instead of hardcoding hrefs — keep names in sync with shell.js's
| NAV_SECTIONS ids when adding pages.
|
| Phase 0 (auth foundation): every module page sits behind the `auth`
| middleware. Unauthenticated visits redirect to /login and bounce back to
| the originally requested page after signing in.
|
| Phase 1 (access control): every module route also carries `module:<id>`,
| which checks the logged-in user's role against App\Support\Roles::
| MODULE_ACCESS and 403s if it isn't allowed — the real, server-side gate
| (the sidebar filtering in shell.js is just the matching UX, not the
| enforcement). The <id> must match both the route name and the nav id in
| shell.js's NAV_SECTIONS.
*/

Route::redirect('/', '/index');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    // Role switcher for testing / demo (Slide 46 Audit Trail)
    Route::post('/switch-role', [LoginController::class, 'switchRole'])->name('switch-role');

    // Every role gets the dashboard — no module gate needed here.
    Route::get('/index', [DashboardController::class, 'index'])->name('dashboard');

    // Procurement
    Route::middleware('module:onboarding')->group(function () {
        Route::get('/onboarding', [VendorController::class, 'create'])->name('onboarding');
        Route::post('/onboarding', [VendorController::class, 'store'])->name('onboarding.store');
    });
    Route::middleware('module:empanelment')->group(function () {
        Route::get('/empanelment', [VendorController::class, 'index'])->name('empanelment');
        Route::get('/empanelment/{vendor}', [VendorController::class, 'show'])->name('empanelment.show');
        Route::post('/empanelment/{vendor}/approve', [VendorController::class, 'approve'])->name('empanelment.approve');
        Route::post('/empanelment/{vendor}/reject', [VendorController::class, 'reject'])->name('empanelment.reject');
    });
    Route::middleware('module:requisition')->group(function () {
        Route::get('/requisition', [PurchaseRequisitionController::class, 'index'])->name('requisition');
        Route::get('/requisition/create', [PurchaseRequisitionController::class, 'create'])->name('requisition.create');
        Route::post('/requisition', [PurchaseRequisitionController::class, 'store'])->name('requisition.store');
        Route::post('/requisition/import-excel', [PurchaseRequisitionController::class, 'importExcel'])->name('requisition.import-excel');
        Route::get('/requisition/{requisition}', [PurchaseRequisitionController::class, 'show'])->name('requisition.show');
        Route::post('/requisition/{requisition}/approve', [PurchaseRequisitionController::class, 'approve'])->name('requisition.approve');
        Route::post('/requisition/{requisition}/reject', [PurchaseRequisitionController::class, 'reject'])->name('requisition.reject');
    });
    Route::middleware('module:rfq')->group(function () {
        Route::get('/rfq', [RfqController::class, 'index'])->name('rfq');
        Route::get('/rfq/create', [RfqController::class, 'create'])->name('rfq.create');
        Route::post('/rfq', [RfqController::class, 'store'])->name('rfq.store');
        Route::get('/rfq/{rfq}', [RfqController::class, 'show'])->name('rfq.show');
        Route::post('/rfq/{rfq}/quotes-received', [RfqController::class, 'markQuotesReceived'])->name('rfq.quotes-received');
    });
    Route::middleware('module:ccs')->group(function () {
        Route::get('/ccs', [CostComparativeStatementController::class, 'index'])->name('ccs');
        Route::get('/ccs/create/{rfq}', [CostComparativeStatementController::class, 'create'])->name('ccs.create');
        Route::post('/ccs/{rfq}', [CostComparativeStatementController::class, 'store'])->name('ccs.store');
        Route::get('/ccs/{ccs}', [CostComparativeStatementController::class, 'show'])->name('ccs.show');
        Route::post('/ccs/{ccs}/approve', [CostComparativeStatementController::class, 'approve'])->name('ccs.approve');
        Route::post('/ccs/{ccs}/reject', [CostComparativeStatementController::class, 'reject'])->name('ccs.reject');
    });
    Route::middleware('module:po')->group(function () {
        Route::get('/po', [PurchaseOrderController::class, 'index'])->name('po');
        Route::get('/po/create', [PurchaseOrderController::class, 'create'])->name('po.create');
        Route::post('/po', [PurchaseOrderController::class, 'store'])->name('po.store');
        Route::get('/po/{po}', [PurchaseOrderController::class, 'show'])->name('po.show');
        Route::get('/po/{po}/edit', [PurchaseOrderController::class, 'edit'])->name('po.edit');
        Route::put('/po/{po}', [PurchaseOrderController::class, 'update'])->name('po.update');
        Route::post('/po/{po}/cancel', [PurchaseOrderController::class, 'cancel'])->name('po.cancel');
        Route::post('/po/{po}/approve', [PurchaseOrderController::class, 'approve'])->name('po.approve');
        Route::post('/po/{po}/reject', [PurchaseOrderController::class, 'reject'])->name('po.reject');
    });
    Route::middleware('module:grn')->group(function () {
        Route::get('/grn', [GrnController::class, 'index'])->name('grn');
        Route::get('/grn/create', [GrnController::class, 'create'])->name('grn.create');
        Route::post('/grn', [GrnController::class, 'store'])->name('grn.store');
        Route::get('/grn/{grn}', [GrnController::class, 'show'])->name('grn.show');
        Route::post('/grn/{grn}/approve', [GrnController::class, 'approve'])->name('grn.approve');
        Route::post('/grn/{grn}/reject', [GrnController::class, 'reject'])->name('grn.reject');
    });

    // Travel & Logistics
    Route::middleware('module:trf')->group(function () {
        Route::get('/trf', [TrfController::class, 'index'])->name('trf');
        Route::get('/trf/create', [TrfController::class, 'create'])->name('trf.create');
        Route::post('/trf', [TrfController::class, 'store'])->name('trf.store');
        Route::post('/trf/import-excel', [TrfController::class, 'importExcel'])->name('trf.import-excel');
        Route::get('/trf/{trf}', [TrfController::class, 'show'])->name('trf.show');
        Route::post('/trf/{trf}/approve', [TrfController::class, 'approve'])->name('trf.approve');
        Route::post('/trf/{trf}/pushback', [TrfController::class, 'pushback'])->name('trf.pushback');
    });
    Route::middleware('module:flight')->group(function () {
        Route::get('/flight', [FlightLogController::class, 'index'])->name('flight');
        Route::get('/flight/create', [FlightLogController::class, 'create'])->name('flight.create');
        Route::post('/flight', [FlightLogController::class, 'store'])->name('flight.store');
        Route::get('/flight/{flightLog}', [FlightLogController::class, 'show'])->name('flight.show');
    });
    Route::middleware('module:reimb')->group(function () {
        Route::get('/reimb', [ReimbursementController::class, 'index'])->name('reimb');
        Route::get('/reimb/create', [ReimbursementController::class, 'create'])->name('reimb.create');
        Route::post('/reimb', [ReimbursementController::class, 'store'])->name('reimb.store');
        Route::get('/reimb/{reimb}', [ReimbursementController::class, 'show'])->name('reimb.show');
        Route::post('/reimb/{reimb}/approve', [ReimbursementController::class, 'approve'])->name('reimb.approve');
        Route::post('/reimb/{reimb}/reject', [ReimbursementController::class, 'reject'])->name('reimb.reject');
    });
    Route::middleware('module:traveleval')->group(function () {
        Route::get('/traveleval', [VendorEvaluationController::class, 'index'])->name('traveleval');
        Route::get('/traveleval/create', [VendorEvaluationController::class, 'create'])->name('traveleval.create');
        Route::post('/traveleval', [VendorEvaluationController::class, 'store'])->name('traveleval.store');
        Route::get('/traveleval/{evaluation}', [VendorEvaluationController::class, 'show'])->name('traveleval.show');
    });

    // Governance & Controls
    Route::get('/checks-balances', [ChecksBalancesController::class, 'index'])->name('checks-balances')->middleware('module:checks-balances');
    Route::middleware('module:exceptions')->group(function () {
        Route::get('/exceptions', [ExceptionController::class, 'index'])->name('exceptions');
        Route::get('/exceptions/create', [ExceptionController::class, 'create'])->name('exceptions.create');
        Route::post('/exceptions', [ExceptionController::class, 'store'])->name('exceptions.store');
        Route::get('/exceptions/{exception}', [ExceptionController::class, 'show'])->name('exceptions.show');
        Route::post('/exceptions/{exception}/approve', [ExceptionController::class, 'approve'])->name('exceptions.approve');
        Route::post('/exceptions/{exception}/reject', [ExceptionController::class, 'reject'])->name('exceptions.reject');
    });
    Route::middleware('module:grievance')->group(function () {
        Route::get('/grievance', [GrievanceController::class, 'index'])->name('grievance');
        Route::get('/grievance/create', [GrievanceController::class, 'create'])->name('grievance.create');
        Route::post('/grievance', [GrievanceController::class, 'store'])->name('grievance.store');
        Route::get('/grievance/{grievance}', [GrievanceController::class, 'show'])->name('grievance.show');
        Route::post('/grievance/{grievance}/escalate', [GrievanceController::class, 'escalate'])->name('grievance.escalate');
        Route::post('/grievance/{grievance}/resolve', [GrievanceController::class, 'resolve'])->name('grievance.resolve');
    });

    // System (open to every authenticated role — see Roles::MODULES_FOR_ALL)
    Route::get('/notifications', [NotificationsController::class, 'index'])->name('notifications')->middleware('module:notifications');
    Route::get('/audit', [AuditLogController::class, 'index'])->name('audit')->middleware('module:audit');
});
