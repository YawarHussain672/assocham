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
        Schema::table('ccs_quotes', function (Blueprint $table) {
            $table->decimal('basic_price', 14, 2)->nullable()->after('quoted_cost');
            $table->decimal('gst_amount', 14, 2)->nullable()->after('basic_price');
            $table->decimal('transportation_cost', 14, 2)->nullable()->after('gst_amount');
            $table->string('warranty')->nullable()->after('delivery_days');
            $table->string('payment_terms')->nullable()->after('warranty');
            $table->string('technical_compliance')->nullable()->default('Yes')->after('payment_terms');
            $table->string('quotation_validity')->nullable()->default('30 Days')->after('technical_compliance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ccs_quotes', function (Blueprint $table) {
            $table->dropColumn([
                'basic_price',
                'gst_amount',
                'transportation_cost',
                'warranty',
                'payment_terms',
                'technical_compliance',
                'quotation_validity',
            ]);
        });
    }
};
