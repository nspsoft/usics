<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\HR\Leave;
use App\Models\HR\LeaveType;
use App\Models\HR\OvertimeRequest;
use App\Models\HR\Reimbursement;
use App\Models\HR\OkrObjective;
use App\Models\HR\OkrKeyResult;
use App\Models\HR\JobPosting;
use App\Models\HR\Applicant;
use Carbon\Carbon;
use Illuminate\Support\Str;

class HrJuneDummyDataSeeder extends Seeder
{
    public function run(): void
    {
        $employees = Employee::all();
        if ($employees->isEmpty()) {
            $this->command->error("No employees found. Run LinkUsersToEmployeesSeeder first!");
            return;
        }

        $this->command->info("Starting HrJuneDummyDataSeeder...");

        // 1. SEED ATTENDANCE DATA (June 1 to June 30, 2026)
        $startDate = Carbon::create(2026, 6, 1);
        $endDate = Carbon::create(2026, 6, 30);
        $attendanceCount = 0;

        for ($date = clone $startDate; $date->lte($endDate); $date->addDay()) {
            // Skip weekends
            if ($date->isWeekend()) {
                continue;
            }

            foreach ($employees as $employee) {
                // Introduce some randomness: 5% chance of being absent or on leave
                $rand = rand(1, 100);
                if ($rand <= 3) {
                    // Absent
                    Attendance::updateOrCreate(
                        ['employee_id' => $employee->id, 'date' => $date->toDateString()],
                        [
                            'status' => 'absent',
                            'clock_in' => null,
                            'clock_out' => null,
                            'late_minutes' => 0,
                            'early_leave_minutes' => 0,
                            'overtime_minutes' => 0,
                            'note' => 'Tanpa keterangan',
                        ]
                    );
                    continue;
                } elseif ($rand <= 7) {
                    // Leave (Handled under Leave section, set status 'leave' in Attendance)
                    Attendance::updateOrCreate(
                        ['employee_id' => $employee->id, 'date' => $date->toDateString()],
                        [
                            'status' => 'leave',
                            'clock_in' => null,
                            'clock_out' => null,
                            'late_minutes' => 0,
                            'early_leave_minutes' => 0,
                            'overtime_minutes' => 0,
                            'note' => 'Cuti terencana',
                        ]
                    );
                    continue;
                }

                // Standard Clock In / Out
                // Normal is 08:00 to 17:00
                $hourIn = rand(7, 8);
                $minIn = rand(0, 59);
                // If hour is 8, ensure some are late
                if ($hourIn === 8 && $minIn > 0) {
                    $lateMinutes = $minIn;
                    $status = 'late';
                } else {
                    $lateMinutes = 0;
                    $status = 'present';
                }

                $clockIn = (clone $date)->setTime($hourIn, $minIn, 0);

                // Early leave calculation
                $hourOut = rand(16, 18);
                $minOut = rand(0, 59);
                if ($hourOut < 17) {
                    $earlyLeaveMinutes = (17 - $hourOut - 1) * 60 + (60 - $minOut);
                    $overtimeMinutes = 0;
                } else {
                    $earlyLeaveMinutes = 0;
                    $overtimeMinutes = $hourOut >= 17 ? (($hourOut - 17) * 60 + $minOut) : 0;
                }

                $clockOut = (clone $date)->setTime($hourOut, $minOut, 0);

                Attendance::updateOrCreate(
                    ['employee_id' => $employee->id, 'date' => $date->toDateString()],
                    [
                        'clock_in' => $clockIn,
                        'clock_out' => $clockOut,
                        'status' => $status,
                        'late_minutes' => $lateMinutes,
                        'early_leave_minutes' => $earlyLeaveMinutes,
                        'overtime_minutes' => $overtimeMinutes,
                        'location_lat' => '-6.2088' . rand(100, 999),
                        'location_lng' => '106.8456' . rand(100, 999),
                        'note' => $status === 'late' ? 'Terlambat karena lalu lintas padat' : 'Hadir normal',
                        'is_face_verified' => true,
                        'is_geofenced' => true,
                        'distance_in_meters' => rand(5, 30),
                        'distance_out_meters' => rand(5, 30),
                    ]
                );
                $attendanceCount++;
            }
        }
        $this->command->info("Seeded {$attendanceCount} Attendance records for June 2026.");

        // 2. SEED LEAVE MANAGEMENT (June 2026)
        $leaveTypeAnnual = LeaveType::where('name', 'Annual Leave')->first();
        $leaveTypeSick = LeaveType::where('name', 'Sick Leave')->first();
        $leaveCount = 0;

        if ($leaveTypeAnnual && $leaveTypeSick) {
            $leaveRequests = [
                [
                    'employee' => 'Nata Surya',
                    'type' => $leaveTypeAnnual,
                    'start' => '2026-06-08',
                    'end' => '2026-06-10',
                    'days' => 3,
                    'reason' => 'Acara keluarga di luar kota',
                    'status' => 'approved',
                    'approval_status' => 'approved',
                ],
                [
                    'employee' => 'Agus',
                    'type' => $leaveTypeSick,
                    'start' => '2026-06-15',
                    'end' => '2026-06-16',
                    'days' => 2,
                    'reason' => 'Sakit demam dan flu, surat dokter terlampir',
                    'status' => 'approved',
                    'approval_status' => 'approved',
                ],
                [
                    'employee' => 'Santi',
                    'type' => $leaveTypeAnnual,
                    'start' => '2026-06-22',
                    'end' => '2026-06-22',
                    'days' => 1,
                    'reason' => 'Urusan perpanjangan dokumen pribadi',
                    'status' => 'approved',
                    'approval_status' => 'approved',
                ],
                [
                    'employee' => 'Andi',
                    'type' => $leaveTypeAnnual,
                    'start' => '2026-06-29',
                    'end' => '2026-06-30',
                    'days' => 2,
                    'reason' => 'Cuti tahunan bersama keluarga',
                    'status' => 'pending',
                    'approval_status' => 'pending',
                ]
            ];

            foreach ($leaveRequests as $req) {
                $emp = Employee::where('full_name', $req['employee'])->first();
                if ($emp) {
                    Leave::create([
                        'employee_id' => $emp->id,
                        'leave_type_id' => $req['type']->id,
                        'start_date' => $req['start'],
                        'end_date' => $req['end'],
                        'total_days' => $req['days'],
                        'reason' => $req['reason'],
                        'status' => $req['status'],
                        'approval_status' => $req['approval_status'],
                        'approved_by' => $req['status'] === 'approved' ? 1 : null, // Admin approved
                        'approved_at' => $req['status'] === 'approved' ? Carbon::parse($req['start'])->subDays(2) : null,
                    ]);

                    // Sync corresponding attendance records to 'leave'
                    for ($d = Carbon::parse($req['start']); $d->lte(Carbon::parse($req['end'])); $d->addDay()) {
                        if (!$d->isWeekend()) {
                            Attendance::updateOrCreate(
                                ['employee_id' => $emp->id, 'date' => $d->toDateString()],
                                [
                                    'status' => 'leave',
                                    'clock_in' => null,
                                    'clock_out' => null,
                                    'late_minutes' => 0,
                                    'early_leave_minutes' => 0,
                                    'overtime_minutes' => 0,
                                    'note' => 'Cuti: ' . $req['reason']
                                ]
                            );
                        }
                    }

                    $leaveCount++;
                }
            }
        }
        $this->command->info("Seeded {$leaveCount} Leave requests for June 2026.");

        // 3. SEED LEMBUR (Overtime Requests - June 2026)
        $overtimeCount = 0;
        $overtimePlans = [
            [
                'employee' => 'Produksi',
                'date' => '2026-06-03',
                'start' => '17:00',
                'end' => '20:00',
                'req_min' => 180,
                'reason' => 'Lembur kejar target output Work Order pipa baja',
                'status' => 'approved',
            ],
            [
                'employee' => 'Andi',
                'date' => '2026-06-10',
                'start' => '17:00',
                'end' => '19:30',
                'req_min' => 150,
                'reason' => 'Perbaikan darurat breakdown mesin slitting A',
                'status' => 'approved',
            ],
            [
                'employee' => 'Delivery',
                'date' => '2026-06-12',
                'start' => '17:00',
                'end' => '21:00',
                'req_min' => 240,
                'reason' => 'Pemuatan muatan armada truk delivery luar kota besok pagi',
                'status' => 'approved',
            ],
            [
                'employee' => 'Santi',
                'date' => '2026-06-25',
                'start' => '17:00',
                'end' => '19:00',
                'req_min' => 120,
                'reason' => 'Rekapitulasi PO akhir bulan bersama tim purchasing',
                'status' => 'pending',
            ]
        ];

        foreach ($overtimePlans as $plan) {
            $emp = Employee::where('full_name', $plan['employee'])->first();
            if ($emp) {
                OvertimeRequest::updateOrCreate(
                    ['employee_id' => $emp->id, 'date' => $plan['date'], 'start_time' => $plan['start']],
                    [
                        'type' => 'post_claim',
                        'end_time' => $plan['end'],
                        'requested_minutes' => $plan['req_min'],
                        'approved_minutes' => $plan['status'] === 'approved' ? $plan['req_min'] : 0,
                        'reason' => $plan['reason'],
                        'status' => $plan['status'],
                        'approved_by' => $plan['status'] === 'approved' ? 1 : null,
                        'approved_at' => $plan['status'] === 'approved' ? Carbon::parse($plan['date'])->addHours(1) : null,
                    ]
                );

                // Sync attendance overtime minutes if present
                if ($plan['status'] === 'approved') {
                    $att = Attendance::where('employee_id', $emp->id)->whereDate('date', $plan['date'])->first();
                    if ($att) {
                        $att->update(['overtime_minutes' => $plan['req_min']]);
                    }
                }

                $overtimeCount++;
            }
        }
        $this->command->info("Seeded {$overtimeCount} Overtime Requests for June 2026.");

        // 4. SEED REIMBURSEMENT (June 2026)
        $reimbursementCount = 0;
        $reimbursements = [
            [
                'employee' => 'Nata Surya',
                'date' => '2026-06-05',
                'type' => 'Transportasi',
                'amount' => 350000,
                'description' => 'Bensin dan Tol kunjungan prospek klien PT Krakatau Steel',
                'status' => 'approved',
                'approval_status' => 'approved',
            ],
            [
                'employee' => 'Agus',
                'date' => '2026-06-12',
                'type' => 'Medis',
                'amount' => 180000,
                'description' => 'Klaim pembelian obat demam & resep dokter',
                'status' => 'approved',
                'approval_status' => 'approved',
            ],
            [
                'employee' => 'Widya',
                'date' => '2026-06-18',
                'type' => 'Lain-lain',
                'amount' => 250000,
                'description' => 'Pembelian snack & konsumsi rapat koordinasi bulanan',
                'status' => 'approved',
                'approval_status' => 'approved',
            ],
            [
                'employee' => 'Andi',
                'date' => '2026-06-26',
                'type' => 'Operasional',
                'amount' => 420000,
                'description' => 'Pembelian sparepart urgent seal hydraulic di Glodok',
                'status' => 'pending',
                'approval_status' => 'pending',
            ]
        ];

        foreach ($reimbursements as $index => $reimb) {
            $emp = Employee::where('full_name', $reimb['employee'])->first();
            if ($emp) {
                $reimbNumber = 'REIMB-' . Carbon::parse($reimb['date'])->format('Ymd') . '-' . sprintf('%03d', $index + 1);
                Reimbursement::updateOrCreate(
                    ['reimbursement_number' => $reimbNumber],
                    [
                        'employee_id' => $emp->id,
                        'date' => $reimb['date'],
                        'type' => $reimb['type'],
                        'amount' => $reimb['amount'],
                        'description' => $reimb['description'],
                        'receipt_path' => 'reimbursements/dummy_receipt.png',
                        'status' => $reimb['status'],
                        'approval_status' => $reimb['approval_status'],
                    ]
                );
                $reimbursementCount++;
            }
        }
        $this->command->info("Seeded {$reimbursementCount} Reimbursements for June 2026.");

        // 5. SEED COMPANY PERFORMANCE (OKRs - Q2 2026)
        $okrCount = 0;
        // June 2026 corresponds to Q2 (April, May, June)
        $period = '2026-Q2';

        $okrPlans = [
            [
                'employee' => 'Nata Surya',
                'objectives' => [
                    [
                        'title' => 'Meningkatkan penjualan pipa baja struktural di area Jawa Barat',
                        'weight' => 50,
                        'krs' => [
                            ['title' => 'Mendapatkan minimal 3 kontrak baru dengan volume > 100 Ton', 'target' => 3, 'current' => 3],
                            ['title' => 'Mencapai total sales revenue 5.000 Juta Rp dari klien baru', 'target' => 5000, 'current' => 4800],
                        ]
                    ],
                    [
                        'title' => 'Optimalisasi retensi pelanggan lama',
                        'weight' => 50,
                        'krs' => [
                            ['title' => 'Melakukan kunjungan / visit berkala ke 10 pelanggan besar', 'target' => 10, 'current' => 9],
                            ['title' => 'Menyelesaikan 100% komplain keluhan pelanggan di bawah 48 jam', 'target' => 100, 'current' => 95],
                        ]
                    ]
                ]
            ],
            [
                'employee' => 'Agus',
                'objectives' => [
                    [
                        'title' => 'Meningkatkan efisiensi lini produksi utama',
                        'weight' => 60,
                        'krs' => [
                            ['title' => 'Mengurangi scrap / waste produksi besi plat menjadi < 2%', 'target' => 2, 'current' => 1.8],
                            ['title' => 'Meningkatkan utilisasi mesin slitting harian hingga 85%', 'target' => 85, 'current' => 82],
                        ]
                    ],
                    [
                        'title' => 'Meningkatkan kepatuhan K3 di area pabrik',
                        'weight' => 40,
                        'krs' => [
                            ['title' => 'Melakukan safety briefing 20 kali dalam satu kuartal', 'target' => 20, 'current' => 20],
                            ['title' => 'Mencapai target Zero Accident (0 insiden kecelakaan)', 'target' => 0, 'current' => 0],
                        ]
                    ]
                ]
            ]
        ];

        foreach ($okrPlans as $plan) {
            $emp = Employee::where('full_name', $plan['employee'])->first();
            if ($emp) {
                foreach ($plan['objectives'] as $objData) {
                    $objective = OkrObjective::firstOrCreate(
                        ['employee_id' => $emp->id, 'period' => $period, 'title' => $objData['title']],
                        [
                            'weight' => $objData['weight'],
                            'score' => 0,
                        ]
                    );

                    foreach ($objData['krs'] as $krData) {
                        OkrKeyResult::updateOrCreate(
                            ['objective_id' => $objective->id, 'title' => $krData['title']],
                            [
                                'target_value' => $krData['target'],
                                'current_value' => $krData['current'],
                            ]
                        );
                    }

                    // Score is automatically calculated on Kr static booted event, but let's call updateScore just in case
                    $objective->updateScore();
                    $okrCount++;
                }
            }
        }
        $this->command->info("Seeded {$okrCount} OKR Objectives for Q2 2026.");

        // 6. SEED JOB POSTINGS (ATS)
        $deptMKT = Department::where('code', 'DEPT-MKT')->first();
        $deptPRD = Department::where('code', 'DEPT-PRD')->first();
        $deptIT = Department::where('code', 'DEPT-IT')->first();
        $jobCount = 0;

        if ($deptMKT && $deptPRD && $deptIT) {
            $jobs = [
                [
                    'title' => 'Sales Executive Senior',
                    'dept' => $deptMKT,
                    'description' => 'Mencari kandidat berpengalaman untuk memperluas penetrasi pasar pipa baja industri di area Banten dan Jawa Barat.',
                    'requirements' => "• Pengalaman minimal 3 tahun di bidang sales manufaktur/B2B baja.\n• Memiliki relasi luas dengan kontraktor dan pabrikator.\n• Kemampuan komunikasi dan negosiasi yang luar biasa.",
                    'status' => 'active',
                    'closing' => '2026-06-30',
                    'applicants' => [
                        [
                            'name' => 'Ferry Dermawan',
                            'email' => 'ferry.d@gmail.com',
                            'phone' => '08119988776',
                            'skills' => 'Sales B2B, Steel Pipes, Negotiation, AutoCAD, CRM',
                            'score' => 85,
                            'status' => 'interview',
                        ],
                        [
                            'name' => 'Dewi Lestari',
                            'email' => 'dewi.lestari@yahoo.com',
                            'phone' => '08128877665',
                            'skills' => 'B2B Sales, Marketing, Negotiation, Presentation',
                            'score' => 70,
                            'status' => 'applied',
                        ]
                    ]
                ],
                [
                    'title' => 'Operator Mesin Slitting',
                    'dept' => $deptPRD,
                    'description' => 'Bertanggung jawab penuh untuk mengoperasikan mesin potong slitting plat baja sesuai instruksi kerja dan menjaga kualitas produk.',
                    'requirements' => "• Pendidikan minimal SMK Teknik Mesin.\n• Pengalaman minimal 2 tahun mengoperasikan mesin slitting/shearing baja.\n• Mampu membaca jangka sorong dan micrometer.",
                    'status' => 'active',
                    'closing' => '2026-06-25',
                    'applicants' => [
                        [
                            'name' => 'Bambang Tri',
                            'email' => 'bambang.tri@gmail.com',
                            'phone' => '08139900112',
                            'skills' => 'Machine Operation, Slitting, Shearing, Precision Measurement',
                            'score' => 90,
                            'status' => 'shortlisted',
                        ],
                        [
                            'name' => 'Hendro Prasetyo',
                            'email' => 'hendro.pr@outlook.com',
                            'phone' => '08785544332',
                            'skills' => 'SMK Graduate, Basic Machine Operation, Welding',
                            'score' => 60,
                            'status' => 'rejected',
                        ]
                    ]
                ],
                [
                    'title' => 'IT Infrastructure Specialist',
                    'dept' => $deptIT,
                    'description' => 'Mengelola jaringan kantor, server ERP lokal, backup cloud data, serta memastikan support helpdesk berjalan dengan baik.',
                    'requirements' => "• Lulusan S1 Teknik Informatika / Sistem Informasi.\n• Menguasai Linux Server, Virtualization (Proxmox/ESXi), dan Cisco Network.\n• Pengalaman maintenance database PostgreSQL/MySQL.",
                    'status' => 'closed',
                    'closing' => '2026-06-15',
                    'applicants' => [
                        [
                            'name' => 'Zulfikar Ali',
                            'email' => 'zulfikar.ali@gmail.com',
                            'phone' => '08569998887',
                            'skills' => 'Linux, Proxmox, Cisco CCNA, MySQL DBA, Bash Scripting',
                            'score' => 95,
                            'status' => 'hired',
                        ]
                    ]
                ]
            ];

            foreach ($jobs as $j) {
                $jobPosting = JobPosting::firstOrCreate(
                    ['title' => $j['title'], 'department_id' => $j['dept']->id],
                    [
                        'description' => $j['description'],
                        'requirements' => $j['requirements'],
                        'status' => $j['status'],
                        'closing_date' => $j['closing'],
                        'created_at' => Carbon::parse($j['closing'])->subDays(20),
                    ]
                );

                foreach ($j['applicants'] as $app) {
                    Applicant::firstOrCreate(
                        ['job_posting_id' => $jobPosting->id, 'email' => $app['email']],
                        [
                            'name' => $app['name'],
                            'phone' => $app['phone'],
                            'resume_path' => 'resumes/dummy_resume.pdf',
                            'parsed_skills' => $app['skills'],
                            'match_score' => $app['score'],
                            'status' => $app['status'],
                            'created_at' => Carbon::parse($j['closing'])->subDays(10),
                        ]
                    );
                }

                $jobCount++;
            }
        }
        $this->command->info("Seeded {$jobCount} Job Postings and their Applicants.");
    }
}
