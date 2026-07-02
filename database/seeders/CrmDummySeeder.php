<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CRM\Lead;
use App\Models\CRM\Opportunity;
use App\Models\CRM\Campaign;
use Carbon\Carbon;

class CrmDummySeeder extends Seeder
{
    public function run()
    {
        // Clear existing to allow fresh seeding
        Opportunity::query()->delete();
        Lead::query()->delete();
        Campaign::query()->delete();

        // 1. Create Campaigns
        $campaigns = [
            ['name' => 'Q2 Growth Burst', 'type' => 'email', 'status' => 'completed', 'budget' => 50000000, 'start' => Carbon::create(2026, 4, 1), 'end' => Carbon::create(2026, 4, 30)],
            ['name' => 'Tech Expo May 2026', 'type' => 'event', 'status' => 'completed', 'budget' => 120000000, 'start' => Carbon::create(2026, 5, 10), 'end' => Carbon::create(2026, 5, 15)],
            ['name' => 'Social Media Outreach', 'type' => 'social', 'status' => 'active', 'budget' => 15000000, 'start' => Carbon::create(2026, 6, 1), 'end' => Carbon::create(2026, 7, 31)],
            ['name' => 'Customer Loyalty Program', 'type' => 'email', 'status' => 'completed', 'budget' => 25000000, 'start' => Carbon::create(2026, 3, 1), 'end' => Carbon::create(2026, 5, 31)],
        ];

        foreach ($campaigns as $camp) {
            Campaign::create([
                'name' => $camp['name'],
                'type' => $camp['type'],
                'status' => $camp['status'],
                'budget' => $camp['budget'],
                'start_date' => $camp['start'],
                'end_date' => $camp['end'],
            ]);
        }

        // 2. Create Leads
        $sources = ['LinkedIn', 'Website', 'Referral', 'Cold Call', 'Exhibition'];
        $statuses = ['new', 'contacted', 'qualified', 'lost'];
        $companies = [
            'Stellar Dynamics', 'Quantum Industries', 'Cybernetic Solutions', 'Aethelred Systems', 
            'Nebula Corp', 'Titan Construction', 'Omega Logistics', 'Vanguard Energy',
            'Blue Horizon Tech', 'Apex Manufacturing', 'Orion Group', 'Zenith Global'
        ];

        for ($i = 0; $i < 60; $i++) {
            $created = Carbon::create(2026, 4, 1)->addDays(rand(0, 90))->setHour(rand(9, 17))->setMinute(rand(0, 59));
            Lead::create([
                'name' => 'Contact ' . fake()->name(),
                'company' => fake()->randomElement($companies) . ' ' . fake()->suffix(),
                'email' => fake()->email(),
                'phone' => fake()->phoneNumber(),
                'status' => fake()->randomElement($statuses),
                'source' => fake()->randomElement($sources),
                'created_at' => $created,
                'updated_at' => $created,
            ]);
        }

        // 3. Create Opportunities (linked to Leads)
        $leads = Lead::where('status', '!=', 'lost')->get();
        $stages = ['prospecting', 'negotiation', 'closed_won', 'closed_lost'];

        foreach ($leads as $lead) {
            // Not all leads become opportunities
            if (rand(0, 100) > 30) { 
                $stage = fake()->randomElement($stages);
                $amount = rand(10, 500) * 1000000; // 10jt to 500jt
                
                // Probability logic based on stage
                $prob = match($stage) {
                    'prospecting' => rand(10, 30),
                    'negotiation' => rand(40, 80),
                    'closed_won' => 100,
                    'closed_lost' => 0,
                };

                $created = Carbon::parse($lead->created_at)->addDays(rand(1, 10));
                $close = $created->copy()->addDays(rand(15, 60));

                Opportunity::create([
                    'name' => 'Deal for ' . $lead->company,
                    'lead_id' => $lead->id,
                    'amount' => $amount,
                    'stage' => $stage,
                    'probability' => $prob,
                    'close_date' => $close,
                    'created_at' => $created,
                    'updated_at' => $created,
                ]);
            }
        }
    }
}
