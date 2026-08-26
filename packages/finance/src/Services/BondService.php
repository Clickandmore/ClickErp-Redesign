<?php

namespace Nami\Finance\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Nami\Finance\Enums\AccountTypesEnum;
use Nami\Finance\Enums\BondCheckStatusEnum;
use Nami\Finance\Enums\BondPaymentTypes;
use Nami\Finance\Enums\BondTypes;
use Nami\Finance\Enums\ConstraintTypiesEnum;
use Nami\Finance\MainService;
use Nami\Finance\Models\Bond;

class BondService extends MainService
{
    public function __construct(Bond      $model, protected AccountService $accountService
        , protected BondChecksService     $bondCheckService, protected BondPaymentService $bondPaymentService
        , protected ConstraintService     $constraintService, protected ConstraintDetailsService $constraintDetailsService
        , protected YearService           $yearService, protected CostCenterService $costCenterService
        , protected ConfirmSettingService $confirmSettingService
        , protected TaxSettingService     $taxSettingService
        , protected BondTaxService        $bondTaxService)
    {
        $this->model = $model;
        $this->fileFolder = 'bonds/';
        $this->files = ['file', 'transfer_file'];
    }

    public function findStandardBondByCode(string|int $code, BondTypes $type): Bond
    {
        return $this->model->newQuery()
            ->where('code', $code)
            ->where('type', $type->value)
            ->where('general_type', 0)
            ->firstOrFail();
    }

    public function getTaxSetting()
    {
        return $this->taxSettingService->get();
    }

    public function filterBonds($request, $dataTable)
    {
        if ($request->filled('bond_search')) {
            $search = trim((string) $request->bond_search);
            $dataTable->where(function ($query) use ($search) {
                $query->where('code', 'like', "%{$search}%")
                    ->orWhere('bond_book_num', 'like', "%{$search}%")
                    ->orWhere('constraint_id', 'like', "%{$search}%")
                    ->orWhere('statement', 'like', "%{$search}%")
                    ->orWhere('transfer_number', 'like', "%{$search}%");
            });
        }
        if ($request->filled('from_date')) {
            $dataTable->where('date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $dataTable->where('date', '<=', $request->to_date);
        }
        if ($request->filled('cost_center_id')) {
            $dataTable->where('cost_center_id', $request->cost_center_id);
        }
        if ($request->filled('debit_account_id')) {
            $dataTable->where('debit_account_id', $request->debit_account_id);
        }
        if ($request->filled('credit_account_id')) {
            $dataTable->where('credit_account_id', $request->credit_account_id);
        }
        if ($request->filled('payment_type')) {
            $dataTable->where('payment_type', $request->payment_type);
        }
        if ($request->filled('is_confirm')) {
            $dataTable->where('is_confirm', (int) $request->is_confirm);
        }
        return $dataTable;
    }

    public function getReportBondSarf()
    {
        return $this->getBondReportTotals(BondTypes::Sarf->value);
    }

    private function getBondReportTotals($type): array
    {
        $now = Carbon::now();
        $ranges = [
            'year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            'last_year' => [$now->copy()->subYear()->startOfYear(), $now->copy()->subYear()->endOfYear()],
            'quarterYearData' => [$now->copy()->startOfQuarter(), $now->copy()->endOfQuarter()],
            'last_quarterYearData' => [$now->copy()->subQuarter()->startOfQuarter(), $now->copy()->subQuarter()->endOfQuarter()],
            'month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'last_month' => [$now->copy()->subMonth()->startOfMonth(), $now->copy()->subMonth()->endOfMonth()],
            'day' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'last_day' => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()],
        ];

        $selects = [];
        $bindings = [];
        foreach ($ranges as $key => [$from, $to]) {
            $selects[] = "COALESCE(SUM(CASE WHEN date BETWEEN ? AND ? THEN value ELSE 0 END), 0) AS {$key}";
            $bindings[] = $from->toDateTimeString();
            $bindings[] = $to->toDateTimeString();
        }

        $totals = $this->model->query()
            ->where('type', $type)
            ->selectRaw(implode(', ', $selects), $bindings)
            ->first();

        return collect(array_keys($ranges))->mapWithKeys(fn ($key) => [
            $key => (object) ['total_value' => (float) ($totals->{$key} ?? 0)],
        ])->all();
    }


