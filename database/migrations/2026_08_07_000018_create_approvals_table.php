<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One polymorphic table for every module's approval chain (Vendor,
     * PurchaseRequisition, PurchaseOrder, Grn, Trf, Reimbursement, ...).
     * A record is created per level when the chain is instantiated (Phase 2/3
     * business logic); "level" + "role_required" describe the fixed step,
     * "approver_id" + "status" record what actually happened at that step.
     */
    public function up(): void
    {
        Schema::create('approvals', function (Blueprint $table) {
            $table->id();
            $table->morphs('approvable');
            $table->unsignedTinyInteger('level');
            $table->string('role_required'); // Roles::* slug
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('pending'); // pending | approved | rejected | pushed_back
            $table->text('comments')->nullable();
            $table->timestamp('acted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approvals');
    }
};
