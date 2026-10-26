<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Actions\ingaz\NationalityAction as objAction;
use App\Http\Controllers\admin\Controller;
use App\Http\Requests\ingaz\NationalityRequest;
use App\Models\ingaz\Nationality;
use App\Models\ingaz\Nationality as objModel;
use App\Services\ingaz\NationalityFinanceAccountService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;
use Yajra\DataTables\Facades\DataTables;

class NationalityController extends Controller
{

    public $folderPath = "admin.ingaz.nationalities";
    public $postData = [
        "logo",
        "phone_code",
        "sort_order",
        "sub_title:ar",
        "sub_title:en",
        "description:ar",
        "description:en",
        "price",
        "direct_recruitment_cost",
        "external_recruitment_cost",
        "government_cost",
        "musaned_commission_rate",
        "salary_with_no_experience",
        "salary_with_experience",
        "service_transportation_cost",
        "rental_cost",
        "expected_duration",
        "monthly_salary",
        "is_show",
    ];
    public $mainRoute = "nationalities";

    function __construct()
    {
        // $this->middleware('permission:الجنسيات-قراءة,admin', ['only' => ['index']]);
        // $this->middleware('permission:الجنسيات-انشاء,admin', ['only' => ['create', 'store']]);
        // $this->middleware('permission:الجنسيات-تعديل,admin', ['only' => ['edit', 'update']]);
        // $this->middleware('permission:الجنسيات-حذف,admin', ['only' => ['destroy']]);
    }


