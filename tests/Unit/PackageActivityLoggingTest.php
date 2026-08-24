<?php

namespace Tests\Unit;

use App\Http\Controllers\admin\ActivityLogController;
use Nami\Finance\Models\Bond;
use Nami\Finance\Models\Constraint;
use Nami\Finance\Models\Finance;
use Nami\Finance\Enums\ConstraintTypiesEnum;
use Nami\Hr\Models\Employee;
use Nami\Hr\Models\EmployeePermission;
use Nami\Hr\Models\EmployeeSanction;
use Nami\Hr\Models\HrModel;
use Nami\Hr\Models\Insurance;
use Nami\Hr\Support\StatusModelRegistry;
use Spatie\Activitylog\Traits\LogsActivity;
use ReflectionMethod;
use Tests\TestCase;

class PackageActivityLoggingTest extends TestCase
{
    /** @test */
    public function every_finance_model_uses_the_audited_finance_base_model(): void
    {
        $this->assertContains(LogsActivity::class, class_uses_recursive(Finance::class));
        $this->assertPackageModelsExtend(
            base_path('packages/finance/src/Models'),
            'Nami\\Finance\\Models\\',
            Finance::class,
            ['Finance']
        );

        $options = (new Bond())->getActivitylogOptions();

        $this->assertSame('finance', $options->logName);
        $this->assertTrue($options->logOnlyDirty);
        $this->assertFalse($options->submitEmptyLogs);
        $this->assertContains('password', $options->logExceptAttributes);
        $this->assertSame(['updated_at'], $options->dontLogIfAttributesChangedOnly);
    }

    /** @test */
    public function every_hr_model_uses_the_audited_hr_base_model(): void
    {
        $this->assertContains(LogsActivity::class, class_uses_recursive(HrModel::class));
        $this->assertPackageModelsExtend(
            base_path('packages/hr/src/Models'),
            'Nami\\Hr\\Models\\',
            HrModel::class,
            ['HrModel']
        );

        $options = (new Employee())->getActivitylogOptions();

        $this->assertSame('hr', $options->logName);
        $this->assertTrue($options->logOnlyDirty);
        $this->assertFalse($options->submitEmptyLogs);
        $this->assertContains('token', $options->logExceptAttributes);
    }

    /** @test */
    public function status_updates_are_limited_to_known_audited_models(): void
    {
        $this->assertSame(Employee::class, StatusModelRegistry::modelFor('hr_employee'));
        $this->assertSame(EmployeePermission::class, StatusModelRegistry::modelFor('hr_employee_permissions'));
        $this->assertSame(EmployeeSanction::class, StatusModelRegistry::modelFor('hr_employee_sanctions'));
        $this->assertNull(StatusModelRegistry::modelFor('admins'));
        $this->assertNull(StatusModelRegistry::modelFor('activity_log'));
    }

    /** @test */
    public function package_subjects_have_readable_arabic_labels_in_the_activity_log(): void
    {
        $controller = app(ActivityLogController::class);
        $method = new ReflectionMethod($controller, 'resolveSubjectTypeLabel');
        $method->setAccessible(true);

        $this->assertSame('السند المالي', $method->invoke($controller, Bond::class));
        $this->assertSame('الموظف', $method->invoke($controller, Employee::class));
    }

    /** @test */
    public function enum_casts_can_be_serialized_by_the_activity_logger_on_laravel_eight(): void
    {
        $constraint = new Constraint();
        $constraint->setRawAttributes(['id' => 1, 'type' => ConstraintTypiesEnum::DailyEntry->value], true);

        $this->assertSame(
            ConstraintTypiesEnum::DailyEntry->value,
            $constraint->getStorableEnumValue(ConstraintTypiesEnum::DailyEntry)
        );
        $this->assertSame(
            ConstraintTypiesEnum::DailyEntry->value,
            $constraint->getStorableEnumValue(ConstraintTypiesEnum::class, ConstraintTypiesEnum::DailyEntry)
        );

        $logChanges = new ReflectionMethod($constraint, 'logChanges');
        $logChanges->setAccessible(true);
        $changes = $logChanges->invoke(null, $constraint);

        $this->assertSame(ConstraintTypiesEnum::DailyEntry->value, $changes['type']);
    }

    private function assertPackageModelsExtend(
        string $directory,
        string $namespace,
        string $baseClass,
        array $except
    ): void {
        foreach (glob($directory . '/*.php') as $file) {
            $className = pathinfo($file, PATHINFO_FILENAME);

            if (in_array($className, $except, true)) {
                continue;
            }

            $class = $namespace . $className;

            $this->assertTrue(class_exists($class), $class . ' must be autoloadable.');
            $this->assertTrue(is_subclass_of($class, $baseClass), $class . ' must use the audited base model.');
        }
    }
}
