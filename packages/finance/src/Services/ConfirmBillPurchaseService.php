<?php

namespace Nami\Finance\Services;

use Illuminate\Support\Facades\Auth;
use Nami\Finance\Enums\AccountTypesEnum;
use Nami\Finance\Enums\ConstraintTypiesEnum;
use Nami\Finance\Enums\PurchaseTypeEnum;
use Nami\Finance\MainService;
use Nami\Finance\Models\Purchase;

class ConfirmBillPurchaseService extends MainService
{

    public function __construct(
        Purchase                           $model,
        protected yearService              $yearService,
        protected ConstraintService        $constraintService,
        protected ConstraintDetailsService $constraintDetailsService,
        protected AccountService           $accountService,
        protected StockSettingService      $stockSetting,
        protected PurchaseService          $purchaseService,
    )
    {
        $this->model = $model;
        $this->fileFolder = 'bill_sales/';
        $this->files = ['file'];
    }


    public function confirmBillPurchase($id)
    {
        $purchaseData = $this->find($id)->toArray();
        if ($purchaseData['type'] == PurchaseTypeEnum::Purchase->value ) {
            $constraintType = ConstraintTypiesEnum::Purchases->value;
        } else {
            $constraintType = ConstraintTypiesEnum::BackPurchases->value;
        }

        // حفظ سجلات المدين والدائن
        $constraint = $this->storeSaleConstraint($purchaseData, $constraintType);
        // تعديل الفاتورة الي تم التأكيد
        $this->update($purchaseData["id"], ["constraint_id" => $constraint->id, "is_confirmed" => 1]);


        $bill = $this->find($id);
        // انشاء اذن اضافة مع الفاتورة
        if ($this->getStocksSetting()->create_additional_permit_with_confirmed_purchase_bill && $bill->movements()->count() == 0) {
             $stock_id = $bill->purchaseItem->first()->stock_id;
             $itemsData = $bill->purchaseItem;
             $billPurchaseData['code'] = $bill->code;
             $billPurchaseData['supplier_bill_number'] = $bill->supplier_bill_number;
             $billPurchaseData['supplier_id'] = $bill->supplier_id;
             $billPurchaseData['date'] = $bill->date;
             $billPurchaseData['notes'] = $bill->notes;
             $billPurchaseData['total_item'] = $bill->total_item;
             $billPurchaseData['file'] = $bill->file;
             $billPurchaseData['fn_stock_id'] = $stock_id;
             $billPurchaseData['type'] = $bill->type;
            // لو المنتجات هتتخزن في اكتر من مخزن يتم انشاء اكثر من اذن اضافة
            if ($stock_id == null){
                $this->purchaseService->saveStockMovement($itemsData, $billPurchaseData,$bill, true);
            }
            // لو المنتجات هتتخزن في مخزن واحد يتم انشاء اذن اضافة واحد
            else{
                $this->purchaseService->saveStockMovementForOneStock($itemsData, $billPurchaseData,$bill,$stock_id, true);
            }
        }
    }

    ############################### تسجيل حسابات المدين والدائن ###########################
    public function storeSaleConstraint($purchaseData, $constraintType)
    {
        ########## حفظ القيد #########
        $constraint = $this->storeMainConstraint($purchaseData, $constraintType);

        if ($purchaseData['type'] == PurchaseTypeEnum::Purchase->value ) {
            ########## (1) المورد دائن (يعني له فلوس  من الشركة) #########
            $this->storeDebtorConstraintDetails($constraint, $purchaseData['credit_supplier_id']);
            ###### (2) حفظ في حسابات المبيعات للشركة وكل منتج يدخل في الحساب الخاص بحساب المبيعات بتاعته #########
            $this->storeCreditorConstraintDetails($constraint, $purchaseData);
        } else {
            ########## (1) المورد دائن (يعني له فلوس  من الشركة) #########
            $this->storeBackDebtorConstraintDetails($constraint, $purchaseData['credit_supplier_id']);
            ###### (2) حفظ في حسابات المبيعات للشركة وكل منتج يدخل في الحساب الخاص بحساب المبيعات بتاعته #########
            $this->storeBackCreditorConstraintDetails($constraint, $purchaseData);
        }

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


    ########## (1) المورد دائن (يعني له فلوس  من الشركة) #########
    public function storeDebtorConstraintDetails($constraint, $creditAccountID)
    {
        //  store debtor account constraint
        $this->constraintDetailsService->store([
            "constraint_id" => $constraint->id,
            "date" => $constraint->date,
            "type" => 'creditor',
            "account_id" => $creditAccountID,
            'debtor_value' => 0,
            'creditor_value' => $constraint->value,
            'statement' => $constraint->statement,
        ]);
    }

    public function storeBackDebtorConstraintDetails($constraint, $creditAccountID)
    {
        //  store debtor account constraint
        $this->constraintDetailsService->store([
            "constraint_id" => $constraint->id,
            "date" => $constraint->date,
            "type" => 'creditor',
            "account_id" => $creditAccountID,
            'debtor_value' => 0,
            'creditor_value' => $constraint->value,
            'statement' => $constraint->statement,
        ]);
    }



    ###### (2) حفظ في حسابات المبيعات للشركة وكل منتج يدخل في الحساب الخاص بحساب المبيعات بتاعته #########
    public function getAccountId($type) {
        $account = $this->accountService->findByType($type);
        if (isset($account)) {
            return $account->id;
        }
        return null;

    }
    public function storeCreditorConstraintDetails($constraint, $saleData)
    {
        $this->constraintDetailsService->store([
            "constraint_id" => $constraint->id,
            "date" => $constraint->date,
            "type" => 'debtor',
            "account_id" => $this->getAccountId(AccountTypesEnum::Purchases->value),
            'debtor_value' => $saleData["final_total"],
            'creditor_value' => 0,
            'statement' => $constraint->statement,
        ]);
    }

    public function storeBackCreditorConstraintDetails($constraint, $saleData)
    {
        $this->constraintDetailsService->store([
            "constraint_id" => $constraint->id,
            "date" => $constraint->date,
            "type" => 'debtor',
            "account_id" => $this->getAccountId(AccountTypesEnum::BackPurchases->value),
            // "account_id" => $this->accountService->findByType(AccountTypesEnum::BackPurchases->value)->id,
            'debtor_value' => $saleData["final_total"],
            'creditor_value' => 0,
            'statement' => $constraint->statement,
        ]);
    }

    public function getStocksSetting()
    {
        return $this->stockSetting->getSetting();
    }

}
