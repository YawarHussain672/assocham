<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reimbursement_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reimbursement_id')->constrained('reimbursements')->cascadeOnDelete();
            $table->date('expense_date');
            $table->string('category', 80);
            $table->string('description', 255)->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('bill_path')->nullable();
            $table->string('bill_disk')->default('public');
            $table->string('bill_name')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reimbursement_items');
    }
};

