<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rfqs', function (Blueprint $table) {
            $table->id();
            $table->string('rfq_number')->unique(); // e.g. RFQ-0057
            // RFQs may only be raised against an already-approved PR.
            $table->foreignId('purchase_requisition_id')->constrained()->cascadeOnDelete();
            $table->date('requisition_date')->nullable();
            $table->foreignId('poc_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('draft'); // draft | dispatched | quotes_received | approved
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfqs');
    }
};
