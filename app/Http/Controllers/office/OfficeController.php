<?php

namespace App\Http\Controllers\office;

use App\Enums\Office\OfficesStatusEnum;
use App\Http\Actions\auth\AdminAction;
use App\Http\Actions\ingaz\JobTitleAction;
use App\Http\Actions\ingaz\JobTypeAction;
use App\Http\Actions\ingaz\NationalityAction;
use App\Http\Actions\office\AdminOfficeAction;
use App\Http\Actions\office\JopTypeOfficeAction;
use App\Http\Actions\office\OfficeAction as objAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\office\OfficeRequest;
use App\Models\office\Office as objModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

//use function App\Http\Controllers\ingaz\__;
//use function App\Http\Controllers\ingaz\response;
//use function App\Http\Controllers\ingaz\route;
//use function App\Http\Controllers\ingaz\view;

class OfficeController extends Controller
{

    public $folderPath = "admin.office.";
    public $postData = ["title:ar", "title:en", "phone_office", "license_number",
        "nationality_id", "street", "manager_name", "manager_phone",
        "manager_email", "password", "bank_address", "bank_account", "bank_account_name",
        "bank_swift_code", "bank_iban", "cost_of_dollar", "cost_of_other", 'job_title_id'];
    public $mainRoute = "offices";

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, objAction $Action)
    {
        if ($request->ajax()) {
            $offices = objModel::query()->latest('id');
            return DataTables::of($offices)
                ->addIndexColumn()
                ->editColumn('nationality_id', function ($row) {
                    return $row->nationality->title ?? '';
                })
                ->editColumn('manager_email', function ($row) {
                    return $row->manager_email ?? '';
                })
                ->editColumn('title', function ($row) {
                    return Str::limit($row->title, 20, '...') ?? '';
                })
                ->addColumn('office_type', function ($row) {
                    return $row->jobTypes()
                        ->with(['translations' => fn($q) => $q->where('locale', app()->getLocale())])
                        ->get()
                        ->map(fn($jt) => optional($jt->translations->first())->title)
                        ->implode(', ');
                })
                ->addColumn('status', function ($row) {
                    if ($row->status == OfficesStatusEnum::Active->value) {
                        return '<button type="button"
                        class="btn btn-sm btn-success toggle-status"
                        data-action="active"
                        data-id="' . $row->id . '">'
                            . __('ingaz.active') . '
                    </button>';
                    } else {
                        return '<button type="button"
                        class="btn btn-sm btn-danger toggle-status"
                        data-action="dis_active"
                        data-id="' . $row->id . '">'
                            . __('ingaz.dis_active') . '
                    </button>';
                    }
                })
                ->addColumn('actions', function ($row) {
                    $editButton = '';
                    $deleteButton = '';
                    $fileButton = '';
                    // if (Auth::guard('admin')->user()->hasPermission('programs-update')) {
                    $editButton = '<a href="' . route('offices.edit', $row->id) . '" class="btn btn-primary">
                                    <i class="fa fa-pencil"></i>
                                   </a>';
                    // }
                    // if (Auth::guard('admin')->user()->hasPermission('programs-delete')) {
                    $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    // }
                    if (isset($row->file)) {
                        $fileButton = '<a href="' . route('offices-file.edit', @$row->file->id) . '" class="btn btn-sm btn-dark mx-1">
                                    <i class="fa fa-plus"></i> ' . __("ingaz.add_office_file") . '
                                   </a>';
                    } else {
                        $fileButton = '<a href="' . route('offices-file.create', ['office_id' => $row->id]) . '" class="btn btn-sm btn-dark mx-1">
                                    <i class="fa fa-plus"></i> ' . __("ingaz.add_office_file") . '
                                   </a>';
                    }

                    return $fileButton . " " . $editButton . " " . $deleteButton;
                })
                ->rawColumns(['actions', 'license_number', 'title', 'nationality_id', 'office_number', 'status'])->make(true);
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
    public function create(Request $request, NationalityAction $nationalityAction, JobTypeAction $jobTypeAction)
    {
        $nationalities = $nationalityAction->get();
        $jobTypes = $jobTypeAction->get();
        return view($this->folderPath . ".create", compact('nationalities', 'jobTypes'));

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(OfficeRequest     $request, objAction $action, JopTypeOfficeAction $jopTypeOfficeAction,
                          AdminOfficeAction $adminOfficeAction, AdminAction $adminAction)
    {
        $data = $request->only($this->postData);
        $jobTitleIds = $data['job_title_id'] ?? [];
        unset($data['job_title_id']);
        $office = $action->store($data);
        foreach ($jobTitleIds as $jobTitleId) {
            $jopTypeOfficeAction->store([
                'office_id' => $office->id,
                'jop_type_id' => $jobTitleId,
            ]);
        }
        $admin = $adminAction->store([
            'name' => $data['manager_name'],
            'phone' => $data['manager_phone'],
            'email' => $data['manager_email'],
            'password' => Hash::make($data['password']),
        ]);
        $adminOfficeAction->store([
            'office_id' => $office->id,
            'admin_id' => $admin->id,
        ]);
        return jsonSuccess(['url' => route('offices.index')], null, 201);
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
    public function edit(Request $request, $id, NationalityAction $nationalityAction, JobTypeAction $jobTypeAction)
    {
        $office = objModel::findorfail($id);
        $nationalities = $nationalityAction->get();
        $jobTypes = $jobTypeAction->get();
        return view($this->folderPath . ".edit", compact('nationalities', 'jobTypes', 'office'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(OfficeRequest $request, objAction $action, $id, AdminAction $adminAction, AdminOfficeAction $adminOfficeAction)
    {
        $data = $request->only($this->postData);
        $obj = $action->find($id);
        if (isset($obj->officeManager->admin_id)) {
            $admin = $adminAction->find($obj->officeManager->admin_id);
            $adminData['name'] = $data['manager_name'];
            $adminData['phone'] = $data['manager_phone'];
            $adminData['email'] = $data['manager_email'];
            $adminData['password'] = Hash::make($data['password']);
            $admin->update($adminData);
        } else {
            $admin = $adminAction->store([
                'name' => $data['manager_name'],
                'phone' => $data['manager_phone'],
                'email' => $data['manager_email'],
                'password' => Hash::make($data['password']),
            ]);
            $adminOfficeAction->store([
                'office_id' => $id,
                'admin_id' => $admin->id,
            ]);
        }

        $jobTitleIds = $data['job_title_id'] ?? [];
        unset($data['job_title_id']);


        $obj->update($data);
        $obj->jobTypes()->sync($jobTitleIds);
        return jsonSuccess(['url' => route('offices.index')], null, 201);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, objAction $action, AdminAction $adminAction)
    {
        $obj = $action->find($id);
        if (isset($obj->officeManager->admin_id)) {
            $admin = $adminAction->find($obj->officeManager->admin_id);
            $adminAction->delete($admin->id);
        }
        $action->delete($id);
        return jsonSuccess();
    }
}
