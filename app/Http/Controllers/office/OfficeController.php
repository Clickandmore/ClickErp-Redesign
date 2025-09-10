<?php

namespace App\Http\Controllers\office;

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
            $units = objModel::all();
            return DataTables::of($units)
                ->addIndexColumn()
                ->editColumn('nationality_id', function ($row) {
                    return $row->nationality->title ?? '';
                })
                ->editColumn('license_number', function ($row) {
                    return $row->license_number ?? '';
                })
                ->editColumn('office_number', function ($row) {
                    return $row->office_number ?? '';
                })
                ->editColumn('title', function ($row) {
                    return $row->title ?? '';
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
                })->rawColumns(['actions', 'license_number','title', 'nationality_id', 'office_number'])->make(true);
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
    public function store(OfficeRequest           $request, objAction $action, JopTypeOfficeAction $jopTypeOfficeAction,
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
        $adminOfficeAction->store([
            'office_id' => $office->id,
            'admin_id' => Auth::guard('admin')->user()->id,
        ]);
        $adminAction->store([
            'name' => $data['manager_name'],
            'phone' => $data['manager_phone'],
            'email' => $data['manager_email'],
            'password' => Hash::make($data['password']),
        ]);
        return jsonSuccess($data, null, 201);
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
    public function update(JobTypeControllerRequest $request, objAction $action, $id)
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
