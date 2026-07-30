<?php

return [
    'pdf' => [
        /*
         * Use the same Chromium renderer in every environment so uploaded
         * Blade templates keep identical HTML/CSS layout in generated PDFs.
         */
        'browser_path' => env('CV_PDF_BROWSER_PATH'),

        /*
         * Extra milliseconds Chromium may keep loading remote images and web
         * fonts before the page is printed. A server is slower than a
         * developer machine, so this must not be too tight.
         */
        'virtual_time_budget' => (int) env('CV_PDF_VIRTUAL_TIME_BUDGET', 15000),

        'timeout' => (int) env('CV_PDF_TIMEOUT', 120),

        /*
         * Shared hosting sometimes forbids running a local binary at all
         * (disabled proc_open, noexec home directory, missing system
         * libraries). Point this at a Gotenberg or Browserless instance to
         * keep rendering with Chromium over HTTP. When set it takes
         * precedence over the local binary.
         */
        'remote' => [
            'driver' => env('CV_PDF_REMOTE_DRIVER'), // gotenberg | browserless
            'url' => env('CV_PDF_REMOTE_URL'),
            'token' => env('CV_PDF_REMOTE_TOKEN'),
            'timeout' => (int) env('CV_PDF_REMOTE_TIMEOUT', 120),
        ],

        /*
         * mPDF supports less CSS and can materially change a CV's layout.
         * Keep this disabled unless a degraded emergency fallback is accepted.
         */
        'allow_mpdf_fallback' => env('CV_PDF_ALLOW_MPDF_FALLBACK', false),
    ],
];
