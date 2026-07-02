<?php

$dir = __DIR__ . '/resources/views/print';
$files = glob($dir . '/*.blade.php');

$replacements = [
    // Logos
    'src="/images/jri-official-logo.png"' => 'src="{{ \App\Models\AppSetting::get(\'company_logo_path\', \'/images/jri-official-logo.png\') }}"',
    'src="{{ asset(\'images/jri-official-logo.png\') }}"' => 'src="{{ \App\Models\AppSetting::get(\'company_logo_path\', asset(\'images/jri-official-logo.png\')) }}"',
    
    // Logo Text (jidoka)
    '>jidoka<' => '>{{ \App\Models\AppSetting::get(\'company_logo_text\', \'jidoka\') }}<',
    
    // Full Name
    '>PT. JIDOKA RESULT INDONESIA<' => '>{{ \App\Models\AppSetting::get(\'company_full_name\', \'PT. JIDOKA RESULT INDONESIA\') }}<',
];

$count = 0;
foreach ($files as $file) {
    $content = file_get_contents($file);
    $original = $content;
    
    foreach ($replacements as $search => $replace) {
        $content = str_replace($search, $replace, $content);
    }
    
    // Some files might have slightly different spacing for address, fallback regex
    // We can just regex replace the content of <div class="company-address">...</div>
    $content = preg_replace_callback('/(<div class="company-address">)(.*?)(<\/div>)/s', function($matches) {
        $inner = trim($matches[2]);
        if (strpos($inner, 'AppSetting') !== false) return $matches[0]; // Already replaced
        
        // Convert <br> back to newlines for the default string
        $defaultText = str_replace(["<br>", "<br/>", "<br />"], "\n", $inner);
        // Clean up multiple spaces/newlines
        $defaultText = preg_replace('/[ \t]+/', ' ', $defaultText);
        $defaultText = preg_replace("/\n\s+/", "\n", $defaultText);
        
        $escaped = var_export(trim($defaultText), true);
        return $matches[1] . "\n                    {!! nl2br(e(\\App\\Models\\AppSetting::get('company_address', {$escaped}))) !!}\n                " . $matches[3];
    }, $content);
    
    if ($content !== $original) {
        file_put_contents($file, $content);
        echo "Updated: " . basename($file) . "\n";
        $count++;
    }
}

echo "Total files updated: $count\n";
