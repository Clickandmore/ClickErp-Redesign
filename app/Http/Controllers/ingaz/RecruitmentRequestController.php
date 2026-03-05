<?php

namespace App\Http\Controllers\ingaz;

use App\Enums\ingaz\NotificationActionEnum;
use App\Enums\ingaz\RequestsStatusEnum;
use App\Enums\Office\MaritalStatusEnum;
use App\Http\Actions\ingaz\JobTitleAction;
use App\Http\Actions\ingaz\NationalityAction;
use App\Http\Actions\ingaz\RecruitmentRequestAction as objAction;
use App\Http\Actions\ingaz\ReligionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ingaz\ProfessionalApplicationRequest as objRequest;
use App\Http\Traits\SendSMSTrait;
use App\Http\Traits\StoreMessageTrait;
use App\Models\ingaz\RecruitmentRequestMaid as objModel;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class RecruitmentRequestController extends Controller
{
    use SendSMSTrait, StoreMessageTrait;

    public $folderPath = "admin.ingaz.RecruitmentRequest";
    public $postData = ["image"];
    public $mainRoute = "recruitment_requests";

    function __construct()
    {
        // $this->middleware('permission:عنا-قراءة,admin', ['only' => ['index']]);
        // $this->middleware('permission:عنا-انشاء,admin', ['only' => ['create', 'store']]);
        // $this->middleware('permission:عنا-تعديل,admin', ['only' => ['edit', 'update']]);
        // $this->middleware('permission:عنا-حذف,admin', ['only' => ['destroy']]);
    }

    public function index(Request $request, objAction $Action, JobTitleAction $jobTitleAction, NationalityAction $nationalityAction, ReligionAction $religionAction)
    {
        if ($request->ajax()) {
            $rows = objModel::query()
                ->with(['client', 'religion'])
                ->when($request->filled('status'), function ($query) use ($request) {
                    $query->where('status', $request->get('status'));
                })
                ->when($request->filled('jobTitle'), function ($query) use ($request) {
                    $query->where('job_title', $request->get('jobTitle'));
                })
                ->when($request->filled('nationality'), function ($query) use ($request) {
                    $query->where('nationality', $request->get('nationality'));
                })
                ->when($request->filled('religion'), function ($query) use ($request) {
                    $query->where('religion_id', $request->get('religion'));
                })
                ->when($request->filled('marital_state'), function ($query) use ($request) {
                    $query->where('social_status', $request->get('marital_state'));
                })
                ->latest();
            return DataTables::of($rows)
                ->addIndexColumn()
                ->editColumn('job_title', function ($row) {
                    return $row->job_title;
                })
                ->editColumn('client_id', function ($row) {
                    return $row->client->name ?? '-';
                })
                ->addColumn('client_phone', function ($row) {
                    $phone = trim((string)(optional($row->client)->phone ?? ''));
                    if ($phone === '') {
                        return '-';
                    }

                    $phoneCode = trim((string)(optional($row->client)->phone_code ?? ''));
                    return trim($phoneCode . ' ' . $phone);
                })
                ->editColumn('nationality', function ($row) {
                    return $row->nationality ?? '-';
                })
                ->addColumn('request_date', function ($row) {
                    return $row->created_at ? $row->created_at->format('Y-m-d') : '-';
                })
                ->addColumn('request_time', function ($row) {
                    return $row->created_at ? $row->created_at->format('h:i A') : '-';
                })
                ->editColumn('religion_id ', function ($row) {
                    return $row->religion?->title ?? '-';
                })
                ->editColumn('social_status', function ($row) {
                    $enum = MaritalStatusEnum::tryFrom($row->social_status);
                    return $enum ? $enum->lang() : ($row->social_status ?? '-');
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

                })->addColumn('actions', function ($row) {
                    // if (@AdminGuardHasPerm(\App\PermKeys::PARTNER_DELETE)) {
                    $deleteButton = ' <button class="dropdown-item delete-order-request" data-id="' . $row->id . '">
                            <i class="fa fa-trash"></i> حذف الطلب
                        </button>';
                    // }
                    // if (@AdminGuardHasPerm(\App\PermKeys::PARTNER_SHOW)) {
                    $showButton = '<a class="dropdown-item" href="' . route('recruitment_requests.show', $row->id) . '">
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
        $data['nationalities'] = $nationalityAction->get();
        $data['religions'] = $religionAction->get();
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
        $contract = objModel::with('client')->findOrFail($id);

        // لو جاي من toggle status
        if ($request->has('status')) {
            $oldStatus = $contract->status;
            $newStatus = (string)$request->status;

            $contract->update([
                'status' => $newStatus
            ]);

            if ($oldStatus !== $newStatus) {
                $statusLabel = RequestsStatusEnum::tryFrom($newStatus)?->lang() ?? $newStatus;
                $message = "عزيزي العميل، تم تحديث حالة طلبكم الخاص رقم #{$contract->id} إلى: {$statusLabel}.";

                $this->storeMessage('تحديث حالة الطلب الخاص', $message, NotificationActionEnum::OrderStatus->value, $contract->client_id);

                $rawClientPhone = trim((string)($contract->client->phone ?? ''));
                if ($rawClientPhone !== '') {
                    try {
                        $smsResponse = $this->sendSMS(formatSaudiPhone($rawClientPhone), $message);
                        $smsResult = json_decode((string)$smsResponse, true);

                        if (is_array($smsResult) && isset($smsResult['code']) && (string)$smsResult['code'] === '1') {
                            $this->storeMessage('ارسال رسالة نصية الى العميل', $message, NotificationActionEnum::SMS->value, $contract->client_id);
                        }
                    } catch (\Throwable $e) {
                        \Log::warning('Failed to send recruitment request status SMS', [
                            'recruitment_request_id' => $contract->id,
                            'client_id' => $contract->client_id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            return response()->json(['success' => true, 'status' => $newStatus]);
        }

        // التحديث العادي (مثلاً صورة أو غيره)
        $updatedData = $request->only($this->postData);
        $data = $action->find($id)->update($updatedData);

        return jsonSuccess($data);
    }
public function show($id)
{
   // جلب الطلب مع العميل والمساعدة (Maid) ونوع الوظيفة (JobType)
    $recruitmentRequest = objModel::with(['client', 'maid'])->findOrFail($id);

    return view($this->folderPath . '.show', compact('recruitmentRequest'));
}

    public function destroy($id, objAction $action)
    {
        $action->delete($id);
        return jsonSuccess(['success' => true]);
    }
}