    public function index(Request $request, objAction $Action)
    {
        if ($request->ajax()) {
            $rows = objModel::query()
                ->select([
                    'id',
                    'logo',
                    'phone_code',
                    'sort_order',
                    'price',
                    'offer_price',
                    'offer_duration',
                    'offer_description',
                    'offer_starts_at',
                    'offer_expires_at',
                    'direct_recruitment_cost',
                    'external_recruitment_cost',
                    'government_cost',
                    'musaned_commission_rate',
                    'salary_with_experience',
                    'salary_with_no_experience',
                    'service_transportation_cost',
                    'rental_cost',
                    'monthly_salary',
                    'expected_duration',
                    'is_show',
                    'created_at',
                ])
                ->withCount(['maids as available_maids_count' => function ($query) {
                    $query->where('available', true)->where('status', 'available');
                }])
                ->ordered();

            $currency = helperTrans('ingaz.currency_sar');

            return DataTables::of($rows)
                ->addIndexColumn()
                ->addColumn('meta', function (objModel $row) {
                    $logo = $row->logo ? showFile($row->logo) : asset('images/no-image.png');
                    $title = e($row->display_name ?: helperTrans('ingaz.not_specified'));
                    $createdAt = $row->created_at ? $row->created_at->format('Y-m-d') : helperTrans('ingaz.not_specified');
                    $phoneCode = $row->phone_code
                        ? (Str::startsWith($row->phone_code, '+') ? $row->phone_code : '+' . $row->phone_code)
                        : helperTrans('ingaz.not_specified');
                    $description = trim(strip_tags($row->description ?? ''));
                    $description = $description
                        ? '<p class="text-muted small mb-0">' . e(Str::limit($description, 160)) . '</p>'
                        : '';

                    $statusBadge = $row->is_show
                        ? '<span class="badge bg-success-subtle text-success border border-success-subtle">' . helperTrans('ingaz.maid_visibility_visible') . '</span>'
                        : '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">' . helperTrans('ingaz.maid_visibility_hidden') . '</span>';

                    return '<div class="d-flex align-items-center gap-3 nationality-card">
                                <div class="flex-shrink-0">
                                    <img src="' . $logo . '" alt="' . $title . '" class="nationality-avatar">
                                </div>
                                <div class="flex-grow-1 d-flex flex-column gap-2">
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <span class="fw-semibold text-dark">' . $title . '</span>
                                        ' . $statusBadge . '
                                    </div>
                                    <div class="text-muted small d-flex flex-wrap gap-3">
                                        <span><i class="fas fa-phone-alt me-1"></i>' . e($phoneCode) . '</span>
                                        <span><i class="fas fa-calendar-day me-1"></i>' . $createdAt . '</span>
                                    </div>
                                    ' . $description . '
                                </div>
                            </div>';
                })
                ->addColumn('financials', function (objModel $row) use ($currency) {
                    $format = function ($value) use ($currency) {
                        return $value !== null
                            ? number_format((float)$value, 0) . ' ' . $currency
                            : helperTrans('ingaz.not_specified');
                    };

                    $items = [
                        ['icon' => 'fa-coins', 'label' => helperTrans('ingaz.recruitment_cost'), 'value' => $row->price],
                        ['icon' => 'fa-handshake', 'label' => 'الاستقدام المباشر', 'value' => $row->direct_recruitment_cost],
                        ['icon' => 'fa-plane-departure', 'label' => 'الاستقدام الخارجي', 'value' => $row->external_recruitment_cost],
                        ['icon' => 'fa-landmark', 'label' => 'التكاليف الحكومية', 'value' => $row->government_cost],
                        ['icon' => 'fa-percent', 'label' => 'نسبة التكاليف البنكية', 'value' => $row->musaned_commission_rate, 'percent' => true],
                        ['icon' => 'fa-briefcase', 'label' => helperTrans('ingaz.salary_with_experience'), 'value' => $row->salary_with_experience],
                        ['icon' => 'fa-user', 'label' => helperTrans('ingaz.salary_with_no_experience'), 'value' => $row->salary_with_no_experience],
                        ['icon' => 'fa-truck', 'label' => helperTrans('ingaz.service_transportation_cost'), 'value' => $row->service_transportation_cost],
                        ['icon' => 'fa-file-contract', 'label' => helperTrans('ingaz.rental_cost'), 'value' => $row->rental_cost],
                        // ['icon' => 'fa-money-bill-wave', 'label' => helperTrans('ingaz.monthly_salary'), 'value' => $row->monthly_salary],
                    ];

                    $chips = collect($items)->map(function ($item) use ($format) {
                        $value = !empty($item['percent'])
                            ? rtrim(rtrim(number_format((float) $item['value'], 4, '.', ''), '0'), '.') . '%'
                            : $format($item['value']);
                        return '<div class="financial-chip">
                                    <i class="fas ' . $item['icon'] . '"></i>
                                    <span class="text-muted">' . $item['label'] . '</span>
                                    <strong>' . $value . '</strong>
                                </div>';
                    })->implode('');

                    return '<div class="d-flex flex-column flex-md-row flex-wrap gap-2">' . $chips .
                                '<div class="financial-chip">
                                    <i class="fas fa-money-bill-wave"></i>
                                    <span class="text-muted">' . helperTrans('ingaz.expected_duration') . '</span>
                                    <strong>' . $row->expected_duration . " " . helperTrans('ingaz.day') . '</strong>
                                </div>' .
                            '</div>';
                })
                ->addColumn('visibility_toggle', function ($row) {
                    $statusLabel = $row->is_show
                        ? helperTrans('ingaz.hide')
                        : helperTrans('ingaz.show');

                    $statusClass = $row->is_show ? 'btn-outline-danger' : 'btn-outline-success';

                    return '<button type="button" class="btn btn-sm ' . $statusClass . ' update-status" data-id="'
                        . $row->id . '"><i class="fas fa-eye' . ($row->is_show ? '-slash' : '') . ' me-1"></i>' . $statusLabel . '</button>';
                })
                ->addColumn('offer', function (objModel $row) use ($currency) {
                    if ($row->offer_price === null) {
                        return '<div class="offer-summary offer-summary--empty">'
                            . '<i class="fas fa-tag"></i><span>لا يوجد عرض</span>'
                            . '<small>' . number_format($row->available_maids_count) . ' عاملة متاحة</small></div>';
                    }

                    $states = [
                        'active' => ['عرض نشط', 'success', 'fa-bolt'],
                        'scheduled' => ['عرض مجدول', 'info', 'fa-clock'],
                        'expired' => ['عرض منتهي', 'secondary', 'fa-history'],
                    ];
                    [$label, $color, $icon] = $states[$row->offer_state] ?? $states['expired'];
                    $dates = ($row->offer_starts_at?->format('Y-m-d') ?: 'غير محدد')
                        . ' — ' . ($row->offer_expires_at?->format('Y-m-d') ?: 'غير محدد');

                    return '<div class="offer-summary">'
                        . '<div><span class="badge bg-' . $color . '-subtle text-' . $color . '"><i class="fas ' . $icon . ' me-1"></i>' . $label . '</span></div>'
                        . '<strong>' . number_format((float) $row->offer_price, 2) . ' ' . $currency . '</strong>'
                        . '<small><i class="far fa-calendar-alt me-1"></i>' . e($dates) . '</small>'
                        . '<small><i class="fas fa-users me-1"></i>يطبق على ' . number_format($row->available_maids_count) . ' عاملة متاحة</small>'
                        . '</div>';
                })
                ->addColumn('actions', function ($row) {
                    $items = '';
                    $editAction = '';
                    $title = e($row->display_name ?: helperTrans('ingaz.not_specified'));

                    if (@AdminGuardHasPerm(\App\PermKeys::NATIONALITIES_CONTROL)) {
                        $editAction = '<a class="btn btn-sm btn-primary" href="' . route($this->mainRoute . '.edit', $row->id) . '">'
                            . '<i class="fas fa-pencil-alt me-1"></i>تعديل</a>';
                        $items .= '<li><button type="button" class="dropdown-item manage-nationality-offer"'
                            . ' data-id="' . $row->id . '" data-name="' . $title . '"'
                            . ' data-route="' . route('nationalities.offer', $row->id) . '"'
                            . ' data-offer-price="' . e($row->offer_price ?? '') . '"'
                            . ' data-offer-description="' . e($row->offer_description ?? '') . '"'
                            . ' data-offer-start="' . ($row->offer_starts_at?->format('Y-m-d') ?? '') . '"'
                            . ' data-offer-expire="' . ($row->offer_expires_at?->format('Y-m-d') ?? '') . '"'
                            . ' data-has-offer="' . ($row->offer_price !== null ? '1' : '0') . '">'
                            . '<i class="fas fa-tags text-warning me-2"></i>إدارة العرض</button></li>';
                        $items .= '<li><button type="button" class="dropdown-item generate-nationality-accounts"'
                            . ' data-route="' . route('nationalities.generate-financial-accounts', $row->id) . '" data-name="' . $title . '">'
                            . '<i class="fas fa-sitemap text-info me-2"></i>إنشاء الحسابات المالية</button></li>';
                    }

                    if (@AdminGuardHasPerm(\App\PermKeys::NATIONALITIES_DELETE)) {
                        $items .= '<li><hr class="dropdown-divider"></li><li><button type="button" class="dropdown-item text-danger deleteButton"'
                            . ' delete-route="' . route($this->mainRoute . '.destroy', $row->id) . '">'
                            . '<i class="fas fa-trash-alt me-2"></i>حذف الجنسية</button></li>';
                    }

                    if ($editAction === '' && $items === '') {
                        return '—';
                    }

                    $menu = $items === '' ? '' : '<div class="dropdown actions-dropdown">'
                        . '<button class="btn btn-light border btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">'
                        . '<i class="fas fa-ellipsis-v me-1"></i>المزيد</button>'
                        . '<ul class="dropdown-menu dropdown-menu-end shadow-sm">' . $items . '</ul></div>';

                    return '<div class="d-flex align-items-center justify-content-center gap-2 flex-wrap">' . $editAction . $menu . '</div>';
                })
                ->addColumn('visibility_key', fn ($row) => $row->is_show ? 'visible' : 'hidden')
                ->addColumn('offer_state_key', fn ($row) => $row->offer_state)
                ->addColumn('sub_title_plain', function ($row) {
                    return $row->display_name;
                })
                ->filterColumn('sub_title_plain', function ($query, $keyword) {
                    $query->whereTranslationLike('sub_title', '%' . $keyword . '%');
                })
                ->rawColumns(['meta', 'financials', 'offer', 'visibility_toggle', 'actions'])
                ->make(true);
        }

        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = helperTrans('api.nationalities');
        $data["addButtonText"] = helperTrans('api.nationality');
        $data["modalType"] = "modal-lg";
        $data["stats"] = [
            'total' => objModel::count(),
            'visible' => objModel::where('is_show', true)->count(),
            'hidden' => objModel::where('is_show', false)->count(),
            'offers' => objModel::whereNotNull('offer_price')->count(),
        ];

        return view($this->folderPath . '.index', $data);
    }

