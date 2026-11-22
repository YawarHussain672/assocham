<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_requisition_items', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_requisition_items', 'purchase_order_id')) {
                $table->foreignId('purchase_order_id')
                    ->nullable()
                    ->after('purchase_requisition_id')
                    ->constrained('purchase_orders')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('purchase_requisition_items', 'vendor_id')) {
                $table->foreignId('vendor_id')
                    ->nullable()
                    ->after('purchase_order_id')
                    ->constrained('vendors')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_requisition_items', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_requisition_items', 'purchase_order_id')) {
                $table->dropForeign(['purchase_order_id']);
                $table->dropColumn('purchase_order_id');
            }

            if (Schema::hasColumn('purchase_requisition_items', 'vendor_id')) {
                $table->dropForeign(['vendor_id']);
                $table->dropColumn('vendor_id');
            }
        });
    }
};
