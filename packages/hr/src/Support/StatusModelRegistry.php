<?php

namespace Nami\Hr\Support;

use Nami\Hr\Models\Employee;
use Nami\Hr\Models\EmployeePermission;
use Nami\Hr\Models\EmployeeSanction;
use Nami\Hr\Models\Insurance;
use Nami\Hr\Models\Reward;
use Nami\Hr\Models\Shift;
use Nami\Hr\Models\Tax;

final class StatusModelRegistry
{
    /**
     * Resolve only the tables exposed by the HR status controls.
     *
     * @return class-string<\Illuminate\Database\Eloquent\Model>|null
     */
    public static function modelFor(string $table): ?string
    {
        return [
            'hr_employee' => Employee::class,
            'hr_employee_permissions' => EmployeePermission::class,
            'hr_employee_sanctions' => EmployeeSanction::class,
            'hr_insurances' => Insurance::class,
            'hr_rewards' => Reward::class,
            'hr_shifts' => Shift::class,
            'hr_tax' => Tax::class,
        ][$table] ?? null;
    }
}
