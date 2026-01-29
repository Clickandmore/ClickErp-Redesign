<?php

namespace App\Http\Actions\ingaz;

use App\Enums\ingaz\NotificationActionEnum;
use App\Http\Actions\MainAction;
use App\Http\Traits\Firebase;
use App\Http\Traits\SendSMSTrait;
use App\Models\ingaz\Notification;
use App\Models\ingaz\User;

class NotificationAction extends MainAction
{
    use Firebase, SendSMSTrait;

    public function __construct(Notification $model)
    {
        $this->model = $model;
        $this->fileFolder = 'images/Notification/';
    }


    public function storeNotification($data, $request)
    {
        if ($request->type == 'sms') {
            $msg = ($request->title['ar'] ?? '') . "\n" . ($request->description['ar'] ?? '');
            if ($request->all_or_some == 'all') {
                $phones = User::where('is_blocked', 0)
                    ->whereNotNull('phone')
                    ->pluck('phone');
            } elseif ($request->all_or_some == 'some') {
                $ids = (array) $request->user_id;
                $phones = User::whereIn('id', $ids)
                    ->where('is_blocked', 0)
                    ->whereNotNull('phone')
                    ->pluck('phone');
            } else {
                $phones = collect();
            }

            foreach ($phones as $phone) {
                $this->sendSMS(formatSaudiPhone($phone), $msg);
            }
        } else {
            $usersIDs = [];
            $data['type'] = NotificationActionEnum::General->value;

            if ($request->all_or_some == 'all') {
                $data['user_id'] = null;
                $this->store($data);
                $usersIDs = User::where('is_blocked', false)->pluck('id')->toArray();
            } elseif ($request->all_or_some == 'some') {
                $usersIDs = $request->user_id;
                foreach ($usersIDs as $user_id) {
                    $data['user_id'] = $user_id;
                    $this->store($data);
                }
            }

            foreach ($usersIDs as $to_user_id) {
                $data['user_id'] = $to_user_id;
                $this->sendFCMNotification($request->title, $request->description, $to_user_id, $data);
            }
        }

    }
}
