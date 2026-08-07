<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Slug from App\Support\Roles — one of the ten portal role logins.
            $table->string('role')->nullable()->after('email');
            // Department this user's requisitions/spend are scoped to for
            // budget checks (Projects, IT, Marketing, Finance, Operations, HR ...).
            $table->string('department')->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'department']);
        });
    }
};
