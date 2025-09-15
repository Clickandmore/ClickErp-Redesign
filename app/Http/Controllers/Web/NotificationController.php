<?php

namespace App\Http\Controllers\Web;

use App\Enums\ingaz\NotificationsTypeEnum;
use App\Http\Actions\ingaz\NotificationAction as objAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\NotificationResource as objResource;
use App\Models\ingaz\Notification as objModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $data['notifications'] = objModel::where('user_id',getGuardedUser()->id)
            ->orWhere(['type'=>NotificationsTypeEnum::General->value])->latest()->get();
        return view('web.ingaz.notifications',$data);
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

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $data = objModel::where('user_id',Auth::user()->id)->find($id);
        if (!$data) {
            return jsonSuccess(null,__('api.not found'),201);
        } else {
            $data->delete();
            return jsonSuccess();
        }
    }
}
