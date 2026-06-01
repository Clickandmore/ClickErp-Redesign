<?php
$path = __DIR__ . '/../resources/lang/ar/admin.php';
$lines = file($path);
$sq = 0; $par=0;
foreach ($lines as $n => $ln) {
    if (strpos($ln, '[') !== false) {
        $count = substr_count($ln, '[');
        $sq += $count;
        echo ($n+1) . ": +$count '[' -> sq=$sq : " . trim($ln) . "\n";
    }
    if (strpos($ln, ']') !== false) {
        $count = substr_count($ln, ']');
        $sq -= $count;
        echo ($n+1) . ": -$count ']' -> sq=$sq : " . trim($ln) . "\n";
    }
}
echo "final square count: $sq\n";
