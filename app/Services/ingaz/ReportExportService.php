<?php

namespace App\Services\ingaz;

use App\Enums\ingaz\ContractStatusEnum;
use App\Enums\ingaz\OrderEnum;
use App\Enums\Office\OfficesStatusEnum;
use App\Models\City;
use App\Models\Contract;
use App\Models\Country;
use App\Models\FinancialRequest;
use App\Models\RentalContract;
use App\Models\RecruitmentOffices;
use App\Models\ShelterPlaces;
use App\Models\SponsorshipTransfer;
use App\Models\TravelHistory;
use App\Models\auth\Admin;
use App\Models\ingaz\Complaint;
use App\Models\ingaz\ComplaintType;
use App\Models\ingaz\JobType;
use App\Models\ingaz\Maid;
use App\Models\ingaz\Nationality;
use App\Models\ingaz\Order;
use App\Models\ingaz\Religion;
use App\Models\ingaz\User;
use App\Models\office\Office;
use App\NewPermKeys;
use App\PermKeys;
use App\Services\BranchAccessService;
use App\Support\AutomationSettings;
use App\Support\TravelAirports;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PDF;
use Spatie\Activitylog\Models\Activity;

class ReportExportService
{
    protected ?int $rentalJobTypeId = null;
    protected ?bool $hasShelterQuickTags = null;

    public function availableReports(): array
    {
        return array_filter(
            $this->definitions(),
            fn ($definition, $key) => $this->canAccessReport($key),
            ARRAY_FILTER_USE_BOTH
        );
    }

    public function canAccessAnyReport(): bool
    {
        return !empty($this->availableReports());
    }

    public function formatPermissions(): array
    {
        $permissions = [];

        foreach (array_keys($this->availableReports()) as $reportKey) {
            $permissions[$reportKey] = [
                'pdf' => $this->canExportFormat($reportKey, 'pdf'),
                'excel' => $this->canExportFormat($reportKey, 'excel'),
            ];
        }

        return $permissions;
    }

    public function filterOptions(): array
    {
        return [
            'job_types' => JobType::with('translations')->orderBy('id')->get(['id']),
            'nationalities' => Nationality::with('translations')->orderBy('id')->get(['id']),
            'countries' => Country::query()->active()->ordered()->get(['id', 'name_ar', 'name_en']),
            'cities' => City::query()->active()->ordered()->get(['id', 'name_ar', 'name_en']),
            'religions' => Religion::with('translations')->orderBy('id')->get(['id']),
            'offices' => RecruitmentOffices::with('translations')->orderBy('id')->get(['id']),
            'admins' => Admin::query()
                ->select('id', 'name')
                ->whereNull('deleted_at')
                ->orderBy('name')
                ->get(),
            'shelters' => ShelterPlaces::with('translations')->orderBy('id')->get(['id']),
            'complaint_types' => ComplaintType::query()->orderBy('name')->get(['id', 'name']),
        ];
    }

    public function maidStatusOptions(): array
    {
        return [
            'available' => 'متاحة',
            'reserved' => 'محجوزة',
            'contracted' => 'تم التعاقد',
            'in_accommodation' => 'داخل الإيواء',
            'backout' => 'باك أوت',
            'suspended' => 'معلقة',
            'deported' => 'مرحلة الترحيل',
        ];
    }

    public function orderStatusOptions(): array
    {
        return [
            'current' => 'جارية',
            OrderEnum::New->value => $this->resolveOrderStatusLabel(OrderEnum::New->value),
            OrderEnum::UnderProcess->value => $this->resolveOrderStatusLabel(OrderEnum::UnderProcess->value),
            'completed' => 'مكتملة',
            'not_completed' => 'غير مكتملة',
            OrderEnum::Contracted->value => $this->resolveOrderStatusLabel(OrderEnum::Contracted->value),
            OrderEnum::ContractedWithoutContract->value => $this->resolveOrderStatusLabel(OrderEnum::ContractedWithoutContract->value),
            OrderEnum::Refused->value => $this->resolveOrderStatusLabel(OrderEnum::Refused->value),
            OrderEnum::Return->value => $this->resolveOrderStatusLabel(OrderEnum::Return->value),
            OrderEnum::CanceledBySystem->value => $this->resolveOrderStatusLabel(OrderEnum::CanceledBySystem->value),
        ];
    }

    public function clientStatusOptions(): array
    {
        return [
            'active' => 'مفعل',
            'blocked' => 'محظور',
        ];
    }

    public function recruitmentStatusOptions(): array
    {
        return [
            'current' => 'السارية',
            'arrival_today' => 'وصول اليوم',
            'delayed_arrival' => 'تجاوزت 60 يوماً بدون تذاكر',
            'expired_over_2y' => 'أقدم من عامين',
            ContractStatusEnum::PendingMaidSelection->value => 'انتظار اختيار العاملة',
            ContractStatusEnum::New->value => $this->resolveContractStatusLabel(ContractStatusEnum::New->value),
            ContractStatusEnum::Msaned->value => $this->resolveContractStatusLabel(ContractStatusEnum::Msaned->value),
            ContractStatusEnum::Tafwed->value => $this->resolveContractStatusLabel(ContractStatusEnum::Tafwed->value),
            ContractStatusEnum::Taffez->value => $this->resolveContractStatusLabel(ContractStatusEnum::Taffez->value),
            ContractStatusEnum::Tazker->value => $this->resolveContractStatusLabel(ContractStatusEnum::Tazker->value),
            'completed_stage' => 'المكتملة (إجمالي)',
            ContractStatusEnum::Arrival->value => $this->resolveContractStatusLabel(ContractStatusEnum::Arrival->value),
            ContractStatusEnum::Sari->value => $this->resolveContractStatusLabel(ContractStatusEnum::Sari->value),
            ContractStatusEnum::Completed->value => $this->resolveContractStatusLabel(ContractStatusEnum::Completed->value),
            ContractStatusEnum::Refunded->value => $this->resolveContractStatusLabel(ContractStatusEnum::Refunded->value),
            ContractStatusEnum::Back->value => $this->resolveContractStatusLabel(ContractStatusEnum::Back->value),
            ContractStatusEnum::Shelter->value => $this->resolveContractStatusLabel(ContractStatusEnum::Shelter->value),
        ];
    }

    public function warrantyStatusOptions(): array
    {
        return [
            'active' => 'نشط',
            'expired' => 'منتهي',
            'not_applicable' => 'غير مطبق',
        ];
    }

    public function rentStatusOptions(): array
    {
        return [
            'new' => 'جديد',
            'waiting_contract_send' => 'انتظار إرسال العقد',
            'contract_sent' => 'تم إرسال العقد',
            'contract_signed' => 'تم توقيع العقد',
            'maid_delivered' => 'تم تسليم العاملة',
            'active' => 'نشط',
            'completed' => 'مكتمل',
            'transferred' => 'تم النقل',
            'cancelled' => 'ملغي',
            'suspended' => 'معلق',
            'available' => 'مغلقة - متاحة',
        ];
    }

    public function rentExtensionFilterOptions(): array
    {
        return [
            'extended' => 'ممددة',
            'not_extended' => 'غير ممددة',
        ];
    }

    public function shelterGuaranteeStatusOptions(): array
    {
        return [
            'within_guarantee' => 'ضمن الضمان',
            'expired_guarantee' => 'منتهي الضمان',
        ];
    }

    public function shelterWorkWillingnessOptions(): array
    {
        return [
            'yes' => 'ترغب بالعمل',
            'no' => 'لا ترغب بالعمل',
            'unknown' => 'غير محدد',
        ];
    }

    public function shelterInstantReceiptOptions(): array
    {
        return [
            'yes' => 'متاحة للاستلام الفوري',
            'no' => 'غير متاحة للاستلام الفوري',
        ];
    }

    public function shelterServiceCategoryOptions(): array
    {
        return [
            'recruitment' => 'استقدام',
            'rental' => 'تأجير',
            'transfer' => 'نقل خدمات',
        ];
    }

    public function shelterReceiptSourceOptions(): array
    {
        $options = __('shelter_worker_statuses.received_by');

        return is_array($options) ? $options : [];
    }

    public function sponsorshipTransferStatusOptions(): array
    {
        return [
            'pending' => 'في الانتظار',
            'waiting_contract_send' => 'بانتظار تسجيل استلام العاملة',
            'contract_sent' => 'تم إرسال العقد',
            'contract_signed' => 'تم توقيع العقد',
            'maid_delivered' => 'تم تسليم العاملة',
            'trial_period' => 'فترة التجربة',
            'trial_started' => 'بدأت التجربة',
            'approved' => 'موافق عليه',
            'transferred' => 'تم النقل',
            'failed' => 'فشل النقل',
            'rejected' => 'مرفوض',
            'completed' => 'مكتمل',
        ];
    }

    public function travelTypeOptions(): array
    {
        $options = __('travel_types.travel_types');

        return is_array($options) ? $options : [];
    }

    public function travelWithOptions(): array
    {
        $options = __('travel_types.travel_with');
        $options = is_array($options) ? $options : [];

        return $options + [
            'group_transport' => helperTrans('ingaz.travel_with_group_transport'),
        ];
    }

    public function travelStatusOptions(): array
    {
        $options = __('travel_types.status');

        return is_array($options) ? $options : [];
    }

    public function travelAirportOptions(): array
    {
        return TravelAirports::options();
    }

    public function complaintStatusOptions(): array
    {
        return [
            'pending' => __('ingaz.complaint_status_pending'),
            'in_progress' => __('ingaz.complaint_status_in_progress'),
            'resolved' => __('ingaz.complaint_status_resolved'),
            'closed' => __('ingaz.complaint_status_closed'),
            'rejected' => __('ingaz.complaint_status_rejected'),
        ];
    }

    public function complaintPriorityOptions(): array
    {
        return [
            'urgent' => __('ingaz.priority_urgent'),
            'high' => __('ingaz.priority_high'),
            'medium' => __('ingaz.priority_medium'),
            'low' => __('ingaz.priority_low'),
        ];
    }

    public function officeStatusOptions(): array
    {
        return [
            OfficesStatusEnum::Active->value => __('ingaz.active'),
            OfficesStatusEnum::DisActive->value => __('ingaz.dis_active'),
        ];
    }

    public function officeLinkedStateOptions(): array
    {
        return [
            'with_links' => __('ingaz.has_linked_cvs'),
            'without_links' => __('ingaz.without_linked_cvs'),
        ];
    }

    public function officeCostStateOptions(): array
    {
        return [
            'with_cost' => __('ingaz.has_cost_of_dollar'),
            'without_cost' => __('ingaz.without_cost_of_dollar'),
        ];
    }

    public function financialRequestTypeOptions(): array
    {
        return FinancialRequest::types();
    }

    public function financialRequestStatusOptions(): array
    {
        return FinancialRequest::statuses();
    }

    public function financialRequestPriorityOptions(): array
    {
        return FinancialRequest::priorities();
    }

    public function financialRequestScopeOptions(): array
    {
        return [
            'incomplete' => 'غير مكتملة',
            'paid' => 'مكتملة / تم سدادها',
        ];
    }

    public function export(string $reportKey, string $format, Request $request)
    {
        $this->ensureReportAccess($reportKey);
        abort_unless($this->canExportFormat($reportKey, $format), 403);
        $this->configureRuntime();

        $payload = $this->buildReportPayload($reportKey, $request);

        return $format === 'excel'
            ? $this->exportExcel($payload)
            : $this->exportPdf($payload, $request);
    }

