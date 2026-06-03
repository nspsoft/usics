<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Meeting;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;

class MeetingDummySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user1 = User::first();
        $user2 = User::skip(1)->first() ?? $user1;
        $user3 = User::skip(2)->first() ?? $user2;
        $user4 = User::skip(3)->first() ?? $user1;

        if (!$user1) {
            $this->command->warn("No users found in database to link chairperson/secretary!");
            return;
        }

        // Helper to ensure employees exist for users so notifications can be simulated
        $ensureEmployee = function($user, $phoneSuffix) {
            $employee = $user->employee;
            if (!$employee) {
                $deptId = \App\Models\Department::first()?->id ?? 1;
                $posId = \App\Models\Position::first()?->id ?? 1;
                \App\Models\Employee::create([
                    'user_id' => $user->id,
                    'nik' => 'EMP-' . str_pad($user->id, 5, '0', STR_PAD_LEFT),
                    'full_name' => $user->name,
                    'email' => $user->email,
                    'phone' => '628123456789' . $phoneSuffix,
                    'address' => 'Jl. Jidoka Result Indonesia No. 123',
                    'joining_date' => now()->toDateString(),
                    'employment_status' => 'permanent',
                    'department_id' => $deptId,
                    'position_id' => $posId,
                    'is_active' => true,
                ]);
            }
        };

        $ensureEmployee($user1, '1');
        $ensureEmployee($user2, '2');
        $ensureEmployee($user3, '3');
        $ensureEmployee($user4, '4');

        DB::transaction(function () use ($user1, $user2, $user3, $user4) {
            // Clean existing meeting records
            Meeting::query()->delete();

            // 1. ERP review meeting
            $m1 = Meeting::create([
                'company_id' => 1,
                'title' => 'Review Implementasi ERP Logistik & Uang Jalan',
                'meeting_date' => '2026-06-02',
                'start_time' => '10:00:00',
                'end_time' => '11:30:00',
                'location' => 'Ruang Rapat Utama Lantai 2',
                'type' => 'internal',
                'chairperson_id' => $user1->id,
                'secretary_id' => $user2->id,
                'discussion_notes' => "Agenda 1: Evaluasi shipment number pada DO.\nSopir sudah dapat melihat shipment number pada halaman dashboard aktif secara visual.\n\nAgenda 2: Input uang jalan oleh Finance.\nKeperluan kartu e-toll sudah berjalan lancar dan data tersimpan di DO utama.\n\nAgenda 3: Uji coba scanner di lokasi tujuan.\nSopir berhasil mengupload foto struk pendaftaran pengeluaran di DO terakhir.",
                'status' => 'published',
                'created_by' => $user1->id,
            ]);

            $m1->attendees()->create(['user_id' => $user1->id, 'status' => 'present']);
            $m1->attendees()->create(['user_id' => $user2->id, 'status' => 'present']);
            $m1->attendees()->create(['guest_name' => 'Agus Santoso (Konsultan IT)', 'status' => 'present']);

            $m1->actionItems()->create([
                'description' => 'Pemeriksaan saldo e-toll card sebelum keberangkatan DO.',
                'pic_id' => $user2->id,
                'due_date' => '2026-06-08',
                'status' => 'pending',
            ]);
            $m1->actionItems()->create([
                'description' => 'Evaluasi flow approval untuk refund uang jalan sopir.',
                'pic_id' => $user1->id,
                'due_date' => '2026-06-10',
                'status' => 'in_progress',
            ]);

            // 2. HRGA Pantry meeting
            $m2 = Meeting::create([
                'company_id' => 1,
                'title' => 'Koordinasi Penyediaan ATK & Pantry Kantor HRGA',
                'meeting_date' => '2026-06-03',
                'start_time' => '14:00:00',
                'end_time' => '15:00:00',
                'location' => 'Virtual Zoom Room 3',
                'type' => 'internal',
                'chairperson_id' => $user2->id,
                'secretary_id' => $user1->id,
                'discussion_notes' => "Pembahasan stock opname ATK bulanan kantor pusat.\nBeberapa produk seperti kertas A4, spidol whiteboard, dan kopi sachet pantry sudah menipis dan perlu diajukan kembali ke Purchasing.\n\nFasilitas gedung juga melaporkan bahwa AC ruangan meeting 1 meneteskan air, tiket perbaikan sudah dibuat di modul GA.",
                'status' => 'draft',
                'created_by' => $user1->id,
            ]);

            $m2->attendees()->create(['user_id' => $user1->id, 'status' => 'present']);
            $m2->attendees()->create(['user_id' => $user2->id, 'status' => 'present']);

            $m2->actionItems()->create([
                'description' => 'Buat Purchase Request (PR) barang kertas A4 & Kopi Sachet melalui modul GA Requests.',
                'pic_id' => $user1->id,
                'due_date' => '2026-06-05',
                'status' => 'completed',
            ]);

            // 3. Cloud Server Migration kick-off meeting
            $m3 = Meeting::create([
                'company_id' => 1,
                'title' => 'Kick-off Project Migrasi Server Cloud ERP',
                'meeting_date' => '2026-06-01',
                'start_time' => '09:00:00',
                'end_time' => '10:30:00',
                'location' => 'Ruang Meeting IT Lantai 3',
                'type' => 'project',
                'chairperson_id' => $user3->id,
                'secretary_id' => $user2->id,
                'discussion_notes' => "Pembahasan rencana pemindahan server ERP lokal ke cloud AWS.\nTarget migrasi selesai dalam 3 bulan.\nPerlu disiapkan dokumentasi arsitektur cloud dan rincian alokasi biaya bulanan.",
                'status' => 'published',
                'created_by' => $user1->id,
            ]);

            $m3->attendees()->create(['user_id' => $user1->id, 'status' => 'present']);
            $m3->attendees()->create(['user_id' => $user2->id, 'status' => 'present']);
            $m3->attendees()->create(['user_id' => $user3->id, 'status' => 'present']);
            $m3->attendees()->create(['guest_name' => 'Budi Santoso (AWS Architect)', 'status' => 'present']);

            $m3->actionItems()->create([
                'description' => 'Menyusun arsitektur topologi AWS Cloud ERP.',
                'pic_id' => $user3->id,
                'due_date' => '2026-06-10',
                'status' => 'pending',
            ]);
            $m3->actionItems()->create([
                'description' => 'Membuat Rencana Anggaran Biaya (RAB) AWS bulanan.',
                'pic_id' => $user2->id,
                'due_date' => '2026-06-12',
                'status' => 'pending',
            ]);
            $m3->actionItems()->create([
                'description' => 'Melakukan backup database ERP lokal secara menyeluruh.',
                'pic_id' => $user1->id,
                'due_date' => '2026-06-15',
                'status' => 'in_progress',
            ]);

            // 4. Sales & Marketing Weekly Sync
            $m4 = Meeting::create([
                'company_id' => 1,
                'title' => 'Weekly Sync Tim Sales & Marketing',
                'meeting_date' => '2026-06-03',
                'start_time' => '08:30:00',
                'end_time' => '09:30:00',
                'location' => 'Virtual Google Meet',
                'type' => 'internal',
                'chairperson_id' => $user2->id,
                'secretary_id' => $user3->id,
                'discussion_notes' => "Review pencapaian target penjualan bulan Mei.\nPenjualan pipa meningkat 10% dibanding bulan lalu.\nPromosi produk baru di sosial media akan dijalankan mulai minggu depan oleh Tim Marketing.",
                'status' => 'published',
                'created_by' => $user1->id,
            ]);

            $m4->attendees()->create(['user_id' => $user2->id, 'status' => 'present']);
            $m4->attendees()->create(['user_id' => $user3->id, 'status' => 'present']);
            $m4->attendees()->create(['user_id' => $user4->id, 'status' => 'excused']);

            $m4->actionItems()->create([
                'description' => 'Menyusun laporan sales closing bulan Mei.',
                'pic_id' => $user2->id,
                'due_date' => '2026-06-07',
                'status' => 'pending',
            ]);
            $m4->actionItems()->create([
                'description' => 'Membuat materi promosi sosial media untuk produk pipa baru.',
                'pic_id' => $user3->id,
                'due_date' => '2026-06-09',
                'status' => 'in_progress',
            ]);

            // 5. Vendor evaluation
            $m5 = Meeting::create([
                'company_id' => 1,
                'title' => 'Rapat Evaluasi Kinerja Vendor Transportasi & Logistik',
                'meeting_date' => '2026-06-01',
                'start_time' => '13:00:00',
                'end_time' => '14:30:00',
                'location' => 'Ruang Rapat Direksi',
                'type' => 'external',
                'chairperson_id' => $user1->id,
                'secretary_id' => $user2->id,
                'discussion_notes' => "Evaluasi ketepatan waktu pengiriman oleh vendor logistik pihak ketiga.\nRata-rata keterlambatan pengiriman bahan baku meningkat sebesar 5%.\nVendor berjanji akan menambah armada cadangan di area Jabodetabek untuk mengimbangi lonjakan DO.",
                'status' => 'locked',
                'created_by' => $user1->id,
                'approved_at' => '2026-06-01 15:00:00',
                'signature_hash' => '8a7d32a9f1a234b5c6d7e8f901a2b3c4d5e6f7a8b9c0d1e2f3a4b5c6d7e8f90a',
                'chairperson_signature' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAKwAAABkCAYAAAAZ3O2qAAAABmJLR0QA/wD/AP+gvaeTAAAACXBIWXMAAAsTAAALEwEAmpwYAAAAB3RJTUUH6AYECwUTM29vWwAAADtJREFUeN7t1AEJAAAMAsF7/55HcYmPAyMFAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA4GMt1AEAADfO9gAAAABJRU5ErkJggg==',
            ]);

            $m5->attendees()->create(['user_id' => $user1->id, 'status' => 'present']);
            $m5->attendees()->create(['user_id' => $user2->id, 'status' => 'present']);
            $m5->attendees()->create(['guest_name' => 'Hendri (Direktur PT Trans Logistik)', 'status' => 'present']);

            $m5->actionItems()->create([
                'description' => 'Membuat SLA (Service Level Agreement) baru untuk vendor logistik.',
                'pic_id' => $user1->id,
                'due_date' => '2026-06-14',
                'status' => 'pending',
            ]);
            $m5->actionItems()->create([
                'description' => 'Memonitor performa ketepatan waktu pengiriman mingguan vendor.',
                'pic_id' => $user2->id,
                'due_date' => '2026-06-20',
                'status' => 'in_progress',
            ]);

            // 6. UI/UX Redesign
            $m6 = Meeting::create([
                'company_id' => 1,
                'title' => 'Brainstorming UI/UX Redesign Portal Pelanggan B2B',
                'meeting_date' => '2026-06-04',
                'start_time' => '10:00:00',
                'end_time' => '12:00:00',
                'location' => 'Ruang Kreatif Lt. 1',
                'type' => 'project',
                'chairperson_id' => $user3->id,
                'secretary_id' => $user4->id,
                'discussion_notes' => "Diskusi ide perbaikan tampilan portal order B2B bagi pelanggan luar.\nBanyak pelanggan mengeluhkan navigasi tracking pengiriman yang membingungkan.\nDiusulkan tampilan mobile-friendly dengan peta pelacakan GPS.",
                'status' => 'draft',
                'created_by' => $user1->id,
            ]);

            $m6->attendees()->create(['user_id' => $user1->id, 'status' => 'present']);
            $m6->attendees()->create(['user_id' => $user2->id, 'status' => 'present']);
            $m6->attendees()->create(['user_id' => $user3->id, 'status' => 'present']);
            $m6->attendees()->create(['user_id' => $user4->id, 'status' => 'present']);

            // 7. Audit Internal ISO
            $m7 = Meeting::create([
                'company_id' => 1,
                'title' => 'Persiapan Audit Internal ISO 9001:2015',
                'meeting_date' => '2026-06-02',
                'start_time' => '15:00:00',
                'end_time' => '16:30:00',
                'location' => 'Ruang Training Center',
                'type' => 'internal',
                'chairperson_id' => $user1->id,
                'secretary_id' => $user3->id,
                'discussion_notes' => "Pembahasan persiapan kelengkapan dokumen untuk audit internal mutu ISO.\nMasing-masing departemen wajib meng-update Standard Operating Procedure (SOP) masing-masing.\nTemuan audit sebelumnya harus dipastikan telah ditindaklanjuti.",
                'status' => 'published',
                'created_by' => $user1->id,
            ]);

            $m7->attendees()->create(['user_id' => $user1->id, 'status' => 'present']);
            $m7->attendees()->create(['user_id' => $user3->id, 'status' => 'present']);
            $m7->attendees()->create(['user_id' => $user4->id, 'status' => 'present']);

            $m7->actionItems()->create([
                'description' => 'Pengumpulan berkas SOP ter-update dari departemen Produksi & GA.',
                'pic_id' => $user3->id,
                'due_date' => '2026-06-09',
                'status' => 'pending',
            ]);
            $m7->actionItems()->create([
                'description' => 'Melakukan pre-audit internal dokumen kendali mutu di gudang.',
                'pic_id' => $user4->id,
                'due_date' => '2026-06-11',
                'status' => 'pending',
            ]);
        });

        $this->command->info("Extended meeting seeding completed successfully with 7 meetings!");
    }
}
