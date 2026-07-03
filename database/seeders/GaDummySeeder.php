<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use App\Models\GaLocation;
use App\Models\GaAsset;
use App\Models\GaAssetLog;
use App\Models\GaPmSchedule;
use App\Models\GaPmLog;
use App\Models\GaTicket;
use App\Models\GaTicketLog;
use App\Models\Vehicle;
use App\Models\GaVehicleBooking;
use App\Models\GaVehicleTrip;
use App\Models\User;
use Carbon\Carbon;

class GaDummySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key constraints to safely clear tables
        Schema::disableForeignKeyConstraints();
        GaVehicleTrip::query()->delete();
        GaVehicleBooking::query()->delete();
        GaTicketLog::query()->delete();
        GaTicket::query()->delete();
        GaPmLog::query()->delete();
        GaPmSchedule::query()->delete();
        GaAssetLog::query()->delete();
        GaAsset::query()->delete();
        GaLocation::query()->delete();
        Schema::enableForeignKeyConstraints();

        // Get users for reporter and assignee seeding
        $user1 = User::first();
        $user2 = User::skip(1)->first() ?? $user1;

        if (!$user1) {
            // Fallback in case no users exist
            $user1 = User::create([
                'name' => 'Staf GA Demo',
                'email' => 'ga.demo@example.com',
                'password' => bcrypt('password'),
            ]);
            $user2 = $user1;
        }

        // 1. Create Locations
        $loc1 = GaLocation::create([
            'name' => 'Gedung Kantor Utama',
            'description' => 'Gedung kantor pusat Lantai 1-3, termasuk ruang kerja karyawan dan resepsionis.',
        ]);

        $loc2 = GaLocation::create([
            'name' => 'Pabrik Produksi A',
            'description' => 'Area produksi utama, workshop perakitan, dan area pemuatan barang.',
        ]);

        $loc3 = GaLocation::create([
            'name' => 'Ruang Server & IT Center',
            'description' => 'Ruangan khusus ber-AC dingin untuk server utama, switchboard, dan penyimpanan UPS.',
        ]);

        // 2. Create Assets
        // Office AC (Daikin)
        $ac = GaAsset::create([
            'asset_code' => 'AST-AC-001',
            'name' => 'Air Conditioner Daikin 2 PK',
            'category' => 'Elektronik',
            'purchase_date' => Carbon::now()->subYears(2)->toDateString(),
            'price' => 8500000,
            'condition' => 'Baik',
            'location' => 'Lantai 1 - Ruang Pertemuan Utama',
            'ga_location_id' => $loc1->id,
            'status' => 'active',
        ]);
        $ac->logs()->create([
            'action' => 'created',
            'notes' => 'Aset didaftarkan melalui dummy seeder.',
            'user_id' => $user1->id,
        ]);

        // Generator (Hartech)
        $genset = GaAsset::create([
            'asset_code' => 'AST-GEN-001',
            'name' => 'Genset Silent Hartech 100 kVA',
            'category' => 'Lainnya',
            'purchase_date' => Carbon::now()->subYears(3)->toDateString(),
            'price' => 125000000,
            'condition' => 'Baik',
            'location' => 'Area Parkir Belakang',
            'ga_location_id' => $loc1->id,
            'status' => 'active',
        ]);
        $genset->logs()->create([
            'action' => 'created',
            'notes' => 'Aset didaftarkan melalui dummy seeder.',
            'user_id' => $user1->id,
        ]);

        // Forklift (Toyota)
        $forklift = GaAsset::create([
            'asset_code' => 'AST-FL-001',
            'name' => 'Forklift Toyota 3 Ton (Diesel)',
            'category' => 'Kendaraan',
            'purchase_date' => Carbon::now()->subMonths(18)->toDateString(),
            'price' => 280000000,
            'condition' => 'Baik',
            'location' => 'Loading Dock Barat',
            'ga_location_id' => $loc2->id,
            'status' => 'active',
        ]);
        $forklift->logs()->create([
            'action' => 'created',
            'notes' => 'Aset didaftarkan melalui dummy seeder.',
            'user_id' => $user1->id,
        ]);

        // UPS server (APC)
        $ups = GaAsset::create([
            'asset_code' => 'AST-UPS-001',
            'name' => 'APC Smart-UPS 3000VA LCD',
            'category' => 'Elektronik',
            'purchase_date' => Carbon::now()->subMonths(8)->toDateString(),
            'price' => 14800000,
            'condition' => 'Baik',
            'location' => 'Rack Server A-4',
            'ga_location_id' => $loc3->id,
            'status' => 'active',
        ]);
        $ups->logs()->create([
            'action' => 'created',
            'notes' => 'Aset didaftarkan melalui dummy seeder.',
            'user_id' => $user1->id,
        ]);

        // 3. Create PM Schedules
        // AC - Cuci AC berkala (90 days interval)
        GaPmSchedule::create([
            'ga_asset_id' => $ac->id,
            'task_name' => 'Cuci AC Berkala',
            'description' => 'Melakukan pembersihan filter udara, pencucian indoor evaporator, outdoor condenser, serta cek tekanan freon.',
            'interval_days' => 90,
            'next_due_date' => Carbon::now()->addDays(45)->toDateString(),
            'status' => 'active',
            'assignee_id' => $user2->id,
        ]);

        // Genset - Pemanasan & Cek Oli (7 days interval)
        GaPmSchedule::create([
            'ga_asset_id' => $genset->id,
            'task_name' => 'Pemanasan & Cek Aki Genset',
            'description' => 'Nyalakan genset tanpa beban selama 15 menit, cek volume air aki, kekencangan belt, dan ketinggian oli mesin.',
            'interval_days' => 7,
            'next_due_date' => Carbon::now()->addDays(3)->toDateString(),
            'status' => 'active',
            'assignee_id' => $user2->id,
        ]);

        // Forklift - Servis Hidrolik & Ganti Oli (180 days interval)
        GaPmSchedule::create([
            'ga_asset_id' => $forklift->id,
            'task_name' => 'Servis Mesin & Hidrolik Forklift',
            'description' => 'Mengganti oli mesin diesel, filter oli, cek kebocoran oli hidrolik, dan pelumasan rantai angkat forklift.',
            'interval_days' => 180,
            'next_due_date' => Carbon::now()->addDays(120)->toDateString(),
            'status' => 'active',
            'assignee_id' => $user2->id,
        ]);

        // UPS - Discharge Battery Test (30 days interval - Set to OVERDUE)
        GaPmSchedule::create([
            'ga_asset_id' => $ups->id,
            'task_name' => 'Uji Beban & Kapasitas Baterai UPS',
            'description' => 'Lakukan simulasi pemadaman listrik (load test discharge) selama 5 menit untuk memverifikasi kesehatan sel baterai UPS server.',
            'interval_days' => 30,
            'next_due_date' => Carbon::now()->subDays(5)->toDateString(), // 5 days overdue!
            'status' => 'active',
            'assignee_id' => $user2->id,
        ]);

        // 4. Create Service Tickets
        // Ticket 1: Open
        $tkt1 = GaTicket::create([
            'ticket_code' => 'TKT-' . Carbon::now()->format('Ymd') . '-0001',
            'title' => 'Pintu Kaca Utama Tidak Bisa Mengunci',
            'description' => 'Pintu masuk utama lantai 1 gedung depan pengait kuncinya longgar, sehingga tidak bisa dikunci rapat dari luar saat malam hari.',
            'category' => 'facility',
            'priority' => 'high',
            'status' => 'open',
            'ga_location_id' => $loc1->id,
            'reporter_id' => $user1->id,
        ]);
        $tkt1->logs()->create([
            'action' => 'created',
            'notes' => 'Ticket created by ' . $user1->name,
            'user_id' => $user1->id,
        ]);

        // Ticket 2: In Progress
        $tkt2 = GaTicket::create([
            'ticket_code' => 'TKT-' . Carbon::now()->format('Ymd') . '-0002',
            'title' => 'Lampu Pabrik Area Barat Padam',
            'description' => '3 buah lampu gantung LED High Bay di area loading dock padam total. Sangat membahayakan aktivitas operasional forklift pada shift malam.',
            'category' => 'facility',
            'priority' => 'critical',
            'status' => 'in_progress',
            'ga_location_id' => $loc2->id,
            'reporter_id' => $user1->id,
            'assignee_id' => $user2->id,
        ]);
        $tkt2->logs()->create([
            'action' => 'created',
            'notes' => 'Ticket created by ' . $user1->name,
            'user_id' => $user1->id,
        ]);
        $tkt2->logs()->create([
            'action' => 'assigned',
            'notes' => 'Ticket assigned to ' . $user2->name,
            'user_id' => $user1->id,
        ]);
        $tkt2->logs()->create([
            'action' => 'status_changed',
            'notes' => 'Status changed from OPEN to IN_PROGRESS.',
            'user_id' => $user2->id,
        ]);

        // Ticket 3: Resolved (AC Leak)
        $tkt3 = GaTicket::create([
            'ticket_code' => 'TKT-' . Carbon::now()->subDays(2)->format('Ymd') . '-0003',
            'title' => 'AC Ruang Meeting Daikin Menetes Air',
            'description' => 'Unit AC Daikin di Ruang Pertemuan Utama meneteskan air sangat deras dari blower bawah, membasahi dokumen rapat dan karpet ruangan.',
            'category' => 'facility',
            'priority' => 'medium',
            'status' => 'resolved',
            'ga_location_id' => $loc1->id,
            'ga_asset_id' => $ac->id,
            'pos_x' => 45.5, // simulate position
            'pos_y' => 32.2,
            'reporter_id' => $user1->id,
            'assignee_id' => $user2->id,
            'resolved_at' => Carbon::now()->subDay(),
            'resolution_notes' => 'Sudah dilakukan pembersihan drainase indoor AC yang tersumbat debu luar. Unit AC sudah ditest menyala 2 jam dan tidak ada bocor air lagi.',
        ]);
        $tkt3->logs()->create([
            'action' => 'created',
            'notes' => 'Ticket created by ' . $user1->name,
            'user_id' => $user1->id,
        ]);
        $tkt3->logs()->create([
            'action' => 'assigned',
            'notes' => 'Ticket assigned to ' . $user2->name,
            'user_id' => $user1->id,
        ]);
        $tkt3->logs()->create([
            'action' => 'status_changed',
            'notes' => 'Status changed from OPEN to IN_PROGRESS.',
            'user_id' => $user2->id,
        ]);
        $tkt3->logs()->create([
            'action' => 'status_changed',
            'notes' => 'Status changed from IN_PROGRESS to RESOLVED. Notes: Sudah dilakukan pembersihan drainase indoor AC...',
            'user_id' => $user2->id,
        ]);

        // 5. Seed Vehicles Catalog (Using firstOrCreate to prevent breaking logistics flow)
        $v1 = Vehicle::firstOrCreate(
            ['license_plate' => 'B 1234 ABC'],
            [
                'brand' => 'Toyota',
                'model' => 'Avanza 1.3G',
                'vehicle_type' => 'Mobil Penumpang',
                'driver_name' => 'Budi Utomo',
                'status' => 'available',
                'is_active' => true,
                'notes' => 'Kendaraan operasional staf & manajemen.',
                'usage_type' => 'passenger',
            ]
        );
        // Ensure status is reset for seeder demo
        $v1->update(['status' => 'available', 'usage_type' => 'passenger']);

        $v2 = Vehicle::firstOrCreate(
            ['license_plate' => 'B 5678 XYZ'],
            [
                'brand' => 'Mitsubishi',
                'model' => 'Triton Double Cabin',
                'vehicle_type' => 'Mobil Pick-up/Logistik',
                'driver_name' => 'Ahmad Fauzi',
                'status' => 'available',
                'is_active' => true,
                'notes' => 'Kendaraan logistik & kunjungan pabrik.',
                'usage_type' => 'passenger',
            ]
        );
        $v2->update(['status' => 'in_use', 'usage_type' => 'passenger']); // Seed one as in_use

        $v3 = Vehicle::firstOrCreate(
            ['license_plate' => 'B 2468 JKL'],
            [
                'brand' => 'Toyota',
                'model' => 'Innova Reborn',
                'vehicle_type' => 'Mobil Penumpang',
                'driver_name' => 'Dedi Kurniawan',
                'status' => 'available',
                'is_active' => true,
                'notes' => 'Kendaraan dinas direksi.',
                'usage_type' => 'passenger',
            ]
        );
        $v3->update(['status' => 'available', 'usage_type' => 'passenger']);

        $v4 = Vehicle::firstOrCreate(
            ['license_plate' => 'B 4421 FBA'],
            [
                'brand' => 'Honda',
                'model' => 'Vario 150',
                'vehicle_type' => 'Sepeda Motor',
                'driver_name' => 'Slamet Riyadi',
                'status' => 'available',
                'is_active' => true,
                'notes' => 'Sepeda motor operasional kurir GA.',
                'usage_type' => 'passenger',
            ]
        );
        $v4->update(['status' => 'available', 'usage_type' => 'passenger']);

        // 6. Create Vehicle Bookings & Trip Logs
        // Booking 1: Pending Request
        GaVehicleBooking::create([
            'user_id' => $user1->id,
            'purpose' => 'Kunjungan audit lapangan dan meeting dengan manajemen PT Harapan Jaya.',
            'destination' => 'PT Harapan Jaya, Karawang',
            'start_time' => Carbon::now()->addDay()->setHour(9)->setMinute(0),
            'end_time' => Carbon::now()->addDay()->setHour(17)->setMinute(0),
            'passengers_count' => 3,
            'status' => 'pending',
        ]);

        // Booking 2: Active Trip
        $book2 = GaVehicleBooking::create([
            'user_id' => $user1->id,
            'vehicle_id' => $v2->id,
            'purpose' => 'Mengirim sparepart mesin cnc urgent ke subkontraktor di Tangerang.',
            'destination' => 'CV Logam Presisi, Tangerang',
            'start_time' => Carbon::now()->subHours(2),
            'end_time' => Carbon::now()->addHours(3),
            'passengers_count' => 1,
            'status' => 'active',
            'driver_name' => 'Ahmad Fauzi',
            'approval_notes' => 'Tolong hati-hati membawa sparepart mesin berat.',
        ]);
        GaVehicleTrip::create([
            'ga_vehicle_booking_id' => $book2->id,
            'vehicle_id' => $v2->id,
            'odometer_start' => 45120,
        ]);

        // Booking 3: Completed Trip with Operational Cost Log
        $book3 = GaVehicleBooking::create([
            'user_id' => $user1->id,
            'vehicle_id' => $v1->id,
            'purpose' => 'Membeli ATK bulanan kantor dan kebutuhan pantry katering di Lotte Grosir.',
            'destination' => 'Lotte Grosir Bekasi',
            'start_time' => Carbon::now()->subDays(2)->setHour(10)->setMinute(0),
            'end_time' => Carbon::now()->subDays(2)->setHour(14)->setMinute(0),
            'passengers_count' => 2,
            'status' => 'completed',
            'driver_name' => 'Budi Utomo',
            'approval_notes' => 'Gunakan e-toll kantor yang berada di laci dashboard.',
        ]);
        GaVehicleTrip::create([
            'ga_vehicle_booking_id' => $book3->id,
            'vehicle_id' => $v1->id,
            'odometer_start' => 12050,
            'odometer_end' => 12085,
            'fuel_liters' => 5.5,
            'fuel_cost' => 68000,
            'toll_cost' => 24000,
            'notes' => 'Pembelian ATK selesai. e-toll kantor sisa saldo Rp 120.000. Struk parkir dilampirkan.',
        ]);

        // Booking 4: New Pending Request for Honda Vario
        GaVehicleBooking::create([
            'user_id' => $user1->id,
            'purpose' => 'Mengirim berkas pajak daerah ke Kantor Dispenda Kota.',
            'destination' => 'Kantor Dispenda Kota, Bekasi',
            'start_time' => Carbon::now()->addDays(2)->setHour(10)->setMinute(0),
            'end_time' => Carbon::now()->addDays(2)->setHour(13)->setMinute(0),
            'passengers_count' => 1,
            'status' => 'pending',
        ]);

        // Booking 5: Approved Request for Toyota Innova
        GaVehicleBooking::create([
            'user_id' => $user1->id,
            'vehicle_id' => $v3->id,
            'purpose' => 'Menjemput kedatangan tamu auditor eksternal dari Bandara Soekarno-Hatta.',
            'destination' => 'Bandara Soekarno-Hatta (Terminal 3)',
            'start_time' => Carbon::now()->addDays(1)->setHour(13)->setMinute(0),
            'end_time' => Carbon::now()->addDays(1)->setHour(18)->setMinute(0),
            'passengers_count' => 4,
            'status' => 'approved',
            'driver_name' => 'Dedi Kurniawan',
            'approval_notes' => 'Tolong pastikan AC mobil dingin dan kabin bersih sebelum berangkat.',
        ]);

        // Booking 6: Rejected Request for Toyota Avanza
        GaVehicleBooking::create([
            'user_id' => $user1->id,
            'purpose' => 'Kunjungan pribadi ke rumah kerabat keluarga.',
            'destination' => 'Bandung',
            'start_time' => Carbon::now()->addDays(3)->setHour(8)->setMinute(0),
            'end_time' => Carbon::now()->addDays(3)->setHour(20)->setMinute(0),
            'passengers_count' => 5,
            'status' => 'rejected',
            'approval_notes' => 'Peminjaman ditolak karena tujuan bukan untuk keperluan dinas perusahaan.',
        ]);
    }
}