    private function definitions(): array
    {
        return [
            'maids' => [
                'label' => 'السير الذاتية',
                'icon' => 'fas fa-user-friends',
                'description' => 'تصفية السير الذاتية وتصديرها بصيغة PDF موحدة أو Excel.',
            ],
            'orders' => [
                'label' => 'طلبات الاستقدام',
                'icon' => 'fas fa-clipboard-list',
                'description' => 'تصدير الطلبات الحالية بعد الفلترة حسب الحالة والعميل والمسوق وغيرها.',
            ],
            'clients' => [
                'label' => 'العملاء',
                'icon' => 'fas fa-users',
                'description' => 'تقرير العملاء مع بيانات الاتصال والحالة ومؤشرات النشاط وآخر العمليات.',
            ],
            'recruitment_contracts' => [
                'label' => 'عقود الاستقدام',
                'icon' => 'fas fa-file-signature',
                'description' => 'تقرير موحد لعقود الاستقدام مع فلاتر المرحلة والضمان والعميل والعاملة.',
            ],
            'rent_contracts' => [
                'label' => 'عقود الإيجار',
                'icon' => 'fas fa-handshake',
                'description' => 'تقرير عقود الإيجار مع حالة العقد والتمديد والضمان والمسوق.',
            ],
            'shelter_contracts' => [
                'label' => 'عقود الإيواء',
                'icon' => 'fas fa-house-user',
                'description' => 'تقرير الإيواء الموحّد مع الضمان والرغبة في العمل ومكان الإيواء.',
            ],
            'sponsorship_transfers' => [
                'label' => 'نقل الكفالة',
                'icon' => 'fas fa-exchange-alt',
                'description' => 'تقرير معاملات نقل الكفالة مع بيانات الكفلاء والعاملة والحالة والمدفوعات.',
            ],
            'deportation_travel' => [
                'label' => 'رحلات الترحيل',
                'icon' => 'fas fa-plane-departure',
                'description' => 'تقرير رحلات الترحيل مع بيانات العقد والعاملة والعميل والرحلة.',
            ],
            'travel_trips' => [
                'label' => 'رحلات الاستقدام',
                'icon' => 'fas fa-plane-arrival',
                'description' => 'تقرير رحلات الاستقدام مجمعة حسب الرحلة كما تظهر في قسم السفر.',
            ],
            'complaints' => [
                'label' => 'الشكاوى',
                'icon' => 'fas fa-headset',
                'description' => 'تقرير الشكاوى والتذاكر مع الحالة والأولوية والتعيين والردود.',
            ],
            'offices' => [
                'label' => 'المكاتب',
                'icon' => 'fas fa-building',
                'description' => 'تقرير المكاتب الخارجية مع الجنسية والحساب والمدير والارتباطات.',
            ],
            'financial_requests' => [
                'label' => 'الطلبات المالية',
                'icon' => 'fas fa-money-check-alt',
                'description' => 'تقرير الطلبات المالية التشغيلية حسب النوع والحالة والتصعيد.',
            ],
        ];
    }

    private function canAccessReport(string $reportKey): bool
    {
        return $this->canExportFormat($reportKey, 'pdf')
            || $this->canExportFormat($reportKey, 'excel');
    }

    private function canExportFormat(string $reportKey, string $format): bool
    {
        return match ($reportKey) {
            'maids' => $format === 'pdf'
                ? AdminGuardHasAnyPerm([
                    PermKeys::MAIDS_EXPORT,
                    PermKeys::MAIDS_EXPORT_PDF,
                    NewPermKeys::REPORTS_EXPORT,
                ])
                : AdminGuardHasAnyPerm([
                    PermKeys::MAIDS_EXPORT,
                    PermKeys::MAIDS_EXPORT_EXCEL,
                    NewPermKeys::REPORTS_EXPORT,
                ]),
            'orders' => AdminGuardHasAnyPerm([
                PermKeys::ORDERS_EXPORT,
                NewPermKeys::REPORTS_EXPORT,
            ]),
            'clients' => $format === 'pdf'
                ? AdminGuardHasAnyPerm([
                    PermKeys::CLIENTS_EXPORT,
                    PermKeys::CLIENTS_EXPORT_PDF,
                    NewPermKeys::CLIENTS_EXPORT,
                    NewPermKeys::REPORTS_EXPORT,
                ])
                : AdminGuardHasAnyPerm([
                    PermKeys::CLIENTS_EXPORT,
                    PermKeys::CLIENTS_EXPORT_EXCEL,
                    NewPermKeys::CLIENTS_EXPORT,
                    NewPermKeys::REPORTS_EXPORT,
                ]),
            'recruitment_contracts' => AdminGuardHasAnyPerm([
                PermKeys::REC_CONTRACTS_EXPORT,
                NewPermKeys::REC_CONTRACTS_EXPORT,
                NewPermKeys::REPORTS_EXPORT,
            ]),
            'rent_contracts' => AdminGuardHasAnyPerm([
                PermKeys::RENT_CONTRACT_VIEW,
                NewPermKeys::REPORTS_EXPORT,
            ]),
            'shelter_contracts' => AdminGuardHasAnyPerm([
                PermKeys::SHELTER_CONTRACT_VIEW,
                NewPermKeys::REPORTS_EXPORT,
            ]),
            'sponsorship_transfers' => AdminGuardHasAnyPerm([
                PermKeys::SPONSOR_TRANSFER_VIEW,
                PermKeys::SPONSOR_TRANSFER_CONTROL,
                NewPermKeys::REPORTS_EXPORT,
            ]),
            'deportation_travel', 'travel_trips' => AdminGuardHasAnyPerm([
                PermKeys::TRAVEL_VIEW,
                PermKeys::TRAVEL_CONTROL,
                NewPermKeys::REPORTS_EXPORT,
            ]),
            'complaints' => AdminGuardHasAnyPerm([
                PermKeys::COMPLAINT_VIEW,
                PermKeys::COMPLAINT_CONTROL,
                NewPermKeys::REPORTS_EXPORT,
            ]),
            'offices' => AdminGuardHasAnyPerm([
                PermKeys::OFFICE_VIEW,
                PermKeys::OFFICE_CONTROL,
                NewPermKeys::REPORTS_EXPORT,
            ]),
            'financial_requests' => AdminGuardHasAnyPerm([
                PermKeys::FINANCIAL_REQUESTS_VIEW,
                PermKeys::FINANCIAL_REQUESTS_CREATE,
                PermKeys::FINANCIAL_REQUESTS_EXPORT,
                PermKeys::FINANCIAL_REQUESTS_CONTROL,
                NewPermKeys::REPORTS_EXPORT,
            ]),
            default => false,
        };
    }

    private function ensureReportAccess(string $reportKey): void
    {
        abort_unless(isset($this->definitions()[$reportKey]), 404);
        abort_unless($this->canAccessReport($reportKey), 403);
    }

    private function configureRuntime(): void
    {
        @ini_set('memory_limit', '768M');
        @set_time_limit(600);
    }

    private function buildReportPayload(string $reportKey, Request $request): array
    {
        return match ($reportKey) {
            'maids' => $this->buildMaidReportPayload($request),
            'orders' => $this->buildOrdersReportPayload($request),
            'clients' => $this->buildClientsReportPayload($request),
            'recruitment_contracts' => $this->buildRecruitmentContractsReportPayload($request),
            'rent_contracts' => $this->buildRentContractsReportPayload($request),
            'shelter_contracts' => $this->buildShelterContractsReportPayload($request),
            'sponsorship_transfers' => $this->buildSponsorshipTransfersReportPayload($request),
            'deportation_travel' => $this->buildDeportationTravelReportPayload($request),
            'travel_trips' => $this->buildTravelTripsReportPayload($request),
            'complaints' => $this->buildComplaintsReportPayload($request),
            'offices' => $this->buildOfficesReportPayload($request),
            'financial_requests' => $this->buildFinancialRequestsReportPayload($request),
        };
    }

    private function buildMaidReportPayload(Request $request): array
    {
        $query = Maid::query()
            ->with([
                'nationality',
                'religion',
                'jobTypes',
                'office',
                'contracts' => function ($contractQuery) {
                    $contractQuery->select(['id', 'maid_id', 'status']);
                },
                'orders' => function ($orderQuery) {
                    $orderQuery->select(['id', 'maid_id', 'status']);
                },
            ])
            ->select([
                'id',
                'cv_code',
                'name',
                'passport_num',
                'nationality_id',
                'religion_id',
                'office_id',
                'price',
                'salary',
                'available',
                'status',
                'is_pinned',
                'created_at',
                'maid_accommodation_type',
                'request_type',
            ]);

        $this->applyRecruitmentMaidsScope($query);
        $this->applyMaidFilters($request, $query);

        $maids = $query
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->get();

        return [
            'title' => 'تقرير السير الذاتية',
            'filename_prefix' => 'maids_report',
            'headings' => [
                'رقم السيرة',
                'الاسم',
                'رقم الجواز',
                'الجنسية',
                'المكتب الخارجي',
                'نوع الطلب',
                'الحالة',
                'الراتب',
                'التكلفة',
                'أنواع العمل',
                'تاريخ الإضافة',
            ],
            'rows' => $maids->map(function (Maid $maid) {
                return [
                    $maid->cv_code ?: $maid->id,
                    $maid->name ?: '-',
                    $maid->passport_num ?: '-',
                    $this->modelLabel($maid->nationality, ['sub_title', 'title', 'name']),
                    $this->modelLabel($maid->office, ['title', 'name']),
                    $maid->request_type_label,
                    $this->resolveMaidExportStatus($maid),
                    $this->formatMoney($maid->resolved_salary),
                    $this->formatMoney($maid->resolved_price),
                    $maid->jobTypes->pluck('title')->filter()->implode('، ') ?: '-',
                    $this->formatDateValue($maid->created_at, true),
                ];
            })->all(),
        ];
    }

    private function buildOrdersReportPayload(Request $request): array
    {
        $query = Order::query()
            ->with([
                'user',
                'maid.nationality',
                'maid.office',
                'jobType',
                'details.admin',
            ])
            ->where('job_type_id', 1)
            ->orderByDesc('created_at');

        $this->applyOrderFilters($request, $query);

        $orders = $query->get();

        return [
            'title' => 'تقرير طلبات الاستقدام',
            'filename_prefix' => 'orders_report',
            'headings' => [
                'رقم الطلب',
                'كود الطلب',
                'العميل',
                'الجوال',
                'العاملة',
                'رقم الجواز',
                'الجنسية',
                'المكتب الخارجي',
                'الموظف المسؤول',
                'الحالة',
                'تاريخ الإنشاء',
            ],
            'rows' => $orders->map(function (Order $order) {
                return [
                    $order->id,
                    $order->order_code ?: '-',
                    $order->user?->name ?: '-',
                    $this->resolveUserPhone($order->user),
                    $order->maid?->name ?: '-',
                    $order->maid?->passport_num ?: '-',
                    $this->modelLabel($order->maid?->nationality, ['sub_title', 'title', 'name']),
                    $this->modelLabel($order->maid?->office, ['title', 'name']),
                    $order->resolveResponsibleAdmin()?->name ?: '-',
                    $this->resolveOrderStatusLabel($order->status),
                    $this->formatDateValue($order->created_at, true),
                ];
            })->all(),
        ];
    }

    private function buildClientsReportPayload(Request $request): array
    {
        $query = User::query()
            ->with(['nationality'])
            ->withCount([
                'orders as orders_count',
                'orders as active_orders_count' => function ($innerQuery) {
                    $innerQuery->whereNotIn('status', ['completed', 'done', 'refused', 'return']);
                },
                'rentalContracts as rental_contracts_count',
                'sponsorshipTransfersAsNewSponsor as transfer_requests_count',
            ])
            ->addSelect([
                'last_order_at' => Order::select('created_at')
                    ->whereColumn('user_id', 'users.id')
                    ->latest('created_at')
                    ->limit(1),
                'last_rental_contract_at' => RentalContract::select('created_at')
                    ->whereColumn('user_id', 'users.id')
                    ->latest('created_at')
                    ->limit(1),
                'last_transfer_request_at' => SponsorshipTransfer::select('created_at')
                    ->whereColumn('new_sponsor_id', 'users.id')
                    ->latest('created_at')
                    ->limit(1),
            ])
            ->orderByDesc('id');

        $this->applyClientFilters($request, $query);

        $clients = $query->get();

        return [
            'title' => 'تقرير العملاء',
            'filename_prefix' => 'clients_report',
            'headings' => [
                'رقم العميل',
                'الاسم',
                'رقم الجوال',
                'البريد الإلكتروني',
                'الجنسية',
                'رقم الهوية',
                'المدينة',
                'الحالة',
                'عدد الطلبات',
                'الطلبات النشطة',
                'عقود الإيجار',
                'طلبات النقل',
                'آخر طلب',
                'آخر عقد إيجار',
                'آخر طلب نقل',
                'تاريخ التسجيل',
            ],
            'rows' => $clients->map(function (User $client) {
                $nationalityLabel = $this->modelLabel($client->nationality, ['title', 'name_ar', 'name_en', 'name']);

                return [
                    $client->id,
                    $client->name ?: '-',
                    $this->resolveUserPhone($client),
                    $client->email ?: '-',
                    $nationalityLabel !== '' ? $nationalityLabel : '-',
                    $client->national_num ?: '-',
                    $client->city ?: '-',
                    $client->is_blocked ? 'محظور' : 'مفعل',
                    number_format((int) ($client->orders_count ?? 0)),
                    number_format((int) ($client->active_orders_count ?? 0)),
                    number_format((int) ($client->rental_contracts_count ?? 0)),
                    number_format((int) ($client->transfer_requests_count ?? 0)),
                    $this->formatDateValue($client->last_order_at, true),
                    $this->formatDateValue($client->last_rental_contract_at, true),
                    $this->formatDateValue($client->last_transfer_request_at, true),
                    $this->formatDateValue($client->created_at, true),
                ];
            })->all(),
        ];
    }

