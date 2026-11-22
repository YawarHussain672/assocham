<?php

use App\Models\ProjectBudget;
use App\Models\PurchaseRequisition;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('purchase_requisitions', 'project_budget_id')) {
            Schema::table('purchase_requisitions', function (Blueprint $table) {
                $table->foreignId('project_budget_id')
                    ->nullable()
                    ->after('event_name')
                    ->constrained('project_budgets')
                    ->nullOnDelete();
            });
        }

        // Backfill existing PRs that have an event_name matching an existing project
        foreach (PurchaseRequisition::whereNull('project_budget_id')->whereNotNull('event_name')->get() as $pr) {
            $pb = ProjectBudget::where('project_name', $pr->event_name)->first();
            if ($pb) {
                $pr->update(['project_budget_id' => $pb->id]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('purchase_requisitions', function (Blueprint $table) {
            $table->dropForeign(['project_budget_id']);
            $table->dropColumn('project_budget_id');
        });
    }
};
