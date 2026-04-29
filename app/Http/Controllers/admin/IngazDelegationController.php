<?php

namespace App\Http\Controllers\admin;

use App\Http\Requests\StoreIngazDelegationRequest;
use App\Models\auth\Admin;
use App\Models\ingaz\JobTitle;
use App\Models\ingaz\Maid;
use App\Models\ingaz\MaidJobTitles;
use App\Models\ingaz\Nationality;
use App\Models\ingaz\Religion;
use App\Models\ingaz\ReligionsTranslation;
use App\Models\ingaz\User;
use App\Models\IngazDelegation;
use App\Models\office\Office as RecruitmentOffice;
use App\PermKeys;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class IngazDelegationController extends Controller
{
    public function __construct()
    {
        $this->middleware($this->permissionMiddleware($this->delegationViewPermissions()), ['only' => ['index', 'show']]);
        $this->middleware($this->permissionMiddleware($this->delegationCreatePermissions()), ['only' => ['create', 'store']]);
        $this->middleware($this->permissionMiddleware($this->delegationEditPermissions()), ['only' => ['edit', 'update']]);
        $this->middleware($this->permissionMiddleware($this->delegationDeletePermissions()), ['only' => ['destroy']]);
        $this->middleware($this->permissionMiddleware($this->delegationPrintPermissions()), ['only' => ['print']]);
        $this->middleware($this->permissionMiddleware($this->delegationStatusPermissions()), ['only' => ['updateStatus']]);
        $this->middleware($this->permissionMiddleware($this->delegationFormPermissions()), ['only' => ['findMaidByPassport']]);
    }

    public function index(Request $request): View|JsonResponse
    {
        $query = IngazDelegation::with(['nationality', 'profession', 'user', 'maid', 'marketer', 'invoice.musanedTransferItem.transfer']);

        $searchInput = $request->input('global_search', $request->query('search'));

        if (is_string($searchInput) && trim($searchInput) !== '') {
            $search = trim($searchInput);

            $query->where(function ($inner) use ($search) {
                $inner->where('ingaz_number', 'like', "%{$search}%")
                    ->orWhere('msaned_contract_number', 'like', "%{$search}%")
                    ->orWhere('visa_number', 'like', "%{$search}%")
                    ->orWhere('worker_name', 'like', "%{$search}%")
                    ->orWhere('worker_passport_number', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('national_num', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('nationality_id')) {
            $query->where('nationality_id', $request->nationality_id);
        }

        if ($request->filled('client_source_type')) {
            $query->where('client_source_type', $request->client_source_type);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('delegation_date_gregorian', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('delegation_date_gregorian', '<=', $request->to_date);
        }

        if ($request->ajax()) {
            $canViewDelegation = $this->adminCanAny($this->delegationViewPermissions());
            $canEditDelegation = $this->adminCanAny($this->delegationEditPermissions());
            $canDeleteDelegation = $this->adminCanAny($this->delegationDeletePermissions());
            $canPrintDelegation = $this->adminCanAny($this->delegationPrintPermissions());
            $canChangeDelegationStatus = $this->adminCanAny($this->delegationStatusPermissions());

            $rows = $query->latest('id');

            return DataTables::eloquent($rows)
                ->editColumn('id', fn (IngazDelegation $row) => $row->id)
                ->addColumn('client', function (IngazDelegation $row) {
                    $name = e($row->user?->name ?? '-');
                    $meta = e($row->user?->phone ?: ($row->user?->national_num ?: '-'));

                    return '<div>' . $name . '</div><small class="text-muted">' . $meta . '</small>';
                })
                ->addColumn('msaned_transfer', fn (IngazDelegation $row) => e($row->msaned_contract_number ?: '-'))
                ->addColumn('nationality_name', function (IngazDelegation $row) {
                    return e($row->nationality?->sub_title ?? $row->nationality?->title ?? '-');
                })
                ->addColumn('profession_name', fn (IngazDelegation $row) => e($row->profession?->title ?? '-'))
                ->addColumn('invoice', function (IngazDelegation $row) {
                    if ($row->invoice) {
                        return '<a href="' . route('invoices.show', $row->invoice->id) . '" class="btn btn-sm btn-outline-success">عرض الفاتورة</a>';
                    }

                    return '<a href="' . route('invoices.create', ['ingaz_delegation_id' => $row->id]) . '" class="btn btn-sm btn-outline-primary">إضافة فاتورة</a>';
                })
                ->addColumn('status', fn (IngazDelegation $row) => $this->statusBadgeHtml($row))
                ->addColumn('status_control', function (IngazDelegation $row) use ($canChangeDelegationStatus) {
                    if (!$canChangeDelegationStatus) {
                        return $this->statusBadgeHtml($row);
                    }

                    $options = collect(IngazDelegation::statusOptions())->map(function ($label, $value) use ($row) {
                        $selected = $row->status === $value ? 'selected' : '';

                        return '<option value="' . e($value) . '" ' . $selected . '>' . e($label) . '</option>';
                    })->implode('');

                    return '
                        <select class="form-select form-select-sm delegation-status-select" data-url="' . e(route('ingaz-delegations.update-status', $row)) . '">
                            ' . $options . '
                        </select>
                    ';
                })
                ->addColumn('print_action', function (IngazDelegation $row) use ($canPrintDelegation) {
                    if (!$canPrintDelegation) {
                        return '<span class="text-muted">-</span>';
                    }

                    return '<a href="' . route('ingaz-delegations.print', $row) . '" target="_blank" class="btn btn-sm btn-outline-dark"><i class="fa fa-print"></i></a>';
                })
                ->addColumn('actions', function (IngazDelegation $row) use ($canViewDelegation, $canEditDelegation, $canDeleteDelegation) {
                    $showUrl = route('ingaz-delegations.show', $row);
                    $editUrl = route('ingaz-delegations.edit', $row);
                    $deleteUrl = route('ingaz-delegations.destroy', $row);
                    $csrf = csrf_token();
                    $actions = [];

                    if ($canViewDelegation) {
                        $actions[] = '<a href="' . $showUrl . '" class="btn btn-outline-info">عرض</a>';
                    }

                    if ($canEditDelegation) {
                        $actions[] = '<a href="' . $editUrl . '" class="btn btn-outline-primary">تعديل</a>';
                    }

                    if ($row->invoice) {
                        $actions[] = '<a href="' . route('invoices.show', $row->invoice->id) . '" class="btn btn-outline-success">الفاتورة</a>';
                        $transfer = $row->invoice->musanedTransferItem?->transfer;
                        if ($transfer) {
                            $actions[] = '<a href="' . route('musaned-transfers.show', $transfer->id) . '" class="btn btn-outline-info">تحويل مساند</a>';
                        } else {
                            $actions[] = '<a href="' . route('musaned-transfers.create', ['ingaz_delegation_ids' => [$row->id]]) . '" class="btn btn-outline-info">تسجيل تحويل مساند</a>';
                        }
                    } else {
                        $actions[] = '<a href="' . route('invoices.create', ['ingaz_delegation_id' => $row->id]) . '" class="btn btn-outline-success">إضافة فاتورة</a>';
                    }

                    if ($canDeleteDelegation) {
                        $actions[] = '
                            <form action="' . $deleteUrl . '" method="POST" class="d-inline-block" onsubmit="return confirm(\'هل أنت متأكد من حذف هذا التفويض؟\');">
                                <input type="hidden" name="_token" value="' . $csrf . '">
                                <input type="hidden" name="_method" value="DELETE">
                                <button type="submit" class="btn btn-outline-danger">حذف</button>
                            </form>
                        ';
                    }

                    if ($actions === []) {
                        return '<span class="text-muted">-</span>';
                    }

                    return '<div class="btn-group btn-group-sm">' . implode('', $actions) . '</div>';
                })
                ->rawColumns(['client', 'invoice', 'status', 'status_control', 'print_action', 'actions'])
                ->make(true);
        }

        $nationalities = Nationality::query()->orderByTranslation('sub_title', 'asc')->get(['id']);
        $canCreateDelegation = $this->adminCanAny($this->delegationCreatePermissions());

        return view('admin.ingaz_delegations.index', compact('nationalities', 'canCreateDelegation'));
    }

    public function print(IngazDelegation $ingazDelegation): View
    {
        $ingazDelegation->load(['nationality', 'profession', 'user', 'maid.religion', 'marketer', 'invoice.musanedTransferItem.transfer']);

        return view('admin.ingaz_delegations.print', [
            'delegation' => $ingazDelegation,
            'religionOptions' => $this->religionOptions(),
            'experienceOptions' => $this->experienceOptions(),
            'visaTypeOptions' => $this->visaTypeOptions(),
        ]);
    }

    public function create(): View
    {
        return view('admin.ingaz_delegations.create', $this->formData());
    }

    public function store(StoreIngazDelegationRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $client = $this->resolveClient($request);
            $maid = $this->resolveMaid($request);

            IngazDelegation::create($this->buildDelegationPayload($request, $client, $maid));
        });

        return redirect()
            ->route('ingaz-delegations.index')
            ->with('success', 'تم إضافة تفويض الإنجاز بنجاح.');
    }

    public function show(IngazDelegation $ingazDelegation): View
    {
        $ingazDelegation->load(['nationality', 'profession', 'user', 'maid.religion', 'marketer', 'invoice']);

        return view('admin.ingaz_delegations.show', [
            'delegation' => $ingazDelegation,
            'religionOptions' => $this->religionOptions(),
            'experienceOptions' => $this->experienceOptions(),
            'visaTypeOptions' => $this->visaTypeOptions(),
            'canEditDelegation' => $this->adminCanAny($this->delegationEditPermissions()),
        ]);
    }

    public function edit(IngazDelegation $ingazDelegation): View
    {
        $ingazDelegation->load(['nationality', 'profession', 'user', 'maid.religion', 'marketer']);

        return view('admin.ingaz_delegations.edit', array_merge(
            $this->formData(),
            ['delegation' => $ingazDelegation]
        ));
    }

    public function update(StoreIngazDelegationRequest $request, IngazDelegation $ingazDelegation): RedirectResponse
    {
        DB::transaction(function () use ($request, $ingazDelegation) {
            $client = $this->resolveClient($request);
            $maid = $this->resolveMaid($request, $ingazDelegation->maid);

            $ingazDelegation->update($this->buildDelegationPayload($request, $client, $maid, $ingazDelegation));
        });

        return redirect()
            ->route('ingaz-delegations.show', $ingazDelegation)
            ->with('success', 'تم تحديث تفويض الإنجاز بنجاح.');
    }

    public function destroy(IngazDelegation $ingazDelegation): RedirectResponse
    {
        $this->deleteAttachments($ingazDelegation->attachments ?? []);
        $ingazDelegation->delete();

        return redirect()
            ->route('ingaz-delegations.index')
            ->with('success', 'تم حذف تفويض الإنجاز بنجاح.');
    }

    public function findMaidByPassport(Request $request): JsonResponse
    {
        $passport = trim((string) $request->get('passport'));

        if ($passport === '') {
            return response()->json(['found' => false]);
        }

        $maid = Maid::with(['religion'])->where('passport_num', $passport)->first();

        if (!$maid) {
            return response()->json(['found' => false]);
        }

        return response()->json([
            'found' => true,
            'maid' => [
                'id' => $maid->id,
                'name' => $maid->name,
                'passport_num' => $maid->passport_num,
                'religion' => $maid->religion?->title,
                'experience_status' => $maid->experience_status,
                'experience_years' => $maid->experience_years,
            ],
        ]);
    }

    public function updateStatus(Request $request, IngazDelegation $ingazDelegation): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:' . implode(',', array_keys(IngazDelegation::statusOptions()))],
        ]);

        $ingazDelegation->update([
            'status' => $validated['status'],
        ]);

        return response()->json([
            'message' => 'تم تحديث حالة التفويض بنجاح.',
            'status' => $ingazDelegation->status,
            'status_label' => $ingazDelegation->status_label,
        ]);
    }

    private function formData(): array
    {
        return [
            'nationalities' => Nationality::query()->orderByTranslation('sub_title', 'asc')->get(['id']),
            'professions' => JobTitle::with('translations')->orderByTranslation('title', 'asc')->get(['id']),
            'recruitmentOffices' => RecruitmentOffice::query()
                ->orderByTranslation('title', 'asc')
                ->get(['id', 'license_number', 'phone_office', 'street']),
            'existingUsers' => User::query()
                ->orderBy('name')
                ->get(['id', 'name', 'phone_code', 'phone', 'national_num', 'address']),
            'marketers' => Admin::query()->orderBy('name')->get(['id', 'name']),
            'visaTypeOptions' => $this->visaTypeOptions(),
            'religionOptions' => $this->religionOptions(),
            'experienceOptions' => $this->experienceOptions(),
            'maidLookupRoute' => route('ingaz-delegations.find-maid-by-passport'),
        ];
    }

    private function visaTypeOptions(): array
    {
        return [
            'electronic_ingaz_delegation' => 'تفويض إنجاز إلكتروني',
        ];
    }

    private function religionOptions(): array
    {
        return [
            'muslim' => 'مسلم',
            'non_muslim' => 'غير مسلم',
            'unspecified' => 'غير محدد',
        ];
    }

    private function experienceOptions(): array
    {
        return [
            'previously_worked' => 'سبق العمل',
            'not_previously_worked' => 'لم يسبق العمل',
            'appointed_by_client' => 'معينة من قبل العميل',
            'unspecified' => 'غير محدد',
            'known_service' => 'خدمة معروفة',
        ];
    }

    private function buildDelegationPayload(
        StoreIngazDelegationRequest $request,
        User $client,
        Maid $maid,
        ?IngazDelegation $delegation = null
    ): array {
        return [
            'delegation_date_gregorian' => $request->delegation_date_gregorian,
            'delegation_date_hijri' => $request->delegation_date_hijri,
            'nationality_id' => $request->nationality_id,
            'profession_id' => $request->profession_id,
            'user_id' => $client->id,
            'maid_id' => $maid->id,
            'marketer_id' => $request->marketer_id,
            'client_source_type' => $request->client_source_type,
            'visa_type' => $request->filled('visa_type') ? $request->visa_type : 'electronic_ingaz_delegation',
            'worker_name' => $request->worker_name,
            'worker_passport_number' => $request->worker_passport_number,
            'worker_passport_issue_date' => $request->worker_passport_issue_date,
            'visa_number' => $request->visa_number,
            'visa_date' => $request->visa_date,
            'worker_religion' => $request->worker_religion,
            'worker_experience' => $request->worker_experience,
            'external_office_name' => $request->external_office_name,
            'external_office_license_number' => $request->external_office_license_number,
            'external_office_address' => $request->external_office_address,
            'external_office_phone' => $request->external_office_phone,
            'status' => $delegation?->status ?? IngazDelegation::STATUS_UNDER_PROCESS,
            'ingaz_number' => $request->ingaz_number,
            'msaned_contract_number' => $request->msaned_contract_number,
            'notes' => $request->notes,
            'attachments' => $this->storeAttachments($request, $delegation),
        ];
    }

    private function resolveClient(StoreIngazDelegationRequest $request): User
    {
        if ($request->client_source_type === 'existing_in_program') {
            return User::findOrFail($request->existing_user_id);
        }

        $phone = $this->normalizePhone($request->separate_client_phone);
        $identityNumber = trim((string) $request->separate_client_identity_number);

        $existingByPhone = User::where('phone', $phone)->first();
        $existingByIdentity = $identityNumber !== ''
            ? User::where('national_num', $identityNumber)->first()
            : null;

        if ($existingByPhone && $existingByIdentity && $existingByPhone->id !== $existingByIdentity->id) {
            throw ValidationException::withMessages([
                'separate_client_identity_number' => 'رقم الهوية مرتبط بعميل آخر برقم جوال مختلف. اختر العميل الموجود من القائمة.',
            ]);
        }

        if (
            $existingByPhone
            && !empty($existingByPhone->national_num)
            && $identityNumber !== ''
            && $existingByPhone->national_num !== $identityNumber
        ) {
            throw ValidationException::withMessages([
                'separate_client_identity_number' => 'رقم الهوية المدخل لا يطابق العميل المرتبط بهذا الجوال.',
            ]);
        }

        if (
            !$existingByPhone
            && $existingByIdentity
            && !empty($existingByIdentity->phone)
            && $existingByIdentity->phone !== $phone
        ) {
            throw ValidationException::withMessages([
                'separate_client_phone' => 'رقم الهوية مسجل لعميل آخر برقم جوال مختلف. اختر العميل الموجود من القائمة أو استخدم جواله المسجل.',
            ]);
        }

        $user = $existingByPhone ?: $existingByIdentity ?: new User();
        $user->name = $request->separate_client_name;
        $user->phone_code = $user->phone_code ?: '+966';
        $user->phone = $phone;
        $user->national_num = $identityNumber !== '' ? $identityNumber : $user->national_num;
        $user->address = $request->separate_client_address;

        if (!$user->exists) {
            $user->password = Hash::make('123456');
            $user->register_from = 'web';
        }

        $user->save();

        return $user;
    }

    private function resolveMaid(StoreIngazDelegationRequest $request, ?Maid $currentMaid = null): Maid
    {
        $passportNumber = trim((string) $request->worker_passport_number);
        $religionId = $this->resolveReligionId($request->worker_religion);
        $experienceStatus = $this->mapExperienceToMaidStatus($request->worker_experience);

        $maid = Maid::where('passport_num', $passportNumber)->first();

        if (!$maid && $currentMaid && $currentMaid->passport_num === $passportNumber) {
            $maid = $currentMaid;
        }

        if (!$maid) {
            $maid = new Maid();
            $maid->price = 0;
            $maid->salary = 0;
            $maid->age = 0;
            $maid->cv_file = '';
            $maid->image = '';
            $maid->available = false;
            $maid->available_at = now()->toDateString();
            $maid->is_show = false;
            $maid->status = 'available';
        }

        if (!$maid->exists || empty($maid->nationality_id)) {
            $maid->nationality_id = (int) $request->nationality_id;
        }

        $maid->name = $request->worker_name;
        $maid->passport_num = $passportNumber;
        $maid->religion_id = $religionId;
        $maid->experience_status = $experienceStatus;
        $maid->experience_years = $experienceStatus === 'has_experience' ? max((int) ($maid->experience_years ?? 1), 1) : 0;
        $maid->request_type = Maid::resolveRequestType($maid->request_type);
        $maid->save();

        MaidJobTitles::firstOrCreate([
            'maid_id' => $maid->id,
            'job_title_id' => (int) $request->profession_id,
        ]);

        return $maid;
    }

    private function resolveReligionId(string $workerReligion): int
    {
        $labels = match ($workerReligion) {
            'muslim' => ['ar' => 'مسلم', 'en' => 'Muslim'],
            'non_muslim' => ['ar' => 'غير مسلم', 'en' => 'Non-Muslim'],
            default => ['ar' => 'غير محدد', 'en' => 'Unspecified'],
        };

        $religion = Religion::whereHas('translations', function ($query) use ($labels) {
            $query->where(function ($inner) use ($labels) {
                $inner->where('title', $labels['ar'])
                    ->orWhere('title', $labels['en'])
                    ->orWhere('title', 'like', '%' . $labels['ar'] . '%')
                    ->orWhere('title', 'like', '%' . $labels['en'] . '%');
            });
        })->first();

        if ($religion) {
            return $religion->id;
        }

        $religion = Religion::create(['is_show' => false]);

        ReligionsTranslation::create([
            'religion_id' => $religion->id,
            'locale' => 'ar',
            'title' => $labels['ar'],
        ]);

        ReligionsTranslation::create([
            'religion_id' => $religion->id,
            'locale' => 'en',
            'title' => $labels['en'],
        ]);

        return $religion->id;
    }

    private function mapExperienceToMaidStatus(string $experience): string
    {
        return in_array($experience, ['previously_worked', 'appointed_by_client', 'known_service'], true)
            ? 'has_experience'
            : 'no_experience';
    }

    private function storeAttachments(
        StoreIngazDelegationRequest $request,
        ?IngazDelegation $delegation = null
    ): array {
        $attachments = $delegation?->attachments ?? [];

        if ($request->boolean('replace_attachments')) {
            $this->deleteAttachments($attachments);
            $attachments = [];
        }

        if (!$request->hasFile('attachments')) {
            return $attachments;
        }

        foreach ($request->file('attachments') as $file) {
            $attachments[] = [
                'path' => $file->store('ingaz-delegations', 'public'),
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
            ];
        }

        return $attachments;
    }

    private function deleteAttachments(array $attachments): void
    {
        foreach ($attachments as $attachment) {
            $path = is_array($attachment) ? ($attachment['path'] ?? null) : $attachment;

            if ($path) {
                Storage::disk('public')->delete($path);
            }
        }
    }

    private function permissionMiddleware(array $permissions): string
    {
        return 'permission:' . implode('|', array_values(array_unique($permissions))) . ',admin';
    }

    private function adminCanAny(array $permissions): bool
    {
        $admin = auth('admin')->user();

        if (!$admin) {
            return false;
        }

        foreach (array_values(array_unique($permissions)) as $permission) {
            if ($admin->can($permission)) {
                return true;
            }
        }

        return false;
    }

    private function delegationViewPermissions(): array
    {
        return $this->delegationAnyPermissions();
    }

    private function delegationCreatePermissions(): array
    {
        return [
            PermKeys::INGAZ_DELEGATIONS_CREATE,
            PermKeys::INGAZ_DELEGATIONS_CONTROL,
            PermKeys::REC_CONTRACTS_CREATE,
            PermKeys::REC_CONTRACTS_MANAGE,
        ];
    }

    private function delegationEditPermissions(): array
    {
        return [
            PermKeys::INGAZ_DELEGATIONS_EDIT,
            PermKeys::INGAZ_DELEGATIONS_CONTROL,
            PermKeys::REC_CONTRACTS_EDIT,
            PermKeys::REC_CONTRACTS_MANAGE,
        ];
    }

    private function delegationDeletePermissions(): array
    {
        return [
            PermKeys::INGAZ_DELEGATIONS_DELETE,
            PermKeys::INGAZ_DELEGATIONS_CONTROL,
            PermKeys::REC_CONTRACTS_DELETE,
            PermKeys::REC_CONTRACTS_MANAGE,
        ];
    }

    private function delegationPrintPermissions(): array
    {
        return [
            PermKeys::INGAZ_DELEGATIONS_PRINT,
            PermKeys::INGAZ_DELEGATIONS_CONTROL,
            PermKeys::REC_CONTRACTS_PRINT,
            PermKeys::REC_CONTRACTS_VIEW,
            PermKeys::REC_CONTRACTS_MANAGE,
        ];
    }

    private function delegationStatusPermissions(): array
    {
        return [
            PermKeys::INGAZ_DELEGATIONS_CHANGE_STATUS,
            PermKeys::INGAZ_DELEGATIONS_CONTROL,
            PermKeys::REC_CONTRACTS_EDIT,
            PermKeys::REC_CONTRACTS_MANAGE,
        ];
    }

    private function delegationFormPermissions(): array
    {
        return array_values(array_unique(array_merge(
            $this->delegationCreatePermissions(),
            $this->delegationEditPermissions()
        )));
    }

    private function delegationAnyPermissions(): array
    {
        return array_values(array_unique(array_merge(
            [
                PermKeys::INGAZ_DELEGATIONS_VIEW,
                PermKeys::INGAZ_DELEGATIONS_CREATE,
                PermKeys::INGAZ_DELEGATIONS_EDIT,
                PermKeys::INGAZ_DELEGATIONS_DELETE,
                PermKeys::INGAZ_DELEGATIONS_PRINT,
                PermKeys::INGAZ_DELEGATIONS_CHANGE_STATUS,
                PermKeys::INGAZ_DELEGATIONS_CONTROL,
            ],
            [
                PermKeys::REC_CONTRACTS_VIEW,
                PermKeys::REC_CONTRACTS_CREATE,
                PermKeys::REC_CONTRACTS_EDIT,
                PermKeys::REC_CONTRACTS_DELETE,
                PermKeys::REC_CONTRACTS_PRINT,
                PermKeys::REC_CONTRACTS_MANAGE,
            ]
        )));
    }

    private function statusBadgeHtml(IngazDelegation $row): string
    {
        if (!empty($row->ingaz_number) && !empty($row->msaned_contract_number)) {
            $label = 'مكتمل';
            $class = 'success';
        } elseif (!empty($row->ingaz_number) || !empty($row->msaned_contract_number)) {
            $label = 'قيد التنفيذ';
            $class = 'warning text-dark';
        } else {
            $label = 'جديد';
            $class = 'secondary';
        }

        return '<span class="badge bg-' . $class . '">' . $label . '</span>';
    }

    private function normalizePhone(?string $phone): string
    {
        return preg_replace('/\s+/', '', trim((string) $phone)) ?? '';
    }
}
