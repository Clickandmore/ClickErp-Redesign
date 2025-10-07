<?php

namespace App\Http\Controllers\ingaz;

use App\Enums\ingaz\RequestsStatusEnum;
use App\Http\Actions\ingaz\JobTitleAction;
use App\Http\Actions\ingaz\ProfessionalApplicationAction as objAction;
use App\Http\Controllers\Controller;
use App\Models\ProfessionalApplication as objModel;
use App\Http\Requests\ingaz\ProfessionalApplicationRequest as objRequest;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ProfessionalApplicationController extends Controller
{

    public $folderPath = "admin.ingaz.ProfessionalApplication";
    public $postData = ["image"];
    public $mainRoute = "professional_application";

    function __construct()
    {
        // $this->middleware('permission:عنا-قراءة,admin', ['only' => ['index']]);
        // $this->middleware('permission:عنا-انشاء,admin', ['only' => ['create', 'store']]);
        // $this->middleware('permission:عنا-تعديل,admin', ['only' => ['edit', 'update']]);
        // $this->middleware('permission:عنا-حذف,admin', ['only' => ['destroy']]);
    }

    public function index(Request $request, objAction $Action, JobTitleAction $jobTitleAction)
    {
        if ($request->ajax()) {
            $rows = objModel::query()
                ->when($request->filled('status'), function ($query) use ($request) {
                    $query->where('status', $request->get('status'));
                })
                ->when($request->filled('jobTitle'), function ($query) use ($request) {
                    $query->where('job_title_id', $request->get('jobTitle'));
                })
                ->latest();
            return DataTables::of($rows)
                ->addIndexColumn()
                ->editColumn('facility_name', function ($row) {
                    return $row->facility_name;
                })
                ->editColumn('contact_number', function ($row) {
                    return $row->phone;
                })
                ->editColumn('type_of_labor_required', function ($row) {
                    return $row->jobtitle?->title;
                })
                ->editColumn('visa_number', function ($row) {
                    return $row->visa_number;
                })
                ->editColumn('number_of_workers_required', function ($row) {
                    return $row->number_of_workers_required;
                })
                ->editColumn('status', function ($row) {
                    if ($row->status == RequestsStatusEnum::Pending->value) {
                        return '
                        <div class="btn-group" role="group">
                            <button class="btn btn-success btn-sm accept-request" data-id="' . $row->id . '" data-store-id="' . @$row->id . '">
                                <i class="fas fa-check"></i> ' . __('ingaz.approved') . '
                            </button>
                            <button class="btn btn-danger btn-sm reject-request mx-2" data-id="' . $row->id . '" data-store-id="' . @$row->id . '">
                                <i class="fas fa-times"></i> ' . __('ingaz.rejected') . '
                            </button>
                        </div>';
                    } else if ($row->status == RequestsStatusEnum::Approved->value) {
                        return '<span class="badge bg-success" > ' . RequestsStatusEnum::tryFrom($row->status)->lang() . '</span>';

                    } else if ($row->status == RequestsStatusEnum::Rejected->value) {
                        return '<span class="badge bg-danger" >' . RequestsStatusEnum::tryFrom($row->status)->lang() . '</span>';
                    }

                })
                ->addColumn('actions', function ($row) {
                    // if (@AdminGuardHasPerm(\App\PermKeys::PARTNER_DELETE)) {
                    $deleteButton = ' <button class="dropdown-item delete-order-request" data-id="' . $row->id . '">
                            <i class="fa fa-trash"></i> حذف الطلب
                        </button>';
                    // }
                    // if (@AdminGuardHasPerm(\App\PermKeys::PARTNER_SHOW)) {
                    $showButton = '<a class="dropdown-item" href="' . route('professional_application.show', $row->id) . '">
                                    <i class="fas fa-eye"></i> عرض التفاصيل
                                </a>';
                    // }
                    $actions = '
                                <div class="dropdown actions-dropdown">
                                    <button class="btn btn-sm btn-primary dropdown-toggle actions-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="الإجراءات المتاحة">
                                        <i class="fas fa-cogs me-1"></i> الإجراءات
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-lg">
                                        <li>' . $showButton . '</li>
                                        <li>' . $deleteButton . '</li>
                                    </ul>
                                </div>';
                    return $actions;
                })
                ->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = helperTrans('api.parteners');
        $data["addButtonText"] = helperTrans('api.parteners');
        $data['jobTitles'] = $jobTitleAction->get();
        $data['status'] = RequestsStatusEnum::cases();
        $data['count'] = objModel::count();
        $data['approvedCount'] = objModel::where('status', RequestsStatusEnum::Approved->value)->count();
        $data['rejectedCount'] = objModel::where('status', RequestsStatusEnum::Rejected->value)->count();
        $data['pendingCount'] = objModel::where('status', RequestsStatusEnum::Pending->value)->count();
        return view($this->folderPath . '.index', compact('data'));
    }

    public function create(Request $request)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".create")->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }


    public function store(objRequest $request, objAction $action)
    {

        $data = $request->validated();
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

    public function update(objRequest $request, objAction $action, $id)
    {
        $contract = objModel::findOrFail($id);
        if ($request->has('status')) {
            $contract->update([
                'status' => $request->status
            ]);

            return response()->json(['success' => true, 'status' => $request->status]);
        }

        // التحديث العادي (مثلاً صورة أو غيره)
        $updatedData = $request->only($this->postData);
        $data = $action->find($id)->update($updatedData);

        return jsonSuccess($data);
    }

    public function show($id)
    {
        $application = objModel::with(['user', 'jobtitle'])->findOrFail($id);
        return view($this->folderPath . '.show', compact('application'));
    }

    public function destroy($id, objAction $action)
    {
        $action->delete($id);
        return jsonSuccess(['success' => true]);
    }
}
