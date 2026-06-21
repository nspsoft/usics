<?php
$content = file_get_contents('c:/laragon/www/ERP/resources/js/Pages/Blueprints/Costing.vue');
// Extract template section
if (preg_match('/<template>(.*)<\/template>/s', $content, $matches)) {
    $template = $matches[1];
    // Find all tags in order
    preg_match_all('/<\/?([a-zA-Z0-9:-]+)[^>]*>/', $template, $tags, PREG_OFFSET_CAPTURE);
    
    $stack = [];
    foreach ($tags[0] as $idx => $tagData) {
        $tag = $tagData[0];
        $offset = $tagData[1];
        $tagName = $tags[1][$idx][0];
        
        // Skip self-closing tags or template/slot tags if needed, or component tags
        if (strpos($tag, '/>') !== false || in_array($tagName, ['img', 'br', 'hr', 'input'])) {
            continue;
        }
        
        // Find line number
        $lineNum = substr_count(substr($template, 0, $offset), "\n") + 1;
        
        if (strpos($tag, '</') === 0) {
            // Closing tag
            if (empty($stack)) {
                echo "Unmatched closing tag: {$tag} on line {$lineNum}\n";
            } else {
                $last = array_pop($stack);
                if ($last['name'] !== $tagName) {
                    echo "Tag mismatch: opened {$last['tag']} on line {$last['line']} but closed with {$tag} on line {$lineNum}\n";
                }
            }
        } else {
            // Opening tag
            $stack[] = [
                'tag' => $tag,
                'name' => $tagName,
                'line' => $lineNum
            ];
        }
    }
    
    if (!empty($stack)) {
        echo "Unclosed tags remaining:\n";
        foreach ($stack as $unclosed) {
            echo "  {$unclosed['tag']} opened on line {$unclosed['line']}\n";
        }
    } else {
        echo "All tags match perfectly!\n";
    }
} else {
    echo "No template section found\n";
}
