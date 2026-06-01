<?php
$path = $argv[1] ?? __DIR__ . '/../resources/lang/en/ingaz.php';
$lines = file($path);
$paren = 0;
foreach ($lines as $n => $ln) {
    $open = substr_count($ln, '(');
    $close = substr_count($ln, ')');
    $paren += $open - $close;
    if ($open || $close) {
        printf("%5d: +%d -%d => paren=%d  %s\n", $n+1, $open, $close, $paren, trim($ln));
    }
}
echo "FINAL paren=$paren\n";
