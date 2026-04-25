<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Actions\ingaz\SliderAction as objAction;
use App\Http\Controllers\admin\Controller;
use App\Http\Requests\ingaz\SliderRequest;
use App\Models\ingaz\Slider as objModel;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class SliderController extends Controller
{
    public $folderPath = "admin.ingaz.sliders";
    public $postData = ["title:ar", "title:en", "description:ar", "description:en", "image"];
    public $mainRoute = "sliders";

    public function index(Request $request, objAction $Action)
    {
        if ($request->ajax()) {
            $rows = objModel::query()->latest();
            return DataTables::of($rows)
                ->addIndexColumn()
                ->editColumn('is_show', function ($row) {
                    return
                        $row->is_show == 1 ? '
                        <button class="btn btn-sm btn-danger update-status" id="update-status" data-id="' . $row->id . '">اخفاء</button>' :
                            '<button class="btn btn-sm btn-success update-status" data-id="' . $row->id . '">اظهار </button>';
                })
                ->editColumn('image', function ($row) {
                    return getImgTag($row->image);
                })
                ->addColumn('actions', function ($row) {
                    $editButton = '';
                    $deleteButton = '';
                    if (@AdminGuardHasPerm(\App\PermKeys::SLIDERS_CONTROL)) {
                        $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->title);
                    }

                    if (@AdminGuardHasPerm(\App\PermKeys::SLIDERS_DELETE)) {
                        $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    }
                    return $editButton . " " . $deleteButton;
                })->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = helperTrans('api.sliders');
        $data["addButtonText"] = helperTrans('api.slider');
        $data["modalType"] = "modal-lg";
        return view($this->folderPath . '.index', $data);

    }


    public function create(Request $request)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".create")->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }


    public function store(SliderRequest $request, objAction $action)
    {

        $data = $request->only($this->postData);
        $data = $action->store($data);
        return jsonSuccess($data);
    }


    public function edit(Request $request, $id)
    {
        if ($request->ajax()) {
            $obj = objModel::findorfail($id);
            $returnHTML = view($this->folderPath . '.edit')
                ->with(['obj' => $obj])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    public function update(SliderRequest $request, objAction $action, $id)
    {
        $updatedData = $request->only($this->postData);
        $data = $action->updateSlider($id, $updatedData);
        return jsonSuccess($data);
    }

    public function destroy($id, objAction $action)
    {
        $action->deleteWithFile($id, 'image');
        return jsonSuccess();
    }
}
