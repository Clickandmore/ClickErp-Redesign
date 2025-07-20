<?php

namespace App\Http\Controllers\ingaz;

use App\Enums\ingaz\ContractStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\ingaz\ShelterContactStore;
use App\Models\Contract;
use App\Models\RecruitmentOffices;
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
        \Log::info('Request data: ' . json_encode($request->all()));
        \Log::info('Is AJAX: ' . ($request->ajax() ? 'true' : 'false'));

        $filterStatus = Request('s');

        if ($request->ajax()) {
            \Log::info('index AJAX request');
            try {
                $rows = Contract::where('status', ContractStatusEnum::Shelter->value)
                    ->with([
                        'maid',
                        'info',
                        'maid.nationality.translations',
                        'maid.job_types.job_type.translations',
                        'info.shelter.translations'
                    ]);

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
                \Log::error('Error in index: ' . $e->getMessage());
                \Log::error('Stack trace: ' . $e->getTraceAsString());
                return response()->json(['error' => 'حدث خطأ في عرض البيانات: ' . $e->getMessage()], 500);
            }
        }

        \Log::info('index returning view');
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["modalType"] = "modal-lg";
        $data["nationalities"] = \App\Models\ingaz\Nationality::with('translations')->get();
        $data["shelterPlaces"] = \App\Models\ShelterPlaces::with('translations')->get();
        $data["jobTypes"] = \App\Models\ingaz\JobType::with('translations')->get();
        return view($this->folderPath . '.index', $data);
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
        //
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
            $contractId = $request->get('contract_id');
            $contract = Contract::with(['maid', 'info'])->find($contractId);

            if (!$contract) {
                return response()->json([
                    'error' => 'العقد غير موجود',
                    'debug_info' => 'Contract ID: ' . $contractId
                ], 404);
            }

            // Get arrival date from contract info
            $arrivalDate = null;
            if ($contract->info && $contract->info->arrival_date) {
                $arrivalDate = $contract->info->arrival_date;
            }

            // If no arrival date in contract info, try to get it from maid
            if (!$arrivalDate && $contract->maid && $contract->maid->arrival_date) {
                $arrivalDate = $contract->maid->arrival_date;
            }

            return response()->json([
                'arrival_date' => $arrivalDate,
                'contract_id' => $contract->id,
                'contract_status' => $contract->status,
                'debug_info' => 'Contract found successfully',
                'has_info' => $contract->info ? 'yes' : 'no',
                'info_arrival_date' => $contract->info ? $contract->info->arrival_date : 'null',
                'maid_arrival_date' => $contract->maid ? $contract->maid->arrival_date : 'null',
                'final_arrival_date' => $arrivalDate,
                'contract_info_exists' => $contract->info ? 'yes' : 'no',
                'contract_info_id' => $contract->info ? $contract->info->id : 'null',
                'is_completed' => in_array($contract->status, ['completed', 'under_warranty', 'closed']) ? 'yes' : 'no'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'حدث خطأ أثناء جلب تفاصيل العقد',
                'debug_info' => $e->getMessage()
            ], 500);
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
        if ($request->filled('maid_name')) {
            $maidName = $request->input('maid_name');
            $rows->whereHas('maid', function ($query) use ($maidName) {
                $query->where('name', 'like', '%' . $maidName . '%')
                      ->orWhere('passport_num', 'like', '%' . $maidName . '%');
            });
        }

        if ($request->filled('passport_number')) {
            $passport = $request->input('passport_number');
            $rows->whereHas('maid', function ($query) use ($passport) {
                $query->where('passport_num', 'like', '%' . $passport . '%');
            });
        }

        if ($request->filled('maid_type')) {
            $maidType = $request->input('maid_type');
            $rows->whereHas('maid.job_types', function ($query) use ($maidType) {
                $query->where('job_type_id', $maidType);
            });
        }

        if ($request->filled('nationality')) {
            $nationality = $request->input('nationality');
            $rows->whereHas('maid.nationality', function ($query) use ($nationality) {
                $query->where('id', $nationality);
            });
        }

        if ($request->filled('shelter_location')) {
            $shelter = $request->input('shelter_location');
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

        if ($request->filled('guarantee_status')) {
            $guaranteeStatus = $request->input('guarantee_status');
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

        if ($request->filled('arrival_date')) {
            $arrivalDate = $request->input('arrival_date');
            $rows->whereHas('info', function ($query) use ($arrivalDate) {
                $query->whereDate('visa_milad_date', $arrivalDate)
                      ->orWhereDate('contract_date', $arrivalDate)
                      ->orWhereDate('ingaz_from', $arrivalDate);
            });
        }

        if ($request->filled('return_date')) {
            $returnDate = $request->input('return_date');
            $rows->whereHas('info', function ($query) use ($returnDate) {
                $query->whereDate('maid_date_back', $returnDate);
            });
        }

        if ($request->filled('work_willingness')) {
            $workWillingness = $request->input('work_willingness');
            $rows->whereHas('info', function ($query) use ($workWillingness) {
                $query->where('wants_to_work', $workWillingness);
            });
        }
    }

    /**
     * Build DataTable response
     */
    private function buildDataTable($rows)
    {
        // Add debugging
        \Log::info('buildDataTable called with rows count: ' . $rows->count());

        try {
            \Log::info('Starting DataTables::of');

            $dataTable = DataTables::of($rows)
                ->addIndexColumn()
                ->editColumn('status', function ($row) {
                    // Get work willingness status
                    $workWillingness = $row->info ? $row->info->wants_to_work : null;

                    if ($workWillingness === 'yes') {
                        return '<span class="badge bg-success">داخل الإيواء (متاح للنقل)</span>';
                    } elseif ($workWillingness === 'no') {
                        return '<span class="badge bg-warning">داخل الإيواء (مرحلة الترحيل)</span>';
                    } elseif ($workWillingness === 'unknown') {
                        return '<span class="badge bg-info">داخل الإيواء</span>';
                    } else {
                        return '<span class="badge bg-secondary">داخل الإيواء</span>';
                    }
                })
                ->editColumn('maid_name', function ($row) {
                    if (!$row->maid) {
                        return '<span class="text-muted">غير محدد</span>';
                    }

                    // If maid has a name, show it, otherwise show passport number
                    $displayName = $row->maid->name;
                    if (empty($displayName) && $row->maid->passport_num) {
                        $displayName = 'جواز رقم: ' . $row->maid->passport_num;
                    } elseif (empty($displayName)) {
                        $displayName = 'عاملة رقم: ' . $row->maid->id;
                    }

                    return '<span class="text-primary">' . $displayName . '</span>';
                })
                ->editColumn('passport_number', function ($row) {
                    if (!$row->maid || !$row->maid->passport_num) {
                        return '<span class="text-muted">غير محدد</span>';
                    }
                    return '<span class="text-info">' . $row->maid->passport_num . '</span>';
                })
                ->editColumn('maid_type', function ($row) {
                    if (!$row->maid || !$row->maid->job_types || $row->maid->job_types->isEmpty()) {
                        return '<span class="text-muted">غير محدد</span>';
                    }

                    // Get the first job type (assuming one maid can have multiple job types)
                    $jobType = $row->maid->job_types->first();
                    if (!$jobType || !$jobType->job_type) {
                        return '<span class="text-muted">غير محدد</span>';
                    }

                    // Get the translated title
                    $locale = app()->getLocale();
                    $translation = $jobType->job_type->translations->where('locale', $locale)->first();
                    $title = $translation ? $translation->title : $jobType->job_type->translations->first()->title;

                    // Determine badge color based on job type ID
                    switch($jobType->job_type_id) {
                        case 1: // استقدام
                            return '<span class="badge bg-primary">' . $title . '</span>';
                        case 2: // ايجار عمالة
                            return '<span class="badge bg-success">' . $title . '</span>';
                        case 3: // إستلام فورى
                            return '<span class="badge bg-warning">' . $title . '</span>';
                        default:
                            return '<span class="badge bg-secondary">' . $title . '</span>';
                    }
                })
                ->editColumn('nationality', function ($row) {
                    if (!$row->maid || !$row->maid->nationality) {
                        return '<span class="text-muted">غير محدد</span>';
                    }

                    // Get the translated name based on current locale
                    $locale = app()->getLocale();
                    $translation = $row->maid->nationality->translations->where('locale', $locale)->first();

                    if ($translation) {
                        return '<span class="text-success">' . $translation->sub_title . '</span>';
                    }

                    // Fallback to first available translation
                    $firstTranslation = $row->maid->nationality->translations->first();
                    if ($firstTranslation) {
                        return '<span class="text-success">' . $firstTranslation->sub_title . '</span>';
                    }

                    return '<span class="text-muted">غير محدد</span>';
                })
                ->editColumn('shelter_location', function ($row) {
                    if (!$row->info || !$row->info->shelter) {
                        return '<span class="text-muted">غير محدد</span>';
                    }

                    // Get the translated name based on current locale
                    $locale = app()->getLocale();
                    $translation = $row->info->shelter->translations->where('locale', $locale)->first();

                    if ($translation) {
                        return '<span class="text-info">' . $translation->title . '</span>';
                    }

                    // Fallback to first available translation
                    $firstTranslation = $row->info->shelter->translations->first();
                    if ($firstTranslation) {
                        return '<span class="text-info">' . $firstTranslation->title . '</span>';
                    }

                    return '<span class="text-muted">غير محدد</span>';
                })
                ->editColumn('receipt_source', function ($row) {
                    if (!$row->info || !$row->info->receipt_source) {
                        return '<span class="text-muted">غير محدد</span>';
                    }

                    // Get the receipt source name from language file
                    $receiptSource = __('shelter_worker_statuses.received_by.' . $row->info->receipt_source);

                    if ($receiptSource) {
                        return '<span class="badge bg-secondary">' . $receiptSource . '</span>';
                    }

                    return '<span class="text-muted">غير محدد</span>';
                })
                ->editColumn('guarantee_status', function ($row) {
                    // Get arrival date from available date fields
                    $arrivalDate = null;
                    if ($row->info) {
                        $arrivalDate = $row->info->visa_milad_date ??
                                      $row->info->contract_date ??
                                      $row->info->ingaz_from;
                    }

                    if (!$arrivalDate) {
                        return '<span class="text-muted">غير محدد</span>';
                    }

                    try {
                        $arrivalDate = \Carbon\Carbon::parse($arrivalDate);
                        $today = \Carbon\Carbon::now();
                        $guaranteeEndDate = $arrivalDate->copy()->addDays(90);

                        if ($today->gt($guaranteeEndDate)) {
                            return '<span class="text-danger">منتهي الضمان</span>';
                        } else {
                            $remainingDays = $today->diffInDays($guaranteeEndDate);
                            return '<span class="text-success">ضمن الضمان (' . $remainingDays . ' يوم متبقي)</span>';
                        }
                    } catch (\Exception $e) {
                        return '<span class="text-danger">خطأ في التاريخ</span>';
                    }
                })
                ->editColumn('work_willingness', function ($row) {
                    if (!$row->info || !$row->info->wants_to_work) {
                        return '<span class="text-muted">غير محدد</span>';
                    }

                    switch($row->info->wants_to_work) {
                        case 'yes':
                            return '<span class="badge bg-success">ترغب بالعمل</span>';
                        case 'no':
                            return '<span class="badge bg-danger">لا ترغب بالعمل</span>';
                        case 'unknown':
                            return '<span class="badge bg-secondary">غير محدد</span>';
                        default:
                            return '<span class="text-muted">غير محدد</span>';
                    }
                })
                ->editColumn('arrival_date', function ($row) {
                    // Get arrival date from available date fields
                    $arrivalDate = null;
                    if ($row->info) {
                        $arrivalDate = $row->info->visa_milad_date ??
                                      $row->info->contract_date ??
                                      $row->info->ingaz_from;
                    }

                    if (!$arrivalDate) {
                        return '<span class="text-muted">غير محدد</span>';
                    }
                    try {
                        $date = \Carbon\Carbon::parse($arrivalDate)->format('Y-m-d');
                        return '<span class="text-success">' . $date . '</span>';
                    } catch (\Exception $e) {
                        return '<span class="text-danger">خطأ في التاريخ</span>';
                    }
                })
                ->editColumn('return_date', function ($row) {
                    if (!$row->info || !$row->info->maid_date_back) {
                        return '<span class="text-muted">غير محدد</span>';
                    }
                    try {
                        $date = \Carbon\Carbon::parse($row->info->maid_date_back)->format('Y-m-d');
                        return '<span class="text-warning">' . $date . '</span>';
                    } catch (\Exception $e) {
                        return '<span class="text-danger">خطأ في التاريخ</span>';
                    }
                })
                ->addColumn('updateStatus', function ($row) {
                    if ($row->status != ContractStatusEnum::Back->value && $row->status != ContractStatusEnum::Closed->value)
                        return '<button class="btn btn-success update-status-btn" data-bs-toggle="modal" data-bs-target="#updateStatusModal" data-row-id="' . $row->id . '" data-status="' . $row->status . '">إغلاق العقد داخل الإيواء</button>';
                    else
                        return '<span>---</span>';
                })
                ->addColumn('back', function ($row) {
                    if ($row->status == ContractStatusEnum::Back->value) {
                        return '<span class="text-danger">مرتجع</span>';
                    } else {
                        return '<button class="btn btn-danger backContract" data-id="' . $row->id . '">ارجاع العقد داخل الإيواء <i class="fa fa-undo"></i> </button>';
                    }
                })
                ->addColumn('actions', function ($row) {
                    $buttons = '';

                    // View button
                    $buttons .= '<a href="' . route($this->mainRoute . ".show", $row->id) . '" class="btn btn-sm btn-info me-1" title="عرض"><i class="fa fa-eye"></i></a>';

                    // Edit button
                    $buttons .= '<a href="' . route($this->mainRoute . ".edit", $row->id) . '" class="btn btn-sm btn-warning me-1" title="تعديل"><i class="fa fa-edit"></i></a>';

                    // Delete button - removed permission check
                    $buttons .= deleteButton(route($this->mainRoute . ".destroy", $row->id));

                    return $buttons;
                })
                ->escapeColumns([]);

            \Log::info('DataTables columns configured, calling make()');

            $result = $dataTable->make(true);

            \Log::info('DataTable response created successfully');
            \Log::info('Response data count: ' . (isset($result->getData()->data) ? count($result->getData()->data) : 'No data'));

            // Debug: Log the actual response data
            $responseData = $result->getData();
            \Log::info('Response structure: ' . json_encode([
                'draw' => $responseData->draw ?? 'N/A',
                'recordsTotal' => $responseData->recordsTotal ?? 'N/A',
                'recordsFiltered' => $responseData->recordsFiltered ?? 'N/A',
                'dataCount' => isset($responseData->data) ? count($responseData->data) : 'N/A'
            ]));

            if (isset($responseData->data) && count($responseData->data) > 0) {
                \Log::info('First row in response: ' . json_encode($responseData->data[0]));
            }

            return $result;
        } catch (\Exception $e) {
            \Log::error('Error in buildDataTable: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }
}
