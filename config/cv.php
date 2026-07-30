<?php

return [
    'pdf' => [
        /*
         * Use the same Chromium renderer in every environment so uploaded
         * Blade templates keep identical HTML/CSS layout in generated PDFs.
         */
        'browser_path' => env('CV_PDF_BROWSER_PATH'),

        /*
         * mPDF supports less CSS and can materially change a CV's layout.
         * Keep this disabled unless a degraded emergency fallback is accepted.
         */
        'allow_mpdf_fallback' => env('CV_PDF_ALLOW_MPDF_FALLBACK', false),
    ],
];
