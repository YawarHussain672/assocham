<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ChecksBalancesController;
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
use App\Http\Controllers\UserManagementController;
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
        Route::get('/empanelment/{vendor}/edit', [VendorController::class, 'edit'])->name('empanelment.edit');
        Route::put('/empanelment/{vendor}', [VendorController::class, 'update'])->name('empanelment.update');
        Route::delete('/empanelment/{vendor}', [VendorController::class, 'destroy'])->name('empanelment.destroy');
        Route::post('/empanelment/{vendor}/approve', [VendorController::class, 'approve'])->name('empanelment.approve');
        Route::post('/empanelment/{vendor}/reject', [VendorController::class, 'reject'])->name('empanelment.reject');
        Route::post('/empanelment/{vendor}/resend-email', [VendorController::class, 'resendEmpanelmentEmail'])->name('empanelment.resend-email');
        Route::post('/empanelment/{vendor}/blacklist', [VendorController::class, 'blacklist'])->name('empanelment.blacklist');
        Route::post('/empanelment/{vendor}/unblacklist', [VendorController::class, 'unblacklist'])->name('empanelment.unblacklist');
    });
    Route::get('/empanelment/{vendor}', [VendorController::class, 'show'])->name('empanelment.show');
    Route::get('/vendor/{vendor}', [VendorController::class, 'show'])->name('vendor.show');
    Route::middleware('module:requisition')->group(function () {
        Route::get('/requisition', [PurchaseRequisitionController::class, 'index'])->name('requisition');
        Route::get('/requisition/create', [PurchaseRequisitionController::class, 'create'])->name('requisition.create');
        Route::post('/requisition', [PurchaseRequisitionController::class, 'store'])->name('requisition.store');
        Route::get('/requisition/template', [PurchaseRequisitionController::class, 'downloadTemplate'])->name('requisition.template');
        Route::post('/requisition/parse-excel', [PurchaseRequisitionController::class, 'parseExcel'])->name('requisition.parse-excel');
        Route::post('/requisition/import-excel', [PurchaseRequisitionController::class, 'importExcel'])->name('requisition.import-excel');
        Route::get('/requisition/{requisition}/edit', [PurchaseRequisitionController::class, 'edit'])->name('requisition.edit');
        Route::put('/requisition/{requisition}', [PurchaseRequisitionController::class, 'update'])->name('requisition.update');
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
        Route::post('/rfq/{rfq}/save-quotations', [RfqController::class, 'saveQuotations'])->name('rfq.save-quotations');
        Route::post('/rfq/{rfq}/select-vendor', [RfqController::class, 'selectBestVendor'])->name('rfq.select-vendor');
        Route::post('/rfq/{rfq}/resend-award-email', [RfqController::class, 'resendAwardEmail'])->name('rfq.resend-award-email');
        Route::post('/rfq/{rfq}/vendors/{vendor}/resend-email', [RfqController::class, 'resendVendorEmail'])->name('rfq.vendor.resend-email');
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
        Route::post('/po/{po}/regenerate', [PurchaseOrderController::class, 'regenerate'])->name('po.regenerate');
        Route::get('/po/{po}/pdf', [PurchaseOrderController::class, 'downloadPdf'])->name('po.pdf');
        Route::get('/po/{po}/preview-pdf', [PurchaseOrderController::class, 'previewPdf'])->name('po.preview-pdf');
        Route::post('/po/{po}/terms', [PurchaseOrderController::class, 'updateTerms'])->name('po.update-terms');
    });
    Route::middleware('module:grn')->group(function () {
        Route::get('/grn', [GrnController::class, 'index'])->name('grn');
        Route::get('/grn/create', [GrnController::class, 'create'])->name('grn.create');
        Route::post('/grn', [GrnController::class, 'store'])->name('grn.store');
        Route::get('/grn/{grn}', [GrnController::class, 'show'])->name('grn.show');
        Route::get('/grn/{grn}/edit', [GrnController::class, 'edit'])->name('grn.edit');
        Route::put('/grn/{grn}', [GrnController::class, 'update'])->name('grn.update');
        Route::post('/grn/{grn}/approve', [GrnController::class, 'approve'])->name('grn.approve');
        Route::post('/grn/{grn}/reject', [GrnController::class, 'reject'])->name('grn.reject');
    });

    // Travel & Logistics
    Route::middleware('module:trf')->group(function () {
        Route::get('/trf', [TrfController::class, 'index'])->name('trf');
        Route::get('/trf/create', [TrfController::class, 'create'])->name('trf.create');
        Route::post('/trf', [TrfController::class, 'store'])->name('trf.store');
        Route::get('/trf/template', [TrfController::class, 'downloadTemplate'])->name('trf.template');
        Route::post('/trf/import-excel', [TrfController::class, 'importExcel'])->name('trf.import-excel');
        Route::get('/trf/{trf}/edit', [TrfController::class, 'edit'])->name('trf.edit');
        Route::put('/trf/{trf}', [TrfController::class, 'update'])->name('trf.update');
        Route::get('/trf/{trf}', [TrfController::class, 'show'])->name('trf.show');
        Route::post('/trf/{trf}/approve', [TrfController::class, 'approve'])->name('trf.approve');
        Route::post('/trf/{trf}/pushback', [TrfController::class, 'pushback'])->name('trf.pushback');
        Route::post('/trf/{trf}/boarding-passes', [TrfController::class, 'uploadBoardingPasses'])->name('trf.boarding-passes.store');
        Route::post('/trf/{trf}/boarding-passes/share', [TrfController::class, 'shareBoardingPasses'])->name('trf.boarding-passes.share');
        Route::get('/trf/{trf}/boarding-passes/{attachment}', [TrfController::class, 'viewBoardingPass'])->name('trf.boarding-passes.show');
        Route::delete('/trf/{trf}/boarding-passes/{attachment}', [TrfController::class, 'deleteBoardingPass'])->name('trf.boarding-passes.destroy');
    });
    Route::middleware('module:flight')->group(function () {
        Route::get('/flight', [FlightLogController::class, 'index'])->name('flight');
        Route::get('/flight/create', [FlightLogController::class, 'create'])->name('flight.create');
        Route::post('/flight', [FlightLogController::class, 'store'])->name('flight.store');
        Route::get('/flight/{flightLog}/proof', [FlightLogController::class, 'proof'])->name('flight.proof');
        Route::post('/flight/{flightLog}/resend-email', [FlightLogController::class, 'resendEmail'])->name('flight.resend-email');
        Route::get('/flight/{flightLog}', [FlightLogController::class, 'show'])->name('flight.show');
    });
    Route::middleware('module:reimb')->group(function () {
        Route::get('/reimb', [ReimbursementController::class, 'index'])->name('reimb');
        Route::get('/reimb/create', [ReimbursementController::class, 'create'])->name('reimb.create');
        Route::post('/reimb', [ReimbursementController::class, 'store'])->name('reimb.store');
        Route::get('/reimb/{reimb}/attachments/{attachment}', [ReimbursementController::class, 'viewAttachment'])->name('reimb.attachments.show');
        Route::get('/reimb/{reimb}/items/{item}/bill', [ReimbursementController::class, 'viewItemBill'])->name('reimb.items.bill');
        Route::get('/reimb/{reimb}', [ReimbursementController::class, 'show'])->name('reimb.show');
        Route::post('/reimb/{reimb}/approve', [ReimbursementController::class, 'approve'])->name('reimb.approve');
        Route::post('/reimb/{reimb}/reject', [ReimbursementController::class, 'reject'])->name('reimb.reject');
    });
    Route::middleware('module:traveleval')->group(function () {
        Route::get('/traveleval', [VendorEvaluationController::class, 'index'])->name('traveleval');
        Route::get('/traveleval/create', [VendorEvaluationController::class, 'create'])->name('traveleval.create');
        Route::post('/traveleval', [VendorEvaluationController::class, 'store'])->name('traveleval.store');
        Route::get('/traveleval/vendor/{vendor}', [VendorEvaluationController::class, 'vendorHistory'])->name('traveleval.vendor-history');
        Route::get('/traveleval/{evaluation}', [VendorEvaluationController::class, 'show'])->name('traveleval.show');
    });

    // Governance & Controls
    Route::middleware('module:checks-balances')->group(function () {
        Route::get('/checks-balances', [ChecksBalancesController::class, 'index'])->name('checks-balances');
        Route::post('/checks-balances/budgets', [ChecksBalancesController::class, 'storeBudget'])->name('checks-balances.budgets.store');
        Route::put('/checks-balances/budgets/{budget}', [ChecksBalancesController::class, 'updateBudget'])->name('checks-balances.budgets.update');
        Route::delete('/checks-balances/budgets/{budget}', [ChecksBalancesController::class, 'destroyBudget'])->name('checks-balances.budgets.destroy');
    });
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
        Route::get('/grievance/{grievance}/attachments/{attachment}', [GrievanceController::class, 'showAttachment'])->name('grievance.attachments.show');
        Route::get('/grievance/{grievance}', [GrievanceController::class, 'show'])->name('grievance.show');
        Route::post('/grievance/{grievance}/respond', [GrievanceController::class, 'respond'])->name('grievance.respond');
        Route::post('/grievance/{grievance}/escalate', [GrievanceController::class, 'escalate'])->name('grievance.escalate');
        Route::post('/grievance/{grievance}/resolve', [GrievanceController::class, 'resolve'])->name('grievance.resolve');
    });

    // System (open to every authenticated role — see Roles::MODULES_FOR_ALL)
    Route::get('/notifications', [NotificationsController::class, 'index'])->name('notifications')->middleware('module:notifications');
    Route::get('/audit', [AuditLogController::class, 'index'])->name('audit')->middleware('module:audit');

    Route::middleware('module:users')->group(function () {
        Route::get('/users', [UserManagementController::class, 'index'])->name('users');
        Route::get('/users/create', [UserManagementController::class, 'create'])->name('users.create');
        Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserManagementController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');
    });
});
