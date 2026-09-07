<?php

return [
    // Explicit tenant allowlist. A report's presence on disk never authorizes
    // importing another company's history into the currently connected database.
    'alrafedin_databases' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('ALRAFEDIN_ACTIVITY_IMPORT_DATABASES', ''))
    ))),
];
