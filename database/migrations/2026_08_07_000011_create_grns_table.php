<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grns', function (Blueprint $table) {
            $table->id();
            $table->string('grn_number')->unique(); // e.g. GRN-0142
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->string('item_description')->nullable();
            $table->unsignedInteger('po_qty');
            $table->unsignedInteger('received_qty');
            $table->string('invoice_number')->nullable();
            $table->date('receipt_date')->nullable();
            $table->text('quality_remarks')->nullable();
            $table->string('status')->default('pending_hod'); // pending_hod | approved
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grns');
    }
};
