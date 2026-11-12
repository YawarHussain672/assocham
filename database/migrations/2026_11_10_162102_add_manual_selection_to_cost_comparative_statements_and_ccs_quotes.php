<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cost_comparative_statements', function (Blueprint $table) {
            $table->foreignId('selected_vendor_id')->nullable()->after('rfq_id')->constrained('vendors')->nullOnDelete();
            $table->text('selection_remarks')->nullable()->after('selected_vendor_id');
            $table->foreignId('selected_by')->nullable()->after('selection_remarks')->constrained('users')->nullOnDelete();
            $table->timestamp('selected_at')->nullable()->after('selected_by');
        });

        Schema::table('ccs_quotes', function (Blueprint $table) {
            $table->boolean('is_selected')->default(false)->after('rank');
            $table->text('selection_remarks')->nullable()->after('is_selected');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cost_comparative_statements', function (Blueprint $table) {
            $table->dropForeign(['selected_vendor_id']);
            $table->dropForeign(['selected_by']);
            $table->dropColumn(['selected_vendor_id', 'selection_remarks', 'selected_by', 'selected_at']);
        });

        Schema::table('ccs_quotes', function (Blueprint $table) {
            $table->dropColumn(['is_selected', 'selection_remarks']);
        });
    }
};
