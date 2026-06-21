<?php
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;

echo "Wiping existing finance data...\n";

Schema::disableForeignKeyConstraints();

DB::table('journal_items')->truncate();
echo "Wiped journal_items.\n";

DB::table('journals')->truncate();
echo "Wiped journals.\n";

DB::table('coas')->truncate();
echo "Wiped coas.\n";

Schema::enableForeignKeyConstraints();

echo "Running FinanceDummySeeder...\n";

try {
    Artisan::call('db:seed', [
        '--class' => 'FinanceDummySeeder',
        '--force' => true
    ]);
    echo "FinanceDummySeeder executed successfully!\n";
} catch (\Exception $e) {
    echo "Error running FinanceDummySeeder: " . $e->getMessage() . "\n";
}