    public function getReportBondQabd()
    {
        return $this->getBondReportTotals(BondTypes::Qabd->value);
    }

    public function getBondArchiveSummary($type): object
    {
        return $this->model->query()
            ->where('type', $type)
            ->where('general_type', 0)
            ->selectRaw('COUNT(*) AS total_count')
            ->selectRaw('COALESCE(SUM(COALESCE(legacy_financial_movement, value)), 0) AS total_movement')
            ->first();
    }

    public function getBondPaymentTypies()
    {
        return BondPaymentTypes::cases();
    }

    public function getAccounts()
    {
        return $this->accountService->getAccountWithNoChild();
    }

    public function getCostCenters()
    {
        return $this->costCenterService->get();
    }

    public function getTaxRate($type)
    {
        return $this->confirmSettingService->getConfirmSetting($type);
    }

    /*
    * -----------------------------------------------------------------------------------------------------------------
    *
    * -----------------------------------------------------------------------------------------------------------------
    */

    public function storeBond($data)
    {
        // $bondData = $this->validInsertData($data);
        $data["bond_book_num"] = (isset($data['bond_book_num'])) ? $data['bond_book_num'] : " ";
        $data["created_by_id"] = Auth::guard('admin')->user()->id;
        $year = $this->yearService->getActiveYear();
        $data['year_id'] = $year->id ?? null;
        $bond = $this->store($data);
        if ($data["payment_type"] == BondPaymentTypes::Check->value) {
            $this->storeBondCheck($bond->id, $data);
        }
        return $bond;
    }

    public function storeBondCheck($bond_id, $data)
    {
        $year = $this->yearService->getActiveYear();
        return $this->bondCheckService->store([
            "bond_id" => $bond_id,
            "check_num" => $data["check_num"],
            "check_value" => $data["value"],
            "check_date" => $data["check_date"],
            "year_id" => $year->id ?? null,
            "status" => $data['status'],
        ]);
    }

    public function storeSarfBond($data)
    {
        $data["type"] = BondTypes::Sarf->value;
        $data["status"] = BondCheckStatusEnum::NewSarf->value;
        $bond = $this->storeBond($data);
        $this->storeBondTaxValue($data, $bond->id);
        return $bond;
    }

    public function checkIsConfirmSetting($data, $type)
    {
        $isConfirm = $this->confirmSettingService->getConfirmSetting($type);
        if (!isset($isConfirm) || $isConfirm->value == "off") {
            $this->confirmBond($data->id);
        }

    }

    public function storeQabdBond($data)
    {
        $data["type"] = BondTypes::Qabd->value;
        $data["status"] = BondCheckStatusEnum::NewQabd->value;
        $bond = $this->storeBond($data);
        $this->storeBondTaxValue($data, $bond->id);
        return $bond;
    }

    public function storeBondTaxValue($data, $bondId)
    {
        if ($data['is_tax'] == 1) {
            $dataInsert['bond_id'] = $bondId;
            $dataInsert['tax_rate'] = $data['tax_rate'];
            $dataInsert['tax_amount'] = $data['tax_amount'];
            $dataInsert['tax_setting_id'] = $data['tax_setting_id'];
            $dataInsert['tax_account_id'] = $data['tax_account_id'];
            $this->bondTaxService->storeBondTaxs($dataInsert);
        }
    }


    /*
    * -----------------------------------------------------------------------------------------------------------------
    *
    * -----------------------------------------------------------------------------------------------------------------
    */

    public function getEditPaymentTypeView($type, $bond_type)
    {
        $this->bondPaymentService->folderPath = "finance::finance.bond." . $bond_type . ".payment_types";;
        return $this->bondPaymentService->getTypeMap($type);
    }

    public function updateBond($id, $data)
    {
        $data["bond_book_num"] = (isset($data['bond_book_num'])) ? $data['bond_book_num'] : " ";
        $obj = $this->find($id);
        if ($obj->bondTax) {
            $obj->bondTax->delete();
        }
        $obj->update($data);
        $this->storeBondTaxValue($data, $id);
        if ($data["payment_type_old"] == BondPaymentTypes::Check->value && $data["payment_type_old"] != $data["payment_type"]) {
            $this->bondCheckService->deleteByBondId($id);
        }
        if ($data["payment_type"] == BondPaymentTypes::Check->value) {
            $this->updateBondCheck($id, $data);
        }
        $obj = $this->find($id);
        if ($obj->is_confirm == 1) {
            $constraintId = $obj->constraint_id;
            $obj->update(['constraint_id' => null]);
            $constraint = $this->constraintService->find($constraintId);
            $constraint->constraintDetails()->delete();
            $constraint->delete();
            $this->confirmBond($id);
        }
        $bond = $this->findToArray($id);
        return $bond;
    }

