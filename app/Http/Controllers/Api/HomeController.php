<?php

namespace App\Http\Controllers\Api;

use App\Enums\auth\AdminTypes;
use App\Http\Controllers\Controller;
use App\Http\Traits\SendSMSTrait;
use App\Models\auth\Admin;
use App\Models\Contract;
use App\Models\ingaz\Complaint;
use App\Models\ingaz\Nationality;
use App\Models\ingaz\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class HomeController extends Controller
{
    use SendSMSTrait;

    public function contractStatus(Request $request)
    {
        $contractQuery = Contract::query()->with(['user', 'maid', 'info']);
        $hasFilter = false;
        if (!empty($request->inpuut_id)) {
            $hasFilter = true;
            $contractQuery->whereHas('user', function ($q) use ($request) {
                $q->where('national_num', $request->inpuut_id);
            });
        } elseif (!empty($request->inpuut_musand_id)) {
            $hasFilter = true;
            $contractQuery->where(function ($q) use ($request) {
                $q->where('msaned_number', $request->inpuut_musand_id)
                    ->orWhereHas('info', function ($infoQuery) use ($request) {
                        $infoQuery->where('msaned_number', $request->inpuut_musand_id);
                    });
            });
        } elseif (!empty($request->inpuut_passport_id)) {
            $hasFilter = true;
            $contractQuery->whereHas('maid', function ($q) use ($request) {
                $q->where('passport_num', $request->inpuut_passport_id);
            });
        } elseif (!empty($request->inpuut_visa_id)) {
            $hasFilter = true;
            $contractQuery->where(function ($q) use ($request) {
                $q->where('visa_number', $request->inpuut_visa_id)
                    ->orWhereHas('info', function ($infoQuery) use ($request) {
                        $infoQuery->where('visa_number', $request->inpuut_visa_id);
                    });
            });
        }

        $contract = $hasFilter ? $contractQuery->latest()->first() : null;

        if (!empty($contract)) {
            $status = getEnumValue('contract_statuses', $contract->status);
            if ($status === 'undefined') {
                $status = $contract->status;
            }

            return [
                "status" => 1,
                "data" => $status
            ];
        } else {
            return [
                "status" => 0,
            ];
        }
    }
    public function nationalites(Request $request)
    {
        $nationalities = Nationality::where('is_show', 1)->get()->pluck('id', 'title');

        return [
            "status" => 1,
            "data" => $nationalities
        ];
    }
    public function allStaff(Request $request)
    {
        $staffs = Admin::where('admin_type', AdminTypes::CustomerService->value)->get();
        $users = $staffs->mapWithKeys(function ($staff) {
            $phone = !empty($staff->whatsapp) ? $staff->whatsapp : $staff->phone;
            return [$staff->name => $phone];
        });
        return [
            "status" => 1,
            "data" => $users
        ];
    }
    public function storeTicket(Request $request)
    {
        $user = User::where('phone', 'like', '%' . $request->phone  . '%')->first();
        $ticket = new Complaint;
        if (!empty($user)) {
            $ticket->user_id = $request->user_id ?? $user->id;
        } else {
            $user = new User();
            $user->name = $request->name;
            $user->phone = $request->phone;
            $user->phone_code = $request->phone_code ?? '966';
            $user->password = bcrypt(Str::random(12));
            $user->register_from = 'api';
            $user->save();
            $ticket->user_id = $user->id;
        }

        $ticket->subject = $request->subject;
        $ticket->details = $request->message;
        $ticket->message = $request->message;
        $ticket->address = $request->link ?? $request->address ?? '';
        $ticket->complainant_name = $request->name ?? $user->name;
        $ticket->complainant_phone = $request->phone ?? $user->phone;

        if ($ticket->save()) {
            $ticket->code = $ticket->id;
            $msg = " عزيزنا العميل ,تم تسليم الشكوى لدى مكتب بلاتنيوم الشرقية للاستقدام بنجاح . وهى محل اهتمامنا و سيتم المتابعة و أعلامك بالمستجدات رقم الشكوى" . $ticket->code;
            if (!empty($user->phone)) {
                $this->sendSMS($user->phone, $msg);
            }
            return [
                "status" => 1,
            ];
        } else {
            return [
                "status" => 0,
            ];
        }
    }
}
