<?php

return [
    /*
    | Website push for customers (browsers). Off until the Firebase web app keys are configured.
    | Sending reuses the Firebase service account from config/firebase.php but nothing else of the
    | mobile push path: its own token table, its own requests, no message-log rows.
    */
    'enabled' => filter_var(env('WEB_PUSH_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
];