    private function buildRecruitmentContractsReportPayload(Request $request): array
    {
        $query = Contract::query()
            ->with([
                'user',
                'maid.nationality.translations',
                'maid.religion.translations',
                'maid.office.translations',
                'marketer',
                'order.details.admin',
                'info.manualMaidOffice.translations',
            ])
            ->whereIn('type', ['recruitment', 'maid'])
            ->orderByDesc('id');

        $this->applyRecruitmentContractFilters($request, $query);

        $contracts = $query->get();

        return [
            'title' => 'تقرير عقود الاستقدام',
            'filename_prefix' => 'recruitment_contracts_report',
            'headings' => [
                'رقم العقد',
                'العميل',
                'الجوال',
                'العاملة',
                'رقم الجواز',
                'المكتب الخارجي',
                'حالة العقد',
                'حالة الضمان',
                'تاريخ العقد',
                'تاريخ انتهاء الضمان',
                'المسوق',
            ],
            'rows' => $contracts->map(function (Contract $contract) {
                return [
                    $contract->id,
                    $contract->user?->name ?: '-',
                    $this->resolveUserPhone($contract->user),
                    $this->resolveRecruitmentContractMaidName($contract),
                    $this->resolveRecruitmentContractPassport($contract),
                    $this->resolveRecruitmentContractOffice($contract),
                    $this->resolveContractStatusLabel($contract->status),
                    $this->resolveRecruitmentWarrantyLabel($contract),
                    $this->formatDateValue($contract->info?->contract_date),
                    $this->formatDateValue($contract->warranty_end_date),
                    $contract->marketer?->name ?: ($contract->order?->details?->admin?->name ?: '-'),
                ];
            })->all(),
        ];
    }

    private function buildRentContractsReportPayload(Request $request): array
    {
        $query = RentalContract::query()
            ->with(['user', 'maid.nationality', 'info', 'marketer'])
            ->withCount('extensions')
            ->orderByDesc('id');

        $this->applyRentContractFilters($request, $query);

        $contracts = $query->get();

        return [
            'title' => 'تقرير عقود الإيجار',
            'filename_prefix' => 'rent_contracts_report',
            'headings' => [
                'رقم العقد',
                'العميل',
                'الجوال',
                'العاملة',
                'رقم الجواز',
                'المسوق',
                'حالة العقد',
                'التمديد',
                'حالة الضمان',
                'المدة بالشهور',
                'القيمة الشهرية',
                'قيمة العقد',
                'تاريخ العقد',
            ],
            'rows' => $contracts->map(function (RentalContract $contract) {
                return [
                    $contract->contract_number ?: $contract->id,
                    $contract->user?->name ?: '-',
                    $this->resolveUserPhone($contract->user),
                    $contract->maid?->name ?: '-',
                    $contract->maid?->passport_num ?: '-',
                    $contract->marketer?->name ?: '-',
                    $this->resolveRentStatusLabel($contract),
                    ($contract->extensions_count ?? 0) > 0 ? 'ممدد' : 'غير ممدد',
                    $this->resolveRentWarrantyStatusLabel($contract->warranty_status),
                    $contract->info?->contract_duration_months ?: '-',
                    $this->formatMoney($contract->info?->monthly_cost),
                    $this->formatMoney($contract->info?->contract_value),
                    $this->formatDateValue($contract->contract_date),
                ];
            })->all(),
        ];
    }

    private function buildShelterContractsReportPayload(Request $request): array
    {
        $query = Contract::insideShelter()
            ->with([
                'maid.jobTypes',
                'maid.nationality.translations',
                'user',
                'info.shelter.translations',
                'latestShelterLog',
                'travelHistory',
            ])
            ->orderByDesc('id');

        $this->applyShelterContractFilters($request, $query);

        $contracts = $query->get();

        return [
            'title' => 'تقرير عقود الإيواء',
            'filename_prefix' => 'shelter_contracts_report',
            'headings' => [
                'رقم العقد',
                'العميل',
                'العاملة',
                'رقم الجواز',
                'تصنيف الخدمة',
                'الجنسية',
                'مكان الإيواء',
                'تاريخ الوصول',
                'تاريخ دخول الإيواء',
                'حالة الضمان',
                'رغبة العمل',
                'مصدر الاستلام',
                'الاستلام الفوري',
                'التصنيفات السريعة',
            ],
            'rows' => $contracts->map(function (Contract $contract) {
                $quickTags = $this->extractQuickTags($contract->info?->shelter_quick_tags);
                $guaranteeData = $this->resolveShelterGuaranteeData($contract);

                return [
                    $contract->id,
                    $contract->user?->name ?: '-',
                    $contract->maid?->name ?: ('عاملة #' . ($contract->maid?->id ?: '-')),
                    $contract->maid?->passport_num ?: '-',
                    $this->serviceCategoryLabel($this->determineShelterServiceCategory($contract->maid)),
                    $this->resolveShelterNationalityLabel($contract),
                    $this->resolveShelterPlaceLabel($contract),
                    $this->formatDateValue($this->resolveShelterArrivalDate($contract)),
                    $this->formatDateValue($this->resolveShelterEntryDate($contract)),
                    $guaranteeData['label'],
                    $this->formatShelterWorkWillingnessLabel($contract->info?->wants_to_work),
                    $this->resolveShelterReceiptSourceLabel($contract->info?->receipt_source),
                    $this->formatShelterInstantReceiptLabel($contract),
                    !empty($quickTags) ? implode('، ', $quickTags) : '-',
                ];
            })->all(),
        ];
    }

    private function buildSponsorshipTransfersReportPayload(Request $request): array
    {
        $query = SponsorshipTransfer::query()
            ->with([
                'contract.maid.nationality',
                'oldSponsor',
                'newSponsor',
                'approvedBy',
                'transferredBy',
                'failedBy',
                'paymentConfirmedBy',
            ])
            ->orderByDesc('created_at');

        $this->applyReportBranchFilter($query, $request, 'sponsorship_transfers');
        $this->applySponsorshipTransferFilters($request, $query);

        $transfers = $query->get();

        return [
            'title' => 'تقرير نقل الكفالة',
            'filename_prefix' => 'sponsorship_transfers_report',
            'headings' => [
                'رقم النقل',
                'رقم العقد',
                'الكفيل السابق',
                'الكفيل الجديد',
                'العاملة',
                'رقم الجواز',
                'الجنسية',
                'الحالة',
                'أيام التجربة',
                'بداية التجربة',
                'نهاية التجربة',
                'قيمة العقد',
                'المدفوع',
                'المتبقي',
                'طريقة الدفع',
                'حالة الدفع',
                'تاريخ الإنشاء',
                'تاريخ النقل',
                'تاريخ الفشل',
            ],
            'rows' => $transfers->map(function (SponsorshipTransfer $transfer) {
                return [
                    $transfer->id,
                    $transfer->contract?->id ?: '-',
                    $transfer->oldSponsor?->name ?: '-',
                    $transfer->newSponsor?->name ?: '-',
                    $transfer->contract?->maid?->name ?: '-',
                    $transfer->contract?->maid?->passport_num ?: '-',
                    $this->modelLabel($transfer->contract?->maid?->nationality, ['sub_title', 'title', 'name']),
                    $transfer->status_text,
                    $transfer->trial_days ? $transfer->trial_days . ' يوم' : '-',
                    $this->formatDateValue($transfer->effective_trial_start_date, true),
                    $this->formatDateValue($transfer->trial_end_date),
                    $this->formatMoney($transfer->contract_value),
                    $this->formatMoney($transfer->paid_amount),
                    $this->formatMoney($transfer->remaining_amount),
                    $transfer->payment_type_text,
                    $transfer->payment_status_text,
                    $this->formatDateValue($transfer->created_at, true),
                    $this->formatDateValue($transfer->transferred_at, true),
                    $this->formatDateValue($transfer->failed_at, true),
                ];
            })->all(),
        ];
    }

    private function buildDeportationTravelReportPayload(Request $request): array
    {
        $query = TravelHistory::query()
            ->with([
                'user',
                'maid',
                'maid.nationality.translations',
                'order',
            ])
            ->where('travel_type', 'deportation')
            ->orderByDesc('id');

        $this->applyReportBranchFilter($query, $request, 'travel_histories');
        $this->applyDeportationTravelFilters($request, $query);

        $trips = $query->get();
        $statuses = $this->travelStatusOptions();

        return [
            'title' => 'تقرير رحلات الترحيل',
            'filename_prefix' => 'deportation_travel_report',
            'headings' => [
                '#',
                'رقم العقد',
                'العاملة',
                'العميل',
                'الجوال',
                'المطار',
                'تاريخ السفر',
                'وقت السفر',
                'نوع السفر',
                'شركة الطيران',
                'رقم الرحلة',
                'الحالة',
            ],
            'rows' => $trips->map(function (TravelHistory $trip) use ($statuses) {
                return [
                    $trip->id,
                    $trip->order_id ? '#' . $trip->order_id : 'غير محدد',
                    $trip->maid?->name ?: 'غير محدد',
                    $trip->user?->name ?: 'غير محدد',
                    $this->resolveUserPhone($trip->user),
                    $this->travelAirportLabel($trip->arrival_airport),
                    $this->formatDateValue($trip->arrival_date),
                    $trip->arrival_time ?: 'غير محدد',
                    $trip->travel_with ?: '-',
                    $trip->flight_company ?: '-',
                    $trip->flight_number ?: '-',
                    $statuses[$trip->status] ?? ($trip->status === 'completed' ? 'مكتملة' : 'مجدولة'),
                ];
            })->all(),
        ];
    }

    private function buildTravelTripsReportPayload(Request $request): array
    {
        $query = TravelHistory::query()
            ->with(['user', 'maid', 'maid.nationality', 'maid.nationality.translations'])
            ->where('travel_type', '!=', 'deportation')
            ->orderByDesc('id');

        $this->applyReportBranchFilter($query, $request, 'travel_histories');
        $this->applyTravelTripFilters($request, $query);

        $trips = $this->buildGroupedTravelTripsCollection($query->get());
        $types = $this->travelTypeOptions();
        $statuses = $this->travelStatusOptions();
        $travelWith = $this->travelWithOptions();

        return [
            'title' => 'تقرير رحلات الاستقدام',
            'filename_prefix' => 'travel_trips_report',
            'headings' => [
                '#',
                __('admin.client_id'),
                __('admin.maid_id'),
                helperTrans('ingaz.contracts'),
                __('admin.travel_type'),
                __('admin.travel_with'),
                __('admin.flight_company'),
                __('admin.flight_number'),
                __('admin.status'),
                'تاريخ الإنشاء',
                'المنشئ',
                helperTrans('ingaz.arrival_datetime_prefix'),
                'المطار',
            ],
            'rows' => $trips->map(function ($trip) use ($types, $statuses, $travelWith) {
                return [
                    $trip->id,
                    collect($trip->users)->pluck('name')->filter()->implode('، ') ?: 'غير محدد',
                    collect($trip->maids)->pluck('name')->filter()->implode('، ') ?: 'غير محدد',
                    collect($trip->order_ids)->map(fn ($id) => '#' . $id)->implode('، ') ?: '-',
                    $types[$trip->travel_type] ?? $trip->travel_type ?? '-',
                    $travelWith[$trip->travel_with] ?? $trip->travel_with ?? '-',
                    $trip->flight_company ?: '-',
                    $trip->flight_number ?: '-',
                    $statuses[$trip->status] ?? $trip->status ?? '-',
                    $this->formatDateValue($trip->created_at, true),
                    $trip->creator_name ?: '-',
                    trim(($trip->arrival_date ?: '-') . ' ' . ($trip->arrival_time ?: '')),
                    $this->travelAirportLabel($trip->arrival_airport),
                ];
            })->all(),
        ];
    }

