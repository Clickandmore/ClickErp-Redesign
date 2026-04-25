<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Actions\ingaz\StepAction as objAction;
use App\Http\Controllers\admin\Controller;
use App\Http\Requests\ingaz\StepRequest;
use App\Models\ingaz\Step as objModel;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class StepController extends Controller
{

       public $folderPath = "admin.ingaz.steps";
        public $postData = ["title:ar","title:en","icon"];
        public $mainRoute = "steps";

    function __construct()
    {
        // $this->middleware('permission:الخطوات-قراءة,admin', ['only' => ['index']]);
        // $this->middleware('permission:الخطوات-انشاء,admin', ['only' => ['create', 'store']]);
        // $this->middleware('permission:الخطوات-تعديل,admin', ['only' => ['edit', 'update']]);
        // $this->middleware('permission:الخطوات-حذف,admin', ['only' => ['destroy']]);
    }
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
                    ->editColumn('icon', function ($row) {
                        return $row->icon;
                    })
                    ->addColumn('actions', function ($row) {
                        $editButton = '';
                        $deleteButton = '';
                        if (@AdminGuardHasPerm(\App\PermKeys::STEPS_CONTROL)) {
                            $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);
                        }

                        if (@AdminGuardHasPerm(\App\PermKeys::STEPS_DELETE)) {
                            $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));
                        }
                        return $editButton . " " . $deleteButton;
                    })->escapeColumns([])->make(true);
            }
            $data["createRoute"] = route($this->mainRoute . ".create");
            $data["dataTableRoute"] = route($this->mainRoute . ".index");
            $data["bladeTitle"] = helperTrans("ingaz.steps");
            $data["addButtonText"] = helperTrans("ingaz.steps");
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
        public function store(StepRequest $request, objAction $action)
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
                    ->with(['obj' => $unit])->render();
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
        public function update(StepRequest $request, objAction $action, $id)
        {
            $unitData = $request->only($this->postData);
            $data = $action->updateStep($id, $unitData);
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
