<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Actions\ingaz\JobTitleAction;
use App\Http\Actions\ingaz\JobTypeAction;
use App\Http\Actions\ingaz\MaidAction as objAction;
use App\Http\Actions\ingaz\NationalityAction;
use App\Http\Actions\ingaz\RecruitmentOfficeAction;
use App\Http\Actions\ingaz\ReligionAction;
use App\Http\Actions\ingaz\SkillAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ingaz\MaidRequest;
use App\Http\Requests\StoreRapidCv;
use App\Models\ingaz\JobType;
use App\Models\ingaz\Maid;
use App\Models\ingaz\Maid as objModel;
use App\Models\ingaz\Nationality;
use App\Models\RecruitmentOffices;
use App\Models\ingaz\Skill;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;
use App\Models\MaidUnavailableRecord;

class MaidController extends Controller
{

    public $folderPath = "admin.ingaz.maids";
    public $postData = ["nationality_id", "expertises", 'experience_status', 'image', 'video', 'childrens_count', 'duration', 'additional_notes', 'video_link', 'cv_file', 'marital_status:ar', 'marital_status:en', 'languages', 'percentage', 'experience_years', 'cv_preview', 'religion_id', 'passport_num', 'price', 'salary', 'age', 'office_id', 'name:en', 'type_id', 'job_id', 'skills', 'birth_date', 'height', 'weight', 'id_number', 'id_expiry_date', 'create_shelter_contract'];
    public $mainRoute = "maids";

