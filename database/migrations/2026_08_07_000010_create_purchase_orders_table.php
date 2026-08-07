<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number')->unique(); // e.g. PO-2026-0175
            $table->foreignId('purchase_requisition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('po_qty')->default(1);
            $table->date('delivery_date')->nullable();
            $table->decimal('amount_incl_gst', 14, 2);

            // draft -> pending_admin_head (non-IT) -> pending_cfo (>=10L) -> pending_sg -> approved / rejected
            // Editing an approved PO resets it back to "pending" (see deck: "edit-after-approval resets to pending").
            $table->string('status')->default('draft');

            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
