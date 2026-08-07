<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trf_id')->constrained('trfs')->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('cost_score');
            $table->unsignedTinyInteger('time_score');
            $table->unsignedTinyInteger('quality_score');
            $table->unsignedTinyInteger('responsiveness_score');
            $table->unsignedTinyInteger('accuracy_score');
            $table->text('comments')->nullable();
            $table->foreignId('rated_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_evaluations');
    }
};
