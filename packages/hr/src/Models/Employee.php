<?php

namespace Nami\Hr\Models;

use App\Models\Nami\Admin;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Employee extends HrModel
{
    use \App\Models\Concerns\AssignsBranch, \App\Models\Concerns\BranchScoped;
    use HasFactory;

    public const CATEGORY_EMPLOYEE = 'employee';
    public const CATEGORY_MAID = 'maid';

    protected static ?bool $hasEmployeeCategoryColumn = null;

    protected $guarded = [];
    protected $table = 'hr_employee';

    protected static function booted(): void
    {
        static::addGlobalScope('employee_category', function (Builder $builder) {
            if (!static::hasEmployeeCategoryColumn()) {
                return;
            }

            $builder->where(
                $builder->getModel()->getTable() . '.employee_category',
                static::CATEGORY_EMPLOYEE
            );
        });
    }

    public static function hasEmployeeCategoryColumn(): bool
    {
        if (static::$hasEmployeeCategoryColumn === null) {
            static::$hasEmployeeCategoryColumn = Schema::hasColumn((new static())->getTable(), 'employee_category');
        }

        return static::$hasEmployeeCategoryColumn;
    }

    public function scopeWithAllCategories($query)
    {
        return $query->withoutGlobalScope('employee_category');
    }

    public function scopeOnlyEmployees($query)
    {
        $query = $query->withoutGlobalScope('employee_category');

        if (!static::hasEmployeeCategoryColumn()) {
            return Schema::hasColumn($this->getTable(), 'maid_id')
                ? $query->whereNull($this->getTable() . '.maid_id')
                : $query;
        }

        return $query->where(
            $this->getTable() . '.employee_category',
            static::CATEGORY_EMPLOYEE
        );
    }

    public function scopeOnlyMaids($query)
    {
        $query = $query->withoutGlobalScope('employee_category');

        if (!static::hasEmployeeCategoryColumn()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(
            $this->getTable() . '.employee_category',
            static::CATEGORY_MAID
        );
    }

    public function scopeAdminType($query)
    {

        if (Auth::guard('admin')->check()) {
            $userType = Auth::guard('admin')->user()->admin_type;
            if ($userType == 'department_manager') {
                $departmentId = Auth::guard('admin')->user()->adminEmployees->department_id;
                return $query->where('department_id', $departmentId);
            }
            if ($userType == 'employee') {
                $employeeId = Auth::guard('admin')->user()->adminEmployees->employee_id;
                return $query->where('id', $employeeId);
            }
        }
    }

    public function departments()
    {
        return $this->belongsTo(Department::class,'deparment_id');
    }
    public function admin()
    {
        return $this->belongsTo(Admin::class,'admin_id');
    }

    public function maid()
    {
        return $this->belongsTo(\App\Models\ingaz\Maid::class, 'maid_id');
    }

    public function nationality()
    {
        return $this->belongsTo(\App\Models\Country::class, 'country_id');
    }

    public function salary()
    {
        return $this->hasOne(EmployeeSalary::class, 'employee_id', 'id');
    }

    public function salaries()
    {
        return $this->hasMany(EmployeeSalary::class, 'employee_id', 'id');
    }

    public function payrollItems()
    {
        return $this->hasMany(PayrollItem::class, 'employee_id', 'id');
    }

    public function jobs()
    {
        return $this->belongsTo(Job::class,'job_id');
    }

    public function shifts()
    {
        return $this->hasMany(EmployeeShift::class,'shift_id');
    }

    public function sanctions()
    {
        return $this->hasMany(EmployeeSanction::class,'employee_id');
    }

    public function shift()
    {
        return $this->hasOne(EmployeeShift::class,'employee_id')->with('shift');
    }

    public function tax() {
        return $this->belongsToMany(Tax::class, 'hr_employee_tax', 'employee_id', 'tax_id');
    }

    public function hasTax($taxId)
    {
        return in_array($taxId, $this->tax->pluck('id')->toArray());
    }

    public function insurances() {
        return $this->belongsToMany(Insurance::class, 'hr_employee_insurances', 'employee_id', 'insurances_id');
    }

    public function hasInsurance($insuranceId)
    {
        return in_array($insuranceId, $this->insurances->pluck('id')->toArray());
    }

    public function allowances() {
        return $this->belongsToMany(Allowance::class, 'hr_employee_allowances', 'employee_id', 'allowances_id')->withPivot('id','allowances_id','type', 'value');;
    }
    public function manyInsurances() {
        return $this->hasMany(EmployeeInsurance::class, 'employee_id');
    }
    public function manyAllowances() {
        return $this->hasMany(EmployeeAllowance::class, 'employee_id');
    }
    public function manyTax() {
        return $this->hasMany(EmployeeTax::class, 'employee_id');
    }
    public function manyVacations() {
        return $this->hasMany(EmployeeVacationBalance::class, 'employee_id');
    }
    public function responsible() {
        return $this->belongsToMany(Employee::class, 'hr_employee_responsible', 'employee_id', 'responsible_employee_id');
    }

    public function hasResponsible($responsibleId)
    {
        return in_array($responsibleId, $this->responsible->pluck('id')->toArray());
    }

    public function hasAllowance($allowancesId)
    {
        return in_array($allowancesId, $this->allowances->pluck('id','type','value')->toArray());
    }

    public function files() {
        return $this->hasMany(EmployeeFile::class, 'employee_id');
    }

    public function vacations() {
        return $this->belongsToMany(Vacation::class, 'hr_employee_vacation_balance', 'employee_id', 'vacation_id')->withPivot('id','vacation_id','total_days','token_days','remain_days','vacation_year_id');
    }
}
