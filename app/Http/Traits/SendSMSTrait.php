<?php

namespace App\Http\Traits;

use Illuminate\Support\Facades\Http;

trait SendSMSTrait
{
    function sendSMS($phone, $msg, $code = null)
    {
        // $phone = preg_replace('/[^0-9]/', '', $phone);

        // if (substr($phone, 0, 2) == '05') {
        //     $phone = '966' . substr($phone, 1);
        // } elseif (substr($phone, 0, 4) == '9660') {
        //     $phone = ltrim($phone, '9660');
        //     $phone = '966' . $phone;
        // } elseif (substr($phone, 0, 4) == '00966') {
        //     $phone = '966' . substr($phone, 5);
        // } else {
        //     $phone = '966' . $phone;
        // }

        $ch = curl_init(config('msegat.url'));
        $data = array(
            'userName' => config('msegat.username'),
            'apiKey' => config('msegat.api_key'),
            'numbers' => $phone,
            "userSender" => config('msegat.sender'),
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

        logger()->info("Msegat SMS Sent Successfully: ", ['code' => $code, 'phone' => $phone, 'msg' => $msg, 'response' => $result]);

        return $result;
    }
}
