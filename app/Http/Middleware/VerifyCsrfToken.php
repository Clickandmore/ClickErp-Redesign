<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        '/ar/bank_transfer_submit',
        '/en/bank_transfer_submit',
        'moyasar/webhook',
        '*/moyasar/webhook',
    ];
}
