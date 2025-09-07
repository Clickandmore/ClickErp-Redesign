<?php

namespace App\Http\Controllers\Admin;

use App\Keys;
use App\Models\TravelHistory;
use Carbon\Carbon;
use App\Models\Contract;
use App\Models\ingaz\Maid;
use App\Models\ContractInfo;
use Illuminate\Http\Request;
use App\Models\ShelterWorkers;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Enums\ingaz\ContractStatusEnum;
use App\Models\ingaz\Setting;
use App\Models\RentalContractTerm;

class AdminContractController extends Controller
{
    public function getContractEdit($contractId)
    {
        $data = Contract::with('info')->find($contractId);
        $info = ContractInfo::where('contract_id', $contractId)->first();
        if (!$data) {
            return redirect()->back();
        }
        return view('admin.contract.edit', compact('data', 'info'));
    }

    public function updateContractInfo(Request $request)
    {
        // Validate the incoming request data
        $validator = Validator::make($request->all(), [
            'contract_id' => 'required|integer|exists:contracts,id',
            'contract_duration' => 'required|integer',
            'contract_date' => 'required|date',
        ]);

        // If validation fails, redirect back with errors
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $contract_id = $request->contract_id;
        $contract = Contract::find($contract_id);
        if (!$contract) {
            return redirect()->back();
        }

        // Use info() method to access the relationship
        $infos = ContractInfo::updateOrCreate(
            [
                'contract_id' => $contract_id,
            ],
            [
                'contract_duration' => $request->contract_duration,
                'monthly_cost' => $request->monthly_cost,
                'contract_date' => $request->contract_date,
            ]
        );

        return redirect(route('RentContract.index'))->with('success', __('ingaz.success'));
    }

    public function maidsLoopContractsAndSetAvailableWhenDoneCronJob()
    {
        $count = $maidsLooped = $failedUpdates = 0;

        // Retrieve all unavailable maids
        $getUnavailableMaids = Maid::where('available', 0)->get();

        // Check if there are any unavailable maids
        if ($getUnavailableMaids->isNotEmpty()) {
            foreach ($getUnavailableMaids as $maid) {
                $maidsLooped++;
                $maidRemainingContractDays = ahxMaidContractRemaining($maid->id);

                // If no remaining contract days, update maid to available
                if ($maidRemainingContractDays == 0) {
                    $updated = $maid->update(['available' => 1]);

                    if ($updated) {
                        $count++;
                    } else {
                        $failedUpdates++;
                    }
                }
            }
        }
        // Retrieve all unavailable maids
        $getUnavailableShelterWorkers = ShelterWorkers::whereNoyNull(Keys::contract_id)->get();

        // Check if there are any unavailable maids
        if ($getUnavailableShelterWorkers->isNotEmpty()) {
            foreach ($getUnavailableShelterWorkers as $maid) {
                $maidsLooped++;
                $maidRemainingContractDays = ahxWorkerContractRemaining($maid->id);

                // If no remaining contract days, update maid to available
                if ($maidRemainingContractDays == 0) {
                    $updated = $maid->update([Keys::contract_id => null]);

                    if ($updated) {
                        $count++;
                    } else {
                        $failedUpdates++;
                    }
                }
            }
        }

        // Return status message
        return 'done - maidsLooped:' . $maidsLooped . ' - Updated to available count:' . $count . ' - failed Updates:' . $failedUpdates;
    }

    public function printRentContract($contractId)
    {
        $data = Contract::find($contractId);
        if (!$data) {
            return redirect()->back();
        }
        $info = getContractInfo($contractId);

        $contractDuration = @$info->contract_duration ?? 0;
        $contractDate = Carbon::parse(@$info->contract_date);
        $info['contract_expire_date'] = $contractDate ? date('Y-m-d', strtotime($contractDate . ' + ' . $contractDuration . ' days')) : '-------------';
        return view('admin.print.rent_contract', compact('data', 'info'));
    }

