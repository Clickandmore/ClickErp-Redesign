<?php

namespace App\Http\Controllers\ingaz;

use App\Enums\ingaz\RequestsStatusEnum;
use App\Enums\Office\MaritalStatusEnum;
use App\Http\Actions\ingaz\JobTitleAction;
use App\Http\Actions\ingaz\NationalityAction;
use App\Http\Actions\ingaz\ReligionAction;
use App\Http\Actions\ingaz\RenewContractAction as objAction;
use App\Http\Controllers\admin\Controller;
use App\Http\Requests\ingaz\RenewContractRequest as objRequest;
use App\Models\RenewContract as objModel;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class RenewContractController extends Controller
{

    public $folderPath = "admin.ingaz.RenewContracts";
    public $postData = ["image"];
    public $mainRoute = "renew-contracts";

    function __construct()
    {
        // $this->middleware('permission:عنا-قراءة,admin', ['only' => ['index']]);
        // $this->middleware('permission:عنا-انشاء,admin', ['only' => ['create', 'store']]);
        // $this->middleware('permission:عنا-تعديل,admin', ['only' => ['edit', 'update']]);
        // $this->middleware('permission:عنا-حذف,admin', ['only' => ['destroy']]);
    }

    public function index(Request $request, objAction $Action, JobTitleAction $jobTitleAction, NationalityAction $nationalityAction, ReligionAction $religionAction)
    {
        abort_unless(@AdminGuardHasPerm(\App\PermKeys::RENEW_CONTRACTS_VIEW), 403);

        if ($request->ajax()) {
            $rows = objModel::query()
                ->when($request->filled('status'), function ($query) use ($request) {
                    $query->where('status', $request->get('status'));
                })
                ->when($request->filled('nationality'), function ($query) use ($request) {
                    $query->where('nationality_id', $request->get('nationality'));
                })
                ->when($request->filled('marital_state'), function ($query) use ($request) {
                    $query->where('worker_marital_status', $request->get('marital_state'));
                })
                ->latest();
            return DataTables::of($rows)
                ->addIndexColumn()
                ->editColumn('client_name', function ($row) {
                    return $row->client_name;
                })
                ->editColumn('city', function ($row) {
                    return $row->city ?? '-';
                })
                ->editColumn('nationality', function ($row) {
                    return $row->nationality?->title ?? '-';
                })
                ->editColumn('worker_marital_status', function ($row) {
                    $enum = MaritalStatusEnum::tryFrom($row->worker_marital_status);
                    return $enum ? $enum->lang() : ($row->worker_marital_status ?? '-');
                })
                ->editColumn('status', function ($row) {
                    if ($row->status == RequestsStatusEnum::Pending->value) {
                        if (!@AdminGuardHasPerm(\App\PermKeys::ORDERS_EDIT)) {
                            return '<span class="badge bg-warning" >' . RequestsStatusEnum::tryFrom($row->status)->lang() . '</span>';
                        }

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
                })->addColumn('actions', function ($row) {
                    $items = [];

                    if (@AdminGuardHasPerm(\App\PermKeys::RENEW_CONTRACTS_VIEW)) {
                        $items[] = '<li><a class="dropdown-item" href="' . route('renew-contracts.show', $row->id) . '">
                                    <i class="fas fa-eye"></i> عرض التفاصيل
                                </a></li>';
                    }

                    if (@AdminGuardHasPerm(\App\PermKeys::ORDERS_DELETE)) {
                        $items[] = '<li><button class="dropdown-item delete-order-request" data-id="' . $row->id . '">
                            <i class="fa fa-trash"></i> حذف الطلب
                        </button></li>';
                    }

                    if ($items === []) {
                        return '<span class="text-muted">-</span>';
                    }

                    return '
                                <div class="dropdown actions-dropdown">
                                    <button class="btn btn-sm btn-primary dropdown-toggle actions-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="الإجراءات المتاحة">
                                        <i class="fas fa-cogs me-1"></i> الإجراءات
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-lg">'
                                        . implode('', $items) .
                                    '</ul>
                                </div>';
                })
                ->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = helperTrans('api.parteners');
        $data["addButtonText"] = helperTrans('api.parteners');
        $data['nationalities'] = $nationalityAction->get();
        $data['status'] = RequestsStatusEnum::cases();
        $data['marital_status'] = MaritalStatusEnum::cases();
        $data['count'] = objModel::count();
        $data['approvedCount'] = objModel::where('status', RequestsStatusEnum::Approved->value)->count();
        $data['rejectedCount'] = objModel::where('status', RequestsStatusEnum::Rejected->value)->count();
        $data['pendingCount'] = objModel::where('status', RequestsStatusEnum::Pending->value)->count();
        return view($this->folderPath . '.index', compact('data'));
    }


    public function create(Request $request)
    {
        if ($request->ajax())   {
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
    abort_unless(@AdminGuardHasPerm(\App\PermKeys::ORDERS_EDIT), 403);

    $contract = objModel::findOrFail($id);

    // لو جاي من toggle status
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
    abort_unless(@AdminGuardHasPerm(\App\PermKeys::RENEW_CONTRACTS_VIEW), 403);

    $renew = objModel::with(['user', 'nationality'])->findOrFail($id);
    return view($this->folderPath . '.show', compact('renew'));
}

    public function destroy($id, objAction $action)
    {
        abort_unless(@AdminGuardHasPerm(\App\PermKeys::ORDERS_DELETE), 403);

        $action->deletewithfiles($id);
        return jsonSuccess(['success' => true]);
    }
}
