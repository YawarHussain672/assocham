<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grievances', function (Blueprint $table) {
            $table->foreignId('reimbursement_id')->nullable()->after('description')->constrained('reimbursements')->nullOnDelete();
            $table->text('resolution_notes')->nullable()->after('resolved_at');
        });
    }

    public function down(): void
    {
        Schema::table('grievances', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reimbursement_id');
            $table->dropColumn('resolution_notes');
        });
    }
};

