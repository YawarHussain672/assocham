<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Deck (p.41, live-portal note): "Off-TRF claims bypass TRF links but
     * require SG override reasons. Reason justification is mandatory."
     * The original schema had no free-text field for that justification.
     */
    public function up(): void
    {
        Schema::table('reimbursements', function (Blueprint $table) {
            $table->text('reason')->nullable()->after('routing_flag');
        });
    }

    public function down(): void
    {
        Schema::table('reimbursements', function (Blueprint $table) {
            $table->dropColumn('reason');
        });
    }
};
