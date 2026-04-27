<?php

namespace Nami\Finance\Services;

use Illuminate\Support\Facades\Auth;
use Nami\Finance\Enums\ConstraintTypiesEnum;
use Nami\Finance\MainService;
use Nami\Finance\Models\AccountTransfer as Model;

class AccountTransferService extends MainService
{

    public function __construct(
        Model                              $model,
        protected AccountService           $accountService,
        protected YearService              $yearService,
        protected ConstraintService        $constraintService,
        protected CostCenterService        $costCenterService,
        protected ConstraintDetailsService $constraintDetailsService,
        protected AccountTransferFileService $accountTransferFileService,
        protected ConfirmSettingService $confirmSettingService

    )
    {
        $this->model = $model;
    }

    public function getAdminID()
    {
        return Auth::guard('admin')->user()->id;
    }

    public function getBoxsAndBanks()
    {
        return $this->accountService->getBoxsAndBanksAccounts();
    }

    public function getCostCenters()
    {
        return $this->costCenterService->get();
    }

    public function storeTransfer($transferData, $year)
    {
        $transferData['year_id'] = $year->id;
        return $this->store($transferData);
    }

    public function checkIsConfirmSetting($accountTransfer, $type)
    {
        $isConfirm = $this->confirmSettingService->getConfirmSetting($type);
        if (isset($isConfirm) && $isConfirm->value == "off") {
            $this->confirmTransfer($accountTransfer->id);
            $lastCode = $this->constraintService->lastCode();
            $admin_id = $this->getAdminID();
            $this->storeTransferConstraint($accountTransfer,$lastCode,$admin_id);
        }

    }

    public function storeFiles($transfer, $data)
    {
        if (isset($data['file'])) {
            $this->accountTransferFileService->storeFiles($transfer, $data['file']);
        }
        return true;
    }

    public function updateTransfer($id, $transferData, $year)
    {
        $transferData['year_id'] = $year->id;
        return $this->update($id, $transferData);
    }

    public function confirmTransfer($id)
    {
         $this->model->find($id)->update(['is_confirm' => 1]);
         return $this->model->find($id);
    }

    public function storeTransferConstraint($transferData,$lastCode,$admin_id)
    {
        // store constraint
        $constraint = $this->constraintService->store([
            "type" => ConstraintTypiesEnum::TransferEntry->value,
            "value" => $transferData["value"],
            "date" => $transferData["date"],
            'code' => $lastCode,
            "statement" => $transferData["statement"],
            'created_by_id' => $admin_id,
            'year_id' => $transferData["year_id"],
        ]);
        //  store debtor account constraint
        $this->constraintDetailsService->store([
            "constraint_id" => $constraint->id,
            "date" => $constraint->date,
            "type" => 'debtor',
            "account_id" => $transferData["debit_account_id"],
            'debtor_value' =>$transferData["value"],
            'creditor_value' => 0,
            'year_id' => $transferData["year_id"],
            'statement' => $constraint->statement,
        ]);
        //  store creditor account constraint
        $this->constraintDetailsService->store([
            "constraint_id" => $constraint->id,
            "date" => $constraint->date,
            "type" => 'creditor',
            "account_id" => $transferData["credit_account_id"],
            'debtor_value' => 0,
            'creditor_value' =>$transferData["value"],
            'year_id' => $transferData["year_id"],
            'statement' => $constraint->statement,
        ]);
        // set constraint_id on freelancer Work
        $this->update($transferData["id"], ["constraint_id" => $constraint->id]);
    }

    public function updateTransferConstraint($transferData){

        $constraint_id = $transferData["constraint_id"];
        if (!$constraint_id) {
            return;
        }
        // update constraint
        $this->constraintService->update($constraint_id,[
            "type" => ConstraintTypiesEnum::TransferEntry->value,
            "value" => abs($transferData["value"]),
            "date" => $transferData["date"],
            "statement" => $transferData["statement"],
        ]);
        //  update debtor account constraint
        $this->constraintDetailsService->updateWhere([
            ["constraint_id", '=', $constraint_id],
            ["type", '=', 'debtor'],
        ],[
            "date" => $transferData["date"],
            "account_id" => $transferData["debit_account_id"],
            'debtor_value' => $transferData["value"],
            'statement' => $transferData["statement"],
        ]);
        //  update creditor account constraint
        $this->constraintDetailsService->updateWhere([
            ["constraint_id", '=', $constraint_id],
            ["type", '=', 'creditor'],
        ],[
            "date" =>$transferData["date"],
            "account_id" => $transferData["credit_account_id"],
            'creditor_value' => $transferData["value"],
            'statement' => $transferData["statement"],
        ]);

    }
}
