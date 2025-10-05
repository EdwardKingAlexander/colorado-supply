<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Customer;
use App\Models\LostReason;
use App\Models\Opportunity;
use App\Models\Pipeline;
use App\Models\Stage;
use App\Models\User;
use Illuminate\Database\Seeder;

class CRMSeeder extends Seeder
{
    public function run(): void
    {
        // Create Lost Reasons first
        $lostReasons = [
            ['label' => 'Budget', 'description' => 'Customer did not have sufficient budget', 'active' => true],
            ['label' => 'No Decision', 'description' => 'Customer did not make a decision', 'active' => true],
            ['label' => 'Competitor', 'description' => 'Lost to a competitor', 'active' => true],
            ['label' => 'Timing', 'description' => 'Timing was not right for the customer', 'active' => true],
            ['label' => 'No Response', 'description' => 'Customer stopped responding', 'active' => true],
        ];

        foreach ($lostReasons as $reason) {
            LostReason::firstOrCreate(['label' => $reason['label']], $reason);
        }

        // Create default pipeline: "Standard Sales"
        $pipeline = Pipeline::firstOrCreate(
            ['name' => 'Standard Sales'],
            [
                'description' => 'Default sales pipeline for B2B opportunities',
                'is_default' => true,
                'position' => 1,
            ]
        );

        // Create stages in order
        $stages = [
            ['name' => 'Qualification', 'probability_default' => 10, 'forecast_category' => 'Pipeline', 'position' => 1],
            ['name' => 'Discovery', 'probability_default' => 25, 'forecast_category' => 'Pipeline', 'position' => 2],
            ['name' => 'Proposal', 'probability_default' => 50, 'forecast_category' => 'BestCase', 'position' => 3],
            ['name' => 'Negotiation', 'probability_default' => 70, 'forecast_category' => 'Commit', 'position' => 4],
            ['name' => 'Closed Won', 'probability_default' => 100, 'forecast_category' => 'Closed', 'is_won' => true, 'position' => 5],
            ['name' => 'Closed Lost', 'probability_default' => 0, 'forecast_category' => 'Closed', 'is_lost' => true, 'position' => 6],
        ];

        $stageModels = [];
        foreach ($stages as $stageData) {
            $stageModels[$stageData['name']] = Stage::firstOrCreate(
                ['pipeline_id' => $pipeline->id, 'name' => $stageData['name']],
                $stageData
            );
        }

        // Get or create a demo user
        $user = User::first();
        if (!$user) {
            $user = User::create([
                'name' => 'Demo User',
                'email' => 'demo@example.com',
                'password' => bcrypt('password'),
            ]);
        }

        // Create sample customers
        $customers = [
            ['name' => 'Acme Corporation', 'email' => 'contact@acme.com', 'phone' => '555-0100', 'company' => 'Acme Corp', 'owner_id' => $user->id],
            ['name' => 'TechStart Inc', 'email' => 'info@techstart.com', 'phone' => '555-0101', 'company' => 'TechStart', 'owner_id' => $user->id],
            ['name' => 'Global Solutions', 'email' => 'sales@globalsolutions.com', 'phone' => '555-0102', 'company' => 'Global Solutions LLC', 'owner_id' => $user->id],
            ['name' => 'Innovative Systems', 'email' => 'contact@innovativesys.com', 'phone' => '555-0103', 'company' => 'Innovative Systems', 'owner_id' => $user->id],
            ['name' => 'Enterprise Partners', 'email' => 'partners@enterprise.com', 'phone' => '555-0104', 'company' => 'Enterprise Partners', 'owner_id' => $user->id],
        ];

        $customerModels = [];
        foreach ($customers as $customerData) {
            $customerModels[] = Customer::create($customerData);
        }

        // Create sample opportunities across different stages
        $opportunities = [
            [
                'customer' => $customerModels[0],
                'stage' => $stageModels['Qualification'],
                'title' => 'Industrial Supplies - Q1 2025',
                'description' => 'Potential order for industrial supplies and equipment',
                'amount' => 45000,
                'expected_close_date' => now()->addDays(30),
                'source' => 'Website',
                'score' => 3,
            ],
            [
                'customer' => $customerModels[1],
                'stage' => $stageModels['Discovery'],
                'title' => 'Welding Equipment Package',
                'description' => 'Complete welding equipment setup for new facility',
                'amount' => 125000,
                'expected_close_date' => now()->addDays(45),
                'source' => 'Referral',
                'score' => 4,
            ],
            [
                'customer' => $customerModels[2],
                'stage' => $stageModels['Proposal'],
                'title' => 'MRO Supplies Contract',
                'description' => 'Annual MRO supplies contract renewal',
                'amount' => 250000,
                'expected_close_date' => now()->addDays(20),
                'source' => 'Existing Customer',
                'score' => 5,
                'probability_override' => 60, // Override stage default
            ],
            [
                'customer' => $customerModels[3],
                'stage' => $stageModels['Negotiation'],
                'title' => 'CNC Machining Services',
                'description' => 'Custom CNC machining and fabrication services',
                'amount' => 180000,
                'expected_close_date' => now()->addDays(15),
                'source' => 'Trade Show',
                'score' => 5,
            ],
            [
                'customer' => $customerModels[4],
                'stage' => $stageModels['Closed Won'],
                'title' => 'Fasteners Bulk Order',
                'description' => 'Large bulk order of fasteners and hardware',
                'amount' => 95000,
                'expected_close_date' => now()->subDays(5),
                'source' => 'Direct Mail',
                'score' => 5,
                'status' => 'won',
                'closed_at' => now()->subDays(5),
            ],
            [
                'customer' => $customerModels[0],
                'stage' => $stageModels['Closed Lost'],
                'title' => 'Plumbing Supplies',
                'description' => 'Plumbing supplies for construction project',
                'amount' => 75000,
                'expected_close_date' => now()->subDays(10),
                'source' => 'Cold Call',
                'score' => 2,
                'status' => 'lost',
                'lost_reason_id' => LostReason::where('label', 'Competitor')->first()->id,
                'closed_at' => now()->subDays(10),
            ],
        ];

        foreach ($opportunities as $oppData) {
            $opportunity = Opportunity::create([
                'customer_id' => $oppData['customer']->id,
                'pipeline_id' => $pipeline->id,
                'stage_id' => $oppData['stage']->id,
                'title' => $oppData['title'],
                'description' => $oppData['description'],
                'amount' => $oppData['amount'],
                'currency' => 'USD',
                'probability_override' => $oppData['probability_override'] ?? null,
                'expected_close_date' => $oppData['expected_close_date'],
                'status' => $oppData['status'] ?? 'open',
                'owner_id' => $user->id,
                'source' => $oppData['source'],
                'score' => $oppData['score'],
                'lost_reason_id' => $oppData['lost_reason_id'] ?? null,
                'closed_at' => $oppData['closed_at'] ?? null,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            // Add some activities to open opportunities
            if ($opportunity->status === 'open') {
                Activity::create([
                    'opportunity_id' => $opportunity->id,
                    'type' => 'call',
                    'subject' => 'Initial discovery call',
                    'body' => 'Discussed requirements and timeline',
                    'owner_id' => $user->id,
                    'done_at' => now()->subDays(rand(1, 7)),
                ]);

                Activity::create([
                    'opportunity_id' => $opportunity->id,
                    'type' => 'task',
                    'subject' => 'Follow up on proposal',
                    'body' => 'Send updated proposal with pricing',
                    'owner_id' => $user->id,
                    'due_at' => now()->addDays(rand(1, 5)),
                ]);
            }
        }

        $this->command->info('CRM data seeded successfully!');
        $this->command->info("Pipeline: {$pipeline->name} with " . count($stages) . ' stages');
        $this->command->info('Created ' . count($customers) . ' customers');
        $this->command->info('Created ' . count($opportunities) . ' opportunities');
        $this->command->info('Created ' . count($lostReasons) . ' lost reasons');
    }
}
