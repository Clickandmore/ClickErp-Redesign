<?php

namespace App\Http\Controllers\site\profile;

use App\Http\Actions\ingaz\NotificationAction;
use App\Http\Controllers\Controller;

class NotificationController extends Controller
{
    public function index(NotificationAction $action)
    {
        $notifications = $action->getWhere(['user_id' => auth('web')->id()]);
        return view('site.pages.profile.notifications',get_defined_vars());
    }


}
