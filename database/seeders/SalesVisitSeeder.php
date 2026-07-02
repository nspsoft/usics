<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Models\CRM\Lead;
use App\Models\CRM\SalesVisit;
use App\Models\User;
use Carbon\Carbon;

class SalesVisitSeeder extends Seeder
{
    public function run()
    {
        // Pastikan ada Lead dengan menjalankan CrmDummySeeder jika kosong
        if (Lead::count() === 0) {
            $this->call(CrmDummySeeder::class);
        }

        // 1. Assign lat/lng coordinates to existing customers
        $jakartaCustomers = [
            ['lat' => -6.175392, 'lng' => 106.827153, 'city' => 'Jakarta Pusat'], // Monas area
            ['lat' => -6.208763, 'lng' => 106.845599, 'city' => 'Jakarta Selatan'], // Sudirman area
            ['lat' => -6.126442, 'lng' => 106.873214, 'city' => 'Jakarta Utara'], // Kelapa Gading
            ['lat' => -6.167423, 'lng' => 106.763421, 'city' => 'Jakarta Barat'], // Kebon Jeruk
            ['lat' => -6.225014, 'lng' => 106.900412, 'city' => 'Jakarta Timur'], // Duren Sawit
            ['lat' => -6.210124, 'lng' => 106.812351, 'city' => 'Jakarta Selatan'], // Senopati
            ['lat' => -6.195324, 'lng' => 106.795124, 'city' => 'Jakarta Barat'], // Palmerah
            ['lat' => -6.244125, 'lng' => 106.800125, 'city' => 'Jakarta Selatan'], // Blok M
        ];

        $surabayaCustomers = [
            ['lat' => -7.250445, 'lng' => 112.750800, 'city' => 'Surabaya Pusat'],
            ['lat' => -7.285492, 'lng' => 112.695324, 'city' => 'Surabaya Barat'],
            ['lat' => -7.319532, 'lng' => 112.724391, 'city' => 'Surabaya Selatan'],
            ['lat' => -7.265492, 'lng' => 112.784391, 'city' => 'Surabaya Timur'],
        ];

        $allCoords = array_merge($jakartaCustomers, $surabayaCustomers);

        $customers = Customer::all();
        foreach ($customers as $index => $customer) {
            $coord = $allCoords[$index % count($allCoords)];
            $customer->update([
                'latitude' => $coord['lat'] + (rand(-100, 100) / 100000.0), // add slight randomness
                'longitude' => $coord['lng'] + (rand(-100, 100) / 100000.0),
                'city' => $coord['city']
            ]);
        }

        // 2. Assign lat/lng coordinates to some leads
        $leads = Lead::limit(15)->get();
        foreach ($leads as $index => $lead) {
            $coord = $allCoords[($index + 3) % count($allCoords)];
            $lead->update([
                'latitude' => $coord['lat'] + (rand(-150, 150) / 100000.0),
                'longitude' => $coord['lng'] + (rand(-150, 150) / 100000.0),
            ]);
        }

        // 3. Fetch salespersons (or users)
        $salesUsers = User::limit(5)->get();
        if ($salesUsers->isEmpty()) {
            return;
        }

        // 4. Clear existing visits to prevent duplicate seeding issues
        SalesVisit::truncate();

        // 5. Create Mock visits
        $purposes = [
            'Product Presentation & Demo',
            'Contract Negotiation',
            'Follow-up on Proposal',
            'Routine Customer Feedback',
            'Relationship Building Lunch',
            'Technical Assessment & Site Survey',
            'New Feature Pitching'
        ];

        // A. Create Completed Visits (Checked Out in the past)
        for ($i = 0; $i < 12; $i++) {
            $sales = $salesUsers->random();
            $customer = $customers->random();
            $plannedDate = Carbon::create(2026, 4, 1)->addDays(rand(0, 89))->setHour(rand(9, 15))->setMinute(0);
            
            // Check-in location slightly offset from customer location
            $checkInLat = $customer->latitude + (rand(-50, 50) / 100000.0);
            $checkInLng = $customer->longitude + (rand(-50, 50) / 100000.0);

            // Checkout location same or slightly offset
            $checkOutLat = $checkInLat + (rand(-10, 10) / 100000.0);
            $checkOutLng = $checkInLng + (rand(-10, 10) / 100000.0);

            SalesVisit::create([
                'sales_id' => $sales->id,
                'customer_id' => $customer->id,
                'purpose' => $purposes[array_rand($purposes)],
                'notes' => 'Discussing new pricing models and contract renewal details.',
                'status' => 'completed',
                'planned_at' => $plannedDate,
                'check_in_at' => $plannedDate->copy()->addMinutes(rand(5, 20)),
                'check_out_at' => $plannedDate->copy()->addMinutes(rand(60, 120)),
                'check_in_lat' => $checkInLat,
                'check_in_lng' => $checkInLng,
                'check_in_address' => "Jl. Sudirman Kav " . rand(20, 80) . ", " . $customer->city . ", Indonesia",
                'check_out_lat' => $checkOutLat,
                'check_out_lng' => $checkOutLng,
                'check_out_address' => "Jl. Sudirman Kav " . rand(20, 80) . ", " . $customer->city . ", Indonesia",
                'summary' => 'Meeting completed successfully. Client agreed to the new standard contract terms. Proposal to be sent by next Monday.'
            ]);
        }

        // B. Create Active Visits (Currently Checked-In)
        for ($i = 0; $i < 2; $i++) {
            $sales = $salesUsers[$i % $salesUsers->count()];
            $customer = $customers->random();
            $plannedDate = Carbon::now()->subHours(rand(1, 2));

            $checkInLat = $customer->latitude + (rand(-30, 30) / 100000.0);
            $checkInLng = $customer->longitude + (rand(-30, 30) / 100000.0);

            SalesVisit::create([
                'sales_id' => $sales->id,
                'customer_id' => $customer->id,
                'purpose' => 'Site Visit & Technical Evaluation',
                'notes' => 'Need to check their network cabinet and server setup.',
                'status' => 'checked_in',
                'planned_at' => $plannedDate,
                'check_in_at' => $plannedDate->copy()->addMinutes(15),
                'check_in_lat' => $checkInLat,
                'check_in_lng' => $checkInLng,
                'check_in_address' => "Gedung Office Tower " . rand(1, 5) . ", " . $customer->city . ", Indonesia",
            ]);
        }

        // C. Create Planned Visits (Scheduled in the future)
        for ($i = 0; $i < 8; $i++) {
            $sales = $salesUsers->random();
            $client = (rand(0, 1) === 0 || $leads->isEmpty()) ? ['type' => 'customer', 'obj' => $customers->random()] : ['type' => 'lead', 'obj' => $leads->random()];
            $plannedDate = Carbon::now()->addDays(rand(1, 7))->setHour(rand(9, 16))->setMinute(rand(0, 3) * 15);

            SalesVisit::create([
                'sales_id' => $sales->id,
                'customer_id' => $client['type'] === 'customer' ? $client['obj']->id : null,
                'lead_id' => $client['type'] === 'lead' ? $client['obj']->id : null,
                'purpose' => $purposes[array_rand($purposes)],
                'notes' => 'Initial introductory meeting with the procurement manager.',
                'status' => 'planned',
                'planned_at' => $plannedDate,
            ]);
        }
    }
}
