<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Actions\ingaz\NationalityAction;
use App\Http\Actions\ingaz\PackageAction as objAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ingaz\PackageRequest;
use App\Models\ingaz\Package as objModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class PackageController extends Controller
{

    public $folderPath = "admin.ingaz.packages";
    public $postData = ["original_price", "price_after_discount",'nationality_id', "price_with_tax", "days_count","title:ar","title:en"];
    public $mainRoute = "packages";

    function __construct()
    {
        // $this->middleware('permission:الباقات-قراءة,admin', ['only' => ['index']]);
        // $this->middleware('permission:الباقات-انشاء,admin', ['only' => ['create', 'store']]);
        // $this->middleware('permission:الباقات-تعديل,admin', ['only' => ['edit', 'update']]);
        // $this->middleware('permission:الباقات-حذف,admin', ['only' => ['destroy']]);
    }


    public function index(Request $request, objAction $Action)
    {
        if ($request->ajax()) {
            $rows = objModel::latest();
            return DataTables::of($rows)
                ->addIndexColumn()
                ->addColumn('actions', function ($row) {
                    $editButton = '';
                    $deleteButton = '';
                    if (@AdminGuardHasPerm(\App\PermKeys::PACKAGES_CONTROL)) {
                        $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);
                    }

                    if (@AdminGuardHasPerm(\App\PermKeys::PACKAGES_DELETE)) {
                        $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    }
                    return $editButton . " " . $deleteButton;
                })->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = helperTrans("ingaz.packages");
        $data["addButtonText"] = helperTrans("ingaz.packages");
        $data["modalType"] = "";
        return view($this->folderPath . '.index', $data);

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request,NationalityAction $act)
    {
        if ($request->ajax()) {
            $data['nationalities'] = $act->get();
            $returnHTML = view($this->folderPath . ".create",$data)->render();
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
    public function edit(Request $request,NationalityAction $act, $id)
    {
        if ($request->ajax()) {
            $data['obj'] = objModel::findorfail($id);
            $data['nationalities'] = $act->get();
            $returnHTML = view($this->folderPath . '.edit')
                ->with($data)->render();
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
    public function update(PackageRequest $request, objAction $action, $id)
    {
        $updatedData = $request->only($this->postData);
        $data = $action->updatePackage($id, $updatedData);
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
