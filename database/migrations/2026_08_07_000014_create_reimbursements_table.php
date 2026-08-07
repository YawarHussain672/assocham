<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reimbursements', function (Blueprint $table) {
            $table->id();
            $table->string('claim_number')->unique(); // e.g. REIM-0064
            // Off-TRF claims are allowed (SG override, reason mandatory) so this is nullable.
            $table->foreignId('trf_id')->nullable()->constrained('trfs')->nullOnDelete();
            $table->foreignId('employee_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('claim_amount', 12, 2);
            $table->decimal('advance_amount', 12, 2)->default(0);
            $table->decimal('balance_amount', 12, 2)->default(0); // positive = due to traveler
            $table->string('routing_flag')->default('standard'); // standard | asg_ch_claim | exception
            $table->string('status')->default('pending_cfo'); // pending_cfo | pending_sg | approved | rejected
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reimbursements');
    }
};
