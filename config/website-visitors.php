<?php

return [
    /*
    | The old tracker records every successful public page request without
    | requiring a site-specific embed code. Keep it disabled so only active
    | tracking codes can create visitor records.
    */
    'legacy_internal_tracking' => env('WEBSITE_VISITOR_LEGACY_INTERNAL_TRACKING', false),
];
