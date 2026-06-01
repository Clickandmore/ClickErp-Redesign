<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Actions\ingaz\NationalityAction;
use App\Http\Actions\ingaz\PackageAction as objAction;
use App\Http\Controllers\admin\Controller;
use App\Http\Requests\ingaz\PackageRequest;
use App\Models\ingaz\Package as objModel;
use Illuminate\Http\Request;

class PackageController extends Controller
{

    public $folderPath = "admin.ingaz.packages";
    public $postData = [
        "original_price",
        'nationality_id',
        "price_with_tax",
        "days_count",
        "rental_type",
        "duration_unit",
        "duration_count",
        "sort_order",
        "working_hours_per_day",
        "is_show",
        "description",
        "title:ar",
        "title:en",
    ];
    public $mainRoute = "packages";

    function __construct()
    {
        // $this->middleware('permission:الباقات-قراءة,admin', ['only' => ['index']]);
        // $this->middleware('permission:الباقات-انشاء,admin', ['only' => ['create', 'store']]);
        // $this->middleware('permission:الباقات-تعديل,admin', ['only' => ['edit', 'update']]);
        // $this->middleware('permission:الباقات-حذف,admin', ['only' => ['destroy']]);
    }


    public function index(Request $request, objAction $Action)
    {
        if ($request->ajax()) {
            $rows = objModel::query()
                ->with('nationality')
                ->ordered()
                ->get();

            $data = $rows->values()->map(function ($row, $index) {
                $actions = [
                    '<li><button type="button" class="dropdown-item editButton" data-bs-toggle="modal" data-bs-target="#createModal" model-route="' . route($this->mainRoute . '.show', $row->id) . '" model-title="عرض ' . e($row->title ?: ('#' . $row->id)) . '"><i class="fa fa-eye me-1"></i> عرض الباقة</button></li>',
                ];

                if (@AdminGuardHasPerm(\App\PermKeys::PACKAGES_CONTROL)) {
                    $toggleLabel = $row->is_show ? 'إخفاء' : 'تفعيل';
                    $toggleIcon = $row->is_show ? 'fa-eye-slash' : 'fa-check';
                    $actions[] = '<li><button type="button" class="dropdown-item editButton" data-bs-toggle="modal" data-bs-target="#createModal" model-route="' . route($this->mainRoute . '.edit', $row->id) . '" model-title="تعديل ' . e($row->title ?: ('#' . $row->id)) . '"><i class="fa fa-pencil me-1"></i> تعديل الباقة</button></li>';
                    $actions[] = '<li><button type="button" class="dropdown-item editButton" data-bs-toggle="modal" data-bs-target="#createModal" model-route="' . route($this->mainRoute . '.offer', $row->id) . '" model-title="إدارة عرض ' . e($row->title ?: ('#' . $row->id)) . '"><i class="fa fa-tag me-1"></i> إدارة العرض</button></li>';
                    $actions[] = '<li><button type="button" class="dropdown-item package-toggle-status" data-url="' . route($this->mainRoute . '.toggle-visibility', $row->id) . '"><i class="fa ' . $toggleIcon . ' me-1"></i> ' . $toggleLabel . '</button></li>';
                }

                if (@AdminGuardHasPerm(\App\PermKeys::PACKAGES_DELETE)) {
                    $actions[] = '<li><hr class="dropdown-divider"></li>';
                    $actions[] = '<li><button type="button" class="dropdown-item text-danger deleteButton" delete-route="' . route($this->mainRoute . '.destroy', $row->id) . '"><i class="fa fa-trash-o me-1"></i> حذف الباقة</button></li>';
                }

                $offerAmount = (float) ($row->offer_amount ?? 0);
                $offerLabel = $row->offer_is_active && $offerAmount > 0
                    ? number_format($offerAmount, 2) . ' حتى ' . ($row->offer_ends_at ? $row->offer_ends_at->format('Y-m-d') : 'بدون تاريخ انتهاء')
                    : '<span class="text-muted">لا يوجد عرض</span>';

                return [
                    'DT_RowIndex' => $index + 1,
                    'title' => $row->title,
                    'nationality' => $this->resolveNationalityLabel($row),
                    'rental_type' => $row->rental_type_label,
                    'duration' => ($row->duration_count ?: 1) . ' ' . $row->duration_unit_label,
                    'sort_order' => $row->sort_order ?? 0,
                    'original_price' => number_format((float) $row->original_price, 2),
                    'vat_amount' => number_format(max(0, (float) $row->price_with_tax - (float) $row->original_price), 2),
                    'price_with_tax' => number_format((float) $row->price_with_tax, 2),
                    'offer' => $offerLabel,
                    'days_count' => $row->days_count,
                    'is_show' => $row->is_show ? '<span class="badge bg-success">ظاهر</span>' : '<span class="badge bg-secondary">مخفي</span>',
                    'actions' => '<div class="dropdown actions-dropdown text-center">
                        <button class="btn btn-sm btn-primary dropdown-toggle actions-btn" type="button" aria-expanded="false">الإجراءات</button>
                        <ul class="dropdown-menu dropdown-menu-end">' . implode('', $actions) . '</ul>
                    </div>',
                ];
            });

            return response()->json([
                'draw' => (int) $request->input('draw'),
                'recordsTotal' => $rows->count(),
                'recordsFiltered' => $rows->count(),
                'data' => $data,
            ]);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = helperTrans("ingaz.packages");
        $data["addButtonText"] = helperTrans("ingaz.packages");
        $data["modalType"] = "";
        return view($this->folderPath . '.index', $data);

    }

    private function resolveNationalityLabel(objModel $row): string
    {
        if ($row->nationality) {
            return $row->nationality->sub_title ?? $row->nationality->title ?? '-';
        }

        if ($row->nationality_id) {
            return 'جنسية غير موجودة';
        }

        return 'جميع الجنسيات';
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request,NationalityAction $act)
    {
        if ($request->ajax()) {
            $data['nationalities'] = $act->getNationalities();
            $returnHTML = view($this->folderPath . ".create",$data)->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(PackageRequest $request, objAction $action)
    {

        $data = $request->only($this->postData);
        $data = $action->store($data);
        return jsonSuccess($data);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $model = objModel::with('nationality')->findOrFail($id);
        $returnHTML = view($this->folderPath . '.show', ['obj' => $model])->render();

        return jsonSuccess(["html" => $returnHTML]);
    }

    public function toggleVisibility($id)
    {
        $model = objModel::findOrFail($id);
        $model->is_show = !$model->is_show;
        $model->update();
        return response()->json(['success' => true]);
    }

    public function offer($id)
    {
        $obj = objModel::findOrFail($id);
        $returnHTML = view($this->folderPath . '.offer', compact('obj'))->render();

        return jsonSuccess(["html" => $returnHTML]);
    }

    public function updateOffer(Request $request, $id)
    {
        $data = $request->validate([
            'offer_amount' => 'nullable|numeric|min:0',
            'offer_starts_at' => 'nullable|date',
            'offer_ends_at' => 'nullable|date|after_or_equal:offer_starts_at',
            'offer_is_active' => 'nullable|boolean',
            'offer_description' => 'nullable|string|max:500',
        ]);

        $data['offer_is_active'] = (bool) ($data['offer_is_active'] ?? false);
        $data['offer_amount'] = isset($data['offer_amount']) && $data['offer_amount'] !== ''
            ? round((float) $data['offer_amount'], 2)
            : null;

        objModel::findOrFail($id)->update($data);

        return jsonSuccess();
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request,NationalityAction $act, $id)
    {
        if ($request->ajax()) {
            $data['obj'] = objModel::findorfail($id);
            $data['nationalities'] = $act->getNationalities();
            $returnHTML = view($this->folderPath . '.edit')
                ->with($data)->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(PackageRequest $request, objAction $action, $id)
    {
        $updatedData = $request->only($this->postData);
        $data = $action->updatePackage($id, $updatedData);
        return jsonSuccess($data);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, objAction $action)
    {
        $action->delete($id);
        return jsonSuccess();
    }
}
