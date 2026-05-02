<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Actions\ingaz\ShelterPlaceAction as objAction;
use App\Http\Controllers\admin\Controller;
use App\Http\Requests\ingaz\ShelterPlaceRequest as objRequest;
use App\Models\auth\Admin;
use App\Models\ShelterPlaces as objModel;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ShelterPlaces extends Controller
{
    public $folderPath = "admin.ingaz.shelter-places";
    public $postData = [
        'title:ar',
        'title:en',
        'female_capacity',
        'male_capacity',
        'address',
        'map_url',
        'latitude',
        'longitude',
        'supervisor_admin_id',
        'responsible_admin_id',
        'notes',
    ];
    public $mainRoute = "shelter-places";

    function __construct()
    {
        $this->middleware('permission:shelter_places_view|shelter_places_control,admin', ['only' => ['index']]);
        $this->middleware('permission:shelter_places_create|shelter_places_control,admin', ['only' => ['create', 'store']]);
        $this->middleware('permission:shelter_places_edit|shelter_places_control,admin', ['only' => ['edit', 'update']]);
        $this->middleware('permission:shelter_places_delete|shelter_places_control,admin', ['only' => ['destroy']]);
    }


    public function index(Request $request)
    {
        if ($request->ajax()) {
            $rows = objModel::query()
                ->with(['supervisor', 'responsible'])
                ->withCount([
                    'contractInfos as current_occupancy_count' => function ($query) {
                        $query->whereHas('contract', function ($contractQuery) {
                            $contractQuery->insideShelter();
                        });
                    },
                ])
                ->latest();
            return DataTables::of($rows)
                ->addIndexColumn()
                ->addColumn('capacity', function ($row) {
                    $femaleCapacity = $row->female_capacity !== null ? number_format($row->female_capacity) : '-';
                    $maleCapacity = $row->male_capacity !== null ? number_format($row->male_capacity) : '-';

                    return '<div class="small">'
                        . '<div><strong>' . helperTrans('ingaz.female_capacity') . ':</strong> ' . $femaleCapacity . '</div>'
                        . '<div><strong>' . helperTrans('ingaz.male_capacity') . ':</strong> ' . $maleCapacity . '</div>'
                        . '</div>';
                })
                ->addColumn('current_occupancy', function ($row) {
                    $count = (int) $row->current_occupancy_count;
                    $capacity = $row->female_capacity;
                    $badgeClass = ($capacity !== null && $capacity > 0 && $count >= $capacity) ? 'bg-danger' : 'bg-success';

                    return '<span class="badge ' . $badgeClass . '">' . number_format($count) . '</span>';
                })
                ->addColumn('location', function ($row) {
                    $parts = [];
                    if ($row->address) {
                        $parts[] = e($row->address);
                    }

                    if ($row->map_url) {
                        $parts[] = '<a href="' . e($row->map_url) . '" target="_blank" rel="noopener">'
                            . helperTrans('ingaz.open_map') . '</a>';
                    } elseif ($row->latitude !== null && $row->longitude !== null) {
                        $mapUrl = 'https://www.google.com/maps?q=' . $row->latitude . ',' . $row->longitude;
                        $parts[] = '<a href="' . e($mapUrl) . '" target="_blank" rel="noopener">'
                            . helperTrans('ingaz.open_map') . '</a>';
                    }

                    return $parts ? implode('<br>', $parts) : '-';
                })
                ->addColumn('management', function ($row) {
                    $supervisor = $row->supervisor?->name ?: '-';
                    $responsible = $row->responsible?->name ?: '-';

                    return '<div class="small">'
                        . '<div><strong>' . helperTrans('ingaz.shelter_supervisor') . ':</strong> ' . e($supervisor) . '</div>'
                        . '<div><strong>' . helperTrans('ingaz.shelter_responsible') . ':</strong> ' . e($responsible) . '</div>'
                        . '</div>';
                })
                ->editColumn('created_at', function ($row) {
                    return ($row->created_at->format('Y-m-d h:i A'));
                })
                ->addColumn('actions', function ($row) {
                    $editButton = '';
                    $deleteButton = '';
                    if (@AdminGuardHasPerm(\App\PermKeys::SHELTER_PLACES_CONTROL)) {
                        $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);
                    }

                    if (@AdminGuardHasPerm(\App\PermKeys::SHELTER_PLACES_DELETE)) {
                        $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    }
                    return $editButton . " " . $deleteButton;
                })->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = helperTrans("ingaz.shelter_places");
        $data["addButtonText"] = helperTrans("ingaz.shelter_places");
        $data["modalType"] = "";
        return view($this->folderPath . '.index', $data);

    }

    public function create(Request $request)
    {
        if ($request->ajax()) {
            $admins = $this->adminOptions();
            $returnHTML = view($this->folderPath . ".create", compact('admins'))->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }


    public function store(objRequest $request, objAction $action)
    {
        $data = $request->only($this->postData);
        $data = $action->store($data);
        return jsonSuccess($data);
    }


    public function edit(Request $request, $id)
    {
        if ($request->ajax()) {
            $obj = objModel::findorfail($id);
            $admins = $this->adminOptions($obj);
            $returnHTML = view($this->folderPath . '.edit')
                ->with(['obj' => $obj, 'admins' => $admins])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    public function update(objRequest $request, objAction $action, $id)
    {
        $updatedData = $request->only($this->postData);
        $data = $action->updateRec($id, $updatedData);
        return jsonSuccess($data);
    }

    public function destroy($id, objAction $action)
    {
        $action->deleteWithFile($id, 'image');
        return jsonSuccess();
    }

    private function adminOptions(?objModel $shelterPlace = null)
    {
        $selectedIds = collect([
            $shelterPlace?->supervisor_admin_id,
            $shelterPlace?->responsible_admin_id,
        ])->filter()->values()->all();

        return Admin::withTrashed()
            ->select('id', 'name', 'phone')
            ->where(function ($query) use ($selectedIds) {
                $query->whereNull('deleted_at');

                if ($selectedIds) {
                    $query->orWhereIn('id', $selectedIds);
                }
            })
            ->orderByRaw('CASE WHEN deleted_at IS NULL AND status = "active" THEN 0 ELSE 1 END')
            ->orderBy('name')
            ->get();
    }
}
