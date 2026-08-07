<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ccs_quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cost_comparative_statement_id')->constrained('cost_comparative_statements')->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->decimal('quoted_cost', 14, 2);
            $table->unsignedInteger('delivery_days')->nullable();
            $table->text('remarks')->nullable();
            $table->string('rank')->nullable(); // L1 | L2 | ...
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ccs_quotes');
    }
};
