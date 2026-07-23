<?php

namespace App\Http\Controllers\Agent;

use App\Http\Actions\ingaz\MaidAction;
use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Country;
use App\Models\ingaz\JobTitle;
use App\Models\ingaz\JobType;
use App\Models\ingaz\Maid;
use App\Models\ingaz\MaidStatusChangeRequest;
use App\Models\ingaz\Nationality;
use App\Models\ingaz\Religion;
use App\Models\ingaz\Skill;
use App\Models\RecruitmentOffices;
use App\Models\office\Office;
use App\Enums\ingaz\ContractStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class DashboardController extends Controller
{
    public function index()
    {
        $officeIds = $this->agentOfficeIds();
        $offices = Office::query()
            ->with('nationality')
            ->whereIn('id', $officeIds->all())
            ->orderBy('id')
            ->get();

        $maidsQuery = $this->maidsQuery($officeIds);
        $contractsQuery = $this->contractsQuery($officeIds);

        $stats = [
            'maids_count' => (clone $maidsQuery)->count(),
            'available_maids_count' => (clone $maidsQuery)
                ->where(function (Builder $query) {
                    $this->applyAvailableMaidConstraint($query);
                })
                ->count(),
            'contracts_count' => (clone $contractsQuery)->count(),
            'active_contracts_count' => (clone $contractsQuery)
                ->whereNotIn('status', ['completed', 'closed', 'back'])
                ->count(),
        ];

        $latestMaids = (clone $maidsQuery)
            ->with(['nationality', 'jobTypes'])
            ->latest('id')
            ->take(6)
            ->get();

        $latestContracts = (clone $contractsQuery)
            ->with(['user', 'maid.nationality', 'maid.office', 'info'])
            ->latest('id')
            ->take(6)
            ->get();

        return view('agent.dashboard', compact('offices', 'stats', 'latestMaids', 'latestContracts'));
    }

    public function maids(Request $request)
    {
        $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $workerFilter = $request->input('worker_filter', 'all');
        if (!in_array($workerFilter, ['all', 'available', 'contracted'], true)) {
            $workerFilter = 'all';
        }

        $defaultSort = match ($workerFilter) {
            'available' => 'name',
            'contracted' => 'contracted_first',
            default => 'available_first',
        };
        $sort = $request->input('sort', $defaultSort);
        if (!in_array($sort, ['available_first', 'contracted_first', 'newest', 'oldest', 'name'], true)) {
            $sort = $defaultSort;
        }

        $submissionFilter = $request->input('submission_filter', 'all');
        if (!in_array($submissionFilter, ['all', 'pending', 'approved', 'rejected'], true)) {
            $submissionFilter = 'all';
        }

        $baseQuery = $this->maidsQuery();

        if ($request->filled('date_from')) {
            $baseQuery->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $baseQuery->whereDate('created_at', '<=', $request->input('date_to'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $baseQuery->where(function (Builder $innerQuery) use ($search) {
                $innerQuery->where('name', 'like', '%' . $search . '%')
                    ->orWhere('passport_num', 'like', '%' . $search . '%')
                    ->orWhere('id_number', 'like', '%' . $search . '%');
            });
        }

        $filterCounts = [
            'all' => (clone $baseQuery)->count(),
            'available' => (clone $baseQuery)
                ->where(function (Builder $query) {
                    $this->applyAvailableMaidConstraint($query);
                })
                ->count(),
            'contracted' => (clone $baseQuery)
                ->where(function (Builder $query) {
                    $this->applyContractedMaidConstraint($query);
                })
                ->count(),
        ];

        $submissionCounts = [
            'all' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->where('submission_status', Maid::SUBMISSION_PENDING)->count(),
            'approved' => (clone $baseQuery)
                ->where(function (Builder $q) {
                    $q->where('submission_status', Maid::SUBMISSION_APPROVED)
                        ->orWhereNull('submission_status');
                })->count(),
            'rejected' => (clone $baseQuery)->where('submission_status', Maid::SUBMISSION_REJECTED)->count(),
        ];

        $query = (clone $baseQuery)
            ->with(['nationality', 'jobTypes', 'office', 'pendingStatusChangeRequest'])
            ->withCount([
                'contracts as active_contracts_count' => function (Builder $contractQuery) {
                    $this->applyActiveContractConstraint($contractQuery);
                },
            ]);

        if ($workerFilter === 'available') {
            $query->where(function (Builder $innerQuery) {
                $this->applyAvailableMaidConstraint($innerQuery);
            });
        } elseif ($workerFilter === 'contracted') {
            $query->where(function (Builder $innerQuery) {
                $this->applyContractedMaidConstraint($innerQuery);
            });
        }

        if ($submissionFilter === 'pending') {
            $query->where('submission_status', Maid::SUBMISSION_PENDING);
        } elseif ($submissionFilter === 'approved') {
            $query->where(function (Builder $q) {
                $q->where('submission_status', Maid::SUBMISSION_APPROVED)
                    ->orWhereNull('submission_status');
            });
        } elseif ($submissionFilter === 'rejected') {
            $query->where('submission_status', Maid::SUBMISSION_REJECTED);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        match ($sort) {
            'name' => $query->orderBy('name')->orderByDesc('id'),
            'oldest' => $query->orderBy('id'),
            'contracted_first' => $query
                ->orderByDesc('active_contracts_count')
                ->orderByRaw("CASE WHEN status = 'contracted' THEN 0 ELSE 1 END")
                ->orderByDesc('id'),
            'available_first' => $query
                ->orderBy('active_contracts_count')
                ->orderByRaw("CASE WHEN (available = 1 OR status = 'available') AND (status IS NULL OR status NOT IN ('contracted', 'reserved', 'suspended', 'backout')) THEN 0 ELSE 1 END")
                ->orderByDesc('id'),
            default => $query->orderByDesc('id'),
        };

        $maids = $query->paginate(15)->appends($request->query());

        return view('agent.maids.index', compact(
            'maids',
            'workerFilter',
            'filterCounts',
            'submissionFilter',
            'submissionCounts',
            'sort'
        ));
    }

    public function createMaid()
    {
        $nationalities = Nationality::all();
        $religions = Religion::all();
        $offices = $this->agentOffices();
        $jobTypes = JobType::all();
        $types = $jobTypes;
        $jobs = JobTitle::all();
        $skills = Skill::all();
        $countries = Country::query()->orderBy('name_ar')->get();
        $defaultTypeId = optional($types->first())->id;

        return view('agent.maids.create', compact(
            'nationalities',
            'religions',
            'offices',
            'jobTypes',
            'types',
            'jobs',
            'skills',
            'countries',
            'defaultTypeId'
        ));
    }

    public function extractCv(Request $request)
    {
        $text = (string) $request->input('text');

        // Basic extraction logic using regex
        $data = [
            'name' => null,
            'passport_num' => null,
            'nationality_id' => null,
            'age' => null,
            'religion_id' => null,
            'experience_years' => null,
        ];

        // Name extraction (very basic mock)
        if (preg_match('/Name[:\s]+([a-zA-Z\s]+)/i', $text, $matches)) {
            $data['name'] = trim($matches[1]);
        }

        // Passport extraction
        if (preg_match('/Passport[:\s#]+([A-Z0-9]+)/i', $text, $matches)) {
            $data['passport_num'] = trim($matches[1]);
        }

        // Age extraction
        if (preg_match('/Age[:\s]+(\d+)/i', $text, $matches)) {
            $data['age'] = (int) $matches[1];
        }

        // Nationality extraction (matching by title)
        $nationalities = Nationality::all();
        foreach ($nationalities as $nationality) {
            if (stripos($text, $nationality->title) !== false || stripos($text, $nationality->sub_title) !== false) {
                $data['nationality_id'] = $nationality->id;
                break;
            }
        }

        return response()->json($data);
    }

    public function storeMaid(Request $request, MaidAction $maidAction)
    {
        $request->validate([
            'name:en' => 'required|string|max:255',
            'passport_num' => 'required|string|max:255|unique:maids,passport_num',
            'nationality_id' => 'required|exists:nationalities,id',
            'office_id' => 'required|exists:recruitment_offices,id',
            'type_id' => 'required|exists:job_types,id',
            'religion_id' => 'nullable|exists:religions,id',
            'job_id' => 'required|array',
            'job_id.*' => 'exists:job_titles,id',
            'image' => 'required|file|image',
            'cv_file' => 'nullable|file',
            'gender' => 'nullable|in:male,female',
            'salary' => 'nullable|numeric',
            'age' => 'nullable|numeric|min:18|max:90',
            'birth_date' => 'nullable|date',
            'height' => 'nullable',
            'weight' => 'nullable',
            'childrens_count' => 'nullable|numeric',
            'duration' => 'nullable|string',
            'experience_status' => 'required|in:has_experience,no_experience',
            'experience_years' => 'nullable',
            'video_link' => 'nullable|string|max:2000',
            'additional_notes' => 'nullable|string',
            'id_number' => 'nullable|string|max:30',
            'id_expiry_date' => 'nullable|date',
            'border_number' => 'nullable|string|max:50',
            'passport_issue_date' => 'nullable|date',
            'passport_expiry_date' => 'nullable|date',
            'languages' => 'nullable|array',
            'percentage' => 'nullable|array',
            'skills' => 'nullable|array',
            'expertises' => 'nullable|array',
            'request_type' => 'nullable|in:known,specific,custom_spec',
            'marital_status:ar' => 'nullable|string',
            'marital_status:en' => 'nullable|string',
        ]);

        $officeIds = $this->agentOfficeIds();
        if (!$officeIds->contains((int) $request->office_id)) {
            abort(403, 'Unauthorized office');
        }

        // Agent-submitted CVs always start as pending review and hidden from the site.
        $data = $request->all();
        $data['status'] = 'available';
        $data['added_by'] = auth('agent')->id();
        $data['available'] = 1;
        $data['is_show'] = 0;
        $data['submission_status'] = Maid::SUBMISSION_PENDING;
        // Force experience_status fallback to avoid unexpected null
        $data['experience_status'] = $data['experience_status'] ?? 'no_experience';

        try {
            $maidAction->storeMaid($data, $request);
        } catch (\Throwable $e) {
            \Log::error('Agent storeMaid failed: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('agent.maids.index')->with('success', app()->getLocale() === 'ar'
            ? 'تم إضافة السيرة الذاتية وهي بانتظار المراجعة'
            : 'CV added successfully and pending review.');
    }

    public function createRapidMaid()
    {
        $nationalities = Nationality::all();
        $offices = $this->agentOffices();
        $jobs = JobTitle::all();
        $jobTypes = JobType::all();
        $defaultTypeId = optional($jobTypes->first())->id;

        return view('agent.maids.rapid_cv_add', compact('nationalities', 'offices', 'jobs', 'defaultTypeId'));
    }

    public function storeRapidMaid(Request $request, MaidAction $maidAction)
    {
        $data = $request->validate([
            'name:en' => 'nullable|string|max:255',
            'gender' => 'required|in:male,female',
            'nationality_id' => 'required|exists:nationalities,id',
            'passport_num' => 'required|unique:maids,passport_num',
            'type_id' => 'required|exists:job_types,id',
            'job_id' => 'required|array|min:1',
            'job_id.*' => 'exists:job_titles,id',
            'office_id' => 'required|exists:recruitment_offices,id',
            'cv_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $officeIds = $this->agentOfficeIds();
        if (!$officeIds->contains((int) $request->office_id)) {
            abort(403, 'Unauthorized office');
        }

        $data = array_merge($request->all(), $data);
        $data['status'] = 'available';
        $data['added_by'] = auth('agent')->id();
        $data['available'] = 1;
        $data['is_show'] = 0;
        $data['submission_status'] = Maid::SUBMISSION_PENDING;
        $data['experience_status'] = $data['experience_status'] ?? 'no_experience';
        $data['experience_years'] = $data['experience_years'] ?? '0';

        $name = trim((string) ($data['name:en'] ?? ''));
        $data['name'] = $name !== '' ? $name : null;
        unset($data['name:en']);

        try {
            $maidAction->storeRapidCvMaid($data, $request);
        } catch (\Throwable $e) {
            \Log::error('Agent storeRapidMaid failed: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('agent.maids.index')->with('success', app()->getLocale() === 'ar'
            ? 'تم إضافة السيرة المختصرة وهي بانتظار المراجعة'
            : 'Short CV added and pending review.');
    }

    public function updateNotes(Request $request, Maid $maid)
    {
        $officeIds = $this->agentOfficeIds();
        if (!$officeIds->contains($maid->office_id)) {
            abort(403, 'Unauthorized office');
        }

        $request->validate([
            'additional_notes' => 'nullable|string|max:1000',
        ]);

        $maid->update([
            'additional_notes' => $request->input('additional_notes'),
        ]);

        $message = app()->getLocale() === 'ar' ? 'تم تحديث الملاحظات بنجاح' : 'Notes updated successfully';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'notes' => $maid->additional_notes,
            ]);
        }

        return redirect()->back()->with('success', $message);
    }

    public function requestMaidStatusChange(Request $request, Maid $maid)
    {
        $officeIds = $this->agentOfficeIds();
        if (!$officeIds->contains($maid->office_id)) {
            abort(403, 'Unauthorized office');
        }

        $validated = $request->validate([
            'type' => ['required', 'in:suspend,backout'],
            'reason' => ['nullable', 'required_if:type,backout', 'string', 'max:1000'],
        ], [
            'type.required' => app()->getLocale() === 'ar' ? 'نوع الطلب مطلوب.' : 'Request type is required.',
            'reason.required' => app()->getLocale() === 'ar' ? 'سبب الطلب مطلوب.' : 'Request reason is required.',
        ]);

        $statusChangeRequest = null;

        DB::transaction(function () use ($maid, $validated, &$statusChangeRequest) {
            $lockedMaid = Maid::query()->lockForUpdate()->findOrFail($maid->id);

            $hasPendingRequest = MaidStatusChangeRequest::query()
                ->where('maid_id', $lockedMaid->id)
                ->where('status', MaidStatusChangeRequest::STATUS_PENDING)
                ->exists();

            if ($hasPendingRequest) {
                throw ValidationException::withMessages([
                    'type' => app()->getLocale() === 'ar'
                        ? 'يوجد طلب قيد المراجعة لهذه العاملة بالفعل.'
                        : 'This worker already has a request pending review.',
                ]);
            }

            if (in_array($lockedMaid->status, ['suspended', 'backout'], true)) {
                throw ValidationException::withMessages([
                    'type' => app()->getLocale() === 'ar'
                        ? 'حالة العاملة الحالية لا تسمح بإنشاء هذا الطلب.'
                        : 'The worker current status does not allow this request.',
                ]);
            }

            if (in_array($lockedMaid->submission_status, [Maid::SUBMISSION_PENDING, Maid::SUBMISSION_REJECTED], true)) {
                throw ValidationException::withMessages([
                    'type' => app()->getLocale() === 'ar'
                        ? 'يجب اعتماد السيرة أولًا قبل طلب تغيير حالة العاملة.'
                        : 'The CV must be approved before requesting a worker status change.',
                ]);
            }

            $hasActiveContract = $lockedMaid->contracts()
                ->where(function (Builder $query) {
                    $this->applyActiveContractConstraint($query);
                })
                ->exists();
            $isContracted = $lockedMaid->status === 'contracted' || $hasActiveContract;
            $isAvailable = !$isContracted
                && ((bool) $lockedMaid->available || $lockedMaid->status === 'available')
                && !in_array($lockedMaid->status, ['reserved', 'suspended', 'backout'], true);

            if ($validated['type'] === MaidStatusChangeRequest::TYPE_SUSPEND && !$isAvailable) {
                throw ValidationException::withMessages([
                    'type' => app()->getLocale() === 'ar'
                        ? 'يمكن طلب التعليق للعاملة المتاحة فقط.'
                        : 'Only an available worker can be suspended.',
                ]);
            }

            if ($validated['type'] === MaidStatusChangeRequest::TYPE_BACKOUT && !$isContracted) {
                throw ValidationException::withMessages([
                    'type' => app()->getLocale() === 'ar'
                        ? 'يمكن طلب Back Out للعاملة المتعاقد عليها فقط.'
                        : 'Back Out can only be requested for a contracted worker.',
                ]);
            }

            $statusChangeRequest = MaidStatusChangeRequest::create([
                'maid_id' => $lockedMaid->id,
                'office_id' => $lockedMaid->office_id,
                'requested_by_admin_id' => Auth::guard('agent')->id(),
                'type' => $validated['type'],
                'reason' => filled($validated['reason'] ?? null)
                    ? $validated['reason']
                    : 'طلب تعليق السيرة من الوكيل.',
                'status' => MaidStatusChangeRequest::STATUS_PENDING,
            ]);
        });

        $message = app()->getLocale() === 'ar'
            ? 'تم إرسال الطلب إلى الإدارة للمراجعة.'
            : 'The request was sent to administration for review.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'maid_id' => $maid->id,
                'request_type' => $statusChangeRequest?->type,
                'reason' => $statusChangeRequest?->reason,
            ]);
        }

        return redirect()->back()->with('success', $message);
    }

    public function bulkRequestMaidSuspension(Request $request)
    {
        $validated = $request->validate([
            'maid_ids' => ['required', 'array', 'min:1', 'max:100'],
            'maid_ids.*' => ['integer', 'distinct', 'exists:maids,id'],
        ], [
            'maid_ids.required' => app()->getLocale() === 'ar'
                ? 'اختر عاملة واحدة على الأقل.'
                : 'Select at least one worker.',
        ]);

        $officeIds = $this->agentOfficeIds();
        $createdCount = 0;
        $createdMaidIds = [];

        DB::transaction(function () use ($validated, $officeIds, &$createdCount, &$createdMaidIds) {
            $maids = Maid::query()
                ->whereIn('id', $validated['maid_ids'])
                ->whereIn('office_id', $officeIds)
                ->whereNotIn('status', ['suspended', 'backout'])
                ->where(function (Builder $query) {
                    $query->whereNull('submission_status')
                        ->orWhere('submission_status', Maid::SUBMISSION_APPROVED);
                })
                ->where(function (Builder $query) {
                    $query->where('available', true)
                        ->orWhere('status', 'available');
                })
                ->where(function (Builder $query) {
                    $query->whereNull('status')
                        ->orWhereNotIn('status', ['contracted', 'reserved', 'suspended', 'backout']);
                })
                ->whereDoesntHave('contracts', function (Builder $query) {
                    $this->applyActiveContractConstraint($query);
                })
                ->whereDoesntHave('statusChangeRequests', function (Builder $query) {
                    $query->where('status', MaidStatusChangeRequest::STATUS_PENDING);
                })
                ->lockForUpdate()
                ->get();

            foreach ($maids as $maid) {
                MaidStatusChangeRequest::create([
                    'maid_id' => $maid->id,
                    'office_id' => $maid->office_id,
                    'requested_by_admin_id' => Auth::guard('agent')->id(),
                    'type' => MaidStatusChangeRequest::TYPE_SUSPEND,
                    'reason' => 'طلب تعليق جماعي من الوكيل بعد الفلترة.',
                    'status' => MaidStatusChangeRequest::STATUS_PENDING,
                ]);
                $createdCount++;
                $createdMaidIds[] = $maid->id;
            }
        });

        $message = app()->getLocale() === 'ar'
            ? "تم إرسال {$createdCount} طلب تعليق إلى الإدارة."
            : "{$createdCount} suspension requests were sent to administration.";

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'created_count' => $createdCount,
                'maid_ids' => $createdMaidIds,
            ]);
        }

        return redirect()->back()->with('success', $message);
    }

    public function showMaid(Maid $maid)
    {
        $officeIds = $this->agentOfficeIds();
        if (!$officeIds->contains($maid->office_id)) {
            abort(403, 'Unauthorized office');
        }

        $maid->load(['nationality', 'religion', 'jobTypes', 'contracts.user', 'attachments', 'expertises']);

        return view('agent.maids.show', compact('maid'));
    }

    public function contracts(Request $request)
    {
        $baseQuery = $this->contractsQuery();
        $query = (clone $baseQuery)
            ->with(['user', 'maid.nationality', 'maid.office', 'info'])
            ->latest('id');

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function (Builder $innerQuery) use ($search) {
                $innerQuery->where('id', $search)
                    ->orWhereHas('user', function (Builder $userQuery) use ($search) {
                        $userQuery->where('name', 'like', '%' . $search . '%')
                            ->orWhere('phone', 'like', '%' . $search . '%')
                            ->orWhere('national_num', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('maid', function (Builder $maidQuery) use ($search) {
                        $maidQuery->where('name', 'like', '%' . $search . '%')
                            ->orWhere('passport_num', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('info', function (Builder $infoQuery) use ($search) {
                        $infoQuery->where('visa_number', 'like', '%' . $search . '%');
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $statuses = (clone $baseQuery)
            ->select('status')
            ->whereNotNull('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status');

        $contracts = $query->paginate(15)->appends($request->query());

        return view('agent.contracts.index', compact('contracts', 'statuses'));
    }

    public function dispatches(Request $request)
    {
        $officeIds = $this->agentOfficeIds();
        $currentStatuses = $this->currentRecruitmentStatuses();

        $baseQuery = Contract::query()
            ->where(function (Builder $query) use ($officeIds) {
                $query->whereHas('maid', function (Builder $maidQuery) use ($officeIds) {
                    $maidQuery->whereIn('office_id', $officeIds->all());
                });

                if (Schema::hasTable('contract_infos') && Schema::hasColumn('contract_infos', 'manual_maid_office_id')) {
                    $query->orWhereHas('info', function (Builder $infoQuery) use ($officeIds) {
                        $infoQuery->whereIn('manual_maid_office_id', $officeIds->all());
                    });
                }
            })
            ->whereIn('type', ['recruitment', 'maid'])
            ->where(function ($inner) use ($currentStatuses) {
                $inner->whereIn('status', $currentStatuses)
                    ->orWhereHas('externalOfficeDispatch');
            });

        $query = (clone $baseQuery)
            ->with([
                'user',
                'maid.religion',
                'maid.office',
                'maid.recruitmentOffice',
                'info',
                'info.manualMaidOffice',
                'externalOfficeDispatch',
            ])
            ->latest('id');

        $dispatchFilter = $request->get('dispatch_filter', 'sent');

        if ($dispatchFilter === 'not_sent') {
            $query->whereIn('status', $currentStatuses)
                ->whereDoesntHave('externalOfficeDispatch');
        } elseif ($dispatchFilter === 'sent') {
            $query->whereHas('externalOfficeDispatch');
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function (Builder $innerQuery) use ($search) {
                $innerQuery->where('id', 'like', '%' . $search . '%')
                    ->orWhereHas('info', function ($infoQuery) use ($search) {
                        $infoQuery->where('visa_number', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', '%' . $search . '%')
                            ->orWhere('national_num', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('maid', function ($maidQuery) use ($search) {
                        $maidQuery->where('name', 'like', '%' . $search . '%')
                            ->orWhere('passport_num', 'like', '%' . $search . '%');
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $contracts = $query->paginate(15)->appends($request->query());

        $stats = [
            'all' => (clone $baseQuery)->count(),
            'sent' => (clone $baseQuery)->whereHas('externalOfficeDispatch')->count(),
            'not_sent' => (clone $baseQuery)
                ->whereIn('status', $currentStatuses)
                ->whereDoesntHave('externalOfficeDispatch')
                ->count(),
        ];

        return view('agent.dispatches.index', compact('contracts', 'stats', 'dispatchFilter'));
    }

    private function currentRecruitmentStatuses(): array
    {
        return [
            ContractStatusEnum::PendingMaidSelection->value,
            ContractStatusEnum::New ->value,
            ContractStatusEnum::Msaned->value,
            ContractStatusEnum::Tafwed->value,
            ContractStatusEnum::Taffez->value,
            ContractStatusEnum::Tazker->value,
        ];
    }

    public function invoices(Request $request)
    {
        $officeIds = $this->agentOfficeIds();

        $query = Contract::query()
            ->where(function (Builder $query) use ($officeIds) {
                $query->whereHas('maid', function (Builder $maidQuery) use ($officeIds) {
                    $maidQuery->whereIn('office_id', $officeIds->all());
                });

                if (Schema::hasTable('contract_infos') && Schema::hasColumn('contract_infos', 'manual_maid_office_id')) {
                    $query->orWhereHas('info', function (Builder $infoQuery) use ($officeIds) {
                        $infoQuery->whereIn('manual_maid_office_id', $officeIds->all());
                    });
                }
            })
            ->whereHas('invoice')
            ->with(['user', 'maid.nationality', 'invoice'])
            ->latest('id');

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function (Builder $innerQuery) use ($search) {
                $innerQuery->where('id', $search)
                    ->orWhereHas('user', function (Builder $userQuery) use ($search) {
                        $userQuery->where('name', 'like', '%' . $search . '%')
                            ->orWhere('phone', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('invoice', function (Builder $invQuery) use ($search) {
                        $invQuery->where('id', $search);
                    });
            });
        }

        if ($request->filled('is_paid')) {
            $isPaid = $request->input('is_paid') === '1';
            $query->whereHas('invoice', function (Builder $invQuery) use ($isPaid) {
                $invQuery->where('is_paid', $isPaid);
            });
        }

        $contracts = $query->paginate(15)->appends($request->query());

        return view('agent.invoices.index', compact('contracts'));
    }

    public function showContract(Contract $contract)
    {
        $contract = $this->contractsQuery()
            ->with([
                'user',
                'maid.nationality',
                'maid.religion',
                'maid.jobTypes',
                'maid.office',
                'info.manualMaidOffice',
                'externalOfficeDispatch',
                'invoice',
            ])
            ->whereKey($contract->id)
            ->firstOrFail();

        return view('agent.contracts.show', compact('contract'));
    }

    public function notifications()
    {
        $notifications = \App\Models\AdminNotification::where('admin_id', auth('agent')->id())->orderBy('created_at', 'desc')->paginate(15);
        return view('agent.notifications.index', compact('notifications'));
    }

    public function markAsRead($id)
    {
        $notification = \App\Models\AdminNotification::where('admin_id', auth('agent')->id())->findOrFail($id);
        $notification->update(['read_at' => now()]);
        return redirect()->back();
    }

    public function markAllAsRead()
    {
        \App\Models\AdminNotification::where('admin_id', auth('agent')->id())->whereNull('read_at')->update(['read_at' => now()]);
        return redirect()->back()->with('success', app()->getLocale() === 'ar' ? 'تم تعيين الكل كمقروء' : 'All marked as read');
    }

    private function agentOffices(): Collection
    {
        $officeIds = $this->agentOfficeIds();
        return RecruitmentOffices::whereIn('id', $officeIds->all())->get();
    }

    private function agentOfficeIds(): Collection
    {
        $impersonatedOfficeId = session('_agent_impersonate_office_id');

        if ($impersonatedOfficeId && Auth::guard('admin')->check()) {
            return collect([(int) $impersonatedOfficeId]);
        }

        return Auth::guard('agent')
            ->user()
            ->agentOfficeLinks()
            ->pluck('office_id')
            ->filter()
            ->unique()
            ->values();
    }

    private function maidsQuery(?Collection $officeIds = null): Builder
    {
        $officeIds = $officeIds ?: $this->agentOfficeIds();

        return Maid::query()->whereIn('office_id', $officeIds->all());
    }

    private function contractsQuery(?Collection $officeIds = null): Builder
    {
        $officeIds = $officeIds ?: $this->agentOfficeIds();

        return Contract::query()
            ->where(function (Builder $query) use ($officeIds) {
                $query->whereHas('maid', function (Builder $maidQuery) use ($officeIds) {
                    $maidQuery->whereIn('office_id', $officeIds->all());
                });

                if (Schema::hasTable('contract_infos') && Schema::hasColumn('contract_infos', 'manual_maid_office_id')) {
                    $query->orWhereHas('info', function (Builder $infoQuery) use ($officeIds) {
                        $infoQuery->whereIn('manual_maid_office_id', $officeIds->all());
                    });
                }
            });
    }

    private function applyAvailableMaidConstraint(Builder $query): void
    {
        $query
            ->where(function (Builder $availabilityQuery) {
                $availabilityQuery->where('available', true)
                    ->orWhere('status', 'available');
            })
            ->where(function (Builder $statusQuery) {
                $statusQuery->whereNull('status')
                    ->orWhereNotIn('status', ['contracted', 'reserved']);
            })
            ->whereDoesntHave('contracts', function (Builder $contractQuery) {
                $this->applyActiveContractConstraint($contractQuery);
            });
    }

    private function applyContractedMaidConstraint(Builder $query): void
    {
        $query->where(function (Builder $contractedQuery) {
            $contractedQuery->where('status', 'contracted')
                ->orWhereHas('contracts', function (Builder $contractQuery) {
                    $this->applyActiveContractConstraint($contractQuery);
                });
        });
    }

    private function applyActiveContractConstraint(Builder $query): void
    {
        $query->where(function (Builder $statusQuery) {
            $statusQuery->whereNull('status')
                ->orWhereNotIn('status', [
                    ContractStatusEnum::Completed->value,
                    ContractStatusEnum::Closed->value,
                    ContractStatusEnum::Back->value,
                ]);
        });
    }
}
