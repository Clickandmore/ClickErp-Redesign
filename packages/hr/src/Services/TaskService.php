<?php

namespace Nami\Hr\Services;

use App\Services\BranchAccessService;
use Illuminate\Validation\ValidationException;
use Nami\Hr\MainService;
use Nami\Hr\Models\Employee;
use Nami\Hr\Models\HrEmployeeTask;
use Nami\Hr\Models\HrTask;

class TaskService extends MainService
{

    function __construct()
    {
        $this->model = new HrTask();
        $this->fileFolder = "images/admins/hr_tasks/";
    }


    public function createData($mainRoute)
    {
        $data['employees'] = @Employee::get();
        $data['storeRoute'] = route($mainRoute.'.store');
        return $data;
    }

    public function editData($id,$mainRoute)
    {
        $data['obj'] = $this->find($id);
        $data['employees'] = @Employee::get();
        $data['employee_tasks'] = HrEmployeeTask::where('task_id', $id)->pluck('employee_id')->toArray();

        $data['updateRoute'] = route($mainRoute.'.update', $id);
        return $data;
    }

    public function getMyDataTable($user_id = null)
    {
        if($user_id == null){
            $user_id = @auth('admin')->user()->employee->id;
        }

        // $user_id = @auth('admin')->user()->id;
        // $employee = @Employee::where('admin_id', $user_id)->first()->id;
        $tasks_ids = @HrEmployeeTask::where('employee_id', $user_id)->pluck('task_id')->toArray();

        return $this->model->whereIn('id', $tasks_ids)->latest();
    }

    public function getDataTable()
    {
        return parent::getDataTable()->with('branch');
    }

    public function store($data)
    {
        $employeeIds = collect($data['employee_ids'] ?? [])->map(fn ($id) => (int) $id)->filter()->unique()->values();
        $branchId = $this->resolveEmployeesBranchId($employeeIds->all());

        unset($data['employee_ids']);
        $data['branch_id'] = $branchId;

        if (isset($data['file'])){
            $data['file'] = uploadFile($data['file'], $this->fileFolder);
        }
        $task = parent::store($data);
        $this->syncEmployees($task, $employeeIds->all());
        return $task;
    }

    public function update($id, $data)
    {
        $task = $this->find($id);
        $employeeIds = collect($data['employee_ids'] ?? [])->map(fn ($employeeId) => (int) $employeeId)->filter()->unique()->values();
        $branchId = $this->resolveEmployeesBranchId($employeeIds->all());

        if ($task->branch_id && $branchId && (int) $task->branch_id !== $branchId) {
            throw ValidationException::withMessages([
                'employee_ids' => 'لا يمكن نقل مهمة قائمة إلى موظفين في فرع آخر.',
            ]);
        }

        $data['branch_id'] = $task->branch_id ?: $branchId;
        unset($data['employee_ids']);
        if (isset($data['file'])){
            $data['file'] = uploadFile($data['file'], $this->fileFolder);
        }
        $updated = parent::update($id, $data);
        $task = $this->find($id);
        $this->syncEmployees($task, $employeeIds->all());

        return $updated;
    }

    private function resolveEmployeesBranchId(array $employeeIds): ?int
    {
        $employees = Employee::query()->whereIn('id', $employeeIds)->get(['id', 'branch_id']);

        if ($employees->count() !== count($employeeIds)) {
            throw ValidationException::withMessages([
                'employee_ids' => 'تتضمن المهمة موظفاً غير متاح في فرعك.',
            ]);
        }

        $branchAccess = app(BranchAccessService::class);
        if (! $branchAccess->enabled()) {
            return null;
        }

        $mainBranchId = $branchAccess->mainBranchId();
        $branchIds = $employees
            ->map(fn (Employee $employee) => $employee->branch_id ?: $mainBranchId)
            ->filter()
            ->unique()
            ->values();

        if ($branchIds->count() !== 1) {
            throw ValidationException::withMessages([
                'employee_ids' => 'يجب أن يكون جميع موظفي المهمة من فرع واحد.',
            ]);
        }

        return (int) $branchIds->first();
    }

    private function syncEmployees(HrTask $task, array $employeeIds): void
    {
        $pivotData = collect($employeeIds)
            ->mapWithKeys(fn (int $employeeId) => [
                $employeeId => ['branch_id' => $task->branch_id],
            ])
            ->all();

        $task->employees()->sync($pivotData);
    }

    public function showData($id)
    {

        $data['obj'] = $this->find($id);
        return $data;
    }

}
