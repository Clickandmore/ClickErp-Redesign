<?php

namespace App\Http\Controllers\ingaz;

use App\Enums\ingaz\ContractStatusEnum;
use App\Http\Actions\ingaz\JobTitleAction;
use App\Http\Actions\ingaz\JobTypeAction;
use App\Http\Actions\ingaz\MaidAction as objAction;
use App\Http\Actions\ingaz\NationalityAction;
use App\Http\Actions\ingaz\RecruitmentOfficeAction;
use App\Http\Actions\ingaz\ReligionAction;
use App\Http\Actions\ingaz\SkillAction;
use App\Http\Controllers\admin\Controller;
use App\Http\Requests\ingaz\MaidRequest;
use App\Http\Requests\StoreRapidCv;
use App\Models\Contract;
use App\Models\ingaz\JobType;
use App\Models\ingaz\Maid;
use App\Models\ingaz\Maid as objModel;
use App\Models\ingaz\MaidAttachment;
use App\Models\ingaz\Nationality;
use App\Models\ingaz\Skill;
use App\Models\MaidUnavailableRecord;
use App\Models\RecruitmentOffices;
use App\Services\ingaz\MaidCvExtractionService;
use App\Services\ingaz\RentalMaidFinanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class MaidController extends Controller
{

    public $folderPath = "admin.ingaz.maids";
    public $postData = ["nationality_id", "expertises", 'experience_status', 'image', 'video', 'childrens_count', 'duration', 'additional_notes', 'video_link', 'cv_file', 'marital_status:ar', 'marital_status:en', 'languages', 'percentage', 'experience_years', 'cv_preview', 'religion_id', 'passport_num', 'price', 'salary', 'age', 'office_id', 'name:en', 'gender', 'type_id', 'job_id', 'skills', 'birth_date', 'height', 'weight', 'id_number', 'id_expiry_date', 'border_number', 'passport_issue_date', 'passport_expiry_date', 'arrival_date', 'create_shelter_contract', 'request_type'];
    public $mainRoute = "maids";

    public function __construct()
    {
        $this->middleware('permission:maids_view,admin', ['only' => ['index']]);
        $this->middleware('permission:maids_rental_view,admin', ['only' => ['rentalIndex']]);
        $this->middleware('permission:maids_backout_view,admin', ['only' => ['noneAvailableMaids']]);
        $this->middleware('permission:maids_deleted_view,admin', ['only' => ['deletedCvs']]);
        $this->middleware('permission:maids_create,admin', ['only' => ['create', 'createRental', 'create_rapid_cv', 'store', 'store_rapid_cv', 'extractCvData']]);
        $this->middleware('permission:maids_edit,admin', ['only' => ['edit', 'update', 'update_cv', 'updateOffer', 'backout', 'clearBackout', 'suspendCv']]);
        $this->middleware('permission:maids_delete,admin', ['only' => ['destroy', 'restore']]);
        $this->middleware('permission:maids_toggle_status,admin', ['only' => ['show']]);
        $this->middleware('permission:maids_toggle_pin,admin', ['only' => ['togglePin']]);
        $this->middleware('permission:maids_export|maids_export_pdf,admin', ['only' => ['exportPdf']]);
        $this->middleware('permission:maids_export|maids_export_excel,admin', ['only' => ['exportExcel']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, objAction $Action, RecruitmentOfficeAction $officeAct)
    {
        $query = objModel::query()
            ->with(['nationality', 'religion', 'jobTypes', 'contracts', 'orders', 'office', 'creatorActivity.causer', 'addedBy'])
            ->select([
                'id',
                'name',
                'cv_code',
                'image',
                'cv_file',
                'cv_preview',
                'nationality_id',
                'religion_id',
                'passport_num',
                'price',
                'salary',
                'age',
                'is_show',
                'available',
                'available_at',
                'created_at',
                'is_pinned',
                'status',
                'office_id',
                'experience_years',
                'experience_status',
                'maid_accommodation_type',
                'added_by',
                'request_type',
                // 'arrival_date'
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('is_pinned');

        $this->applyRecruitmentMaidsScope($query);

        if ($request->ajax()) {
            $this->applyMaidFilter($request, $query);

            $statsQuery = objModel::query();
            $this->applyRecruitmentMaidsScope($statsQuery);
            $this->applyMaidFilter($request, $statsQuery);
            $stats = $this->buildRecruitmentMaidStats($statsQuery);

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('photo', function ($row) {
                    $rawPath = $row->image ?: ($row->cv_preview ?: $row->cv_file);
                    $imageUrl = $rawPath ? showFile($rawPath) : asset('admin/assets/img/avatar.png');
                    return '<img src="' . $imageUrl . '" alt="maid-avatar" class="border" style="width:85px;height:110px;object-fit:cover;">';
                })
                ->addColumn('profile', function (Maid $row) {
                    $nationality = e($row->nationality?->sub_title ?? helperTrans('ingaz.not_specified'));
                    $religion = e($row->religion?->title ?? helperTrans('ingaz.not_specified'));
                    $name = e($row->name ?? helperTrans('ingaz.not_specified'));
                    $passport = e($row->passport_num ?? helperTrans('ingaz.not_specified'));
                    $cvCode = $row->cv_code ? e($row->cv_code) : null;
                    $requestType = e($row->request_type_label);

                    $pinnedBadge = $row->is_pinned
                        ? '<span class="badge bg-warning-subtle text-warning ms-1"><i class="fas fa-thumbtack text-secondary"></i> ' . helperTrans('ingaz.pinned') . '</span>'
                        : '';
                    $cvCodeLabel = helperTrans('ingaz.cv_code');
                    $cvCodeLine = $cvCode
                        ? '<div class="text-muted text-medium"><i class="fas fa-id-card me-1 text-secondary"></i>' . $cvCodeLabel . ': ' . $cvCode . '</div>'
                        : '';


                    return '<div class="d-flex flex-column gap-2">'
                        . '<div class="d-flex align-items-center gap-2">'
                        . '<span class="fw-semibold text-dark maid-name notranslate" translate="no" dir="auto" data-maid-display="' . $name . '" aria-label="' . $name . '"></span>'
                        . $pinnedBadge
                        . '</div>'
                        . $cvCodeLine
                        . '<div class="text-muted text-medium">'
                        . '<i class="fas fa-passport me-1 text-secondary"></i>' . $passport
                        . '</div>'
                        . '<div class="text-muted text-medium">'
                        . '<i class="fas fa-flag me-1 text-secondary"></i>' . $nationality
                        . '</div>'
                        . '<div class="text-muted text-medium">'
                        . '<i class="fas fa-praying-hands me-1 text-secondary"></i>' . $religion
                        . '</div>'
                        . '<div class="text-muted text-medium">'
                        . '<i class="fas fa-diagram-project me-1 text-secondary"></i>' . helperTrans('ingaz.request_type') . ': ' . $requestType
                        . '</div>'
                        . '</div>'
                        . '</div>';
                })
                ->addColumn('added_info', function (Maid $row) {
                    $addedOnLabel = helperTrans('ingaz.added_on');
                    $addedByLabel = helperTrans('ingaz.added_by');
                    $recentLabel = helperTrans('ingaz.recently_added');
                    $addedAt = $row->created_at
                        ? $row->created_at->timezone(config('app.timezone'))->format('Y-m-d h:i A')
                        : helperTrans('ingaz.not_specified');
                    // $addedBy = $row->added_by_name
                    //     ? e($row->added_by_name)
                    //     : helperTrans('ingaz.not_specified');
                    $addedBy = $row->addedBy->name ?? __('ingaz.undefined');
                    $isRecent = $row->created_at ? $row->created_at->gte(Carbon::now()->subDay()) : false;
                    $recentBadge = $isRecent
                        ? '<span class="badge bg-success-subtle text-success d-flex align-items-center gap-2 p-0"><i class="fas fa-star me-1"></i>' . $recentLabel . '</span>'
                        : '';

                    $officeTitle = $row->office?->title ? e(Str::limit($row->office->title, 30)) : helperTrans('ingaz.not_specified');

                    return '<div class="d-flex flex-column gap-2">'
                        . '<div class="text-muted text-medium d-flex align-items-center gap-2">'
                        . '<i class="fas fa-clock text-secondary"></i>'
                        . '<span class="p-0">' . $addedOnLabel . ': <span class="fw-medium text-dark p-0">' . $addedAt . '</span></span>'
                        . '</div>'
                        . '<div class="text-muted text-medium d-flex align-items-center gap-2 p-0">'
                        . '<i class="fas fa-user-plus text-secondary"></i>'
                        . '<span class="p-0">' . $addedByLabel . ': <span class="fw-medium text-primary p-0">' . $addedBy . '</span></span>'
                        . '</div>'
                        . ($recentBadge
                            ? '<div class="mt-1">' . $recentBadge . '</div>'
                            : '')

                        . '<div class="text-muted text-medium mt-1 d-flex align-items-center gap-2 p-0">'
                        . '<i class="fas fa-building text-secondary ps-1"></i>' . __('ingaz.recruitment_office') . ": " . $officeTitle
                        . '</div>'
                        . '</div>';
                })
                ->addColumn('status_badge', function (Maid $row) {
                    $labelAvailable = helperTrans('ingaz.maid_status_available');
                    $labelReserved = helperTrans('ingaz.maid_status_reserved');
                    $labelContracted = helperTrans('ingaz.maid_status_contracted');
                    $labelAccommodation = helperTrans('ingaz.maid_status_in_accommodation');
                    $labelBackout = helperTrans('ingaz.maid_status_backout');
                    $labelUnknown = helperTrans('ingaz.maid_status_unknown');
                    $labelSuspended = helperTrans('ingaz.maid_status_suspended');
                    $labelDeported = helperTrans('ingaz.maid_status_deported');
                    $statuses = [
                        'available' => ['class' => 'bg-success-subtle text-success', 'icon' => 'fa-check-circle', 'label' => $labelAvailable],
                        'reserved' => ['class' => 'bg-warning-subtle text-warning', 'icon' => 'fa-hand-paper', 'label' => $labelReserved],
                        'contracted' => ['class' => 'bg-primary-subtle text-primary', 'icon' => 'fa-file-signature', 'label' => $labelContracted],
                        // 'in_accommodation' => ['class' => 'bg-info-subtle text-info', 'icon' => 'fa-warehouse', 'label' => $labelAccommodation],
                        'backout' => ['class' => 'bg-danger-subtle text-danger', 'icon' => 'fa-undo', 'label' => $labelBackout],
                        'deported' => ['class' => 'bg-secondary-subtle text-secondary', 'icon' => 'fa-plane-departure', 'label' => $labelDeported],
                        'suspended' => ['class' => 'bg-dark-subtle text-dark', 'icon' => 'fa-ban', 'label' => $labelSuspended],
                    ];

                    $statusMeta = $statuses[$row->status] ?? ['class' => 'bg-secondary-subtle text-secondary', 'icon' => 'fa-question-circle', 'label' => $labelUnknown];

                    $hasActiveRecruitmentContract = $row->contracts
                        ->whereIn('status', $this->recruitmentContractStatuses())
                        ->isNotEmpty();
                    $hasActiveOrder = $row->orders->whereIn('status', $this->activeOrderStatuses())->isNotEmpty();

                    if ($row->maid_accommodation_type == 2) {
                        $statusMeta = ['class' => 'bg-info-subtle text-info', 'icon' => 'fa-warehouse', 'label' => $labelAccommodation];
                        if ($row->status == 'deported') {
                            $statusMeta = ['class' => 'bg-secondary-subtle text-secondary', 'icon' => 'fa-plane-departure', 'label' => $labelDeported];
                        }
                    } elseif ($hasActiveRecruitmentContract && !in_array($row->status, ['backout', 'suspended', 'deported'], true)) {
                        $statusMeta = $statuses['contracted'];
                    } elseif ($row->status === 'reserved' || (($hasActiveOrder || !$row->available) && $row->status === 'available')) {
                        $statusMeta = $statuses['reserved'];
                    }

                    $visibilityLabel = $row->is_show ? helperTrans('ingaz.maid_visibility_visible') : helperTrans('ingaz.maid_visibility_hidden');
                    $availabilityLabel = $row->available ? helperTrans('ingaz.maid_available_now') : helperTrans('ingaz.maid_not_available_now');
                    $visibilityBadge = $row->is_show
                        ? '<span class="badge text-success d-flex align-items-center gap-2 p-0"> <i class="fas fa-eye"></i> ' . $visibilityLabel . '</span>'
                        : '<span class="badge text-dark d-flex align-items-center gap-2 p-0"><i class="fas fa-eye-slash"></i>' . $visibilityLabel . '</span>';
                    $availabilityBadge = $row->available
                        ? '<span class="badge text-success d-flex align-items-center gap-2 p-0"> <i class="fas fa-circle-check"></i>' . $availabilityLabel . '</span>'
                        : '<span class="badge text-muted d-flex align-items-center gap-2 p-0"> <i class="fas fa-circle-xmark"></i>' . $availabilityLabel . '</span>';
                    $createdAt = $row->created_at?->format('Y-m-d') ?? '-';
                    $today = Carbon::today();

                    $offerBadge = '';
                    if ($row->offer_price !== null) {
                        $startsAt = $row->offer_starts_at;
                        $expiresAt = $row->offer_expires_at;

                        $state = 'expired';
                        if ((!$startsAt || $startsAt->lte($today)) && (!$expiresAt || $expiresAt->gte($today))) {
                            $state = 'active';
                        } elseif ($startsAt && $startsAt->gt($today)) {
                            $state = 'scheduled';
                        }

                        $offerClasses = [
                            'active' => 'bg-warning-subtle text-warning border border-warning-subtle',
                            'scheduled' => 'bg-info-subtle text-info border border-info-subtle',
                            'expired' => 'bg-light text-muted border',
                        ];
                        $offerLabels = [
                            'active' => helperTrans('ingaz.offer_badge_active'),
                            'scheduled' => helperTrans('ingaz.offer_badge_scheduled'),
                            'expired' => helperTrans('ingaz.offer_badge_expired'),
                        ];

                        $offerBadge = '<span class="badge d-flex align-items-center gap-2 p-0' . ($offerClasses[$state] ?? 'bg-light text-muted border') . ' d-inline-flex align-items-center gap-2">'
                            . '<i class="fas fa-tags"></i>' . ($offerLabels[$state] ?? helperTrans('ingaz.offer_badge'))
                            . '</span>';
                    }

                    $statusBadge = '<span class="badge d-flex align-items-center gap-2 p-0 ' . $statusMeta['class'] . ' d-inline-flex align-items-center gap-2">'
                        . '<i class="fas ' . $statusMeta['icon'] . '"></i>' . $statusMeta['label']
                        . '</span>';

                    $statusLine = $offerBadge
                        ? '<div class="d-flex flex-wrap gap-2">' . $offerBadge . $statusBadge . '</div>'
                        : $statusBadge;

                    return '<div class="d-flex flex-column gap-3">'

                        // Status & Offer
                        . '<div class="d-flex flex-column gap-3">'
                        . ($offerBadge ?? '')
                        . $statusBadge
                        . '</div>'

                        // Visibility & Availability
                        . '<div class="d-flex flex-column gap-3">'
                        . $visibilityBadge
                        . $availabilityBadge
                        . '</div>'

                        // Created at
                        . '<div class="text-muted text-medium d-flex align-items-center gap-2">'
                        . '<i class="fas fa-calendar-day text-secondary"></i>'
                        . '<span style=" white-space: nowrap;">' . $createdAt . '</span>'
                        . '</div>'

                        . '</div>';
                })
                ->addColumn('job_info', function (Maid $row) {
                    $jobTypes = e($row->jobTypes->pluck('title')->filter()->take(3)->implode(', ') ?: helperTrans('ingaz.not_specified'));
                    $primaryJobTypeId = (int) ($row->jobTypes->first()?->id ?? 0);
                    $jobTypeClass = match ($primaryJobTypeId) {
                        1 => 'istqdam',
                        2 => 'egar',
                        3 => 'transfer',
                        default => '',
                    };
                    $jobTypeHtml = '<span class="job-type ' . $jobTypeClass . '">' . $jobTypes . '</span>';

                    $officeTitle = $row->office?->title ? e(Str::limit($row->office->title, 30)) : helperTrans('ingaz.not_specified');
                    $salaryValue = $row->resolved_salary;
                    $priceValue = $row->resolved_price;
                    $salary = $salaryValue !== null ? number_format($salaryValue, 2) : helperTrans('ingaz.not_specified');
                    $price = $priceValue !== null ? number_format($priceValue, 2) : helperTrans('ingaz.not_specified');
                    $jobTypesLabel = helperTrans('ingaz.job_types');
                    $offerInfo = '';
                    $today = Carbon::today();
                    $requestTypeBadge = '<span class="badge bg-light text-dark border d-inline-flex align-items-center gap-2 p-0">'
                        . '<i class="fas fa-diagram-project text-secondary"></i>'
                        . '<span>' . helperTrans('ingaz.request_type') . ': </span>' . e($row->request_type_label)
                        . '</span>';

                    if ($row->offer_price !== null) {
                        $startsAt = $row->offer_starts_at;
                        $expiresAt = $row->offer_expires_at;

                        $state = 'expired';
                        if ((!$startsAt || $startsAt->lte($today)) && (!$expiresAt || $expiresAt->gte($today))) {
                            $state = 'active';
                        } elseif ($startsAt && $startsAt->gt($today)) {
                            $state = 'scheduled';
                        }

                        $stateClasses = [
                            'active' => 'bg-warning-subtle text-warning border border-warning-subtle',
                            'scheduled' => 'bg-info-subtle text-info border border-info-subtle',
                            'expired' => 'bg-light text-muted border',
                        ];
                        $stateIcon = [
                            'active' => 'fa-bolt',
                            'scheduled' => 'fa-clock',
                            'expired' => 'fa-history',
                        ];

                        $offerPrice = number_format((float) $row->offer_price, 2);
                        $description = $row->offer_description ? e(Str::limit($row->offer_description, 80)) : null;

                        $offerInfo = '<div class="text-medium rounded-3 px-3 py-2 mt-1 ' . ($stateClasses[$state] ?? 'bg-light text-muted border') . '">'
                            . '<div class="fw-semibold d-flex align-items-center gap-2">'
                            . '<i class="fas fa-tags"></i>'
                            . '<span>' . helperTrans('ingaz.offer_price_new') . ': ' . $offerPrice . ' ' . helperTrans('ingaz.SAR') . '</span>'
                            . '</div>'
                            . '<div class="d-flex flex-column gap-1 mt-2">';

                        $offerInfo .= '<span><i class="fas ' . ($stateIcon[$state] ?? 'fa-info-circle') . ' me-1"></i>' . helperTrans('ingaz.offer_state_' . $state) . '</span>';
                        if ($description) {
                            $offerInfo .= '<span><i class="fas fa-clipboard-list me-1"></i>' . $description . '</span>';
                        }

                        $offerInfo .= '</div></div>';
                    }

                    $availableAt = $row->available_at ? Carbon::parse($row->available_at)->format('Y-m-d') : helperTrans('ingaz.not_specified');
                    // $experienceYears = $row->experience_status === 'has_experience' ? ($row->experience_years ?? '0') : '0';
                    $experienceYears = $row->experience_status === 'has_experience' ? (format_experience($row->experience_years) ?? '0') : '0';
                    $availableFromLabel = helperTrans('ingaz.available_from');
                    $experienceLabel = helperTrans('ingaz.experience_years');

                    $experienceHtml = $experienceYears > 0 && $experienceYears != 'null' ? '<div class="text-muted text-medium d-flex flex-column gap-3">
                            <span class="mx-1"><i class="fas fa-user-graduate me-1 text-secondary"></i>' . $experienceLabel . ': ' . $experienceYears .'</span>
                        </div>' : null;

                    return '<div class="d-flex flex-column gap-3">'

                        // Job type (Primary)
                        . '<div class="fw-semibold text-dark">'
                        . $jobTypeHtml
                        . '</div>'

                        . '<div class="d-flex flex-wrap gap-3">'
                        . $requestTypeBadge
                        . '</div>'

                        // Office
                        // . '<div class="text-muted small d-flex align-items-center gap-2">'
                        // . '<i class="fas fa-building text-secondary"></i>'
                        // . '<span>' . $officeTitle . '</span>'
                        // . '</div>'

                        // Salary & Price
                        . '<div class="d-flex flex-column gap-3">'
                        . '<span class="badge bg-success-subtle text-success d-inline-flex align-items-center gap-2 p-0">'
                        . '<i class="fas fa-hand-holding-usd"></i>'
                        . '<span class=""text-muted">'. helperTrans('ingaz.salary') .' : </span>' .$salary
                        . '</span>'
                        // . '<span class="badge bg-primary-subtle text-primary d-inline-flex align-items-center gap-2 p-0">'
                        // . '<i class="fas fa-tag"></i>'
                        // . '<span class=""text-muted">'. helperTrans('ingaz.price') .' : </span>' .$price
                        // . '</span>'
                        . '</div>'

                        . $experienceHtml

                        // Offer info (if exists)
                        . ($offerInfo ? '<div class="mt-1">' . $offerInfo . '</div>' : '')

                        . '</div>';
                })
                ->addColumn('metrics', function (Maid $row) {
                    $ordersTotal = $row->orders->count();
                    $activeOrders = $row->orders->whereIn('status', $this->activeOrderStatuses())->count();
                    $contractsTotal = $row->contracts->count();
                    $activeContracts = $row->contracts->whereIn('status', $this->recruitmentContractStatuses())->count();

                    $ordersLabel = helperTrans('ingaz.total_orders_count');
                    $activeOrdersLabel = helperTrans('ingaz.active_orders_count');
                    $contractsLabel = helperTrans('ingaz.recruitment_contracts_count');
                    $activeContractsLabel = helperTrans('ingaz.active_contracts_count');

                    // HTML Elements
                    $ordersHTML = $ordersTotal > 0 ? '<span class="badge bg-light text-muted border"><i class="fas fa-clipboard-list me-1"></i>' . $ordersLabel . ': ' . $ordersTotal . '</span>' : null;
                    $activeOrdersHTML = $activeOrders > 0 ? '<span class="badge bg-warning-subtle text-warning border border-warning-subtle"><i class="fas fa-tasks me-1"></i>' . $activeOrdersLabel . ': ' . $activeOrders . '</span>' : null;
                    $contractsHTML = $contractsTotal > 0 ? '<span class="badge bg-info-subtle text-info border border-info-subtle"><i class="fas fa-file-contract me-1"></i>' . $contractsLabel . ': ' . $contractsTotal . '</span>' : null;
                    $activeContractsHTML = $activeContracts > 0 ? '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle"><i class="fas fa-clipboard-check me-1"></i>' . $activeContractsLabel . ': ' . $activeContracts . '</span>' : null;


                    return $ordersTotal < 1 && $activeOrders < 1 && $contractsTotal < 1 && $activeContracts < 1 ? '<span class="text-primary text-center mx-auto">__</span>' : '<div class="d-flex flex-column gap-3">'
                        . '<div class="d-flex flex-wrap gap-3">'
                        . $ordersHTML
                        . $activeOrdersHTML
                        . '</div>'
                        . '<div class="d-flex flex-wrap gap-3">'
                        . $contractsHTML
                        . $activeContractsHTML
                        . '</div>'
                        . '</div>';
                })
                ->addColumn('actions', function (Maid $row) {
                    $menuItems = '';
                    $today = Carbon::today();
                    $offerActive = $row->offer_price !== null
                        && (!$row->offer_starts_at || $row->offer_starts_at->lte($today))
                        && (!$row->offer_expires_at || $row->offer_expires_at->gte($today));

                    if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_SHOW)) {
                        $menuItems .= '<li><a class="dropdown-item" href="' . route('showMaidDetails', $row->id) . '"><i class="fas fa-eye text-primary me-2"></i>' . helperTrans('ingaz.view_details') . '</a></li>';
                        $menuItems .= '<li><a class="dropdown-item" href="' . route('showMaidDetails', $row->id) . '#maid-attachments"><i class="fas fa-paperclip text-info me-2"></i>' . helperTrans('ingaz.attachments') . '</a></li>';
                    }

                    if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_EDIT)) {
                        $menuItems .= '<li><a class="dropdown-item" href="' . route($this->mainRoute . '.edit', $row->id) . '"><i class="fas fa-edit text-secondary me-2"></i>' . helperTrans('ingaz.edit_maid') . '</a></li>';
                        if ($row->available && $row->status === 'available') {
                            $menuItems .= '<li><button type="button" class="dropdown-item manage-offer" data-id="' . $row->id . '" data-name="' . e($row->name ?? '') . '" data-offer-price="' . e($row->offer_price ?? '') . '" data-offer-duration="' . e($row->offer_duration ?? '') . '" data-offer-description="' . e($row->offer_description ?? '') . '" data-offer-start="' . ($row->offer_starts_at ? $row->offer_starts_at->format('Y-m-d') : '') . '" data-offer-expire="' . ($row->offer_expires_at ? $row->offer_expires_at->format('Y-m-d') : '') . '" data-offer-active="' . ($offerActive ? 'true' : 'false') . '"><i class="fas fa-tags text-warning me-2"></i>' . helperTrans('ingaz.manage_offer') . '</button></li>';
                        }
                    }

                    if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_TOGGLE_STATUS)) {
                        $menuItems .= '<li><button class="dropdown-item update-status" data-id="' . $row->id . '"><i class="fas fa-adjust text-muted me-2"></i>' . helperTrans('ingaz.toggle_visibility') . '</button></li>';
                    }

                    if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_EDIT)) {
                        if ($row->status == "suspended") {
                            $menuItems .= '  <a  href="javascript:void(0)" onclick="suspendCv(' . $row->id . ',' . 0 . ')"  title="' . helperTrans('ingaz.unsuspend_cv') . '" class="dropdown-item"><i class="fas fa-play-circle text-success me-2"></i>' . helperTrans('ingaz.unsuspend_cv') . '</a>';
                        } elseif ($row->status == "available") {
                            $menuItems .= ' <a href="javascript:void(0)" onclick="suspendCv(' . $row->id . ',' . 1 . ')"  title="' . helperTrans('ingaz.suspend_cv') . '" class="dropdown-item"><i class="fas fa-pause-circle text-warning me-2"></i>' . helperTrans('ingaz.suspend_cv') . '</a>';
                        }
                    }

                    if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_EDIT)) {
                        if ($row->status == "available" || $row->status == "suspended") {
                            $menuItems .= '<a href="javascript:void(0)" onclick="back_out(' . $row->id . ')" title="' . helperTrans('ingaz.backout') . '" class="dropdown-item  "><i class="fas fa-toggle-off text-danger me-2"></i>' . helperTrans('ingaz.backout') . '</a>';
                        } elseif ($row->status == "backout") {
                            $menuItems .= '<a href="javascript:void(0)" onclick="back_out_on(' . $row->id . ')" title="' . helperTrans('ingaz.return_available') . '" class="dropdown-item  "><i class="fas fa-toggle-on text-success me-2"></i>' . helperTrans('ingaz.return_available') . '</a>';
                        }
                    }


                    if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_TOGGLE_PIN) && $row->available == 1) {
                        $pinLabel = $row->is_pinned ? helperTrans('ingaz.unpin_maid') : helperTrans('ingaz.pin_maid');
                        $menuItems .= '<li><button class="dropdown-item toggle-pin" data-id="' . $row->id . '" data-pinned="' . ($row->is_pinned ? 'true' : 'false') . '"><i class="fas fa-thumbtack text-warning me-2"></i>' . $pinLabel . '</button></li>';
                    }

                    if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_DELETE) && $this->canDeleteMaidCv($row)) {
                        $menuItems .= '<li><div class="dropdown-item" style="padding: 4px 16px !important;">' . deleteButton(route($this->mainRoute . ".destroy", $row->id)) . '</div></li>';
                    }

                    return '<div class="dropdown actions-dropdown">
                                <button class="btn btn-sm btn-primary dropdown-toggle actions-btn" type="button" aria-expanded="false">
                                    <i class="fas fa-cogs me-1"></i>' . helperTrans('ingaz.actions_menu') . '
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-lg">
                                    ' . $menuItems . '
                                </ul>
                            </div>';
                })
                ->rawColumns(['photo', 'profile', 'added_info', 'status_badge', 'job_info', 'metrics', 'actions'])
                ->with('stats', $stats)
                ->make(true);
        }

        $statsBase = objModel::query();
        $this->applyRecruitmentMaidsScope($statsBase);
        $stats = $this->buildRecruitmentMaidStats($statsBase);

        $filters = [
            'nationalities' => Nationality::orderByTranslation('sub_title', 'asc')->get(['id']),
            'offices' => $officeAct->getAllOffices(),
            'job_types' => $this->getRecruitmentIndexJobTypes(),
            'request_types' => objModel::getRequestTypeOptions(),
            'statuses' => [
                'available' => helperTrans('ingaz.maid_status_available'),
                'reserved' => helperTrans('ingaz.maid_status_reserved'),
                'contracted' => helperTrans('ingaz.maid_status_contracted'),
                'in_accommodation' => helperTrans('ingaz.maid_status_in_accommodation'),
                'backout' => helperTrans('ingaz.maid_status_backout'),
                'suspended' => helperTrans('ingaz.maid_status_suspended'),
                'deported' => helperTrans('ingaz.maid_status_deported'),
            ],
        ];

        return view($this->folderPath . '.index', [
            'stats' => $stats,
            'filters' => $filters,
            'dataTableRoute' => route($this->mainRoute . '.index'),
            'createRoute' => route($this->mainRoute . '.create'),
        ]);
    }

    public function rentalIndex(Request $request, RecruitmentOfficeAction $officeAct)
    {
        $rentalJobTypeId = $this->getRentalJobTypeId();

        if (!$rentalJobTypeId) {
            return redirect()
                ->route('maids.index')
                ->with('error', __('ingaz.rental_cv_type_missing'));
        }

        $request->merge(['job_type' => $rentalJobTypeId]);

        if ($request->ajax()) {
            $rows = objModel::query()
                ->with([
                    'nationality',
                    'religion',
                    'jobTypes',
                    'contracts',
                    'orders',
                    'rentalContracts' => function ($query) {
                        $query->select(['id', 'maid_id', 'status']);
                    },
                    'rentalFinancialAdjustments' => function ($query) {
                        $query->where('context', 'maid')
                            ->select(['id', 'maid_id', 'type', 'label', 'amount', 'settled_at']);
                    },
                ])
                ->select([
                    'id',
                    'name',
                    'image',
                    'cv_file',
                    'cv_preview',
                    'nationality_id',
                    'religion_id',
                    'passport_num',
                    'cv_code',
                    'price',
                    'salary',
                    'age',
                    'is_show',
                    'available',
                    'available_at',
                    'created_at',
                    'is_pinned',
                    'status',
                    'office_id',
                    'experience_years',
                    'experience_status',
                    'maid_accommodation_type',
                    'id_expiry_date',
                    'request_type',
                ])
                ->orderByDesc('is_pinned')
                ->orderByDesc('available')
                ->orderByDesc('created_at');

            $this->applyMaidFilter($request, $rows);

            return DataTables::of($rows)
                ->addIndexColumn()
                ->editColumn('cv_file', function (Maid $row) {
                    $rawPath = $row->image ?: ($row->cv_preview ?: $row->cv_file);
                    $imageUrl = $rawPath ? showFile($rawPath) : asset('admin/assets/img/avatar.png');

                    return '<a href="' . route('showMaidDetails', $row->id) . '" title="' . helperTrans('ingaz.view_details') . '">'
                        . '<img src="' . $imageUrl . '" alt="maid-preview" class="border rounded-circle" style="width:120px;height:120px;object-fit:cover;">'
                        . '</a>';
                })
                ->addColumn('name', function (Maid $row) {
                    $name = e($row->name ?: helperTrans('ingaz.maid') . ' #' . $row->id);
                    $passport = e($row->passport_num ?: '-');
                    $cvCode = $row->cv_code ? '<span class="small text-muted">' . helperTrans('ingaz.cv_code') . ': ' . e($row->cv_code) . '</span>' : '';
                    $requestType = e($row->request_type_label);
                    $nameHtml = '<span class="fw-semibold maid-name notranslate" translate="no" dir="auto" data-maid-display="' . $name . '" aria-label="' . $name . '"></span>';

                    return '<div class="d-flex flex-column gap-1">'
                        . '<a href="' . route('showMaidDetails', $row->id) . '" class="text-decoration-none notranslate" translate="no" dir="auto">' . $nameHtml . '</a>'
                        . '<span class="small text-muted">' . helperTrans('ingaz.passport') . ': ' . $passport . '</span>'
                        . $cvCode
                        . '<span class="small text-muted">' . helperTrans('ingaz.request_type') . ': ' . $requestType . '</span>'
                        . '</div>';
                })
                ->editColumn('nationality_id', fn(Maid $row) => e($row->nationality?->sub_title ?? '-'))
                ->addColumn('job_type', fn(Maid $row) => e($row->jobTypes->pluck('title')->filter()->implode(', ') ?: '-'))
                ->editColumn('passport_num', fn(Maid $row) => e($row->passport_num ?? '-'))
                ->editColumn('price', fn(Maid $row) => $row->resolved_price !== null ? number_format((float) $row->resolved_price, 2) : '-')
                ->editColumn('salary', fn(Maid $row) => $row->resolved_salary !== null ? number_format((float) $row->resolved_salary, 2) : '-')
                ->addColumn('financial_items', function (Maid $row) {
                    $items = $row->rentalFinancialAdjustments->map(function ($item) {
                        return [
                            'type' => $item->type,
                            'label' => $item->label,
                            'amount' => (float) $item->amount,
                            'settled_at' => optional($item->settled_at)->format('Y-m-d H:i'),
                        ];
                    })->values();

                    if ($items->isEmpty()) {
                        return '<span class="text-muted">' . helperTrans('ingaz.no_financial_items_recorded') . '</span>';
                    }

                    $expenseTotal = $items->where('type', 'expense')->sum('amount');
                    $incomeTotal = $items->where('type', 'income')->sum('amount');

                    return '<button type="button" class="btn btn-sm btn-outline-info rental-finance-view"'
                        . ' data-maid-name="' . e($row->name ?: (helperTrans('ingaz.maid') . ' #' . $row->id)) . '"'
                        . ' data-items="' . e($items->toJson(JSON_UNESCAPED_UNICODE)) . '">'
                        . '<i class="fa fa-wallet"></i> ' . $items->count() . ' ' . helperTrans('ingaz.items')
                        . '</button>'
                        . '<div class="small text-muted mt-1">' . helperTrans('ingaz.expense') . ': ' . number_format((float) $expenseTotal, 2) . '</div>'
                        . '<div class="small text-muted">' . helperTrans('ingaz.income') . ': ' . number_format((float) $incomeTotal, 2) . '</div>';
                })
                ->editColumn('religion_id', fn(Maid $row) => e($row->religion?->title ?? '-'))
                ->editColumn('age', fn(Maid $row) => $row->age ?: '-')
                ->addColumn('maidContractRemaining', function (Maid $row) {
                    $remaining = ahxMaidContractRemaining($row->id);
                    return $remaining == 0 ? __('ingaz.none') : $remaining . ' ' . __('ingaz.days');
                })
                ->addColumn('is_show', function (Maid $row) {
                    return $row->is_show
                        ? '<span class="badge bg-success"><i class="fa fa-eye"></i> ' . helperTrans('ingaz.maid_visibility_visible') . '</span>'
                        : '<span class="badge bg-dark"><i class="fa fa-eye-slash"></i> ' . helperTrans('ingaz.maid_visibility_hidden') . '</span>';
                })
                ->addColumn('is_pinned', function (Maid $row) {
                    return $row->is_pinned
                        ? '<span class="badge bg-warning text-dark"><i class="fa fa-thumb-tack"></i> ' . helperTrans('ingaz.pinned') . '</span>'
                        : '<span class="badge bg-secondary"><i class="fa fa-thumb-tack"></i> ' . helperTrans('ingaz.unpinned') . '</span>';
                })
                ->addColumn('cv_status', function (Maid $row) {
                    if ($row->status === 'backout') {
                        return '<span class="badge bg-danger">' . helperTrans('ingaz.backout') . '</span>';
                    }

                    $hasActiveRecruitmentContracts = $row->contracts->whereIn('status', $this->recruitmentContractStatuses())->isNotEmpty();
                    $hasActiveOrders = $row->orders->whereIn('status', $this->activeOrderStatuses())->isNotEmpty();
                    $hasActiveRentalContracts = $row->rentalContracts->whereIn('status', $this->rentalActiveContractStatuses())->isNotEmpty();
                    $canCreateRentalContract = $this->canCreateRentalContractForMaid($row);

                    if ($canCreateRentalContract) {
                        $badges = ['<span class="badge bg-success">' . helperTrans('ingaz.ready_for_contract') . '</span>'];

                        if ((int) $row->maid_accommodation_type === 2) {
                            $badges[] = '<span class="badge bg-info text-dark">' . helperTrans('ingaz.inside_shelter') . '</span>';
                        }

                        return implode(' ', $badges);
                    }

                    if ($hasActiveRentalContracts) {
                        return '<span class="badge bg-primary">' . helperTrans('ingaz.has_rental_contract') . '</span>';
                    }

                    if (
                        !$row->available
                        || $row->status === 'reserved'
                        || $hasActiveRecruitmentContracts
                        || $hasActiveOrders
                        || $hasActiveRentalContracts
                    ) {
                        return '<span class="badge bg-warning text-dark">' . helperTrans('ingaz.maid_status_reserved') . '</span>';
                    }

                    if ((int) $row->maid_accommodation_type === 2) {
                        return '<span class="badge bg-info text-dark">' . helperTrans('ingaz.inside_shelter') . '</span>';
                    }

                    return '<span class="badge bg-success">' . helperTrans('ingaz.maid_status_available') . '</span>';
                })
                ->editColumn('created_at', fn(Maid $row) => $row->created_at?->format('Y-m-d h:i A') ?? '-')
                ->addColumn('actions', function (Maid $row) {
                    $menuItems = [];
                    $canCreateRentalContract = $this->canCreateRentalContractForMaid($row);
                    $canUseAvailableActions = (bool) $row->available && in_array($row->status, ['available', 'completed'], true);

                    if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_SHOW) || @AdminGuardHasPerm(\App\PermKeys::MAIDS_RENTAL_VIEW)) {
                        $menuItems[] = '<li><a class="dropdown-item" href="' . route('showMaidDetails', $row->id) . '">
                            <i class="fas fa-eye text-primary me-2"></i>' . helperTrans('ingaz.view_details') . '
                        </a></li>';
                        $menuItems[] = '<li><a class="dropdown-item" href="' . route('showMaidDetails', $row->id) . '#maid-attachments">
                            <i class="fas fa-paperclip text-info me-2"></i>' . helperTrans('ingaz.attachments') . '
                        </a></li>';
                    }

                    if (@AdminGuardHasPerm(\App\PermKeys::RENT_CONTRACT_CREATE) && $canCreateRentalContract) {
                        $menuItems[] = '<li><a class="dropdown-item" href="' . route('RentContract.create', ['maid_id' => $row->id]) . '">
                            <i class="fas fa-file-signature text-success me-2"></i>' . helperTrans('ingaz.create_rental_contract') . '
                        </a></li>';
                    }

                    if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_EDIT)) {
                        $menuItems[] = '<li><a class="dropdown-item" href="' . route($this->mainRoute . '.edit', $row->id) . '">
                            <i class="fas fa-pen text-secondary me-2"></i>' . helperTrans('ingaz.edit_maid') . '
                        </a></li>';
                    }

                    if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_TOGGLE_STATUS)) {
                        $menuItems[] = '<li><button class="dropdown-item update-status" data-id="' . $row->id . '">
                            <i class="fas fa-adjust text-muted me-2"></i>' . helperTrans('ingaz.toggle_visibility') . '
                        </button></li>';
                    }

                    if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_EDIT)) {
                        if ($row->status === 'suspended') {
                            $menuItems[] = '<li><button type="button" class="dropdown-item" onclick="suspendCv(' . $row->id . ',0)">
                                <i class="fas fa-play-circle text-success me-2"></i>' . helperTrans('ingaz.unsuspend_cv') . '
                            </button></li>';
                        } elseif ($canUseAvailableActions) {
                            $menuItems[] = '<li><button type="button" class="dropdown-item" onclick="suspendCv(' . $row->id . ',1)">
                                <i class="fas fa-pause-circle text-warning me-2"></i>' . helperTrans('ingaz.suspend_cv') . '
                            </button></li>';
                        }

                        if ($row->status === 'backout') {
                            $menuItems[] = '<li><button type="button" class="dropdown-item" onclick="back_out_on(' . $row->id . ')">
                                <i class="fas fa-toggle-on text-success me-2"></i>' . helperTrans('ingaz.return_available') . '
                            </button></li>';
                        } elseif (in_array($row->status, ['available', 'completed', 'suspended'], true)) {
                            $menuItems[] = '<li><button type="button" class="dropdown-item" onclick="back_out(' . $row->id . ')">
                                <i class="fas fa-toggle-off text-danger me-2"></i>' . helperTrans('ingaz.backout') . '
                            </button></li>';
                        }
                    }

                    if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_TOGGLE_PIN) && $canUseAvailableActions) {
                        $pinLabel = $row->is_pinned ? helperTrans('ingaz.unpin_maid') : helperTrans('ingaz.pin_maid');
                        $menuItems[] = '<li><button class="dropdown-item toggle-pin" data-id="' . $row->id . '" data-pinned="' . ($row->is_pinned ? 'true' : 'false') . '">
                            <i class="fas fa-thumbtack text-warning me-2"></i>' . $pinLabel . '
                        </button></li>';
                    }

                    if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_DELETE) && $this->canDeleteMaidCv($row)) {
                        $menuItems[] = '<li><div class="px-2 py-1">' . deleteButton(route($this->mainRoute . '.destroy', $row->id)) . '</div></li>';
                    }

                    if (empty($menuItems)) {
                        return '<span class="text-muted">-</span>';
                    }

                    return '<div class="dropdown actions-dropdown">
                                <button class="btn btn-sm btn-primary dropdown-toggle actions-btn" type="button" aria-expanded="false">
                                    <i class="fas fa-cogs me-1"></i>' . helperTrans('ingaz.actions_menu') . '
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-lg">
                                    ' . implode('', $menuItems) . '
                                </ul>
                            </div>';
                })
                ->rawColumns(['cv_file', 'name', 'financial_items', 'is_show', 'is_pinned', 'cv_status', 'actions'])
                ->make(true);
        }

        $filter = [
            'nationalities' => Nationality::orderByTranslation('sub_title', 'asc')->get(['id']),
            'offices' => $officeAct->getAllOffices(),
            'job_types' => JobType::with('translations')->orderByTranslation('title', 'asc')->get(['id']),
            'request_types' => objModel::getRequestTypeOptions(),
        ];

        $defaultFilters = array_filter([
            'keyword' => $request->keyword,
            'passport_num' => $request->passport_num,
            'nationality_id' => $request->nationality_id,
            'office_id' => $request->office_id,
            'job_type' => $rentalJobTypeId,
            'experience_years' => $request->experience_years,
            'cv_status' => $request->cv_status,
            'visibility' => $request->visibility,
            'experience_status' => $request->experience_status,
            'request_type' => $request->request_type,
            'pinned' => $request->pinned,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'rental_stat' => $request->rental_stat,
        ], static fn($value) => !is_null($value) && $value !== '');

        return view($this->folderPath . '.rental-index', [
            'filter' => $filter,
            'request' => $request,
            'createRoute' => route('maids.rental.create'),
            'dataTableRoute' => route('maids.rental.index'),
            'resetRoute' => route('maids.rental.index'),
            'pageBaseUrl' => route('maids.rental.index'),
            'bladeTitle' => helperTrans('ingaz.rental_maids_title'),
            'addButtonText' => helperTrans('ingaz.add_rental_cv'),
            'defaultFilters' => $defaultFilters,
            'enforcedFilters' => ['job_type' => $rentalJobTypeId],
            'rentalStats' => $this->calculateRentalStats($rentalJobTypeId),
            'showRentalStats' => true,
            'isRentalPage' => true,
            'rentalTypeId' => $rentalJobTypeId,
        ]);
    }

    public function noneAvailableMaids(Request $request, objAction $Action)
    {
        $rows = objModel::latest()
            ->with(['nationality', 'religion', 'jobTypes', 'contracts', 'orders', 'unavailableRecords'])
            ->select(['id', 'name', 'cv_code', 'image', 'cv_file', 'cv_preview', 'nationality_id', 'religion_id', 'passport_num', 'price', 'salary', 'age', 'is_show', 'available', 'available_at', 'created_at', 'is_pinned', 'status', 'back_out_reason'])
            ->where('status', 'backout')
            ->where('available', 0);

        if ($request->ajax()) {
            // تطبيق الفلاتر الإضافية مع الحفاظ على فلتر backout
            $this->applyMaidFilterForBackout($request, $rows);

            $dataTable = DataTables::of($rows)
                ->addIndexColumn()
                ->editColumn('is_show', function ($row) {
                    if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_TOGGLE_STATUS)) {
                        return $row->is_show == 1 ?
                            '<button class="btn btn-sm btn-success update-status" data-id="' . $row->id . '">' . helperTrans('ingaz.maid_visibility_visible') . '</button>' :
                            '<button class="btn btn-sm btn-dark update-status" data-id="' . $row->id . '">' . helperTrans('ingaz.maid_visibility_hidden') . '</button>';
                    } else {
                        return $row->is_show == 1 ? '<span class="badge bg-success">' . helperTrans('ingaz.maid_visibility_visible') . '</span>' : '<span class="badge bg-dark">' . helperTrans('ingaz.maid_visibility_hidden') . '</span>';
                    }
                })
                ->editColumn('available', function ($row) {
                    return $row->available == 1 ?
                        '<i class ="fa fa-check text-success">' : '<i class ="fa fa-times text-danger">';
                })
                ->editColumn('available_at', function ($row) {
                    return $row->available_at <= date('Y-m-d') ?
                        '<i class="fa fa-check text-success"></i>' :
                        $row->available_at;
                })
                ->editColumn('cv_file', function ($row) {
                    $rawPath = $row->image ?: ($row->cv_preview ?: $row->cv_file);
                    $imageUrl = $rawPath ? showFile($rawPath) : asset('admin/assets/img/avatar.png');

                    $imgTag = '<img src="' . $imageUrl . '" alt="maid-file" class="maid-cv-thumb">';

                    return '<a href="#" class="maidViewData" data-id="' . $row->id . '">' . $imgTag . '</a>';
                })
                ->editColumn('created_at', function ($row) {
                    return ($row->created_at->format('Y-m-d h:i A'));
                })
                ->editColumn('religion_id', function ($row) {
                    return ($row->religion) ? $row->religion->title : '';
                })
                ->editColumn('nationality_id', function ($row) {
                    return ($row->nationality) ? $row->nationality->title : '';
                })
                ->addColumn('maidContractRemaining', function ($row) {
                    $hasRemainingContracts = ahxMaidContractRemaining($row->id);
                    if ($hasRemainingContracts == 0) {
                        return __('ingaz.none');
                    } else {
                        return $hasRemainingContracts . ' ' . __('ingaz.days');
                    }
                })
                ->addColumn('job_type', function ($row) {
                    return $row->jobTypes->pluck('title')->implode(', ');
                })
                ->addColumn('cv_status', function ($row) {
                    // تحديد حالة السيرة الذاتية بناءً على الحقول المتاحة
                    if ($row->status === 'backout') {
                        return '<span class="badge bg-danger">' . helperTrans('ingaz.backout') . '</span>';
                    } elseif ($row->available == 0) {
                        return '<span class="badge bg-warning">' . helperTrans('ingaz.maid_status_reserved') . '</span>';
                    } else {
                        // التحقق من وجود عقود أو طلبات نشطة
                        $hasActiveContracts = $row->contracts()->whereIn('status', $this->recruitmentContractStatuses())->exists();
                        $hasActiveOrders = $row->orders()->whereIn('status', ['new', 'under_process', 'contracted', 'contracted_without_contract'])->exists();

                        if ($hasActiveContracts || $hasActiveOrders) {
                            return '<span class="badge bg-warning">' . helperTrans('ingaz.maid_status_reserved') . '</span>';
                        } else {
                            return '<span class="badge bg-success">' . helperTrans('ingaz.maid_status_available') . '</span>';
                        }
                    }
                })
                ->addColumn('unavailable_reason', function ($row) {
                    $unavailableRecord = $row->unavailableRecords()->latest()->first();
                    $reason = $unavailableRecord?->reason ?: $row->back_out_reason;

                    if ($reason) {
                        return '<span class="reason-badge" title="' . e($reason) . '">' .
                            e(Str::limit($reason, 70)) .
                            '</span>';
                    }

                    return '<span class="text-muted">' . helperTrans('ingaz.no_specific_reason') . '</span>';
                })
                ->addColumn('actions', function ($row) {
                    $showButton = '';
                    $editButton = '';
                    $deleteButton = '';

                    $showButton = viewSingleButton(route('showMaidDetails', $row->id));

                    if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_CONTROL)) {
                        $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);
                    }

                    if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_DELETE) && $this->canDeleteMaidCv($row)) {
                        $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    }

                    return $showButton . " " . $editButton . " " . $deleteButton;
                })
                ->rawColumns(['is_show', 'available', 'available_at', 'cv_file', 'cv_status', 'unavailable_reason', 'actions'])
                ->make(true);

            return $dataTable;
        }

        $bladeTitle = helperTrans('ingaz.backout_maids');

        // Fetch filter options for dropdowns
        $filter['nationalities'] = \App\Models\ingaz\Nationality::orderByTranslation('sub_title', 'asc')->get();
        $filter['job_types'] = \App\Models\ingaz\JobType::orderByTranslation('title', 'asc')->get();

        // Prepare data for the view
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route('noneAvailableMaids');
        $data["bladeTitle"] = $bladeTitle;
        $data["addButtonText"] = helperTrans("ingaz.maid");
        $data["modalType"] = "modal-lg";

        // Pass filter options to the view
        return view($this->folderPath . '.none-ava', $data, compact('filter', 'request'));
    }

    public function deletedCvs(Request $request, objAction $Action)
    {
        $rows = objModel::onlyTrashed()
            ->with(['nationality', 'jobTypes', 'deletedBy'])
            ->select([
                'id',
                'name',
                'cv_code',
                'image',
                'cv_file',
                'cv_preview',
                'nationality_id',
                'passport_num',
                'price',
                'salary',
                'deleted_at',
                'deleted_by',
            ])
            ->orderByDesc('deleted_at');

        if ($request->ajax()) {
            $this->applyMaidFilterForDeleted($request, $rows);

            return DataTables::of($rows)
                ->addIndexColumn()
                ->editColumn('cv_file', function ($row) {
                    $rawPath = $row->image ?: ($row->cv_preview ?: $row->cv_file);
                    $imageUrl = $rawPath ? showFile($rawPath) : asset('admin/assets/img/avatar.png');

                    $imgTag = '<img src="' . $imageUrl . '" alt="maid-file" class="border" style="width:90px;height:120px;object-fit:cover;">';

                    return '<a href="#" class="maidViewData" data-id="' . $row->id . '">' . $imgTag . '</a>';
                })
                ->editColumn('name', function ($row) {
                    return e($row->name ?? '');
                })
                ->editColumn('passport_num', function ($row) {
                    return e($row->passport_num ?? '');
                })
                ->editColumn('nationality_id', function ($row) {
                    $nationality = $row->nationality?->sub_title ?? $row->nationality?->title ?? '';
                    return e($nationality);
                })
                ->addColumn('job_type', function ($row) {
                    return e($row->jobTypes->pluck('title')->filter()->implode(', '));
                })
                ->editColumn('price', function ($row) {
                    $price = $row->resolved_price ?? $row->price;
                    return $price !== null ? number_format((float) $price, 2) : '-';
                })
                ->editColumn('salary', function ($row) {
                    $salary = $row->resolved_salary ?? $row->salary;
                    return $salary !== null ? number_format((float) $salary, 2) : '-';
                })
                ->editColumn('deleted_at', function ($row) {
                    return $row->deleted_at ? $row->deleted_at->format('Y-m-d h:i A') : '';
                })
                ->addColumn('deleted_by', function ($row) {
                    return e($row->deletedBy?->name ?? '-');
                })
                ->addColumn('actions', function ($row) {
                    $actions = '';

                    if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_VIEW)) {
                        $actions .= viewSingleButton(route('showMaidDetails', $row->id));
                    }

                    if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_DELETE)) {
                        $actions .= ' <button type="button" class="btn btn-sm btn-success restore-maid" data-id="' . $row->id . '" title="' . helperTrans('ingaz.restore') . '"><i class="fa fa-undo"></i></button>';
                    }

                    return $actions ?: '-';
                })
                ->rawColumns(['cv_file', 'actions'])
                ->make(true);
        }

        $bladeTitle = helperTrans('ingaz.deleted') . ' ' . helperTrans('ingaz.cvs');

        $filter['nationalities'] = \App\Models\ingaz\Nationality::orderByTranslation('sub_title', 'asc')->get();
        $filter['job_types'] = \App\Models\ingaz\JobType::orderByTranslation('title', 'asc')->get();

        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route('maids.deleted_cvs');
        $data["bladeTitle"] = $bladeTitle;
        $data["addButtonText"] = helperTrans("ingaz.maid");
        $data["modalType"] = "modal-lg";

        return view($this->folderPath . '.cvs_deleted', $data, compact('filter', 'request'));
    }

    public function showMaidDetails($id, objAction $action)
    {
        $maid = objModel::withTrashed()->with([
            'nationality',
            'religion',
            'jobTypes',
            'jobTitles',
            'office',
            'addedBy',
            'contracts.info',
            'contracts.user',
            'orders.jobType',
            'orders.user',
            'orders.contracts',
            'expertises.country',
            'expertises.jobTitle',
            'attachments.uploader',
            'rentalFinancialAdjustments' => function ($query) {
                $query->where('context', 'maid')->latest();
            },
        ])->findOrFail($id);

        $action->syncRentalMaidToHrRecord($maid);

        $skills = [];
        $skillsRaw = $maid->skills;
        $decodedSkills = is_string($skillsRaw) ? json_decode($skillsRaw, true) : $skillsRaw;

        if (json_last_error() === JSON_ERROR_NONE && is_array($decodedSkills) && collect($decodedSkills)->every(fn($item) => is_numeric($item) || (is_string($item) && is_numeric($item)))) {
            $ids = collect($decodedSkills)->map(fn($item) => (int) $item)->values();
            $skills = Skill::whereIn('id', $ids)->get()->map(fn($skill) => $skill->title)->toArray();
        } else {
            $skills = collect($decodedSkills ?: [])
                ->filter()
                ->map(function ($value) {
                    if (is_string($value)) {
                        $decoded = json_decode($value, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $value = $decoded;
                        } else {
                            return trim($value);
                        }
                    }

                    if (is_numeric($value)) {
                        return optional(Skill::find($value))->title;
                    }

                    if ($value instanceof \JsonSerializable) {
                        $value = $value->jsonSerialize();
                    }

                    if (is_object($value)) {
                        $value = json_decode(json_encode($value), true);
                    }

                    if (is_array($value)) {
                        if (isset($value['title']) && is_string($value['title'])) {
                            return $value['title'];
                        }

                        if (isset($value['translations']) && is_array($value['translations'])) {
                            $translations = collect($value['translations']);
                            $locale = app()->getLocale();
                            $translation = $translations->firstWhere('locale', $locale)
                                ?? $translations->firstWhere('locale', 'ar')
                                ?? $translations->first();
                            return $translation['title'] ?? null;
                        }
                    }

                    return null;
                })
                ->filter()
                ->map(fn($name) => trim((string) $name))
                ->unique()
                ->values()
                ->toArray();
        }

        $languagesRaw = $maid->languages;
        if (is_string($languagesRaw)) {
            $languagesRaw = json_decode($languagesRaw, true);
        }
        $languages = collect($languagesRaw ?? [])->filter()->values()->map(fn($lang) => is_string($lang) ? $lang : (is_array($lang) && isset($lang['title']) ? $lang['title'] : $lang))->toArray();

        $percentagesRaw = $maid->percentage;
        if (is_string($percentagesRaw)) {
            $percentagesRaw = json_decode($percentagesRaw, true);
        }
        $languagePercentages = collect($percentagesRaw ?? [])->filter()->toArray();

        $orders = $maid->orders->sortByDesc('created_at');
        $contracts = $maid->contracts->sortByDesc('created_at');
        $rentalFinanceItems = $maid->rentalFinancialAdjustments->values();
        $rentalFinanceSummary = [
            'income' => (float) $rentalFinanceItems->where('type', 'income')->sum('amount'),
            'expense' => (float) $rentalFinanceItems->where('type', 'expense')->sum('amount'),
            'net' => (float) $rentalFinanceItems->where('type', 'income')->sum('amount')
                - (float) $rentalFinanceItems->where('type', 'expense')->sum('amount'),
        ];
        $isRentalMaid = $this->isRentalMaid($maid);
        $backToListRoute = $isRentalMaid ? route('maids.rental.index') : route('maids.index');
        $hrEmployeeRecord = null;

        if (class_exists(\Nami\Hr\Models\Employee::class)
            && \Illuminate\Support\Facades\Schema::hasTable('hr_employee')
            && \Nami\Hr\Models\Employee::hasEmployeeCategoryColumn()) {
            $hrEmployeeRecord = \Nami\Hr\Models\Employee::query()
                ->withAllCategories()
                ->with(['departments', 'jobs'])
                ->where('maid_id', $maid->id)
                ->first();
        }

        $stats = [
            'orders_total' => $orders->count(),
            'orders_active' => $orders->whereIn('status', ['new', 'under_process', 'contracted', 'contracted_without_contract'])->count(),
            'contracts_total' => $contracts->count(),
            'contracts_active' => $contracts->whereIn('status', $this->recruitmentContractStatuses())->count(),
            'experience_years' => $maid->experience_status === 'has_experience' ? ($maid->experience_years ?? 0) : 0,
        ];

        $effectiveStatus = $maid->status;
        $effectiveAvailable = (bool) $maid->available;

        if ($maid->maid_accommodation_type == 2 && $maid->status !== 'deported') {
            $effectiveStatus = 'in_accommodation';
            $effectiveAvailable = false;
        } elseif ($maid->status !== 'backout' && $stats['contracts_active'] > 0) {
            $effectiveStatus = 'contracted';
            $effectiveAvailable = false;
        } elseif ($maid->status !== 'backout' && ($stats['orders_active'] > 0 || !$maid->available)) {
            $effectiveStatus = 'reserved';
            $effectiveAvailable = false;
        }

        $availability = [
            'status' => $effectiveStatus,
            'is_show' => $maid->is_show,
            'available' => $effectiveAvailable,
            'available_at' => $maid->available_at,
            'created_at' => $maid->created_at,
        ];

        $activities = method_exists($maid, 'activities')
            ? $this->buildActivityTimeline(
                $maid->activities()->with('causer')->latest()->limit(12)->get(),
                [
                    'name' => helperTrans('ingaz.name'),
                    'nationality_id' => helperTrans('ingaz.nationality'),
                    'religion_id' => helperTrans('ingaz.religion'),
                    'passport_num' => helperTrans('ingaz.passport_num'),
                    'price' => helperTrans('ingaz.price'),
                    'salary' => helperTrans('ingaz.salary'),
                    'age' => helperTrans('ingaz.age'),
                    'office_id' => helperTrans('ingaz.recruitment office'),
                    'status' => helperTrans('ingaz.status'),
                    'available' => helperTrans('ingaz.availability_status'),
                    'available_at' => helperTrans('ingaz.available_from'),
                    'is_show' => helperTrans('ingaz.visibility'),
                    'is_pinned' => helperTrans('ingaz.pinned'),
                    'request_type' => helperTrans('ingaz.request_type'),
                    'additional_notes' => helperTrans('ingaz.additional_notes'),
                    'cv_file' => helperTrans('ingaz.cv_file'),
                    'cv_preview' => helperTrans('ingaz.cv_preview'),
                    'image' => helperTrans('ingaz.image'),
                    'video' => helperTrans('ingaz.video'),
                    'video_link' => helperTrans('ingaz.video_link'),
                    'arrival_date' => helperTrans('ingaz.arrival_date'),
                    'offer_price' => helperTrans('ingaz.offer_price_new'),
                    'offer_starts_at' => helperTrans('ingaz.offer_starts_at'),
                    'offer_expires_at' => helperTrans('ingaz.offer_expires_at'),
                ],
                helperTrans('ingaz.cv_label')
            )
            : collect();

        return view($this->folderPath . '.details', compact(
            'maid',
            'orders',
            'contracts',
            'skills',
            'languages',
            'languagePercentages',
            'stats',
            'availability',
            'activities',
            'rentalFinanceItems',
            'rentalFinanceSummary',
            'isRentalMaid',
            'backToListRoute',
            'hrEmployeeRecord'
        ));
    }

    public function storeAttachments(Request $request, $id)
    {
        if (!@AdminGuardHasPerm(\App\PermKeys::MAIDS_EDIT)) {
            abort(403);
        }

        $maid = objModel::withTrashed()->findOrFail($id);

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'attachments' => ['required', 'array', 'min:1'],
            'attachments.*' => ['required', 'file', 'max:20480'],
        ], [
            'attachments.required' => __('ingaz.attachments_required'),
            'attachments.*.max' => __('ingaz.attachment_max_size'),
        ]);

        foreach ($request->file('attachments', []) as $file) {
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $fileName = Str::uuid()->toString() . ($extension ? '.' . $extension : '');
            $path = $file->storeAs('maids/attachments/' . $maid->id, $fileName, 'public');

            MaidAttachment::create([
                'maid_id' => $maid->id,
                'uploaded_by' => auth('admin')->id() ?: auth()->id(),
                'title' => $validated['title'] ?? null,
                'original_name' => $originalName,
                'path' => $path,
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize() ?: 0,
            ]);
        }

        return redirect()
            ->route('showMaidDetails', $maid->id)
            ->with('success', __('ingaz.maid_attachments_uploaded_successfully'));
    }

    public function downloadAttachments(Request $request, $id)
    {
        if (!@AdminGuardHasPerm(\App\PermKeys::MAIDS_VIEW) && !@AdminGuardHasPerm(\App\PermKeys::MAIDS_SHOW) && !@AdminGuardHasPerm(\App\PermKeys::MAIDS_RENTAL_VIEW)) {
            abort(403);
        }

        $maid = objModel::withTrashed()->with('attachments')->findOrFail($id);
        $selectedIds = collect((array) $request->input('attachments', []))
            ->filter(fn($value) => is_numeric($value))
            ->map(fn($value) => (int) $value)
            ->values();

        $attachments = $maid->attachments;
        if ($selectedIds->isNotEmpty()) {
            $attachments = $attachments->whereIn('id', $selectedIds);
        }

        $attachments = $attachments->filter(fn(MaidAttachment $attachment) => $attachment->existsOnDisk())->values();

        if ($attachments->isEmpty()) {
            return redirect()->back()->with('error', __('ingaz.no_valid_attachments_to_download'));
        }

        $zip = new \ZipArchive();
        $zipFile = tempnam(sys_get_temp_dir(), 'maid-attachments-');

        if ($zip->open($zipFile, \ZipArchive::OVERWRITE) !== true) {
            return redirect()->back()->with('error', __('ingaz.attachments_zip_failed'));
        }

        foreach ($attachments as $attachment) {
            $zip->addFile(
                Storage::disk('public')->path($attachment->path),
                $this->buildAttachmentDownloadName($attachment)
            );
        }

        $zip->close();

        if (filesize($zipFile) <= 0) {
            @unlink($zipFile);
            return redirect()->back()->with('error', __('ingaz.no_valid_attachments_to_download'));
        }

        return response()
            ->download($zipFile, 'maid-' . $maid->id . '-attachments.zip')
            ->deleteFileAfterSend(true);
    }

    public function downloadAttachment($id, MaidAttachment $attachment)
    {
        if (!@AdminGuardHasPerm(\App\PermKeys::MAIDS_VIEW) && !@AdminGuardHasPerm(\App\PermKeys::MAIDS_SHOW) && !@AdminGuardHasPerm(\App\PermKeys::MAIDS_RENTAL_VIEW)) {
            abort(403);
        }

        objModel::withTrashed()->findOrFail($id);

        if ((int) $attachment->maid_id !== (int) $id || !$attachment->existsOnDisk()) {
            abort(404);
        }

        return Storage::disk('public')->download($attachment->path, $attachment->original_name);
    }

    public function viewAttachment($id, MaidAttachment $attachment)
    {
        if (!@AdminGuardHasPerm(\App\PermKeys::MAIDS_VIEW) && !@AdminGuardHasPerm(\App\PermKeys::MAIDS_SHOW) && !@AdminGuardHasPerm(\App\PermKeys::MAIDS_RENTAL_VIEW)) {
            abort(403);
        }

        objModel::withTrashed()->findOrFail($id);

        if ((int) $attachment->maid_id !== (int) $id || !$attachment->existsOnDisk()) {
            abort(404);
        }

        $filename = str_replace(['"', "\r", "\n"], '', $attachment->original_name ?: 'attachment');

        return response()->file(Storage::disk('public')->path($attachment->path), [
            'Content-Type' => $attachment->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    public function deleteAttachment($id, MaidAttachment $attachment)
    {
        if (!@AdminGuardHasPerm(\App\PermKeys::MAIDS_EDIT)) {
            abort(403);
        }

        objModel::withTrashed()->findOrFail($id);

        if ((int) $attachment->maid_id !== (int) $id) {
            abort(404);
        }

        if ($attachment->path) {
            Storage::disk('public')->delete($attachment->path);
        }

        $attachment->delete();

        return redirect()
            ->route('showMaidDetails', $id)
            ->with('success', __('ingaz.maid_attachment_deleted_successfully'));
    }

    private function buildAttachmentDownloadName(MaidAttachment $attachment): string
    {
        $extension = pathinfo($attachment->original_name ?: $attachment->path, PATHINFO_EXTENSION);
        $baseName = pathinfo($attachment->original_name ?: 'attachment', PATHINFO_FILENAME);
        $safeName = Str::slug($baseName, '-');

        if ($safeName === '') {
            $safeName = 'attachment-' . $attachment->id;
        }

        return $attachment->id . '-' . $safeName . ($extension ? '.' . $extension : '');
    }

    public function create(NationalityAction $act, RecruitmentOfficeAction $officeAct, ReligionAction $relAct, JobTitleAction $jobTitleAction, JobTypeAction $jobTypesAction, SkillAction $skillAction, Request $request)
    {
        if ($request->ajax()) {
            return $this->createAjaxResponse(
                $act,
                $officeAct,
                $relAct,
                $jobTitleAction,
                $jobTypesAction,
                'create',
                [
                    'defaultShelterContract' => '0',
                    'defaultIsShow' => true,
                    'isRentalForm' => false,
                    'formRedirectRoute' => route('maids.index'),
                    'requireIdFields' => true,
                ]
            );
        }

        // Handle direct request
        $nationalities = $act->getNationalities();
        $religions = $relAct->getReligions();
        $jobs = $jobTitleAction->getJobsTitles();
        [$types, $defaultTypeId] = $this->prepareJobTypesForForm($jobTypesAction->getJobsTypes());
        $offices = $officeAct->getAllOffices();
        $skills = $skillAction->get();
        $rentalFinanceItems = $this->buildCreateRentalFinanceItems();

        $defaultShelterContract = '0';
        $defaultIsShow = true;
        $isRentalForm = false;
        $formRedirectRoute = route('maids.index');
        $requireIdFields = true;

        return view(
            "{$this->folderPath}.create",
            compact(
                'nationalities',
                'offices',
                'religions',
                'jobs',
                'types',
                'skills',
                'defaultTypeId',
                'defaultShelterContract',
                'defaultIsShow',
                'isRentalForm',
                'formRedirectRoute',
                'requireIdFields',
                'rentalFinanceItems'
            )
        );
    }

    public function createRental(NationalityAction $act, RecruitmentOfficeAction $officeAct, ReligionAction $relAct, JobTitleAction $jobTitleAction, JobTypeAction $jobTypesAction, SkillAction $skillAction, Request $request)
    {
        $rentalTypeId = $this->getRentalJobTypeId();

        if ($request->ajax()) {
            return $this->createAjaxResponse(
                $act,
                $officeAct,
                $relAct,
                $jobTitleAction,
                $jobTypesAction,
                'create',
                [
                    'defaultTypeId' => $rentalTypeId,
                    'defaultShelterContract' => '0',
                    'defaultIsShow' => true,
                    'isRentalForm' => true,
                    'formRedirectRoute' => route('maids.rental.index'),
                    'requireIdFields' => true,
                ]
            );
        }

        $nationalities = $act->getNationalities();
        $religions = $relAct->getReligions();
        $jobs = $jobTitleAction->getJobsTitles();
        [$types, $defaultTypeId] = $this->prepareJobTypesForForm($jobTypesAction->getJobsTypes());
        $offices = $officeAct->getAllOffices();
        $skills = $skillAction->get();
        $rentalFinanceItems = $this->buildCreateRentalFinanceItems();

        $defaultTypeId = $rentalTypeId ?? $defaultTypeId;
        $defaultShelterContract = '0';
        $defaultIsShow = true;
        $isRentalForm = true;
        $formRedirectRoute = route('maids.rental.index');
        $requireIdFields = true;

        return view(
            "{$this->folderPath}.create",
            compact(
                'nationalities',
                'offices',
                'religions',
                'jobs',
                'types',
                'skills',
                'defaultTypeId',
                'defaultShelterContract',
                'defaultIsShow',
                'isRentalForm',
                'formRedirectRoute',
                'requireIdFields',
                'rentalFinanceItems'
            )
        );
    }

    public function create_rapid_cv(NationalityAction $act, RecruitmentOfficeAction $officeAct, JobTitleAction $jobTitleAction, JobTypeAction $jobTypesAction, Request $request)
    {
        // Handle direct request
        $nationalities = $act->getNationalities();
        $offices = $officeAct->getAllOffices();
        $jobs = $jobTitleAction->getJobsTitles();
        [, $defaultTypeId] = $this->prepareJobTypesForForm($jobTypesAction->getJobsTypes());

        return view(
            "{$this->folderPath}.rapid_cv_add",
            compact(
                'nationalities',
                'offices',
                'jobs',
                'defaultTypeId',
            )
        );
    }

    public function store(MaidRequest $request, objAction $action)
    {
        $data = $request->all();

        // Set default status in Arabic
        $data['status'] = 'available';
        $data['added_by'] = auth('admin')->id() ?? auth()->id();

        // $data['added_by'] = null; // Temporarily set to null
        // $data = $action->storeMaid($data, $request);
        $data = $action->storeMaid($data, $request);
        if ($request->ajax()) {
            return jsonSuccess($data);
        }

        return response()->json(['code' => 200, 'message' => __('ingaz.maid_added_successfully')]);
    }

    public function extractCvData(Request $request, MaidCvExtractionService $extractor)
    {
        $request->validate([
            'cv_import_file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp,bmp,tif,tiff', 'max:10240'],
        ], [
            'cv_import_file.required' => __('ingaz.cv_file_required_validation'),
            'cv_import_file.mimes' => __('ingaz.cv_file_mimes_validation'),
            'cv_import_file.max' => __('ingaz.cv_file_max_validation'),
        ]);

        try {
            $result = $extractor->extract($request->file('cv_import_file'));
        } catch (\Throwable $exception) {
            return response()->json([
                'code' => 422,
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'code' => 200,
            'message' => __('ingaz.cv_data_extracted_review'),
            'data' => $result,
        ]);
    }

    public function store_rapid_cv(StoreRapidCv $request, objAction $action)
    {
        $data = $request->validated();

        // Set defaults so the rapid form saves without missing fields
        $data['status'] = 'available';
        $data['added_by'] = auth('admin')->id() ?? auth()->id();
        $data['available'] = $data['available'] ?? 1;
        // Rapid CVs should not be visible on the public site by default
        $data['is_show'] = $data['is_show'] ?? 0;
        $data['experience_status'] = $data['experience_status'] ?? 'no_experience';
        $data['experience_years'] = $data['experience_years'] ?? '0';

        $name = trim((string) ($data['name:en'] ?? ''));
        $data['name'] = $name !== '' ? $name : null;
        unset($data['name:en']);

        $data = $action->storeRapidCvMaid($data, $request);

        return response()->json(['code' => 200, 'message' => __('ingaz.cv_added_successfully')]);
    }

    // Helper method to get status in current language
    private function getStatusInCurrentLanguage($status)
    {
        $currentLocale = App::getLocale();
        foreach (Maid::STATUS_AVAILABLE as $key => $value) {
            if ($value === $status) {
                return Maid::STATUS_AVAILABLE[$currentLocale];
            }
        }
        foreach (Maid::STATUS_RESERVED as $key => $value) {
            if ($value === $status) {
                return Maid::STATUS_RESERVED[$currentLocale];
            }
        }
        foreach (Maid::STATUS_CONTRACTED as $key => $value) {
            if ($value === $status) {
                return Maid::STATUS_CONTRACTED[$currentLocale];
            }
        }
        foreach (Maid::STATUS_IN_ACCOMMODATION as $key => $value) {
            if ($value === $status) {
                return Maid::STATUS_IN_ACCOMMODATION[$currentLocale];
            }
        }
        return $status;
    }

    // Update status method
    public function updateStatus(Request $request, $id)
    {
        $maid = Maid::findOrFail($id);
        $newStatus = $request->input('status');

        // Validate status
        $validStatuses = [
            'available',
            'reserved',
            'contracted',
            'in_accommodation'
        ];

        if (!in_array($newStatus, $validStatuses)) {
            return response()->json([
                'code' => 400,
                'message' => __('ingaz.invalid_status')
            ], 400);
        }

        $maid->status = $newStatus;
        $maid->save();

        return response()->json([
            'code' => 200,
            'message' => __('ingaz.status_updated_successfully'),
            'status' => $this->getStatusInCurrentLanguage($newStatus)
        ]);
    }

    public function convert_page($id)
    {
        $data = objModel::where('id', $id)->first();
        return view($this->folderPath . '.convert_page', ['data' => $data]);
    }

    public function show($id)
    {
        // Check if user has permission to toggle status
        $hasPermission = @AdminGuardHasPerm(\App\PermKeys::MAIDS_TOGGLE_STATUS);

        if (!$hasPermission) {
            return response()->json([
                'success' => false,
                'message' => __('ingaz.permission_denied_change_visibility')
            ], 403);
        }

        try {
            $model = objModel::findOrFail($id);
            $oldStatus = $model->is_show;
            $model->is_show = !$model->is_show;
            $model->save(); // Save the changes

            $status = $model->is_show ? helperTrans('ingaz.maid_visibility_visible') : helperTrans('ingaz.maid_visibility_hidden');
            $oldStatusText = $oldStatus ? helperTrans('ingaz.maid_visibility_visible') : helperTrans('ingaz.maid_visibility_hidden');

            // Log the action
            \Log::info("Maid visibility toggled", [
                'maid_id' => $id,
                'old_status' => $oldStatusText,
                'new_status' => $status,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => __('ingaz.visibility_changed_successfully', ['old' => $oldStatusText, 'new' => $status]),
                'new_status' => $model->is_show,
                'old_status' => $oldStatus
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => __('ingaz.cv_not_found')
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Error toggling maid visibility', [
                'maid_id' => $id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => __('ingaz.status_update_error', ['message' => $e->getMessage()])
            ], 500);
        }
    }

    public function edit(Request $request, $id, NationalityAction $act, RecruitmentOfficeAction $officeAct, ReligionAction $relAct, JobTitleAction $jobTitleAction, JobTypeAction $jobTypesAction, SkillAction $skillAction)
    {
        $obj = objModel::with('jobTypes')->findOrFail($id);
        $rentalTypeId = $this->getRentalJobTypeId();
        $isRentalMaid = $rentalTypeId ? $obj->jobTypes->contains('id', $rentalTypeId) : false;
        $defaultShelterContract = $obj->maid_accommodation_type == 2 ? '1' : '0';
        $isRentalForm = false;
        $formRedirectRoute = $isRentalMaid ? route('maids.rental.index') : route('maids.index');

        if ($request->ajax()) {
            return $this->createAjaxResponse($act, $officeAct, $relAct, $jobTitleAction, $jobTypesAction, 'edit', [
                'obj' => $obj,
                'defaultShelterContract' => $defaultShelterContract,
                'isRentalForm' => $isRentalForm,
                'formRedirectRoute' => $formRedirectRoute,
            ]);
        }

        // Handle direct request for edit page
        $nationalities = $act->getNationalities();
        $religions = $relAct->getReligions();
        $jobs = $jobTitleAction->getJobsTitles();
        $types = $jobTypesAction->getJobsTypes();
        $offices = $officeAct->getAllOffices();
        $skills = $skillAction->get();

        return view("{$this->folderPath}.edit", compact(
            'obj',
            'nationalities',
            'offices',
            'religions',
            'jobs',
            'types',
            'skills',
            'defaultShelterContract',
            'isRentalForm',
            'formRedirectRoute'
        ));
    }

    public function update(MaidRequest $request, objAction $action, $id)
    {
        $data = $request->only($this->postData);
        $data = $action->updateMaid($id, $data, $request);


        // Handle direct form submission
        //        return jsonSuccess($data, null, 201);
        if ($request->ajax()) {
            return jsonSuccess($data);
        }
        // return redirect()->route('maids.index')->with('success', 'تم تحديث العاملة بنجاح');
    }

    public function update_cv(Request $request, objAction $action, $id)
    {
        $data = $request->only($this->postData);
        $data = $action->updateMaid($id, $data, $request);
        return jsonSuccess($data);
    }

    public function updateOffer($id, Request $request)
    {
        if (!@AdminGuardHasPerm(\App\PermKeys::MAIDS_EDIT)) {
            return response()->json([
                'success' => false,
                'message' => helperTrans('ingaz.permission_denied')
            ], 403);
        }

        $maid = objModel::findOrFail($id);

        if (!$maid->available || $maid->status !== 'available') {
            return response()->json([
                'success' => false,
                'message' => helperTrans('ingaz.offer_only_available')
            ], 422);
        }

        if ($request->boolean('clear_offer')) {
            $maid->offer_price = null;
            $maid->offer_duration = null;
            $maid->offer_description = null;
            $maid->offer_starts_at = null;
            $maid->offer_expires_at = null;
            $maid->save();

            return response()->json([
                'success' => true,
                'message' => helperTrans('ingaz.offer_cleared_successfully'),
            ]);
        }

        $validated = $request->validate([
            'offer_price' => ['nullable', 'numeric', 'min:0'],
            'offer_description' => ['nullable', 'string', 'max:500'],
            'offer_starts_at' => ['nullable', 'date'],
            'offer_expires_at' => ['nullable', 'date', 'after_or_equal:offer_starts_at'],
        ], [], [
            'offer_price' => helperTrans('ingaz.offer_price_new'),
            'offer_description' => helperTrans('ingaz.offer_description'),
            'offer_starts_at' => helperTrans('ingaz.offer_starts_at'),
            'offer_expires_at' => helperTrans('ingaz.offer_expires_at'),
        ]);

        if (!array_key_exists('offer_price', $validated) || $validated['offer_price'] === null) {
            return response()->json([
                'success' => false,
                'message' => helperTrans('ingaz.offer_price_required')
            ], 422);
        }

        $maid->offer_price = round((float) $validated['offer_price'], 2);
        $maid->offer_description = isset($validated['offer_description']) && trim($validated['offer_description']) !== ''
            ? trim($validated['offer_description'])
            : null;
        $maid->offer_starts_at = $validated['offer_starts_at'] ?? null;
        $maid->offer_expires_at = $validated['offer_expires_at'] ?? null;

        if ($maid->offer_starts_at && $maid->offer_expires_at && $maid->offer_expires_at->gte($maid->offer_starts_at)) {
            $maid->offer_duration = (string) ($maid->offer_starts_at->diffInDays($maid->offer_expires_at) + 1);
        } else {
            $maid->offer_duration = null;
        }

        $maid->save();

        $today = Carbon::today();
        $isActive = $maid->offer_price !== null
            && (!$maid->offer_starts_at || $maid->offer_starts_at->lte($today))
            && (!$maid->offer_expires_at || $maid->offer_expires_at->gte($today));

        return response()->json([
            'success' => true,
            'message' => helperTrans('ingaz.offer_saved_successfully'),
            'offer' => [
                'price' => $maid->offer_price,
                'duration' => $maid->offer_duration,
                'description' => $maid->offer_description,
                'starts_at' => optional($maid->offer_starts_at)->format('Y-m-d'),
                'expires_at' => optional($maid->offer_expires_at)->format('Y-m-d'),
                'is_active' => $isActive,
            ],
        ]);
    }

    public function togglePin($id)
    {
        \Log::info('Toggle pin method called for maid ID: ' . $id);

        // Check if user has permission to toggle pin
        $hasPermission = @AdminGuardHasPerm(\App\PermKeys::MAIDS_TOGGLE_PIN);
        \Log::info('User permission check for MAIDS_TOGGLE_PIN: ' . ($hasPermission ? 'true' : 'false'));

        if (!$hasPermission) {
            \Log::warning('User does not have permission to toggle pin');
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لتثبيت/إلغاء تثبيت العاملة'
            ], 403);
        }

        try {
            $maid = objModel::findOrFail($id);
            \Log::info('Maid found: ' . $maid->name . ', current pin status: ' . ($maid->is_pinned ? 'true' : 'false'));

            if (!$maid->available || !in_array($maid->status, ['available', 'completed'], true)) {
                \Log::warning('Attempt to pin a maid that is not currently available', ['maid_id' => $maid->id, 'status' => $maid->status, 'available' => $maid->available]);
                return response()->json([
                    'success' => false,
                    'message' => helperTrans('ingaz.offer_only_available')
                ], 422);
            }

            $maid->is_pinned = !$maid->is_pinned;
            $maid->save();

            \Log::info('Maid pin status updated to: ' . ($maid->is_pinned ? 'true' : 'false'));

            $message = $maid->is_pinned ? 'تم تثبيت العاملة بنجاح' : 'تم إلغاء التثبيت بنجاح';

            return response()->json([
                'success' => true,
                'data' => $maid,
                'message' => $message,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in togglePin method: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث حالة التثبيت: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, objAction $action)
    {
        // $action->delete($id);
        $maid = objModel::with(['contracts', 'orders'])->findOrFail($id);

        if (!$this->canDeleteMaidCv($maid)) {
            return jsonError('لا يمكن حذف السيرة لأنها محجوزة أو تم التعاقد عليها. يمكن استبدال العاملة من الطلب أو العقد وتحديد سبب الباك أوت.', 422);
        }

        $maid->deleted_by = auth()->id();
        $maid->save();
        $maid->delete();
        return jsonSuccess(['success' => true], "تم حذف العاملة بنجاح", 200);
    }

    public function restore($id)
    {
        $maid = objModel::onlyTrashed()->findOrFail($id);
        $maid->restore();

        return response()->json([
            'success' => true,
            'message' => helperTrans('ingaz.updated_successfully'),
        ]);
    }

    public function exportPdf(Request $request)
    {
        $this->configureMaidExportRuntime();
        $maids = $this->buildMaidExportQuery($request)->lazy(200);

        // Create HTML content with RTL support and better styling
        $html = '<!DOCTYPE html>
        <html dir="rtl">
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
            <title>قائمة السيرة الذاتية</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    direction: rtl;
                    margin: 20px;
                }
                .header {
                    text-align: center;
                    margin-bottom: 30px;
                    padding: 10px;
                    background-color: #f8f9fa;
                    border-radius: 5px;
                }
                .header h1 {
                    color: #333;
                    margin-bottom: 10px;
                }
                .print-button {
                    display: inline-block;
                    padding: 10px 20px;
                    background-color: #4CAF50;
                    color: white;
                    text-decoration: none;
                    border-radius: 5px;
                    margin-bottom: 20px;
                    cursor: pointer;
                    border: none;
                    font-size: 16px;
                }
                .print-button:hover {
                    background-color: #45a049;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 20px;
                    font-size: 12px;
                }
                th, td {
                    border: 1px solid #ddd;
                    padding: 8px;
                    text-align: right;
                }
                th {
                    background-color: #f2f2f2;
                    color: #333;
                    font-weight: bold;
                }
                tr:nth-child(even) {
                    background-color: #f9f9f9;
                }
                .status-active {
                    color: green;
                }
                .status-blocked {
                    color: red;
                }
                @media print {
                    .print-button {
                        display: none;
                    }
                    body {
                        margin: 0;
                        padding: 20px;
                    }
                    table {
                        page-break-inside: auto;
                    }
                    tr {
                        page-break-inside: avoid;
                        page-break-after: auto;
                    }
                }
            </style>
            <script>
                function printDocument() {
                    window.print();
                }
                window.onload = function() {
                    window.print();
                }
            </script>
        </head>
        <body>
            <div class="header">
                <h1>قائمة السيرة الذاتية</h1>
                <p>تم التصدير في: ' . date('Y-m-d H:i:s') . '</p>
                <button class="print-button" onclick="printDocument()">طباعة</button>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>رقم السيرة الذاتية</th>
                        <th>الاسم</th>
                        <th>رقم جواز السفر</th>
                        <th>الجنسية</th>
                        <th>الديانة</th>
                        <th>العمر</th>
                        <th>سنوات الخبرة</th>
                        <th>الراتب</th>
                        <th>التكلفة</th>
                        <th>أنواع الوظائف</th>
                        <th>الوظائف</th>
                        <th>الحالة</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($maids as $maid) {
            $jobTypes = $maid->jobTypes->pluck('title')->implode(', ');
            $jobTitles = $maid->jobTitles->pluck('title')->implode(', ');
            $statusText = $maid->available ? 'متاح' : 'غير متاح';

            $html .= '<tr>
                <td>' . $maid->id . '</td>
                <td>' . $maid->name . '</td>
                <td>' . $maid->passport_num . '</td>
                <td>' . ($maid->nationality->title ?? '') . '</td>
                <td>' . ($maid->religion->title ?? '') . '</td>
                <td>' . $maid->age . '</td>
                <td>' . $maid->experience_years . '</td>
                <td>' . ($maid->resolved_salary ?? '0') . '</td>
                <td>' . ($maid->resolved_price ?? '0') . '</td>
                <td>' . $jobTypes . '</td>
                <td>' . $jobTitles . '</td>
                <td>' . $statusText . '</td>
            </tr>';
        }

        $html .= '</tbody></table></body></html>';

        // Return HTML with print styles
        $headers = [
            'Content-Type' => 'text/html',
            'Content-Disposition' => 'inline; filename="maids.html"',
        ];

        return response($html, 200, $headers);
    }

    public function exportExcel(Request $request)
    {
        $this->configureMaidExportRuntime();
        $maids = $this->buildMaidExportQuery($request)->lazy(200);

        // Create new Spreadsheet object
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set RTL direction
        $sheet->setRightToLeft(true);

        // Set headers
        $headers = [
            'رقم السيرة الذاتية',
            'الاسم',
            'رقم جواز السفر',
            'الجنسية',
            'الديانة',
            'العمر',
            'سنوات الخبرة',
            'الراتب',
            'التكلفة',
            'أنواع الوظائف',
            'الوظائف',
            'الحالة'
        ];

        // Set headers in first row
        $sheet->fromArray($headers, NULL, 'A1');

        // Style headers
        $headerStyle = [
            'font' => [
                'bold' => true,
                'size' => 12,
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'F2F2F2',
                ],
            ],
        ];
        $sheet->getStyle('A1:L1')->applyFromArray($headerStyle);

        // Set data
        $row = 2;
        foreach ($maids as $maid) {
            $jobTypes = $maid->jobTypes->pluck('title')->implode(', ');
            $jobTitles = $maid->jobTitles->pluck('title')->implode(', ');
            $statusText = $maid->available ? 'متاح' : 'غير متاح';

            $sheet->setCellValue('A' . $row, $maid->id);
            $sheet->setCellValue('B' . $row, $maid->name);
            $sheet->setCellValue('C' . $row, $maid->passport_num);
            $sheet->setCellValue('D' . $row, $maid->nationality->title ?? '');
            $sheet->setCellValue('E' . $row, $maid->religion->title ?? '');
            $sheet->setCellValue('F' . $row, $maid->age);
            $sheet->setCellValue('G' . $row, $maid->experience_years);
            $sheet->setCellValue('H' . $row, $maid->resolved_salary ?? 0);
            $sheet->setCellValue('I' . $row, $maid->resolved_price ?? 0);
            $sheet->setCellValue('J' . $row, $jobTypes);
            $sheet->setCellValue('K' . $row, $jobTitles);
            $sheet->setCellValue('L' . $row, $statusText);
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'L') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Create Excel writer
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        // Set headers for download
        $headers = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="maids.xlsx"',
        ];

        // Save to temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx');
        $writer->save($tempFile);

        // Return file
        return response()->download($tempFile, 'maids.xlsx', $headers)->deleteFileAfterSend(true);
    }

    private function configureMaidExportRuntime(): void
    {
        @ini_set('memory_limit', '512M');
        @set_time_limit(600);
    }

    private function buildMaidExportQuery(Request $request)
    {
        $maidsQuery = objModel::query()
            ->with([
                'nationality',
                'religion',
                'jobTypes',
                'jobTitles',
                'contracts' => function ($query) {
                    $query->select(['id', 'maid_id', 'status'])
                        ->where('status', ContractStatusEnum::Shelter->value);
                },
            ])
            ->select([
                'id',
                'name',
                'passport_num',
                'id_number',
                'nationality_id',
                'religion_id',
                'office_id',
                'price',
                'salary',
                'age',
                'available',
                'status',
                'is_pinned',
                'created_at',
                'experience_years',
                'experience_status',
                'maid_accommodation_type',
                'id_expiry_date',
                'request_type',
            ]);

        if ($request->boolean('deleted')) {
            $maidsQuery->onlyTrashed();
        }

        $this->applyMaidFilter($request, $maidsQuery);

        return $maidsQuery
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at');
    }

    private function createAjaxResponse(NationalityAction $act, RecruitmentOfficeAction $officeAction, ReligionAction $relAct, JobTitleAction $jobTitleAction, JobTypeAction $jobTypesAction, $viewName, $additionalData = [])
    {
        $extraData = $additionalData;
        $nationalities = $act->getNationalities();
        $religions = $relAct->getReligions();
        $jobs = $jobTitleAction->getJobsTitles();
        $typesSource = $extraData['types'] ?? $jobTypesAction->getJobsTypes();

        if ($viewName === 'create') {
            [$types, $defaultTypeId] = $this->prepareJobTypesForForm($typesSource);

            if (!isset($extraData['defaultTypeId'])) {
                $extraData['defaultTypeId'] = $defaultTypeId;
            }

            $extraData['rentalFinanceItems'] = $extraData['rentalFinanceItems'] ?? $this->buildCreateRentalFinanceItems();
        } else {
            $types = $typesSource;
        }
        $offices = $officeAction->getAllOffices();
        $skills = app(SkillAction::class)->get();

        unset($extraData['types']);

        $data = array_merge(compact('nationalities', 'offices', 'religions', 'jobs', 'types', 'skills'), $extraData);
        $returnHTML = view("{$this->folderPath}.{$viewName}", $data)->render();
        return jsonSuccess(['html' => $returnHTML]);
    }

    private function buildCreateRentalFinanceItems(): array
    {
        return app(RentalMaidFinanceService::class)->getFormItems((array) old('rental_finance_items', []));
    }

    private function normalizeArabicText(?string $text): string
    {
        if ($text === null) {
            return '';
        }

        $normalized = mb_strtolower($text, 'UTF-8');
        $search = ['أ', 'إ', 'آ', 'ى', 'ؤ', 'ئ', 'ة'];
        $replace = ['ا', 'ا', 'ا', 'ي', 'و', 'ي', 'ه'];

        return trim(str_replace($search, $replace, $normalized));
    }

    private function recruitmentContractStatuses(): array
    {
        return [
            ContractStatusEnum::PendingMaidSelection->value,
            ContractStatusEnum::New->value,
            ContractStatusEnum::Msaned->value,
            ContractStatusEnum::Tafwed->value,
            ContractStatusEnum::Taffez->value,
            ContractStatusEnum::Tazker->value,
            ContractStatusEnum::Arrival->value,
        ];
    }

    private function activeOrderStatuses(): array
    {
        return ['new', 'under_process', 'contracted', 'contracted_without_contract'];
    }

    private function rentalActiveContractStatuses(): array
    {
        return ['new', 'waiting_contract_send', 'contract_sent', 'contract_signed', 'maid_delivered', 'active', 'transferred', 'suspended', 'frozen'];
    }

    private function canDeleteMaidCv(Maid $maid): bool
    {
        if (in_array($maid->status, ['reserved', 'contracted'], true)) {
            return false;
        }

        $hasActiveRecruitmentContracts = $maid->relationLoaded('contracts')
            ? $maid->contracts->whereIn('status', $this->recruitmentContractStatuses())->isNotEmpty()
            : $maid->contracts()->whereIn('status', $this->recruitmentContractStatuses())->exists();

        if ($hasActiveRecruitmentContracts) {
            return false;
        }

        $hasActiveOrders = $maid->relationLoaded('orders')
            ? $maid->orders->whereIn('status', $this->activeOrderStatuses())->isNotEmpty()
            : $maid->orders()->whereIn('status', $this->activeOrderStatuses())->exists();

        return !$hasActiveOrders;
    }

    private function applyRecruitmentMaidsScope($query): void
    {
        $rentalJobTypeId = $this->getRentalJobTypeId();

        if (!$rentalJobTypeId) {
            return;
        }

        $query->whereDoesntHave('jobTypes', function ($jobTypeQuery) use ($rentalJobTypeId) {
            $jobTypeQuery->whereKey($rentalJobTypeId);
        });
    }

    private function getRecruitmentIndexJobTypes()
    {
        $query = JobType::with('translations')->orderByTranslation('title', 'asc');
        $rentalJobTypeId = $this->getRentalJobTypeId();

        if ($rentalJobTypeId) {
            $query->where('job_types.id', '!=', $rentalJobTypeId);
        }

        return $query->get(['id']);
    }

    private function prepareJobTypesForForm($types): array
    {
        $collection = collect($types);

        $filtered = $collection->filter(function ($type) {
            $title = $this->normalizeArabicText($type->title ?? '');
            return !Str::contains($title, ['استلام', 'فوري', 'instant', 'immediate']);
        })->values();

        $defaultType = $filtered->first(function ($type) {
            $title = $this->normalizeArabicText($type->title ?? '');
            return Str::contains($title, ['استقدام', 'recruit']);
        });

        $defaultTypeId = $defaultType->id ?? ($filtered->first()->id ?? null);

        return [$filtered, $defaultTypeId];
    }

    private function getRentalJobTypeId(): ?int
    {
        return $this->findJobTypeIdByKeywords(['ايجار', 'إيجار', 'تأجير', 'rent']) ?? 2;
    }

    private function isRentalMaid(Maid $maid): bool
    {
        $rentalTypeId = $this->getRentalJobTypeId();

        if (!$rentalTypeId) {
            return false;
        }

        $maid->loadMissing('jobTypes');

        return $maid->jobTypes
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->contains($rentalTypeId);
    }

    private function findJobTypeIdByKeywords(array $keywords): ?int
    {
        $jobType = JobType::with('translations')->get()->first(function ($type) use ($keywords) {
            $title = $this->normalizeArabicText($type->title ?? '');

            foreach ($keywords as $keyword) {
                if (Str::contains($title, $this->normalizeArabicText($keyword))) {
                    return true;
                }
            }

            return false;
        });

        return $jobType?->id;
    }

    private function calculateRentalStats(int $jobTypeId): array
    {
        $baseQuery = objModel::query()->whereHas('jobTypes', function ($query) use ($jobTypeId) {
            $query->where('job_type_id', $jobTypeId);
        });

        $today = Carbon::today();
        $expiryThreshold = Carbon::today()->addDays(30);
        $shelterContracts = Contract::insideShelter()->whereHas('maid', function ($maidQuery) use ($jobTypeId) {
            $maidQuery->whereHas('jobTypes', function ($jobTypeQuery) use ($jobTypeId) {
                $jobTypeQuery->where('job_type_id', $jobTypeId);
            });
        });

        return [
            'total' => (clone $baseQuery)->count(),
            'available' => $this->countRentalMaidsByStatus($baseQuery, 'available'),
            'reserved' => $this->countRentalMaidsByStatus($baseQuery, 'reserved'),
            'visible' => (clone $baseQuery)->where('is_show', true)->count(),
            'hidden' => (clone $baseQuery)->where('is_show', false)->count(),
            'in_accommodation' => (clone $shelterContracts)->select('maid_id')->distinct()->count('maid_id'),
        ];
    }

    private function countRentalMaidsByStatus($baseQuery, string $status): int
    {
        $query = clone $baseQuery;

        if ($status === 'available') {
            $this->applyRentalAvailableFilter($query);
        } elseif ($status === 'reserved') {
            $this->applyRentalReservedFilter($query);
        } elseif ($status === 'backout') {
            $this->applyRentalBackoutFilter($query);
        } elseif ($status === 'completed_rental_contracts') {
            $this->applyRentalCompletedContractsFilter($query);
        } else {
            $query->where('status', $status);
        }

        return $query->count();
    }

    private function applyRentalAvailableFilter($query): void
    {
        $query->where('available', true)
            ->whereIn('status', ['available', 'completed'])
            ->whereDoesntHave('contracts', function ($contractQuery) {
                $contractQuery->whereIn('status', $this->recruitmentContractStatuses());
            })
            ->whereDoesntHave('orders', function ($orderQuery) {
                $orderQuery->whereIn('status', $this->activeOrderStatuses());
            })
            ->whereDoesntHave('rentalContracts', function ($rentalQuery) {
                $rentalQuery->whereIn('status', $this->rentalActiveContractStatuses());
            });
    }

    private function applyRentalReservedFilter($query): void
    {
        $query->whereNotIn('status', ['backout', 'deported'])
            ->where(function ($reservedQuery) {
                $reservedQuery->where('status', 'reserved')
                    ->orWhere('available', false)
                    ->orWhereHas('contracts', function ($contractQuery) {
                        $contractQuery->whereIn('status', $this->recruitmentContractStatuses());
                    })
                    ->orWhereHas('orders', function ($orderQuery) {
                        $orderQuery->whereIn('status', $this->activeOrderStatuses());
                    })
                    ->orWhereHas('rentalContracts', function ($rentalQuery) {
                        $rentalQuery->whereIn('status', $this->rentalActiveContractStatuses());
                    });
            });
    }

    private function applyRentalBackoutFilter($query): void
    {
        $query->where('status', 'backout')
            ->where('available', false);
    }

    private function applyRentalActiveContractsFilter($query): void
    {
        $query->whereHas('rentalContracts', function ($contractQuery) {
            $contractQuery->whereIn('status', $this->rentalActiveContractStatuses());
        });
    }

    private function applyRentalCompletedContractsFilter($query): void
    {
        $query->whereHas('rentalContracts', function ($contractQuery) {
            $contractQuery->where('status', 'completed');
        })->whereDoesntHave('rentalContracts', function ($contractQuery) {
            $contractQuery->whereIn('status', $this->rentalActiveContractStatuses());
        });
    }

    private function canCreateRentalContractForMaid(Maid $maid): bool
    {
        $hasActiveRecruitmentContracts = $maid->contracts->whereIn('status', $this->recruitmentContractStatuses())->isNotEmpty();
        $hasActiveOrders = $maid->orders->whereIn('status', $this->activeOrderStatuses())->isNotEmpty();
        $hasActiveRentalContracts = $maid->rentalContracts->whereIn('status', $this->rentalActiveContractStatuses())->isNotEmpty();

        return (bool) $maid->available
            && in_array($maid->status, ['available', 'completed'], true)
            && !$hasActiveRecruitmentContracts
            && !$hasActiveOrders
            && !$hasActiveRentalContracts;
    }

    private function isRentalFilterSelected($request): bool
    {
        $rentalJobTypeId = $this->getRentalJobTypeId();
        $selectedJobTypes = $request->job_type_id ?? $request->job_type ?? null;
        $selectedJobTypes = is_array($selectedJobTypes) ? $selectedJobTypes : [$selectedJobTypes];
        $selectedJobTypes = array_map('strval', array_filter($selectedJobTypes, static fn($value) => $value !== null && $value !== ''));

        return (bool) ($rentalJobTypeId && in_array((string) $rentalJobTypeId, $selectedJobTypes, true));
    }

    private function buildRecruitmentMaidStats($baseQuery): array
    {
        return [
            'total' => (clone $baseQuery)->count(),
            'available' => $this->countRecruitmentMaidsByEffectiveStatus($baseQuery, 'available'),
            'reserved' => $this->countRecruitmentMaidsByEffectiveStatus($baseQuery, 'reserved'),
            'in_accommodation' => $this->countRecruitmentMaidsByEffectiveStatus($baseQuery, 'in_accommodation'),
            'backout' => $this->countRecruitmentMaidsByEffectiveStatus($baseQuery, 'backout'),
            'suspended' => $this->countRecruitmentMaidsByEffectiveStatus($baseQuery, 'suspended'),
            'deported' => $this->countRecruitmentMaidsByEffectiveStatus($baseQuery, 'deported'),
            'pinned' => (clone $baseQuery)->where('is_pinned', 1)->count(),
        ];
    }

    private function countRecruitmentMaidsByEffectiveStatus($baseQuery, string $status): int
    {
        $query = clone $baseQuery;
        $this->applyEffectiveStatusFilter($query, [$status]);

        return $query->count();
    }

    private function applyEffectiveStatusFilter($rows, array $statuses): void
    {
        $statuses = array_values(array_unique(array_filter(
            array_map(static fn($status) => is_string($status) ? trim($status) : $status, $statuses),
            static fn($status) => !is_null($status) && $status !== ''
        )));

        if (empty($statuses)) {
            return;
        }

        $rows->where(function ($outerQuery) use ($statuses) {
            foreach ($statuses as $index => $status) {
                $method = $index === 0 ? 'where' : 'orWhere';
                $outerQuery->{$method}(function ($statusQuery) use ($status) {
                    $this->applySingleEffectiveStatusCondition($statusQuery, $status);
                });
            }
        });
    }

    private function applySingleEffectiveStatusCondition($query, string $status): void
    {
        $activeOrderStatuses = $this->activeOrderStatuses();
        $recruitmentContractStatuses = $this->recruitmentContractStatuses();

        switch ($status) {
            case 'available':
                $query->where('status', 'available')
                    ->where('available', true)
                    ->where('maid_accommodation_type', '!=', 2)
                    ->whereDoesntHave('contracts', function ($contractQuery) use ($recruitmentContractStatuses) {
                        $contractQuery->whereIn('status', $recruitmentContractStatuses);
                    })
                    ->whereDoesntHave('orders', function ($orderQuery) use ($activeOrderStatuses) {
                        $orderQuery->whereIn('status', $activeOrderStatuses);
                    });
                break;

            case 'reserved':
                $query->where('maid_accommodation_type', '!=', 2)
                    ->whereDoesntHave('contracts', function ($contractQuery) use ($recruitmentContractStatuses) {
                        $contractQuery->whereIn('status', $recruitmentContractStatuses);
                    })
                    ->where(function ($reservedQuery) use ($activeOrderStatuses) {
                        $reservedQuery->where('status', 'reserved')
                            ->orWhere(function ($availableReservedQuery) use ($activeOrderStatuses) {
                                $availableReservedQuery->where('status', 'available')
                                    ->where(function ($availabilityQuery) use ($activeOrderStatuses) {
                                        $availabilityQuery->where('available', false)
                                            ->orWhereHas('orders', function ($orderQuery) use ($activeOrderStatuses) {
                                                $orderQuery->whereIn('status', $activeOrderStatuses);
                                            });
                                    });
                            });
                    });
                break;

            case 'contracted':
                $query->where('maid_accommodation_type', '!=', 2)
                    ->whereNotIn('status', ['backout', 'suspended', 'deported'])
                    ->whereHas('contracts', function ($contractQuery) use ($recruitmentContractStatuses) {
                        $contractQuery->whereIn('status', $recruitmentContractStatuses);
                    });
                break;

            case 'in_accommodation':
                $query->where('maid_accommodation_type', 2)
                    ->where('status', '!=', 'deported');
                break;

            case 'backout':
                $query->where('status', 'backout')
                    ->where('maid_accommodation_type', '!=', 2);
                break;

            case 'suspended':
                $query->where('status', 'suspended')
                    ->where('maid_accommodation_type', '!=', 2);
                break;

            case 'deported':
                $query->where('status', 'deported');
                break;

            default:
                $query->where('status', $status);
                break;
        }
    }

    public function applyMaidFilter($request, $rows)
    {
        if ($request->filled('keyword')) {
            $keyword = trim($request->keyword);
            $rows->where(function ($q) use ($keyword) {
                $q->where('name', 'like', '%' . $keyword . '%')
                    ->orWhere('passport_num', 'like', '%' . $keyword . '%')
                    ->orWhere('id_number', 'like', '%' . $keyword . '%');
            });
        }

        if ($request->has('passport_num') && $request->passport_num != null) {
            $rows->where('passport_num', 'like', '%' . $request->passport_num . '%');
        }
        if ($request->has('nationality_id') && $request->nationality_id != null) {
            $rows->where('nationality_id', $request->nationality_id);
        }
        if ($request->has('office_id') && $request->office_id != null) {
            $rows->where('office_id', $request->office_id);
        }
        if ($request->has('order_by') && $request->order_by != null) {
            if ($request->order_by == 'low_price') {
                $rows->orderBy('price', 'ASC');
            } elseif ($request->order_by == 'low_salary') {
                $rows->orderBy('salary', 'ASC');
            }
        }

        $jobTypeFilter = $request->job_type_id ?? $request->job_type ?? null;
        if (!empty($jobTypeFilter)) {
            $jobTypeIds = is_array($jobTypeFilter) ? $jobTypeFilter : [$jobTypeFilter];
            $rows->whereHas('jobTypes', function ($subQ) use ($request) {
                $filter = $request->job_type_id ?? $request->job_type;
                $ids = is_array($filter) ? $filter : [$filter];
                $subQ->whereIn('job_type_id', $ids);
            });
        }
        if ($request->has('experience_years') && $request->experience_years != null) {
            $rows->where('experience_years', $request->experience_years);
        }
        if ($request->has('experience_status') && $request->experience_status != null) {
            $rows->where('experience_status', $request->experience_status);
        }
        if ($request->has('datefilter') && $request->datefilter != null) {
            $dates = explode(' - ', $request->datefilter);
            if (count($dates) == 2) {
                $startDate = Carbon::parse($dates[0])->startOfDay();
                $endDate = Carbon::parse($dates[1])->endOfDay();
                $rows->whereBetween('created_at', [$startDate, $endDate]);
            }
        }

        if ($request->filled('date_from')) {
            $rows->whereDate('created_at', '>=', Carbon::parse($request->date_from)->startOfDay());
        }

        if ($request->filled('date_to')) {
            $rows->whereDate('created_at', '<=', Carbon::parse($request->date_to)->endOfDay());
        }

        if ($request->filled('status')) {
            $statuses = collect((array) $request->status)
                ->filter(static fn($status) => !is_null($status) && $status !== '')
                ->map(static fn($status) => (string) $status)
                ->values();

            if ($statuses->isNotEmpty()) {
                $this->applyEffectiveStatusFilter($rows, $statuses->all());
            }
        }

        if ($request->filled('visibility')) {
            if ($request->visibility === 'visible') {
                $rows->where('is_show', true);
            } elseif ($request->visibility === 'hidden') {
                $rows->where('is_show', false);
            }
        }

        if ($request->filled('request_type')) {
            $rows->ofRequestType($request->request_type);
        }

        if ($request->filled('pinned')) {
            if ($request->pinned === 'only') {
                $rows->where('is_pinned', true);
            } elseif ($request->pinned === 'unpinned') {
                $rows->where('is_pinned', false);
            }
        }

        if ($request->filled('availability')) {
            if ($request->availability === 'available') {
                $rows->where('available', true);
            } elseif ($request->availability === 'unavailable') {
                $rows->where('available', false);
            }
        }

        $isRentalFilter = $this->isRentalFilterSelected($request);

        // فلتر حالة السيرة الذاتية - تحديث ليدعم حالة backout
        if ($request->has('cv_status') && $request->cv_status != null) {
            if ($request->cv_status == 'available') {
                if ($isRentalFilter) {
                    $this->applyRentalAvailableFilter($rows);
                } else {
                    $rows->where('available', 1)
                        ->where('status', 'available')
                        ->where('maid_accommodation_type', '!=', 2)
                        ->whereDoesntHave('contracts', function ($q) {
                            $q->whereIn('status', $this->recruitmentContractStatuses());
                        })
                        ->whereDoesntHave('orders', function ($q) {
                            $q->whereIn('status', $this->activeOrderStatuses());
                        });
                }
            } elseif ($request->cv_status == 'reserved') {
                if ($isRentalFilter) {
                    $this->applyRentalReservedFilter($rows);
                } else {
                    $rows->where(function ($q) {
                        $q->where('available', 0)
                            ->where('status', 'reserved')
                            ->orWhereHas('contracts', function ($subQ) {
                                $subQ->whereIn('status', $this->recruitmentContractStatuses());
                            })
                            ->orWhereHas('orders', function ($subQ) {
                                $subQ->whereIn('status', $this->activeOrderStatuses());
                            });
                    });
                }
            } elseif ($request->cv_status == 'backout') {
                // فلتر خاص للعاملات باك اوت
                $rows->where('status', 'backout')
                    ->where('available', 0);
            }
        }

        if ($request->filled('rental_stat')) {
            $rentalStat = $request->rental_stat;
            $today = Carbon::today();
            $expiryThreshold = Carbon::today()->addDays(30);

            if ($rentalStat === 'expiring') {
                $rows->whereNotNull('id_expiry_date')
                    ->whereBetween('id_expiry_date', [$today, $expiryThreshold]);
            } elseif ($rentalStat === 'active_contracts') {
                $this->applyRentalActiveContractsFilter($rows);
            } elseif ($rentalStat === 'completed_rental_contracts') {
                $this->applyRentalCompletedContractsFilter($rows);
            } elseif ($rentalStat === 'in_accommodation') {
                $rows->whereHas('contracts', function ($query) {
                    $query->insideShelter();
                });
            } elseif ($rentalStat === 'wants_to_work') {
                $rows->whereHas('contracts', function ($query) {
                    $query->insideShelter()
                        ->whereHas('info', function ($infoQuery) {
                            $infoQuery->where('wants_to_work', 'yes');
                        });
                });
            } elseif ($rentalStat === 'in_deportation') {
                $rows->whereHas('contracts', function ($query) {
                    $query->insideShelter()
                        ->whereHas('info', function ($infoQuery) {
                            $infoQuery->where('wants_to_work', 'no');
                        });
                });
            } elseif ($rentalStat === 'undecided') {
                $rows->whereHas('contracts', function ($query) {
                    $query->insideShelter()
                        ->whereHas('info', function ($infoQuery) {
                            $infoQuery->where(function ($condition) {
                                $condition->whereNull('wants_to_work')
                                    ->orWhere('wants_to_work', 'unknown');
                            });
                        });
                });
            }
        }
    }

    public function applyMaidFilterForBackout($request, $rows)
    {
        if ($request->has('passport_num') && $request->passport_num != null) {
            $rows->whereRaw('LOWER(passport_num) like ?', ['%' . strtolower($request->passport_num) . '%']);
        }

        if ($request->has('nationality_id') && $request->nationality_id != null) {
            $rows->where('nationality_id', $request->nationality_id);
        }
        if ($request->has('order_by') && $request->order_by != null) {
            if ($request->order_by == 'low_price') {
                $rows->orderBy('price', 'ASC');
            } elseif ($request->order_by == 'low_salary') {
                $rows->orderBy('salary', 'ASC');
            }
        }
        if ($request->has('job_type') && !empty($request->job_type)) {
            $rows->whereHas('jobTypes', function ($subQ) use ($request) {
                if (is_array($request->job_type)) {
                    $subQ->whereIn('job_type_id', $request->job_type);
                } else {
                    $subQ->where('job_type_id', $request->job_type);
                }
            });
        }
        if ($request->has('experience_years') && $request->experience_years != null) {
            $rows->where('experience_years', $request->experience_years);
        }
        if ($request->has('experience_status') && $request->experience_status != null) {
            $rows->where('experience_status', $request->experience_status);
        }
        if ($request->has('datefilter') && $request->datefilter != null) {
            $dates = explode(' - ', $request->datefilter);
            if (count($dates) == 2) {
                $startDate = Carbon::parse($dates[0])->startOfDay();
                $endDate = Carbon::parse($dates[1])->endOfDay();
                $rows->whereBetween('created_at', [$startDate, $endDate]);
            }
        }
    }

    public function applyMaidFilterForDeleted($request, $rows)
    {
        if ($request->has('passport_num') && $request->passport_num != null) {
            $rows->whereRaw('LOWER(passport_num) like ?', ['%' . strtolower($request->passport_num) . '%']);
        }

        if ($request->has('nationality_id') && $request->nationality_id != null) {
            $rows->where('nationality_id', $request->nationality_id);
        }

        if ($request->has('job_type') && !empty($request->job_type)) {
            $rows->whereHas('jobTypes', function ($subQ) use ($request) {
                if (is_array($request->job_type)) {
                    $subQ->whereIn('job_type_id', $request->job_type);
                } else {
                    $subQ->where('job_type_id', $request->job_type);
                }
            });
        }

        if ($request->has('experience_years') && $request->experience_years != null) {
            $rows->where('experience_years', $request->experience_years);
        }

        if ($request->has('datefilter') && $request->datefilter != null) {
            $dates = explode(' - ', $request->datefilter);
            if (count($dates) == 2) {
                $startDate = Carbon::parse($dates[0])->startOfDay();
                $endDate = Carbon::parse($dates[1])->endOfDay();
                $rows->whereBetween('deleted_at', [$startDate, $endDate]);
            }
        }
    }

    public function getMaidDetails($maidId)
    {
        $maid = Maid::withTrashed()->with(['nationality', 'religion', 'job_types.job_type'])
            ->findOrFail($maidId);

        // Ensure the image URL is correctly formatted
        $imageUrl = $maid->image ? showFile($maid->image) : null;
        $cvFileUrl = $maid->cv_file ? showFile($maid->cv_file) : null;
        $cvPreviewUrl = $maid->cv_preview ? showFile($maid->cv_preview) : $cvFileUrl;

        // Determine the locale from the URL
        $locale = request()->segment(1); // Assuming language segment is the first part of the URL

        // Set the translated religion title based on the detected locale
        if ($maid->religion && $maid->religion->title == 'muslim') {
            $regTitle = $locale == 'ar' ? 'مسلم' : 'Muslim';
        } else {
            $regTitle = $locale == 'ar' ? 'غير مسلم' : 'Non-Muslim';
        }

        // Get nationality title based on locale
        $nationalityTitle = '';
        if ($maid->nationality) {
            $nationalityTitle = $locale == 'ar' ? $maid->nationality->title : $maid->nationality->sub_title;
        }

        // Get job type title
        $jobTypeTitle = '';
        if ($maid->job_types && $maid->job_types->isNotEmpty()) {
            $jobType = $maid->job_types->first()->job_type;
            if ($jobType) {
                $jobTypeTitle = $jobType->title;
            }
        }

        // Ensure all required fields are present
        $response = [
            'id' => $maid->id,
            'name' => $maid->name,
            'passport_num' => $maid->passport_num,
            'religion_title' => $regTitle,
            'religion' => $regTitle,
            'nationality' => [
                'id' => $maid->nationality ? $maid->nationality->id : null,
                'title' => $nationalityTitle
            ],
            'job_types' => [
                [
                    'id' => $maid->job_types->first()->job_type->id ?? null,
                    'title' => $jobTypeTitle
                ]
            ],
            'job_title' => $jobTypeTitle,
            'occupation' => $jobTypeTitle,
            'salary' => $maid->resolved_salary,
            'price' => $maid->resolved_price,
            'request_type' => \App\Models\ingaz\Maid::resolveRequestType($maid->request_type),
            'request_type_label' => $maid->request_type_label,
            'experience_years' => $maid->experience_years,
            'age' => $maid->age,
            'birth_date' => $maid->birth_date,
            'height' => $maid->height,
            'weight' => $maid->weight,
            'marital_status' => $maid->marital_status ?? 'single',
            'image' => $imageUrl,
            'cv_file' => $cvFileUrl,
            'cv_preview' => $cvPreviewUrl
        ];

        return response()->json($response);
    }

    public function getOfficesByNationality(Request $request)
    {
        $offices = RecruitmentOffices::where('nationality_id', $request->nationality_id)
            ->with('translations')
            ->get()
            ->map(function ($office) {
                return [
                    'id' => $office->id,
                    'title' => $office->title
                ];
            });

        return response()->json($offices);
    }

    /**
     * Check if passport number is unique
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkPassportUnique(Request $request)
    {
        $passportNum = $request->passport_num;

        if (empty($passportNum)) {
            return response()->json(['exists' => false]);
        }

        $exists = objModel::where('passport_num', $passportNum)->exists();

        return response()->json(['exists' => $exists]);
    }

    public function getAvailableMaids()
    {
        try {
            $maids = Maid::where('available', true)
                ->where('status', 'available')
                ->with(['nationality'])
                ->select(['id', 'name', 'passport_num', 'nationality_id'])
                ->get()
                ->map(function ($maid) {
                    return [
                        'id' => $maid->id,
                        'name' => $maid->name,
                        'passport_num' => $maid->passport_num,
                        'nationality' => $maid->nationality ? $maid->nationality->title : ''
                    ];
                });

            return jsonSuccess(['maids' => $maids]);
        } catch (\Exception $e) {
            return jsonError(['message' => 'حدث خطأ أثناء تحميل العاملات المتاحات']);
        }
    }

    public function searchMaids(Request $request)
    {
        try {
            $search = $request->get('search');

            $maids = Maid::where('available', true)
                ->where('status', 'available')
                ->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%' . $search . '%')
                        ->orWhere('passport_num', 'like', '%' . $search . '%');
                })
                ->with(['nationality'])
                ->select(['id', 'name', 'passport_num', 'nationality_id'])
                ->get()
                ->map(function ($maid) {
                    return [
                        'id' => $maid->id,
                        'name' => $maid->name,
                        'passport_num' => $maid->passport_num,
                        'nationality' => $maid->nationality ? $maid->nationality->title : ''
                    ];
                });

            return jsonSuccess(['maids' => $maids]);
        } catch (\Exception $e) {
            return jsonError(['message' => 'حدث خطأ أثناء البحث عن العاملات']);
        }
    }

    public function getMaidInfo($id)
    {
        try {
            $maid = Maid::with(['nationality', 'religion'])
                ->select(['id', 'name', 'passport_num', 'nationality_id', 'religion_id', 'age', 'experience_years'])
                ->findOrFail($id);

            return jsonSuccess([
                'maid' => [
                    'id' => $maid->id,
                    'name' => $maid->name,
                    'passport_num' => $maid->passport_num,
                    'nationality' => $maid->nationality ? $maid->nationality->title : '',
                    'age' => $maid->age,
                    'experience' => $maid->experience_years . ' سنوات'
                ]
            ]);
        } catch (\Exception $e) {
            return jsonError(['message' => 'حدث خطأ أثناء تحميل معلومات العاملة']);
        }
    }

    public function backout(Request $request)
    {
        $request->validate([
            'maid_id' => 'required|exists:maids,id',
            'backout_reason' => 'required|string|max:1000',
        ]);

        try {
            $maid = Maid::findOrFail($request->maid_id);
            $maid->update(['status' => 'backout', 'available' => 0, 'back_out_reason' => $request->backout_reason]);

            // Save at maid unavailable record
            $record_maid_unavailable = MaidUnavailableRecord::create([
                'maid_id' => $request->maid_id,
                'reason' => $request->backout_reason,
                'admin_id' => auth()->guard('admin')->user()->id,
            ]);


            return jsonSuccess(['success' => true, 'message' => 'تم إرجاع العاملة إلى قائمة العاملات المُرجعة'], 'تم إرجاع العاملة إلى قائمة العاملات المُرجعة', 200);
        } catch (\Exception $e) {
            return jsonError(['success' => false, 'message' => 'حدث خطأ أثناء إرجاع العاملة'], 500);
        }
    }

    public function clearBackout(Request $request)
    {
        try {
            $maid = Maid::findOrFail($request->maid_id);
            $maid->update(['status' => 'available', 'available' => 1, 'back_out_reason' => null]);

            $record_maid_unavailable = MaidUnavailableRecord::where('maid_id', $request->maid_id)
                ->whereNotNull('reason')
                ->orderBy('created_at', 'desc')
                ->first();

            if ($record_maid_unavailable) {
                $record_maid_unavailable->delete();
            }

            return jsonSuccess(['success' => true, 'message' => 'تم إعادة العاملة إلى قائمة العاملات المتاحات'], 'تم إعادة العاملة إلى قائمة العاملات المتاحات', 200);
        } catch (\Exception $e) {
            return jsonError(['success' => false, 'message' => 'حدث خطأ أثناء إعادة العاملة'], 500);
        }
    }

    public function suspendCv(Request $request)
    {
        $maid = Maid::findOrFail($request->id);

        if ($maid) {
            if ($request->status == 1) {
                $maid->status = 'suspended';
                $maid->available = 0;
            } else {
                $maid->status = 'available';
                $maid->available = 1;
            }

            $maid->save();
            return jsonSuccess(['success' => true, 'message' => 'تم تحديث حالة العاملة'], 'تم تحديث حالة العاملة', 200);
        } else {
            return jsonError(['success' => false, 'message' => 'حدث خطأ أثناء تحديث حالة العاملة'], 500);
        }
    }
}
