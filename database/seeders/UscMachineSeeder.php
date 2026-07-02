<?php

namespace Database\Seeders;

use App\Models\Machine;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Carbon\Carbon;

class UscMachineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Truncate existing machines to avoid duplicate codes and clean up generic seeds
        Machine::query()->delete();

        $machines = [
            // ==========================================
            // SLITTER MACHINES
            // ==========================================
            [
                'name' => 'Slitter Machine SA',
                'code' => 'SLITTER-SA',
                'type' => 'Slitting',
                'maker' => 'TOTO',
                'capacity' => '4,757 MT/Month',
                'purchase_date' => '2002-02-15',
                'purchase_price' => 7500000000.00,
                'runtime_hours' => 85420.50,
            ],
            [
                'name' => 'Slitter Machine S2',
                'code' => 'SLITTER-S2',
                'type' => 'Slitting',
                'maker' => 'YONEMORI',
                'capacity' => '6,300 MT/Month',
                'purchase_date' => '2005-06-10',
                'purchase_price' => 9200000000.00,
                'runtime_hours' => 72150.20,
            ],
            [
                'name' => 'Slitter Machine S4',
                'code' => 'SLITTER-S4',
                'type' => 'Slitting',
                'maker' => 'HONDA MF758',
                'capacity' => '5,250 MT/Month',
                'purchase_date' => '2012-08-20',
                'purchase_price' => 11000000000.00,
                'runtime_hours' => 45280.10,
            ],
            [
                'name' => 'Slitter Machine S5',
                'code' => 'SLITTER-S5',
                'type' => 'Slitting',
                'maker' => 'SONODA',
                'capacity' => '7,949 MT/Month',
                'purchase_date' => '2018-11-05',
                'purchase_price' => 15500000000.00,
                'runtime_hours' => 24100.80,
            ],

            // ==========================================
            // MINI SLITTER MACHINES
            // ==========================================
            [
                'name' => 'Mini Slitter SB',
                'code' => 'MINI-SLITTER-SB',
                'type' => 'Mini Slitting',
                'maker' => 'TOTO',
                'capacity' => '268 MT/Month',
                'purchase_date' => '2003-05-14',
                'purchase_price' => 2800000000.00,
                'runtime_hours' => 64120.30,
            ],
            [
                'name' => 'Mini Slitter S3',
                'code' => 'MINI-SLITTER-S3',
                'type' => 'Mini Slitting',
                'maker' => 'HAKUSAN',
                'capacity' => '131 MT/Month',
                'purchase_date' => '2008-04-18',
                'purchase_price' => 2100000000.00,
                'runtime_hours' => 52310.40,
            ],

            // ==========================================
            // LEVELLER MACHINES
            // ==========================================
            [
                'name' => 'Leveller LA',
                'code' => 'LEVELLER-LA',
                'type' => 'Levelling',
                'maker' => 'SUMIKURA',
                'capacity' => '3,502 MT/Month',
                'purchase_date' => '2002-02-20',
                'purchase_price' => 6200000000.00,
                'runtime_hours' => 88150.00,
            ],
            [
                'name' => 'Leveller LC',
                'code' => 'LEVELLER-LC',
                'type' => 'Levelling',
                'maker' => 'KYOWA',
                'capacity' => '583 MT/Month',
                'purchase_date' => '2006-09-12',
                'purchase_price' => 3800000000.00,
                'runtime_hours' => 61420.60,
            ],
            [
                'name' => 'Leveller LD',
                'code' => 'LEVELLER-LD',
                'type' => 'Levelling',
                'maker' => 'SONODA',
                'capacity' => '1,087 MT/Month',
                'purchase_date' => '2014-10-30',
                'purchase_price' => 5100000000.00,
                'runtime_hours' => 38450.25,
            ],

            // ==========================================
            // MINI LEVELLER MACHINES
            // ==========================================
            [
                'name' => 'Mini Leveller L5',
                'code' => 'MINI-LEVELLER-L5',
                'type' => 'Mini Levelling',
                'maker' => 'FUMIRA',
                'capacity' => '630 MT/Month',
                'purchase_date' => '2007-03-22',
                'purchase_price' => 1900000000.00,
                'runtime_hours' => 57200.40,
            ],
            [
                'name' => 'Mini Leveller L6',
                'code' => 'MINI-LEVELLER-L6',
                'type' => 'Mini Levelling',
                'maker' => 'SONODA',
                'capacity' => '1,155 MT/Month',
                'purchase_date' => '2016-05-15',
                'purchase_price' => 2700000000.00,
                'runtime_hours' => 29140.20,
            ],

            // ==========================================
            // SHEARING MACHINES
            // ==========================================
            [
                'name' => 'Shearing Machine SS-GF',
                'code' => 'SHEARING-SS-GF',
                'type' => 'Shearing',
                'maker' => 'YAMATSU',
                'capacity' => '144 MT/Month',
                'purchase_date' => '2004-11-18',
                'purchase_price' => 1500000000.00,
                'runtime_hours' => 48120.90,
            ],

            // ==========================================
            // GUILLOTINE SHEAR MACHINES
            // ==========================================
            [
                'name' => 'Guillotine Shear GA',
                'code' => 'GUILLOTINE-GA',
                'type' => 'Guillotine Shear',
                'maker' => 'AMADA',
                'capacity' => '158 MT/Month',
                'purchase_date' => '2002-03-01',
                'purchase_price' => 1200000000.00,
                'runtime_hours' => 74150.40,
            ],
            [
                'name' => 'Guillotine Shear GC',
                'code' => 'GUILLOTINE-GC',
                'type' => 'Guillotine Shear',
                'maker' => 'AIZAWA',
                'capacity' => '215 MT/Month',
                'purchase_date' => '2005-02-14',
                'purchase_price' => 1650000000.00,
                'runtime_hours' => 61280.30,
            ],
            [
                'name' => 'Guillotine Shear GD',
                'code' => 'GUILLOTINE-GD',
                'type' => 'Guillotine Shear',
                'maker' => 'AMADA',
                'capacity' => '252 MT/Month',
                'purchase_date' => '2008-07-19',
                'purchase_price' => 1800000000.00,
                'runtime_hours' => 52140.80,
            ],
            [
                'name' => 'Guillotine Shear GE',
                'code' => 'GUILLOTINE-GE',
                'type' => 'Guillotine Shear',
                'maker' => 'YSD',
                'capacity' => '273 MT/Month',
                'purchase_date' => '2010-09-05',
                'purchase_price' => 1950000000.00,
                'runtime_hours' => 45190.20,
            ],
            [
                'name' => 'Guillotine Shear GG',
                'code' => 'GUILLOTINE-GG',
                'type' => 'Guillotine Shear',
                'maker' => 'AIZAWA',
                'capacity' => '184 MT/Month',
                'purchase_date' => '2003-12-10',
                'purchase_price' => 1400000000.00,
                'runtime_hours' => 68230.10,
            ],
            [
                'name' => 'Guillotine Shear GN',
                'code' => 'GUILLOTINE-GN',
                'type' => 'Guillotine Shear',
                'maker' => 'AIZAWA',
                'capacity' => '221 MT/Month',
                'purchase_date' => '2006-04-28',
                'purchase_price' => 1700000000.00,
                'runtime_hours' => 59120.40,
            ],
            [
                'name' => 'Guillotine Shear GO',
                'code' => 'GUILLOTINE-GO',
                'type' => 'Guillotine Shear',
                'maker' => 'AMADA',
                'capacity' => '305 MT/Month',
                'purchase_date' => '2015-08-11',
                'purchase_price' => 2400000000.00,
                'runtime_hours' => 28410.70,
            ],
            [
                'name' => 'Guillotine Shear GI',
                'code' => 'GUILLOTINE-GI',
                'type' => 'Guillotine Shear',
                'maker' => 'AMADA',
                'capacity' => '76,125 Pcs/Month',
                'purchase_date' => '2011-03-24',
                'purchase_price' => 2100000000.00,
                'runtime_hours' => 41200.50,
            ],
            [
                'name' => 'Guillotine Shear GJ',
                'code' => 'GUILLOTINE-GJ',
                'type' => 'Guillotine Shear',
                'maker' => 'AIZAWA',
                'capacity' => '65,625 Pcs/Month',
                'purchase_date' => '2007-10-15',
                'purchase_price' => 1850000000.00,
                'runtime_hours' => 54100.20,
            ],
            [
                'name' => 'Guillotine Shear GL',
                'code' => 'GUILLOTINE-GL',
                'type' => 'Guillotine Shear',
                'maker' => 'YSD',
                'capacity' => '77,175 Pcs/Month',
                'purchase_date' => '2013-05-09',
                'purchase_price' => 2200000000.00,
                'runtime_hours' => 34890.30,
            ],
            [
                'name' => 'Guillotine Shear GM',
                'code' => 'GUILLOTINE-GM',
                'type' => 'Guillotine Shear',
                'maker' => 'YSD',
                'capacity' => '8,400 Pcs/Month',
                'purchase_date' => '2017-02-18',
                'purchase_price' => 1500000000.00,
                'runtime_hours' => 21040.60,
            ],
            [
                'name' => 'Guillotine Shear G4',
                'code' => 'GUILLOTINE-G4',
                'type' => 'Guillotine Shear',
                'maker' => 'AMADA',
                'capacity' => '420 MT/Month',
                'purchase_date' => '2014-06-30',
                'purchase_price' => 2800000000.00,
                'runtime_hours' => 31250.40,
            ],
            [
                'name' => 'Guillotine Shear G5',
                'code' => 'GUILLOTINE-G5',
                'type' => 'Guillotine Shear',
                'maker' => 'KOWA',
                'capacity' => '315 MT/Month',
                'purchase_date' => '2009-11-12',
                'purchase_price' => 2300000000.00,
                'runtime_hours' => 48200.80,
            ],
            [
                'name' => 'Guillotine Shear G6',
                'code' => 'GUILLOTINE-G6',
                'type' => 'Guillotine Shear',
                'maker' => 'AMADA',
                'capacity' => '158 MT/Month',
                'purchase_date' => '2019-01-25',
                'purchase_price' => 3200000000.00,
                'runtime_hours' => 15420.30,
            ],
            [
                'name' => 'Guillotine Shear G7',
                'code' => 'GUILLOTINE-G7',
                'type' => 'Guillotine Shear',
                'maker' => 'AMADA',
                'capacity' => '158 MT/Month',
                'purchase_date' => '2021-04-18',
                'purchase_price' => 3600000000.00,
                'runtime_hours' => 8940.50,
            ],

            // ==========================================
            // BLANKING PRESS MACHINES
            // ==========================================
            [
                'name' => 'Blanking Press BA',
                'code' => 'BLANKING-BA',
                'type' => 'Blanking',
                'maker' => 'AIDA',
                'capacity' => '349,928 Pcs/Month',
                'purchase_date' => '2002-03-15',
                'purchase_price' => 12500000000.00,
                'runtime_hours' => 84120.20,
            ],
            [
                'name' => 'Blanking Press BB',
                'code' => 'BLANKING-BB',
                'type' => 'Blanking',
                'maker' => 'AIDA',
                'capacity' => '666,288 Pcs/Month',
                'purchase_date' => '2015-10-12',
                'purchase_price' => 21000000000.00,
                'runtime_hours' => 35140.50,
            ],

            // ==========================================
            // TWB (WELDING) MACHINES
            // ==========================================
            [
                'name' => 'Laser Welder WA',
                'code' => 'WELDING-WA',
                'type' => 'Welding',
                'maker' => 'OYABE SEIKI',
                'capacity' => '94,169 Pcs/Month',
                'purchase_date' => '2003-04-20',
                'purchase_price' => 18500000000.00,
                'runtime_hours' => 74120.30,
            ],
            [
                'name' => 'Laser Welder WB',
                'code' => 'WELDING-WB',
                'type' => 'Welding',
                'maker' => 'OYABE SEIKI',
                'capacity' => '68,849 Pcs/Month',
                'purchase_date' => '2010-11-25',
                'purchase_price' => 24000000000.00,
                'runtime_hours' => 45180.80,
            ],
            [
                'name' => 'Laser Welder WD',
                'code' => 'WELDING-WD',
                'type' => 'Welding',
                'maker' => 'OYABE SEIKI',
                'capacity' => '63,000 Pcs/Month',
                'purchase_date' => '2018-07-02',
                'purchase_price' => 32000000000.00,
                'runtime_hours' => 21050.40,
            ],

            // ==========================================
            // SUPPORTING MACHINERY
            // ==========================================
            [
                'name' => 'Washing Machine',
                'code' => 'SUPPORT-WASHING',
                'type' => 'Supporting',
                'maker' => 'ECHO Co., Ltd.',
                'capacity' => 'Width 300-1850 / L=300-4000',
                'purchase_date' => '2002-02-15',
                'purchase_price' => 1500000000.00,
                'runtime_hours' => 68450.20,
            ],
            [
                'name' => 'Main Piller',
                'code' => 'SUPPORT-MAIN-PILLER',
                'type' => 'Supporting',
                'maker' => 'ECHO Co., Ltd.',
                'capacity' => 'Width 300-1850 / L=300-4000',
                'purchase_date' => '2002-02-15',
                'purchase_price' => 2200000000.00,
                'runtime_hours' => 71500.40,
            ],
            [
                'name' => 'Turn Over Machine',
                'code' => 'SUPPORT-TURNOVER',
                'type' => 'Supporting',
                'maker' => 'ECHO Co., Ltd.',
                'capacity' => '7 Ton',
                'purchase_date' => '2005-08-30',
                'purchase_price' => 1100000000.00,
                'runtime_hours' => 34500.80,
            ],
        ];

        foreach ($machines as $m) {
            Machine::create([
                'name' => $m['name'],
                'code' => $m['code'],
                'type' => $m['type'],
                'maker' => $m['maker'],
                'capacity' => $m['capacity'],
                'qr_code_uuid' => (string) Str::uuid(),
                'purchase_date' => Carbon::parse($m['purchase_date']),
                'purchase_price' => $m['purchase_price'],
                'runtime_hours' => $m['runtime_hours'],
                'is_active' => true,
            ]);
        }
    }
}
