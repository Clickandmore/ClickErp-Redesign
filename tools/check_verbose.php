<?php
$file = __DIR__ . '/../resources/lang/en/web.php';
$lines = file($file);
$stack = [];
foreach ($lines as $i=>$line) {
    $num = $i+1;
    for ($j=0;$j<strlen($line);$j++) {
        $c = $line[$j];
        if ($c === '[') { $stack[]='['; echo "push [ on $num:$j\n"; }
        if ($c === '(') {
            $context = substr($line, max(0,$j-6), 12);
            if (strpos($context,'array') !== false) { $stack[]='('; echo "push ( (array) on $num:$j\n"; }
        }
        if ($c === ']') { $last=array_pop($stack); echo "pop got $last for ] on $num:$j\n"; if ($last!=='[') { echo "MISMATCH at $num:$j opened $last\n"; exit(1);} }
        if ($c === ')') { $last=array_pop($stack); echo "pop got $last for ) on $num:$j\n"; if ($last!=='(') { echo "MISMATCH at $num:$j opened $last\n"; exit(1);} }
    }
}
echo "Done, stack:"; var_export($stack); echo "\n";