    public function updateBondCheck($bond_id, $data)
    {
        return $this->bondCheckService->updateOrCreate($bond_id, [
            "bond_id" => $bond_id,
            "check_num" => $data["check_num"],
            "check_value" => $data["value"],
            "check_date" => $data["check_date"],
            "status" => 0,
            "created_by_id" => auth('admin')->user()->id
        ]);
    }

    /*
    * -----------------------------------------------------------------------------------------------------------------
    *
    * -----------------------------------------------------------------------------------------------------------------
    */

    public function confirmBond($data)
    {
        $bond_id = $data;
        // dd($bond_id);
        $bondData = $this->model->with('bondTax')->find($bond_id)->toArray();
        $constraintType = ($bondData["type"] == BondTypes::Sarf->value) ? ConstraintTypiesEnum::SarfBond->value : ConstraintTypiesEnum::QapdBond->value;
        $constraint = $this->storeBondConstraint($bondData, $constraintType);
        return $this->update($bondData["id"], ["constraint_id" => $constraint->id, "is_confirm" => 1]);
    }

    public function storeBondConstraint($bondData, $constraintType)
    {
        $constraintAction = $this->constraintService;
        $constraintDetailsAction = $this->constraintDetailsService;
        $oldBold = $bondData;
        $bondData = $this->checkBondPayment($bondData);

        // store constraint
        $constraint = $constraintAction->store([
            "type" => $constraintType,
            "value" => $bondData["value"],
            "date" => $bondData["date"],
            "statement" => $bondData["statement"],
            "year_id" => $bondData["year_id"],
            "created_by_id" => Auth::guard('admin')->user()->id,
            "code" => $constraintAction->lastCode(),
        ]);
        $accounts = $this->getAccountIDForDetails($oldBold);
        if ($bondData['type'] == BondTypes::Sarf->value && $bondData['is_tax'] == 1) {
            $this->storeSarfConstraintIfExistTax($bondData, $constraintDetailsAction, $constraint, $accounts);
        } elseif ($bondData['type'] == BondTypes::Qabd->value && $bondData['is_tax'] == 1) {
            $this->storeQabdConstraintIfExistTax($bondData, $constraintDetailsAction, $constraint, $accounts);
        } else {
            //  store debtor account constraint
            $constraintDetailsAction->store([
                "constraint_id" => $constraint->id,
                "date" => $constraint->date,
                "type" => 'debtor',
                "account_id" => $accounts['debit_account_id'],
                'debtor_value' => $constraint->value,
                'creditor_value' => 0,
                'statement' => $constraint->statement,
            ]);
            //  store creditor account constraint
            $constraintDetailsAction->store([
                "constraint_id" => $constraint->id,
                "date" => $constraint->date,
                "type" => 'creditor',
                "account_id" => $accounts["credit_account_id"],
                'debtor_value' => 0,
                'creditor_value' => $constraint->value,
                'statement' => $constraint->statement,
            ]);
        }

        // set constraint_id on freelancer Work
        return $constraint;
    }

    public function storeSarfConstraintIfExistTax($bondData, $constraintDetailsAction, $constraint, $accounts)
    {
        $constraintDetailsAction->store([
            "constraint_id" => $constraint->id,
            "date" => $constraint->date,
            "type" => 'debtor',
            "account_id" => $accounts['debit_account_id'],
            'debtor_value' => $bondData['net'],
            'creditor_value' => 0,
            'statement' => $constraint->statement,
        ]);
        $constraintDetailsAction->store([
            "constraint_id" => $constraint->id,
            "date" => $constraint->date,
            "type" => 'debtor',
            "account_id" => $bondData['bond_tax']['tax_account_id'],
            'debtor_value' => $bondData['bond_tax']['tax_amount'],
            'creditor_value' => 0,
            'statement' => $constraint->statement,
        ]);
        $constraintDetailsAction->store([
            "constraint_id" => $constraint->id,
            "date" => $constraint->date,
            "type" => 'creditor',
            "account_id" => $accounts["credit_account_id"],
            'debtor_value' => 0,
            'creditor_value' => $constraint->value,
            'statement' => $constraint->statement,
        ]);
    }

