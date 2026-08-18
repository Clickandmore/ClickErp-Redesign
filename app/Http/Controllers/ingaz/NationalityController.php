<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Actions\ingaz\NationalityAction as objAction;
use App\Http\Controllers\admin\Controller;
use App\Http\Requests\ingaz\NationalityRequest;
use App\Models\ingaz\Nationality;
use App\Models\ingaz\Nationality as objModel;
use App\Services\ingaz\NationalityFinanceAccountService;
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
                    'direct_recruitment_cost',
                    'external_recruitment_cost',
                    'government_cost',
                    'salary_with_experience',
                    'salary_with_no_experience',
                    'service_transportation_cost',
                    'rental_cost',
                    'monthly_salary',
                    'expected_duration',
                    'is_show',
                    'created_at',
                ])
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
                        ['icon' => 'fa-briefcase', 'label' => helperTrans('ingaz.salary_with_experience'), 'value' => $row->salary_with_experience],
                        ['icon' => 'fa-user', 'label' => helperTrans('ingaz.salary_with_no_experience'), 'value' => $row->salary_with_no_experience],
                        ['icon' => 'fa-truck', 'label' => helperTrans('ingaz.service_transportation_cost'), 'value' => $row->service_transportation_cost],
                        ['icon' => 'fa-file-contract', 'label' => helperTrans('ingaz.rental_cost'), 'value' => $row->rental_cost],
                        // ['icon' => 'fa-money-bill-wave', 'label' => helperTrans('ingaz.monthly_salary'), 'value' => $row->monthly_salary],
                    ];

                    $chips = collect($items)->map(function ($item) use ($format) {
                        return '<div class="financial-chip">
                                    <i class="fas ' . $item['icon'] . '"></i>
                                    <span class="text-muted">' . $item['label'] . '</span>
                                    <strong>' . $format($item['value']) . '</strong>
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
                ->addColumn('actions', function ($row) {
                    $menu = '';
                    $title = e($row->display_name ?: helperTrans('ingaz.not_specified'));

                    if (@AdminGuardHasPerm(\App\PermKeys::NATIONALITIES_CONTROL)) {
                        $menu .= '<button type="button" class="btn btn-sm btn-info text-white generate-nationality-accounts ms-1"
                            data-route="' . route('nationalities.generate-financial-accounts', $row->id) . '"
                            data-name="' . $title . '"
                            title="إنشاء الحسابات المالية">
                            <i class="fas fa-sitemap"></i>
                        </button> ';

                        $menu .= editButton(route($this->mainRoute . ".edit", $row->id), $row->display_name);
                    }

                    if (@AdminGuardHasPerm(\App\PermKeys::NATIONALITIES_DELETE)) {
                        $menu .= ' ' . deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    }
                    return $menu;
                })
                ->addColumn('sub_title_plain', function ($row) {
                    return $row->display_name;
                })
                ->filterColumn('sub_title_plain', function ($query, $keyword) {
                    $query->whereTranslationLike('sub_title', '%' . $keyword . '%');
                })
                ->rawColumns(['meta', 'financials', 'visibility_toggle', 'actions'])
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
        if ($request->ajax()) {
            $obj = Nationality::findorfail($id);
            $returnHTML = view($this->folderPath . '.edit')
                ->with(['obj' => $obj])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    public function update(NationalityRequest $request, objAction $action, $id)
    {
        $updatedData = $this->syncTitleWithSubTitle($request->only($this->postData));
        // dd($updatedData);
        $data = $action->updateNationality($id, $updatedData);
        return jsonSuccess($data);
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
