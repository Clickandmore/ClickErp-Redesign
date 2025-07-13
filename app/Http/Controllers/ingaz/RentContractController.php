<?php

namespace App\Http\Controllers\ingaz;

use App\Models\Contract;
use App\Models\ingaz\Maid;
use App\Models\ingaz\User;
use App\Models\ingaz\Order;
use App\Models\ContractInfo;
use Illuminate\Http\Request;
use App\Enums\ingaz\OrderEnum;
use App\Models\ShelterWorkers;
use App\Models\ContractStatusLog;
use App\Models\ingaz\Nationality;
use App\Models\ingaz\UserAddress;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Enums\ingaz\ContractStatusEnum;
use Yajra\DataTables\Facades\DataTables;
use App\Http\Actions\ingaz\ContractAction;
use App\Http\Requests\ingaz\StoreContractRequest;
use App\Http\Actions\ingaz\RecruitmentOfficeAction;

class RentContractController extends Controller
{
    public $folderPath = "admin.ingaz.RentContract";
    public $mainRoute = "RentContract";
    public $contractInfoData = [
        'visa_number', 'visa_milad_date', 'working_hours_num'
        , 'sponsorship_price', 'salary_after_sponsorship', 'monthly_cost', 'contract_duration'
        , 'contract_value', 'contract_date', 'ingaz_from', 'ingaz_to', 'previous_sponsorship',
        'msaned_number', 'tafwed_number'
    ];

    public function index(Request $request, $status = null)
    {
        if ($request->ajax()) {
            $status = $request->get('status', $status);
            if ($status)
                $rows = Contract::where('status', $status)->latest();
            else
                $rows = Contract::RentContract()->latest();

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
                ->addColumn('remainingDays', function ($row) {
                    $CalcContractRemaining = ahxCalcContractRemaining($row->id);
                    if($CalcContractRemaining > 0){
                       return  $CalcContractRemaining . ' '. __('ingaz.days');
                    }else{
                        return __('ingaz.ended');
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
                        return '<button class="btn btn-danger backContract" data-id="'.$row->id.'">ارجاع العقد <i class="fa fa-undo"></i> </button>';
                    }
                })
                ->addColumn('actions', function ($row) {

                    $printButton = '<a target="_blank" href="' . route('print.contract', $row->id) . '" class="m-2 btn btn-success btn-block btn-custom btn-sm"><i class="fa fa-print"></i></a>';


                    // $printButton = '<a href="' . route('printRentContract',$row->id ) . '" class="m-2 btn btn-success btn-block btn-custom print_contract printButton btn-sm"><i class="fa fa-print"></i></a>';
                    $editButton = '';
                    $deleteButton = '';

                    $editButton = ahxEditBtn(route(('getContractEdit'), $row->id));

                    if (@AdminGuardHasPerm(\App\PermKeys::RENT_CONTRACT_DELETE)) {
                        $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    }
                    return $printButton . " " . $editButton . " " . $deleteButton;
                })
                ->escapeColumns([])
                ->make(true);
        }

        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["modalType"] = "modal-lg";
        return view($this->folderPath . '.index', $data);
    }


    public function create(RecruitmentOfficeAction $officeAction)
    {
        // عقد الاستقدام بيكون للطلبات من نوع استقدام ومنتهية
        $endedOrders = Order::where('job_type_id',1)->whereIn('status', [OrderEnum::Done->value])->latest()->get();
        $countries = Country::active()->ordered()->get();
        $offices = $officeAction->getAllOffices();

        // select maids
        $all_available_maids = Maid::Shown()->latest()->get();

        return view($this->folderPath . '.create', compact('endedOrders', 'countries', 'all_available_maids','offices'));
    }


    public function store(StoreContractRequest $request,ContractAction $action)
    {
        // Initialize contract data array
        $contractData = [
            'status' => ContractStatusEnum::Sari->value,
            'maid_id' => $request->maid_id,
        ];

        // Scenario 1: User selects an existing order to create a contract
        if ($request->order_id) {
            $order = Order::find($request->order_id);
            $contractData['order_id'] = $order->id;
            $contractData['user_id'] = $order->user_id;
            $contractData['maid_id'] = $order->maid_id;

            //update user incoming inputs
            $clientUser = User::find($order->user_id);
            // dd($request->client_nationality_id, $request->client_email);
            $clientUser->update([
                'name' => $request->client_name,
                'phone' => $request->client_phone,
                'nationality_id' => $request->client_nationality_id,
                'email' => $request->client_email,
                'national_num' => $request->client_national_num,
                'birthdate' => $request->client_birthdate,
                'phone2' => $request->phone2,
                'address' => $request->address,
                'city' => $request->city,
                // 'beds_num' => $request->beds_num,
                'family_num' => $request->family_num,
                'children' => $request->children,
                'job' => $request->job,
            ]);


        }
        // Scenario 2: Admin creates a contract for a new client
        else {
            $newUser = $action->saveUserData($request);
            $contractData['user_id'] = $newUser->id;
        }

        // Create contract
        $contract = Contract::create($contractData);

        // Save contract info
        $contractInfo = $request->only($this->contractInfoData);
        $action->saveContractInfo($contractInfo,$contract);

        // Save contract status log
        $action->saveContractStatus($contract,$contractData['status'],$request);

        return jsonSuccess();
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    public function destroy($id)
    {
        $data = Contract::find($id);
        if (!$data) {
            $maid_id = $data->maid_id;
            $type = $data->type;
            if($type == 'maid'){
                $maid = Maid::find($maid_id);
                if($maid){
                    $maid->update([
                        'available' => '1'
                    ]);
                }
            }elseif($type == 'shelter_worker'){
                $maid = ShelterWorkers::find($maid_id);
                if($maid){
                    $maid->update([
                        'contract_id' => null
                    ]);
                }
            }
        }
        @$data->delete();
        return jsonSuccess();
    }


}
