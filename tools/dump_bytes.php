<?php
if ($argc < 4) { echo "Usage: php dump_bytes.php <file> <startLine> <endLine>\n"; exit(1); }
$path=$argv[1]; $start=(int)$argv[2]; $end=(int)$argv[3];
$lines=file($path);
for($i=$start;$i<=$end && $i<=count($lines);$i++){
    $ln = $lines[$i-1];
    $bytes = array_map(function($ch){ return ord($ch); }, preg_split('//u', $ln, -1, PREG_SPLIT_NO_EMPTY));
    printf("%5d: %s\n", $i, rtrim($ln,"\r\n"));
    echo "      bytes: "; foreach($bytes as $b) echo $b.' '; echo "\n";
}
