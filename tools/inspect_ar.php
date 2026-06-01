<?php
$path = __DIR__ . '/../resources/lang/ar/admin.php';
$lines = file($path);
$start = 1320; $end = 1360;
for ($i = $start; $i <= $end && $i <= count($lines); $i++) {
    $ln = $lines[$i-1];
    $bytes = array_map(function($ch){ return ord($ch); }, preg_split('//u', $ln, -1, PREG_SPLIT_NO_EMPTY));
    echo sprintf("%4d: %s\n", $i, rtrim($ln, "\r\n"));
    echo "      bytes: ";
    foreach ($bytes as $b) echo $b . ' ';
    echo "\n";
}
