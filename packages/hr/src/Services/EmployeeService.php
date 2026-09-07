<?php

namespace Nami\Hr\Services;


use App\Enums\AdminTypeisEnum;
use App\Services\BranchAccessService;
use Nami\Hr\MainService;
use Nami\Hr\Models\Employee;
use Nami\Hr\Models\Department;
use Nami\Hr\Models\EmployeeFile;
use Nami\Hr\Models\EmployeeInsurance;
use Nami\Hr\Models\EmployeeShift;
use Nami\Hr\Models\EmployeeAllowance;
use Nami\Hr\Models\Job;
use App\Services\Nami\AdminService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Nami\Hr\Models\EmployeeVacationBalance;
use App\Models\Nami\Admin;

class EmployeeService extends MainService
{
    public $folderPath = "hr.employees";

    public function __construct(
        Employee                  $model,
        private DepartmentService $departmentService,
        private VacationYearService $vacationYearService,
        private AdminService      $adminService,
        private JobService        $jobService,
        private CountriesService  $countriesService, private ShiftService $shiftService, private AllowanceService $allowanceService
        , private VacationService $vacationService, private InsuranceService $insuranceService, private TaxService $taxService)
    {
        $this->model = $model;
        $this->fileFolder = "employees/";
    }

    public function GetFilterEmployees($request, string $category = Employee::CATEGORY_EMPLOYEE)
    {
        $query = $category === Employee::CATEGORY_MAID
            ? $this->model->query()->onlyMaids()->latest()
            : $this->model->query()->onlyEmployees()->latest();

        $query->with(['branch', $category === Employee::CATEGORY_MAID ? 'maid' : 'admin']);

        $branchAccess = app(BranchAccessService::class);
        $branchId = $branchAccess->selectedBranchId($request);

        return $query
            ->when($branchId, function ($builder) use ($branchId, $branchAccess) {
                $branchAccess->applyBranchIdToQuery($builder, $branchId, 'hr_employee');
            })
            ->when($request->filled('department_id'), function ($builder) use ($request) {
                $builder->where('deparment_id', $request->department_id);
            })
            ->when($request->filled('job_id'), function ($builder) use ($request) {
                $builder->where('job_id', $request->job_id);
            })
            ->get();
    }

    public function getEmployee()
    {
        return $this->model->adminType()->get();
    }

    public function getEmployeesName()
    {
        return $this->model->adminType()->latest()->get(['id', 'name']);
    }

    public function getCountEmployee()
    {
        return $this->model->count();
    }

    public function getEmployeeWithShift()
    {
        return $this->model->with('shift')->get();
    }

    public function getCountries()
    {
        return $this->countriesService->getCountries();
    }
    public function getEmployeeWithoutMe($id)
    {
        return $this->model->where('id',"!=",$id)->get();
    }

    public function getdepartment()
    {
        return $this->departmentService->getDepartment();
    }

    public function getDepartmentsForCategory(string $category)
    {
        return Department::query()
            ->whereHas('employees', function ($query) use ($category) {
                $category === Employee::CATEGORY_MAID
                    ? $query->onlyMaids()
                    : $query->onlyEmployees();
            })
            ->get();
    }

    public function getJob()
    {
        return $this->jobService->getJob();
    }

    public function getJobsForCategory(string $category)
    {
        return Job::query()
            ->whereHas('employees', function ($query) use ($category) {
                $category === Employee::CATEGORY_MAID
                    ? $query->onlyMaids()
                    : $query->onlyEmployees();
            })
            ->get();
    }

    public function getShift()
    {
        return $this->shiftService->getShift();
    }

    public function getAllowance()
    {
        return $this->allowanceService->getAllowance();
    }

    public function getVacation()
    {
        return $this->vacationService->getVacation();
    }

    public function getInsurance()
    {
        return $this->insuranceService->getInsurance();
    }

    public function getActiveInsurance()
    {
        return $this->insuranceService->getActiveInsurance();
    }

    public function getTax()
    {
        return $this->taxService->getTax();
    }

    public function getActiveTax()
    {
        return $this->taxService->getActiveTax();
    }



    public function storeEmployee($data, $request)
    {
        if ($request->hasFile('image')) {
            $image = $data['image'];
            $data['image'] = uploadFile($image, $this->fileFolder);
        }

        $employee = $this->store($data);
        $employee_data =  $employee;

        $this->handleEmployeeFiles($request, $employee, $this->fileFolder);

        $this->handleEmployeeAllowances($request, $employee);

        if ($request->has('responsible')) {
            $employee->responsible()->attach($request->responsible);
        }

        $employeeShift = new EmployeeShift();
        $employeeShift->employee_id = $employee->id;
        $employeeShift->shift_id = $request->shift_id;
        $employeeShift->save();


        if ($request->create_admin_account) {
            $adminData['name'] = $employee->name;
            $adminData['phone'] = $employee->phone;
            $adminData['email'] = $employee->email;
            $adminData['password'] = $request->password;
            $adminData['identity_number'] = $employee->national_num;
            $adminData['identity_expiry_date'] = $employee->identity_expiry_date;
//            $adminData['image'] = $image ?? '';
            $adminData['admin_type'] = AdminTypeisEnum::Employee->value;
            $admin = $this->adminService->store($adminData);
            $admin->image = $image ?? '';
            $admin->save();
            $employee->update(['admin_id' => $admin->id]);
        }

        return $employee_data;
    }

