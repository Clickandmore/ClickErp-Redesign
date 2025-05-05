<?php

namespace App\Http\Controllers\Web;

use App\Http\Actions\ingaz\ComplaintAction as objAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ingaz\ComplaintRequest;
use App\Models\ingaz\Complaint as objModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ComplaintController extends Controller
{
       public $postData = ['address','message'];

        /**
         * Display a listing of the resource.
         *
         * @return \Illuminate\Http\Response
         */
        public function index(Request $request, objAction $action)
        {
            $complaints = objModel::where('user_id', getGuardedUser()->id)->latest()->get();
            $complaintTypes = \App\Models\ingaz\ComplaintType::all();
            return view('web.ingaz.complaints.index', compact('complaints', 'complaintTypes'));
        }

        /**
         * Show the form for creating a new resource.
         *
         * @return \Illuminate\Http\Response
         */
        public function create(Request $request)
        {
            $complaintTypes = \App\Models\ingaz\ComplaintType::all();
            return view('web.ingaz.complaints.create', compact('complaintTypes'));
        }

        /**
         * Store a newly created resource in storage.
         *
         * @param  \Illuminate\Http\Request  $request
         * @return \Illuminate\Http\Response
         */
        public function store(ComplaintRequest $request, objAction $action)
        {
            $dataInsert = $request->only(array_merge($this->postData, ['complaint_type_id']));
            $dataInsert['user_id'] = getGuardedUser()->id;
            $data = $action->store($dataInsert);
            return jsonSuccess(null,'تم ارسال الشكوي بنجاح');
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
