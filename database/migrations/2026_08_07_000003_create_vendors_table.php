<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('vendor_code')->unique(); // e.g. VEN-0098
            $table->string('company_name');
            $table->string('pan')->nullable();
            $table->string('gstin')->nullable();
            $table->string('category')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('bank_account_no')->nullable();
            $table->string('bank_ifsc')->nullable();
            $table->boolean('msme_status')->default(false);
            $table->string('payment_terms')->nullable(); // e.g. "Net 30"
            $table->text('address')->nullable();

            // draft -> pending_admin_head -> pending_l1 -> pending_l2 -> pending_cfo -> approved / rejected
            $table->string('status')->default('draft');
            $table->string('compliance_status')->default('active'); // active | suspended | blacklisted

            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
