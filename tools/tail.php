<?php
$path = $argv[1];
$lines = file($path);
$count = count($lines);
echo "Total lines: $count\n";
$start = max(0,$count-60);
for ($i=$start;$i<$count;$i++){
    printf("%5d: %s", $i+1, $lines[$i]);
}
