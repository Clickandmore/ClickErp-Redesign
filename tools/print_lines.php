<?php
$file = __DIR__ . '/../resources/lang/en/ingaz.php';
if (!file_exists($file)) { echo "File not found\n"; exit(2); }
$lines = file($file);
$start = max(1, (int)($argv[1] ?? 1));
$end = min(count($lines), (int)($argv[2] ?? $start+20));
for ($i=$start;$i<=$end;$i++) {
    printf("%5d: %s", $i, rtrim($lines[$i-1], "\n"));
    echo "\n";
}
