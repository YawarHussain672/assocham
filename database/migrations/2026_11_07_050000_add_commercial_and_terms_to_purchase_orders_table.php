<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('event_name')->nullable()->after('amount_incl_gst');
            $table->text('payment_terms')->nullable()->after('event_name');
            $table->text('delivery_terms')->nullable()->after('payment_terms');
            $table->text('billing_address')->nullable()->after('delivery_terms');
            $table->text('delivery_address')->nullable()->after('billing_address');
            $table->string('contact_person')->nullable()->after('delivery_address');
            $table->string('contact_number')->nullable()->after('contact_person');
            $table->longText('terms_and_conditions')->nullable()->after('contact_number');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn([
                'event_name',
                'payment_terms',
                'delivery_terms',
                'billing_address',
                'delivery_address',
                'contact_person',
                'contact_number',
                'terms_and_conditions',
            ]);
        });
    }
};

