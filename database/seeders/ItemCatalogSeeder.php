<?php

namespace Database\Seeders;

use App\Models\ItemCatalog;
use Illuminate\Database\Seeder;

class ItemCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            // COPEX Items (Capital Expenditure)
            [
                'item_code' => 'COPEX-001',
                'name' => 'High-Performance Executive Laptop',
                'category' => 'IT & Hardware',
                'expense_type' => 'copex',
                'fixed_price' => 85000.00,
                'unit' => 'Units',
                'gst_percent' => 18.00,
                'description' => 'Dell Latitude 5540, Intel Core i7 13th Gen, 16GB RAM, 512GB SSD, 3-Year Onsite ProSupport.',
                'is_active' => true,
            ],
            [
                'item_code' => 'COPEX-002',
                'name' => 'Laptops',
                'category' => 'IT & Hardware',
                'expense_type' => 'copex',
                'fixed_price' => 65000.00,
                'unit' => 'Units',
                'gst_percent' => 18.00,
                'description' => 'Standard enterprise workstation laptop for staff, 16GB RAM, 512GB SSD, Windows 11 Pro.',
                'is_active' => true,
            ],
            [
                'item_code' => 'COPEX-003',
                'name' => 'High-End Mobile Workstation',
                'category' => 'IT & Hardware',
                'expense_type' => 'copex',
                'fixed_price' => 145000.00,
                'unit' => 'Units',
                'gst_percent' => 18.00,
                'description' => 'Dell Precision 3580, Nvidia RTX A500, 32GB RAM, 1TB SSD for design and data analytics.',
                'is_active' => true,
            ],
            [
                'item_code' => 'COPEX-004',
                'name' => 'Conference Room 4K LED Display & VC System',
                'category' => 'AV & Electronics',
                'expense_type' => 'copex',
                'fixed_price' => 320000.00,
                'unit' => 'Sets',
                'gst_percent' => 18.00,
                'description' => '75-inch commercial 4K display, PTZ optical zoom camera, wireless microphone array.',
                'is_active' => true,
            ],
            [
                'item_code' => 'COPEX-005',
                'name' => 'Cisco Catalyst Core Network Switch',
                'category' => 'IT & Infrastructure',
                'expense_type' => 'copex',
                'fixed_price' => 180000.00,
                'unit' => 'Units',
                'gst_percent' => 18.00,
                'description' => '48-port Gigabit PoE+ managed enterprise core switch with redundant power supplies.',
                'is_active' => true,
            ],
            [
                'item_code' => 'COPEX-006',
                'name' => 'Enterprise Server PowerEdge R750',
                'category' => 'IT & Infrastructure',
                'expense_type' => 'copex',
                'fixed_price' => 850000.00,
                'unit' => 'Units',
                'gst_percent' => 18.00,
                'description' => 'Dual Intel Xeon Silver, 128GB ECC RAM, RAID-10 enterprise NVMe storage array.',
                'is_active' => true,
            ],
            [
                'item_code' => 'COPEX-007',
                'name' => 'Heavy Duty Industrial Multi-Function Copier',
                'category' => 'Office Equipment',
                'expense_type' => 'copex',
                'fixed_price' => 160000.00,
                'unit' => 'Units',
                'gst_percent' => 18.00,
                'description' => 'Floor-standing color A3 laser multi-function printer with finisher unit.',
                'is_active' => true,
            ],
            [
                'item_code' => 'COPEX-008',
                'name' => 'Ergonomic Executive Workstation & Chair Set',
                'category' => 'Office Furniture',
                'expense_type' => 'copex',
                'fixed_price' => 45000.00,
                'unit' => 'Sets',
                'gst_percent' => 18.00,
                'description' => 'Modular motorized height-adjustable desk and BIFMA ergonomic high-back mesh chair.',
                'is_active' => true,
            ],

            // OPEX Items (Operating Expenditure)
            [
                'item_code' => 'OPEX-001',
                'name' => 'Executive Printing & Stationery Kit',
                'category' => 'Stationery & Consumables',
                'expense_type' => 'opex',
                'fixed_price' => 12500.00,
                'unit' => 'Sets',
                'gst_percent' => 18.00,
                'description' => 'ASSOCHAM branded executive letterheads, envelopes, note pads, and writing supplies.',
                'is_active' => true,
            ],
            [
                'item_code' => 'OPEX-002',
                'name' => 'Monthly Office Housekeeping & Consumables',
                'category' => 'Facilities & Maintenance',
                'expense_type' => 'opex',
                'fixed_price' => 35000.00,
                'unit' => 'Months',
                'gst_percent' => 18.00,
                'description' => 'Monthly pantry, sanitization, and housekeeping consumables.',
                'is_active' => true,
            ],
            [
                'item_code' => 'OPEX-003',
                'name' => 'Annual Cloud ERP & Hosting Subscription',
                'category' => 'Software & Cloud',
                'expense_type' => 'opex',
                'fixed_price' => 240000.00,
                'unit' => 'Years',
                'gst_percent' => 18.00,
                'description' => 'Secure cloud hosting, daily backups, and SSL infrastructure maintenance.',
                'is_active' => true,
            ],
            [
                'item_code' => 'OPEX-004',
                'name' => 'Professional Event Stage & AV Rental',
                'category' => 'Event Operations',
                'expense_type' => 'opex',
                'fixed_price' => 175000.00,
                'unit' => 'Days',
                'gst_percent' => 18.00,
                'description' => 'Daily rental for conference stage lighting, sound system, and technician support.',
                'is_active' => true,
            ],
            [
                'item_code' => 'OPEX-005',
                'name' => 'Courier & Express Logistics Bulk Package',
                'category' => 'Logistics',
                'expense_type' => 'opex',
                'fixed_price' => 18000.00,
                'unit' => 'Packages',
                'gst_percent' => 18.00,
                'description' => 'Bulk nationwide dispatch package for official event passes and summit brochures.',
                'is_active' => true,
            ],
            [
                'item_code' => 'OPEX-006',
                'name' => 'Annual Central HVAC Comprehensive AMC',
                'category' => 'Facilities & Maintenance',
                'expense_type' => 'opex',
                'fixed_price' => 650000.00,
                'unit' => 'Years',
                'gst_percent' => 18.00,
                'description' => 'High-value annual comprehensive heating, ventilation, and air-conditioning maintenance contract.',
                'is_active' => true,
            ],
        ];

        foreach ($items as $data) {
            ItemCatalog::updateOrCreate(
                ['name' => $data['name']],
                $data
            );
        }
    }
}
