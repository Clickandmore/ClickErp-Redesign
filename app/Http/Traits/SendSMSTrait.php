<?php

namespace App\Http\Traits;

use App\Support\SmsGateway;

trait SendSMSTrait
{
    function sendSMS($phone, $msg, $code = null, $senderType = null)
    {
        return SmsGateway::send($phone, $msg, $code, $senderType);
    }

    private function resolveSenderName($senderType = null, $code = null): string
    {
        return SmsGateway::resolveSenderName($senderType, $code);
    }
}
