<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Single consolidated, append-only trail across every module — written
     * to automatically by the LogsAuditTrail model trait (see app/Models/Concerns).
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('module'); // model class basename, e.g. "PurchaseRequisition"
            $table->string('action'); // created | updated | deleted | approved | rejected | overridden
            $table->string('reference')->nullable(); // human-readable ref, e.g. "PR-0211"
            $table->nullableMorphs('auditable');
            $table->text('detail')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
