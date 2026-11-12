<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->boolean('needs_regeneration')->default(false)->after('status');
            $table->unsignedInteger('revision_number')->default(0)->after('needs_regeneration');
            $table->timestamp('regenerated_at')->nullable()->after('revision_number');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn([
                'needs_regeneration',
                'revision_number',
                'regenerated_at',
            ]);
        });
    }
};

