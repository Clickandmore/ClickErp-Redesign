<?php

namespace Nami\Hr\Services;


use Nami\Hr\Enums\AdvanceMethod;
use Carbon\Carbon;
use Nami\Hr\MainService;
use Nami\Hr\Models\Advance;
use Nami\Hr\Models\AdvanceValue;

class AdvanceService extends MainService
{
    public function __construct()
    {
        $this->model = new Advance();
        $this->fileFolder = "images/admins/advances/";
        $this->files =['file'];
    }

    public function getDataTable($user_id = null)
    {
        if($user_id == null){
            $user_id =  @auth('admin')->user()->employee->id;
        }
        return parent::getDataTable()->where('employee_id', $user_id);
    }

    public function getAdvanceMethod()
    {
        return AdvanceMethod::cases();
    }

    public function storeAdvance($data, $request)
    {
        if ($request->payment_method == AdvanceMethod::MonthlyInstallment->value) {
            $fromDate = Carbon::createFromFormat('Y-m', $request->from_date)->startOfMonth();
            $toDate = Carbon::createFromFormat('Y-m', $request->to_date)->endOfMonth();
            $data['from_date'] = $fromDate->format('Y-m-d');
            $data['to_date'] = $toDate->format('Y-m-d');
        } elseif ($request->payment_method == AdvanceMethod::DebtPayments)  {
            $data['from_date'] = $request->date_value[0];
            $data['to_date'] = $request->date_value[count($request->date_value) - 1];
        }
        $advace = $this->store($data);
        if ($request->payment_method == AdvanceMethod::MonthlyInstallment->value) {
            $this->storeMonthlyInstallment($advace, $request);
        } elseif ($request->payment_method == AdvanceMethod::DebtPayments->value) {
            $this->storeDebtPayments($advace, $request);
        }
        return $advace;
    }

    public function storeMonthlyInstallment($advace, $request) {
        $endOfMonth = Carbon::createFromFormat('Y-m', $request->from_date)->endOfMonth();
        $toDate = Carbon::createFromFormat('Y-m', $request->to_date)->endOfMonth();
        while ($endOfMonth->lte($toDate)) {
            $endOfMonth->day(30);
            AdvanceValue::create([
                'advance_id' => $advace->id,
                'amount' => $request->amount,
                'date' => $endOfMonth->format('Y-m-d')
            ]);
            $endOfMonth->addMonth();
        }
    }

    public function storeDebtPayments($advace, $request) {
        foreach ($request->date_value as $key => $date_value) {
            AdvanceValue::create([
                'advance_id' => $advace->id,
                'amount' => $request->amount_value[$key],
                'date' => $date_value
            ]);
        }
    }



    public function updateAdvance($id, $data, $request)
    {
        if ($request->payment_method == AdvanceMethod::MonthlyInstallment->value) {
            $fromDate = Carbon::createFromFormat('Y-m', $request->from_date)->startOfMonth();
            $toDate = Carbon::createFromFormat('Y-m', $request->to_date)->endOfMonth();
            $data['from_date'] = $fromDate->format('Y-m-d');
            $data['to_date'] = $toDate->format('Y-m-d');
        } elseif ($request->payment_method == AdvanceMethod::DebtPayments->value)  {
            $data['from_date'] = $request->date_value[0];
            $data['to_date'] = $request->date_value[count($request->date_value) - 1];
        }
        $this->update($id,$data);
        $advace = $this->find($id);
        if ($request->payment_method == AdvanceMethod::MonthlyInstallment->value) {
            AdvanceValue::where('advance_id', $id)->delete();
            $this->storeMonthlyInstallment($advace, $request);
        } elseif ($request->payment_method == AdvanceMethod::DebtPayments->value) {
            AdvanceValue::where('advance_id', $id)->delete();
            $this->storeDebtPayments($advace, $request);
        }
        return $advace;
    }

    public function deleteAdvance($id)
    {
        $obj = $this->find($id);
        $this->delete($id);
    }

}
