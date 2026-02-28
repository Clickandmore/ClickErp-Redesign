<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Actions\ingaz\NotificationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ingaz\StoreNotificationRequest;
use App\Models\SmsTemplate;
use App\Models\ingaz\User;
use Illuminate\Http\Request;


class NotificationController extends Controller
{

       public $folderPath = "admin.ingaz.notifications";
        public $mainRoute = "notifications";

    function __construct()
    {
        // $this->middleware('permission:الإشعارات-قراءة,admin', ['only' => ['index']]);
        // $this->middleware('permission:الإشعارات-انشاء,admin', ['only' => ['create', 'store']]);
        // $this->middleware('permission:الإشعارات-تعديل,admin', ['only' => ['edit', 'update']]);
        // $this->middleware('permission:الإشعارات-حذف,admin', ['only' => ['destroy']]);
    }


    public function index()
    {
        $appUsers = User::where('is_blocked', false)
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->select('name', 'id', 'phone')
            ->latest()
            ->get();

        $smsTemplates = SmsTemplate::query()
            ->where('is_active', true)
            ->orderBy('title')
            ->get(['id', 'name', 'title', 'message', 'variables']);

        $totalEligibleUsers = $appUsers->count();

        return view($this->folderPath . '.index', compact('appUsers', 'smsTemplates', 'totalEligibleUsers'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    public function store(StoreNotificationRequest $request, NotificationAction $action)
    {
        $result = $action->storeNotification($request->validated(), $request);

        $message = 'تم إرسال الرسائل النصية بنجاح.';
        if (($result['failed_count'] ?? 0) > 0) {
            $message = "تم إرسال {$result['sent_count']} رسالة وتعذر إرسال {$result['failed_count']} رسالة.";
        } elseif (isset($result['sent_count'])) {
            $message = "تم إرسال {$result['sent_count']} رسالة نصية بنجاح.";
        }

        return jsonSuccess($result, $message, 201);

    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
