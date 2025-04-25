<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Actions\ingaz\NotificationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ingaz\StoreNotificationRequest;
use App\Models\ingaz\Notification as objModel;
use App\Models\ingaz\User;
use Illuminate\Http\Request;


class NotificationController extends Controller
{

       public $folderPath = "admin.ingaz.notifications";
        public $postData = ["title",'description','to_user_id','type'];
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
        $appUsers = User::where('is_blocked',false)->select('name','id')->latest()->get();
        return view($this->folderPath . '.index', compact('appUsers'));
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
        $data = $request->only($this->postData);
        $action->storeNotification($data, $request);
        return jsonSuccess(null,'تم ارسال الاشعار بنجاح',201);

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
