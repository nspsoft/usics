<?php
$ref = new ReflectionMethod(\App\Services\GeminiService::class, 'analyzeBankReconciliation');
$filename = $ref->getFileName();
$start_line = $ref->getStartLine();
$end_line = $ref->getEndLine();
$length = $end_line - $start_line + 1;

$source = file($filename);
$method_source = array_slice($source, $start_line - 1, $length);
echo implode("", $method_source);
