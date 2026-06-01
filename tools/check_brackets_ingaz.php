<?php
$file = __DIR__ . '/../resources/lang/en/ingaz.php';
if (!file_exists($file)) { echo "File not found\n"; exit(2); }
$lines = file($file);
$pairs = ['('=>')','['=>']','{'=>'}'];
$openStack = [];
// Also print parenthesis depth starting at line 1085
$trackFrom = 1085;
$depth = 0;
foreach ($lines as $ln => $line) {
    $chars = str_split($line);
    foreach ($chars as $i => $ch) {
        if (isset($pairs[$ch])) {
            $openStack[] = ['ch'=>$ch,'line'=>$ln+1,'pos'=>$i];
            if ($ln+1 >= $trackFrom && $ch==='(') $depth++;
        } else if (in_array($ch, $pairs)) {
            $last = end($openStack);
            if ($last && $pairs[$last['ch']] === $ch) {
                array_pop($openStack);
                if ($ln+1 >= $trackFrom && $ch===')') $depth--;
            } else {
                echo "MISMATCH at line " . ($ln+1) . " char '$ch' - expected closing for " . ($last['ch'] ?? '?') . " opened at line " . ($last['line'] ?? '?') . "\n";
            }
        }
    }
    if ($ln+1 >= $trackFrom && trim($line) !== '') {
        echo 'Line '.($ln+1)." depth=$depth\n";
    }
}
if (!empty($openStack)) {
    echo "Unclosed opens:\n";
    foreach ($openStack as $o) echo "$o[ch] opened at line $o[line]\n";
} else {
    echo "All brackets balanced\n";
}
exit(0);
