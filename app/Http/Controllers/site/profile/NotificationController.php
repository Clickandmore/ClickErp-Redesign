<?php

namespace App\Http\Controllers\site\profile;

use App\Http\Actions\ingaz\NotificationAction;
use App\Http\Controllers\Controller;
use App\Models\ingaz\Notification as objModel;
use App\Enums\ingaz\NotificationsTypeEnum;

class NotificationController extends Controller
{
    public function index(NotificationAction $action)
    {
        $data['notifications'] = objModel::where('user_id',getGuardedUser()->id)->get();
        // dump(getGuardedUser()->id);
        // exit;
        return view('site.pages.profile.notifications_new',$data);

//         $notifications = $action->getWhere(['user_id' => auth('web')->id()]);
//         return view('site.pages.profile.notifications',get_defined_vars());
    }


}
