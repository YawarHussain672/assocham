<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_mail_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trf_id')->constrained('trfs')->cascadeOnDelete();
            $table->foreignId('flight_log_id')->constrained('flight_logs')->cascadeOnDelete();
            $table->string('recipient_email');
            $table->string('recipient_name')->nullable();
            $table->string('subject');
            $table->longText('body_html')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('status')->default('sent'); // sent | failed
            $table->text('error_message')->nullable();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_mail_transactions');
    }
};

