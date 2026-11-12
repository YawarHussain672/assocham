<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trfs', function (Blueprint $table) {
            $table->string('travel_mode')->nullable()->after('route');
            $table->text('destinations')->nullable()->after('travel_mode');
            $table->string('accommodation_details')->nullable()->after('accommodation');
            $table->boolean('visa_required')->default(false)->after('insurance');
            $table->string('visa_details')->nullable()->after('visa_required');
        });
    }

    public function down(): void
    {
        Schema::table('trfs', function (Blueprint $table) {
            $table->dropColumn([
                'travel_mode',
                'destinations',
                'accommodation_details',
                'visa_required',
                'visa_details',
            ]);
        });
    }
};

