<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\ingaz\Notification;
use Illuminate\Http\Request;

class MessageController extends Controller
{
        public function index(Request $request)
    {
        if (!@AdminGuardHasPerm(\App\PermKeys::NOTIFICATIONS_CONTROL)) {
            abort(403, 'ليس لديك صلاحية لعرض الرسائل');
        }

        $connection = (new \App\Models\ingaz\User())->getConnectionName();
        $messagesQuery = Message::on($connection)->with('user')->latest();
        $notificationsQuery = Notification::on($connection)->with('user')->latest();

        if ($request->filled('q')) {
            $search = trim($request->input('q'));
            $messagesQuery->where(function ($query) use ($search) {
                $query->where('message', 'like', '%' . $search . '%')
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', '%' . $search . '%')
                            ->orWhere('phone', 'like', '%' . $search . '%');
                    });
            });
            $notificationsQuery->where(function ($query) use ($search) {
                $query->where('title', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%')
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', '%' . $search . '%')
                            ->orWhere('phone', 'like', '%' . $search . '%');
                    });
            });
        }

        $messages = $messagesQuery->paginate(25, ['*'], 'sms_page')->withQueryString();
        $notifications = $notificationsQuery->paginate(25, ['*'], 'notif_page')->withQueryString();

        return view('admin.ingaz.messages.index', compact('messages', 'notifications'));
    }
}
