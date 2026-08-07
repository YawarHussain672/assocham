<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PO Creation "auto-populates from PR & CCS data" (deck p.13) — the
     * vendor and amount come from the CCS's L1 (lowest) quote. Also used
     * to prevent raising more than one PO against the same CCS.
     */
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->foreignId('cost_comparative_statement_id')->nullable()
                ->after('purchase_requisition_id')
                ->constrained('cost_comparative_statements')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cost_comparative_statement_id');
        });
    }
};
