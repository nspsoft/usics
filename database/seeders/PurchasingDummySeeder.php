<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PurchasingDummySeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PurchasingSeeder::class);
    }
}
