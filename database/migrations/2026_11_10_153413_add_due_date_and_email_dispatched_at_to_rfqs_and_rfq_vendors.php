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
        Schema::table('rfqs', function (Blueprint $table) {
            $table->date('due_date')->nullable()->after('requisition_date');
            $table->timestamp('email_dispatched_at')->nullable()->after('status');
        });

        Schema::table('rfq_vendors', function (Blueprint $table) {
            $table->timestamp('email_sent_at')->nullable()->after('response_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rfqs', function (Blueprint $table) {
            $table->dropColumn(['due_date', 'email_dispatched_at']);
        });

        Schema::table('rfq_vendors', function (Blueprint $table) {
            $table->dropColumn(['email_sent_at']);
        });
    }
};
