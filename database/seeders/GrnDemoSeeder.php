<?php

namespace Database\Seeders;

use App\Models\Approval;
use App\Models\Grn;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Database\Seeder;

class GrnDemoSeeder extends Seeder
{
    public function run(): void
    {
        $creator = User::where('role', Roles::PR_SR_EXEC)->first() ?? User::first();
        $hod = User::where('role', Roles::HOD)->first() ?? $creator;

        $pos = PurchaseOrder::with(['purchaseRequisition.items', 'vendor'])->get();
        if ($pos->isEmpty()) {
            return;
        }

        $dummyConfigs = [
            [
                'invoice' => 'INV-2026-8812',
                'received_qty' => 5,
                'status' => 'pending_hod',
                'quality' => 'Initial bulk lot received at YMCA Stores. Physical outer packaging verified intact.',
                'date' => now()->subDays(2)->toDateString(),
            ],
            [
                'invoice' => 'CH-DEL-5421',
                'received_qty' => 8,
                'status' => 'approved',
                'quality' => 'Complete hardware batch tested and verified. Serial numbers mapped to IT inventory register.',
                'date' => now()->subDays(5)->toDateString(),
            ],
            [
                'invoice' => 'INV-99430-A',
                'received_qty' => 3,
                'status' => 'pending_hod',
                'quality' => 'Consignment inspected on site; preliminary check satisfactory. Awaiting HOD final verification.',
                'date' => now()->subDay()->toDateString(),
            ],
            [
                'invoice' => 'INV-BL-7741',
                'received_qty' => 4,
                'status' => 'approved',
                'quality' => 'All items unpacked, tested, and accepted against technical specifications.',
                'date' => now()->subDays(7)->toDateString(),
            ],
        ];

        foreach ($dummyConfigs as $idx => $cfg) {
            $po = $pos[$idx % $pos->count()];
            $itemsList = $po->purchaseRequisition?->items?->pluck('item_name')->implode(', ') ?: 'Institutional Supplies';

            $grnNumber = 'GRN-' . str_pad((string) (4000 + $idx + 1), 4, '0', STR_PAD_LEFT);

            // Skip if already exists
            if (Grn::where('grn_number', $grnNumber)->exists()) {
                continue;
            }

            $grn = Grn::create([
                'grn_number' => $grnNumber,
                'purchase_order_id' => $po->id,
                'purchase_requisition_id' => $po->purchase_requisition_id,
                'item_description' => $itemsList,
                'po_qty' => $po->po_qty ?: 22,
                'received_qty' => $cfg['received_qty'],
                'invoice_number' => $cfg['invoice'],
                'receipt_date' => $cfg['date'],
                'quality_remarks' => $cfg['quality'],
                'status' => $cfg['status'],
                'created_by' => $creator->id,
            ]);

            if ($cfg['status'] === 'approved') {
                $grn->approvals()->create([
                    'level' => 1,
                    'role_required' => Roles::HOD,
                    'approver_id' => $hod->id,
                    'status' => 'approved',
                    'comments' => 'Goods received in sound condition. Quality, specifications, and counts verified against PO.',
                    'acted_at' => now()->subDays(3),
                ]);
            } else {
                $grn->approvals()->create([
                    'level' => 1,
                    'role_required' => Roles::HOD,
                    'status' => 'pending',
                ]);
            }
        }
    }
}
