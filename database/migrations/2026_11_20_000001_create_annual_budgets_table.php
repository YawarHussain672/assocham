<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('annual_budgets', function (Blueprint $table) {
            $table->id();
            $table->string('budget_number', 60)->unique();
            $table->string('department', 100);
            $table->string('fiscal_year', 20);
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('proposed_amount', 14, 2);
            $table->decimal('approved_amount', 14, 2)->nullable();
            $table->text('justification')->nullable();
            $table->string('status', 30)->default('pending_cfo');
            $table->unsignedInteger('current_approval_level')->default(1);
            $table->timestamp('cfo_approved_at')->nullable();
            $table->text('cfo_remarks')->nullable();
            $table->timestamp('sg_approved_at')->nullable();
            $table->text('sg_remarks')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->foreignId('department_budget_id')->nullable()->constrained('department_budgets')->nullOnDelete();
            $table->timestamps();

            $table->index(['department', 'fiscal_year']);
            $table->index('status');
        });

        Schema::create('annual_budget_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('annual_budget_id')->constrained('annual_budgets')->cascadeOnDelete();
            $table->string('category', 100);
            $table->string('title', 255);
            $table->decimal('amount', 14, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('annual_budget_items');
        Schema::dropIfExists('annual_budgets');
    }
};
