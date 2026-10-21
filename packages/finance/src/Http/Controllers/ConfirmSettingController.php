<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use App\Services\ingaz\RentalMaidFinanceService;
use App\Services\ingaz\ShelterLivingExpenseAccountingService;
use App\Services\ingaz\NationalityFinanceAccountService;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nami\Finance\Http\Requests\ConfirmSettingRequest as ObjRequest;
use Nami\Finance\Services\ConfirmSettingService as ObjService;
use Throwable;

class ConfirmSettingController extends Controller
{
    public string $folderPath = "finance::finance.confirm_settings";
    public string $mainRoute = "finance.confirm-settings";

    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View|\Illuminate\Foundation\Application|JsonResponse|\Illuminate\View\View
     * @throws Exception
     */

    public function index(
        Request $request,
        ObjService $service,
        RentalMaidFinanceService $rentalMaidFinanceService,
        ShelterLivingExpenseAccountingService $shelterLivingExpenseAccountingService,
        NationalityFinanceAccountService $nationalityFinanceAccountService
    )
    {
        $settings = $service->getAllSettings();
        $musanedIntermediateBranchAccounts = \App\Support\MusanedIntermediateAccountSettings::settingsForForm();
        $nationalityAccountAssignments = $nationalityFinanceAccountService->getNationalityAssignments($settings);
        $settings = $nationalityFinanceAccountService->persistDetectedAssignments(
            $settings,
            $nationalityAccountAssignments
        );
        $rentalMaidExpenseDefinitions = $rentalMaidFinanceService->getSettingDefinitions($settings);
        $shelterLivingExpenseAccountId = $shelterLivingExpenseAccountingService->defaultExpenseAccountId();
        $additionalSelectedAccountIds = collect($rentalMaidExpenseDefinitions)
            ->flatMap(fn (array $definition) => [
                $definition['selected_account_id'] ?? null,
                $definition['selected_prepaid_account_id'] ?? null,
            ])
            ->push($shelterLivingExpenseAccountId)
            ->filter()
            ->all();

        $data = [
            "bladeTitle" => __("finance::finance.Account settings"),
            "addButtonText" => __("finance::finance.Account settings"),
            "storeRoute" => route($this->mainRoute . ".store"),
            "obj" => $settings,
            "selected_accounts" => $service->getSelectedAccounts($settings, $additionalSelectedAccountIds),
            "tax_settings" => $service->getTaxSetting(),
            "rental_maid_expense_definitions" => $rentalMaidExpenseDefinitions,
            "shelter_living_expense_account_id" => $shelterLivingExpenseAccountId,
            "nationality_account_definitions" => $nationalityFinanceAccountService->getSettingDefinitions($settings),
            "nationality_account_assignments" => $nationalityAccountAssignments,
            "nationality_account_options_route" => route($this->mainRoute . '.account-options'),
            "branchesEnabled" => \App\Support\MusanedIntermediateAccountSettings::enabled(),
            "musanedIntermediateAccountMode" => \App\Support\MusanedIntermediateAccountSettings::mode(),
            "musanedIntermediateBranchAccounts" => $musanedIntermediateBranchAccounts,
            "branches" => \App\Support\MusanedIntermediateAccountSettings::enabled()
                ? \App\Models\Branch::query()->active()->ordered()->get(['id', 'name_ar', 'name_en', 'code'])
                : collect(),
        ];
        return view($this->folderPath . '.index', $data);
    }

    /**
     * @param Request $request
     * @param ObjService $service
     * @return JsonResponse|void
     * @throws Throwable
     */
    public function create(Request $request, ObjService $service)
    {
        //
    }

    public function accountOptions(Request $request, ObjService $service): JsonResponse
    {
        return response()->json([
            'results' => $service->searchAccounts(
                (string) $request->input('q', ''),
                (string) $request->input('scope', '')
            ),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param ObjRequest $request
     * @param ObjService $service
     * @return JsonResponse
     */
    public function store(ObjRequest $request, ObjService $service)
    {
        $dataInsert = $request->validated();
        $service->storeData($dataInsert);
        return jsonSuccess(null,null,201);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return void
     */
    public function show(int $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @param Request $request
     * @param ObjService $service
     * @return JsonResponse|void
     * @throws Throwable
     */
    public function edit(int $id, Request $request, ObjService $service)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param ObjRequest $request
     * @param int $id
     * @param ObjService $service
     * @return JsonResponse
     */
    public function update(ObjRequest $request, int $id, ObjService $service)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @param ObjService $service
     * @return JsonResponse
     */
    public function destroy(int $id, ObjService $service)
    {
        //
    }
}
