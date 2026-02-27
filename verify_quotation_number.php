<?php

dump([
    'feb' => \App\Models\Quotation::generateNumber(null, '2026-02-01'),
    'mar' => \App\Models\Quotation::generateNumber(null, '2026-03-01'),
]);

