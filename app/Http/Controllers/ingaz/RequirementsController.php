<?php

namespace App\Http\Controllers\ingaz;

use App\PermKeys;
use App\Enums\ingaz\RequirementEnum;
use App\Http\Actions\ingaz\RequirementAction as objAction;
use App\Http\Controllers\admin\Controller;
use App\Http\Requests\ingaz\RequirementRequest as objRequest;
use App\Models\Requirement as objModel;
use App\Models\RequirementItem;
use App\Support\AdminRoutePermissionMatrix;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class RequirementsController extends Controller
{
    public $folderPath = 'admin.ingaz.requiremens';
    public $mainRoute = 'requiremens';

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $rows = objModel::query()
                ->withCount('items')
                ->orderBy('sort_order')
                ->orderBy('id');

            return DataTables::of($rows)
                ->addIndexColumn()
                ->editColumn('title', fn ($row) => e($row->title))
                ->editColumn('type', function ($row) {
                    $enum = collect(RequirementEnum::cases())
                        ->first(fn ($case) => $case->value === $row->type);

                    return $enum
                        ? helperTrans('ingaz.' . $enum->name)
                        : e($row->type);
                })
                ->editColumn('is_active', function ($row) {
                    return $row->is_active
                        ? '<span class="badge bg-success">' . __('recruitment_requirements.active') . '</span>'
                        : '<span class="badge bg-secondary">' . __('recruitment_requirements.inactive') . '</span>';
                })
                ->addColumn('actions', function ($row) {
                    $admin = auth('admin')->user();
                    $buttons = [];

                    if (AdminRoutePermissionMatrix::adminHasAnyPermission($admin, [
                        PermKeys::RECRUITMENT_REQUIREMENTS_EDIT,
                        PermKeys::RECRUITMENT_REQUIREMENTS_CONTROL,
                    ])) {
                        $buttons[] = editButton(
                            route($this->mainRoute . '.edit', $row->id),
                            $row->title
                        );
                    }

                    if (AdminRoutePermissionMatrix::adminHasAnyPermission($admin, [
                        PermKeys::RECRUITMENT_REQUIREMENTS_DELETE,
                        PermKeys::RECRUITMENT_REQUIREMENTS_CONTROL,
                    ])) {
                        $buttons[] = deleteButton(
                            route($this->mainRoute . '.destroy', $row->id)
                        );
                    }

                    return $buttons ? implode(' ', $buttons) : '-';
                })
                ->escapeColumns([])
                ->make(true);
        }

        $data['createRoute'] = route($this->mainRoute . '.create');
        $data['dataTableRoute'] = route($this->mainRoute . '.index');
        $data['bladeTitle'] = helperTrans('api.Recruitment requirements');
        $data['addButtonText'] = helperTrans('api.Recruitment requirements');
        $data['count'] = objModel::count();

        return view($this->folderPath . '.index', compact('data'));
    }

    public function create(Request $request)
    {
        if ($request->ajax()) {
            $enums = RequirementEnum::cases();
            $nextSortOrder = max(1, ((int) objModel::max('sort_order')) + 1);
            $returnHTML = view(
                $this->folderPath . '.create',
                compact('enums', 'nextSortOrder')
            )->render();

            return jsonSuccess(['html' => $returnHTML]);
        }
    }

    public function store(objRequest $request, objAction $action)
    {
        $validated = $request->validated();
        $points = $validated['points'];
        unset($validated['points']);

        $requirement = DB::transaction(function () use ($action, $validated, $points) {
            $requirement = $action->store($validated);
            $this->syncPoints($requirement, $points);

            return $requirement;
        });

        return jsonSuccess($requirement->load('items'));
    }

    public function edit(Request $request, $id)
    {
        if ($request->ajax()) {
            $enums = RequirementEnum::cases();
            $obj = objModel::with('items')->findOrFail($id);
            $returnHTML = view(
                $this->folderPath . '.edit',
                compact('obj', 'enums')
            )->render();

            return jsonSuccess(['html' => $returnHTML]);
        }
    }

    public function update(objRequest $request, objAction $action, $id)
    {
        $validated = $request->validated();
        $points = $validated['points'];
        unset($validated['points']);

        $requirement = DB::transaction(function () use ($action, $id, $validated, $points) {
            $requirement = $action->find($id);
            $requirement->update($validated);
            $this->syncPoints($requirement, $points);

            return $requirement;
        });

        return jsonSuccess($requirement->load('items'));
    }

    public function destroy($id, objAction $action)
    {
        $requirement = $action->find($id);

        if ($requirement?->image) {
            deleteFile($requirement->image);
        }

        $action->delete($id);

        return jsonSuccess();
    }

    private function syncPoints(objModel $requirement, array $points): void
    {
        $keptIds = [];

        foreach (array_values($points) as $index => $point) {
            $item = !empty($point['id'])
                ? $requirement->items()->findOrFail($point['id'])
                : new RequirementItem(['requirement_id' => $requirement->id]);

            $item->requirement_id = $requirement->id;
            $item->icon = $point['icon'] ?? null;
            $item->sort_order = $point['sort_order'] ?? ($index + 1);
            $item->translateOrNew('ar')->text = $point['ar']['text'];
            $item->translateOrNew('ar')->description = $point['ar']['description'] ?? null;
            $item->translateOrNew('en')->text = $point['en']['text'];
            $item->translateOrNew('en')->description = $point['en']['description'] ?? null;
            $item->save();

            $keptIds[] = $item->id;
        }

        $requirement->items()
            ->whereNotIn('id', $keptIds)
            ->delete();
    }
}
