<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

class RecordAuthenticationActivity
{
    public function handle($event): void
    {
        $action = $event instanceof Login ? 'login' : ($event instanceof Logout ? 'logout' : 'login_failed');
        $logger = activity('authentication')->event($action)->withProperties(['guard' => $event->guard]);
        // Never log credentials, even on failed authentication.
        if ($event->user && !($event instanceof Failed)) {
            $logger->causedBy($event->user)->performedOn($event->user);
        } else {
            $logger->causedByAnonymous();
        }
        $logger->log(match ($action) {
            'login' => 'تسجيل الدخول',
            'logout' => 'تسجيل الخروج',
            default => 'محاولة دخول غير ناجحة',
        });
    }
}
