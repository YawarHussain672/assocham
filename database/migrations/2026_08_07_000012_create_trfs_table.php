<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trfs', function (Blueprint $table) {
            $table->id();
            $table->string('trf_number')->unique(); // e.g. TRF-0087
            $table->foreignId('employee_id')->constrained('users')->cascadeOnDelete();
            $table->string('booking_type')->default('self'); // self | on_behalf
            $table->string('purpose')->nullable();
            $table->string('route')->nullable(); // "Delhi -> Mumbai -> Bengaluru"
            $table->boolean('is_international')->default(false);
            $table->date('travel_start')->nullable();
            $table->date('travel_end')->nullable();
            $table->boolean('insurance')->default(false);
            $table->boolean('accommodation')->default(false);
            $table->decimal('requested_advance', 12, 2)->default(0);

            // draft -> pending_l1 (ASG) -> pending_l2 (SG, international/direct reports only) -> approved / pushed_back / rejected
            $table->string('status')->default('draft');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trfs');
    }
};