    public function show($id)
    {
        $model = objModel::findOrFail($id);
        $model->is_show = !$model->is_show;
        $model->save();

        return response()->json(['success' => true, 'is_show' => $model->is_show]);
    }


    public function create(Request $request)
    {
        $nextSortOrder = max(1, ((int) objModel::max('sort_order')) + 1);

        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".create", compact('nextSortOrder'))->render();
            return jsonSuccess(["html" => $returnHTML]);
        }

        return view($this->folderPath . '.create-page', compact('nextSortOrder'));
    }


    public function store(NationalityRequest $request, objAction $action)
    {

        $data = $this->syncTitleWithSubTitle($request->only($this->postData));
        $data = $action->store($data);
        return jsonSuccess($data);
    }


    public function edit(Request $request, $id)
    {
        $obj = Nationality::findOrFail($id);

        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . '.edit')
                ->with(['obj' => $obj])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }

        return view($this->folderPath . '.edit-page', compact('obj'));
    }

    public function update(NationalityRequest $request, objAction $action, $id)
    {
        $updatedData = $this->syncTitleWithSubTitle($request->only($this->postData));
        // dd($updatedData);
        $data = $action->updateNationality($id, $updatedData);
        return jsonSuccess($data);
    }

    public function updateOffer(Request $request, int $id)
    {
        if (!@AdminGuardHasPerm(\App\PermKeys::NATIONALITIES_CONTROL)) {
            return response()->json(['success' => false, 'message' => helperTrans('ingaz.permission_denied')], 403);
        }

        $nationality = objModel::findOrFail($id);

        if ($request->boolean('clear_offer')) {
            $nationality->update([
                'offer_price' => null,
                'offer_duration' => null,
                'offer_description' => null,
                'offer_starts_at' => null,
                'offer_expires_at' => null,
            ]);

            return response()->json(['success' => true, 'message' => 'تم حذف عرض الجنسية بنجاح.']);
        }

        $validated = $request->validate([
            'offer_price' => ['required', 'numeric', 'min:0'],
            'offer_description' => ['nullable', 'string', 'max:500'],
            'offer_starts_at' => ['nullable', 'date'],
            'offer_expires_at' => ['nullable', 'date', 'after_or_equal:offer_starts_at'],
        ], [
            'offer_expires_at.after_or_equal' => 'تاريخ نهاية العرض يجب أن يكون بعد تاريخ البداية أو مساوياً له.',
        ], [
            'offer_price' => 'سعر العرض',
            'offer_description' => 'وصف العرض',
            'offer_starts_at' => 'بداية العرض',
            'offer_expires_at' => 'نهاية العرض',
        ]);

        $startsAt = filled($validated['offer_starts_at'] ?? null) ? Carbon::parse($validated['offer_starts_at']) : null;
        $expiresAt = filled($validated['offer_expires_at'] ?? null) ? Carbon::parse($validated['offer_expires_at']) : null;

        $nationality->update([
            'offer_price' => round((float) $validated['offer_price'], 2),
            'offer_duration' => $startsAt && $expiresAt ? (string) ($startsAt->diffInDays($expiresAt) + 1) : null,
            'offer_description' => filled($validated['offer_description'] ?? null) ? trim($validated['offer_description']) : null,
            'offer_starts_at' => $startsAt?->toDateString(),
            'offer_expires_at' => $expiresAt?->toDateString(),
        ]);

        $availableMaids = $nationality->maids()->where('available', true)->where('status', 'available')->count();

        return response()->json([
            'success' => true,
            'message' => 'تم حفظ العرض وتطبيقه تلقائياً على ' . number_format($availableMaids) . ' عاملة متاحة.',
        ]);
    }

    private function syncTitleWithSubTitle(array $data): array
    {
        foreach (config('translatable.locales', []) as $locale) {
            $subTitleKey = "sub_title:$locale";

            if (array_key_exists($subTitleKey, $data)) {
                $data["title:$locale"] = $data[$subTitleKey];
            }
        }

        return $data;
    }

    public function generateFinancialAccounts(int $id, NationalityFinanceAccountService $service)
    {
        $nationality = objModel::findOrFail($id);
        try {
            $result = $service->generateFor($nationality);
        } catch (Throwable $throwable) {
            return jsonError($throwable->getMessage(), 422);
        }

        $message = 'تمت معالجة حسابات الجنسية (' . $result['nationality'] . '). ';
        if ($result['created_nodes'] === 0) {
            $message .= 'جميع الحسابات المطلوبة موجودة مسبقًا.';
        } else {
            $intermediateNodes = $result['created_nodes'] - $result['created_leaves'];
            $message .= 'تم إنشاء ' . $result['created_leaves'] . ' حساب نهائي';
            if ($intermediateNodes > 0) {
                $message .= ' و' . $intermediateNodes . ' حساب وسيط';
            }
            $message .= '، مع استخدام الحسابات الموجودة دون تكرار.';
        }

        return jsonSuccess($result, $message);
    }

    public function destroy($id, objAction $action)
    {
        $action->deleteWithFile($id, 'image');
        return jsonSuccess();
    }

}
