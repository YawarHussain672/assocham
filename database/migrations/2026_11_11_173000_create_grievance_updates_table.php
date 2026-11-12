<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grievance_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grievance_id')->constrained('grievances')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action'); // filed | responded | escalated | resolved
            $table->string('owner_role');
            $table->string('status'); // under_review | resolved
            $table->text('message');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grievance_updates');
    }
};

