<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\admin\Controller;
use App\Http\Resources\Api\SettingPoliciesResource;
use App\Http\Resources\Api\SettingResource as objResource;
use App\Http\Traits\API\ApiResponse;
use App\Models\ingaz\Setting as objModel;
use Illuminate\Http\Request;

class ApiSettingController extends Controller
{
    use ApiResponse;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
       $data = objModel::with('translations')->first();
       if (isset($data->id)) {
            $settings = objResource::make($data);
            return $this->successResponse($settings, __('api.All items'), 200);
       } else {
            return $this->successResponse(null, __('api.not found'), 201);
       }
    }

    public function policies()
    {
        $data = objModel::with('translations')->first();

        if (isset($data->id)) {
            return $this->successResponse(
                SettingPoliciesResource::make($data),
                __('api.All items'),
                200
            );
        }

        return $this->successResponse(null, __('api.not found'), 201);
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