    private function buildComplaintsReportPayload(Request $request): array
    {
        $query = Complaint::query()
            ->with([
                'user',
                'complaintType',
                'assignedTo',
                'maidNationality',
                'contract',
                'resolvedBy',
            ])
            ->orderByDesc('id');

        $this->applyReportBranchFilter($query, $request, 'complaints');
        $this->applyComplaintFilters($request, $query);

        $complaints = $query->get();

        return [
            'title' => 'تقرير الشكاوى',
            'filename_prefix' => 'complaints_report',
            'headings' => [
                'رقم التذكرة',
                'العميل',
                'اسم مقدم الشكوى',
                'جوال مقدم الشكوى',
                'الموضوع',
                'نوع الشكوى',
                'الأولوية',
                'الحالة',
                'الموظف المسؤول',
                'عقد الإيواء',
                'جواز العاملة',
                'جنسية العاملة',
                'تاريخ الإنشاء',
                'تاريخ الحل',
                'تم الحل بواسطة',
                'الرد',
            ],
            'rows' => $complaints->map(function (Complaint $complaint) {
                return [
                    '#' . str_pad((string) $complaint->id, 6, '0', STR_PAD_LEFT),
                    $complaint->user?->name ?: '-',
                    $complaint->complainant_name ?: '-',
                    $complaint->complainant_phone ?: $this->resolveUserPhone($complaint->user),
                    $complaint->subject ?: '-',
                    $complaint->complaintType?->name ?: '-',
                    $this->complaintPriorityLabel($complaint->priority),
                    $this->complaintStatusLabel($complaint->status),
                    $complaint->assignedTo?->name ?: '-',
                    $complaint->contract?->id ? '#' . $complaint->contract->id : '-',
                    $complaint->maid_passport ?: '-',
                    $this->modelLabel($complaint->maidNationality, ['sub_title', 'title', 'name']),
                    $this->formatDateValue($complaint->created_at, true),
                    $this->formatDateValue($complaint->resolved_at, true),
                    $complaint->resolvedBy?->name ?: '-',
                    $complaint->response ?: ($complaint->last_response ?: '-'),
                ];
            })->all(),
        ];
    }

    private function buildOfficesReportPayload(Request $request): array
    {
        $linkedContractsCountSubquery = $this->officeLinkedContractsCountSubquery();

        $query = Office::query()
            ->select('recruitment_offices.*')
            ->with(['nationality', 'account', 'officeManager.admin'])
            ->selectSub($linkedContractsCountSubquery, 'linked_contracts_count')
            ->selectSub($this->officePreviousSponsorshipsCountSubquery(), 'previous_sponsorships_count')
            ->selectSub($this->officeExternalDispatchesCountSubquery(), 'external_dispatches_count')
            ->withCount(['maids as linked_cvs_count']);

        $this->applyOfficeFilters($request, $query);

        $offices = $query->get()
            ->sortBy(fn (Office $office) => mb_strtolower((string) ($office->title ?? '')))
            ->values();

        return [
            'title' => 'تقرير المكاتب',
            'filename_prefix' => 'offices_report',
            'headings' => [
                'رقم المكتب',
                'اسم المكتب',
                'الجنسية',
                'الهاتف',
                'رقم الترخيص',
                'مدير المكتب',
                'هاتف المدير',
                'بريد المدير',
                'حالة المدير',
                'كود الحساب',
                'السير المرتبطة',
                'العقود المرتبطة',
                'إرساليات المكتب الخارجي',
                'قيمة الدولار',
                'تكلفة أخرى',
                'الحالة',
            ],
            'rows' => $offices->map(function (Office $office) {
                $manager = $office->officeManager?->admin;

                return [
                    $office->id,
                    $office->title ?: '-',
                    $this->modelLabel($office->nationality, ['sub_title', 'title', 'name']),
                    $office->phone_office ?: '-',
                    $office->license_number ?: '-',
                    $office->manager_name ?: ($manager?->name ?: '-'),
                    $office->manager_phone ?: '-',
                    $office->manager_email ?: '-',
                    $this->officeManagerStatusLabel($office),
                    $office->account?->code ?: '-',
                    (int) ($office->linked_cvs_count ?? 0),
                    (int) ($office->linked_contracts_count ?? 0),
                    (int) ($office->external_dispatches_count ?? 0),
                    $this->formatMoney($office->cost_of_dollar),
                    $this->formatMoney($office->cost_of_other),
                    $this->officeStatusLabel($office->status),
                ];
            })->all(),
        ];
    }

    private function buildFinancialRequestsReportPayload(Request $request): array
    {
        $query = FinancialRequest::query()
            ->with(['creator', 'approver', 'financeBond'])
            ->latest();

        if (!$this->canViewAllFinancialRequestsForReport()) {
            $query->where('created_by_id', auth('admin')->id());
        }

        $this->applyReportBranchFilter($query, $request, 'financial_requests');
        $this->applyFinancialRequestFilters($request, $query);

        $requests = $query->get();

        return [
            'title' => 'تقرير الطلبات المالية',
            'filename_prefix' => 'financial_requests_report',
            'headings' => [
                'رقم الطلب',
                'نوع الطلب',
                'الحالة',
                'التصعيد',
                'المبلغ',
                'تاريخ الطلب',
                'رقم العقد',
                'نوع العقد',
                'العميل',
                'هوية العميل',
                'العاملة',
                'جواز العاملة',
                'مقدم الطلب',
                'المعتمد بواسطة',
                'تاريخ السداد',
                'رقم السند',
                'الوصف',
            ],
            'rows' => $requests->map(function (FinancialRequest $requestItem) {
                return [
                    $requestItem->request_no ?: $requestItem->id,
                    $requestItem->type_label,
                    $requestItem->status_label,
                    $requestItem->priority_label,
                    $this->formatMoney($requestItem->amount),
                    $this->formatDateValue($requestItem->request_date),
                    $requestItem->contract_number ?: '-',
                    $requestItem->contract_type_label,
                    $requestItem->client_name ?: '-',
                    $requestItem->client_identity ?: '-',
                    $requestItem->maid_name ?: '-',
                    $requestItem->maid_passport ?: '-',
                    $requestItem->creator?->name ?: '-',
                    $requestItem->approver?->name ?: '-',
                    $this->formatDateValue($requestItem->paid_at, true),
                    $requestItem->financeBond?->code ?: ($requestItem->finance_bond_id ?: '-'),
                    $requestItem->description ?: '-',
                ];
            })->all(),
        ];
    }

    private function exportPdf(array $payload, Request $request)
    {
        $viewData = [
            'title' => $payload['title'],
            'headings' => $payload['headings'],
            'rows' => $payload['rows'],
            'exportedAt' => now()->format('Y-m-d H:i'),
            'recordCount' => count($payload['rows']),
        ];

        // Default to a browser print view because it preserves Arabic shaping
        // more reliably than Dompdf for these wide tabular reports.
        if ($request->query('driver') !== 'dompdf') {
            return response()->view(
                'admin.ingaz.reports.print',
                array_merge($viewData, [
                    'printLabel' => 'طباعة / حفظ PDF',
                ]),
                200,
                [
                    'Content-Type' => 'text/html; charset=UTF-8',
                    'Content-Disposition' => 'inline; filename="' . $this->buildFilename($payload['filename_prefix'], 'html') . '"',
                ]
            );
        }

        $filename = $this->buildFilename($payload['filename_prefix'], 'pdf');
        $pdf = PDF::loadView('admin.ingaz.reports.pdf', $viewData)->setPaper('a4', 'landscape');

        $pdf->setWarnings(false);
        $pdf->setOptions([
            'defaultFont' => 'dejavu sans',
            'isHtml5ParserEnabled' => true,
        ]);

        if ($request->boolean('download') || $request->query('type') === 'download') {
            return $pdf->download($filename);
        }

        return $pdf->stream($filename);
    }

    private function exportExcel(array $payload)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(mb_substr($payload['title'], 0, 31));
        $sheet->setRightToLeft(true);

        $headings = $payload['headings'];
        $rows = $payload['rows'];
        $lastColumn = Coordinate::stringFromColumnIndex(count($headings));

        $sheet->fromArray($headings, null, 'A1');

