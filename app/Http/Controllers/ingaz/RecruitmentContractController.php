<?php

namespace App\Http\Controllers\ingaz;

use App\Enums\ingaz\ContractStatusEnum;
use App\Enums\ingaz\OrderEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\ingaz\StoreContractRequest;
use App\Models\Contract;
use App\Models\ContractInfo;
use App\Models\ContractStatusLog;
use App\Models\ingaz\Contact;
use App\Models\ingaz\Maid;
use App\Models\ingaz\Nationality;
use App\Models\ingaz\Order;
use App\Models\ingaz\User;
use App\Models\ingaz\UserAddress;
use App\Models\TravelHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class RecruitmentContractController extends Controller
{
    public $folderPath = "admin.ingaz.RecruitmentContract";
    public $mainRoute = "RecruitmentContract";
    public $contractInfoData = [
        'visa_number',
        'visa_hijry_date',
        'visa_milad_date',
        'working_hours_num'
        ,
        'sponsorship_price',
        'salary_after_sponsorship',
        'monthly_cost',
        'contract_duration'
        ,
        'contract_value',
        'contract_date',
        'ingaz_from',
        'ingaz_to',
        'previous_sponsorship',
        'msaned_number',
        'tafwed_number'
    ];


    public function index(Request $request, $status = null)
    {
        if ($request->ajax()) {
            $status = $request->get('status', $status);
            if ($status)
                $rows = Contract::RecruitmentContract()->where('status', $status)->latest();
            else
                $rows = Contract::RecruitmentContract()->latest();

            return DataTables::of($rows)
                ->addIndexColumn()
                ->editColumn('status', function ($row) {
                    return getEnumValue('contract_statuses', $row->status);
                })
                ->editColumn('user_id', function ($row) {
                    return ($row->user) ? $row->user->name : '';
                })
                ->addColumn('show_details', function ($row) {
                    return '<a class="btn btn-md btn-secondary" href="' . route('contracts.details', $row->id) . '"> عرض العقد <i class="fa fa-file mr-2"></i></a>';
                })
                ->editColumn('phone', function ($row) {
                    return ($row->user) ? getPhoneWithTag($row->user->phone_code . $row->user->phone) : '';
                })
                ->editColumn('maid_id', function ($row) {
                    return ($row->maid) ? getImgTag($row->maid->cv_file, "80px", "80px") : '';
                })
                ->addColumn('updateStatus', function ($row) {
                    if ($row->status != ContractStatusEnum::Back->value && $row->status != ContractStatusEnum::Arrival->value && $row->status != ContractStatusEnum::Tazker->value) {
                        $lastContractStatusLog = $row->getLastContractStatusLog();
                        return '<button class="btn btn-success update-status-btn" data-bs-toggle="modal" data-bs-target="#updateStatusModal" data-row-id="' . $row->id . '" data-status="' . $row->status . '" data-date="' . ($lastContractStatusLog ? $lastContractStatusLog->date : \Carbon\Carbon::now()->format('Y-m-d')) . '">' . contractStatusConfirmBtnText($row->status) . '</button>';
                    } else if ($row->status == ContractStatusEnum::Tazker->value) {
                        // check if there is a travel history for this contract
                        $travelHistory = TravelHistory::where('order_id', $row->id)->first();
                        if ($travelHistory) {
                            return '<a class="btn btn-primary" href="' . route('travel.control', ['id' => $row->id, 'userId' => $row->user_id, 'maidId' => $row->maid_id]) . '">' . __("admin.show_travel") . ' <i class="fa fa-eye"></i></a>';
                        } else {
                            return '<a class="btn btn-success" href="' . route('travel.control', ['id' => $row->id, 'userId' => $row->user_id, 'maidId' => $row->maid_id]) . '">' . __("admin.add_travel") . ' <i class="fa fa-plus"></i></a>';
                        }
                    } else {
                        return '<span class="text-danger">--</span>';
                    }
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
                    if (@AdminGuardHasPerm(\App\PermKeys::REC_CONTRACT_DELETE)) {
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


    public function details($id)
    {
        $contract = Contract::with('user', 'maid', 'info', 'contract_status_logs')->findOrFail($id);
        $info = ContractInfo::where('contract_id', $contract->id)->first();
        //        return $contract;
        return view('admin.ingaz.contract.details', compact('contract', 'info'));
    }

    public function create()
    {
        $orderId = request()->orderId;
        // عقد الاستقدام بيكون للطلبات من نوع استقدام ومنتهية
        $endedOrders = Order::where('job_type_id', 1)->whereIn('status', [OrderEnum::Done->value])->latest()->get();
        $countries = Nationality::all();
        // select maids
        $all_available_maids = Maid::Shown()->latest()->get();
        return view($this->folderPath . '.create', compact('endedOrders', 'countries', 'all_available_maids', 'orderId'));
    }


    public function store(StoreContractRequest $request)
    {
        // Initialize contract data array
        $contractData = [
            'status' => ContractStatusEnum::New ->value,
            'maid_id' => $request->maid_id,
        ];

        // Scenario 1: User selects an existing order to create a contract
        if ($request->order_id) {
            $order = Order::find($request->order_id);
            $contractData['order_id'] = $order->id;
            $contractData['user_id'] = $order->user_id;
            $contractData['maid_id'] = $order->maid_id;
        } // Scenario 2: Admin creates a contract for a new client
        else {
            $phoneExists = User::where('phone', $request->client_phone)->first();
            if ($phoneExists) {
                return jsonError(__("admin.phone_exists"));
            }
            // Create new user
            $newUser = User::create([
                'name' => $request->client_name,
                'phone_code' => "+966",
                'phone' => $request->client_phone,
                'nationality_id' => $request->nationality_id,
                'email' => $request->client_email,
                'national_num' => $request->client_national_num,
                'phone2' => $request->phone2,
                'city' => $request->city,
                'rooms_num' => $request->beds_num,
                'family_num' => $request->family_num,
                'children' => $request->children,
                'job' => $request->job,
            ]);

            // Create user address
            UserAddress::create([
                'user_id' => $newUser->id,
                'address' => $request->address,
            ]);

            // Assign user id to contract data
            $contractData['user_id'] = $newUser->id;
        }

        // Create contract
        $contract = Contract::create($contractData);

        // Save contract info
        $contractInfo = $request->only($this->contractInfoData);
        $contractInfo['contract_id'] = $contract->id;
        ContractInfo::create($contractInfo);

        // Save contract status log
        ContractStatusLog::create([
            'contract_id' => $contract->id,
            'status' => ContractStatusEnum::New ->value,
            'date' => $request->contract_date,
        ]);

        return jsonSuccess();
    }


    public function show($id, Request $request)
    {
        $row = Contract::find($id);

        switch ($row->status) {
            case ContractStatusEnum::New ->value:
                $row->status = ContractStatusEnum::Msaned->value;
                break;
            case ContractStatusEnum::Msaned->value:
                $row->status = ContractStatusEnum::Tafwed->value;
                break;
            case ContractStatusEnum::Tafwed->value:
                $row->status = ContractStatusEnum::Taffez->value;
                break;
            case ContractStatusEnum::Taffez->value:
                $row->status = ContractStatusEnum::Tazker->value;
                break;
            case ContractStatusEnum::Tazker->value:
                $row->status = ContractStatusEnum::Arrival->value;
                break;
            case ContractStatusEnum::Sari->value:
                $row->status = ContractStatusEnum::Closed->value;
                break;
            default:
                break;
        }

        // Save the updated contract status
        $row->save();

        // Create or update the contract status log
        $row->contract_status_logs()->create([
            'status' => $row->status,
            'date' => $request->date,
        ]);

        return jsonSuccess();
    }

    public function back($id)
    {
        $contract = Contract::find($id);
        $newStatus = contractBackStatus($contract->status);
        $contract->update(['status' => $newStatus->value]);
        $contract->contract_status_logs()->create(['status' => $newStatus->value]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
    }

    public function destroy($id)
    {
        Contract::find($id)->delete();
        return jsonSuccess();
    }

    public function getMaidByNationality($nationalityId)
    {
        $maids = Maid::where('nationality_id', $nationalityId)->get();
        return response()->json($maids->toArray());
    }
}
