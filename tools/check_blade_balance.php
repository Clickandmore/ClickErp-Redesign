<?php
$file = __DIR__ . '/../resources/views/admin/layout/inc/sidebar.blade.php';
if (!file_exists($file)) {
    echo "File not found: $file\n";
    exit(2);
}
// Read entire file and remove Blade comments ({{-- ... --}}) to avoid false positives
$content = file_get_contents($file);
$content = preg_replace('/{{--.*?--}}/s', '', $content);
$lines = explode("\n", $content);
$depth = 0;
$issues = [];
$stack = [];
foreach ($lines as $i => $line) {
    // find all occurrences in order
    $offset = 0;
    while (preg_match('/@if\b|@endif\b/', $line, $m, PREG_OFFSET_CAPTURE, $offset)) {
        $token = $m[0][0];
        $pos = $m[0][1];
        if ($token === '@if') {
            $depth++;
            $stack[] = $i + 1;
            $issues[] = ($i + 1) . ": +1 => depth=$depth";
        } else {
            $depth--;
            $issues[] = ($i + 1) . ": -1 => depth=$depth";
            array_pop($stack);
        }
        $offset = $pos + strlen($token);
    }
}
foreach ($issues as $l) echo $l . "\n";
echo "FINAL_DEPTH:$depth\n";
if (!empty($stack)) {
    echo "Unclosed @if lines: " . implode(', ', $stack) . "\n";
    exit(3);
}
exit(0);
