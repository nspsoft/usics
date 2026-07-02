<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class JidokaDetailedSeeder extends Seeder
{
    public function run(): void
    {
        $managerId = 1; // Administrator

        // 1. Get or Create the Main Project
        $projectName = 'USICS ERP IMPLEMENTATION';
        $project = DB::table('projects')->where('name', $projectName)->first();
        
        if ($project) {
            $projectId = $project->id;
            // Clear existing tasks to re-seed accurately with JIDOKA context
            DB::table('project_tasks')->where('project_id', $projectId)->delete();
        } else {
            $projectId = DB::table('projects')->insertGetId([
                'name' => $projectName,
                'description' => 'Implementasi USICS ERP untuk Solusi Pengemasan Presisi Tinggi PT JIDOKA.',
                'start_date' => '2026-01-23',
                'end_date' => '2026-03-01',
                'status' => 'active',
                'manager_id' => $managerId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2. Define Detailed Phases and Tasks (JIDOKA PACKAGING CONTEXT)
        $phases = [
            [
                'name' => 'PHASE 1: PROJECT PREPARATION',
                'start' => '2026-01-23', 'end' => '2026-01-30', 'status' => 'completed', 'progress' => 100,
                'tasks' => [
                    ['name' => 'Kick-off Meeting', 'desc' => 'Finalisasi struktur project JIDOKA.', 'progress' => 100, 'status' => 'completed'],
                    ['name' => 'Cloud Env Setup', 'desc' => 'Setup server produksi & kelancaran akses.', 'progress' => 100, 'status' => 'completed'],
                    ['name' => 'Packaging Master Data', 'desc' => 'Template data Board, Ink, & Die-lines.', 'progress' => 100, 'status' => 'completed'],
                    ['name' => 'User Role Definition', 'desc' => 'Hak akses Admin Produksi & Gudang.', 'progress' => 100, 'status' => 'completed'],
                ]
            ],
            [
                'name' => 'PHASE 2: BUSINESS BLUEPRINT & DESIGN',
                'start' => '2026-01-31', 'end' => '2026-02-07', 'status' => 'completed', 'progress' => 100,
                'tasks' => [
                    ['name' => 'Packaging Workflow Analysis', 'desc' => 'Detail alur Cutting ke Glueing.', 'progress' => 100, 'status' => 'completed'],
                    ['name' => 'OEM Standard Compliance', 'desc' => 'Persyaratan label Honda/Toyota.', 'progress' => 100, 'status' => 'completed'],
                    ['name' => 'Financial COA Realignment', 'desc' => 'Struktur akun biaya produksi karton.', 'progress' => 100, 'status' => 'completed'],
                    ['name' => 'UI Mockup Approval', 'desc' => 'Desain Dashboard Produksi Presisi.', 'progress' => 100, 'status' => 'completed'],
                ]
            ],
            [
                'name' => 'PHASE 3: REALIZATION / BUILD',
                'start' => '2026-02-08', 'end' => '2026-02-20', 'status' => 'in_progress', 'progress' => 85,
                'tasks' => [
                    ['name' => 'Manufacturing Module Core', 'desc' => 'Logika Up & Production Yield.', 'progress' => 100, 'status' => 'completed'],
                    ['name' => 'Automotive Traceability', 'desc' => 'QR Code tracking lot bahan baku.', 'progress' => 90, 'status' => 'in_progress'],
                    ['name' => 'Inventory Board Management', 'desc' => 'Stock tracking lembaran & roll.', 'progress' => 80, 'status' => 'in_progress'],
                    ['name' => 'Finance Automation', 'desc' => 'Jurnal otomatis dari SPK Produksi.', 'progress' => 70, 'status' => 'in_progress'],
                    ['name' => 'Neon Interior HUD UI', 'desc' => 'Implementasi mode gelap dashboard.', 'progress' => 100, 'status' => 'completed'],
                ]
            ],
            [
                'name' => 'PHASE 4: FINAL PREPARATION',
                'start' => '2026-02-21', 'end' => '2026-02-27', 'status' => 'todo', 'progress' => 0,
                'tasks' => [
                    ['name' => 'End-to-End Packaging UAT', 'desc' => 'Simulasi dari Order ke Pengiriman.', 'progress' => 0, 'status' => 'todo'],
                    ['name' => 'OEM Label Verification', 'desc' => 'Validasi scan label standar industri.', 'progress' => 0, 'status' => 'todo'],
                    ['name' => 'User Training Session', 'desc' => 'Training operator mesin & admin gudang.', 'progress' => 0, 'status' => 'todo'],
                ]
            ],
            [
                'name' => 'PHASE 5: GO-LIVE & SUPPORT',
                'start' => '2026-02-28', 'end' => '2026-03-05', 'status' => 'todo', 'progress' => 0,
                'tasks' => [
                    ['name' => 'System Cut-Over', 'desc' => 'Transisi dari Excel ke USICS.', 'progress' => 0, 'status' => 'todo'],
                    ['name' => 'OFFICIAL GO-LIVE', 'desc' => 'Pendampingan penuh operasional.', 'progress' => 0, 'status' => 'todo'],
                    ['name' => 'First Week Performance Audit', 'desc' => 'Audit akurasi data & stok.', 'progress' => 0, 'status' => 'todo'],
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
