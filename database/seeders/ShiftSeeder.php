<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ShiftSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $shifts = [
            ['name' => 'Shift 1', 'start_time' => '07:00:00', 'end_time' => '15:00:00'],
            ['name' => 'Shift 2', 'start_time' => '15:00:00', 'end_time' => '23:00:00'],
            ['name' => 'Shift 3', 'start_time' => '23:00:00', 'end_time' => '07:00:00'],
        ];

        foreach ($shifts as $shift) {
            \App\Models\Shift::updateOrCreate(['name' => $shift['name']], $shift);
        }
    }
}
