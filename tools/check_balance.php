<?php
$path = __DIR__ . '/../resources/lang/ar/admin.php';
$lines = file($path);
$paren = 0; $brack = 0; $square = 0;
foreach ($lines as $n => $ln) {
    $chars = preg_split('//u', $ln, -1, PREG_SPLIT_NO_EMPTY);
    foreach ($chars as $ch) {
        if ($ch === '(') $paren++;
        if ($ch === ')') $paren--;
        if ($ch === '{') $brack++;
        if ($ch === '}') $brack--;
        if ($ch === '[') $square++;
        if ($ch === ']') $square--;
    }
    if ($paren < 0 || $brack < 0 || $square < 0) {
        echo "Imbalance at line " . ($n+1) . " paren=$paren brack=$brack square=$square\n";
        exit(0);
    }
    if (($n+1) % 50 == 0) echo "line " . ($n+1) . " counts: paren=$paren brack=$brack square=$square\n";
}
echo "Final counts: paren=$paren brack=$brack square=$square\n";
