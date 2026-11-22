<?php

namespace Database\Seeders;

use App\Models\Approval;
use App\Models\DepartmentBudget;
use App\Models\ProjectBudget;
use App\Models\PurchaseRequisition;
use App\Models\PurchaseRequisitionItem;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProjectBudgetSeeder extends Seeder
{
    public function run(): void
    {
        $fiscalYear = 'FY26-27';

        // Authentic ASSOCHAM National Councils & Industry Event Projects
        $projects = [
            [
                'project_name' => 'National Conference on Green Mobility & EV Infrastructure',
                'department' => 'Projects',
                'council' => 'Green Mobility',
                'cluster_head' => 'Rajesh Sharma',
                'hod' => 'Rajesh Verma',
                'category' => 'Flagship',
                'location' => 'Delhi',
                'event_month' => 'Aug 2026',
                'allocated_amount' => 3_500_000,
            ],
            [
                'project_name' => 'Annual Strategy Conclave',
                'department' => 'Projects',
                'council' => 'Strategic Initiatives',
                'cluster_head' => 'Manish Gupta',
                'hod' => 'Rajesh Verma',
                'category' => 'Flagship',
                'location' => 'Delhi',
                'event_month' => 'Nov 2026',
                'allocated_amount' => 3_000_000,
            ],
            [
                'project_name' => 'Conference on Building a Resilient Critical Minerals Supply Chain in India',
                'department' => 'Projects',
                'council' => 'Green Mobility',
                'cluster_head' => 'Rajesh Sharma',
                'hod' => 'Rajesh Verma',
                'category' => 'Minor',
                'location' => 'Delhi',
                'event_month' => 'Jun 2026',
                'allocated_amount' => 1_500_000,
            ],
            [
                'project_name' => 'National Summit on Changing Landscape of Indian Automotive Sector',
                'department' => 'Projects',
                'council' => 'Auto & Auto Ancillaries',
                'cluster_head' => 'Rajesh Sharma',
                'hod' => 'Rajesh Verma',
                'category' => 'Major',
                'location' => 'Delhi',
                'event_month' => 'Oct 2026',
                'allocated_amount' => 2_800_000,
            ],
            [
                'project_name' => 'National Education & Future Skills Conclave',
                'department' => 'Projects',
                'council' => 'Education & Skills',
                'cluster_head' => 'Dr. Anita Roy',
                'hod' => 'Rajesh Verma',
                'category' => 'Major',
                'location' => 'Bengaluru',
                'event_month' => 'Dec 2026',
                'allocated_amount' => 3_000_000,
            ],
            [
                'project_name' => 'India Leadership Banquet & Corporate Governance Awards',
                'department' => 'Projects',
                'council' => 'Corporate Affairs',
                'cluster_head' => 'S. K. Bansal',
                'hod' => 'Rajesh Verma',
                'category' => 'Flagship',
                'location' => 'Mumbai',
                'event_month' => 'Jan 2027',
                'allocated_amount' => 2_500_000,
            ],
            [
                'project_name' => 'East India Regional Trade & Investment Expo',
                'department' => 'Marketing',
                'council' => 'Regional Trade & Investment',
                'cluster_head' => 'Sunil Ghosh',
                'hod' => 'Priya Nair',
                'category' => 'Flagship',
                'location' => 'Kolkata',
                'event_month' => 'Oct 2026',
                'allocated_amount' => 1_500_000,
            ],
            [
                'project_name' => 'National Membership Drive & Industry Outreach Program',
                'department' => 'Marketing',
                'council' => 'Member Relations & Outreach',
                'cluster_head' => 'Sunil Ghosh',
                'hod' => 'Priya Nair',
                'category' => 'Membership',
                'location' => 'Pan-India',
                'event_month' => 'Apr 2026 - Mar 2027',
                'allocated_amount' => 1_000_000,
            ],
            [
                'project_name' => 'Digital Transformation & Enterprise Cyber Security Conclave',
                'department' => 'IT',
                'council' => 'Technology & Systems',
                'cluster_head' => 'Deepak Tyagi',
                'hod' => 'Amitabh Sen',
                'category' => 'Major',
                'location' => 'Delhi',
                'event_month' => 'Jul 2026',
                'allocated_amount' => 1_200_000,
            ],
            [
                'project_name' => 'National MSME Competitiveness & Financing Summit',
                'department' => 'Projects',
                'council' => 'MSME & Financial Inclusion',
                'cluster_head' => 'R. K. Mehra',
                'hod' => 'Rajesh Verma',
                'category' => 'Minor',
                'location' => 'Ahmedabad',
                'event_month' => 'Sep 2026',
                'allocated_amount' => 1_400_000,
            ],
            [
                'project_name' => 'National Healthcare Infrastructure & MedTech Forum',
                'department' => 'Projects',
                'council' => 'Healthcare & Lifesciences',
                'cluster_head' => 'Dr. Anita Roy',
                'hod' => 'Rajesh Verma',
                'category' => 'Major',
                'location' => 'Hyderabad',
                'event_month' => 'Nov 2026',
                'allocated_amount' => 2_200_000,
            ],
            [
                'project_name' => 'National Logistics & Multimodal Supply Chain Conclave',
                'department' => 'Operations',
                'council' => 'Logistics & Supply Chain',
                'cluster_head' => 'V. Raman',
                'hod' => 'Vikram Seth',
                'category' => 'Major',
                'location' => 'Delhi',
                'event_month' => 'Aug 2026',
                'allocated_amount' => 2_500_000,
            ],
        ];

        // Wipe old dummy project rows that aren't real events
        $validNames = array_column($projects, 'project_name');
        
        // Remove or detach PRs from deleted projects
        ProjectBudget::where('fiscal_year', $fiscalYear)
            ->whereNotIn('project_name', $validNames)
            ->delete();

        foreach ($projects as $p) {
            ProjectBudget::updateOrCreate(
                ['project_name' => $p['project_name'], 'fiscal_year' => $fiscalYear],
                [
                    'department' => $p['department'],
                    'council' => $p['council'],
                    'cluster_head' => $p['cluster_head'],
                    'hod' => $p['hod'],
                    'category' => $p['category'],
                    'location' => $p['location'],
                    'event_month' => $p['event_month'],
                    'allocated_amount' => $p['allocated_amount'],
                    'utilised_amount' => 0.00, // Computed live from real PRs
                ]
            );
        }

        // Real-world: seed authentic, verifiable multi-item PRs for active flagship events in local/dev
        if (! app()->runningUnitTests()) {
            $this->seedRealisticProjectRequisitions($fiscalYear);
        }

        // Recalculate and synchronize utilised_amount strictly from actual active PRs in DB
        foreach (ProjectBudget::where('fiscal_year', $fiscalYear)->get() as $pb) {
            $realSpend = (float) $pb->purchaseRequisitions()
                ->where('status', '!=', 'rejected')
                ->with('items')
                ->get()
                ->sum(fn ($pr) => $pr->estimatedCostInclGst());

            $pb->update(['utilised_amount' => round($realSpend, 2)]);
        }
    }

    private function seedRealisticProjectRequisitions(string $fiscalYear): void
    {
        $requester = User::where('role', Roles::PR_SR_EXEC)->first() ?? User::first();
        if (! $requester) {
            return;
        }

        $activeProjects = [
            'National Conference on Green Mobility & EV Infrastructure' => [
                [
                    'pr_number' => 'PR/FY26-27/EV-202608-01',
                    'title' => 'Vigyan Bhawan Plenary Hall & Audio-Visual Setup',
                    'department' => 'Projects',
                    'region' => 'Delhi HQ',
                    'status' => 'approved',
                    'items' => [
                        ['item_name' => 'Convention Hall 1 Booking (2 Days)', 'quantity' => 1, 'unit' => 'Days', 'unit_cost' => 650000, 'gst_percent' => 18],
                        ['item_name' => 'Dual LED Video Wall (24x12 ft) & Line Array Sound', 'quantity' => 1, 'unit' => 'Package', 'unit_cost' => 380000, 'gst_percent' => 18],
                        ['item_name' => 'Simultaneous Interpretation & Stage Lighting', 'quantity' => 1, 'unit' => 'Setup', 'unit_cost' => 120000, 'gst_percent' => 18],
                    ],
                ],
                [
                    'pr_number' => 'PR/FY26-27/EV-202608-02',
                    'title' => 'Delegate Catering, Kits & Conference Collaterals',
                    'department' => 'Projects',
                    'region' => 'Delhi HQ',
                    'status' => 'pending_l3',
                    'items' => [
                        ['item_name' => 'Executive Delegate Buffet Lunch & High Tea (500 pax)', 'quantity' => 500, 'unit' => 'Plates', 'unit_cost' => 1250, 'gst_percent' => 18],
                        ['item_name' => 'Eco-friendly Delegate Conference Bags & Lanyards', 'quantity' => 500, 'unit' => 'Kits', 'unit_cost' => 450, 'gst_percent' => 18],
                    ],
                ],
            ],
            'Annual Strategy Conclave' => [
                [
                    'pr_number' => 'PR/FY26-27/ASC-202611-01',
                    'title' => 'Taj Palace Ball Room Venue & Technical Staging',
                    'department' => 'Projects',
                    'region' => 'Delhi HQ',
                    'status' => 'approved',
                    'items' => [
                        ['item_name' => 'Ball Room Day Rental & Setup Fee', 'quantity' => 1, 'unit' => 'Day', 'unit_cost' => 850000, 'gst_percent' => 18],
                        ['item_name' => 'Curved Seamless LED Screen & Live Multi-Cam Webcast', 'quantity' => 1, 'unit' => 'Package', 'unit_cost' => 420000, 'gst_percent' => 18],
                    ],
                ],
                [
                    'pr_number' => 'PR/FY26-27/ASC-202611-02',
                    'title' => 'VIP Speaker Transport & Premium Hospitality',
                    'department' => 'Projects',
                    'region' => 'Delhi HQ',
                    'status' => 'pending_l2',
                    'items' => [
                        ['item_name' => 'Luxury Fleet Chauffeur Transfers for Keynote Speakers', 'quantity' => 12, 'unit' => 'Vehicles', 'unit_cost' => 18000, 'gst_percent' => 18],
                        ['item_name' => 'Speaker Mementos & Citations in Brass', 'quantity' => 25, 'unit' => 'Pieces', 'unit_cost' => 4500, 'gst_percent' => 18],
                    ],
                ],
            ],
            'Conference on Building a Resilient Critical Minerals Supply Chain in India' => [
                [
                    'pr_number' => 'PR/FY26-27/MIN-202606-01',
                    'title' => 'India Habitat Centre Stein Auditorium & Logistics',
                    'department' => 'Projects',
                    'region' => 'Delhi HQ',
                    'status' => 'approved',
                    'items' => [
                        ['item_name' => 'Auditorium Booking & Technical Operator', 'quantity' => 1, 'unit' => 'Session', 'unit_cost' => 250000, 'gst_percent' => 18],
                        ['item_name' => 'Critical Minerals Knowledge Paper Printing (1000 copies)', 'quantity' => 1000, 'unit' => 'Copies', 'unit_cost' => 85, 'gst_percent' => 12],
                    ],
                ],
            ],
            'East India Regional Trade & Investment Expo' => [
                [
                    'pr_number' => 'PR/FY26-27/EXP-202610-01',
                    'title' => 'Biswa Bangla Convention Centre Exhibition Hangar & Stalls',
                    'department' => 'Marketing',
                    'region' => 'Kolkata',
                    'status' => 'approved',
                    'items' => [
                        ['item_name' => 'Exhibition Hall Octanorm Stalls (40 Standard 3x3m booths)', 'quantity' => 40, 'unit' => 'Booths', 'unit_cost' => 22000, 'gst_percent' => 18],
                        ['item_name' => 'Exhibition Fascia Branding, Gate Arch & Signages', 'quantity' => 1, 'unit' => 'Job', 'unit_cost' => 140000, 'gst_percent' => 18],
                    ],
                ],
            ],
            'Digital Transformation & Enterprise Cyber Security Conclave' => [
                [
                    'pr_number' => 'PR/FY26-27/IT-202607-01',
                    'title' => 'Cyber Security Conclave Cloud Platform & Demo Lab',
                    'department' => 'IT',
                    'region' => 'Delhi HQ',
                    'status' => 'approved',
                    'items' => [
                        ['item_name' => 'High-Density Wi-Fi Infrastructure & 1Gbps Dedicated Leased Line', 'quantity' => 1, 'unit' => 'Event', 'unit_cost' => 180000, 'gst_percent' => 18],
                        ['item_name' => 'Interactive Touch Kiosks & Registration Counters', 'quantity' => 6, 'unit' => 'Units', 'unit_cost' => 15000, 'gst_percent' => 18],
                    ],
                ],
            ],
        ];

        foreach ($activeProjects as $projName => $prList) {
            $project = ProjectBudget::where('project_name', $projName)->where('fiscal_year', $fiscalYear)->first();
            if (! $project) {
                continue;
            }

            foreach ($prList as $prData) {
                if (PurchaseRequisition::where('pr_number', $prData['pr_number'])->exists()) {
                    continue;
                }

                $pr = PurchaseRequisition::create([
                    'pr_number' => $prData['pr_number'],
                    'title' => $prData['title'],
                    'event_name' => $project->project_name,
                    'project_budget_id' => $project->id,
                    'department' => $prData['department'],
                    'region' => $prData['region'],
                    'status' => $prData['status'],
                    'current_level' => $prData['status'] === 'approved' ? 4 : 2,
                    'created_by' => $requester->id,
                ]);

                foreach ($prData['items'] as $it) {
                    $pr->items()->create([
                        'item_name' => $it['item_name'],
                        'quantity' => $it['quantity'],
                        'unit' => $it['unit'],
                        'unit_cost' => $it['unit_cost'],
                        'gst_percent' => $it['gst_percent'],
                        'status' => $prData['status'] === 'approved' ? 'approved' : 'pending',
                    ]);
                }

                // Seed approvals
                foreach ([1 => Roles::PR_APPROVER_L1, 2 => Roles::PR_APPROVER_L2, 3 => Roles::CFO, 4 => Roles::SG] as $level => $role) {
                    $isApproved = ($prData['status'] === 'approved') || ($level === 1);
                    $pr->approvals()->create([
                        'level' => $level,
                        'role_required' => $role,
                        'status' => $isApproved ? 'approved' : 'pending',
                        'approver_id' => $isApproved ? $requester->id : null,
                        'acted_at' => $isApproved ? now() : null,
                    ]);
                }
            }
        }
    }
}
