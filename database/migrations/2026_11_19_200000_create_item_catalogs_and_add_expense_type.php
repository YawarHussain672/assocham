<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('item_catalogs')) {
            Schema::create('item_catalogs', function (Blueprint $table) {
                $table->id();
                $table->string('item_code', 50)->nullable()->unique();
                $table->string('name');
                $table->string('category', 100)->nullable();
                $table->string('expense_type', 20)->nullable()->default(null); // 'opex' | 'capex' | 'copex' (optional)
                $table->decimal('fixed_price', 14, 2)->default(0.00);
                $table->string('unit', 50)->default('Units');
                $table->decimal('gst_percent', 5, 2)->default(18.00);
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('purchase_requisitions', 'expense_type')) {
            Schema::table('purchase_requisitions', function (Blueprint $table) {
                $table->string('expense_type', 20)->nullable()->default('opex')->after('sole_source');
            });
        }

        if (! Schema::hasColumn('purchase_requisition_items', 'expense_type')) {
            Schema::table('purchase_requisition_items', function (Blueprint $table) {
                $table->string('expense_type', 20)->nullable()->default(null)->after('gst_percent');
                $table->foreignId('item_catalog_id')->nullable()->after('expense_type')->constrained('item_catalogs')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('purchase_requisition_items', 'item_catalog_id')) {
            Schema::table('purchase_requisition_items', function (Blueprint $table) {
                $table->dropForeign(['item_catalog_id']);
                $table->dropColumn(['item_catalog_id', 'expense_type']);
            });
        }

        if (Schema::hasColumn('purchase_requisitions', 'expense_type')) {
            Schema::table('purchase_requisitions', function (Blueprint $table) {
                $table->dropColumn('expense_type');
            });
        }

        Schema::dropIfExists('item_catalogs');
    }
};
