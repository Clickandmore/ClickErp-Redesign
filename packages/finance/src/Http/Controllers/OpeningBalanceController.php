<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Services\OpeningBalanceService;
use Nami\Finance\Services\YearService;


class OpeningBalanceController extends Controller
{
    public string $folderPath = "finance::finance.opening_balance";


    public function index(request $request,OpeningBalanceService $service,YearService $yeService)
    {
//        return $service->getOpeningBalances($request->all());
        if ($request->ajax()) {
            $data['data'] = $service->getOpeningBalances($request->all());
//            dd($data);
            $html = view($this->folderPath . '.table',$data)->render();
            return jsonSuccess(["html" => $html]);
        }
        $data["bladeTitle"] = __("finance::finance.opening-balance");
        $data["years"] = $yeService->get();
        $data["accountLevels"] = $service->getAccountLevels();

        return view($this->folderPath . '.index', $data);
    }


    public function create()
    {

    }

    public function store(Request $request, OpeningBalanceService $service)
    {
        $data = $request->validate([
            'value' => ['required', 'numeric'],
            'date' => ['required', 'date'],
            'statement' => ['nullable', 'string'],
            'account_id' => ['required', 'array', 'min:1'],
            'account_id.*' => ['required', 'integer'],
            'account_type' => ['required', 'array'],
            'account_type.*' => ['required'],
            'debtor_value' => ['required', 'array'],
            'debtor_value.*' => ['required', 'numeric', 'min:0'],
            'creditor_value' => ['required', 'array'],
            'creditor_value.*' => ['required', 'numeric', 'min:0'],
            'year_id' => ['required', 'integer'],
        ]);

        if (
            count($data['account_id']) !== count($data['account_type'])
            || count($data['account_id']) !== count($data['debtor_value'])
            || count($data['account_id']) !== count($data['creditor_value'])
        ) {
            return response()->json([
                'message' => 'بيانات الحسابات غير مكتملة',
            ], 422);
        }

        $debtorTotal = array_sum(array_map('floatval', $data['debtor_value']));
        $creditorTotal = array_sum(array_map('floatval', $data['creditor_value']));

        foreach ($data['account_id'] as $key => $accountId) {
            $debtorValue = (float) ($data['debtor_value'][$key] ?? 0);
            $creditorValue = (float) ($data['creditor_value'][$key] ?? 0);

            if ($debtorValue > 0 && $creditorValue > 0) {
                return response()->json([
                    'message' => 'لا يمكن إدخال مدين ودائن لنفس الحساب في نفس صف الرصيد الافتتاحي',
                ], 422);
            }
        }

        if (abs($debtorTotal - $creditorTotal) > 0.00001) {
            return response()->json([
                'message' => 'يجب أن يكون إجمالي المدين مساوياً لإجمالي الدائن',
            ], 422);
        }

        $data['statement'] = filled($data['statement'] ?? null)
            ? trim($data['statement'])
            : '-';

        $service->storeFirstBalanceConstraint($data);

        return jsonSuccess();
    }

    public function show(string $id)
    {

    }

    public function edit(string $id)
    {

    }


    public function update(Request $request, string $id)
    {

    }


    public function destroy(string $id)
    {

    }
}
