<?php

return [
    'php_binary' => env('SCHEDULER_PHP_BINARY'),
    'memory_limit' => env('SCHEDULER_MEMORY_LIMIT', '192M'),
    'delay_seconds' => env('SCHEDULER_DELAY_SECONDS', 0),
];
