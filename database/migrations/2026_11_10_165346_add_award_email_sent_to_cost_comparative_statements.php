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
            $table->timestamp('award_email_sent_at')->nullable()->after('selected_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cost_comparative_statements', function (Blueprint $table) {
            $table->dropColumn('award_email_sent_at');
        });
    }
};
