<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ingaz\ComplaintRequest;
use Illuminate\Http\Request;
use App\Http\Actions\ingaz\ComplaintAction as objAction;
use App\Models\ingaz\Complaint as objModel;
use Illuminate\Support\Str;

class ApiComplaintController extends Controller
{
    public $postData = ['address', 'message', 'details', 'file', 'complaint_type_id'];


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $complaints = objModel::where('user_id', getGuardedUser()->id)->latest()->get();
        $complaintTypes = \App\Models\ingaz\ComplaintType::all();

        $data = [
            'complaints' => $complaints,
            'complaintTypes' => $complaintTypes
        ];

        return apiResponse(true, __('api.All items'), $data, 200);
    }

    public function complaints_types()
    {
        $complaintTypes = \App\Models\ingaz\ComplaintType::all();

        $data = [
            'complaintTypes' => $complaintTypes
        ];

        return apiResponse(true, __('api.All items'), $data, 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ComplaintRequest $request, objAction $action)
    {
        $dataInsert = $request->only($this->postData);
        $dataInsert['user_id'] = auth('api')->user()->id;

        // Handle file upload
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $extension = $file->getClientOriginalExtension();
            $fileName = Str::random(40) . '.' . $extension;
            $file->storeAs('public/complaints', $fileName);
            $dataInsert['file'] = 'complaints/' . $fileName;
        }

        $data = $action->store($dataInsert);

        return apiResponse(true, 'تم ارسال الشكوي بنجاح', $data, 200);
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
    public function edit($id, Request $request, ObjAction $action)
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
    public function update(Request $request, $id, ObjAction $action)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, ObjAction $action)
    {
        //
    }
}
