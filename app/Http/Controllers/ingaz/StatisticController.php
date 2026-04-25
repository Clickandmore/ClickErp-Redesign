<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Actions\ingaz\StatisticAction as objAction;
use App\Http\Controllers\admin\Controller;
use App\Http\Requests\ingaz\StatisticRequest;
use App\Models\ingaz\Statistic as objModel;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class StatisticController extends Controller
{

       public $folderPath = "admin.ingaz.statistics";
        public $postData = ["title:ar","title:en","counter_num","image","counter_unit"];
        public $mainRoute = "statistics";

    function __construct()
    {
        // $this->middleware('permission:الإحصائيات-قراءة,admin', ['only' => ['index']]);
        // $this->middleware('permission:الإحصائيات-انشاء,admin', ['only' => ['create', 'store']]);
        // $this->middleware('permission:الإحصائيات-تعديل,admin', ['only' => ['edit', 'update']]);
        // $this->middleware('permission:الإحصائيات-حذف,admin', ['only' => ['destroy']]);
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
                    ->editColumn('image', function ($row) {
                        return "<i class='fs-2 ".$row->image."'></i>";
                    })
                    ->addColumn('actions', function ($row) {
                        $editButton = '';
                        $deleteButton = '';
//                        if (@AdminGuardHasPerm(\App\PermKeys::STAT_CONTROL)) {
                            $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);
//                        }

//                        if (@AdminGuardHasPerm(\App\PermKeys::STAT_DELETE)) {
                            $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));
//                        }
                        return $editButton . " " . $deleteButton;
                    })->escapeColumns([])->make(true);
            }
            $data["createRoute"] = route($this->mainRoute . ".create");
            $data["dataTableRoute"] = route($this->mainRoute . ".index");
            $data["bladeTitle"] = __("auth.admins");
            $data["addButtonText"] = __("auth.admin");
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
        public function store(StatisticRequest $request, objAction $action)
        {

            $data = $request->only($this->postData);
            $data = $action->storeStatistic($data);
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
                $obj = objModel::findorfail($id);
                $returnHTML = view($this->folderPath . '.edit')
                    ->with(['obj' => $obj])->render();
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
        public function update(StatisticRequest $request, objAction $action, $id)
        {
            $unitData = $request->only($this->postData);
            $data = $action->updateStatistic($id, $unitData);
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
