<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\admin\Controller;
use App\Http\Requests\ingaz\API\SponsorshipTransferRequest;
use App\Http\Resources\Api\SponsorshipTransferResource;
use App\Models\Contract;
use App\Models\ingaz\User;
use App\Models\SponsorshipTransfer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiSponsorshipTransferController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    // dashboard/app/Http/Controllers/Api/ApiSponsorshipTransferController.php
    public function index(Request $request)
    {
        $userId = auth()->id();

        $sql = SponsorshipTransfer::with([
            'contract',
            'oldSponsor',
            'newSponsor',
            'approvedBy',
            'transferredBy',
            'failedBy',
        ])->where(function ($q) use ($userId) {
            $q->where('old_sponsor_id', $userId)
                ->orWhere('new_sponsor_id', $userId);
        })->orderByDesc('created_at');

        // لو عندكم generalReturn في dashboard استخدمها مباشرة
        return generalReturn($request, $sql, SponsorshipTransferResource::class, __("api.All items"));
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(SponsorshipTransferRequest $request)
    {
        try {
            // التحقق من تسجيل الدخول
            if (!auth()->check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'يجب تسجيل الدخول لإرسال طلب نقل الخدمات'
                ], 401);
            }

            DB::beginTransaction();

            // التحقق من العقد
            $contract = Contract::with(['info'])->find($request->contract_id);
            if (!$contract) {
                return response()->json([
                    'success' => false,
                    'message' => 'العقد غير موجود'
                ], 404);
            }

            // التحقق من أن العقد يخص المستخدم الحالي
            if ($contract->user_id != auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن نقل خدمات عقد لا يخصك'
                ], 403);
            }

            // التحقق من أن العقد متاح للنقل
            if (!$contract->info || $contract->info->wants_to_work !== 'yes') {
                return response()->json([
                    'success' => false,
                    'message' => 'هذا العقد غير متاح للنقل'
                ], 400);
            }

            if ($contract->hasEscapeReport() || $contract->info->is_outside_shelter) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن نقل الخدمات العاملة لوجود بلاغ هروب عليها'
                ], 422);
            }

            // إنشاء أو البحث عن الكفيل الجديد
            $newSponsor = User::where('national_num', $request->new_sponsor_national_id)->first();

            if (!$newSponsor) {
                // إنشاء كفيل جديد
                $newSponsor = User::create([
                    'name' => $request->new_sponsor_name,
                    'phone' => $request->new_sponsor_phone,
                    'phone_code' => '+966',
                    'national_num' => $request->new_sponsor_national_id,
                    'address' => $request->new_sponsor_address,
                    'register_from' => 'web',
                    'status' => 1,
                ]);
            } else {
                // تحديث بيانات الكفيل الموجود
                $newSponsor->update([
                    'name' => $request->new_sponsor_name,
                    'phone' => $request->new_sponsor_phone,
                    'address' => $request->new_sponsor_address,
                ]);
            }

            // إنشاء طلب نقل خدمات
            $transferData = [
                'contract_id' => $contract->id,
                'old_sponsor_id' => auth()->id(),
                'new_sponsor_id' => $newSponsor->id,
                'trial_days' => $request->trial_days,
                'trial_start_date' => $request->trial_start_date,
                'contract_value' => $request->contract_value,
                'paid_amount' => $request->paid_amount,
                'remaining_amount' => $request->contract_value - $request->paid_amount,
                'notes' => $request->notes,
                'status' => 'pending',
            ];

            SponsorshipTransfer::create($transferData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم إرسال طلب نقل الخدمات بنجاح'
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            \Log::error('Error in SponsorshipTransfer store: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في إرسال طلب نقل الخدمات'
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $userId = auth()->id();

        $transfer = SponsorshipTransfer::with([
            'contract',
            'oldSponsor',
            'newSponsor',
            'approvedBy',
            'transferredBy',
            'failedBy',
        ])->where(function ($q) use ($userId) {
            $q->where('old_sponsor_id', $userId)
                ->orWhere('new_sponsor_id', $userId);
        })->find($id);

        if (!$transfer) {
            return jsonSuccess(null, __('api.not found'), 201);
        }

        return jsonSuccess(SponsorshipTransferResource::make($transfer));
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

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
