<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grns', function (Blueprint $table) {
            $table->foreignId('purchase_requisition_id')
                ->nullable()
                ->after('purchase_order_id')
                ->constrained('purchase_requisitions')
                ->nullOnDelete();
        });

        // Backfill existing GRNs from their parent PO's originating PR (SQLite & MySQL compatible)
        DB::statement(
            'UPDATE grns SET purchase_requisition_id = (SELECT purchase_requisition_id FROM purchase_orders WHERE purchase_orders.id = grns.purchase_order_id) WHERE purchase_requisition_id IS NULL'
        );
    }

    public function down(): void
    {
        Schema::table('grns', function (Blueprint $table) {
            $table->dropConstrainedForeignId('purchase_requisition_id');
        });
    }
};