    public function printRentalContract($contractId)
    {
        try {
            $contract = \App\Models\RentalContract::with([
                'user.nationality',
                'maid.nationality.translations',
                'maid.religion.translations',
                'info',
                'marketer'
            ])->findOrFail($contractId);

                        // Get settings for contract printing
            $contractSettings = [];
            $settingKeys = [
                'company_name', 'license_number', 'license_date', 'commercial_register',
                'commercial_date', 'city', 'address', 'po_box', 'postal_code',
                'phone', 'mobile', 'fax', 'email', 'default_trial_period',
                'default_working_hours', 'daily_deduction', 'notice_period', 'logo'
            ];

            foreach ($settingKeys as $key) {
                $setting = Setting::where('key', 'rent_contract_' . $key)->first();
                $contractSettings[$key] = $setting ? $setting->value : null;
            }

            // Get contract terms from database
            $termsPage1 = RentalContractTerm::active()->byPage(1)->ordered()->get();
            $termsPage2 = RentalContractTerm::active()->byPage(2)->ordered()->get();
            $termsPage3 = RentalContractTerm::active()->byPage(3)->ordered()->get();

            return view('admin.print.rental-contract-dynamic', compact('contract', 'contractSettings', 'termsPage1', 'termsPage2', 'termsPage3'));

        } catch (\Exception $e) {
            \Log::error('Error printing rental contract: ' . $e->getMessage());
            return redirect()->back()->with('error', 'حدث خطأ أثناء طباعة العقد');
        }
    }

    public function checkTazkerContracts()
    {
        try {
            // Get all contracts with tazker status
            $tazkerContracts = Contract::where('status', ContractStatusEnum::Tazker)->get();

            foreach ($tazkerContracts as $contract) {
                // Get related travel history
                $travelHistory = TravelHistory::where('order_id', $contract->id)->first();

                if ($travelHistory) {
                    $arrivalDateTime = Carbon::parse($travelHistory->arrival_date . ' ' . $travelHistory->arrival_time);

                    // Check if arrival date/time has passed
                    if ($arrivalDateTime->isPast()) {
                        // Update contract status to under warranty
                        $contract->update([
                            'status' => ContractStatusEnum::UnderWarranty
                        ]);
                    }
                }
            }
            return response()->json([
                'status' => true,
                'message' => 'Contracts checked and updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error checking contracts: ' . $e->getMessage()
            ]);
        }
    }

    public function checkWarrantyContracts()
    {
        try {
            // Get warranty period from settings
            $warrantyPeriod = Setting::where('key', 'warranty_period')->first()->value ?? 90; // default 90 days

            // Get all contracts under warranty
            $contracts = Contract::where('status', ContractStatusEnum::UnderWarranty)->get();

            foreach ($contracts as $contract) {
                // Get travel history for this contract
                $travelHistory = TravelHistory::where('order_id', $contract->id)
                    ->whereNotNull('arrival_date')
                    ->whereNotNull('arrival_time')
                    ->first();

                if ($travelHistory) {
                    // Combine arrival date and time
                    $arrivalDateTime = Carbon::parse($travelHistory->arrival_date . ' ' . $travelHistory->arrival_time);

                    // Add warranty period to arrival datetime
                    $warrantyEndDate = $arrivalDateTime->addDays($warrantyPeriod);

                    // If warranty period has passed, close the contract
                    if ($warrantyEndDate->isPast()) {
                        $contract->update([
                            'status' => ContractStatusEnum::Closed
                        ]);
                    }
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'Warranty contracts checked successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error checking warranty contracts: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * حفظ توقيع العقد
     */
    public function saveSignature(Request $request, $contractId)
    {
        try {
            // التحقق من وجود العقد
            $contract = \App\Models\RentalContract::findOrFail($contractId);

            // التحقق من البيانات المرسلة
            $request->validate([
                "signature" => "required|string"
            ]);

            // تحويل base64 إلى ملف
            $signatureData = $request->signature;

            // إزالة header من base64
            $signatureData = str_replace("data:image/png;base64,", "", $signatureData);
            $signatureData = str_replace(" ", "+", $signatureData);

            // فك تشفير base64
            $signatureImage = base64_decode($signatureData);

            if (!$signatureImage) {
                return response()->json([
                    "success" => false,
                    "message" => "بيانات التوقيع غير صحيحة"
                ], 400);
            }

            // إنشاء اسم فريد للملف
            $fileName = "signature_" . $contractId . "_" . time() . ".png";

            // حفظ الملف في storage
            $filePath = "signatures/" . $fileName;
            \Storage::disk("public")->put($filePath, $signatureImage);

            // حفظ مسار الملف في قاعدة البيانات
            $contract->update([
                "signature" => $fileName
            ]);

            // إعادة تحميل العقد للتأكد من التحديث
            $contract->refresh();

            return response()->json([
                "success" => true,
                "message" => "تم حفظ التوقيع بنجاح",
                "signature_path" => $filePath,
                "signature_file" => $fileName
            ]);

        } catch (\Exception $e) {
            \Log::error("Error saving signature: " . $e->getMessage());

            return response()->json([
                "success" => false,
                "message" => "حدث خطأ أثناء حفظ التوقيع: " . $e->getMessage()
            ], 500);
        }
    }

}
