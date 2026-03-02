<?php

namespace App\Http\Traits;

trait SendSMSTrait
{
    function sendSMS($phone, $msg, $code = null, $senderType = null)
    {

        $resolvedSender = $this->resolveSenderName($senderType, $code);

        $ch = curl_init(config('msegat.url'));
        $data = array(
            'userName' => config('msegat.username'),
            'apiKey' => config('msegat.api_key'),
            'numbers' => $phone,
            "userSender" => $resolvedSender,
            'msg' => $msg,
            'msgEncoding' => config('msegat.encoding'),
        );
        $response_data = json_encode($data);
        curl_setopt($ch, CURLOPT_POST, 1);
        //Attach our encoded JSON string to the POST fields.
        curl_setopt($ch, CURLOPT_POSTFIELDS, $response_data);
        //Set the content type to application/json
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        //Execute the request
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($ch);

        logger()->info("Msegat SMS Sent Successfully: ", [
            'code' => $code,
            'phone' => $phone,
            'msg' => $msg,
            'sender_type' => $senderType,
            'sender' => $resolvedSender,
            'response' => $result,
        ]);

        return $result;
    }

    private function resolveSenderName($senderType = null, $code = null): string
    {
        $resolvedType = strtolower(trim((string) ($senderType ?? '')));
        if ($resolvedType === '') {
            $resolvedType = $code !== null
                ? 'verification'
                : strtolower((string) config('msegat.default_sender_type', 'advertising'));
        }

        if ($resolvedType === 'verification') {
            return (string) config('msegat.verification_sender', config('msegat.sender', ''));
        }

        if ($resolvedType === 'advertising') {
            return (string) config('msegat.advertising_sender', config('msegat.sender', ''));
        }

        return (string) config('msegat.sender', '');
    }
}
