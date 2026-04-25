<?php

namespace App\Http\Controllers\Admin;

use App\Models\auth\Admin;
use App\Models\Contract;
use App\Models\ContractInfo;
use App\Models\ingaz\Maid;
use App\Models\ingaz\Nationality;
use App\Models\ingaz\Order;
use App\Models\ingaz\OrderDetail;
use App\Models\ingaz\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminFullOrderContractController extends Controller
{

    public function viewFullOrderContract(Request $request)
    {
        //clients
        //nationalities
        //maids
        $clientsList = $this->usersList();
        $adminsList = Admin::query()
            ->where('admin_type', 'customer_service')
            ->where(function ($query) {
                $query->whereNull('status')->orWhere('status', 'active');
            })
            ->pluck('name', 'id')
            ->all();
        $nationalitiesList = Nationality::all();
        $maidsList = Maid::where('available', 1)
            ->whereHas('job_types', function ($query) {
                $query->where('job_type_id', 2);
            })
            ->pluck('passport_num', 'id')
            ->all();
        return view('admin.full_order_contract.create', compact('clientsList', 'nationalitiesList', 'maidsList', 'adminsList'));
    }

    public function createFullOrderContract(Request $request)
    {
        DB::beginTransaction();

        try {
            //choose client or add new
            //i get user_id
            $clientType = $request->client_type;
            if ($clientType == 'new') {
                $clientName = $request->client_name;
                $phoneCode = $request->phone_code;
                $phone = $request->phone;
                $password = $request->password;
                $nationality_id = $request->nationality_id;
                $email = $request->email;
                $birthdate = $request->birthdate;
                $national_num = $request->national_num;
                $phone2 = $request->phone2;
                $address = $request->address;
                $city = $request->city;
                $rooms_num = $request->rooms_num;
                $family_num = $request->family_num;
                $children = $request->children;
                $job = $request->job;

                $client = User::create([
                    'name' => $clientName,
                    'phone_code' => $phoneCode,
                    'phone' => $phone,
                    'password' => bcrypt($password), // Hash the password
                    'nationality_id' => $nationality_id,
                    'email' => $email,
                    'birthdate' => $birthdate,
                    'national_num' => $national_num,
                    'phone2' => $phone2,
                    'address' => $address,
                    'city' => $city,
                    'rooms_num' => $rooms_num,
                    'family_num' => $family_num,
                    'children' => $children,
                    'job' => $job,
                ]);
            } else {
                $client = User::find($request->client_id);
            }

            if (!$client) {
                return redirect()->back()->with('error', 'هذا العميل غير موجود');
            }

            $order = Order::create([
                'user_id' => $client->id,
                'maid_id' => $request->maid_id,
                'job_type_id' => 2,
                'status' => 'started',
                'payment_method' => 'online',
                'payment_type' => 'visa',
                'order_code' => generateRandomCodeAhx(5),
            ]);

            if (!$order) {
                return redirect()->back()->with('error', 'حدث خطأ بالطلب');
            }

            $maid = Maid::find($request->maid_id);
            @$maid->update([
                'available' => 0
            ]);

            $orderDetails = OrderDetail::updateOrCreate(
                [
                    'order_id' => $order->id,
                ],
                [
                    'order_id' => $order->id,
                    'admin_id' => $request->admin_id,
                ]
            );

            $contractData['order_id'] = $order->id;
            $contractData['user_id'] = $order->user_id;
            $contractData['maid_id'] = $order->maid_id;
            $contractData['status'] = 'sari';

            // Create contract
            $contract = Contract::create($contractData);

            $infos = ContractInfo::updateOrCreate(
                [
                    'contract_id' => $contract->id,
                ],
                [
                    'contract_duration' => $request->contract_duration,
                    'contract_date' => $request->contract_date,
                    'monthly_cost' => $request->monthly_cost,
                ]
            );

            DB::commit();

            return redirect()->back()->with('success', 'تمت عملية الاضافة بنجاح');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'حدث خطأ أثناء العملية: ' . $e->getMessage());
        }
    }


}
