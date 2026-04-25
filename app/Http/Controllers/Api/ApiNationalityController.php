<?php

namespace App\Http\Controllers\Api;

use App\Http\Actions\ingaz\NotificationAction;
use App\Http\Controllers\admin\Controller;
use App\Models\ingaz\Notification;
use Illuminate\Http\Request;

class ApiNationalityController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(NotificationAction $action)
    {
       // $notifications = $action->getWhere(['user_id' => auth('web')->id()]);
        // return view('site.pages.profile.notifications',get_defined_vars());

        $data['notifications'] = Notification::where('user_id',getGuardedUser()->id)->get();

        return apiResponse(true, __('api.All items'), $data, 200);
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
       //
    }
}
