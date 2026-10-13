<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'accounts.customer.code',
        'accounts.worker.code',
        'accounts.driver.code',
        'websocket',
        'hourly.password',
        'hourly.password_confirmation',
        'current_password',
        'password',
        'password_confirmation',
        'whatsapp.chatwoot.access_token',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->renderable(function (Throwable $e, $request) {
            if (HourlyApiErrors::matches($request)) {
                return app(HourlyApiErrors::class)->render($e, $request);
            }
        });
        $this->reportable(function (Throwable $e) {
            //
        });
    }
}
