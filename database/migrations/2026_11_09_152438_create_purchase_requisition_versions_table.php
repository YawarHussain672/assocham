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
        Schema::create('purchase_requisition_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_requisition_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version_number')->default(1);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event'); // created, edited, approved, rejected, resubmitted
            $table->string('action_title');
            $table->text('change_summary')->nullable(); // description of what changed
            $table->text('comments')->nullable(); // user remarks / rejection reasons
            $table->json('changes')->nullable(); // detailed key/value or item changes
            $table->json('snapshot')->nullable(); // full snapshot of PR data and line items at this version
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_requisition_versions');
    }
};
