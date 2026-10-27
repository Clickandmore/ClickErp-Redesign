<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\admin\Controller;
use App\Models\ingaz\UserWebPushToken;
use App\Services\WebPush\WebPushSender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Browser registrations for website push. The mobile app registers through /firebase/update and is not affected. */
class ApiWebPushTokenController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate(['token' => 'required|string|max:500', 'user_agent' => 'nullable|string|max:1000']);
        if (!WebPushSender::enabled()) return jsonSuccess(['registered' => false], __('auth.done successfully'));
        UserWebPushToken::register((int) Auth::id(), $data['token'], $data['user_agent'] ?? $request->userAgent());
        return jsonSuccess(['registered' => true]);
    }

    public function destroy(Request $request)
    {
        $data = $request->validate(['token' => 'required|string|max:500']);
        if (WebPushSender::enabled()) UserWebPushToken::where('user_id', Auth::id())->where('token', $data['token'])->delete();
        return jsonSuccess();
    }
}
