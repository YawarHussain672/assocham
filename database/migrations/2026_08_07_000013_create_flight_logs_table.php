<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flight_logs', function (Blueprint $table) {
            $table->id();
            $table->string('ref')->unique(); // e.g. FLT-0041
            $table->foreignId('trf_id')->constrained('trfs')->cascadeOnDelete();
            $table->string('type')->nullable(); // Flight ticket | Hotel | Transport | ...
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('cost', 12, 2)->default(0);
            $table->string('file_path')->nullable(); // boarding pass / mail transaction
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flight_logs');
    }
};
