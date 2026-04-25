<?php

namespace App\Http\Controllers\site\profile;

use App\Http\Controllers\admin\Controller;
use App\Models\Contract;
use App\Models\ingaz\User;
use App\Models\SponsorshipTransfer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SponsorshipTransferController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $transfers = SponsorshipTransfer::with([
            'contract',
            'oldSponsor',
            'newSponsor',
            'approvedBy',
            'transferredBy',
            'failedBy'
        ])->where(function($query) use ($user) {
            $query->where('old_sponsor_id', $user->id)
                  ->orWhere('new_sponsor_id', $user->id);
        })->orderBy('created_at', 'desc')->paginate(10);

        return view('site.pages.profile.sponsorship_transfers', compact('transfers'));
    }

    public function show(SponsorshipTransfer $transfer)
    {
        $user = auth()->user();

        // التحقق من أن المستخدم له صلاحية عرض هذا الطلب
        if ($transfer->old_sponsor_id != $user->id && $transfer->new_sponsor_id != $user->id) {
            abort(403, 'ليس لديك صلاحية لعرض هذا الطلب');
        }

        return view('web.ingaz.sponsorship-transfers.show', compact('transfer'));
    }

    public function store(Request $request)
    {
        try {
            // التحقق من تسجيل الدخول
            if (!auth()->check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'يجب تسجيل الدخول لإرسال طلب نقل الكفالة'
                ], 401);
            }

            // Validation
            $request->validate([
                'contract_id' => 'required|exists:contracts,id',
                'new_sponsor_name' => 'required|string|max:255',
                'new_sponsor_phone' => 'required|string|max:20',
                'new_sponsor_national_id' => 'required|string|max:20',
                'new_sponsor_address' => 'required|string|max:500',
                'trial_days' => 'required|integer|min:1|max:90',
                'trial_start_date' => 'required|date',
                'contract_value' => 'required|numeric|min:0',
                'paid_amount' => 'required|numeric|min:0',
                'notes' => 'nullable|string|max:1000',
            ], [
                'contract_id.required' => 'يجب اختيار العقد',
                'contract_id.exists' => 'العقد المختار غير موجود',
                'new_sponsor_name.required' => 'يجب إدخال اسم الكفيل الجديد',
                'new_sponsor_phone.required' => 'يجب إدخال رقم الجوال',
                'new_sponsor_national_id.required' => 'يجب إدخال رقم الهوية',
                'new_sponsor_address.required' => 'يجب إدخال العنوان',
                'trial_days.required' => 'يجب إدخال عدد أيام العمل التجريبي',
                'trial_days.integer' => 'عدد أيام العمل التجريبي يجب أن يكون رقماً صحيحاً',
                'trial_days.min' => 'عدد أيام العمل التجريبي يجب أن يكون 1 يوم على الأقل',
                'trial_days.max' => 'عدد أيام العمل التجريبي يجب أن يكون 90 يوم كحد أقصى',
                'trial_start_date.required' => 'يجب إدخال تاريخ بداية العمل التجريبي',
                'trial_start_date.date' => 'تاريخ بداية العمل التجريبي غير صحيح',
                'contract_value.required' => 'يجب إدخال قيمة العقد',
                'contract_value.numeric' => 'قيمة العقد يجب أن تكون رقماً',
                'contract_value.min' => 'قيمة العقد يجب أن تكون أكبر من صفر',
                'paid_amount.required' => 'يجب إدخال المبلغ المدفوع',
                'paid_amount.numeric' => 'المبلغ المدفوع يجب أن يكون رقماً',
                'paid_amount.min' => 'المبلغ المدفوع يجب أن يكون أكبر من أو يساوي صفر',
                'notes.max' => 'الملاحظات يجب أن تكون أقل من 1000 حرف',
            ]);

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
                    'message' => 'لا يمكن نقل كفالة عقد لا يخصك'
                ], 403);
            }

            // التحقق من أن العقد متاح للنقل
            if (!$contract->info || $contract->info->wants_to_work !== 'yes') {
                return response()->json([
                    'success' => false,
                    'message' => 'هذا العقد غير متاح للنقل'
                ], 400);
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

            // إنشاء طلب نقل كفالة
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
                'message' => 'تم إرسال طلب نقل الكفالة بنجاح'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Error in SponsorshipTransfer store: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في إرسال طلب نقل الكفالة'
            ], 500);
        }
    }
}