    public function __construct()
    {
        // $this->middleware('permission:العاملات-قراءة,admin', ['only' => ['index']]);
        // $this->middleware('permission:العاملات-انشاء,admin', ['only' => ['create', 'store']]);
        // $this->middleware('permission:العاملات-تعديل,admin', ['only' => ['edit', 'update']]);
        // $this->middleware('permission:العاملات-حذف,admin', ['only' => ['destroy']]);
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
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('is_pinned');

        if ($request->ajax()) {
            $this->applyMaidFilter($request, $query);

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

                    $pinnedBadge = $row->is_pinned
                        ? '<span class="badge bg-warning-subtle text-warning ms-1"><i class="fas fa-thumbtack text-secondary"></i> ' . helperTrans('ingaz.pinned') . '</span>'
                        : '';
                    $cvCodeLabel = helperTrans('ingaz.cv_code');
                    $cvCodeLine = $cvCode
                        ? '<div class="text-muted text-medium"><i class="fas fa-id-card me-1 text-secondary"></i>' . $cvCodeLabel . ': ' . $cvCode . '</div>'
                        : '';

                    return '<div class="d-flex flex-column gap-2">'
                        . '<div class="d-flex align-items-center gap-2">'
                        . '<span class="fw-semibold text-dark maid-name">' . $name . '</span>'
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

                    $hasActiveOrder = $row->orders->whereIn('status', ['new', 'under_process', 'contracted', 'contracted_without_contract'])->isNotEmpty();

                    if ($hasActiveOrder && $row->status === 'available') {
                        $statusMeta = $statuses['reserved'];
                    }

                    if ($row->maid_accommodation_type == 2) {
                        $statusMeta = ['class' => 'bg-info-subtle text-info', 'icon' => 'fa-warehouse', 'label' => $labelAccommodation];
                        if ($row->status == 'deported') {
                            $statusMeta = ['class' => 'bg-secondary-subtle text-secondary', 'icon' => 'fa-plane-departure', 'label' => $labelDeported];
                        }
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

                        // Office
                        . '<div class="text-muted small d-flex align-items-center gap-2">'
                        . '<i class="fas fa-building text-secondary"></i>'
                        . '<span>' . $officeTitle . '</span>'
                        . '</div>'

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
                    $activeOrders = $row->orders->whereIn('status', ['new', 'under_process', 'contracted', 'contracted_without_contract'])->count();
                    $contractsTotal = $row->contracts->count();
                    $activeContracts = $row->contracts->whereIn('status', ['new', 'msaned', 'tafwed', 'taffez', 'tazker', 'arrival'])->count();
                    
                    $ordersLabel = helperTrans('ingaz.total_orders_count');
                    $activeOrdersLabel = helperTrans('ingaz.active_orders_count');
                    $contractsLabel = helperTrans('ingaz.rental_contracts_count');
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
                            $menuItems .= '  <a  href="javascript:void(0)" onclick="suspendCv(' . $row->id . ',' . 0 . ')"  title="' . 'إلغاء تعليق السيرة' . '" class="dropdown-item"><i class="fas fa-play-circle text-success me-2"></i>' . 'إلغاء تعليق السيرة' . '</a>';
                        } elseif ($row->status == "available") {
                            $menuItems .= ' <a href="javascript:void(0)" onclick="suspendCv(' . $row->id . ',' . 1 . ')"  title="' . 'تعليق السيرة' . '" class="dropdown-item"><i class="fas fa-pause-circle text-warning me-2"></i>' . 'تعليق السيرة' . '</a>';
                        }
                    }

                    if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_EDIT)) {
                        if ($row->status == "available" || $row->status == "suspended") {
                            $menuItems .= '<a href="javascript:void(0)" onclick="back_out(' . $row->id . ')" title="' . 'باك اوت' . '" class="dropdown-item  "><i class="fas fa-toggle-off text-danger me-2"></i>' . 'باك اوت' . '</a>';
                        } elseif ($row->status == "backout") {
                            $menuItems .= '<a href="javascript:void(0)" onclick="back_out_on(' . $row->id . ')" title="' . 'ارجاع متاح' . '" class="dropdown-item  "><i class="fas fa-toggle-on text-success me-2"></i>' . 'ارجاع متاح' . '</a>';
                        }
                    }


                    if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_TOGGLE_PIN) && $row->available == 1) {
                        $pinLabel = $row->is_pinned ? helperTrans('ingaz.unpin_maid') : helperTrans('ingaz.pin_maid');
                        $menuItems .= '<li><button class="dropdown-item toggle-pin" data-id="' . $row->id . '" data-pinned="' . ($row->is_pinned ? 'true' : 'false') . '"><i class="fas fa-thumbtack text-warning me-2"></i>' . $pinLabel . '</button></li>';
                    }

                    if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_DELETE)) {
                        $menuItems .= '<li><div class="dropdown-item" style="padding: 4px 16px !important;">' . deleteButton(route($this->mainRoute . ".destroy", $row->id)) . '</div></li>';
                    }

                    return '<div class="dropdown actions-dropdown">
                                <button class="btn btn-sm btn-primary dropdown-toggle actions-btn" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                                    <i class="fas fa-cogs me-1"></i>' . helperTrans('ingaz.actions_menu') . '
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-lg">
                                    ' . $menuItems . '
                                </ul>
                            </div>';
                })
                ->rawColumns(['photo', 'profile', 'added_info', 'status_badge', 'job_info', 'metrics', 'actions'])
                ->make(true);
        }

        $stats = [
            'total' => objModel::count(),
            'available' => objModel::where('status', 'available')->count(),
            'reserved' => objModel::where('status', 'reserved')->count(),
            // 'in_accommodation' => objModel::where('status', 'in_accommodation')->count(),
            'in_accommodation' => objModel::where('maid_accommodation_type', '2')->count(),
            'backout' => objModel::where('status', 'backout')->count(),
            'suspended' => objModel::where('status', 'suspended')->count(),
            'deported' => objModel::where('status', 'deported')->count(),
            'pinned' => objModel::where('is_pinned', 1)->count(),
        ];

        $filters = [
            'nationalities' => Nationality::orderByTranslation('sub_title', 'asc')->get(['id']),
            'offices' => $officeAct->getAllOffices(),
            'job_types' => JobType::with('translations')->orderByTranslation('title', 'asc')->get(['id']),
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

    public function noneAvailableMaids(Request $request, objAction $Action)
    {
        $rows = objModel::latest()
            ->with(['nationality', 'religion', 'jobTypes', 'contracts', 'orders', 'unavailableRecords']) // Added unavailableRecords
            ->select(['id', 'cv_code', 'image', 'cv_file', 'cv_preview', 'nationality_id', 'religion_id', 'passport_num', 'price', 'salary', 'age', 'is_show', 'available', 'available_at', 'created_at', 'is_pinned', 'status']) // Added status field
            ->where('status', 'backout')
            ->where('available', false);

        if ($request->ajax()) {
            // تطبيق الفلاتر الإضافية مع الحفاظ على فلتر backout
            $this->applyMaidFilterForBackout($request, $rows);

            $dataTable = DataTables::of($rows)
                ->addIndexColumn()
                ->editColumn('is_show', function ($row) {
                    if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_TOGGLE_STATUS)) {
                        return $row->is_show == 1 ?
                            '<button class="btn btn-sm btn-success update-status" data-id="' . $row->id . '">ظاهر</button>' :
                            '<button class="btn btn-sm btn-dark update-status" data-id="' . $row->id . '">مخفي</button>';
                    } else {
                        return $row->is_show == 1 ? '<span class="badge bg-success">ظاهر</span>' : '<span class="badge bg-dark">مخفي</span>';
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

                    $imgTag = '<img src="' . $imageUrl . '" alt="maid-file" class="border" style="width:90px;height:120px;object-fit:cover;">';

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
                        return '<span class="badge bg-danger">باك اوت</span>';
                    } elseif ($row->available == 0) {
                        return '<span class="badge bg-warning">محجوزة</span>';
                    } else {
                        // التحقق من وجود عقود أو طلبات نشطة
                        $hasActiveContracts = $row->contracts()->whereIn('status', ['new', 'msaned', 'tafwed', 'taffez', 'tazker', 'arrival'])->exists();
                        $hasActiveOrders = $row->orders()->whereIn('status', ['new', 'under_process', 'contracted', 'contracted_without_contract'])->exists();

                        if ($hasActiveContracts || $hasActiveOrders) {
                            return '<span class="badge bg-warning">محجوزة</span>';
                        } else {
                            return '<span class="badge bg-success">متاحة</span>';
                        }
                    }
                })
                ->addColumn('unavailable_reason', function ($row) {
                    // جلب سبب عدم التوفر من جدول maid_unavailable_records
                    $unavailableRecord = $row->unavailableRecords()->latest()->first();
                    if ($unavailableRecord) {
                        return '<span class="reason-badge" title="' . htmlspecialchars($unavailableRecord->reason) . '">' .
                            htmlspecialchars(substr($unavailableRecord->reason, 0, 50)) .
                            (strlen($unavailableRecord->reason) > 50 ? '...' : '') . '</span>';
                    }
                    return '<span class="text-muted">لا يوجد سبب محدد</span>';
                })
                ->addColumn('actions', function ($row) {
                    $showButton = '';
                    $editButton = '';
                    $deleteButton = '';

                    $showButton = viewSingleButton(route('showMaidDetails', $row->id));

                    if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_CONTROL)) {
                        $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);
                    }

                    if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_DELETE)) {
                        $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    }

                    return $showButton . " " . $editButton . " " . $deleteButton;
                })
                ->rawColumns(['is_show', 'available', 'available_at', 'cv_file', 'cv_status', 'unavailable_reason', 'actions'])
                ->make(true);

            return $dataTable;
        }

        $bladeTitle = "العاملات باك اوت";

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

    public function showMaidDetails($id)
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
        ])->findOrFail($id);

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

        $stats = [
            'orders_total' => $orders->count(),
            'orders_active' => $orders->whereIn('status', ['new', 'under_process', 'contracted', 'contracted_without_contract'])->count(),
            'contracts_total' => $contracts->count(),
            'contracts_active' => $contracts->whereIn('status', ['new', 'msaned', 'tafwed', 'taffez', 'tazker', 'arrival'])->count(),
            'experience_years' => $maid->experience_status === 'has_experience' ? ($maid->experience_years ?? 0) : 0,
        ];

        $availability = [
            'status' => $maid->status,
            'is_show' => $maid->is_show,
            'available' => $maid->available,
            'available_at' => $maid->available_at,
            'created_at' => $maid->created_at,
        ];

        $activities = method_exists($maid, 'activities')
            ? $maid->activities()->latest()->limit(10)->get()
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
            'activities'
        ));
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
                'requireIdFields'
            )
        );
    }
    public function create_rapid_cv(NationalityAction $act, RecruitmentOfficeAction $officeAct, Request $request)
    {
        // Handle direct request
        $nationalities = $act->getNationalities();
        $offices = $officeAct->getAllOffices();

        return view(
            "{$this->folderPath}.rapid_cv_add",
            compact(
                'nationalities',
                'offices',
            )
        );
    }

    public function store(MaidRequest $request, objAction $action)
    {
        $data = $request->all();

        // Set default status in Arabic
        $data['status'] = 'available';
        $data['added_by'] = auth()->user()->id;

        // $data['added_by'] = null; // Temporarily set to null
        // $data = $action->storeMaid($data, $request);
        $data = $action->storeMaid($data, $request);
        if ($request->ajax()) {
            return jsonSuccess($data);
        }

        return response()->json(['code' => 200, 'message' => 'تم إضافة العاملة بنجاح']);
    }

    public function store_rapid_cv(StoreRapidCv $request, objAction $action)
    {
        $data = $request->validated();

        // Set defaults so the rapid form saves without missing fields
        $data['status'] = 'available';
        $data['added_by'] = auth()->id();
        $data['available'] = $data['available'] ?? 1;
        // Rapid CVs should not be visible on the public site by default
        $data['is_show'] = $data['is_show'] ?? 0;
        $data['experience_status'] = $data['experience_status'] ?? 'no_experience';
        $data['experience_years'] = $data['experience_years'] ?? '0';

        // Map the provided English name into the non-translatable column
        if (isset($data['name:en']) && !isset($data['name'])) {
            $data['name'] = $data['name:en'];
        }

        $data = $action->storeRapidCvMaid($data, $request);

        return response()->json(['code' => 200, 'message' => 'تم إضافة السيرة الذاتية بنجاح']);
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
                'message' => 'حالة غير صالحة'
            ], 400);
        }

        $maid->status = $newStatus;
        $maid->save();

        return response()->json([
            'code' => 200,
            'message' => 'تم تحديث الحالة بنجاح',
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
                'message' => 'ليس لديك صلاحية لتغيير حالة الظهور'
            ], 403);
        }

        try {
            $model = objModel::findOrFail($id);
            $oldStatus = $model->is_show;
            $model->is_show = !$model->is_show;
            $model->save(); // Save the changes

            $status = $model->is_show ? 'ظاهر' : 'مخفي';
            $oldStatusText = $oldStatus ? 'ظاهر' : 'مخفي';

            // Log the action
            \Log::info("Maid visibility toggled", [
                'maid_id' => $id,
                'old_status' => $oldStatusText,
                'new_status' => $status,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => "تم تغيير الحالة من {$oldStatusText} إلى {$status} بنجاح",
                'new_status' => $model->is_show,
                'old_status' => $oldStatus
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'السيرة الذاتية غير موجودة'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Error toggling maid visibility', [
                'maid_id' => $id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث الحالة: ' . $e->getMessage()
            ], 500);
        }
    }

    public function edit(Request $request, $id, NationalityAction $act, RecruitmentOfficeAction $officeAct, ReligionAction $relAct, JobTitleAction $jobTitleAction, JobTypeAction $jobTypesAction, SkillAction $skillAction)
    {
        if ($request->ajax()) {
            $obj = objModel::findOrFail($id);
            return $this->createAjaxResponse($act, $officeAct, $relAct, $jobTitleAction, $jobTypesAction, 'edit', ['obj' => $obj]);
        }

        // Handle direct request for edit page
        $obj = objModel::findOrFail($id);
        $nationalities = $act->getNationalities();
        $religions = $relAct->getReligions();
        $jobs = $jobTitleAction->getJobsTitles();
        $types = $jobTypesAction->getJobsTypes();
        $offices = $officeAct->getAllOffices();
        $skills = $skillAction->get();

        return view("{$this->folderPath}.edit", compact('obj', 'nationalities', 'offices', 'religions', 'jobs', 'types', 'skills'));
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

            if (!$maid->available || $maid->status !== 'available') {
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
        $maid = objModel::findOrFail($id);
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
        $maidsQuery = objModel::with('nationality', 'religion', 'jobTypes', 'jobTitles');
        if ($request->boolean('deleted')) {
            $maidsQuery->onlyTrashed();
        }
        $maids = $maidsQuery->get();

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
        $maidsQuery = objModel::with('nationality', 'religion', 'jobTypes', 'jobTitles');
        if ($request->boolean('deleted')) {
            $maidsQuery->onlyTrashed();
        }
        $maids = $maidsQuery->get();

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
            // $statuses = (array) $request->status;
            // $rows->whereIn('status', $statuses);
            if ($request->status == 'in_accommodation') {
                $rows->where('maid_accommodation_type', 2);
            } else {
                $statuses = (array) $request->status;
                $rows->whereIn('status', $statuses);
            }
        }

        if ($request->filled('visibility')) {
            if ($request->visibility === 'visible') {
                $rows->where('is_show', true);
            } elseif ($request->visibility === 'hidden') {
                $rows->where('is_show', false);
            }
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

        // فلتر حالة السيرة الذاتية - تحديث ليدعم حالة backout
        if ($request->has('cv_status') && $request->cv_status != null) {
            if ($request->cv_status == 'available') {
                $rows->where('available', 1)
                    ->where('status', 'available')
                    ->whereDoesntHave('contracts', function ($q) {
                        $q->whereIn('status', ['new', 'msaned', 'tafwed', 'taffez', 'tazker', 'arrival']);
                    })
                    ->whereDoesntHave('orders', function ($q) {
                        $q->whereIn('status', ['new', 'under_process', 'contracted', 'contracted_without_contract']);
                    });
            } elseif ($request->cv_status == 'reserved') {
                $rows->where(function ($q) {
                    $q->where('available', 0)
                        ->where('status', 'reserved')
                        ->orWhereHas('contracts', function ($subQ) {
                            $subQ->whereIn('status', ['new', 'msaned', 'tafwed', 'taffez', 'tazker', 'arrival']);
                        })
                        ->orWhereHas('orders', function ($subQ) {
                            $subQ->whereIn('status', ['new', 'under_process', 'contracted', 'contracted_without_contract']);
                        });
                });
            } elseif ($request->cv_status == 'backout') {
                // فلتر خاص للعاملات باك اوت
                $rows->where('status', 'backout')
                    ->where('available', 0);
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
            $maid->update(['status' => 'backout', 'available' => 0, 'backout_reason' => $request->backout_reason]);

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
            $maid->update(['status' => 'available', 'available' => 1, 'backout_reason' => null]);

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
