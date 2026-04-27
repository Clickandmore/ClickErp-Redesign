<?php

namespace Nami\Finance\Services;

use Nami\Finance\Enums\AccountTypesEnum;
use Nami\Finance\Enums\BondCheckStatusEnum;
use Nami\Finance\Enums\BondTypes;
use Nami\Finance\Enums\ConstraintTypiesEnum;
use Nami\Finance\MainService;
use Nami\Finance\Models\BondChecks as Model;

class ExchangeCheckService extends MainService
{

    function __construct(Model               $model
        , protected ConstraintService        $constraintService
        , protected YearService              $yearService
        , protected ConstraintDetailsService $constraintDetailsService
        , protected AccountService           $accountService
    )
    {
        $this->model = $model;
    }

    public function filterChecks($request, $dataTable)
    {
        return $dataTable
            ->when($request->filled('from_date'), function ($query) use ($request) {
                $query->where('check_date', '>=', $request->from_date);
            })
            ->when($request->filled('to_date'), function ($query) use ($request) {
                $query->where('check_date', '<=', $request->to_date);
            })
            ->when($request->filled('cost_center_id'), function ($query) use ($request) {
                $query->whereHas('bond', function ($q) use ($request) {
                    $q->where('cost_center_id', $request->cost_center_id);
                });
            })
            ->when($request->filled('debit_account_id'), function ($query) use ($request) {
                $query->whereHas('bond', function ($q) use ($request) {
                    $q->where('debit_account_id', $request->debit_account_id);
                });
            })
            ->when($request->filled('credit_account_id'), function ($query) use ($request) {
                $query->whereHas('bond', function ($q) use ($request) {
                    $q->where('credit_account_id', $request->credit_account_id);
                });
            });

    }

    public function getExchangeCheckDataTable()
    {
        return $this->model->query()
            ->whereHas('bond', function ($q) {
                $q->where('type', BondTypes::Sarf)->where('is_confirm', 1);
            })
            ->latest();
    }

    public function refuseExchangeCheck($id, $data)
    {
        $bondCheck = $this->find($id);
        $mainBond = $bondCheck->bond;
        $oldConstraint = $this->getOldConstraint($mainBond->constraint_id);

        $newConstraint = $this->createNewConstraint($mainBond->constraint_id, $data['date'], $data['refuse_reason']);
        $this->swapConstraintDetails($oldConstraint, $newConstraint, $data['date']);

        $this->updateBondCheck($bondCheck, $data['refuse_reason'], $newConstraint->id);
    }

    private function getOldConstraint($constraintId)
    {
        $constraint = $this->constraintService->findToArray($constraintId);
        $constraint['constraintDetails'] = $this->constraintService->find($constraintId)->constraintDetails()->get();
        return $constraint;
    }

    private function createNewConstraint($constraintId, $date, $statement, $con_type = ConstraintTypiesEnum::SarfBond)
    {
        $oldConstraint = $this->getOldConstraint($constraintId);
        $activeYear = $this->yearService->getActiveYear();
        $oldConstraint['statement'] = $statement;
        $oldConstraint['date'] = $date;
        $oldConstraint['code'] = $this->constraintService->lastCode();
        return $this->constraintService->storeConstraint($oldConstraint, $activeYear, $con_type);
    }

    private function swapConstraintDetails($oldConstraint, $newConstraint, $date)
    {
        $oldConstraintDetails = $oldConstraint['constraintDetails'];
        $firstDetails = $oldConstraintDetails->where('debtor_value', '!=', 0)->first()->toArray();
        $secondDetails = $oldConstraintDetails->where('creditor_value', '!=', 0)->first()->toArray();

        $this->storeConstraintDetails($secondDetails, $firstDetails, $newConstraint->id, $newConstraint->statement, $date);
        $this->storeConstraintDetails($firstDetails, $secondDetails, $newConstraint->id, $newConstraint->statement, $date);
    }

    private function storeConstraintDetails($details, $swapDetails, $constraintId, $statement, $date)
    {
        $details['year_id'] = $this->yearService->getActiveYear()->id;
        $details['account_id'] = $swapDetails['account_id'];
        $details['debtor_value'] = $swapDetails['creditor_value'];
        $details['creditor_value'] = $swapDetails['debtor_value'];
        $details['constraint_id'] = $constraintId;
        $details['statement'] = $statement;
        $details['date'] = $date;

        $this->constraintDetailsService->store($details);
    }

    private function updateBondCheck($bondCheck, $refuseReason, $constraintId)
    {
        $bondCheck->update([
            'constraint_id' => $constraintId,
            'refuse_reason' => $refuseReason,
            'status' => BondCheckStatusEnum::RefuseSarf->value,
        ]);
    }

    public function acceptExchangeCheck($id)
    {
        $bondCheck = $this->find($id);
        $mainBond = $bondCheck->bond;
        $statement = "تحصيل شيك بقيمة {$mainBond->net} للشيك رقم {$bondCheck->check_num} رقم السند {$mainBond->code} بتاريخ {$mainBond->date}";
        $con_type = ConstraintTypiesEnum::CheckCollection;
        $newConstraint = $this->createNewConstraint($mainBond->constraint_id, date('Y-m-d'), $statement, $con_type);
        $this->createConstraintDetails($newConstraint, $mainBond);

        $bondCheck->update([
            'constraint_id' => $newConstraint->id,
            'status' => BondCheckStatusEnum::CollectSarf->value,
        ]);
    }

    private function createConstraintDetails($newConstraint, $mainBond)
    {
        $details = [
            'constraint_id' => $newConstraint->id,
            'date' => $newConstraint->date,
            'statement' => $newConstraint->statement,
            'year_id' => $newConstraint->year_id
        ];

        $details['type'] = 'creditor';
        $details['account_id'] = $this->accountService->findByType(AccountTypesEnum::ExchangePapers)->id;
        $details['creditor_value'] = $mainBond->net;
        $details['debtor_value'] = 0;
        $this->constraintDetailsService->store($details);

        $details['type'] = 'debtor';
        $details['account_id'] = $mainBond['debit_account_id'];
        $details['creditor_value'] = 0;
        $details['debtor_value'] = $mainBond->net;
        $this->constraintDetailsService->store($details);
    }
}
