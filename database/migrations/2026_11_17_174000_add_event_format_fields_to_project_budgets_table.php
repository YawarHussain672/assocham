<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_budgets', function (Blueprint $table) {
            $table->string('council')->nullable()->after('department');
            $table->string('cluster_head')->nullable()->after('council');
            $table->string('hod')->nullable()->after('cluster_head');
            $table->string('category')->nullable()->after('hod');
            $table->string('location')->nullable()->after('category');
            $table->string('event_month')->nullable()->after('location');
        });
    }

    public function down(): void
    {
        Schema::table('project_budgets', function (Blueprint $table) {
            $table->dropColumn(['council', 'cluster_head', 'hod', 'category', 'location', 'event_month']);
        });
    }
};