    public function handleEmployeeFiles($request, $employee, $fileFolder)
    {
        if ($request->hasFile('file')) {
            $images = $request->file('file');

            foreach ($images as $key => $image) {
                $fileName = uploadFile($image, $fileFolder);

                $imageData = [
                    'file' => $fileName,
                    'title' => $request->title[$key],
                    'employee_id' => $employee->id,
                ];
                EmployeeFile::create($imageData);
            }
        }
    }

    public function handleEmployeeAllowances($request, $employee)
    {
        $employee->manyInsurances()->delete();
        $employee->manyAllowances()->delete();
        $employee->manyTax()->delete();
        $employee->manyVacations()->delete();
        $vacationData = [];

        if ($request->allowances) {
            $allowancesValue = $request->valueInput;
            $allowancesType = $request->typeInput;


            foreach ($request->allowances as $allowanceId) {
                if (isset($allowancesValue[$allowanceId])) {
                    EmployeeAllowance::create([
                        'employee_id'   => $employee->id,
                        'allowances_id' => $allowanceId,
                        'value' => $allowancesValue[$allowanceId],
                        'type' => $allowancesType[$allowanceId],
                    ]);
                }
            }
        }

        if ($request->insurances) {
            $insuranceValue = $request->insuranceValue;
            foreach ($request->insurances as $insuranceId) {
                if (isset($insuranceValue[$insuranceId])) {
                    EmployeeInsurance::create([
                       'employee_id'   => $employee->id,
                       'insurances_id' => $insuranceId,
                        'value' => $insuranceValue[$insuranceId],
                    ]);
                }
            }
        }


        if ($request->vacations) {
            $totals = $request->input('total_days');
            $tokens = $request->input('token_days');
            $remains = $request->input('remain_days');
            $vacation_year_id = $this->vacationYearService->getActiveYear()->id ?? null;
            $vacations = $request->vacations;
            foreach ($vacations as $key => $vacation) {
                if ($totals[$key] != null) {
                    $data = [
                        'total_days'       => $totals[$key],
                        'token_days'       => $tokens[$key],
                        'remain_days'      => $remains[$key],
                        'vacation_year_id' => $vacation_year_id,
                    ];
                    $vacationData[$vacation] = $data;
                }
            }
        }

        $employee->vacations()->attach($vacationData);
        $employee->tax()->attach($request->tax);
    }

    public function updateEmployee($id, $data, $request)
    {
        if (isset($data['image'])) {
            $obj = $this->find($id);
            deleteFile($obj->image);
            $image = $data['image'];
            $data['image'] = uploadFile($image, $this->fileFolder);
        }
        $employee = $this->find($id);
        $employee->update($data);
        $this->handleUpdateEmployeeFiles($request, $employee);
        $this->handleEmployeeAllowances($request, $employee);
        if ($request->has('responsible')) {
            $employee->responsible()->sync($request->responsible);
        }
        if ((isset($employee->shift->shift->id))) {
            $employeeShift = EmployeeShift::where('employee_id', $employee->id)->first();
        } else {
            $employeeShift = new EmployeeShift();
        }
        $employeeShift->employee_id = $employee->id;
        $employeeShift->shift_id = $request->shift_id;
        $employeeShift->save();
        if($employee->admin){
            $adminData['email'] = $request->email;
            $adminData['password'] = $request->password ? Hash::make($request->password) : $employee->admin->password;
            $adminData['identity_number'] = $employee->national_num;
            $adminData['identity_expiry_date'] = $employee->identity_expiry_date;
            $employee->admin->update($adminData);
        }
        if ($request->create_admin_account) {
            $adminData['name'] = $employee->name;
            $adminData['phone'] = $employee->phone;
            $adminData['email'] = $employee->email;
            $adminData['password'] = $request->password;
            $adminData['identity_number'] = $employee->national_num;
            $adminData['identity_expiry_date'] = $employee->identity_expiry_date;
            if (isset($request->images)){
                $adminData['image'] = $request->images;
            }
            $adminData['admin_type'] = AdminTypeisEnum::Employee->value;
            $admin = $this->adminService->store($adminData);
            $employee->update(['admin_id' => $admin->id]);
        }
        return $employee;
    }

    public function handleUpdateEmployeeFiles($request, $employee)
    {
        $attachmentIds = $request->attachment_id ?? [];
        $titles = $request->title ?? [];
        $files = $request->file('file') ?? [];

        foreach ($attachmentIds as $key => $attachmentId) {
            if ($titles[$key] != null) {
                $imageData = [
                    'title' => $titles[$key],
                    'employee_id' => $employee->id,
                ];
                if (isset($files[$key])) {
                    $fileName = uploadFile($files[$key], $this->fileFolder);
                    $imageData['file'] = $fileName;
                }
                if ($attachmentId) {
                    EmployeeFile::where('id', $attachmentId)->update($imageData);
                } else {
                    EmployeeFile::create($imageData);
                }
            }
        }
    }

    public function deleteEmployee($id)
    {
        $obj = $this->find($id);
        if ($obj->image != 'default.png') {
            Storage::delete('public/images/admins/Equipments/' . $obj->image);
        }
        $this->delete($id);
    }

}
