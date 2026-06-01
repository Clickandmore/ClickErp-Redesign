<?php
$path = $argv[1] ?? die("Usage\n");
$lines = file($path);
$out = [];
foreach ($lines as $ln) {
    if (strpos($ln,'<<<<<<<')!==false || strpos($ln,'=======')!==false || strpos($ln,'>>>>>>>')!==false) continue;
    $out[] = $ln;
}
file_put_contents($path, implode('', $out));
echo "Stripped markers\n";
