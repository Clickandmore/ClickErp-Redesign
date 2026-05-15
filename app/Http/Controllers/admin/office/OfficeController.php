<?php

namespace App\Http\Controllers\admin\office;

use App\Enums\Office\OfficesStatusEnum;
use App\Http\Actions\auth\AdminAction;
use App\Http\Actions\ingaz\JobTypeAction;
use App\Http\Actions\ingaz\NationalityAction;
use App\Http\Actions\office\AdminOfficeAction;
use App\Http\Actions\office\JopTypeOfficeAction;
use App\Http\Actions\office\OfficeAction as objAction;
use App\Http\Actions\office\OfficeAttachmentAction;
use App\Http\Controllers\admin\Controller;
use App\Http\Requests\office\DeleteOfficeRequest;
use App\Http\Requests\office\OfficeRequest;
use App\Models\office\Office as objModel;
use App\Services\office\OfficeFinanceAccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Spatie\Activitylog\Models\Activity;
use Yajra\DataTables\Facades\DataTables;

//use function App\Http\Controllers\ingaz\__;
//use function App\Http\Controllers\ingaz\response;
//use function App\Http\Controllers\ingaz\route;
//use function App\Http\Controllers\ingaz\view;

class OfficeController extends Controller
{

    public $folderPath = "admin.office";
    public $postData = ["title:ar", "title:en", "phone_office", "license_number",
        "nationality_id", "street", "manager_name", "manager_phone",
        "manager_email", "password", "bank_address", "bank_account", "bank_account_name", 'file', 'file_name',
        "bank_swift_code", "bank_iban", "cost_of_dollar", "cost_of_other", 'job_title_id'];
    public $mainRoute = "offices";

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, NationalityAction $nationalityAction)
    {
        $linkedContractsCountSubquery = $this->linkedContractsCountSubquery();

        if ($request->ajax()) {
            $offices = objModel::query()
                ->select('recruitment_offices.*')
                ->with(['nationality', 'file', 'account', 'officeManager.admin'])
                ->selectSub($linkedContractsCountSubquery, 'linked_contracts_count')
                ->withCount(['maids as linked_cvs_count'])
                ->get()
                ->sortBy(function ($office) {
                    return mb_strtolower((string) ($office->title ?? ''));
                })
                ->values();

            return DataTables::of($offices)
                ->addIndexColumn()
                ->editColumn('nationality_id', function ($row) {
                    return $row->nationality?->sub_title ?: ($row->nationality?->title ?: '');
                })
                ->editColumn('title', function ($row) {
                    return $row->title ?? '';
                })
                ->addColumn('account_code', function ($row) {
                    return $row->account?->code ? (string) $row->account->code : '--';
                })
                ->addColumn('linked_cvs', function ($row) {
                    $count = (int) ($row->linked_cvs_count ?? 0);
                    $url = route('maids.index', ['office_id' => $row->id]);

                    return $this->renderRelatedCountBadge($count, $url, 'linked-cvs-link');
                })
                ->addColumn('linked_contracts', function ($row) {
                    $count = (int) ($row->linked_contracts_count ?? 0);
                    $url = route('RecruitmentContract.index', ['office_id' => $row->id]);

                    return $this->renderRelatedCountBadge($count, $url, 'linked-contracts-link');
                })
                ->editColumn('cost_of_dollar', function ($row) {
                    if ($row->cost_of_dollar === null || $row->cost_of_dollar === '') {
                        return '-';
                    }

                    return number_format((float) $row->cost_of_dollar, 2);
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
                    $showButton = '';
                    $deleteButton = '';
                    $fileButton = '';
                    $sendEmailButton = '';
                    $loginAsAgentButton = '';
                    // if (Auth::guard('admin')->user()->hasPermission('programs-update')) {
                    $editButton = '<a href="' . route('offices.edit', $row->id) . '" class="dropdown-item">
                                    <i class="fa fa-pencil"></i> ' . __("ingaz.edit") . '
                                   </a>';
                    $showButton = '<a href="' . route('offices.show', $row->id) . '" class="dropdown-item">
                                    <i class="fa fa-eye"></i> ' . __("ingaz.view_details") . '
                                   </a>';
                    // }
                    // if (Auth::guard('admin')->user()->hasPermission('programs-delete')) {
                    // $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    $deleteButton = ' <button type="button" class="dropdown-item deleteButton" delete-route="' . route($this->mainRoute . ".destroy", $row->id) . '" title="حذف">
                            <i class="fa fa-trash"></i> حذف المكتب
                        </button>';
                    // }
                    if (isset($row->file)) {
                        $fileButton = '<a href="' . route('offices-file.edit', @$row->file->id) . '" class="dropdown-item">
                                    <i class="fa fa-plus"></i> ' . __("ingaz.office_file") . '
                                   </a>';
                    } else {
                        $fileButton = '<a href="' . route('offices-file.create', ['office_id' => $row->id]) . '" class="dropdown-item">
                                    <i class="fa fa-plus"></i> ' . __("ingaz.office_file") . '
                                   </a>';
                    }
                    if (filled($row->manager_email)) {
                        $sendEmailButton = '<button class="dropdown-item sendEmailResetPassword" data-id="' . $row->id . '"data-email="' . $row->manager_email . '">' . '  <i class="fa fa-email"></i> ' . __("ingaz.send_email_reset_password") . '</button>';
                    }
                    if (Route::has('offices.login-as-agent')) {
                        $loginAsAgentButton = '<form action="' . route('offices.login-as-agent', $row->id) . '" method="POST" target="_blank" class="m-0">'
                            . csrf_field()
                            . '<button type="submit" class="dropdown-item">'
                            . '<i class="fa fa-sign-in-alt"></i> تسجيل كوكيل'
                            . '</button>'
                            . '</form>';
                    }

                    // Action Buttons
                    $actions = '
                                <div class="dropdown actions-dropdown">
                                    <button class="btn btn-sm btn-primary dropdown-toggle actions-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="الإجراءات المتاحة">
                                        <i class="fas fa-cogs me-1"></i> ' . __("ingaz.actions") . '
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-lg">
                                        <li>' . $showButton . '</li>
                                        <li>' . $editButton . '</li>
                                        <li>' . $deleteButton . '</li>
                                        <li>' . $fileButton . '</li>
                                        <li>' . $sendEmailButton . '</li>
                                        <li>' . $loginAsAgentButton . '</li>
                                    </ul>
                                </div>';
                    return $actions;
                })
                ->rawColumns(['linked_cvs', 'linked_contracts', 'actions', 'status'])->make(true);
        }

        $officeLinkedStats = objModel::query()
            ->withCount('maids')
            ->get(['id']);

        $stats = [
            'total' => objModel::count(),
            'active' => objModel::where('status', OfficesStatusEnum::Active->value)->count(),
            'inactive' => objModel::where('status', OfficesStatusEnum::DisActive->value)->count(),
            // يُحسب من نفس relation المستخدمة في الجدول لضمان التطابق مع عدد السير الظاهر لكل مكتب.
            'linked_cvs' => (int) $officeLinkedStats->sum('maids_count'),
            'offices_with_linked_cvs' => (int) $officeLinkedStats->where('maids_count', '>', 0)->count(),
        ];

        $nationalities = $nationalityAction->get();

        return view($this->folderPath . '.index', compact('stats', 'nationalities'));
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
        $office = null;

        return view($this->folderPath . ".form", compact('nationalities', 'jobTypes', 'office'));

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(OfficeRequest     $request, objAction $action, JopTypeOfficeAction $jopTypeOfficeAction,
                          AdminOfficeAction $adminOfficeAction, AdminAction $adminAction, OfficeAttachmentAction $officeAttachmentAction,
                          OfficeFinanceAccountService $officeFinanceAccountService)
    {
        $office = null;
        $data = $request->validated();
        $jobTitleIds = $data['job_title_id'] ?? [];
        $managerPassword = $data['password'] ?? null;
        $attachmentFile = $request->file('file');
        $attachmentName = $data['file_name'] ?? null;

        unset($data['job_title_id'], $data['file'], $data['file_name'], $data['password']);

        DB::transaction(function () use (
            &$office,
            $action,
            $data,
            $jobTitleIds,
            $jopTypeOfficeAction,
            $adminAction,
            $adminOfficeAction,
            $officeAttachmentAction,
            $attachmentFile,
            $attachmentName,
            $managerPassword,
            $officeFinanceAccountService
        ) {
            $office = $action->store($data);

            $officeFinanceAccountService->ensureForOffice($office);

            foreach ($jobTitleIds as $jobTitleId) {
                $jopTypeOfficeAction->store([
                    'office_id' => $office->id,
                    'jop_type_id' => $jobTitleId,
                ]);
            }

            $this->syncOfficeAgentAccount($office, $data, $managerPassword, $adminAction, $adminOfficeAction);

            if ($attachmentFile && filled($attachmentName)) {
                $officeAttachmentAction->store([
                    'office_id' => $office->id,
                    'file' => uploadFile($attachmentFile, 'office_attachments'),
                    'file_name' => $attachmentName,
                ]);
            }
        });

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
        $office = objModel::with([
            'nationality',
            'jobTypes',
            'attachment',
            'file.jobTitle',
            'officeManager.admin',
            'account',
        ])->withCount('maids')->findOrFail($id);

        // $office->is_show = !$office->is_show;
        // $office->save();

        $activities = Activity::query()
            ->with('causer')
            ->where('subject_type', objModel::class)
            ->where('subject_id', $office->id)
            ->latest('created_at')
            ->get();

        return view('admin.office.show', [
            'office' => $office,
            'activities' => $activities,
        ]);
    }

    public function loginAsAgent($id)
    {
        $office = objModel::with('officeManager.admin')->findOrFail($id);

        $adminUser = Auth::guard('admin')->user();

        if (! $adminUser) {
            abort(403);
        }

        session(['_agent_impersonate_office_id' => (int) $office->id]);

        Auth::guard('agent')->login($adminUser);
        request()->session()->regenerate();

        return redirect()->route('agent.dashboard');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id, NationalityAction $nationalityAction, JobTypeAction $jobTypeAction)
    {
        $office = objModel::with(['jobTypes', 'attachment', 'account', 'officeManager.admin'])
            ->withCount('maids')
            ->findorfail($id);
        $nationalities = $nationalityAction->get();
        $jobTypes = $jobTypeAction->get();

        return view($this->folderPath . ".form", compact('nationalities', 'jobTypes', 'office'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(OfficeRequest $request, objAction $action, $id, OfficeAttachmentAction $officeAttachmentAction,
                           AdminAction $adminAction, AdminOfficeAction $adminOfficeAction,
                           OfficeFinanceAccountService $officeFinanceAccountService)
    {
        $data = $request->validated();
        $obj = $action->find($id);
        $attachmentFile = $request->file('file');
        $attachmentName = $data['file_name'] ?? null;
        $managerPassword = $data['password'] ?? null;
        $jobTitleIds = $data['job_title_id'] ?? [];
        unset($data['job_title_id'], $data['file'], $data['file_name'], $data['password']);

        DB::transaction(function () use (
            $obj,
            $data,
            $attachmentFile,
            $attachmentName,
            $officeAttachmentAction,
            $adminAction,
            $adminOfficeAction,
            $id,
            $managerPassword,
            $jobTitleIds,
            $request,
            $officeFinanceAccountService
        ) {
            if ($attachmentFile && filled($attachmentName)) {
                $attachment = $obj->attachment;

                if (isset($attachment)) {
                    $attachment->update([
                        'file' => uploadFile($attachmentFile, 'office_attachments'),
                        'file_name' => $attachmentName,
                    ]);
                } else {
                    $officeAttachmentAction->store([
                        'office_id' => $id,
                        'file' => uploadFile($attachmentFile, 'office_attachments'),
                        'file_name' => $attachmentName,
                    ]);
                }
            }

            $obj->update($data);
            $this->syncOfficeAgentAccount($obj->fresh(['officeManager.admin']), $data, $managerPassword, $adminAction, $adminOfficeAction);

            $officeFinanceAccountService->ensureForOffice($obj->fresh(['translations', 'account']));

            if ($request->has('job_title_id')) {
                $obj->jobTypes()->sync($jobTitleIds);
            }
        });

        return jsonSuccess(['url' => route('offices.index')], null, 201);

    }

    private function syncOfficeAgentAccount(
        objModel $office,
        array $data,
        ?string $managerPassword,
        AdminAction $adminAction,
        AdminOfficeAction $adminOfficeAction
    ): void {
        if (! filled($data['manager_email'] ?? null)) {
            return;
        }

        $office->loadMissing('officeManager.admin');

        $adminData = [
            'name' => filled($data['manager_name'] ?? null)
                ? $data['manager_name']
                : ((string) ($office->title ?: $data['manager_email'])),
            'phone' => filled($data['manager_phone'] ?? null) ? $data['manager_phone'] : null,
            'email' => $data['manager_email'],
        ];

        if (filled($managerPassword)) {
            $adminData['password'] = Hash::make($managerPassword);
        }

        if ($office->officeManager?->admin) {
            $office->officeManager->admin->update($adminData);

            return;
        }

        if (! filled($managerPassword)) {
            return;
        }

        $admin = $adminAction->store($adminData);

        $adminOfficeAction->store([
            'office_id' => $office->id,
            'admin_id' => $admin->id,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(DeleteOfficeRequest $request, $id, objAction $action, AdminAction $adminAction)
    {
        $obj = $action->find($id);
        if (isset($obj->officeManager->admin_id)) {
            $admin = $adminAction->find($obj->officeManager->admin_id);
            $adminAction->delete($admin->id);
        }
        $action->delete($id);
        return jsonSuccess();
    }

    private function linkedContractsCountSubquery()
    {
        return DB::table('contracts')
            ->leftJoin('maids', 'maids.id', '=', 'contracts.maid_id')
            ->leftJoin('contract_infos', 'contract_infos.contract_id', '=', 'contracts.id')
            ->selectRaw('COUNT(DISTINCT contracts.id)')
            ->whereIn('contracts.type', ['recruitment', 'maid'])
            ->where(function ($query) {
                $query->where(function ($maidQuery) {
                    $maidQuery->whereColumn('maids.office_id', 'recruitment_offices.id')
                        ->whereNull('maids.deleted_at');
                })->orWhereColumn('contract_infos.manual_maid_office_id', 'recruitment_offices.id');
            });
    }

    private function renderRelatedCountBadge(int $count, string $url, string $class): string
    {
        if ($count < 1) {
            return '<span class="related-count-badge is-empty" data-order="0">0</span>';
        }

        return '<a href="' . $url . '" class="related-count-badge ' . $class . '" data-order="' . $count . '">'
            . $count .
            '</a>';
    }
}
