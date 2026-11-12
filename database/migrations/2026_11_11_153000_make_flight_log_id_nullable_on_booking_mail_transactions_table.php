<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_mail_transactions', function (Blueprint $table) {
            $table->foreignId('flight_log_id')->nullable()->change();
            $table->string('mail_type')->default('booking_confirmation')->after('flight_log_id');
        });
    }

    public function down(): void
    {
        Schema::table('booking_mail_transactions', function (Blueprint $table) {
            $table->dropColumn('mail_type');
        });
    }
};

