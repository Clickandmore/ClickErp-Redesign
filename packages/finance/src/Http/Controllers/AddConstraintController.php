<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use App\Services\BranchAccessService;
use Illuminate\Http\Request;
use Nami\Finance\Http\Middleware\CheckActiveYear;
use Nami\Finance\Http\Requests\ConstraintRequest as ObjRequest;
use Nami\Finance\Services\ConstraintService as ObjService;
use Nami\Finance\Services\YearService;

class AddConstraintController extends Controller
{
    protected string $folderPath = "finance::finance.constraint";
    protected string $mainRoute = "finance.add-constraint";

    public function __construct()
    {
        $this->middleware(CheckActiveYear::class)->only(['create', 'edit']);
    }

    public function index(Request $request, ObjService $service)
    {
        //
    }

    public function create(Request $request, ObjService $service)
    {
        $branchAccess = app(BranchAccessService::class);
        $branchSelector = $branchAccess->selectorData($request);
        $selectedBranchId = $branchSelector['selected_branch_id'];

        return view($this->folderPath . ".add_constraint")->with([
            // صاحب رؤية كل الفروع يختار الفرع أولاً؛ بعدها لا تظهر له في
            // القيد إلا الحسابات المشتركة وحسابات الفرع الذي اختاره.
            'accounts' => $branchSelector['enabled'] && $branchSelector['can_view_all_branches'] && $selectedBranchId === null
                ? collect()
                : $service->getAccounts(null, $selectedBranchId),
            'lastCode' => $service->lastCode(),
            "bladeTitle" => __("finance::finance.Add daily constraint"),
            "cost_centers" => $service->getCostCenters($selectedBranchId),
            'branchSelector' => $branchSelector,
            'selectedBranchId' => $selectedBranchId,
            'createRoute' => route($this->mainRoute . ".create"),
            "storeRoute" => route($this->mainRoute . ".store", $selectedBranchId ? ['branch_id' => $selectedBranchId] : []),
            "storeBaseRoute" => route($this->mainRoute . ".store"),
            "branchOptionsRoute" => route($this->mainRoute . ".branch-options"),
            "cost_center_required" => null,
            "indexRoute" => route("finance.constraints.index"),
        ]);
    }

    public function branchOptions(Request $request, ObjService $service)
    {
        $branchAccess = app(BranchAccessService::class);
        $branchSelector = $branchAccess->selectorData($request);
        $selectedBranchId = $branchSelector['selected_branch_id'];

        if (($branchSelector['enabled'] ?? false) && ($branchSelector['can_view_all_branches'] ?? false) && $selectedBranchId === null) {
            return response()->json([
                'message' => 'اختر الفرع أولاً.',
                'accounts' => [],
                'cost_centers' => [],
            ], 422);
        }

        return response()->json([
            'selected_branch_id' => $selectedBranchId,
            'accounts' => $service->getAccounts(null, $selectedBranchId)
                ->map(fn ($account) => [
                    'id' => (int) $account->id,
                    'name' => (string) $account->name,
                    'text' => $account->code . ' - ' . $account->name,
                ])
                ->values(),
            'cost_centers' => $service->getCostCenters($selectedBranchId)
                ->map(fn ($costCenter) => [
                    'id' => (int) $costCenter->id,
                    'title' => (string) $costCenter->title,
                ])
                ->values(),
        ]);
    }

    public function store(ObjRequest $request, ObjService $service, YearService $yearService)
    {
        $data = $request->validated();
        $branchAccess = app(BranchAccessService::class);
        if ($branchAccess->enabled()) {
            // لا نثق في branch_id القادم من النموذج: الموظف المقيد يُثبت على
            // فرعه، وصاحب رؤية الجميع لا يُقبل منه إلا فرع موجود اختاره صراحة.
            $data['branch_id'] = $branchAccess->selectedBranchId($request);
        }
        if (!$yearService->checkDateRange($data['date'])) {
            return jsonValid(['error' => [__("finance.the year is not exist")]], '');
        }
        $getActiveYear = $yearService->getActiveYear();
        $storeConstraint = $service->storeConstraint($data, $getActiveYear);
        $service->storeConstraintDetails($data['account_id'], $storeConstraint->id, $data);
        $service->storeFiles($storeConstraint, $data);
        return jsonSuccess([
            "id" => $storeConstraint->id,
            "show_url" => route($this->mainRoute . ".show", [
                "add_constraint" => $storeConstraint->id,
                "saved" => 1,
            ]),
            "create_url" => route($this->mainRoute . ".create"),
            "index_url" => route("finance.constraints.index"),
        ], __("finance::finance.constraint_saved_successfully"));
    }

