<?php
$file = __DIR__ . '/../resources/lang/en/web.php';
if (!file_exists($file)) { echo "File not found\n"; exit(2); }
$lines = file($file);
$start = (int)($argv[1] ?? 1);
$end = (int)($argv[2] ?? $start+10);
for ($i=$start;$i<=$end;$i++) {
    $ln = $lines[$i-1];
    printf("%5d: %s", $i, rtrim($ln, "\n"));
    if (strpos($ln, 'array (') !== false) echo '    <<<HAS array (';
    if (strpos($ln, "']") !== false) echo '    <<<HAS \'\]\'';
    echo "\n";
}
