<?php

namespace App\Http\Controllers\site\profile;

use App\Http\Controllers\Controller;
use App\Models\ingaz\Notification as objModel;
use App\Enums\ingaz\NotificationsTypeEnum;

class NotificationController extends Controller
{
    public function index()
    {
        $data['notifications'] = objModel::where('user_id',getGuardedUser()->id)
        ->orWhere(['type'=>NotificationsTypeEnum::General->value])->latest()->get();
        return view('site.pages.profile.notifications_new',$data);
    }


}
