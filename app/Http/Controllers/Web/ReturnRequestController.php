<?php

namespace App\Http\Controllers\Web;

use App\Http\Actions\ingaz\ReturnRequestAction as objAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ingaz\ComplaintRequest;
use App\Http\Requests\ingaz\ReturnRequestRequest;
use App\Models\ingaz\Complaint as objModel;
use App\Models\ingaz\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ReturnRequestController extends Controller
{
       public $postData = ['maid_id','notes','order_id','file'];
       public $folderPath = "web.ingaz.return_requests";

        /**
         * Display a listing of the resource.
         *
         * @return \Illuminate\Http\Response
         */
        public function index(Request $request, objAction $action)
        {
            //
        }

        /**
         * Show the form for creating a new resource.
         *
         * @return \Illuminate\Http\Response
         */
        public function create(Request $request)
        {
            if ($request->ajax()) {
                $data['order'] = Order::find(request('order_id'));
                $returnHTML = view($this->folderPath . ".create")->with($data)->render();
                return jsonSuccess(["html" => $returnHTML]);
            }
        }

        /**
         * Store a newly created resource in storage.
         *
         * @param  \Illuminate\Http\Request  $request
         * @return \Illuminate\Http\Response
         */
        public function store(ReturnRequestRequest $request, objAction $action)
        {
            $dataInsert = $request->only($this->postData);
            $dataInsert['user_id'] = getGuardedUser()->id;
            $action->storeRequest($dataInsert);
            return jsonSuccess(null,'تم ارسال الطلب بنجاح');
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
