<?php

namespace Database\Seeders;

use App\Models\FlightLog;
use App\Models\Reimbursement;
use App\Models\Trf;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorEvaluation;
use App\Support\Roles;
use Illuminate\Database\Seeder;

class TravelEvaluationDemoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', Roles::ADMIN_HEAD)->first() ?? User::first();
        $rohit = User::where('role', Roles::BUYER)->first() ?? $admin;
        $vikram = User::where('role', Roles::HOD)->first() ?? $admin;
        $priya = User::where('role', Roles::PR_SR_EXEC)->first() ?? $admin;

        if (! $admin) {
            return;
        }

        $partners = [
            'balmer' => Vendor::firstOrCreate(
                ['vendor_code' => 'VEND-TRV-101'],
                [
                    'company_name' => 'Balmer Lawrie Travel',
                    'contact_person' => 'Arvind Sharma',
                    'contact_email' => 'arvind@balmertravel.example',
                    'contact_phone' => '9811022334',
                    'category' => 'Travel & Aviation',
                    'status' => 'approved',
                    'compliance_status' => 'active',
                    'submitted_by' => $admin->id,
                ]
            ),
            'mmt' => Vendor::firstOrCreate(
                ['vendor_code' => 'VEND-TRV-102'],
                [
                    'company_name' => 'MakeMyTrip Corporate',
                    'contact_person' => 'Neha Kapoor',
                    'contact_email' => 'neha.k@mmtcorp.example',
                    'contact_phone' => '9820044556',
                    'category' => 'Travel & Hospitality',
                    'status' => 'approved',
                    'compliance_status' => 'active',
                    'submitted_by' => $admin->id,
                ]
            ),
            'thomascook' => Vendor::firstOrCreate(
                ['vendor_code' => 'VEND-TRV-103'],
                [
                    'company_name' => 'Thomas Cook Business',
                    'contact_person' => 'Siddharth Nair',
                    'contact_email' => 'siddharth@thomascook.example',
                    'contact_phone' => '9845066778',
                    'category' => 'Travel & Visa Services',
                    'status' => 'approved',
                    'compliance_status' => 'active',
                    'submitted_by' => $admin->id,
                ]
            ),
        ];

        $scenarios = [
            [
                'trf_number' => 'TRF-DEMO-810',
                'employee_id' => $vikram->id,
                'purpose' => 'National Manufacturing Conclave',
                'route' => 'New Delhi (DEL) → Mumbai (BOM)',
                'travel_mode' => 'Flight',
                'is_international' => false,
                'days_ago_start' => 25,
                'days_ago_end' => 22,
                'advance' => 12000,
                'flt_ref' => 'FLT-DEMO-810',
                'flt_type' => 'Flight ticket',
                'flt_cost' => 11450,
                'vendor_key' => 'balmer',
                'scores' => ['cost' => 5, 'time' => 5, 'quality' => 5, 'resp' => 4, 'acc' => 5],
                'comments' => 'Lowest corporate fare locked within 2 hours; boarding passes shared promptly.',
            ],
            [
                'trf_number' => 'TRF-DEMO-811',
                'employee_id' => $rohit->id,
                'purpose' => 'ASEAN Chamber Delegation',
                'route' => 'New Delhi (DEL) → Singapore (SIN)',
                'travel_mode' => 'Flight',
                'is_international' => true,
                'days_ago_start' => 19,
                'days_ago_end' => 15,
                'advance' => 35000,
                'flt_ref' => 'FLT-DEMO-811',
                'flt_type' => 'Hotel',
                'flt_cost' => 28600,
                'vendor_key' => 'balmer',
                'scores' => ['cost' => 4, 'time' => 5, 'quality' => 5, 'resp' => 5, 'acc' => 5],
                'comments' => 'Seamless Marina Bay corporate hotel check-in and early-morning airport transfer.',
            ],
            [
                'trf_number' => 'TRF-DEMO-812',
                'employee_id' => $priya->id,
                'purpose' => 'Southern Region Industry Roundtable',
                'route' => 'New Delhi (DEL) → Bengaluru (BLR)',
                'travel_mode' => 'Flight',
                'is_international' => false,
                'days_ago_start' => 14,
                'days_ago_end' => 12,
                'advance' => 10000,
                'flt_ref' => 'FLT-DEMO-812',
                'flt_type' => 'Flight ticket',
                'flt_cost' => 9800,
                'vendor_key' => 'mmt',
                'scores' => ['cost' => 4, 'time' => 4, 'quality' => 4, 'resp' => 5, 'acc' => 4],
                'comments' => 'Good flight options and responsive GST invoice issuance.',
            ],
            [
                'trf_number' => 'TRF-DEMO-813',
                'employee_id' => $vikram->id,
                'purpose' => 'Western Corridor Supply Chain Meet',
                'route' => 'New Delhi (DEL) → Ahmedabad (AMD)',
                'travel_mode' => 'Flight',
                'is_international' => false,
                'days_ago_start' => 9,
                'days_ago_end' => 7,
                'advance' => 8000,
                'flt_ref' => 'FLT-DEMO-813',
                'flt_type' => 'Hotel',
                'flt_cost' => 7600,
                'vendor_key' => 'thomascook',
                'scores' => ['cost' => 3, 'time' => 4, 'quality' => 3, 'resp' => 3, 'acc' => 4],
                'comments' => 'Confirmation voucher arrived slightly late; hotel quality was acceptable.',
            ],
        ];

        foreach ($scenarios as $s) {
            $trf = Trf::firstOrCreate(
                ['trf_number' => $s['trf_number']],
                [
                    'employee_id' => $s['employee_id'],
                    'booking_type' => 'self',
                    'purpose' => $s['purpose'],
                    'route' => $s['route'],
                    'travel_mode' => $s['travel_mode'],
                    'destinations' => $s['route'],
                    'is_international' => $s['is_international'],
                    'travel_start' => now()->subDays($s['days_ago_start'])->toDateString(),
                    'travel_end' => now()->subDays($s['days_ago_end'])->toDateString(),
                    'accommodation' => true,
                    'requested_advance' => $s['advance'],
                    'status' => 'approved',
                ]
            );

            // Mark demo TRF settled so it doesn't trigger overdue travel blocks
            Reimbursement::firstOrCreate(
                ['claim_number' => 'REIM-DEMO-' . substr($s['trf_number'], -3)],
                [
                    'trf_id' => $trf->id,
                    'employee_id' => $s['employee_id'],
                    'claim_amount' => $s['advance'],
                    'advance_amount' => $s['advance'],
                    'balance_amount' => 0,
                    'routing_flag' => 'standard',
                    'status' => 'approved',
                    'submitted_at' => now()->subDays(max(1, $s['days_ago_end'] - 2)),
                ]
            );

            $vendor = $partners[$s['vendor_key']];

            FlightLog::firstOrCreate(
                ['ref' => $s['flt_ref']],
                [
                    'trf_id' => $trf->id,
                    'type' => $s['flt_type'],
                    'vendor_id' => $vendor->id,
                    'cost' => $s['flt_cost'],
                    'uploaded_by' => $admin->id,
                ]
            );

            VendorEvaluation::firstOrCreate(
                ['trf_id' => $trf->id, 'vendor_id' => $vendor->id],
                [
                    'cost_score' => $s['scores']['cost'],
                    'time_score' => $s['scores']['time'],
                    'quality_score' => $s['scores']['quality'],
                    'responsiveness_score' => $s['scores']['resp'],
                    'accuracy_score' => $s['scores']['acc'],
                    'comments' => $s['comments'],
                    'rated_by' => $admin->id,
                ]
            );
        }

        // Sync vendor average ratings
        foreach ($partners as $vendor) {
            $evals = VendorEvaluation::where('vendor_id', $vendor->id)->get();
            if ($evals->isNotEmpty()) {
                $avg = round($evals->avg(fn (VendorEvaluation $e) => $e->averageRating()), 1);
                $vendor->update(['rating' => $avg]);
            }
        }
    }
}