    public function show($id, Request $request, ObjService $service)
    {
        if ($request->boolean('edit')) {
            return redirect()->route($this->mainRoute . ".edit", $id);
        }

        $constraint = $service->find($id)->load([
            'constraintDetails.account',
            'constraintDetails.costCenter',
            'files',
            'logs.admin',
        ]);

        $view = $request->boolean('print')
            ? $this->folderPath . ".print_constraint"
            : $this->folderPath . ".show_constraint";

        return view($view)->with([
            "obj" => $constraint,
            "bladeTitle" => __("finance::finance.constraint_details_page") . " #{$constraint->code}",
            "createRoute" => route($this->mainRoute . ".create"),
            "editRoute" => route($this->mainRoute . ".edit", $constraint->id),
            "indexRoute" => route("finance.constraints.index"),
        ]);
    }

    public function edit($id, Request $request, ObjService $service)
    {
        $constraint = $service->find($id)->load([
            'constraintDetails.account',
            'constraintDetails.costCenter',
            'files',
        ]);

        return view($this->folderPath . ".edit_constraint")->with([
            "obj" => $constraint,
            'accounts' => $service->getAccountsForConstraint($constraint),
            "bladeTitle" => __("buttons.edit") . " " . __("finance::finance.constraint_details_page") . " #{$constraint->code}",
            "cost_centers" => $service->getCostCenters(),
            "updateRoute" => route($this->mainRoute . ".update", $id),
            "indexRoute" => route("finance.constraints.index"),
            "showRoute" => route($this->mainRoute . ".show", $id),
            "closeRoute" => route($this->mainRoute . ".show", $id),
            "cost_center_required" => null,
            "constraintFormClass" => "constraint-edit-page-form",
            "inlineConstraintEdit" => true,
        ]);
    }

    public function update(ObjRequest $request, $id, ObjService $service, YearService $yearService)
    {
        $data = $request->validated();
        if (!$yearService->checkDateRange($data['date'])) {
            return jsonValid(['error' => [__("finance.the year is not exist")]], '');
        }

        $getActiveYear = $yearService->getActiveYear();
        $before = $service->constraintSnapshot($service->find($id));

        $updateConstraint = $service->updateConstraint($id, $data, $request, $getActiveYear);
        $service->updateConstraintDetails($updateConstraint->id, $data['account_id'], $data);
        $service->storeFiles($updateConstraint, $data);
        $service->logConstraintActivity($updateConstraint->refresh(), 'updated', __('finance::finance.constraint_updated_log_description'), [
            'before' => $before,
            'after' => $service->constraintSnapshot($updateConstraint),
        ]);
        return jsonSuccess([
            "id" => $updateConstraint->id,
            "show_url" => route($this->mainRoute . ".show", [
                "add_constraint" => $updateConstraint->id,
                "saved" => 1,
            ]),
        ], null, 201);
    }


    public function destroy($id, ObjService $service)
    {
        try {
            $service->deleteConstraint((int) $id);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $errors = $exception->errors();
            $message = (string) (collect($errors)->flatten()->first() ?: 'لا يمكن حذف هذا القيد.');

            return jsonValid($errors, $message);
        }

        return jsonSuccess();
    }

    protected function perData($service, $id)
    {
        $constraint = $service->find($id);

        return [
            "obj" => $constraint,
            'accounts' => $service->getAccountsForConstraint($constraint),
            'lastCode' => $service->lastCode(),
            "bladeTitle" => __("finance.constraints"),
            "cost_centers" => $service->getCostCenters(),
            "updateRoute" => route($this->mainRoute . ".update", $id),
            "cost_center_required" => null,
        ];

    }
}
