<?php
$file = __DIR__ . '/../resources/lang/en/web.php';
if (!file_exists($file)) { echo "File not found\n"; exit(2); }
$lines = file($file);
foreach ($lines as $i=>$line) {
    if (strpos($line,'[')!==false) {
        printf("%5d: %s", $i+1, rtrim($line,"\n"));
        print("\n");
    }
}
