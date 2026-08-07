<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_requisitions', function (Blueprint $table) {
            $table->id();
            $table->string('pr_number')->unique(); // e.g. PR/FY26-27/20260708-0208
            $table->string('department');
            $table->date('required_by')->nullable();
            $table->boolean('sole_source')->default(false);

            // draft -> pending_l1 (Basu) -> pending_l2 (Ambika) -> pending_l3 (CFO) -> pending_l4 (SG) -> approved / rejected
            $table->string('status')->default('draft');
            $table->unsignedTinyInteger('current_level')->default(0); // 0=draft, 1..4 = which approver is next

            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_requisitions');
    }
};
