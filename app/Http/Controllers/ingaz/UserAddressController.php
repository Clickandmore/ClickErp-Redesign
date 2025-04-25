<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Actions\ingaz\UserAddressAction as objAction;
use App\Http\Controllers\Controller;
use App\Models\ingaz\UserAddress as objModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class UserAddressController extends Controller
{

       public $folderPath = "admin.ingaz.UserAddresss";
        public $postData = ["name"];
        public $mainRoute = "UserAddresss";

        /**
         * Display a listing of the resource.
         *
         * @return \Illuminate\Http\Response
         */
        public function index(Request $request, objAction $Action)
        {
            if ($request->ajax()) {
                $units = objModel::all();
                return DataTables::of($units)
                    ->addIndexColumn()
                    ->editColumn('is_show', function ($row) {
                        return
                            $row->is_show == 1 ? '
                        <button class="btn btn-sm btn-danger update-status" id="update-status" data-id="' . $row->id . '">اخفاء</button>' :
                                '<button class="btn btn-sm btn-success update-status" data-id="' . $row->id . '">اظهار </button>';
                    })
                    ->addColumn('actions', function ($row) {
                        $editButton = '';
                        $deleteButton = '';
                        // if (Auth::guard('admin')->user()->hasPermission('programs-update')) {
                            $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);
                        // }

                        // if (Auth::guard('admin')->user()->hasPermission('programs-delete')) {
                            $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));
                        // }
                        return $editButton . " " . $deleteButton;
                    })->rawColumns(['actions', 'is_show'])->make(true);
            }
            $data["createRoute"] = route($this->mainRoute . ".create");
            $data["dataTableRoute"] = route($this->mainRoute . ".index");
            $data["bladeTitle"] = __("auth.admins");
            $data["addButtonText"] = __("auth.admin");
            $data["modalType"] = "";
            return view($this->folderPath . '.index', $data);

        }

        /**
         * Show the form for creating a new resource.
         *
         * @return \Illuminate\Http\Response
         */
        public function create(Request $request)
        {
            if ($request->ajax()) {
                $returnHTML = view($this->folderPath . ".create")->render();
                return jsonSuccess(["html" => $returnHTML]);
            }
        }

        /**
         * Store a newly created resource in storage.
         *
         * @param \Illuminate\Http\Request $request
         * @return \Illuminate\Http\JsonResponse
         */
        public function store(Request $request, objAction $action)
        {

            $data = $request->only($this->postData);
            $data = $action->store($data);
            return jsonSuccess($data);
        }

        /**
         * Display the specified resource.
         *
         * @param int $id
         * @return \Illuminate\Http\Response
         */
        public function show($id)
        {

            $model = objModel::find($id);
            $model->is_show = !$model->is_show;
            $model->update();
            return response()->json(['success' => true]);
        }

        /**
         * Show the form for editing the specified resource.
         *
         * @param int $id
         * @return \Illuminate\Http\Response
         */
        public function edit(Request $request, $id)
        {
            if ($request->ajax()) {
                $unit = objModel::findorfail($id);
                $returnHTML = view($this->folderPath . '.edit')
                    ->with(['unit' => $unit])->render();
                return jsonSuccess(["html" => $returnHTML]);
            }
        }

        /**
         * Update the specified resource in storage.
         *
         * @param \Illuminate\Http\Request $request
         * @param int $id
         * @return \Illuminate\Http\Response
         */
        public function update(UserAddressControllerRequest $request, objAction $action, $id)
        {
            $unitData = $request->only($this->postData);
            $data = $action->update($id, $unitData);
            return jsonSuccess($data);

        }

        /**
         * Remove the specified resource from storage.
         *
         * @param int $id
         * @return \Illuminate\Http\Response
         */
        public function destroy($id, objAction $action)
        {
            $action->delete($id);
            return jsonSuccess();
        }
}
