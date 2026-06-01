<?php
$file = $argv[1] ?? __DIR__ . '/../resources/lang/en/web.php';
if (!file_exists($file)) { echo "File not found: $file\n"; exit(2); }
$lines = file($file);
$stack = [];
foreach ($lines as $i => $line) {
    $num = $i+1;
    $chars = str_split($line);
    for ($j=0;$j<count($chars);$j++) {
        $c = $chars[$j];
        if ($c === '[') $stack[] = ['[',$num,$j];
        if ($c === '(') {
            // check for 'array (' sequence
            $prev = substr($line, max(0,$j-5), 6);
            if (strpos($prev,'array') !== false || strpos($prev,'array') !== false) {
                $stack[] = ['(', $num, $j];
            }
        }
        if ($c === ']') {
            $last = array_pop($stack);
            if (!$last) {
                echo "Unmatched ] on line $num\n";
                exit(1);
            }
            if ($last[0] !== '[') {
                echo "MISMATCH: opened {$last[0]} on line {$last[1]} but closed ] on line $num\n";
                exit(1);
            }
        }
        if ($c === ')') {
            $last = array_pop($stack);
            if (!$last) {
                echo "Unmatched ) on line $num\n";
                exit(1);
            }
            if ($last[0] !== '(') {
                echo "MISMATCH: opened {$last[0]} on line {$last[1]} but closed ) on line $num\n";
                exit(1);
            }
        }
    }
}
if ($stack) {
    $top = end($stack);
    echo "Unclosed {$top[0]} opened on line {$top[1]} (pos {$top[2]})\n";
    exit(1);
}
echo "All brackets matched\n";
