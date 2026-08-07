<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cost_comparative_statements', function (Blueprint $table) {
            $table->id();
            $table->string('ccs_number')->unique(); // e.g. CCS-0030
            $table->foreignId('rfq_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('draft'); // draft | pending_committee | approved
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cost_comparative_statements');
    }
};
