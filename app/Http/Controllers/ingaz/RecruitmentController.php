<?php

namespace App\Http\Controllers\ingaz;

use App\Enums\ingaz\NotificationActionEnum;
use App\Http\Controllers\admin\Controller;
use App\Http\Traits\SendSMSTrait;
use App\Http\Traits\StoreMessageTrait;
use App\Models\auth\Admin;
use App\Models\ingaz\Client;
use App\Models\ingaz\JobType;
use App\Models\ingaz\Maid;
use App\Models\ingaz\Nationality;
use App\Models\ingaz\RecruitmentRequest;
use App\Models\ingaz\Religion;
use App\Models\ingaz\User;
use App\Models\RecruitmentOffices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RecruitmentController extends Controller
{
    use SendSMSTrait, StoreMessageTrait;
    public function create($client_id)
    {
        $client = User::findOrFail($client_id);

        // Get filter parameters
        $nationality_id = request('nationality_id');
        $religion_id = request('religion_id');
        $job_type_id = request('job_type_id');
        $experience_years = request('experience_years');
        $passport_num = request('passport_num');
        $office_id = request('office_id');

        // Base query for available maids with eager loading
        $query = Maid::with([
            'nationality',
            'religion',
            'jobTypes',
            'translations'
        ])
            ->where('status', 'available') // Use constant for better maintainability
            ->where('available', true) // Also check the available boolean field
            ->where('is_show', true)
            ->whereDoesntHave('contracts', function ($q) {
                $q->whereIn('status', ['active', 'pending']);
            })
            ->whereDoesntHave('orders', function ($q) {
                $q->whereIn('status', ['pending', 'approved', 'in_progress']);
            });

        // Apply filters
        if ($nationality_id) {
            $query->where('nationality_id', $nationality_id);
        }
        if ($religion_id) {
            $query->where('religion_id', $religion_id);
        }
        if ($job_type_id) {
            $query->whereHas('jobTypes', function ($q) use ($job_type_id) {
                $q->where('job_type_id', $job_type_id);
            });
        }
        if ($experience_years) {
            $query->where('experience_years', '>=', $experience_years);
        }
        if ($passport_num) {
            $query->where('passport_num', 'like', '%' . $passport_num . '%');
        }
        if ($office_id) {
            $query->where('office_id', $office_id);
        }

        // Order by latest first and paginate
        $available_maids = $query->latest()
            ->paginate(20)
            ->withQueryString();

        // Get data for filters
        $nationalities = Nationality::all();
        $religions = Religion::all();
        $job_types = JobType::all();
        $offices = RecruitmentOffices::all();

        // Get employees
        $employees = Admin::query()
            ->where('admin_type', 'customer_service')
            ->where(function ($query) {
                $query->whereNull('status')->orWhere('status', 'active');
            })
            ->get();

        return view('admin.ingaz.recruitment.select_maid', compact(
            'client',
            'available_maids',
            'nationalities',
            'religions',
            'job_types',
            'employees',
            'offices'
        ));
    }

    public function showMaidForm($client_id, $maid_id)
    {
        $client = User::findOrFail($client_id);
        $maid = Maid::with(['nationality', 'religion', 'jobTypes'])->findOrFail($maid_id);
        $currentAdmin = Auth::guard('admin')->user();

        // Get employees if user is admin
        $employees = [];
        if ($currentAdmin && (
            $currentAdmin->admin_type === 'super_admin'
            || adminHasAnyRoleSafe($currentAdmin, ['super_admin', 'admin', 'Administrator'])
        )) {
            $employees = Admin::query()
                ->where('admin_type', 'customer_service')
                ->where(function ($query) {
                    $query->whereNull('status')->orWhere('status', 'active');
                })
                ->get();
        }

        // Get selected employee ID from request
        $selectedEmployeeId = request('employee_id');

        return view('admin.ingaz.recruitment.create', compact('client', 'maid', 'employees', 'selectedEmployeeId'));
    }

    public function store(Request $request)
    {
        \Log::info('Recruitment Request Data:', $request->all());
        \Log::info('Request URL: ' . $request->url());
        \Log::info('Request Method: ' . $request->method());

        try {
            $request->validate([
                'client_id' => 'required|exists:users,id',
                'maid_id' => 'required|exists:maids,id',
                'employee_id' => [
                    'required',
                    Rule::exists('admins', 'id')->where(function ($query) {
                        $query
                            ->whereNull('deleted_at')
                            ->where('admin_type', 'customer_service')
                            ->where(function ($statusQuery) {
                                $statusQuery->whereNull('status')->orWhere('status', 'active');
                            });
                    }),
                ],
                'job_title' => 'required|string|max:255',
                'nationality' => 'required|string|max:255',
                'experience_years' => 'nullable|string',
                'salary' => 'required|numeric|min:0',
                'notes' => 'nullable|string',
            ]);

            DB::beginTransaction();

            // Get job type ID for recruitment
            $jobType = JobType::where('id', 1)->first(); // Assuming 1 is the ID for recruitment type

            // Create order using the model
            $recruitment = RecruitmentRequest::create([
                'user_id' => $request->client_id,
                'maid_id' => $request->maid_id,
                'job_type_id' => $jobType->id,
                'job_title' => $request->job_title,
                'nationality' => $request->nationality,
                'experience_years' => $request->experience_years,
                'salary' => $request->salary,
                'notes' => $request->notes,
                'status' => 'pending',
                'customer_id' => $request->employee_id,
            ]);

            // Create order details
            DB::table('order_details')->insert([
                'order_id' => $recruitment->id,
                'admin_id' => $request->employee_id,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Update maid availability status
            Maid::where('id', $request->maid_id)->update([
                'status' => 'reserved',
                'updated_at' => now()
            ]);

            DB::commit();

            \Log::info('Order Created Successfully', ['id' => $recruitment->id]);

            // Send SMS
            $userPhone = formatSaudiPhone($recruitment->client->phone);
            $customerPhone = $recruitment?->customer?->formatted_verification_phone;

            if (!empty($customerPhone)) {
                $body =  "يوجد لديكم حجز باسم " . auth()->user()->name . "برقم جوال " . auth()->user()->phone;
                $this->sendSMS($customerPhone, $body);
            }

            if (!empty($userPhone)) {
                $body = "عزيزي العميل، تم حجز سيرة ذاتية جديدة بنجاح. رقم الطلب {$recruitment->order_code}. للمتابعة يرجى التواصل مع المندوب المسؤول عن طلبكم.";
                $response = $this->sendSMS($userPhone, $body);

                $result = json_decode($response, true);

                if (isset($result['code']) && $result['code'] == "1") {
                    $this->storeMessage('ارسال رسالة نصية الى العميل', $body, NotificationActionEnum::SMS->value, $request->client_id);
                }
            }

            return response()->json([
                'success' => true,
                'message' => __('ingaz.recruitment_request_created')
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Order Creation Error: ' . $e->getMessage());
            \Log::error('Error Code: ' . $e->getCode());
            \Log::error('Error File: ' . $e->getFile());
            \Log::error('Error Line: ' . $e->getLine());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            \Log::error('Request Data: ' . json_encode($request->all()));

            return response()->json([
                'success' => false,
                'message' => __('ingaz.error_creating_recruitment_request'),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for selecting an employee.
     *
     * @param  int  $maid_id
     * @param  int  $client_id
     * @return \Illuminate\Http\Response
     */
    public function showEmployeeModal($maid_id, $client_id)
    {
        $maid = Maid::findOrFail($maid_id);
        $client = User::findOrFail($client_id);
        $employees = Admin::whereHas('roles', function ($query) {
            $query->where('name', 'employee');
        })->get();

        return view('admin.ingaz.recruitment.modals.select_employee', compact('maid', 'client', 'employees'));
    }
}
