<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
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
        return view($this->folderPath . ".add_constraint")->with([
            'accounts' => $service->getAccounts(),
            'lastCode' => $service->lastCode(),
            "bladeTitle" => __("finance::finance.Add daily constraint"),
            "cost_centers" => $service->getCostCenters(),
            "storeRoute" => route($this->mainRoute . ".store"),
            "cost_center_required" => null,
            "indexRoute" => route("finance.constraints.index"),
        ]);
    }

    public function store(ObjRequest $request, ObjService $service, YearService $yearService)
    {
        $data = $request->validated();
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
        $constraint = $service->find($id)->load(['logs.admin']);

        return view($this->folderPath . ".show_constraint")->with([
            "obj" => $constraint,
            'accounts' => $service->getAccountsForConstraint($constraint),
            "cost_centers" => $service->getCostCenters(),
            "bladeTitle" => __("finance::finance.constraint_details_page") . " #{$constraint->code}",
            "createRoute" => route($this->mainRoute . ".create"),
            "editRoute" => route($this->mainRoute . ".edit", $constraint->id),
            "updateRoute" => route($this->mainRoute . ".update", $constraint->id),
            "indexRoute" => route("finance.constraints.index"),
            "cost_center_required" => null,
            "constraintFormClass" => "constraint-inline-edit-form",
            "inlineConstraintEdit" => true,
        ]);
    }

    public function edit($id, Request $request, ObjService $service)
    {
        $data = $this->perData($service, $id);
        return view($this->folderPath . ".edit_constraint")->with($data);
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
        $service->deleteConstraint($id);

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
