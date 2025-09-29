<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Actions\ingaz\SocialStatusAction as objAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ingaz\SocialStatusRequest;
use App\Models\ingaz\SocialStatus as objModel;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class SocialStatusController extends Controller
{
    public $folderPath = "admin.ingaz.social_status";
    public $postData = ["title:ar", "title:en"];
    public $mainRoute = "social-statuses";

    function __construct()
    {
        // $this->middleware('permission:social-status-قراءة,admin', ['only' => ['index']]);
        // $this->middleware('permission:social-status-انشاء,admin', ['only' => ['create', 'store']]);
        // $this->middleware('permission:social-status-تعديل,admin', ['only' => ['edit', 'update']]);
        // $this->middleware('permission:social-status-حذف,admin', ['only' => ['destroy']]);
    }

    public function index(Request $request, objAction $Action)
    {
        // Debug: تحقق من ajax request
        if ($request->ajax()) {
            try {
                $rows = objModel::query()->latest();
                return DataTables::of($rows)
                    ->addIndexColumn()
                    ->editColumn('is_show', function ($row) {
                        return
                            $row->is_show == 1 ? '
                            <button class="btn btn-sm btn-success update-status" id="update-status" data-id="' . $row->id . '">ظاهر</button>' :
                                '<button class="btn btn-sm btn-dark update-status" data-id="' . $row->id . '">مخفي </button>';
                    })
                    ->addColumn('actions', function ($row) {
                        $editButton = '';
                        $deleteButton = '';
                        // استخدام MAIDS permissions مؤقتاً
                        if (true) { // @AdminGuardHasPerm(\App\PermKeys::MAIDS_CONTROL)
                            $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->title);
                        }

                        if (true) { // @AdminGuardHasPerm(\App\PermKeys::MAIDS_DELETE)
                            $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));
                        }
                        return $editButton . " " . $deleteButton;
                    })->escapeColumns([])->make(true);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()]);
            }
        }

        // Debug: تحقق من الوصول للصفحة
        try {
            $data["createRoute"] = route($this->mainRoute . ".create");
            $data["dataTableRoute"] = route($this->mainRoute . ".index");
            $data["bladeTitle"] = "الحالات الاجتماعية";
            $data["addButtonText"] = "حالة اجتماعية";
            $data["modalType"] = "modal-lg";

            return view($this->folderPath . '.index', $data);
        } catch (\Exception $e) {
            return response("خطأ في تحميل الصفحة: " . $e->getMessage(), 500);
        }
    }

    public function create(Request $request)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".create")->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    public function store(SocialStatusRequest $request, objAction $action)
    {
        $data = $request->only($this->postData);
        $data = $action->store($data);
        return jsonSuccess($data);
    }

    public function show($id)
    {
        $model = objModel::find($id);
        $model->is_show = !$model->is_show;
        $model->update();
        return response()->json(['success' => true]);
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

    public function update(SocialStatusRequest $request, objAction $action, $id)
    {
        $updatedData = $request->only($this->postData);
        $data = $action->find($id)->update($updatedData);
        return jsonSuccess($data);
    }

    public function destroy($id, objAction $action)
    {
        $action->delete($id);
        return jsonSuccess();
    }
}
