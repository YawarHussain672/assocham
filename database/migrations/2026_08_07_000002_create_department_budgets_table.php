<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department_budgets', function (Blueprint $table) {
            $table->id();
            $table->string('department');
            $table->string('fiscal_year'); // e.g. "FY26-27"
            $table->decimal('allocated_amount', 14, 2);
            $table->decimal('utilised_amount', 14, 2)->default(0);
            $table->decimal('gst_rate', 5, 2)->default(18.00);
            $table->timestamps();

            $table->unique(['department', 'fiscal_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_budgets');
    }
};
