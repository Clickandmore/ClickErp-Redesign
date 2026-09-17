<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Actions\ingaz\WhyUsAction as objAction;
use App\Http\Controllers\admin\Controller;
use App\Http\Requests\ingaz\WhyUsRequest as objRequest;
use App\Models\WhyUs as objModel;
use App\PermKeys;
use App\Support\AdminRoutePermissionMatrix;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

/**
 * بطاقات قسم "لماذا نحن" في الصفحة الرئيسية للموقع الخارجي.
 * الصلاحيات تُفرض من AdminRoutePermissionMatrix؛ هنا فقط لإظهار/إخفاء الأزرار.
 */
class WhyUsController extends Controller
{
    public $folderPath = 'admin.ingaz.why_us';
    public $mainRoute = 'why-us';

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $admin = auth('admin')->user();
            $canEdit = AdminRoutePermissionMatrix::adminHasAnyPermission($admin, [
                PermKeys::WHY_US_EDIT,
                PermKeys::WHY_US_CONTROL,
            ]);
            $canDelete = AdminRoutePermissionMatrix::adminHasAnyPermission($admin, [
                PermKeys::WHY_US_DELETE,
                PermKeys::WHY_US_CONTROL,
            ]);

            return DataTables::of(objModel::query()->ordered())
                ->addIndexColumn()
                ->editColumn('icon', fn ($row) => '<span class="d-inline-grid place-items-center rounded-3 bg-light p-2" style="width:38px;height:38px;place-items:center"><i class="' . e($row->icon) . '"></i></span>')
                ->editColumn('title', fn ($row) => e($row->title))
                ->editColumn('description', fn ($row) => '<span title="' . e($row->description) . '">' . e(Str::limit($row->description, 60)) . '</span>')
                ->editColumn('is_active', function ($row) use ($canEdit) {
                    $badge = $row->is_active
                        ? '<span class="badge bg-success">' . __('why_us.active') . '</span>'
                        : '<span class="badge bg-secondary">' . __('why_us.inactive') . '</span>';

                    if (!$canEdit) {
                        return $badge;
                    }

                    return '<a href="javascript:void(0)" class="why-us-toggle" data-url="'
                        . route($this->mainRoute . '.show', $row->id) . '" title="' . e(__('why_us.toggle_status')) . '">'
                        . $badge . '</a>';
                })
                ->addColumn('actions', function ($row) use ($canEdit, $canDelete) {
                    $buttons = [];

                    if ($canEdit) {
                        $buttons[] = editButton(route($this->mainRoute . '.edit', $row->id), $row->title);
                    }

                    if ($canDelete) {
                        $buttons[] = deleteButton(route($this->mainRoute . '.destroy', $row->id));
                    }

                    return $buttons ? implode(' ', $buttons) : '-';
                })
                ->escapeColumns([])
                ->make(true);
        }

        $data['createRoute'] = route($this->mainRoute . '.create');
        $data['dataTableRoute'] = route($this->mainRoute . '.index');
        $data['bladeTitle'] = helperTrans('api.why_us');
        $data['addButtonText'] = helperTrans('api.why_us');
        $data['modalType'] = 'modal-lg';
        $data['count'] = objModel::count();

        return view($this->folderPath . '.index', $data);
    }

    public function create(Request $request)
    {
        if ($request->ajax()) {
            $nextSortOrder = max(1, ((int) objModel::max('sort_order')) + 1);
            $returnHTML = view($this->folderPath . '.create', compact('nextSortOrder'))->render();

            return jsonSuccess(['html' => $returnHTML]);
        }
    }

    public function store(objRequest $request, objAction $action)
    {
        return jsonSuccess($action->store($request->validated()));
    }

    /** تبديل حالة التفعيل من الجدول */
    public function show($id)
    {
        $model = objModel::findOrFail($id);
        $model->is_active = !$model->is_active;
        $model->save();

        return response()->json(['success' => true, 'is_active' => $model->is_active]);
    }

    public function edit(Request $request, $id)
    {
        if ($request->ajax()) {
            $obj = objModel::findOrFail($id);
            $returnHTML = view($this->folderPath . '.edit', compact('obj'))->render();

            return jsonSuccess(['html' => $returnHTML]);
        }
    }

    public function update(objRequest $request, objAction $action, $id)
    {
        $model = $action->find($id);
        $model->update($request->validated());

        return jsonSuccess($model);
    }

    public function destroy($id, objAction $action)
    {
        $action->delete($id);

        return jsonSuccess();
    }
}
