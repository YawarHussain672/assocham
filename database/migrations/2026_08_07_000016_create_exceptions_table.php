<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Table is "exceptions"; the Eloquent model is named ExceptionRequest
        // to avoid colliding with PHP's built-in Exception class.
        Schema::create('exceptions', function (Blueprint $table) {
            $table->id();
            $table->string('exception_type'); // single_quotation | budget_exceed | po_force_close | grn_reversal | bypass_empanelment_check
            $table->string('reference_type')->nullable(); // e.g. "purchase_requisitions"
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('justification');
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->string('approver_queue')->default('sg'); // sg | cfo — who this exception is routed to
            $table->string('status')->default('pending'); // pending | approved | rejected
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exceptions');
    }
};
