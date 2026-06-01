<?php
$f = __DIR__ . '/../resources/lang/en/web.php';
if (!file_exists($f)) { echo "no\n"; exit(2); }
$lines = file($f);
echo count($lines) . "\n";
