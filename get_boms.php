<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$boms = \App\Models\Bom::with(['product', 'components.product'])->limit(5)->get()->toArray();
print_r($boms);
