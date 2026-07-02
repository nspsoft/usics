<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UsicsDetailedSeeder extends Seeder
{
    public function run(): void
    {
        $managerId = 1; // Administrator

        // 1. Get or Create the Main Project
        $projectName = 'USICS ERP IMPLEMENTATION';
        $project = DB::table('projects')->where('name', $projectName)->first();
        
        if ($project) {
            $projectId = $project->id;
            // Clear existing tasks to re-seed accurately
            DB::table('project_tasks')->where('project_id', $projectId)->delete();
        } else {
            $projectId = DB::table('projects')->insertGetId([
                'name' => $projectName,
                'description' => 'Implementasi Sistem USICS ERP (Sales, Purchasing, Inventory, Manufacturing, Financials).',
                'start_date' => '2026-01-23',
                'end_date' => '2026-03-01',
                'status' => 'active',
                'manager_id' => $managerId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2. Define Detailed Phases and Tasks
        $phases = [
            [
                'name' => 'PHASE 1: PROJECT PREPARATION',
                'start' => '2026-01-23', 'end' => '2026-01-30', 'status' => 'completed', 'progress' => 100,
                'tasks' => [
                    ['name' => 'Kick-off Meeting', 'desc' => 'Finalisasi struktur organisasi project.', 'progress' => 100, 'status' => 'completed'],
                    ['name' => 'Env Preparation', 'desc' => 'Provisioning server produksi, setup SSL.', 'progress' => 100, 'status' => 'completed'],
                    ['name' => 'Master Data Template', 'desc' => 'Distribusi template Excel Master Data.', 'progress' => 100, 'status' => 'completed'],
                    ['name' => 'Security Baseline', 'desc' => 'Setup peran user awal.', 'progress' => 100, 'status' => 'completed'],
                ]
            ],
            [
                'name' => 'PHASE 2: BUSINESS BLUEPRINT & DESIGN',
                'start' => '2026-01-31', 'end' => '2026-02-07', 'status' => 'completed', 'progress' => 100,
                'tasks' => [
                    ['name' => 'Functional Gap Analysis (Sales)', 'desc' => 'Verifikasi alur diskon & pajak.', 'progress' => 100, 'status' => 'completed'],
                    ['name' => 'Functional Gap Analysis (Manufacturing)', 'desc' => 'Detail alur Slitting & Waste.', 'progress' => 100, 'status' => 'completed'],
                    ['name' => 'Functional Gap Analysis (Finance)', 'desc' => 'Sinkronisasi klasifikasi akun COA.', 'progress' => 100, 'status' => 'completed'],
                    ['name' => 'Mockup Validation', 'desc' => 'Approval desain High-Fidelity Dashboard.', 'progress' => 100, 'status' => 'completed'],
                ]
            ],
            [
                'name' => 'PHASE 3: REALIZATION / BUILD',
                'start' => '2026-02-08', 'end' => '2026-02-20', 'status' => 'in_progress', 'progress' => 85,
                'tasks' => [
                    ['name' => 'Slitting Log Implementation', 'desc' => 'Multiplier Coil to Strip logic.', 'progress' => 100, 'status' => 'completed'],
                    ['name' => 'Dashboard OEE Real-time', 'desc' => 'Availability, Performance, Quality.', 'progress' => 100, 'status' => 'completed'],
                    ['name' => 'Automated General Ledger', 'desc' => 'Sinkronisasi jurnal dari Sales/Purchase.', 'progress' => 90, 'status' => 'in_progress'],
                    ['name' => 'HR Absence Integration', 'desc' => 'Integrasi clock-in dengan tunjangan.', 'progress' => 50, 'status' => 'in_progress'],
                    ['name' => 'UI/UX Full Theme Support', 'desc' => 'Neon HUD & Clean Ivory mode.', 'progress' => 100, 'status' => 'completed'],
                ]
            ],
            [
                'name' => 'PHASE 4: FINAL PREPARATION',
                'start' => '2026-02-21', 'end' => '2026-02-27', 'status' => 'todo', 'progress' => 0,
                'tasks' => [
                    ['name' => 'End-to-End UAT Scenario', 'desc' => 'Uji coba dari Order sampai Keuangan.', 'progress' => 0, 'status' => 'todo'],
                    ['name' => 'Final Data Migration', 'desc' => 'Import saldo awal & master data valid.', 'progress' => 0, 'status' => 'todo'],
                    ['name' => 'Intensive User Training', 'desc' => 'Training Admin Produksi & Akuntansi.', 'progress' => 0, 'status' => 'todo'],
                ]
            ],
            [
                'name' => 'PHASE 5: GO-LIVE & SUPPORT',
                'start' => '2026-02-28', 'end' => '2026-03-05', 'status' => 'todo', 'progress' => 0,
                'tasks' => [
                    ['name' => 'System Cut-Over', 'desc' => 'Penghentian pencatatan di sistem lama.', 'progress' => 0, 'status' => 'todo'],
                    ['name' => '01 MARCH GO-LIVE', 'desc' => 'Transisi penuh ke USICS ERP.', 'progress' => 0, 'status' => 'todo'],
                    ['name' => 'Post Go-Live Audit', 'desc' => 'Verifikasi akurasi data minggu pertama.', 'progress' => 0, 'status' => 'todo'],
                ]
            ],
        ];

        // 3. Insert Phases and Tasks
        foreach ($phases as $p) {
            $phaseId = DB::table('project_tasks')->insertGetId([
                'project_id' => $projectId,
                'name' => $p['name'],
                'description' => $p['name'],
                'start_date_plan' => $p['start'],
                'end_date_plan' => $p['end'],
                'start_date_actual' => ($p['status'] !== 'todo') ? $p['start'] : null,
                'end_date_actual' => ($p['status'] === 'completed') ? $p['end'] : null,
                'progress' => $p['progress'],
                'status' => $p['status'],
                'priority' => 'high',
                'parent_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($p['tasks'] as $t) {
                DB::table('project_tasks')->insert([
                    'project_id' => $projectId,
                    'name' => $t['name'],
                    'description' => $t['desc'],
                    'start_date_plan' => $p['start'],
                    'end_date_plan' => $p['end'],
                    'start_date_actual' => ($t['status'] !== 'todo') ? $p['start'] : null,
                    'end_date_actual' => ($t['status'] === 'completed') ? $p['end'] : null,
                    'progress' => $t['progress'],
                    'status' => $t['status'],
                    'priority' => 'medium',
                    'parent_id' => $phaseId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
