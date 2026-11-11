<?php

return [
    'username' => env('DEV_SUPERADMIN_USERNAME', 'dev-superadmin'),
    // لا قيمة افتراضية: كلمة المرور الافتراضية كانت محفوظة في المستودع.
    // بدون DEV_SUPERADMIN_PASSWORD (أو كلمة مرور محفوظة من اللوحة) يبقى الدخول مغلقاً.
    'password' => env('DEV_SUPERADMIN_PASSWORD'),
    'security_file' => storage_path('app/private/dev-superadmin-security.enc'),
    'verification' => [
        // Destructive database operations stay disabled until at least one
        // trusted destination is configured explicitly.
        'email' => env('DEV_SUPERADMIN_VERIFICATION_EMAIL'),
        'phone' => env('DEV_SUPERADMIN_VERIFICATION_PHONE'),
        'expires_minutes' => 10,
        'max_attempts' => 5,
    ],
];
