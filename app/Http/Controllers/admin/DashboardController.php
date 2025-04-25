<?php

namespace App\Http\Controllers\admin;

use App\Http\Actions\dashboard\DashboardAction as objAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(objAction $action)
    {
        $data['types'] = $action->getJobTypes();
        $data['clients_count'] = $action->getClientsCount();
        $data['contact_count'] = $action->getContactsCount();
        $data['current_orders_count'] = $action->getCurrentOrdersCount();
        $data['top_call_center'] = $action->getTopCallCenter();
        $data['call_center_count'] = $action->callCenterCount();
        $data['cv_count'] = $action->cvCount();
        $data['ordered_cv'] = $action->orderedCvCount();
        $data['order_types'] = $action->getOrderCountsByType();
        return view('admin.dashboard',compact('data'));
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
