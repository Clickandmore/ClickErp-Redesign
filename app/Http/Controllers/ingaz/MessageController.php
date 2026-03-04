<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\Controller;
use App\Models\ingaz\Notification;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index(Request $request)
    {
        if (!@AdminGuardHasPerm(\App\PermKeys::NOTIFICATIONS_CONTROL)) {
            abort(403, 'ليس لديك صلاحية لعرض الرسائل');
        }

        $type = (string) $request->input('type', 'all');
        $allowedTypes = ['sms', 'whatsapp'];

        $connection = (new \App\Models\ingaz\User())->getConnectionName();
        $smsLogsQuery = Notification::on($connection)
            ->with('user')
            ->latest();

        if (in_array($type, $allowedTypes, true)) {
            $smsLogsQuery->where('type', $type);
        } else {
            $smsLogsQuery->whereIn('type', $allowedTypes);
            $type = 'all';
        }

        if ($request->filled('q')) {
            $search = trim($request->input('q'));
            $smsLogsQuery->where(function ($query) use ($search) {
                $query->where('title', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%')
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', '%' . $search . '%')
                            ->orWhere('phone', 'like', '%' . $search . '%');
                    });
            });
        }

        $smsLogs = $smsLogsQuery->paginate(25)->withQueryString();

        return view('admin.ingaz.messages.index', compact('smsLogs', 'type'));
    }
}
