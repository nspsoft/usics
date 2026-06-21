<?php
$ref = new ReflectionClass(\App\Services\GeminiService::class);
echo "GeminiService methods:\n";
foreach ($ref->getMethods() as $method) {
    if (stripos($method->getName(), 'recon') !== false || stripos($method->getName(), 'cost') !== false || stripos($method->getName(), 'analyze') !== false) {
        echo " - " . $method->getName() . "\n";
    }
}
