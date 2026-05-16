<?php

namespace Nami\Hr\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Nami\Finance\Enums\AccountTypesEnum;
use Nami\Finance\Models\Account;
use Nami\Hr\MainService;
use Nami\Hr\Models\Employee;
use Nami\Hr\Models\EmployeeCustody;
use Nami\Hr\Models\EmployeeCustodyLocation;

class EmployeeCustodyService extends MainService
{
    public function __construct()
    {
        $this->model = new EmployeeCustody();
        $this->fileFolder = 'images/admins/employee-custodies/';
        $this->files = ['file'];
    }

    public function getDataTable($employeeId = null)
    {
        return parent::getDataTable()
            ->with(['employee', 'account', 'custodyLocation'])
            ->when($employeeId, fn ($query) => $query->where('employee_id', $employeeId));
    }

    public function storeCustody(array $data): EmployeeCustody
    {
        return DB::transaction(function () use ($data) {
            $data['account_id'] = $this->ensureEmployeeCustodyAccount((int) $data['employee_id'])->id;
            $data['location'] = $this->resolveLocationName((int) $data['location_id']);

            return $this->store($data);
        });
    }

    public function updateCustody(int $id, array $data): EmployeeCustody
    {
        return DB::transaction(function () use ($id, $data) {
            $data['account_id'] = $this->ensureEmployeeCustodyAccount((int) $data['employee_id'])->id;
            $data['location'] = $this->resolveLocationName((int) $data['location_id']);
            $this->update($id, $data);

            return $this->find($id);
        });
    }

    protected function resolveLocationName(int $locationId): string
    {
        return EmployeeCustodyLocation::query()->findOrFail($locationId)->name;
    }

    public function ensureEmployeeCustodyAccount(int $employeeId): Account
    {
        $employee = Employee::query()->withAllCategories()->findOrFail($employeeId);
        $parentAccount = Account::query()
            ->where('type', AccountTypesEnum::Custody->value)
            ->orWhere('code', 126)
            ->lockForUpdate()
            ->first();

        if (!$parentAccount) {
            throw ValidationException::withMessages([
                'employee_id' => __('hr::hr.custody_parent_account_missing'),
            ]);
        }

        $existingAccountId = EmployeeCustody::query()
            ->where('employee_id', $employee->id)
            ->whereNotNull('account_id')
            ->value('account_id');

        if ($existingAccountId) {
            $account = Account::query()->find($existingAccountId);
            if ($account) {
                if ($account->name !== $employee->name) {
                    $account->update(['name' => $employee->name]);
                }

                return $account;
            }
        }

        $lastCode = Account::query()->where('parent_id', $parentAccount->id)->max('code');
        if (!$lastCode) {
            $lastCode = (int) ($parentAccount->code . '00000');
        }

        return Account::query()->create([
            'name' => $employee->name,
            'operation_type' => $parentAccount->operation_type,
            'statement_type' => $parentAccount->statement_type ?? null,
            'status' => $parentAccount->status,
            'level' => $parentAccount->level + 1,
            'type' => AccountTypesEnum::Employee->value,
            'parent_id' => $parentAccount->id,
            'code' => $lastCode + 1,
            'created_by_id' => Auth::guard('admin')->id() ?: 1,
        ]);
    }
}
