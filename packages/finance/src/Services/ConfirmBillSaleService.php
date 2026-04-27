<?php

namespace Nami\Finance\Services;

use Illuminate\Support\Facades\Auth;
use Nami\Finance\Enums\ConstraintTypiesEnum;
use Nami\Finance\MainService;
use Nami\Finance\Models\Sale;

class ConfirmBillSaleService extends MainService
{

    public function __construct(
        Sale                               $model,
        protected YearService              $yearService,
        protected ConstraintService        $constraintService,
        protected ConstraintDetailsService $constraintDetailsService,
        protected AccountService           $accountService,
    )
    {
        $this->model = $model;
        $this->fileFolder = 'bill_sales/';
        $this->files = ['file'];
    }


    public function confirmBillSale($id)
    {
        $saleData = $this->find($id)->toArray();
        $constraintType = ConstraintTypiesEnum::Sales->value;

        // حفظ سجلات المدين والدائن
        $constraint = $this->storeSaleConstraint($saleData, $constraintType);
        // تعديل الفاتورة الي تم التأكيد
        return $this->update($saleData["id"], ["constraint_id" => $constraint->id, "is_confirmed" => 1]);
    }

    ############################### تسجيل حسابات المدين والدائن ###########################
    public function storeSaleConstraint($saleData, $constraintType)
    {
        ########## حفظ القيد #########
        $constraint = $this->storeMainConstraint($saleData, $constraintType);

        ########## (1) العميل مدين (يعني عليه فلوس للشركة) #########
        $this->storeDebtorConstraintDetails($constraint,$saleData['debit_client_id']);


        ###### (2) حفظ في حسابات المبيعات للشركة وكل منتج يدخل في الحساب الخاص بحساب المبيعات بتاعته #########
        $this->storeCreditorConstraintDetails($constraint,$saleData);
        ############################################################################

        return $constraint;
    }

    public function storeMainConstraint($saleData, $constraintType)
    {
        $constraint = $this->constraintService->store([
            "type" => $constraintType,
            "value" => $saleData["final_total"],
            "date" => $saleData["date"],
            "statement" => $saleData["notes"],
            "year_id" => $saleData["year_id"] ?? $this->yearService->getActiveYear()->id,
            "created_by_id" => Auth::guard('admin')->user()->id,
            "code" => $this->constraintService->lastCode(),
        ]);
        return $constraint;
    }



    ########## (1) العميل مدين (يعني عليه فلوس للشركة) #########
    public function storeDebtorConstraintDetails($constraint,$debitAccountID)
    {
        //  store debtor account constraint
        $this->constraintDetailsService->store([
            "constraint_id"  => $constraint->id,
            "date"           => $constraint->date,
            "type"           => 'debtor',
            "account_id"     => $debitAccountID,
            'debtor_value'   => $constraint->value,
            'creditor_value' => 0,
            'statement'      => $constraint->statement,
        ]);
    }


    ###### (2) حفظ في حسابات المبيعات للشركة وكل منتج يدخل في الحساب الخاص بحساب المبيعات بتاعته #########
    public function storeCreditorConstraintDetails($constraint,$saleData)
    {
        $items = $this->find($saleData['id'])->items;
        foreach ($items as $itemRow){
            $this->constraintDetailsService->store([
                "constraint_id" => $constraint->id,
                "date" => $constraint->date,
                "type" => 'creditor',
                "account_id" => $itemRow->item->sale_account_id,
                'debtor_value' => 0,
                'creditor_value' => $itemRow->final_total,
                'statement' => $constraint->statement,
            ]);
        }
    }

}
