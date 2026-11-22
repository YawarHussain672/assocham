<?php

namespace Database\Seeders;

use App\Models\AnnualBudget;
use App\Models\AnnualBudgetItem;
use App\Models\Approval;
use App\Models\AuditLog;
use App\Models\DepartmentBudget;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Database\Seeder;

class AnnualBudgetSeeder extends Seeder
{
    public function run(): void
    {
        $adminHead = User::where('role', Roles::ADMIN_HEAD)->first();
        $hodOps = User::where('role', Roles::HOD)->first();
        $cfo = User::where('role', Roles::CFO)->first();
        $sg = User::where('role', Roles::SG)->first();

        $adminId = $adminHead?->id ?? 1;
        $hodId = $hodOps?->id ?? $adminId;
        $cfoId = $cfo?->id ?? $adminId;
        $sgId = $sg?->id ?? $adminId;

        // =========================================================================
        // 1. FY26-27 Approved Proposals (The foundation for the current Budget Master)
        // =========================================================================
        $fy2627Proposals = [
            [
                'budget_number' => 'AB-26-27-PROJ-001',
                'department' => 'Projects',
                'fiscal_year' => 'FY26-27',
                'submitted_by' => $adminId,
                'proposed_amount' => 8500000.00,
                'approved_amount' => 8500000.00,
                'justification' => 'Comprehensive national project delivery budget covering industry summits, state roundtables, and international trade missions.',
                'items' => [
                    ['category' => 'Events & Summits', 'title' => 'National Industry Summit Venue & Staging', 'amount' => 3500000.00, 'notes' => 'Vigyan Bhawan & Pragati Maidan booking and AV staging'],
                    ['category' => 'Exhibition & Fabrication', 'title' => 'Pavilion Construction & Branding', 'amount' => 2500000.00, 'notes' => 'Contracted booth fabrication and thematic branding'],
                    ['category' => 'Logistics & Delegates', 'title' => 'Delegate Management & Speaker Logistics', 'amount' => 1500000.00, 'notes' => 'VIP speaker hospitality and registration portals'],
                    ['category' => 'Outreach', 'title' => 'Regional Industry Roundtables', 'amount' => 1000000.00, 'notes' => 'State chapter tier-2 manufacturing conclaves'],
                ],
            ],
            [
                'budget_number' => 'AB-26-27-IT-001',
                'department' => 'IT',
                'fiscal_year' => 'FY26-27',
                'submitted_by' => $adminId,
                'proposed_amount' => 2400000.00,
                'approved_amount' => 2200000.00,
                'justification' => 'Core digital infrastructure, secure multi-region cloud services, enterprise cyber defense subscriptions, and hardware life-cycle refresh.',
                'items' => [
                    ['category' => 'Cloud & Hosting', 'title' => 'Enterprise Cloud Hosting & Database Cluster', 'amount' => 1200000.00, 'notes' => 'AWS & Google Cloud high-availability hosting'],
                    ['category' => 'Cybersecurity', 'title' => 'Perimeter Firewall & SOC Subscriptions', 'amount' => 600000.00, 'notes' => 'Next-gen threat intelligence and SSL certs'],
                    ['category' => 'Hardware', 'title' => 'Core Network Switch & Workstation Refresh', 'amount' => 400000.00, 'notes' => 'Server rack UPS replacement and engineering terminals'],
                ],
            ],
            [
                'budget_number' => 'AB-26-27-OPER-001',
                'department' => 'Operations',
                'fiscal_year' => 'FY26-27',
                'submitted_by' => $hodId,
                'proposed_amount' => 3500000.00,
                'approved_amount' => 3200000.00,
                'justification' => 'Institutional headquarters operational upkeep, facilities maintenance, power backup, AMC contracts, and administrative utilities.',
                'items' => [
                    ['category' => 'Facilities & AMC', 'title' => 'HVAC, Elevator & Electrical AMCs', 'amount' => 1600000.00, 'notes' => 'Annual comprehensive maintenance contracts'],
                    ['category' => 'Utilities', 'title' => 'Commercial Power & High-Speed Telecoms', 'amount' => 1000000.00, 'notes' => 'Grid electricity, DG diesel, and leased line internet'],
                    ['category' => 'Services', 'title' => 'Security, Housekeeping & Courier Contracts', 'amount' => 600000.00, 'notes' => '24x7 security personnel and facility sanitation'],
                ],
            ],
            [
                'budget_number' => 'AB-26-27-MKT-001',
                'department' => 'Marketing',
                'fiscal_year' => 'FY26-27',
                'submitted_by' => $adminId,
                'proposed_amount' => 1500000.00,
                'approved_amount' => 1500000.00,
                'justification' => 'Institutional brand amplification, digital engagement campaigns, economic research publications, and national media relations.',
                'items' => [
                    ['category' => 'Digital Campaigns', 'title' => 'Omnichannel Digital Campaigns & Social Media', 'amount' => 700000.00, 'notes' => 'LinkedIn sponsored outreach and policy campaign ads'],
                    ['category' => 'Publications', 'title' => 'ASSOCHAM Annual Economic Outlook Reports', 'amount' => 500000.00, 'notes' => 'Research monograph printing and digital distribution'],
                    ['category' => 'Media & PR', 'title' => 'National Press Bureau & PR Wire Retainer', 'amount' => 300000.00, 'notes' => 'National press briefing releases and media tracking'],
                ],
            ],
            [
                'budget_number' => 'AB-26-27-HR-001',
                'department' => 'HR',
                'fiscal_year' => 'FY26-27',
                'submitted_by' => $adminId,
                'proposed_amount' => 1200000.00,
                'approved_amount' => 1200000.00,
                'justification' => 'Group employee health insurance policies, corporate learning & leadership workshops, talent onboarding, and staff welfare.',
                'items' => [
                    ['category' => 'Insurance', 'title' => 'Group Mediclaim & Accidental Cover Subsidy', 'amount' => 700000.00, 'notes' => 'Annual premium co-pay for full-time staff'],
                    ['category' => 'L&D Programs', 'title' => 'Executive Leadership & Compliance Training', 'amount' => 300000.00, 'notes' => 'Workshops on ESG, taxation, and legal compliance'],
                    ['category' => 'Staff Welfare', 'title' => 'Annual Health Checkups & Recognition Awards', 'amount' => 200000.00, 'notes' => 'Preventive health screening and quarterly awards'],
                ],
            ],
            [
                'budget_number' => 'AB-26-27-FIN-001',
                'department' => 'Finance',
                'fiscal_year' => 'FY26-27',
                'submitted_by' => $adminId,
                'proposed_amount' => 900000.00,
                'approved_amount' => 900000.00,
                'justification' => 'Statutory audit retainers, GST advisory consultants, accounting software subscriptions, and corporate compliance filings.',
                'items' => [
                    ['category' => 'Audit & Advisory', 'title' => 'Statutory Audit Fees & GST Advisory', 'amount' => 500000.00, 'notes' => 'Independent chartered accountant retainer and tax filings'],
                    ['category' => 'Software', 'title' => 'Enterprise Accounting & Payroll Cloud License', 'amount' => 250000.00, 'notes' => 'Annual SaaS licenses for accounting ledger'],
                    ['category' => 'Compliance', 'title' => 'ROC & Regulatory Filing Fees', 'amount' => 150000.00, 'notes' => 'Ministry of Corporate Affairs annual returns'],
                ],
            ],
        ];

        foreach ($fy2627Proposals as $pData) {
            $deptBudget = DepartmentBudget::where('department', $pData['department'])
                ->where('fiscal_year', $pData['fiscal_year'])
                ->first();

            $budget = AnnualBudget::updateOrCreate(
                ['budget_number' => $pData['budget_number']],
                [
                    'department' => $pData['department'],
                    'fiscal_year' => $pData['fiscal_year'],
                    'submitted_by' => $pData['submitted_by'],
                    'proposed_amount' => $pData['proposed_amount'],
                    'approved_amount' => $pData['approved_amount'],
                    'justification' => $pData['justification'],
                    'status' => AnnualBudget::STATUS_APPROVED,
                    'current_approval_level' => 2,
                    'cfo_approved_at' => now()->subMonths(6),
                    'cfo_remarks' => 'Financial run-rates vetted and confirmed aligned with institutional priorities. Recommended for full sanction.',
                    'sg_approved_at' => now()->subMonths(6)->addDays(2),
                    'sg_remarks' => 'Approved and sanctioned for FY26-27 departmental operation.',
                    'finalized_at' => now()->subMonths(6)->addDays(2),
                    'department_budget_id' => $deptBudget?->id,
                ]
            );

            // Seed line items
            $budget->items()->delete();
            foreach ($pData['items'] as $item) {
                AnnualBudgetItem::create([
                    'annual_budget_id' => $budget->id,
                    'category' => $item['category'],
                    'title' => $item['title'],
                    'amount' => $item['amount'],
                    'notes' => $item['notes'],
                ]);
            }

            // Seed approvals
            $budget->approvals()->delete();
            $budget->approvals()->create([
                'level' => 1,
                'role_required' => Roles::CFO,
                'approver_id' => $cfoId,
                'status' => Approval::STATUS_APPROVED,
                'acted_at' => now()->subMonths(6),
                'comments' => 'Level 1 financial endorsement granted.',
            ]);
            $budget->approvals()->create([
                'level' => 2,
                'role_required' => Roles::SG,
                'approver_id' => $sgId,
                'status' => Approval::STATUS_APPROVED,
                'acted_at' => now()->subMonths(6)->addDays(2),
                'comments' => 'Level 2 final institutional sanction granted.',
            ]);
        }

        // =========================================================================
        // 2. FY27-28 Upcoming Budget Proposals (Demonstrating Multi-Stage Governance)
        // =========================================================================

        // Proposal A: IT (Pending SG Sanction - Level 2)
        $itBudget = AnnualBudget::updateOrCreate(
            ['budget_number' => 'AB-27-28-IT-001'],
            [
                'department' => 'IT',
                'fiscal_year' => 'FY27-28',
                'submitted_by' => $adminId,
                'proposed_amount' => 2800000.00,
                'approved_amount' => 2600000.00,
                'justification' => 'FY27-28 IT strategic roadmap: Hybrid cloud scalability, zero-trust endpoint security, automated data warehouse, and modern video-conferencing suites.',
                'status' => AnnualBudget::STATUS_PENDING_SG,
                'current_approval_level' => 2,
                'cfo_approved_at' => now()->subDays(2),
                'cfo_remarks' => 'Reviewed against projected institutional revenue. Rationalized hardware refresh to ₹26.0 Lakhs. Endorsed for Level 2 sanction.',
                'sg_approved_at' => null,
                'sg_remarks' => null,
                'finalized_at' => null,
                'department_budget_id' => null,
            ]
        );
        $itBudget->items()->delete();
        AnnualBudgetItem::create(['annual_budget_id' => $itBudget->id, 'category' => 'Cloud Infrastructure', 'title' => 'Multi-Region Hybrid Cloud Hosting', 'amount' => 1400000.00, 'notes' => 'Redundant disaster recovery cluster']);
        AnnualBudgetItem::create(['annual_budget_id' => $itBudget->id, 'category' => 'Cybersecurity', 'title' => 'Zero-Trust Endpoint Protection', 'amount' => 700000.00, 'notes' => 'EDR agents and automated vulnerability scanning']);
        AnnualBudgetItem::create(['annual_budget_id' => $itBudget->id, 'category' => 'Hardware Refresh', 'title' => 'Conference Room AV & Smart Boards', 'amount' => 500000.00, 'notes' => 'Hybrid meeting room modernization']);

        $itBudget->approvals()->delete();
        $itBudget->approvals()->create([
            'level' => 1,
            'role_required' => Roles::CFO,
            'approver_id' => $cfoId,
            'status' => Approval::STATUS_APPROVED,
            'acted_at' => now()->subDays(2),
            'comments' => 'Endorsed at rationalized ₹26,00,000.00. Advanced to Secretary General.',
        ]);
        $itBudget->approvals()->create([
            'level' => 2,
            'role_required' => Roles::SG,
            'status' => Approval::STATUS_PENDING,
        ]);

        // Proposal B: Projects (Pending CFO Endorsement - Level 1)
        $projBudget = AnnualBudget::updateOrCreate(
            ['budget_number' => 'AB-27-28-PROJ-001'],
            [
                'department' => 'Projects',
                'fiscal_year' => 'FY27-28',
                'submitted_by' => $adminId,
                'proposed_amount' => 9800000.00,
                'approved_amount' => null,
                'justification' => 'FY27-28 flagship summits including National Infrastructure Conclave, Global Biofuel Partnership, and G20 Legacy Business Forums.',
                'status' => AnnualBudget::STATUS_PENDING_CFO,
                'current_approval_level' => 1,
                'cfo_approved_at' => null,
                'cfo_remarks' => null,
                'sg_approved_at' => null,
                'sg_remarks' => null,
                'finalized_at' => null,
                'department_budget_id' => null,
            ]
        );
        $projBudget->items()->delete();
        AnnualBudgetItem::create(['annual_budget_id' => $projBudget->id, 'category' => 'Conclaves', 'title' => 'National Infrastructure Summit (Bharat Mandapam)', 'amount' => 4500000.00, 'notes' => '3-day global convention with exhibition']);
        AnnualBudgetItem::create(['annual_budget_id' => $projBudget->id, 'category' => 'Conclaves', 'title' => 'Global Clean Energy & Biofuel Forum', 'amount' => 3000000.00, 'notes' => 'International delegations and diplomatic sessions']);
        AnnualBudgetItem::create(['annual_budget_id' => $projBudget->id, 'category' => 'Regional Summits', 'title' => 'Tier-2 Industrial Hub Series (6 States)', 'amount' => 2300000.00, 'notes' => 'State-level MSME roundtables']);

        $projBudget->approvals()->delete();
        $projBudget->approvals()->create([
            'level' => 1,
            'role_required' => Roles::CFO,
            'status' => Approval::STATUS_PENDING,
        ]);
        $projBudget->approvals()->create([
            'level' => 2,
            'role_required' => Roles::SG,
            'status' => Approval::STATUS_PENDING,
        ]);

        // Proposal C: Marketing (On Hold)
        $mktBudget = AnnualBudget::updateOrCreate(
            ['budget_number' => 'AB-27-28-MKT-001'],
            [
                'department' => 'Marketing',
                'fiscal_year' => 'FY27-28',
                'submitted_by' => $adminId,
                'proposed_amount' => 2100000.00,
                'approved_amount' => null,
                'justification' => 'Expanded international digital branding, podcasts, global economic digests, and television event broadcast sponsorships.',
                'status' => AnnualBudget::STATUS_ON_HOLD,
                'current_approval_level' => 1,
                'cfo_approved_at' => null,
                'cfo_remarks' => null,
                'sg_approved_at' => null,
                'sg_remarks' => null,
                'finalized_at' => null,
                'department_budget_id' => null,
            ]
        );
        $mktBudget->items()->delete();
        AnnualBudgetItem::create(['annual_budget_id' => $mktBudget->id, 'category' => 'Digital Media', 'title' => 'Global Campaign & Digital Content Studio', 'amount' => 1100000.00, 'notes' => 'Video podcasts and thought leadership reels']);
        AnnualBudgetItem::create(['annual_budget_id' => $mktBudget->id, 'category' => 'Broadcast', 'title' => 'Business News Channel Broadcast Partnerships', 'amount' => 1000000.00, 'notes' => 'Live summit streaming and panel coverage']);

        $mktBudget->approvals()->delete();
        $mktBudget->approvals()->create([
            'level' => 1,
            'role_required' => Roles::CFO,
            'approver_id' => $cfoId,
            'status' => Approval::STATUS_ON_HOLD,
            'acted_at' => now()->subDay(),
            'comments' => 'Please provide vendor quotation breakdowns and projected sponsorship revenue offsets for television broadcasts before review.',
        ]);
        $mktBudget->approvals()->create([
            'level' => 2,
            'role_required' => Roles::SG,
            'status' => Approval::STATUS_PENDING,
        ]);

        // Proposal D: Operations (Rejected)
        $opsBudget = AnnualBudget::updateOrCreate(
            ['budget_number' => 'AB-27-28-OPER-001'],
            [
                'department' => 'Operations',
                'fiscal_year' => 'FY27-28',
                'submitted_by' => $hodId,
                'proposed_amount' => 4800000.00,
                'approved_amount' => null,
                'justification' => 'Total building HVAC chiller overhaul, rooftop solar grid installation, and executive cafeteria refurbishment.',
                'status' => AnnualBudget::STATUS_REJECTED,
                'current_approval_level' => 1,
                'cfo_approved_at' => null,
                'cfo_remarks' => null,
                'sg_approved_at' => null,
                'sg_remarks' => null,
                'finalized_at' => null,
                'department_budget_id' => null,
            ]
        );
        $opsBudget->items()->delete();
        AnnualBudgetItem::create(['annual_budget_id' => $opsBudget->id, 'category' => 'Capital Overhaul', 'title' => 'Central Chiller Plant Replacement', 'amount' => 2800000.00, 'notes' => 'Industrial HVAC replacement']);
        AnnualBudgetItem::create(['annual_budget_id' => $opsBudget->id, 'category' => 'Solar Energy', 'title' => '50 kW Rooftop Solar PV Installation', 'amount' => 2000000.00, 'notes' => 'Renewable energy transition']);

        $opsBudget->approvals()->delete();
        $opsBudget->approvals()->create([
            'level' => 1,
            'role_required' => Roles::CFO,
            'approver_id' => $cfoId,
            'status' => Approval::STATUS_REJECTED,
            'acted_at' => now()->subDays(3),
            'comments' => 'Capital overhauls exceeding ₹25 Lakhs cannot be accommodated under general departmental OPEX. Please submit as a specialized Capex project proposal.',
        ]);
        $opsBudget->approvals()->create([
            'level' => 2,
            'role_required' => Roles::SG,
            'status' => Approval::STATUS_PENDING,
        ]);
    }
}
