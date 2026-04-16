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
use App\Http\Actions\office\OfficeAttachmentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\office\DeleteOfficeRequest;
use App\Http\Requests\office\OfficeRequest;
use App\Models\ingaz\Maid;
use App\Models\office\Office as objModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
        if ($request->ajax()) {
            $offices = objModel::query()
                ->with(['nationality', 'file'])
                ->withCount('maids')
                ->orderByTranslation('title', 'asc');

            return DataTables::of($offices)
                ->addIndexColumn()
                ->editColumn('nationality_id', function ($row) {
                    return $row->nationality->title ?? $row->nationality->sub_title ?? '';
                })
                ->editColumn('title', function ($row) {
                    return $row->title ?? '';
                })
                ->addColumn('linked_cvs', function ($row) {
                    return (string) ($row->maids_count ?? 0);
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
                                    </ul>
                                </div>';
                    return $actions;
                })
                ->rawColumns(['actions', 'status'])->make(true);
        }

        $stats = [
            'total' => objModel::count(),
            'active' => objModel::where('status', OfficesStatusEnum::Active->value)->count(),
            'inactive' => objModel::where('status', OfficesStatusEnum::DisActive->value)->count(),
            'linked_cvs' => Maid::whereNotNull('office_id')->count(),
            'with_cost_of_dollar' => objModel::query()
                ->whereNotNull('cost_of_dollar')
                ->where('cost_of_dollar', '!=', '')
                ->count(),
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
                          AdminOfficeAction $adminOfficeAction, AdminAction $adminAction, OfficeAttachmentAction $officeAttachmentAction)
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
            $managerPassword
        ) {
            $office = $action->store($data);

            foreach ($jobTitleIds as $jobTitleId) {
                $jopTypeOfficeAction->store([
                    'office_id' => $office->id,
                    'jop_type_id' => $jobTitleId,
                ]);
            }

            if (
                filled($data['manager_name'] ?? null)
                && filled($data['manager_phone'] ?? null)
                && filled($data['manager_email'] ?? null)
                && filled($managerPassword)
            ) {
                $admin = $adminAction->store([
                    'name' => $data['manager_name'],
                    'phone' => $data['manager_phone'],
                    'email' => $data['manager_email'],
                    'password' => Hash::make($managerPassword),
                ]);

                $adminOfficeAction->store([
                    'office_id' => $office->id,
                    'admin_id' => $admin->id,
                ]);
            }

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

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id, NationalityAction $nationalityAction, JobTypeAction $jobTypeAction)
    {
        $office = objModel::with(['jobTypes', 'attachment'])
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
    public function update(OfficeRequest $request, objAction $action, $id, OfficeAttachmentAction $officeAttachmentAction, AdminAction $adminAction, AdminOfficeAction $adminOfficeAction)
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
            $request
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

            if (isset($obj->officeManager->admin_id)) {
                $admin = $adminAction->find($obj->officeManager->admin_id);
                $adminData = [];

                if (filled($data['manager_name'] ?? null)) {
                    $adminData['name'] = $data['manager_name'];
                }

                if (filled($data['manager_phone'] ?? null)) {
                    $adminData['phone'] = $data['manager_phone'];
                }

                if (filled($data['manager_email'] ?? null)) {
                    $adminData['email'] = $data['manager_email'];
                }

                if (filled($managerPassword)) {
                    $adminData['password'] = Hash::make($managerPassword);
                }

                if (! empty($adminData)) {
                    $admin->update($adminData);
                }
            } elseif (
                filled($data['manager_name'] ?? null)
                && filled($data['manager_phone'] ?? null)
                && filled($data['manager_email'] ?? null)
                && filled($managerPassword)
            ) {
                $admin = $adminAction->store([
                    'name' => $data['manager_name'],
                    'phone' => $data['manager_phone'],
                    'email' => $data['manager_email'],
                    'password' => Hash::make($managerPassword),
                ]);

                $adminOfficeAction->store([
                    'office_id' => $id,
                    'admin_id' => $admin->id,
                ]);
            }

            $obj->update($data);

            if ($request->has('job_title_id')) {
                $obj->jobTypes()->sync($jobTitleIds);
            }
        });

        return jsonSuccess(['url' => route('offices.index')], null, 201);

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
}
