<?php

namespace App\Http\Controllers\ingaz;

use App\Enums\ingaz\ContractStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\ingaz\ShelterContactStore;
use App\Models\Contract;
use App\Models\RecruitmentOffices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class ShelterContractController extends Controller
{
    public $mainRoute = "ShelterContract";
    public $folderPath = "admin.ingaz.ShelterContract";

    public function index(Request $request)
    {
        $filterStatus = Request('s'); //will be used for filter when needed

        if ($request->ajax()) {
            try {
                $rows = Contract::where('status', ContractStatusEnum::Shelter->value)
                    ->with([
                        'maid',
                        'info',
                        'maid.nationality.translations',
                        'info.shelter.translations'
                    ]);
                //add status filter here when more status are added !!
                $rows = $rows->latest();

                // Log for debugging
                \Log::info('ShelterContract query', [
                    'count' => $rows->count(),
                    'sql' => $rows->toSql(),
                    'bindings' => $rows->getBindings()
                ]);

                return DataTables::of($rows)
                    ->addIndexColumn()
                    ->editColumn('status', function ($row) {
                        $status = getEnumValue('contract_statuses', $row->status);
                        if (!$status) {
                            return '<span class="text-muted">غير محدد</span>';
                        }
                        return '<span class="badge bg-info">' . $status . '</span>';
                    })
                    ->editColumn('maid_name', function ($row) {
                        if (!$row->maid) {
                            return '<span class="text-muted">غير محدد</span>';
                        }
                        return '<span class="text-primary">عاملة رقم: ' . $row->maid->id . '</span>';
                    })
                    ->editColumn('passport_number', function ($row) {
                        if (!$row->maid || !$row->maid->passport_num) {
                            return '<span class="text-muted">غير محدد</span>';
                        }
                        return '<span class="text-info">' . $row->maid->passport_num . '</span>';
                    })
                    ->editColumn('maid_type', function ($row) {
                        if (!$row->type) {
                            return '<span class="text-muted">غير محدد</span>';
                        }

                        switch($row->type) {
                            case 'recruitment':
                                return '<span class="badge bg-primary">استقدام</span>';
                            case 'rent':
                                return '<span class="badge bg-success">تأجير</span>';
                            case 'transfer':
                                return '<span class="badge bg-warning">نقل خدمات</span>';
                            default:
                                return '<span class="badge bg-secondary">' . $row->type . '</span>';
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
                    ->editColumn('guarantee_status', function ($row) {
                        if (!$row->info || !$row->info->arrival_date) {
                            return 'غير محدد';
                        }

                        try {
                            $arrivalDate = \Carbon\Carbon::parse($row->info->arrival_date);
                            $today = \Carbon\Carbon::now();
                            $guaranteeEndDate = $arrivalDate->copy()->addDays(90);

                            if ($today->gt($guaranteeEndDate)) {
                                return '<span class="text-danger">منتهي الضمان</span>';
                            } else {
                                $remainingDays = $today->diffInDays($guaranteeEndDate);
                                return '<span class="text-success">ضمن الضمان (' . $remainingDays . ' يوم متبقي)</span>';
                            }
                        } catch (\Exception $e) {
                            return 'خطأ في التاريخ';
                        }
                    })
                    ->editColumn('arrival_date', function ($row) {
                        if (!$row->info || !$row->info->arrival_date) {
                            return '<span class="text-muted">غير محدد</span>';
                        }
                        try {
                            $date = \Carbon\Carbon::parse($row->info->arrival_date)->format('Y-m-d');
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
                            return '<button class="btn btn-success update-status-btn" data-bs-toggle="modal" data-bs-target="#updateStatusModal" data-row-id="' . $row->id . '" data-status="' . $row->status . '">اغلاق العقد</button>';
                        else
                            return '<span>---</span>';
                    })
                    ->addColumn('back', function ($row) {
                        if ($row->status == ContractStatusEnum::Back->value) {
                            return '<span class="text-danger">مرتجع</span>';
                        } else {
                            return '<button class="btn btn-danger backContract" data-id="' . $row->id . '">ارجاع العقد <i class="fa fa-undo"></i> </button>';
                        }
                    })
                    ->addColumn('actions', function ($row) {
                        $buttons = '';

                        // View button
                        $buttons .= '<a href="' . route($this->mainRoute . ".show", $row->id) . '" class="btn btn-sm btn-info me-1" title="عرض"><i class="fa fa-eye"></i></a>';

                        // Edit button
                        $buttons .= '<a href="' . route($this->mainRoute . ".edit", $row->id) . '" class="btn btn-sm btn-warning me-1" title="تعديل"><i class="fa fa-edit"></i></a>';

                        // Delete button
                        if (@AdminGuardHasPerm(\App\PermKeys::SHELTER_CONTRACT_DELETE)) {
                            $buttons .= deleteButton(route($this->mainRoute . ".destroy", $row->id));
                        }

                        return $buttons;
                    })
                    ->escapeColumns([])
                    ->make(true);
            } catch (\Exception $e) {
                \Log::error('Error in ShelterContract index: ' . $e->getMessage());
                return response()->json(['error' => 'حدث خطأ في عرض البيانات'], 500);
            }
        }

        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["modalType"] = "modal-lg";
        return view($this->folderPath . '.index', $data);
    }


    public function create()
    {
        $shelters = \App\Models\ShelterPlaces::latest()->get();
        $contracts = Contract::RecruitmentContract()
            ->where('status', '!=', ContractStatusEnum::Back->value)
            ->where('status', '!=', ContractStatusEnum::Shelter->value)
            ->latest()
            ->get();
        return view($this->folderPath . '.create', compact('shelters', 'contracts'));
    }


    public function store(ShelterContactStore $request)
    {
        $contract = Contract::find($request->contract_id);
        $contract->info()->update([
            'shelter_id' => $request->shelter_id,
            'maid_date_back' => $request->maid_date_back,
            'salary_final_status' => $request->salary_final_status,
            'duration_from_enter' => $request->duration_from_enter,
            'back_reasons' => $request->back_reasons,
        ]);
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
        //
    }


    public function update(Request $request, $id)
    {
        //
    }


    public function destroy($id)
    {
        //
    }

    public function getContractDetails(Request $request)
    {
        $contract = Contract::with('info')->find($request->contract_id);
        if (!$contract) {
            return response()->json(['error' => 'Contract not found'], 404);
        }

        // Log the entire contract object for debugging
        \Log::info('Full Contract Object', [
            'contract' => $contract->toArray(),
            'info' => $contract->info ? $contract->info->toArray() : null
        ]);

        // Try to get arrival date from different possible locations
        $arrivalDate = null;
        if ($contract->info) {
            // Check all possible date fields
            $arrivalDate = $contract->info->arrival_date ??
                          $contract->info->visa_milad_date ??
                          $contract->info->contract_date ??
                          $contract->info->ingaz_from;
        }

        // Log the found arrival date
        \Log::info('Found Arrival Date', [
            'arrival_date' => $arrivalDate,
            'contract_id' => $request->contract_id,
            'all_dates' => $contract->info ? [
                'arrival_date' => $contract->info->arrival_date,
                'visa_milad_date' => $contract->info->visa_milad_date,
                'contract_date' => $contract->info->contract_date,
                'ingaz_from' => $contract->info->ingaz_from
            ] : null
        ]);

        return response()->json([
            'arrival_date' => $arrivalDate,
            'contract_info' => $contract->info,
            'debug_info' => [
                'has_info' => $contract->info ? true : false,
                'info_fields' => $contract->info ? array_keys($contract->info->toArray()) : [],
                'all_dates' => $contract->info ? [
                    'arrival_date' => $contract->info->arrival_date,
                    'visa_milad_date' => $contract->info->visa_milad_date,
                    'contract_date' => $contract->info->contract_date,
                    'ingaz_from' => $contract->info->ingaz_from
                ] : null
            ]
        ]);
    }
}
