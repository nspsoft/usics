<?php

$config = \App\Models\DocumentNumbering::where('code', 'sales_invoice')->first();
$service = app(\App\Services\DocumentNumberService::class);

dump([
    'config' => [
        'code' => $config?->code,
        'format' => $config?->format,
        'padding' => $config?->padding,
        'current_number' => $config?->current_number,
        'reset_period' => $config?->reset_period,
        'last_reset_date' => $config?->last_reset_date,
    ],
    'preview_feb_2026' => $service->preview('sales_invoice', [
        'CUST_CODE' => 'MMKI',
        'ROMAN_MONTH' => 'II',
    ], '2026-02-01'),
]);