        $sheet->getStyle('A1:' . $lastColumn . '1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 11,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'EEF2F7'],
            ],
        ]);

        $rowNumber = 2;
        foreach ($rows as $row) {
            $sheet->fromArray($row, null, 'A' . $rowNumber);
            $rowNumber++;
        }

        foreach (range(1, count($headings)) as $index) {
            $column = Coordinate::stringFromColumnIndex($index);
            $sheet->getColumnDimension($column)->setWidth(22);
        }

        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:' . $lastColumn . '1');

        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'reports_xlsx_');
        $writer->save($tempFile);

        return response()
            ->download(
                $tempFile,
                $this->buildFilename($payload['filename_prefix'], 'xlsx'),
                ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
            )
            ->deleteFileAfterSend(true);
    }

    private function buildFilename(string $prefix, string $extension): string
    {
        return $prefix . '_' . now()->format('Y_m_d_H_i_s') . '.' . $extension;
    }

    private function applyMaidFilters(Request $request, $query): void
    {
        if ($request->filled('keyword')) {
            $keyword = trim((string) $request->keyword);
            $query->where(function ($innerQuery) use ($keyword) {
                $innerQuery->where('name', 'like', '%' . $keyword . '%')
                    ->orWhere('passport_num', 'like', '%' . $keyword . '%')
                    ->orWhere('cv_code', 'like', '%' . $keyword . '%');
            });
        }

        if ($request->filled('status')) {
            $statuses = collect((array) $request->status)
                ->filter(fn ($status) => filled($status))
                ->values();

            if ($statuses->isNotEmpty()) {
                $plainStatuses = $statuses->reject(fn ($status) => in_array($status, ['contracted', 'in_accommodation'], true))->all();
                $includeInAccommodation = $statuses->contains('in_accommodation');
                $includeContracted = $statuses->contains('contracted');
                $contractStatuses = $this->recruitmentContractStatuses();

                $query->where(function ($statusQuery) use ($plainStatuses, $includeInAccommodation, $includeContracted, $contractStatuses) {
                    $hasCondition = false;

                    if (!empty($plainStatuses)) {
                        $statusQuery->whereIn('status', $plainStatuses);
                        $hasCondition = true;
                    }

                    if ($includeInAccommodation) {
                        $method = $hasCondition ? 'orWhere' : 'where';
                        $statusQuery->{$method}('maid_accommodation_type', 2);
                        $hasCondition = true;
                    }

                    if ($includeContracted) {
                        $method = $hasCondition ? 'orWhereHas' : 'whereHas';
                        $statusQuery->{$method}('contracts', function ($contractQuery) use ($contractStatuses) {
                            $contractQuery->whereIn('status', $contractStatuses);
                        });
                    }
                });
            }
        }

        if ($request->filled('job_type_id')) {
            $jobTypeIds = (array) $request->job_type_id;
            $query->whereHas('jobTypes', function ($jobQuery) use ($jobTypeIds) {
                $jobQuery->whereIn('job_type_id', $jobTypeIds);
            });
        }

        if ($request->filled('request_type')) {
            $query->ofRequestType($request->request_type);
        }

        if ($request->filled('nationality_id')) {
            $query->where('nationality_id', $request->nationality_id);
        }

        if ($request->filled('office_id')) {
            $query->where('office_id', $request->office_id);
        }

        if ($request->filled('visibility')) {
            if ($request->visibility === 'visible') {
                $query->where('is_show', true);
            } elseif ($request->visibility === 'hidden') {
                $query->where('is_show', false);
            }
        }

        if ($request->filled('pinned')) {
            if ($request->pinned === 'only') {
                $query->where('is_pinned', true);
            } elseif ($request->pinned === 'unpinned') {
                $query->where('is_pinned', false);
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
    }

    private function applyOrderFilters(Request $request, $query): void
    {
        if ($request->filled('status')) {
            $query->whereIn('status', $this->getSelectedOrderStatus($request->status));
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        if ($request->filled('job_type')) {
            $query->where('job_type_id', $request->job_type);
        }

        if ($request->filled('admin_id')) {
            $query->whereResponsibleAdmin((int) $request->admin_id);
        }

        if ($request->filled('client_name')) {
            $query->whereHas('user', function ($userQuery) use ($request) {
                $userQuery->where('name', 'like', '%' . $request->client_name . '%');
            });
        }

        if ($request->filled('phone')) {
            $query->whereHas('user', function ($userQuery) use ($request) {
                $userQuery->where('phone', 'like', '%' . $request->phone . '%')
                    ->orWhere('phone_code', 'like', '%' . $request->phone . '%');
            });
        }

        if ($request->filled('passport_num')) {
            $query->whereHas('maid', function ($maidQuery) use ($request) {
                $maidQuery->where('passport_num', 'like', '%' . $request->passport_num . '%');
            });
        }

        if ($request->filled('request_type')) {
            $query->whereHas('maid', function ($maidQuery) use ($request) {
                $maidQuery->ofRequestType($request->request_type);
            });
        }

        if ($request->filled('nationality_id')) {
            $query->whereHas('maid', function ($maidQuery) use ($request) {
                $maidQuery->where('nationality_id', $request->nationality_id);
            });
        }

        if ($request->filled('office_name')) {
            $officeName = trim((string) $request->office_name);
            $query->whereHas('maid.office.translations', function ($officeQuery) use ($officeName) {
                $officeQuery->where('title', 'like', '%' . $officeName . '%')
                    ->where('locale', app()->getLocale());
            });
        }

        if ($request->filled('timer_status')) {
            $urgentThresholdHours = AutomationSettings::orderUrgentThresholdHours();

            $query->where(function ($timerQuery) use ($request, $urgentThresholdHours) {
                switch ($request->timer_status) {
                    case 'overdue':
                        $timerQuery->where('deadline_at', '<', now());
                        break;
                    case 'urgent':
                        $timerQuery->where('deadline_at', '>', now())
                            ->where('deadline_at', '<', now()->addHours($urgentThresholdHours));
                        break;
                    case 'normal':
                        $timerQuery->where('deadline_at', '>', now()->addHours($urgentThresholdHours));
                        break;
                }
            });
        }
    }

    private function applyRecruitmentContractFilters(Request $request, $query): void
    {
        $this->applyRecruitmentContractStatusFilter($query, $request->get('status'));

        if ($request->filled('warranty_status')) {
            $statuses = array_filter(array_map('trim', explode(',', (string) $request->warranty_status)));
            if (!empty($statuses)) {
                $today = Carbon::today()->toDateString();
                $query->where(function ($warrantyQuery) use ($statuses, $today) {
                    foreach ($statuses as $status) {
                        switch ($status) {
                            case 'active':
                                $warrantyQuery->orWhere(function ($innerQuery) use ($today) {
                                    $innerQuery->whereNotNull('warranty_end_date')
                                        ->whereDate('warranty_end_date', '>=', $today);
                                });
                                break;
                            case 'expired':
                                $warrantyQuery->orWhere(function ($innerQuery) use ($today) {
                                    $innerQuery->whereNotNull('warranty_end_date')
                                        ->whereDate('warranty_end_date', '<', $today);
                                });
                                break;
                            case 'not_applicable':
                                $warrantyQuery->orWhereNull('warranty_end_date');
                                break;
                            default:
                                $warrantyQuery->orWhere('warranty_status', $status);
                                break;
                        }
                    }
                });
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('client_phone')) {
            $query->whereHas('user', function ($userQuery) use ($request) {
                $userQuery->where('phone', 'like', '%' . $request->client_phone . '%');
            });
        }

        if ($request->filled('client_name')) {
            $query->whereHas('user', function ($userQuery) use ($request) {
                $userQuery->where('name', 'like', '%' . $request->client_name . '%');
            });
        }

        if ($request->filled('maid_passport')) {
            $query->whereHas('maid', function ($maidQuery) use ($request) {
                $maidQuery->where('passport_num', 'like', '%' . $request->maid_passport . '%');
            });
        }

        if ($request->filled('maid_name')) {
            $query->whereHas('maid', function ($maidQuery) use ($request) {
                $maidQuery->where('name', 'like', '%' . $request->maid_name . '%');
            });
        }

        if ($request->filled('request_type')) {
            $query->whereHas('maid', function ($maidQuery) use ($request) {
                $maidQuery->ofRequestType($request->request_type);
            });
        }

        if ($request->filled('contract_number')) {
            $query->where('id', 'like', '%' . $request->contract_number . '%');
        }

        if ($request->filled('nationality')) {
            $query->whereHas('maid.nationality.translations', function ($nationalityQuery) use ($request) {
                $nationalityQuery->where('sub_title', $request->nationality);
            });
        }

        if ($request->filled('religion')) {
            $query->whereHas('maid.religion.translations', function ($religionQuery) use ($request) {
                $religionQuery->where('title', $request->religion);
            });
        }

        if ($request->filled('office_id')) {
            $this->applyRecruitmentOfficeFilter($query, $request->office_id);
        }

        if ($request->filled('city')) {
            $query->whereHas('user', function ($userQuery) use ($request) {
                $userQuery->where('city', 'like', '%' . $request->city . '%');
            });
        }

        if ($request->filled('global_search')) {
            $this->applyRecruitmentGlobalSearchFilter($query, $request->global_search);
        }

        if ($request->filled('marketer_id')) {
            $query->where(function ($marketerQuery) use ($request) {
                $marketerQuery->where('marketer_id', $request->marketer_id)
                    ->orWhereHas('order.details', function ($detailsQuery) use ($request) {
                        $detailsQuery->where('admin_id', $request->marketer_id);
                    });
            });
        }
    }

    private function applyClientFilters(Request $request, $query): void
    {
        if ($request->filled('status')) {
            $status = (string) $request->get('status');

            if ($status === 'active') {
                $query->where('is_blocked', 0);
            } elseif ($status === 'blocked') {
                $query->where('is_blocked', 1);
            }
        }

        if ($request->filled('nationality_id')) {
            $query->where('nationality_id', $request->get('nationality_id'));
        }

        if ($request->filled('city')) {
            $query->where('city', 'like', '%' . trim((string) $request->get('city')) . '%');
        }

        if ($request->filled('has_orders')) {
            $hasOrders = (string) $request->get('has_orders');

            if ($hasOrders === 'with') {
                $query->whereHas('orders');
            } elseif ($hasOrders === 'without') {
                $query->doesntHave('orders');
            }
        }

        if ($request->filled('date_from')) {
            $from = Carbon::parse($request->get('date_from'))->startOfDay();
            $query->where('created_at', '>=', $from);
        }

        if ($request->filled('date_to')) {
            $to = Carbon::parse($request->get('date_to'))->endOfDay();
            $query->where('created_at', '<=', $to);
        }

        if ($request->filled('keyword')) {
            $keyword = trim((string) $request->get('keyword'));

            $query->where(function ($innerQuery) use ($keyword) {
                $innerQuery->where('name', 'like', '%' . $keyword . '%')
                    ->orWhere('email', 'like', '%' . $keyword . '%')
                    ->orWhere('phone', 'like', '%' . $keyword . '%')
                    ->orWhere('phone2', 'like', '%' . $keyword . '%')
                    ->orWhere('national_num', 'like', '%' . $keyword . '%');
            });
        }
    }

    private function applyRentContractFilters(Request $request, $query): void
    {
        $status = $request->input('status_filter', $request->input('status'));
        if (filled($status)) {
            if ($status === 'transferred') {
                $query->where('status', 'completed')->where('closure_type', 'transferred');
            } elseif ($status === 'available') {
                $query->where('status', 'completed')->where('closure_type', 'available');
            } else {
                $query->where('status', $status);
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('contract_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('contract_date', '<=', $request->date_to);
        }

        if ($request->filled('marketer_id')) {
            $query->where('marketer_id', $request->marketer_id);
        }

        if ($request->filled('client_phone')) {
            $phone = (string) $request->client_phone;
            $query->whereHas('user', function ($userQuery) use ($phone) {
                $userQuery->where('phone', 'like', '%' . $phone . '%')
                    ->orWhereRaw("CONCAT(COALESCE(phone_code, ''), COALESCE(phone, '')) LIKE ?", ['%' . $phone . '%']);
            });
        }

        if ($request->filled('client_name')) {
            $query->whereHas('user', function ($userQuery) use ($request) {
                $userQuery->where('name', 'like', '%' . $request->client_name . '%');
            });
        }

        if ($request->filled('maid_passport')) {
            $query->whereHas('maid', function ($maidQuery) use ($request) {
                $maidQuery->where('passport_num', 'like', '%' . $request->maid_passport . '%');
            });
        }

        if ($request->filled('maid_name')) {
            $query->whereHas('maid', function ($maidQuery) use ($request) {
                $maidQuery->where('name', 'like', '%' . $request->maid_name . '%');
            });
        }

        if ($request->filled('contract_number')) {
            $query->where('contract_number', 'like', '%' . $request->contract_number . '%');
        }

        if ($request->filled('request_type')) {
            $query->whereHas('maid', function ($maidQuery) use ($request) {
                $maidQuery->ofRequestType($request->request_type);
            });
        }

        if ($request->filled('warranty_status')) {
            $query->where('warranty_status', $request->warranty_status);
        }

        if ($request->filled('extension_filter')) {
            if ($request->extension_filter === 'extended') {
                $query->whereHas('extensions');
            } elseif ($request->extension_filter === 'not_extended') {
                $query->whereDoesntHave('extensions');
            }
        }
    }

    private function applyShelterContractFilters(Request $request, $query): void
    {
        if ($request->filled('contract_number')) {
            $query->where('id', 'like', '%' . $request->contract_number . '%');
        }

        if ($request->filled('nationality')) {
            $query->whereHas('maid.nationality', function ($nationalityQuery) use ($request) {
                $nationalityQuery->where('id', $request->nationality);
            });
        }

        if ($request->filled('shelter')) {
            $query->whereHas('info', function ($infoQuery) use ($request) {
                $infoQuery->where('shelter_id', $request->shelter);
            });
        }

        if ($request->filled('receipt_source')) {
            $query->whereHas('info', function ($infoQuery) use ($request) {
                $infoQuery->where('receipt_source', $request->receipt_source);
            });
        }

        if ($request->filled('status')) {
            $today = now();
            $guaranteePeriodDays = AutomationSettings::warrantyPeriodDays();

            if ($request->status === 'within_guarantee') {
                $query->whereHas('info', function ($infoQuery) use ($today, $guaranteePeriodDays) {
                    $infoQuery->where(function ($dateQuery) use ($today, $guaranteePeriodDays) {
                        $dateQuery->where(function ($innerQuery) use ($today, $guaranteePeriodDays) {
                            $innerQuery->whereNotNull('arrival_date')
                                ->whereDate('arrival_date', '>=', $today->copy()->subDays($guaranteePeriodDays));
                        })->orWhere(function ($innerQuery) use ($today, $guaranteePeriodDays) {
                            $innerQuery->whereNotNull('visa_milad_date')
                                ->whereDate('visa_milad_date', '>=', $today->copy()->subDays($guaranteePeriodDays));
                        })->orWhere(function ($innerQuery) use ($today, $guaranteePeriodDays) {
                            $innerQuery->whereNotNull('contract_date')
                                ->whereDate('contract_date', '>=', $today->copy()->subDays($guaranteePeriodDays));
                        })->orWhere(function ($innerQuery) use ($today, $guaranteePeriodDays) {
                            $innerQuery->whereNotNull('ingaz_from')
                                ->whereDate('ingaz_from', '>=', $today->copy()->subDays($guaranteePeriodDays));
                        });
                    });
                });
            } elseif ($request->status === 'expired_guarantee') {
                $query->whereHas('info', function ($infoQuery) use ($today, $guaranteePeriodDays) {
                    $infoQuery->where(function ($dateQuery) use ($today, $guaranteePeriodDays) {
                        $dateQuery->where(function ($innerQuery) use ($today, $guaranteePeriodDays) {
                            $innerQuery->whereNotNull('arrival_date')
                                ->whereDate('arrival_date', '<', $today->copy()->subDays($guaranteePeriodDays));
                        })->orWhere(function ($innerQuery) use ($today, $guaranteePeriodDays) {
                            $innerQuery->whereNotNull('visa_milad_date')
                                ->whereDate('visa_milad_date', '<', $today->copy()->subDays($guaranteePeriodDays));
                        })->orWhere(function ($innerQuery) use ($today, $guaranteePeriodDays) {
                            $innerQuery->whereNotNull('contract_date')
                                ->whereDate('contract_date', '<', $today->copy()->subDays($guaranteePeriodDays));
                        })->orWhere(function ($innerQuery) use ($today, $guaranteePeriodDays) {
                            $innerQuery->whereNotNull('ingaz_from')
                                ->whereDate('ingaz_from', '<', $today->copy()->subDays($guaranteePeriodDays));
                        });
                    });
                });
            }
        }

        if ($request->filled('work_willingness')) {
            $query->whereHas('info', function ($infoQuery) use ($request) {
                $infoQuery->where('wants_to_work', $request->work_willingness);
            });
        }

        if ($request->filled('arrival_date')) {
            $query->whereHas('info', function ($infoQuery) use ($request) {
                $infoQuery->where(function ($dateQuery) use ($request) {
                    $dateQuery->whereDate('arrival_date', $request->arrival_date)
                        ->orWhereDate('visa_milad_date', $request->arrival_date)
                        ->orWhereDate('contract_date', $request->arrival_date)
                        ->orWhereDate('ingaz_from', $request->arrival_date);
                });
            });
        }

        if ($request->filled('return_date')) {
            $query->whereHas('info', function ($infoQuery) use ($request) {
                $infoQuery->whereDate('maid_date_back', $request->return_date);
            });
        }

        if ($request->filled('instant_receipt')) {
            if ($request->instant_receipt === 'yes') {
                $query->whereHas('info', function ($infoQuery) {
                    $infoQuery->where('wants_to_work', 'yes')
                        ->where('is_available_for_instant', true);
                });
            } elseif ($request->instant_receipt === 'no') {
                $query->whereHas('info', function ($infoQuery) {
                    $infoQuery->where(function ($innerQuery) {
                        $innerQuery->where('wants_to_work', '!=', 'yes')
                            ->orWhereNull('wants_to_work')
                            ->orWhere('is_available_for_instant', false)
                            ->orWhereNull('is_available_for_instant');
                    });
                });
            }
        }

        if ($request->filled('quick_tag') && $this->supportsQuickTags()) {
            $quickTag = trim((string) $request->quick_tag);
            $query->whereHas('info', function ($infoQuery) use ($quickTag) {
                $infoQuery->whereJsonContains('shelter_quick_tags', $quickTag);
            });
        }

        if ($request->filled('service_category')) {
            if ($request->service_category === 'rental') {
                $query->whereHas('maid.jobTypes.translations', function ($translationQuery) {
                    $this->applyJobTypeKeywordQuery($translationQuery, $this->getRentalServiceKeywords());
                });
            } elseif ($request->service_category === 'transfer') {
                $query->whereHas('maid.jobTypes.translations', function ($translationQuery) {
                    $this->applyJobTypeKeywordQuery($translationQuery, $this->getTransferServiceKeywords());
                });
            } elseif ($request->service_category === 'recruitment') {
                $query->where(function ($categoryQuery) {
                    $categoryQuery->whereDoesntHave('maid.jobTypes')
                        ->orWhereDoesntHave('maid.jobTypes.translations', function ($translationQuery) {
                            $translationQuery->where(function ($innerQuery) {
                                $this->applyJobTypeKeywordQuery($innerQuery, [
                                    ...$this->getRentalServiceKeywords(),
                                    ...$this->getTransferServiceKeywords(),
                                ]);
                            });
                        });
                });
            }
        }

        if ($request->filled('request_type')) {
            $query->whereHas('maid', function ($maidQuery) use ($request) {
                $maidQuery->ofRequestType($request->request_type);
            });
        }
    }

    private function applySponsorshipTransferFilters(Request $request, $query): void
    {
        if ($request->filled('status')) {
            if ($request->status === 'trial_period') {
                $query->whereIn('status', ['trial', 'trial_started']);
            } else {
                $query->where('status', $request->status);
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('contract_number')) {
            $query->whereHas('contract', function ($contractQuery) use ($request) {
                $contractQuery->where('id', 'like', '%' . $request->contract_number . '%');
            });
        }

        if ($request->filled('old_sponsor')) {
            $oldSponsor = trim((string) $request->old_sponsor);
            $query->whereHas('oldSponsor', function ($sponsorQuery) use ($oldSponsor) {
                $sponsorQuery->where('name', 'like', '%' . $oldSponsor . '%')
                    ->orWhere('phone', 'like', '%' . $oldSponsor . '%');
            });
        }

        if ($request->filled('new_sponsor')) {
            $newSponsor = trim((string) $request->new_sponsor);
            $query->whereHas('newSponsor', function ($sponsorQuery) use ($newSponsor) {
                $sponsorQuery->where('name', 'like', '%' . $newSponsor . '%')
                    ->orWhere('phone', 'like', '%' . $newSponsor . '%');
            });
        }

        if ($request->filled('trial_days')) {
            $query->where('trial_days', (int) $request->trial_days);
        }

        if ($request->filled('amount')) {
            if ($request->amount === '15000+') {
                $query->where('contract_value', '>=', 15000);
                return;
            }

            $amountRange = explode('-', (string) $request->amount);
            if (count($amountRange) === 2) {
                $query->whereBetween('contract_value', [(float) $amountRange[0], (float) $amountRange[1]]);
            }
        }
    }

    private function applyDeportationTravelFilters(Request $request, $query): void
    {
        if (!$request->filled('status') || $request->status === '') {
            $query->whereIn('status', ['new', 'active', 'scheduled', 'in_progress', 'pending', 'confirmed']);
        } elseif ($request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('contract_number')) {
            $contractNumber = (int) $request->contract_number;
            $query->where(function ($contractQuery) use ($contractNumber) {
                $contractQuery->whereHas('order', function ($orderQuery) use ($contractNumber) {
                    $orderQuery->where('id', $contractNumber);
                })->orWhere('order_id', $contractNumber);
            });
        }

        if ($request->filled('maid_name')) {
            $maidName = trim((string) $request->maid_name);
            $query->whereHas('maid', function ($maidQuery) use ($maidName) {
                $maidQuery->where('name', 'like', '%' . $maidName . '%');
            });
        }

        if ($request->filled('travel_date')) {
            $query->whereDate('arrival_date', $request->travel_date);
        }
    }

    private function applyTravelTripFilters(Request $request, $query): void
    {
        if (!$request->filled('status') || $request->status === '') {
            $query->whereIn('status', ['new', 'active', 'scheduled', 'in_progress', 'pending', 'confirmed']);
        } elseif ($request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($searchQuery) use ($search) {
                $searchQuery->where('travel_type', 'like', '%' . $search . '%')
                    ->orWhere('travel_with', 'like', '%' . $search . '%')
                    ->orWhere('notes', 'like', '%' . $search . '%')
                    ->orWhere('status', 'like', '%' . $search . '%')
                    ->orWhere('arrival_airport', 'like', '%' . $search . '%')
                    ->orWhere('arrival_date', 'like', '%' . $search . '%')
                    ->orWhere('arrival_time', 'like', '%' . $search . '%')
                    ->orWhere('flight_company', 'like', '%' . $search . '%')
                    ->orWhere('flight_number', 'like', '%' . $search . '%')
                    ->orWhere('order_id', 'like', '%' . $search . '%')
                    ->orWhere('created_at', 'like', '%' . $search . '%')
                    ->orWhereExists(function ($activityQuery) use ($search) {
                        $activityTable = config('activitylog.table_name', 'activity_log');
                        $activityQuery->selectRaw('1')
                            ->from($activityTable)
                            ->join('admins', 'admins.id', '=', $activityTable . '.causer_id')
                            ->whereColumn($activityTable . '.subject_id', 'travel_histories.id')
                            ->where($activityTable . '.subject_type', TravelHistory::class)
                            ->where($activityTable . '.causer_type', Admin::class)
                            ->where($activityTable . '.event', 'created')
                            ->where('admins.name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('maid', function ($maidQuery) use ($search) {
                        $maidQuery->where('passport_num', 'like', '%' . $search . '%')
                            ->orWhere('name', 'like', '%' . $search . '%');
                    });
            });
        }

        if ($request->filled('travel_type')) {
            $query->where('travel_type', $request->travel_type);
        }

        if ($request->filled('travel_with')) {
            $query->where('travel_with', $request->travel_with);
        }

        if ($request->filled('arrival_airport')) {
            $query->whereIn('arrival_airport', TravelAirports::valuesFor($request->arrival_airport));
        }

        if ($request->filled('travel_date')) {
            $query->whereDate('arrival_date', $request->travel_date);
        }

        if ($request->filled('created_date')) {
            $query->whereDate('created_at', $request->created_date);
        }

        if ($request->filled('created_by')) {
            $activityTable = config('activitylog.table_name', 'activity_log');
            $query->whereExists(function ($activityQuery) use ($activityTable, $request) {
                $activityQuery->selectRaw('1')
                    ->from($activityTable)
                    ->whereColumn($activityTable . '.subject_id', 'travel_histories.id')
                    ->where($activityTable . '.subject_type', TravelHistory::class)
                    ->where($activityTable . '.causer_type', Admin::class)
                    ->where($activityTable . '.event', 'created')
                    ->where($activityTable . '.causer_id', $request->created_by);
            });
        }

        if ($request->filled('flight_company')) {
            $query->where('flight_company', 'like', '%' . $request->flight_company . '%');
        }

        if ($request->filled('flight_number')) {
            $query->where('flight_number', 'like', '%' . $request->flight_number . '%');
        }

        if ($request->filled('order_id')) {
            $query->where('order_id', 'like', '%' . $request->order_id . '%');
        }
    }

    private function applyComplaintFilters(Request $request, $query): void
    {
        if ($request->filled('ticket_number')) {
            $ticketNumber = ltrim((string) $request->ticket_number, '#0');
            $ticketNumber = $ticketNumber === '' ? 0 : $ticketNumber;
            $query->where('id', 'like', '%' . $ticketNumber . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('type')) {
            $query->where('complaint_type_id', $request->type);
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        if ($request->filled('keyword')) {
            $keyword = trim((string) $request->keyword);
            $query->where(function ($keywordQuery) use ($keyword) {
                $keywordQuery->where('subject', 'like', '%' . $keyword . '%')
                    ->orWhere('message', 'like', '%' . $keyword . '%')
                    ->orWhere('details', 'like', '%' . $keyword . '%')
                    ->orWhere('complainant_name', 'like', '%' . $keyword . '%')
                    ->orWhere('complainant_phone', 'like', '%' . $keyword . '%')
                    ->orWhere('maid_passport', 'like', '%' . $keyword . '%')
                    ->orWhereHas('user', function ($userQuery) use ($keyword) {
                        $userQuery->where('name', 'like', '%' . $keyword . '%')
                            ->orWhere('phone', 'like', '%' . $keyword . '%');
                    })
                    ->orWhereHas('complaintType', function ($typeQuery) use ($keyword) {
                        $typeQuery->where('name', 'like', '%' . $keyword . '%');
                    })
                    ->orWhereHas('assignedTo', function ($adminQuery) use ($keyword) {
                        $adminQuery->where('name', 'like', '%' . $keyword . '%');
                    });
            });
        }

        if ($request->filled('period')) {
            match ($request->period) {
                'today' => $query->whereDate('created_at', today()),
                'yesterday' => $query->whereDate('created_at', today()->subDay()),
                'week' => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]),
                'month' => $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]),
                'custom' => null,
                default => null,
            };
        }

        if ($request->input('period') === 'custom' || $request->filled('date_from') || $request->filled('date_to')) {
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }
        }
    }

    private function applyOfficeFilters(Request $request, $query): void
    {
        if ($request->filled('office_name')) {
            $officeName = trim((string) $request->office_name);
            $query->whereHas('translations', function ($translationQuery) use ($officeName) {
                $translationQuery->where('title', 'like', '%' . $officeName . '%');
            });
        }

        if ($request->filled('nationality_id')) {
            $query->where('nationality_id', $request->nationality_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('cost_state')) {
            if ($request->cost_state === 'with_cost') {
                $query->whereNotNull('cost_of_dollar')->where('cost_of_dollar', '!=', '');
            } elseif ($request->cost_state === 'without_cost') {
                $query->where(function ($costQuery) {
                    $costQuery->whereNull('cost_of_dollar')
                        ->orWhere('cost_of_dollar', '');
                });
            }
        }

        if ($request->filled('linked_state')) {
            $linkedContractsCountSubquery = $this->officeLinkedContractsCountSubquery();
            $linkedContractsSql = '(' . $linkedContractsCountSubquery->toSql() . ')';
            $linkedContractsBindings = $linkedContractsCountSubquery->getBindings();

            if ($request->linked_state === 'with_links') {
                $query->where(function ($linkedQuery) use ($linkedContractsSql, $linkedContractsBindings) {
                    $linkedQuery->has('maids')
                        ->orWhereRaw($linkedContractsSql . ' > 0', $linkedContractsBindings);
                });
            } elseif ($request->linked_state === 'without_links') {
                $query->doesntHave('maids')
                    ->whereRaw($linkedContractsSql . ' = 0', $linkedContractsBindings);
            }
        }
    }

    private function applyFinancialRequestFilters(Request $request, $query): void
    {
        if ($request->input('scope') === 'incomplete') {
            $query->whereIn('status', [
                FinancialRequest::STATUS_PENDING_REVIEW,
                FinancialRequest::STATUS_IN_PROGRESS,
            ]);
        } elseif ($request->input('scope') === 'paid') {
            $query->where('status', FinancialRequest::STATUS_PAID);
        }

        if ($request->filled('q')) {
            $term = trim((string) $request->input('q'));
            $query->where(function ($searchQuery) use ($term) {
                $searchQuery->where('request_no', 'like', '%' . $term . '%')
                    ->orWhere('contract_number', 'like', '%' . $term . '%')
                    ->orWhere('client_identity', 'like', '%' . $term . '%')
                    ->orWhere('client_name', 'like', '%' . $term . '%')
                    ->orWhere('maid_name', 'like', '%' . $term . '%')
                    ->orWhere('maid_passport', 'like', '%' . $term . '%');
            });
        }

        foreach (['type', 'status', 'priority', 'created_by_id'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }

        if ($request->filled('date')) {
            $query->whereDate('request_date', $request->input('date'));
        }
    }

    private function applyReportBranchFilter($query, Request $request, string $table, ?string $qualifiedColumn = null): void
    {
        app(BranchAccessService::class)->applyToQuery($query, $request, $table, $qualifiedColumn);
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

    private function getRentalJobTypeId(): ?int
    {
        if ($this->rentalJobTypeId !== null) {
            return $this->rentalJobTypeId;
        }

        $this->rentalJobTypeId = $this->findJobTypeIdByKeywords(['ايجار', 'إيجار', 'تأجير', 'rent']) ?? 2;

        return $this->rentalJobTypeId;
    }

    private function findJobTypeIdByKeywords(array $keywords): ?int
    {
        $jobType = JobType::with('translations')->get()->first(function ($type) use ($keywords) {
            $title = $this->normalizeArabicText($type->title ?? '');

            foreach ($keywords as $keyword) {
                if (str_contains($title, $this->normalizeArabicText($keyword))) {
                    return true;
                }
            }

            return false;
        });

        return $jobType?->id;
    }

    private function normalizeArabicText(?string $text): string
    {
        if ($text === null) {
            return '';
        }

        $normalized = mb_strtolower($text, 'UTF-8');

        return trim(str_replace(['أ', 'إ', 'آ', 'ى', 'ؤ', 'ئ', 'ة'], ['ا', 'ا', 'ا', 'ي', 'و', 'ي', 'ه'], $normalized));
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

    private function getSelectedOrderStatus(string $status): array
    {
        return match ($status) {
            'current' => [OrderEnum::New->value, OrderEnum::UnderProcess->value],
            'ended', 'completed' => [
                OrderEnum::Completed->value,
                OrderEnum::Done->value,
                OrderEnum::Contracted->value,
                OrderEnum::ContractedWithoutContract->value,
            ],
            'not_completed' => [
                OrderEnum::Refused->value,
                OrderEnum::Return->value,
                OrderEnum::CanceledBySystem->value,
            ],
            default => OrderEnum::tryFrom($status)
                ? [$status]
                : array_map(fn ($case) => $case->value, OrderEnum::cases()),
        };
    }

    private function applyRecruitmentContractStatusFilter($query, ?string $statusFilter): void
    {
        if (!filled($statusFilter)) {
            return;
        }

        if ($statusFilter === 'current') {
            $query->whereIn('status', [
                ContractStatusEnum::PendingMaidSelection->value,
                ContractStatusEnum::New->value,
                ContractStatusEnum::Msaned->value,
                ContractStatusEnum::Tafwed->value,
                ContractStatusEnum::Taffez->value,
                ContractStatusEnum::Tazker->value,
            ]);
            return;
        }

        if ($statusFilter === 'completed_stage') {
            $query->whereIn('status', [
                ContractStatusEnum::Arrival->value,
                ContractStatusEnum::Completed->value,
                ContractStatusEnum::Refunded->value,
            ]);
            return;
        }

        if ($statusFilter === 'arrival_today') {
            $query->whereIn('status', [
                ContractStatusEnum::Tazker->value,
                ContractStatusEnum::Arrival->value,
            ])->whereRaw(
                "DATE((SELECT th.arrival_date FROM travel_histories as th WHERE th.order_id = contracts.id AND th.deleted_at IS NULL ORDER BY th.id DESC LIMIT 1)) = ?",
                [Carbon::today()->toDateString()]
            );
            return;
        }

        if ($statusFilter === 'delayed_arrival') {
            $query->whereIn('status', [
                ContractStatusEnum::PendingMaidSelection->value,
                ContractStatusEnum::New->value,
                ContractStatusEnum::Msaned->value,
                ContractStatusEnum::Tafwed->value,
                ContractStatusEnum::Taffez->value,
            ])->whereRaw(
                "DATE(COALESCE(NULLIF((SELECT ci.contract_date FROM contract_infos as ci WHERE ci.contract_id = contracts.id LIMIT 1), '0000-00-00'), NULLIF(contracts.contract_date, '0000-00-00'))) < ?",
                [Carbon::today()->subDays(60)->toDateString()]
            );
            return;
        }

        if ($statusFilter === 'expired_over_2y') {
            $query->whereRaw(
                "DATE(COALESCE(NULLIF((SELECT ci.contract_date FROM contract_infos as ci WHERE ci.contract_id = contracts.id LIMIT 1), '0000-00-00'), NULLIF(contracts.contract_date, '0000-00-00'))) < ?",
                [Carbon::today()->subYears(2)->toDateString()]
            );
            return;
        }

        $query->where('status', $statusFilter);
    }

    private function applyRecruitmentGlobalSearchFilter($query, string $search): void
    {
        $search = trim($search);
        if ($search === '') {
            return;
        }

        $likeSearch = '%' . $search . '%';

        $query->where(function ($innerQuery) use ($likeSearch) {
            $innerQuery->where('id', 'like', $likeSearch)
                ->orWhere('order_id', 'like', $likeSearch)
                ->orWhere('status', 'like', $likeSearch)
                ->orWhereHas('user', function ($userQuery) use ($likeSearch) {
                    $userQuery->where('name', 'like', $likeSearch)
                        ->orWhere('phone_code', 'like', $likeSearch)
                        ->orWhere('phone', 'like', $likeSearch)
                        ->orWhereRaw("CONCAT(COALESCE(phone_code, ''), COALESCE(phone, '')) LIKE ?", [$likeSearch])
                        ->orWhere('national_num', 'like', $likeSearch)
                        ->orWhere('email', 'like', $likeSearch)
                        ->orWhere('city', 'like', $likeSearch);
                })
                ->orWhereHas('maid', function ($maidQuery) use ($likeSearch) {
                    $maidQuery->where('name', 'like', $likeSearch)
                        ->orWhere('passport_num', 'like', $likeSearch);
                })
                ->orWhereHas('info', function ($infoQuery) use ($likeSearch) {
                    $infoQuery->where('visa_number', 'like', $likeSearch)
                        ->orWhere('msaned_number', 'like', $likeSearch)
                        ->orWhere('tafwed_number', 'like', $likeSearch)
                        ->orWhere('visa_milad_date', 'like', $likeSearch)
                        ->orWhere('contract_date', 'like', $likeSearch)
                        ->orWhere('arrival_date', 'like', $likeSearch)
                        ->orWhere('ingaz_from', 'like', $likeSearch)
                        ->orWhere('ingaz_to', 'like', $likeSearch)
                        ->orWhere('contract_value', 'like', $likeSearch)
                        ->orWhere('monthly_cost', 'like', $likeSearch);
                })
                ->orWhereHas('order', function ($orderQuery) use ($likeSearch) {
                    $orderQuery->where('id', 'like', $likeSearch)
                        ->orWhere('order_code', 'like', $likeSearch);
                });
        });
    }

    private function applyRecruitmentOfficeFilter($query, $officeId): void
    {
        $query->where(function ($officeQuery) use ($officeId) {
            $officeQuery->whereHas('maid', function ($maidQuery) use ($officeId) {
                $maidQuery->where('office_id', $officeId);
            })->orWhereHas('info', function ($infoQuery) use ($officeId) {
                $infoQuery->where('manual_maid_office_id', $officeId);
            });
        });
    }

    private function determineShelterServiceCategory(?Maid $maid): string
    {
        if (!$maid) {
            return 'recruitment';
        }

        $jobType = $maid->relationLoaded('jobTypes')
            ? $maid->jobTypes->first()
            : $maid->jobTypes()->first();

        $title = $this->normalizeShelterCategoryTitle($jobType?->title);

        if ($title === '') {
            return 'recruitment';
        }

        if (str_contains($title, 'ايجار') || str_contains($title, 'تاجير') || str_contains($title, 'rent')) {
            return 'rental';
        }

        if (str_contains($title, 'استلام') || str_contains($title, 'instant') || str_contains($title, 'transfer') || str_contains($title, 'نقل')) {
            return 'transfer';
        }

        return 'recruitment';
    }

    private function normalizeShelterCategoryTitle(?string $title): string
    {
        if (!$title) {
            return '';
        }

        return preg_replace('/\s+/', ' ', str_replace(['أ', 'إ', 'آ'], 'ا', mb_strtolower(trim($title))));
    }

    private function applyJobTypeKeywordQuery($query, array $keywords): void
    {
        $query->where(function ($innerQuery) use ($keywords) {
            foreach (array_values(array_unique($keywords)) as $index => $keyword) {
                $method = $index === 0 ? 'where' : 'orWhere';
                $innerQuery->{$method}('title', 'like', '%' . $keyword . '%');
            }
        });
    }

    private function getRentalServiceKeywords(): array
    {
        return ['ايجار', 'إيجار', 'تاجير', 'تأجير', 'rent', 'rental'];
    }

    private function getTransferServiceKeywords(): array
    {
        return ['استلام', 'إستلام', 'instant', 'transfer', 'نقل'];
    }

    private function supportsQuickTags(): bool
    {
        if ($this->hasShelterQuickTags === null) {
            $this->hasShelterQuickTags = Schema::hasColumn('contract_infos', 'shelter_quick_tags');
        }

        return $this->hasShelterQuickTags;
    }

    private function extractQuickTags($value): array
    {
        if (!$this->supportsQuickTags()) {
            return [];
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $value = $decoded;
            }
        }

        $values = is_array($value) ? $value : preg_split('/[\r\n,،|]+/u', (string) $value);

        return collect($values)
            ->map(fn ($tag) => trim((string) $tag))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function resolveShelterArrivalDate(Contract $contract): ?Carbon
    {
        $candidate = $contract->info?->arrival_date
            ?? $contract->travelHistory?->arrival_date
            ?? $contract->info?->visa_milad_date
            ?? $contract->info?->contract_date
            ?? $contract->info?->ingaz_from;

        return $this->parseCarbonValue($candidate);
    }

    private function resolveShelterEntryDate(Contract $contract): ?Carbon
    {
        $candidate = $contract->latestShelterLog?->created_at
            ?? $contract->info?->maid_date_back
            ?? $contract->info?->created_at
            ?? $contract->created_at;

        return $this->parseCarbonValue($candidate);
    }

    private function resolveShelterGuaranteeData(Contract $contract): array
    {
        $arrivalDate = $this->resolveShelterArrivalDate($contract);
        if (!$arrivalDate) {
            return [
                'status' => 'unknown',
                'label' => 'غير محدد',
            ];
        }

        $guaranteeEndDate = $arrivalDate->copy()->addDays(AutomationSettings::warrantyPeriodDays());
        $today = now();

        if ($today->lte($guaranteeEndDate)) {
            return [
                'status' => 'within',
                'label' => 'ضمن الضمان (' . $today->diffInDays($guaranteeEndDate) . ' يوم)',
            ];
        }

        return [
            'status' => 'expired',
            'label' => 'منتهي الضمان (' . $guaranteeEndDate->diffInDays($today) . ' يوم)',
        ];
    }

    private function resolveShelterNationalityLabel(Contract $contract): string
    {
        return $this->modelLabel($contract->maid?->nationality, ['sub_title', 'title', 'name']);
    }

    private function resolveShelterPlaceLabel(Contract $contract): string
    {
        return $this->modelLabel($contract->info?->shelter, ['title', 'name']);
    }

    private function resolveShelterReceiptSourceLabel(?string $value): string
    {
        if (!$value) {
            return '-';
        }

        $translationKey = 'shelter_worker_statuses.received_by.' . $value;
        $translated = __($translationKey);

        return $translated === $translationKey ? $value : $translated;
    }

    private function formatShelterWorkWillingnessLabel(?string $value): string
    {
        return match ($value) {
            'yes' => 'ترغب بالعمل',
            'no' => 'لا ترغب بالعمل',
            default => 'غير محدد',
        };
    }

    private function formatShelterInstantReceiptLabel(Contract $contract): string
    {
        if (($contract->info?->wants_to_work ?? null) !== 'yes') {
            return 'غير مطبق';
        }

        return $contract->info?->is_available_for_instant ? 'متاحة' : 'غير متاحة';
    }

    private function resolveMaidExportStatus(Maid $maid): string
    {
        if ($maid->status === 'deported') {
            return 'مرحلة الترحيل';
        }

        if ((int) $maid->maid_accommodation_type === 2) {
            return 'داخل الإيواء';
        }

        if ($maid->contracts->whereIn('status', $this->recruitmentContractStatuses())->isNotEmpty()) {
            return 'تم التعاقد';
        }

        if ($maid->orders->whereIn('status', [
            OrderEnum::New->value,
            OrderEnum::UnderProcess->value,
            OrderEnum::Contracted->value,
            OrderEnum::ContractedWithoutContract->value,
        ])->isNotEmpty() && $maid->status === 'available') {
            return 'محجوزة';
        }

        return match ($maid->status) {
            'available' => 'متاحة',
            'reserved' => 'محجوزة',
            'backout' => 'باك أوت',
            'suspended' => 'معلقة',
            default => $maid->status ?: '-',
        };
    }

    private function resolveOrderStatusLabel(?string $status): string
    {
        if (!$status) {
            return '-';
        }

        $value = getEnumValue('orders_statuses', $status);

        return filled($value) ? $value : $status;
    }

    private function resolveContractStatusLabel(?string $status): string
    {
        if (!$status) {
            return '-';
        }

        return getContractStatus($status) ?: $status;
    }

    private function resolveRecruitmentWarrantyLabel(Contract $contract): string
    {
        if (!$contract->warranty_end_date) {
            return 'غير مطبق';
        }

        $endDate = $this->parseCarbonValue($contract->warranty_end_date);
        if (!$endDate) {
            return 'غير محدد';
        }

        return $endDate->isFuture() || $endDate->isToday()
            ? 'نشط حتى ' . $endDate->format('Y-m-d')
            : 'منتهي في ' . $endDate->format('Y-m-d');
    }

    private function resolveRecruitmentContractMaidName(Contract $contract): string
    {
        return $contract->maid?->name
            ?: ($contract->info?->manual_maid_name ?: '-');
    }

    private function resolveRecruitmentContractPassport(Contract $contract): string
    {
        return $contract->maid?->passport_num
            ?: ($contract->info?->manual_maid_passport_num ?: '-');
    }

    private function resolveRecruitmentContractOffice(Contract $contract): string
    {
        if ($contract->maid?->office) {
            return $this->modelLabel($contract->maid->office, ['title', 'name']);
        }

        if ($contract->info?->manualMaidOffice) {
            return $this->modelLabel($contract->info->manualMaidOffice, ['title', 'name']);
        }

        return '-';
    }

    private function resolveRentStatusLabel(RentalContract $contract): string
    {
        if ($contract->status === 'completed' && $contract->closure_type === 'transferred') {
            return 'تم النقل';
        }

        if ($contract->status === 'completed' && $contract->closure_type === 'available') {
            return 'مغلقة - متاحة';
        }

        return match ($contract->status) {
            'new' => 'جديد',
            'waiting_contract_send' => 'انتظار إرسال العقد',
            'contract_sent' => 'تم إرسال العقد',
            'contract_signed' => 'تم توقيع العقد',
            'maid_delivered' => 'تم تسليم العاملة',
            'active' => 'نشط',
            'completed' => 'مكتمل',
            'cancelled' => 'ملغي',
            'suspended' => 'معلق',
            default => $contract->status ?: '-',
        };
    }

    private function resolveRentWarrantyStatusLabel(?string $status): string
    {
        return match ($status) {
            'active' => 'نشط',
            'expired' => 'منتهي',
            'not_applicable' => 'غير مطبق',
            default => '-',
        };
    }

    private function serviceCategoryLabel(string $category): string
    {
        return match ($category) {
            'rental' => 'تأجير',
            'transfer' => 'نقل خدمات',
            default => 'استقدام',
        };
    }

    private function buildGroupedTravelTripsCollection(Collection $trips): Collection
    {
        $tripGroups = $trips->groupBy(fn (TravelHistory $trip) => $this->resolveTravelTripGroupKey($trip));
        $primaryTrips = $tripGroups->map(fn (Collection $group) => $group->sortByDesc('id')->first());
        $creatorNames = $this->resolveTravelCreatorNames($primaryTrips->pluck('id')->filter()->all());

        return $tripGroups
            ->map(function (Collection $group) use ($creatorNames) {
                $primaryTrip = $group->sortByDesc('id')->first();
                $orderIds = $group->pluck('order_id')->filter()->unique()->values();
                $users = $group->map(fn (TravelHistory $trip) => $trip->user)
                    ->filter()
                    ->unique('id')
                    ->values();
                $maids = $group->map(fn (TravelHistory $trip) => $trip->maid)
                    ->filter()
                    ->unique('id')
                    ->values();

                return (object) [
                    'id' => $primaryTrip->id,
                    'trip_group_code' => $primaryTrip->trip_group_code,
                    'contracts_count' => $orderIds->count(),
                    'order_ids' => $orderIds->all(),
                    'order_id' => $orderIds->implode(', '),
                    'created_at' => $primaryTrip->created_at,
                    'creator_name' => $creatorNames->get($primaryTrip->id),
                    'travel_type' => $primaryTrip->travel_type,
                    'travel_with' => $primaryTrip->travel_with,
                    'arrival_airport' => $primaryTrip->arrival_airport,
                    'arrival_date' => $primaryTrip->arrival_date,
                    'arrival_time' => $primaryTrip->arrival_time,
                    'flight_company' => $primaryTrip->flight_company,
                    'flight_number' => $primaryTrip->flight_number,
                    'status' => $this->resolveTravelGroupStatus($group),
                    'users' => $users,
                    'maids' => $maids,
                ];
            })
            ->sortByDesc('id')
            ->values();
    }

    private function resolveTravelTripGroupKey(TravelHistory $trip): string
    {
        if (!empty($trip->trip_group_code)) {
            return 'group:' . $trip->trip_group_code;
        }

        return 'single:' . $trip->id;
    }

    private function resolveTravelGroupStatus(Collection $group): string
    {
        if ($group->isEmpty()) {
            return 'pending';
        }

        if ($group->every(fn (TravelHistory $trip) => $trip->status === 'completed')) {
            return 'completed';
        }

        foreach (['in_progress', 'active', 'scheduled', 'confirmed', 'pending', 'new'] as $status) {
            if ($group->contains(fn (TravelHistory $trip) => $trip->status === $status)) {
                return $status;
            }
        }

        return (string) ($group->first()->status ?? 'pending');
    }

    private function resolveTravelCreatorNames(array $tripIds): Collection
    {
        $tripIds = array_values(array_unique(array_filter($tripIds)));
        if ($tripIds === [] || !Schema::hasTable(config('activitylog.table_name', 'activity_log'))) {
            return collect();
        }

        return Activity::query()
            ->with('causer')
            ->where('subject_type', TravelHistory::class)
            ->whereIn('subject_id', $tripIds)
            ->where('causer_type', Admin::class)
            ->where('event', 'created')
            ->orderBy('subject_id')
            ->orderBy('id')
            ->get()
            ->groupBy('subject_id')
            ->map(fn (Collection $activities) => optional($activities->first()?->causer)->name)
            ->filter();
    }

    private function travelAirportLabel(?string $airport): string
    {
        if (!$airport) {
            return 'غير محدد';
        }

        return TravelAirports::label($airport);
    }

    private function complaintStatusLabel(?string $status): string
    {
        if (!$status) {
            return '-';
        }

        $options = $this->complaintStatusOptions();

        return $options[$status] ?? $status;
    }

    private function complaintPriorityLabel(?string $priority): string
    {
        if (!$priority) {
            return '-';
        }

        $options = $this->complaintPriorityOptions();

        return $options[$priority] ?? $priority;
    }

    private function officeLinkedContractsCountSubquery()
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

    private function officePreviousSponsorshipsCountSubquery()
    {
        return DB::table('contract_infos')
            ->selectRaw('COUNT(*)')
            ->whereColumn('contract_infos.previous_sponsorship', 'recruitment_offices.id');
    }

    private function officeExternalDispatchesCountSubquery()
    {
        if (!Schema::hasTable('contract_external_office_dispatches')) {
            return DB::query()->selectRaw('0');
        }

        return DB::table('contract_external_office_dispatches')
            ->selectRaw('COUNT(*)')
            ->whereColumn('contract_external_office_dispatches.recruitment_office_id', 'recruitment_offices.id');
    }

    private function officeStatusLabel(?string $status): string
    {
        if (!$status) {
            return '-';
        }

        $options = $this->officeStatusOptions();

        return $options[$status] ?? $status;
    }

    private function officeManagerStatusLabel(Office $office): string
    {
        $manager = $office->officeManager?->admin;

        if (!$manager) {
            return 'بدون مدير';
        }

        if (($manager->status ?? 'active') === 'suspended') {
            return 'مدير موقوف';
        }

        if (method_exists($manager, 'isExternalOfficeAgent') && !$manager->isExternalOfficeAgent()) {
            return 'غير مرتبط بلوحة الوكيل';
        }

        return 'لديه مدير';
    }

    private function canViewAllFinancialRequestsForReport(): bool
    {
        return AdminGuardHasAnyPerm([
            PermKeys::FINANCIAL_REQUESTS_CHANGE_STATUS,
            PermKeys::FINANCIAL_REQUESTS_APPROVE,
            PermKeys::FINANCIAL_REQUESTS_CONTROL,
            PermKeys::FINANCIAL_REQUESTS_EXPORT,
            NewPermKeys::REPORTS_EXPORT,
        ]);
    }

    private function resolveUserPhone($user): string
    {
        if (!$user) {
            return '-';
        }

        $fullPhone = trim((string) ($user->phone_code ?? '') . (string) ($user->phone ?? ''));

        return $fullPhone !== '' ? $fullPhone : '-';
    }

    private function modelLabel($model, array $fields): string
    {
        if (!$model) {
            return '-';
        }

        foreach ($fields as $field) {
            $value = data_get($model, $field);
            if (filled($value)) {
                return (string) $value;
            }
        }

        return '-';
    }

    private function formatDateValue($value, bool $withTime = false): string
    {
        $carbon = $this->parseCarbonValue($value);

        if (!$carbon) {
            return '-';
        }

        return $carbon->format($withTime ? 'Y-m-d H:i' : 'Y-m-d');
    }

    private function parseCarbonValue($value): ?Carbon
    {
        if (!$value) {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value);
        }

        if (in_array($value, ['0000-00-00', '0000-00-00 00:00:00'], true)) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function formatMoney($value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        return number_format((float) str_replace(',', '', (string) $value), 2);
    }
}
