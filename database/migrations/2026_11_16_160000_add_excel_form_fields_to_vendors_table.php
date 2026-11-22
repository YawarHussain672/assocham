<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->string('id_number')->nullable()->after('contact_email');
            $table->string('bank_name')->nullable()->after('bank_account_no');
            $table->string('bank_branch')->nullable()->after('bank_name');
            $table->string('bank_account_holder')->nullable()->after('bank_branch');
            $table->string('bank_account_type')->nullable()->after('bank_account_holder');
            $table->text('references')->nullable()->after('category');
            $table->string('msme_number')->nullable()->after('msme_status');
            $table->string('incorporation_certificate_no')->nullable()->after('gstin');
            $table->string('partnership_deed_no')->nullable()->after('incorporation_certificate_no');
            $table->boolean('declaration_accepted')->default(false)->after('submitted_by');
            $table->string('declaration_signatory_name')->nullable()->after('declaration_accepted');
            $table->string('declaration_designation')->nullable()->after('declaration_signatory_name');
            $table->date('declaration_date')->nullable()->after('declaration_designation');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn([
                'id_number',
                'bank_name',
                'bank_branch',
                'bank_account_holder',
                'bank_account_type',
                'references',
                'msme_number',
                'incorporation_certificate_no',
                'partnership_deed_no',
                'declaration_accepted',
                'declaration_signatory_name',
                'declaration_designation',
                'declaration_date',
            ]);
        });
    }
};
