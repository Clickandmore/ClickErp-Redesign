<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Actions\ingaz\NationalityAction as objAction;
use App\Http\Controllers\admin\Controller;
use App\Http\Requests\ingaz\NationalityRequest;
use App\Models\ingaz\Nationality;
use App\Models\ingaz\Nationality as objModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Nami\Finance\Models\Account;
use Throwable;
use Yajra\DataTables\Facades\DataTables;

class NationalityController extends Controller
{

    public $folderPath = "admin.ingaz.nationalities";
    public $postData = [
        "logo",
        "phone_code",
        "sub_title:ar",
        "sub_title:en",
        "description:ar",
        "description:en",
        "price",
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
                    'price',
                    'salary_with_experience',
                    'salary_with_no_experience',
                    'service_transportation_cost',
                    'rental_cost',
                    'monthly_salary',
                    'is_show',
                    'created_at',
                ])
                ->orderByTranslation('sub_title', 'asc');

            $currency = helperTrans('ingaz.currency_sar');

            return DataTables::of($rows)
                ->addIndexColumn()
                ->addColumn('meta', function (objModel $row) {
                    $logo = $row->logo ? showFile($row->logo) : asset('images/no-image.png');
                    $title = e($row->sub_title ?? $row->title ?? helperTrans('ingaz.not_specified'));
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
                    $title = e($row->sub_title ?? $row->title ?? helperTrans('ingaz.not_specified'));

                    if (@AdminGuardHasPerm(\App\PermKeys::NATIONALITIES_CONTROL)) {
                        $menu .= '<button type="button" class="btn btn-sm btn-info text-white generate-nationality-accounts ms-1"
                            data-route="' . route('nationalities.generate-financial-accounts', $row->id) . '"
                            data-name="' . $title . '"
                            title="إنشاء الحسابات المالية">
                            <i class="fas fa-sitemap"></i>
                        </button> ';

                        $menu .= editButton(route($this->mainRoute . ".edit", $row->id), $row->title);
                    }

                    if (@AdminGuardHasPerm(\App\PermKeys::NATIONALITIES_DELETE)) {
                        $menu .= ' ' . deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    }
                    return $menu;
                })
                ->addColumn('sub_title_plain', function ($row) {
                    return $row->sub_title ?? $row->title ?? '';
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
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".create")->render();
            return jsonSuccess(["html" => $returnHTML]);
        }

        return view($this->folderPath . '.create-page');
    }


    public function store(NationalityRequest $request, objAction $action)
    {

        $data = $request->only($this->postData);
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
        $updatedData = $request->only($this->postData);
        // dd($updatedData);
        $data = $action->updateNationality($id, $updatedData);
        return jsonSuccess($data);
    }

    public function generateFinancialAccounts(int $id)
    {
        $nationality = objModel::findOrFail($id);
        $nationalityName = $this->resolveNationalityAccountName($nationality);

        if ($nationalityName === '') {
            return jsonError('لا يمكن إنشاء الحسابات لأن اسم الجنسية غير متوفر.', 422);
        }

        $createdNodes = 0;
        $createdLeaves = 0;

        try {
            DB::transaction(function () use ($nationalityName, &$createdNodes, &$createdLeaves) {
                foreach ($this->buildNationalityAccountPaths($nationalityName) as $path) {
                    $result = $this->ensureAccountPath($path);
                    $createdNodes += $result['created_nodes'];
                    $createdLeaves += $result['leaf_was_created'] ? 1 : 0;
                }
            });
        } catch (Throwable $throwable) {
            return jsonError($throwable->getMessage(), 422);
        }

        $message = 'تمت معالجة حسابات الجنسية (' . $nationalityName . '). ';
        if ($createdNodes === 0) {
            $message .= 'جميع الحسابات المطلوبة موجودة مسبقًا.';
        } else {
            $message .= 'تم إنشاء ' . $createdLeaves . ' حساب نهائي و' . ($createdNodes - $createdLeaves) . ' حساب وسيط.';
        }

        return jsonSuccess([
            'nationality' => $nationalityName,
            'created_nodes' => $createdNodes,
            'created_leaves' => $createdLeaves,
        ], $message);
    }

    public function destroy($id, objAction $action)
    {
        $action->deleteWithFile($id, 'image');
        return jsonSuccess();
    }

    private function resolveNationalityAccountName(objModel $nationality): string
    {
        $arTranslation = $nationality->translations->firstWhere('locale', 'ar');

        return trim((string) (
            $arTranslation?->sub_title
            ?? $arTranslation?->title
            ?? $nationality->sub_title
            ?? $nationality->title
            ?? ''
        ));
    }

    private function buildNationalityAccountPaths(string $nationalityName): array
    {
        return [
            [
                $this->accountNode('الإيرادات'),
                $this->accountNode('إيراد مبيعات الاستقدام', ['إيرادات مبيعات الاستقدام']),
                $this->accountNode('مبيعات الاستقدام'),
                $this->accountNode('مبيعات'),
                $this->accountNode('مبيعات (' . $nationalityName . ')', ['مبيعات ' . $nationalityName]),
            ],
            [
                $this->accountNode('المصروفات'),
                $this->accountNode('تكاليف مبيعات الاستقدام'),
                $this->accountNode('تكاليف مبيعات', ['تكاليف المبيعات']),
                $this->accountNode('تكاليف مبيعات (' . $nationalityName . ')', ['تكاليف مبيعات ' . $nationalityName]),
            ],
            [
                $this->accountNode('الخصوم'),
                $this->accountNode('الخصوم قصيرة الأجل', ['الخصوم قصيرة الاجل']),
                $this->accountNode('التزامات عقود الاستقدام', ['إلتزامات عقود الاستقدام']),
                $this->accountNode('التزامات عقود', ['إلتزامات عقود']),
                $this->accountNode('التزامات عقود (' . $nationalityName . ')', ['التزامات عقود ' . $nationalityName, 'إلتزامات عقود ' . $nationalityName]),
            ],
            [
                $this->accountNode('الإيرادات'),
                $this->accountNode('إيراد مبيعات الاستقدام', ['إيرادات مبيعات الاستقدام']),
                $this->accountNode('خصم مبيعات'),
                $this->accountNode('خصم مبيعات ' . $nationalityName),
            ],
            [
                $this->accountNode('الإيرادات'),
                $this->accountNode('إيراد نقل الخدمات'),
                $this->accountNode('ايرادات نقل خدمات', ['إيراد نقل خدمات']),
                $this->accountNode('ايراد نقل خدمات ' . $nationalityName, ['إيراد نقل خدمات ' . $nationalityName]),
            ],
            [
                $this->accountNode('المصروفات'),
                $this->accountNode('مصروفات تشغيلية اخرى'),
                $this->accountNode('مصروفات سفارات اجنبية', ['مصروفات سفارات أجنبية']),
                $this->accountNode('مصروفات جوب اوردر ' . $nationalityName),
            ],
            [
                $this->accountNode('المصروفات'),
                $this->accountNode('مصروفات تشغيلية اخرى'),
                $this->accountNode('مصروفات سفارات اجنبية', ['مصروفات سفارات أجنبية']),
                $this->accountNode('مصروفات انهاء اجراءات سفارة ' . $nationalityName),
            ],
            [
                $this->accountNode('المصروفات'),
                $this->accountNode('تكاليف مبيعات الاستقدام'),
                $this->accountNode('مردودات تكلفة الاستقدام'),
                $this->accountNode('مردودات تكلفة ' . $nationalityName),
            ],
            [
                $this->accountNode('الإيرادات'),
                $this->accountNode('إيراد مبيعات الاستقدام', ['إيرادات مبيعات الاستقدام']),
                $this->accountNode('مردودات مبيعات الاستقدام', ['مرتد مبيعات الاستقدام']),
                $this->accountNode('مردودات مبيعات استقدام ' . $nationalityName),
            ],
            [
                $this->accountNode('الإيرادات'),
                $this->accountNode('إيراد نقل الخدمات'),
                $this->accountNode('مردودات مبيعات نقل الخدمات'),
                $this->accountNode('مردودات نقل خدمات ' . $nationalityName),
            ],
            [
                $this->accountNode('الأصول'),
                $this->accountNode('الأصول المتداولة قصيرة الأجل', ['الأصول المتداولة قصيرة الاجل']),
                $this->accountNode('مصروفات مدفوعة مقدما - الايواء', ['مصروفات مدفوعة مقدما الايواء']),
                $this->accountNode('مصروفات مدفوعة مقدما ايواء ' . $nationalityName),
            ],
            [
                $this->accountNode('الخصوم'),
                $this->accountNode('الخصوم قصيرة الأجل', ['الخصوم قصيرة الاجل']),
                $this->accountNode('دفعات مقدمة نقل خدمات'),
                $this->accountNode('دفعات مقدمة نقل خدمات ' . $nationalityName),
            ],
        ];
    }

    private function accountNode(string $createName, array $aliases = []): array
    {
        $allNames = array_merge([$createName], $aliases);
        $allNames = array_map(static fn($name) => trim((string) $name), $allNames);
        $allNames = array_values(array_unique(array_filter($allNames)));

        return [
            'create' => $createName,
            'aliases' => $allNames,
        ];
    }

    private function ensureAccountPath(array $path): array
    {
        $parent = null;
        $createdNodes = 0;
        $leafWasCreated = false;
        $lastIndex = count($path) - 1;

        foreach ($path as $index => $node) {
            $account = $this->findAccountByAliases($node['aliases'] ?? [], $parent?->id);

            if (!$account) {
                if ($index === 0) {
                    throw new \RuntimeException('الحساب الرئيسي "' . ($node['create'] ?? '') . '" غير موجود في الشجرة المالية.');
                }

                $account = $this->createChildAccount($parent, $node['create']);
                $createdNodes++;
                if ($index === $lastIndex) {
                    $leafWasCreated = true;
                }
            }

            $parent = $account;
        }

        return [
            'created_nodes' => $createdNodes,
            'leaf_was_created' => $leafWasCreated,
            'leaf_id' => $parent?->id,
        ];
    }

    private function findAccountByAliases(array $aliases, ?int $parentId): ?Account
    {
        $aliases = array_map(static fn($name) => trim((string) $name), $aliases);
        $aliases = array_values(array_unique(array_filter($aliases)));

        if (empty($aliases)) {
            return null;
        }

        $query = Account::query();
        if ($parentId === null) {
            $query->whereNull('parent_id');
        } else {
            $query->where('parent_id', $parentId);
        }

        $accounts = $query->whereIn('name', $aliases)->get()->keyBy('name');
        foreach ($aliases as $alias) {
            if (isset($accounts[$alias])) {
                return $accounts[$alias];
            }
        }

        $query = Account::query();
        if ($parentId === null) {
            $query->whereNull('parent_id');
        } else {
            $query->where('parent_id', $parentId);
        }

        $siblings = $query->get();
        $normalizedAliases = array_map([$this, 'normalizeAccountName'], $aliases);
        foreach ($siblings as $sibling) {
            if (in_array($this->normalizeAccountName($sibling->name), $normalizedAliases, true)) {
                return $sibling;
            }
        }

        return null;
    }

    private function normalizeAccountName(string $name): string
    {
        $normalized = trim(preg_replace('/\s+/u', ' ', $name));
        $normalized = str_replace(['أ', 'إ', 'آ', 'ة', 'ى'], ['ا', 'ا', 'ا', 'ه', 'ي'], $normalized);

        return $normalized;
    }

    private function createChildAccount(Account $parent, string $accountName): Account
    {
        $status = $parent->status;
        if ($status instanceof \BackedEnum) {
            $status = $status->value;
        }

        return Account::query()->create([
            'name' => $accountName,
            'operation_type' => $parent->operation_type,
            'status' => $status,
            'level' => (int) $parent->level + 1,
            'type' => $parent->type,
            'parent_id' => $parent->id,
            'code' => $this->nextChildCode($parent),
            'statement_type' => $parent->statement_type,
            'created_by_id' => auth('admin')->id() ?: Auth::id() ?: 1,
        ]);
    }

    private function nextChildCode(Account $parent): int
    {
        $lastChildCode = (int) Account::query()
            ->where('parent_id', $parent->id)
            ->lockForUpdate()
            ->max('code');

        if ($lastChildCode > 0) {
            return $lastChildCode + 1;
        }

        $parentCode = (int) $parent->code;
        if ($parentCode > 0) {
            return ($parentCode * 100) + 1;
        }

        return ((int) Account::query()->lockForUpdate()->max('code')) + 1;
    }
}