    public function storeQabdConstraintIfExistTax($bondData, $constraintDetailsAction, $constraint, $accounts)
    {
        $constraintDetailsAction->store([
            "constraint_id" => $constraint->id,
            "date" => $constraint->date,
            "type" => 'debtor',
            "account_id" => $accounts['debit_account_id'],
            'debtor_value' => $constraint->value,
            'creditor_value' => 0,
            'statement' => $constraint->statement,
        ]);
        $constraintDetailsAction->store([
            "constraint_id" => $constraint->id,
            "date" => $constraint->date,
            "type" => 'creditor',
            "account_id" => $accounts["credit_account_id"],
            'debtor_value' => 0,
            'creditor_value' => $bondData['net'],
            'statement' => $constraint->statement,
        ]);
        $constraintDetailsAction->store([
            "constraint_id" => $constraint->id,
            "date" => $constraint->date,
            "type" => 'creditor',
            "account_id" => $bondData['bond_tax']['tax_account_id'],
            'debtor_value' => 0,
            'creditor_value' => $bondData['bond_tax']['tax_amount'],
            'statement' => $constraint->statement,
        ]);
    }

    public function getAccountIDForDetails($bondData)
    {
        $data = [];
        if ($bondData['payment_type'] == BondPaymentTypes::Check->value) {
            if ($bondData['type'] == BondTypes::Sarf->value) {
                $data['debit_account_id'] = $this->accountService->findByType(AccountTypesEnum::ExchangePapers->value)->id;
                $data['credit_account_id'] = $bondData['credit_account_id'];
            }
            if ($bondData['type'] == BondTypes::Qabd->value) {
                $data['credit_account_id'] = $bondData['credit_account_id'];
                $data['debit_account_id'] = $this->accountService->findByType(AccountTypesEnum::CatchPapers->value)->id;
            }
        } else {
            $data['credit_account_id'] = $bondData['credit_account_id'];
            $data['debit_account_id'] = $bondData['debit_account_id'];
        }
        return $data;
    }

    public function checkBondPayment($bondData)
    {
        // لو السند ده طريقه الدفع فيه شيك يبقي وانت بتعمل قيد ظبط حسابات الدائن والمدين
        if ($bondData['payment_type'] == BondPaymentTypes::Check->value) {
            if ($bondData['type'] == BondTypes::Sarf->value) {
                $account_id = $this->accountService->findByType(AccountTypesEnum::ExchangePapers->value)->id;
                $bondData['credit_account_id'] = $account_id;
            }
            if ($bondData['type'] == BondTypes::Qabd->value) {
                $account_id = $this->accountService->findByType(AccountTypesEnum::CatchPapers->value)->id;
                $bondData['debit_account_id'] = $account_id;
            }
        }
        return $bondData;
    }

    /*
    * -----------------------------------------------------------------------------------------------------------------
    *
    * -----------------------------------------------------------------------------------------------------------------
    */

    public function getClientAccounts()
    {
        return $this->accountService->getByType(AccountTypesEnum::Client->value);
    }

    public function deleteBond($bond_id)
    {
        $obj = $this->find($bond_id);
        $constraintAction = $this->constraintService;
        $constraintAction->delete($obj->constraint_id);
        $this->delete($bond_id);
    }

    /*
    * -----------------------------------------------------------------------------------------------------------------
    *
    * -----------------------------------------------------------------------------------------------------------------
    */

    public function getDataTableData($type, $general_type = 0)
    {
        return $this->model->query()->with([
            "debitAccount:id,name,code",
            "creditAccount:id,name,code",
            "costCenter:id,title",
            "createdBy:id,name",
        ])
            ->where("type", $type)
            ->where("general_type", $general_type)
            ->orderByDesc("date")
            ->orderByDesc("id");
    }

    public function getDataTableQabdData($type, $general_type = 0)
    {
        return $this->getDataTableData($type, $general_type)->with('check');
    }

    public function lastCode($type)
    {
        $row = $this->model->where("type", $type)->orderBy('code', 'DESC')->first();
        return (isset($row->code)) ? ($row->code + 1) : 1;
    }
}
