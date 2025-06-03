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
            $rows = Contract::where('status', ContractStatusEnum::Shelter->value);
            //add status filter here when more status are added !!
            $rows = $rows->latest();
            return DataTables::of($rows)
                ->addIndexColumn()
                ->editColumn('status', function ($row) {
                    return getEnumValue('contract_statuses', $row->status);
                })
                ->editColumn('user_id', function ($row) {
                    return ($row->user) ? $row->user->name : '';
                })
                ->editColumn('phone', function ($row) {
                    return ($row->user) ? getPhoneWithTag($row->user->phone_code . $row->user->phone) : '';
                })
                ->editColumn('maid_id', function ($row) {
                    return ($row->maid) ? getImgTag($row->maid->cv_file, "80px", "80px") : '';
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
                    $deleteButton = '';
                    if (@AdminGuardHasPerm(\App\PermKeys::SHELTER_CONTRACT_DELETE)) {
                        $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    }
                    return $deleteButton;
                })
                ->escapeColumns([])
                ->make(true);
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
