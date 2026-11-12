<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->text('blacklist_reason')->nullable()->after('compliance_status');
            $table->timestamp('blacklisted_at')->nullable()->after('blacklist_reason');
            $table->foreignId('blacklisted_by')->nullable()->after('blacklisted_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropForeign(['blacklisted_by']);
            $table->dropColumn(['blacklist_reason', 'blacklisted_at', 'blacklisted_by']);
        });
    }
};
