<?php

namespace App\Http\Traits;

use App\Models\ingaz\Notification;

trait StoreMessageTrait
{

    function storeMessage($title, $msg, $type, $user_id = null, $order_id = null)
    {
        Notification::create([
            'title' => $title,
            'description' => $msg,
            'user_id' => $user_id,
            'order_id' => $order_id,
            'type' => $type,
        ]);
    }
}
