<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grievances', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->unique(); // e.g. GRV-0001
            $table->string('subject_category'); // Technical Issue | Operational Delay | Budget Discrepancy | Vendor Dispute
            $table->string('subject');
            $table->text('description')->nullable();
            $table->foreignId('raised_by')->constrained('users')->cascadeOnDelete();
            // Fixed escalation: HOD -> Admin Head -> CFO -> SG
            $table->string('current_owner_role')->default('hod');
            $table->string('status')->default('under_review'); // under_review | resolved
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grievances');
    }
};
