<?php

namespace App\Http\Controllers\ingaz;

use App\Enums\ingaz\ContractStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\ingaz\ShelterContactStore;
use App\Models\Contract;
use App\Models\RecruitmentOffices;
use App\Models\ingaz\Nationality;
use App\Models\ShelterPlaces;
use App\NewPermKeys;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class ShelterContractController extends Controller
{
    public $mainRoute = "ShelterContract";
    public $folderPath = "admin.ingaz.ShelterContract";

    public function index(Request $request)
    {
        \Log::info('index method called');

        // Get statistics
        $totalContracts = Contract::where('status', ContractStatusEnum::Shelter->value)->count();
        $wantsToWork = Contract::where('status', ContractStatusEnum::Shelter->value)
            ->whereHas('info', function($query) {
                $query->where('wants_to_work', 'yes');
            })->count();

        $withinGuarantee = Contract::where('status', ContractStatusEnum::Shelter->value)
            ->where('warranty_status', 'active')
            ->count();

        $expiredGuarantee = Contract::where('status', ContractStatusEnum::Shelter->value)
            ->where('warranty_status', 'expired')
            ->count();

        // Get filter options
        $nationalities = Nationality::with('translations')->get();
        $shelters = ShelterPlaces::with('translations')->get();

        return view('admin.ingaz.ShelterContract.index', compact(
            'totalContracts',
            'wantsToWork',
            'withinGuarantee',
            'expiredGuarantee',
            'nationalities',
            'shelters'
        ));
    }

    /**
     * Export filtered data to CSV
     */
    private function exportData($contracts)
    {
        $filename = 'shelter_contracts_' . date('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($contracts) {
            $file = fopen('php://output', 'w');

            // Add CSV headers
            fputcsv($file, [
                'ID',
                'رقم العاملة',
                'رقم الجواز',
                'نوع العاملة',
                'الجنسية',
                'مكان الإيواء',
                'مصدر الاستلام',
                'حالة الضمان',
                'تاريخ الوصول',
                'تاريخ الاسترجاع',
                'حالة العقد داخل الإيواء'
            ]);

            // Add data rows
            foreach ($contracts as $contract) {
                // If maid has a name, show it, otherwise show passport number
                $maidName = 'غير محدد';
                if ($contract->maid) {
                    $displayName = $contract->maid->name;
                    if (empty($displayName) && $contract->maid->passport_num) {
                        $maidName = 'جواز رقم: ' . $contract->maid->passport_num;
                    } elseif (empty($displayName)) {
                        $maidName = 'عاملة رقم: ' . $contract->maid->id;
                    } else {
                        $maidName = $displayName;
                    }
                }

                $passport = $contract->maid && $contract->maid->passport_num ? $contract->maid->passport_num : 'غير محدد';

                $maidType = 'غير محدد';
                if ($contract->maid && $contract->maid->job_types && !$contract->maid->job_types->isEmpty()) {
                    $jobType = $contract->maid->job_types->first();
                    if ($jobType && $jobType->job_type) {
                        $locale = app()->getLocale();
                        $translation = $jobType->job_type->translations->where('locale', $locale)->first();
                        $title = $translation ? $translation->title : $jobType->job_type->translations->first()->title;
                        $maidType = $title;
                    }
                }

                $nationality = 'غير محدد';
                if ($contract->maid && $contract->maid->nationality) {
                    $locale = app()->getLocale();
                    $translation = $contract->maid->nationality->translations->where('locale', $locale)->first();
                    if ($translation) {
                        $nationality = $translation->sub_title;
                    } else {
                        $firstTranslation = $contract->maid->nationality->translations->first();
                        if ($firstTranslation) {
                            $nationality = $firstTranslation->sub_title;
                        }
                    }
                }

                $shelter = 'غير محدد';
                if ($contract->info && $contract->info->shelter) {
                    $locale = app()->getLocale();
                    $translation = $contract->info->shelter->translations->where('locale', $locale)->first();
                    if ($translation) {
                        $shelter = $translation->title;
                    } else {
                        $firstTranslation = $contract->info->shelter->translations->first();
                        if ($firstTranslation) {
                            $shelter = $firstTranslation->title;
                        }
                    }
                }

                $receiptSource = 'غير محدد';
                if ($contract->info && $contract->info->receipt_source) {
                    $receiptSource = __('shelter_worker_statuses.received_by.' . $contract->info->receipt_source) ?: $contract->info->receipt_source;
                }

                $guaranteeStatus = 'غير محدد';
                if ($contract->info) {
                    $arrivalDate = $contract->info->visa_milad_date ??
                                  $contract->info->contract_date ??
                                  $contract->info->ingaz_from;

                    if ($arrivalDate) {
                        try {
                            $arrivalDate = \Carbon\Carbon::parse($arrivalDate);
                            $today = \Carbon\Carbon::now();
                            $guaranteeEndDate = $arrivalDate->copy()->addDays(90);

                            if ($today->gt($guaranteeEndDate)) {
                                $guaranteeStatus = 'منتهي الضمان';
                            } else {
                                $remainingDays = $today->diffInDays($guaranteeEndDate);
                                $guaranteeStatus = 'ضمن الضمان (' . $remainingDays . ' يوم متبقي)';
                            }
                        } catch (\Exception $e) {
                            $guaranteeStatus = 'خطأ في التاريخ';
                        }
                    }
                }

                $arrivalDate = null;
                if ($contract->info) {
                    $arrivalDate = $contract->info->visa_milad_date ??
                                  $contract->info->contract_date ??
                                  $contract->info->ingaz_from;
                }
                $arrivalDate = $arrivalDate ? \Carbon\Carbon::parse($arrivalDate)->format('Y-m-d') : 'غير محدد';

                $returnDate = $contract->info && $contract->info->maid_date_back ?
                    \Carbon\Carbon::parse($contract->info->maid_date_back)->format('Y-m-d') : 'غير محدد';

                $contractStatus = getEnumValue('contract_statuses', $contract->status) ?: 'غير محدد';

                // Get work willingness status for export
                $workWillingness = $contract->info ? $contract->info->wants_to_work : null;

                if ($workWillingness === 'yes') {
                    $contractStatus = 'داخل الإيواء (متاح للنقل)';
                } elseif ($workWillingness === 'no') {
                    $contractStatus = 'داخل الإيواء (مرحلة الترحيل)';
                } elseif ($workWillingness === 'unknown') {
                    $contractStatus = 'داخل الإيواء';
                } else {
                    $contractStatus = 'داخل الإيواء';
                }

                fputcsv($file, [
                    $contract->id,
                    $maidName,
                    $passport,
                    $maidType,
                    $nationality,
                    $shelter,
                    $receiptSource,
                    $guaranteeStatus,
                    $arrivalDate,
                    $returnDate,
                    $contractStatus
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function create()
    {
        $shelters = \App\Models\ShelterPlaces::latest()->get();

        // جلب العقود المكتملة فقط (تشمل under_warranty و closed)
        $contracts = Contract::whereIn('status', [
                ContractStatusEnum::Completed->value,      // عقد مكتمل (جديد)
                ContractStatusEnum::UnderWarranty->value,  // تحت الضمان (قديم)
                ContractStatusEnum::Closed->value          // مكتمل (قديم)
            ])
            ->where('status', '!=', ContractStatusEnum::Shelter->value) // ليس في الإيواء
            ->where('status', '!=', ContractStatusEnum::Back->value)    // ليس مسترجع
            ->with(['maid', 'info'])
            ->latest()
            ->get();

        // Debug: Log contracts data
        \Log::info('ShelterContract create - Contracts found:', [
            'total_contracts' => $contracts->count(),
            'contracts_with_maid' => $contracts->whereNotNull('maid')->count(),
            'contracts_with_info' => $contracts->whereNotNull('info')->count(),
            'sample_contract' => $contracts->first() ? [
                'id' => $contracts->first()->id,
                'status' => $contracts->first()->status,
                'has_maid' => $contracts->first()->maid ? 'yes' : 'no',
                'has_info' => $contracts->first()->info ? 'yes' : 'no',
                'info_arrival_date' => $contracts->first()->info ? $contracts->first()->info->arrival_date : 'null'
            ] : 'no contracts',
            'all_contracts' => $contracts->map(function($contract) {
                return [
                    'id' => $contract->id,
                    'status' => $contract->status,
                    'has_maid' => $contract->maid ? 'yes' : 'no',
                    'has_info' => $contract->info ? 'yes' : 'no',
                    'info_arrival_date' => $contract->info ? $contract->info->arrival_date : 'null'
                ];
            })->toArray()
        ]);

        // إذا تم تمرير contract_id في الطلب، نختار العقد المحدد
        $selectedContractId = request('contract_id');

        return view($this->folderPath . '.create', compact('shelters', 'contracts', 'selectedContractId'));
    }

    /**
     * Search contracts by passport number
     */
    public function searchByPassport(Request $request)
    {
        $passportNumber = $request->input('passport_number');

        if (empty($passportNumber)) {
            return response()->json(['contracts' => []]);
        }

        $contracts = Contract::whereIn('status', [
                ContractStatusEnum::Completed->value,      // عقد مكتمل (جديد)
                ContractStatusEnum::UnderWarranty->value,  // تحت الضمان (قديم)
                ContractStatusEnum::Closed->value          // مكتمل (قديم)
            ])
            ->where('status', '!=', ContractStatusEnum::Shelter->value) // ليس في الإيواء
            ->where('status', '!=', ContractStatusEnum::Back->value)    // ليس مسترجع
            ->whereHas('maid', function ($query) use ($passportNumber) {
                $query->where('passport_num', 'like', '%' . $passportNumber . '%');
            })
            ->with(['maid', 'info'])
            ->latest()
            ->get()
            ->map(function ($contract) {
                $warrantyStatus = '';
                if ($contract->status === ContractStatusEnum::Completed->value) {
                    $warrantyStatus = ' (مكتمل)';
                } elseif ($contract->status === ContractStatusEnum::UnderWarranty->value) {
                    $warrantyStatus = ' (تحت الضمان)';
                } elseif ($contract->status === ContractStatusEnum::Closed->value) {
                    $warrantyStatus = ' (مكتمل)';
                }

                return [
                    'id' => $contract->id,
                    'desc' => $contract->desc . $warrantyStatus,
                    'passport_number' => $contract->maid ? $contract->maid->passport_num : '',
                    'maid_name' => $contract->maid ? $contract->maid->name : '',
                    'status' => $contract->status,
                ];
            });

        return response()->json(['contracts' => $contracts]);
    }

    public function store(ShelterContactStore $request)
    {
        $contract = Contract::find($request->contract_id);

        // Update or create contract info with arrival date
        if ($contract->info) {
            $contract->info()->update([
                'shelter_id' => $request->shelter_id,
                'receipt_source' => $request->receipt_source,
                'maid_date_back' => $request->maid_date_back,
                'salary_final_status' => $request->salary_final_status,
                'duration_from_enter' => $request->duration_from_enter,
                'wants_to_work' => $request->wants_to_work,
                'back_reasons' => $request->back_reasons,
                'arrival_date' => $request->arrival_date,
            ]);
        } else {
            // Create contract info if it doesn't exist
            $contract->info()->create([
                'contract_id' => $contract->id,
                'shelter_id' => $request->shelter_id,
                'receipt_source' => $request->receipt_source,
                'maid_date_back' => $request->maid_date_back,
                'salary_final_status' => $request->salary_final_status,
                'duration_from_enter' => $request->duration_from_enter,
                'wants_to_work' => $request->wants_to_work,
                'back_reasons' => $request->back_reasons,
                'arrival_date' => $request->arrival_date,
            ]);
        }

        $contract->status = ContractStatusEnum::Shelter->value;
        $contract->save();

        $contract->contract_status_logs()->create([
            'contract_id' => $contract->id,
            'status' => ContractStatusEnum::Shelter->value,
        ]);

        return jsonSuccess();
    }


    public function show($id)
    {
        $contract = Contract::with([
            'user',
            'maid.nationality',
            'maid.religion',
            'maid.job_types.job_type',
            'info.shelter'
        ])->findOrFail($id);

        // حساب حالة الضمان
        $warrantyStatus = 'غير محدد';
        $warrantyDays = 0;
        if ($contract->info) {
            $arrivalDate = $contract->info->visa_milad_date ??
                          $contract->info->contract_date ??
                          $contract->info->ingaz_from;

            if ($arrivalDate) {
                try {
                    $arrivalDate = \Carbon\Carbon::parse($arrivalDate);
                    $today = \Carbon\Carbon::now();
                    $guaranteeEndDate = $arrivalDate->copy()->addDays(90);

                    if ($today->lte($guaranteeEndDate)) {
                        $warrantyDays = $today->diffInDays($guaranteeEndDate);
                        $warrantyStatus = 'ضمن الضمان';
                    } else {
                        $warrantyDays = $guaranteeEndDate->diffInDays($today);
                        $warrantyStatus = 'منتهي الضمان';
                    }
                } catch (\Exception $e) {
                    $warrantyStatus = 'غير محدد';
                }
            }
        }

        return view('admin.ingaz.ShelterContract.show', compact('contract', 'warrantyStatus', 'warrantyDays'));
    }


    public function edit($id)
    {
        $contract = Contract::with(['info', 'maid'])->findOrFail($id);
        $shelters = \App\Models\ShelterPlaces::latest()->get();

        return view($this->folderPath . '.edit', compact('contract', 'shelters'));
    }


    public function update(Request $request, $id)
    {
        $request->validate([
            'shelter_id' => 'required|exists:shelter_places,id',
            'receipt_source' => 'required|string',
            'maid_date_back' => 'required|date',
            'salary_final_status' => 'required|in:received,not_received',
            'duration_from_enter' => 'required',
            'wants_to_work' => 'required|in:yes,no,unknown',
            'back_reasons' => 'nullable|string',
            'arrival_date' => 'required|date',
        ]);

        $contract = Contract::findOrFail($id);
        $contract->info()->update([
            'shelter_id' => $request->shelter_id,
            'receipt_source' => $request->receipt_source,
            'maid_date_back' => $request->maid_date_back,
            'salary_final_status' => $request->salary_final_status,
            'duration_from_enter' => $request->duration_from_enter,
            'wants_to_work' => $request->wants_to_work,
            'back_reasons' => $request->back_reasons,
            'arrival_date' => $request->arrival_date,
        ]);

        return jsonSuccess(['message' => 'تم تحديث العقد داخل الإيواء بنجاح']);
    }


    public function destroy($id)
    {
        //
    }

    /**
     * Get shelter places
     */
    public function getShelterPlaces()
    {
        $shelterPlaces = \App\Models\ShelterPlaces::with('translations')->get();
        return response()->json($shelterPlaces);
    }

    /**
     * Get contract details for duration calculation
     */
    public function getContractDetails(Request $request)
    {
        try {
            $contractId = $request->input('contract_id');
            $contract = Contract::with(['maid', 'user', 'info'])->find($contractId);

            if (!$contract) {
                return response()->json(['error' => 'العقد غير موجود'], 404);
            }

            $data = [
                'contract_id' => $contract->id,
                'maid_name' => $contract->maid ? $contract->maid->name : null,
                'maid_passport' => $contract->maid ? $contract->maid->passport_num : null,
                'maid_nationality' => $contract->maid && $contract->maid->nationality ?
                    $contract->maid->nationality->translations->where('locale', app()->getLocale())->first()->sub_title ??
                    $contract->maid->nationality->translations->first()->sub_title : null,
                'sponsor_name' => $contract->user ? $contract->user->name : null,
                'sponsor_id' => $contract->user ? $contract->user->id_number : null,
                'sponsor_phone' => $contract->user ? $contract->user->phone : null,
                'sponsor_city' => $contract->user ? $contract->user->city : null,
            ];

            return response()->json($data);
        } catch (\Exception $e) {
            \Log::error('Error in getContractDetails: ' . $e->getMessage());
            return response()->json(['error' => 'حدث خطأ في جلب بيانات العقد'], 500);
        }
    }

    /**
     * Get nationalities for filter dropdown
     */
    public function getNationalities()
    {
        $nationalities = \App\Models\ingaz\Nationality::with('translations')->get();
        return response()->json($nationalities);
    }

    /**
     * Show contracts available for transfer (work_willingness = 'yes')
     */
    public function availableForTransfer(Request $request)
    {
        \Log::info('availableForTransfer method called');
        \Log::info('Request data: ' . json_encode($request->all()));
        \Log::info('Is AJAX: ' . ($request->ajax() ? 'true' : 'false'));

        $filterStatus = Request('s');

        if ($request->ajax()) {
            \Log::info('availableForTransfer AJAX request');
            try {
                $rows = Contract::where('status', ContractStatusEnum::Shelter->value)
                    ->with([
                        'maid',
                        'user',
                        'info',
                        'maid.nationality.translations',
                        'maid.job_types.job_type.translations',
                        'info.shelter.translations'
                    ])
                    ->whereHas('info', function ($query) {
                        $query->where(function($q) {
                            $q->where('wants_to_work', 'yes')
                              ->orWhere('wants_to_work', 1);
                        });
                    });

                // Apply same filters as index method
                $this->applyFilters($rows, $request);

                $rows = $rows->latest();

                // Handle export request
                if ($request->has('export') && $request->input('export') == '1') {
                    return $this->exportData($rows->get());
                }

                // Debug: Get actual data before DataTables
                $actualData = $rows->get();
                \Log::info('Actual data count: ' . $actualData->count());
                if ($actualData->count() > 0) {
                    \Log::info('First record: ' . json_encode($actualData->first()->toArray()));
                }

                $dataTable = $this->buildDataTable($rows);

                \Log::info('DataTable response created successfully');
                \Log::info('DataTable response: ' . json_encode($dataTable->getData()));

                return $dataTable;
            } catch (\Exception $e) {
                \Log::error('Error in availableForTransfer: ' . $e->getMessage());
                \Log::error('Stack trace: ' . $e->getTraceAsString());
                return response()->json(['error' => 'حدث خطأ في عرض البيانات: ' . $e->getMessage()], 500);
            }
        }

        \Log::info('availableForTransfer returning view');

        $data["dataTableRoute"] = route($this->mainRoute . ".availableForTransfer");
        $data["modalType"] = "modal-lg";
        $data["nationalities"] = \App\Models\ingaz\Nationality::with('translations')->get();
        $data["shelterPlaces"] = \App\Models\ShelterPlaces::with('translations')->get();
        $data["jobTypes"] = \App\Models\ingaz\JobType::with('translations')->get();
        return view($this->folderPath . '.available-for-transfer', $data);
    }

    /**
     * Show contracts in deportation stage (work_willingness = 'no')
     */
    public function deportationStage(Request $request)
    {
        \Log::info('deportationStage method called');
        \Log::info('Request data: ' . json_encode($request->all()));
        \Log::info('Is AJAX: ' . ($request->ajax() ? 'true' : 'false'));

        $filterStatus = Request('s');

        if ($request->ajax()) {
            \Log::info('deportationStage AJAX request');
            try {
                $rows = Contract::where('status', ContractStatusEnum::Shelter->value)
                    ->with([
                        'maid',
                        'info',
                        'maid.nationality.translations',
                        'maid.job_types.job_type.translations',
                        'info.shelter.translations'
                    ])
                    ->whereHas('info', function ($query) {
                        $query->where('wants_to_work', 'no');
                    });

                // Apply same filters as index method
                $this->applyFilters($rows, $request);

                $rows = $rows->latest();

                // Handle export request
                if ($request->has('export') && $request->input('export') == '1') {
                    return $this->exportData($rows->get());
                }

                // Debug: Get actual data before DataTables
                $actualData = $rows->get();
                \Log::info('Actual data count: ' . $actualData->count());
                if ($actualData->count() > 0) {
                    \Log::info('First record: ' . json_encode($actualData->first()->toArray()));
                }

                $dataTable = $this->buildDataTable($rows);

                \Log::info('DataTable response created successfully');
                \Log::info('DataTable response: ' . json_encode($dataTable->getData()));

                return $dataTable;
            } catch (\Exception $e) {
                \Log::error('Error in deportationStage: ' . $e->getMessage());
                \Log::error('Stack trace: ' . $e->getTraceAsString());
                return response()->json(['error' => 'حدث خطأ في عرض البيانات: ' . $e->getMessage()], 500);
            }
        }

        \Log::info('deportationStage returning view');

        $data["dataTableRoute"] = route($this->mainRoute . ".deportationStage");
        $data["modalType"] = "modal-lg";
        $data["nationalities"] = \App\Models\ingaz\Nationality::with('translations')->get();
        $data["shelterPlaces"] = \App\Models\ShelterPlaces::with('translations')->get();
        $data["jobTypes"] = \App\Models\ingaz\JobType::with('translations')->get();
        return view($this->folderPath . '.deportation-stage', $data);
    }

    /**
     * Show general shelter contracts (work_willingness = 'unknown' or null)
     */
    public function generalShelter(Request $request)
    {
        \Log::info('generalShelter method called');
        \Log::info('Request data: ' . json_encode($request->all()));
        \Log::info('Is AJAX: ' . ($request->ajax() ? 'true' : 'false'));

        $filterStatus = Request('s');

        if ($request->ajax()) {
            \Log::info('generalShelter AJAX request');
            try {
                $rows = Contract::where('status', ContractStatusEnum::Shelter->value)
                    ->with([
                        'maid',
                        'info',
                        'maid.nationality.translations',
                        'maid.job_types.job_type.translations',
                        'info.shelter.translations'
                    ])
                    ->whereHas('info', function ($query) {
                        $query->whereIn('wants_to_work', ['unknown', null]);
                    });

                // Apply same filters as index method
                $this->applyFilters($rows, $request);

                $rows = $rows->latest();

                // Handle export request
                if ($request->has('export') && $request->input('export') == '1') {
                    return $this->exportData($rows->get());
                }

                // Debug: Get actual data before DataTables
                $actualData = $rows->get();
                \Log::info('Actual data count: ' . $actualData->count());
                if ($actualData->count() > 0) {
                    \Log::info('First record: ' . json_encode($actualData->first()->toArray()));
                }

                $dataTable = $this->buildDataTable($rows);

                \Log::info('DataTable response created successfully');
                \Log::info('DataTable response: ' . json_encode($dataTable->getData()));

                return $dataTable;
            } catch (\Exception $e) {
                \Log::error('Error in generalShelter: ' . $e->getMessage());
                \Log::error('Stack trace: ' . $e->getTraceAsString());
                return response()->json(['error' => 'حدث خطأ في عرض البيانات: ' . $e->getMessage()], 500);
            }
        }

        \Log::info('generalShelter returning view');
        $data["dataTableRoute"] = route($this->mainRoute . ".generalShelter");
        $data["modalType"] = "modal-lg";
        $data["nationalities"] = \App\Models\ingaz\Nationality::with('translations')->get();
        $data["shelterPlaces"] = \App\Models\ShelterPlaces::with('translations')->get();
        $data["jobTypes"] = \App\Models\ingaz\JobType::with('translations')->get();
        return view($this->folderPath . '.general-shelter', $data);
    }

    /**
     * Apply filters to query
     */
    private function applyFilters($rows, $request)
    {
        // Apply filters
        if ($request->filled('contract_number')) {
            $contractNumber = $request->input('contract_number');
            $rows->where('id', 'like', '%' . $contractNumber . '%');
        }

        if ($request->filled('nationality')) {
            $nationality = $request->input('nationality');
            $rows->whereHas('maid.nationality', function ($query) use ($nationality) {
                $query->where('id', $nationality);
            });
        }

        if ($request->filled('shelter')) {
            $shelter = $request->input('shelter');
            $rows->whereHas('info', function ($query) use ($shelter) {
                $query->where('shelter_id', $shelter);
            });
        }

        if ($request->filled('receipt_source')) {
            $receiptSource = $request->input('receipt_source');
            $rows->whereHas('info', function ($query) use ($receiptSource) {
                $query->where('receipt_source', $receiptSource);
            });
        }

        if ($request->filled('status')) {
            $guaranteeStatus = $request->input('status');
            $today = now();

            if ($guaranteeStatus === 'within_guarantee') {
                // Within guarantee period (90 days from arrival)
                $rows->whereHas('info', function ($query) use ($today) {
                    $query->where(function ($q) use ($today) {
                        $q->where(function ($subQ) use ($today) {
                            $subQ->whereNotNull('visa_milad_date')
                                 ->whereDate('visa_milad_date', '>=', $today->copy()->subDays(90));
                        })->orWhere(function ($subQ) use ($today) {
                            $subQ->whereNotNull('contract_date')
                                 ->whereDate('contract_date', '>=', $today->copy()->subDays(90));
                        })->orWhere(function ($subQ) use ($today) {
                            $subQ->whereNotNull('ingaz_from')
                                 ->whereDate('ingaz_from', '>=', $today->copy()->subDays(90));
                        });
                    });
                });
            } elseif ($guaranteeStatus === 'expired_guarantee') {
                // Expired guarantee period
                $rows->whereHas('info', function ($query) use ($today) {
                    $query->where(function ($q) use ($today) {
                        $q->where(function ($subQ) use ($today) {
                            $subQ->whereNotNull('visa_milad_date')
                                 ->whereDate('visa_milad_date', '<', $today->copy()->subDays(90));
                        })->orWhere(function ($subQ) use ($today) {
                            $subQ->whereNotNull('contract_date')
                                 ->whereDate('contract_date', '<', $today->copy()->subDays(90));
                        })->orWhere(function ($subQ) use ($today) {
                            $subQ->whereNotNull('ingaz_from')
                                 ->whereDate('ingaz_from', '<', $today->copy()->subDays(90));
                        });
                    });
                });
            }
        }

        if ($request->filled('work_willingness')) {
            $workWillingness = $request->input('work_willingness');
            $rows->whereHas('info', function ($query) use ($workWillingness) {
                $query->where('wants_to_work', $workWillingness);
            });
        }

        if ($request->filled('arrival_date')) {
            $arrivalDate = $request->input('arrival_date');
            $rows->whereHas('info', function ($query) use ($arrivalDate) {
                $query->where(function ($q) use ($arrivalDate) {
                    $q->whereDate('visa_milad_date', $arrivalDate)
                      ->orWhereDate('contract_date', $arrivalDate)
                      ->orWhereDate('ingaz_from', $arrivalDate);
                });
            });
        }

        if ($request->filled('return_date')) {
            $returnDate = $request->input('return_date');
            $rows->whereHas('info', function ($query) use ($returnDate) {
                $query->whereDate('maid_date_back', $returnDate);
            });
        }

        return $rows;
    }

    /**
     * Get data for DataTables
     */
    public function getData(Request $request)
    {
        \Log::info('getData method called');
        \Log::info('Request URL: ' . $request->fullUrl());
        \Log::info('Request headers: ' . json_encode($request->headers->all()));
        \Log::info('Is AJAX: ' . ($request->ajax() ? 'true' : 'false'));

        try {
            $rows = Contract::where('status', ContractStatusEnum::Shelter->value)
                ->with([
                    'maid',
                    'user',
                    'info',
                    'maid.nationality.translations',
                    'maid.job_types.job_type.translations',
                    'info.shelter.translations'
                ]);

            // Apply filters
            $this->applyFilters($rows, $request);

            $rows = $rows->latest();

            // Handle export request
            if ($request->has('export') && $request->input('export') == '1') {
                return $this->exportData($rows->get());
            }

            $dataTable = $this->buildDataTable($rows);

            \Log::info('getData response created successfully');

            return $dataTable;
        } catch (\Exception $e) {
            \Log::error('Error in getData: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * إرجاع العقد
     */
    public function back($id)
    {
        try {
            $contract = Contract::findOrFail($id);

            // التحقق من أن العقد في الإيواء
            if ($contract->status !== ContractStatusEnum::Shelter->value) {
                return response()->json([
                    'success' => false,
                    'message' => 'العقد ليس في الإيواء'
                ], 400);
            }

            // إرجاع العقد إلى حالة "مكتمل" لأن العقود في الإيواء هي عقود مكتملة أصلاً
            $contract->status = ContractStatusEnum::Completed->value;

            // إزالة معلومات الإيواء
            if ($contract->info) {
                $contract->info->update([
                    'shelter_id' => null,
                    'wants_to_work' => null,
                    'maid_date_back' => null,
                    'receipt_source' => null
                ]);
            }

            $contract->save();

            // تسجيل النشاط
            activity()
                ->performedOn($contract)
                ->causedBy(auth('admin')->user())
                ->log('تم حذف العقد من الإيواء - العقد الآن في حالة مكتمل');

            return response()->json([
                'success' => true,
                'message' => 'تم حذف العقد من الإيواء بنجاح - العقد الآن في حالة مكتمل'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف العقد من الإيواء: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Build DataTable response
     */
    private function buildDataTable($rows)
    {
        \Log::info('buildDataTable called with rows count: ' . $rows->count());

        try {
            \Log::info('Starting DataTables::of');

            $dataTable = DataTables::of($rows)
                ->addIndexColumn()
                ->addColumn('contract_number', function ($row) {
                    return 'عقد رقم: ' . $row->id;
                })
                ->addColumn('maid_data', function ($row) {
                    \Log::info('Processing maid_data for contract: ' . $row->id);
                    if (!$row->maid) {
                        return '<span class="text-muted">غير محدد</span>';
                    }

                    $maidName = $row->maid->name ?: 'عاملة رقم: ' . $row->maid->id;
                    $passport = $row->maid->passport_num ?: 'غير محدد';

                    // Get job type
                    $jobType = 'غير محدد';
                    if ($row->maid->job_types && !$row->maid->job_types->isEmpty()) {
                        $jobTypeModel = $row->maid->job_types->first();
                        if ($jobTypeModel && $jobTypeModel->job_type) {
                            $locale = app()->getLocale();
                            $translation = $jobTypeModel->job_type->translations->where('locale', $locale)->first();
                            $jobType = $translation ? $translation->title : $jobTypeModel->job_type->translations->first()->title;
                        }
                    }

                    return '<div class="maid-info">
                                <strong>' . $maidName . '</strong><br>
                                <small class="text-muted">جواز: ' . $passport . '</small><br>
                                <small class="text-muted">نوع العمل: ' . $jobType . '</small>
                            </div>';
                })
                ->addColumn('client', function ($row) {
                    if (!$row->user) {
                        return '<span class="text-muted">غير محدد</span>';
                    }

                    $clientName = $row->user->name ?: 'عميل رقم: ' . $row->user->id;
                    $clientPhone = $row->user->phone ?: 'غير محدد';

                    return '<div class="user-info">
                                <strong>' . $clientName . '</strong><br>
                                <small class="text-muted">' . $clientPhone . '</small>
                            </div>';
                })
                ->addColumn('nationality', function ($row) {
                    if (!$row->maid || !$row->maid->nationality) {
                        return '<span class="text-muted">غير محدد</span>';
                    }

                    $locale = app()->getLocale();
                    $translation = $row->maid->nationality->translations->where('locale', $locale)->first();
                    $nationality = $translation ? $translation->sub_title : 'غير محدد';

                    return '<span class="badge bg-info">' . $nationality . '</span>';
                })
                ->addColumn('shelter', function ($row) {
                    if (!$row->info || !$row->info->shelter) {
                        return '<span class="text-muted">غير محدد</span>';
                    }

                    $locale = app()->getLocale();
                    $translation = $row->info->shelter->translations->where('locale', $locale)->first();
                    $shelter = $translation ? $translation->title : 'غير محدد';

                    return $shelter;
                })
                ->editColumn('arrival_date', function ($row) {
                    $arrivalDate = $row->info ?
                        ($row->info->visa_milad_date ?? $row->info->contract_date ?? $row->info->ingaz_from) : null;

                    if (!$arrivalDate) {
                        return '<span class="text-muted">غير محدد</span>';
                    }

                    return \Carbon\Carbon::parse($arrivalDate)->format('Y-m-d');
                })
                ->editColumn('return_date', function ($row) {
                    $returnDate = $row->info && $row->info->maid_date_back ?
                        \Carbon\Carbon::parse($row->info->maid_date_back)->format('Y-m-d') : null;

                    return $returnDate ?: '<span class="text-muted">غير محدد</span>';
                })
                ->addColumn('receipt_source', function ($row) {
                    if (!$row->info || !$row->info->receipt_source) {
                        return '<span class="text-muted">غير محدد</span>';
                    }

                    $receiptSource = __('shelter_worker_statuses.received_by.' . $row->info->receipt_source) ?: $row->info->receipt_source;
                    return $receiptSource;
                })
                ->addColumn('guarantee_status', function ($row) {
                    if (!$row->info) {
                        return '<span class="badge bg-secondary">غير محدد</span>';
                    }

                    $arrivalDate = $row->info->visa_milad_date ??
                                  $row->info->contract_date ??
                                  $row->info->ingaz_from;

                    if (!$arrivalDate) {
                        return '<span class="badge bg-secondary">غير محدد</span>';
                    }

                    try {
                        $arrivalDate = \Carbon\Carbon::parse($arrivalDate);
                        $today = \Carbon\Carbon::now();
                        $guaranteeEndDate = $arrivalDate->copy()->addDays(90);

                        if ($today->lte($guaranteeEndDate)) {
                            $daysRemaining = $today->diffInDays($guaranteeEndDate);
                            return '<span class="badge bg-success">ضمن الضمان (' . $daysRemaining . ' يوم)</span>';
                        } else {
                            $daysExpired = $guaranteeEndDate->diffInDays($today);
                            return '<span class="badge bg-danger">منتهي الضمان (' . $daysExpired . ' يوم)</span>';
                        }
                    } catch (\Exception $e) {
                        return '<span class="badge bg-secondary">غير محدد</span>';
                    }
                })
                ->addColumn('work_willingness', function ($row) {
                    $workWillingness = $row->info ? $row->info->wants_to_work : null;

                    if ($workWillingness === 'yes') {
                        return '<span class="badge bg-success">ترغب بالعمل</span>';
                    } elseif ($workWillingness === 'no') {
                        return '<span class="badge bg-warning">لا ترغب بالعمل</span>';
                    } else {
                        return '<span class="badge bg-secondary">غير محدد</span>';
                    }
                })
                ->addColumn('deportation_status', function ($row) {
                    // التحقق من وجود إجراءات ترحيل
                    $procedure = \App\Models\DeportationProcedure::where('contract_id', $row->id)->first();

                    if (!$procedure) {
                        return '<span class="badge bg-secondary">لم تبدأ</span>';
                    }

                    $html = '<div class="deportation-status">';

                    // نسبة الإكمال
                    $percentage = $procedure->completion_percentage;
                    $progressClass = $percentage < 50 ? 'bg-danger' : ($percentage < 100 ? 'bg-warning' : 'bg-success');

                    $html .= '<div class="progress mb-1" style="height: 20px;">
                                <div class="progress-bar ' . $progressClass . '" role="progressbar" style="width: ' . $percentage . '%">
                                    ' . $percentage . '%
                                </div>
                              </div>';

                    // حالة الإجراءات
                    if ($procedure->status === 'completed') {
                        $html .= '<span class="badge bg-success">مكتملة</span>';
                    } elseif ($procedure->trip_scheduled) {
                        $html .= '<span class="badge bg-info">تم جدولة رحلة</span>';
                    } elseif ($procedure->procedures_confirmed) {
                        $html .= '<span class="badge bg-primary">تم التأكيد - بانتظار الرحلة</span>';
                    } elseif ($procedure->status === 'in_progress') {
                        $html .= '<span class="badge bg-warning">قيد التنفيذ</span>';
                    } else {
                        $html .= '<span class="badge bg-secondary">في الانتظار</span>';
                    }

                    $html .= '</div>';

                    return $html;
                })
                ->addColumn('actions', function ($row) {
                    $actions = '<div class="dropdown actions-dropdown">
                                    <button class="btn btn-sm btn-primary dropdown-toggle actions-btn" type="button" aria-expanded="false" title="الإجراءات المتاحة">
                                        <i class="fas fa-cogs me-1"></i> الإجراءات
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-lg">';

                    // عرض التفاصيل
                    $actions .= '<li><a class="dropdown-item" href="' . route('ShelterContract.show', $row->id) . '">
                                    <i class="fas fa-eye"></i> عرض التفاصيل
                                </a></li>';

                    // تحرير
                    $actions .= '<li><a class="dropdown-item" href="' . route('ShelterContract.edit', $row->id) . '">
                                    <i class="fas fa-edit"></i> تحرير
                                </a></li>';

                    // حذف من الإيواء (متاح دائماً للعقود في الإيواء)
                    $actions .= '<li><a class="dropdown-item confirm-action" href="#" data-action="back" data-id="' . $row->id . '" data-text="هل أنت متأكد من حذف العقد من الإيواء؟ سيعود العقد إلى العقود المكتملة">
                                    <i class="fas fa-undo"></i> حذف من الإيواء
                                </a></li>';

                    // نقل الكفالة (فقط إذا كانت ترغب بالعمل)
                    if ($row->info && $row->info->wants_to_work === 'yes') {
                        $actions .= '<li><hr class="dropdown-divider"></li>';
                        $actions .= '<li><a class="dropdown-item confirm-action" href="#" data-action="transfer" data-id="' . $row->id . '" data-text="سيتم فتح صفحة إضافة طلب نقل كفالة">
                                        <i class="fas fa-exchange-alt"></i> إضافة طلب نقل كفالة
                                    </a></li>';
                    }

                    // إجراءات الترحيل (فقط إذا كانت لا ترغب بالعمل)
                    if ($row->info && $row->info->wants_to_work === 'no') {
                        $actions .= '<li><hr class="dropdown-divider"></li>';

                        // التحقق من وجود إجراءات ترحيل
                        $deportationProcedure = $row->deportationProcedure;

                        if ($deportationProcedure) {
                            // عرض إجراءات الترحيل الموجودة
                            $actions .= '<li><a class="dropdown-item" href="' . route('ShelterContract.deportation.procedures', $row->id) . '">
                                            <i class="fas fa-clipboard-check"></i> عرض إجراءات الترحيل
                                        </a></li>';
                        } else {
                            // بدء إجراءات الترحيل
                            $actions .= '<li><a class="dropdown-item confirm-action" href="#" data-action="start-deportation" data-id="' . $row->id . '" data-text="هل تريد بدء إجراءات الترحيل للعاملة؟">
                                            <i class="fas fa-plane-departure"></i> بدء إجراءات الترحيل
                                        </a></li>';
                        }
                    }

                    $actions .= '</ul></div>';

                    return $actions;
                })
                ->rawColumns(['maid_data', 'client', 'nationality', 'arrival_date', 'return_date', 'guarantee_status', 'work_willingness', 'deportation_status', 'actions'])
                ->toJson();

            \Log::info('DataTables processing completed');
            return $dataTable;

        } catch (\Exception $e) {
            \Log::error('Error in buildDataTable: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * بدء إجراءات الترحيل
     */
    public function startDeportation($id)
    {
        try {
            $contract = Contract::with(['maid', 'info'])->findOrFail($id);

            // التحقق من أن العقد في الإيواء
            if ($contract->status !== ContractStatusEnum::Shelter->value) {
                return response()->json([
                    'success' => false,
                    'message' => 'العقد ليس في الإيواء'
                ], 400);
            }

            // التحقق من أن العاملة لا ترغب بالعمل
            if ($contract->info && $contract->info->wants_to_work === 'yes') {
                return response()->json([
                    'success' => false,
                    'message' => 'العاملة ترغب بالعمل - يمكن نقل الكفالة بدلاً من الترحيل'
                ], 400);
            }

            // التحقق من عدم وجود إجراءات ترحيل سابقة
            $existingProcedure = \App\Models\DeportationProcedure::where('contract_id', $contract->id)
                ->whereIn('status', ['pending', 'in_progress'])
                ->first();

            if ($existingProcedure) {
                return response()->json([
                    'success' => false,
                    'message' => 'توجد إجراءات ترحيل قيد التنفيذ بالفعل'
                ], 400);
            }

            // إنشاء إجراءات ترحيل جديدة
            $procedure = \App\Models\DeportationProcedure::create([
                'contract_id' => $contract->id,
                'maid_id' => $contract->maid_id,
                'status' => 'pending',
                'started_at' => now(),
                'created_by' => auth('admin')->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم بدء إجراءات الترحيل بنجاح',
                'procedure_id' => $procedure->id
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * عرض صفحة إجراءات الترحيل
     */
    public function showDeportationProcedures($id)
    {
        $contract = Contract::with(['maid', 'user', 'info'])->findOrFail($id);

        $procedure = \App\Models\DeportationProcedure::where('contract_id', $contract->id)
            ->with(['createdBy', 'completedBy'])
            ->first();

        if (!$procedure) {
            return redirect()->route('ShelterContract.show', $id)
                ->with('error', 'لا توجد إجراءات ترحيل لهذا العقد');
        }

        return view('admin.ingaz.ShelterContract.deportation-procedures', compact('contract', 'procedure'));
    }

    /**
     * تحديث إجراءات الترحيل
     */
    public function updateDeportationProcedures(Request $request, $id)
    {
        \Log::info('updateDeportationProcedures called', [
            'id' => $id,
            'request_data' => $request->all()
        ]);

        try {
            $procedure = \App\Models\DeportationProcedure::findOrFail($id);

            // التحقق من أن الإجراءات غير مكتملة
            if ($procedure->status === 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن تعديل إجراءات مكتملة'
                ], 400);
            }

            // التحقق من طلب تأكيد الإجراءات
            if ($request->has('confirm_procedures') && $request->confirm_procedures) {
                if (!$procedure->areAllQuestionsAnswered()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'يجب الإجابة على جميع الأسئلة أولاً'
                    ], 400);
                }

                $procedure->update([
                    'procedures_confirmed' => true,
                    'confirmed_at' => now(),
                    'confirmed_by' => auth('admin')->id()
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'تم تأكيد اكتمال الإجراءات بنجاح'
                ]);
            }

            // التحقق من طلب إكمال الترحيل
            if ($request->has('complete_deportation') && $request->complete_deportation) {
                // التحقق من اكتمال جميع الإجراءات
                if (!$procedure->isAllProceduresCompleted()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'يجب إكمال جميع الإجراءات أولاً'
                    ], 400);
                }

                // التحقق من وجود رحلة مجدولة
                if (!$procedure->trip_scheduled) {
                    return response()->json([
                        'success' => false,
                        'message' => 'يجب جدولة رحلة أولاً'
                    ], 400);
                }

                // إكمال الترحيل
                $procedure->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'completed_by' => auth('admin')->id()
                ]);

                // تحديث حالة العقد إلى "تم الترحيل"
                $procedure->contract->update([
                    'status' => ContractStatusEnum::Back->value
                ]);

                // إزالة معلومات الإيواء
                if ($procedure->contract->info) {
                    $procedure->contract->info->update([
                        'shelter_id' => null,
                        'wants_to_work' => null,
                        'maid_date_back' => now(),
                        'receipt_source' => null
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'تم إكمال إجراءات الترحيل بنجاح',
                    'redirect' => route('ShelterContract.deported')
                ]);
            }

            // Update procedure data
            $procedure->update([
                'salary_received' => $request->input('salary_received') == '1' ? true : false,
                'salary_received_date' => $request->input('salary_received') == '1' ? $request->salary_received_date : null,
                'has_passport' => $request->input('has_passport') == '1' ? true : false,
                'passport_date' => $request->input('has_passport') == '1' ? $request->passport_date : null,
                'has_deportation_letter' => $request->input('has_deportation_letter') == '1' ? true : false,
                'deportation_letter_date' => $request->input('has_deportation_letter') == '1' ? $request->deportation_letter_date : null,
                'has_settlement_session' => $request->input('has_settlement_session') == '1' ? true : false,
                'settlement_date' => $request->input('has_settlement_session') == '1' ? $request->settlement_date : null,
                'has_final_exit' => $request->input('has_final_exit') == '1' ? true : false,
                'final_exit_date' => $request->input('has_final_exit') == '1' ? $request->final_exit_date : null,
                'notes' => $request->notes,
                'status' => 'in_progress'
            ]);

            // معالجة المرفقات
            if ($request->hasFile('attachments')) {
                $attachments = [];
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('deportation-attachments', 'public');
                    $attachments[] = [
                        'name' => $file->getClientOriginalName(),
                        'path' => $path,
                        'uploaded_at' => now()
                    ];
                }
                $procedure->attachments = array_merge($procedure->attachments ?? [], $attachments);
                $procedure->save();
            }

            // لا ننقل العقد تلقائياً - يجب أن يتم ذلك فقط عند النقر على "إكمال الترحيل"

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث إجراءات الترحيل بنجاح',
                'completion_percentage' => $procedure->completion_percentage
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * عرض العقود التي تم ترحيلها
     */
    public function deported(Request $request)
    {
        if ($request->ajax()) {
            $query = \App\Models\DeportationProcedure::with([
                'contract',
                'contract.maid',
                'contract.maid.nationality.translations',
                'contract.user',
                'completedBy'
            ])->where('status', 'completed');

            // تطبيق الفلاتر
            if ($request->filled('contract_number')) {
                $query->whereHas('contract', function($q) use ($request) {
                    $q->where('id', 'like', '%' . $request->contract_number . '%');
                });
            }

            if ($request->filled('maid_name')) {
                $query->whereHas('contract.maid', function($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->maid_name . '%');
                });
            }

            if ($request->filled('deportation_date')) {
                $query->whereDate('completed_at', $request->deportation_date);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('contract_number', function ($row) {
                    return 'عقد رقم: ' . $row->contract_id;
                })
                ->addColumn('maid_data', function ($row) {
                    if (!$row->contract || !$row->contract->maid) {
                        return '<span class="text-muted">غير محدد</span>';
                    }

                    $maid = $row->contract->maid;
                    $maidName = $maid->name ?: 'عاملة رقم: ' . $maid->id;
                    $passport = $maid->passport_num ?: 'غير محدد';

                    return '<div class="maid-info">
                                <strong>' . $maidName . '</strong><br>
                                <small class="text-muted">جواز: ' . $passport . '</small>
                            </div>';
                })
                ->addColumn('client', function ($row) {
                    if (!$row->contract || !$row->contract->user) {
                        return '<span class="text-muted">غير محدد</span>';
                    }

                    $user = $row->contract->user;
                    $userName = $user->name ?: 'عميل رقم: ' . $user->id;
                    $userPhone = $user->phone ?: 'غير محدد';

                    return '<div class="user-info">
                                <strong>' . $userName . '</strong><br>
                                <small class="text-muted">' . $userPhone . '</small>
                            </div>';
                })
                ->addColumn('nationality', function ($row) {
                    if (!$row->contract || !$row->contract->maid || !$row->contract->maid->nationality) {
                        return '<span class="text-muted">غير محدد</span>';
                    }

                    $locale = app()->getLocale();
                    $translation = $row->contract->maid->nationality->translations->where('locale', $locale)->first();
                    $nationality = $translation ? $translation->sub_title : 'غير محدد';

                    return '<span class="badge bg-info">' . $nationality . '</span>';
                })
                ->addColumn('deportation_date', function ($row) {
                    return $row->completed_at ? $row->completed_at->format('Y-m-d H:i') : 'غير محدد';
                })
                ->addColumn('completed_by', function ($row) {
                    return $row->completedBy ? $row->completedBy->name : 'غير محدد';
                })
                ->addColumn('status', function ($row) {
                    return '<span class="badge deported-badge">تم الترحيل</span>';
                })
                ->addColumn('actions', function ($row) {
                    $actions = '<div class="dropdown actions-dropdown">
                                    <button class="btn btn-sm btn-primary dropdown-toggle actions-btn" type="button" aria-expanded="false" title="الإجراءات المتاحة">
                                        <i class="fas fa-cogs me-1"></i> الإجراءات
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-lg">';

                    // عرض الإجراءات
                    $actions .= '<li><a class="dropdown-item" href="' . route('ShelterContract.deportation.procedures', $row->contract_id) . '">
                                    <i class="fas fa-clipboard-check"></i> عرض إجراءات الترحيل
                                </a></li>';

                    // عرض تفاصيل العقد
                    $actions .= '<li><a class="dropdown-item" href="' . route('ShelterContract.show', $row->contract_id) . '">
                                    <i class="fas fa-eye"></i> عرض تفاصيل العقد
                                </a></li>';

                    $actions .= '</ul></div>';

                    return $actions;
                })
                ->rawColumns(['maid_data', 'client', 'nationality', 'status', 'actions'])
                ->toJson();
        }

        return view('admin.ingaz.ShelterContract.deported');
    }
}
