<?php

namespace App\Http\Controllers\Api;

use App\Enums\auth\AdminTypes;
use App\Enums\ingaz\NotificationActionEnum;
use App\Http\Controllers\Controller;
use App\Http\Traits\SendSMSTrait;
use App\Http\Traits\StoreMessageTrait;
use App\Models\auth\Admin;
use App\Models\Contract;
use App\Models\ingaz\Complaint;
use App\Models\ingaz\ComplaintType;
use App\Models\ingaz\Nationality;
use App\Models\ingaz\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class HomeController extends Controller
{
    use SendSMSTrait, StoreMessageTrait;

    public function contractStatus(Request $request)
    {
        $contractQuery = Contract::query()->with(['user', 'maid', 'info']);
        [, $searchValue] = $this->resolveContractSearchInput($request);
        if (!empty($searchValue)) {
            $contractQuery->where(function ($query) use ($searchValue) {
                $query->whereHas('user', function ($userQuery) use ($searchValue) {
                    $userQuery->where('national_num', $searchValue);
                })->orWhere('msaned_number', $searchValue)
                    ->orWhere('visa_number', $searchValue)
                    ->orWhereHas('info', function ($infoQuery) use ($searchValue) {
                        $infoQuery->where('msaned_number', $searchValue)
                            ->orWhere('visa_number', $searchValue);
                    })
                    ->orWhereHas('maid', function ($maidQuery) use ($searchValue) {
                        $maidQuery->where('passport_num', $searchValue);
                    });
            });
        }

        $contract = !empty($searchValue) ? $contractQuery->latest()->first() : null;

        if (!empty($contract)) {
            $status = getEnumValue('contract_statuses', $contract->status);
            if ($status === 'undefined') {
                $status = $contract->status;
            }

            $msanedNumber = $contract->msaned_number ?: optional($contract->info)->msaned_number;
            $visaNumber = $contract->visa_number ?: optional($contract->info)->visa_number;
            $maidName = optional($contract->maid)->name;
            $maidPassport = optional($contract->maid)->passport_num;
            $clientName = optional($contract->user)->name;
            $clientNationalId = optional($contract->user)->national_num;
            [$searchLabel, $searchValue] = $this->resolveMatchedContractSearchInput($contract, $request);
            $stageDetails = $this->getContractStageDescription($contract->status);

            $details = array_filter([
                "تم العثور على العقد المطلوب بنجاح.",
                $searchLabel ? "طريقة البحث: {$searchLabel}" : null,
                $searchValue ? "الرقم المدخل: {$searchValue}" : null,
                $clientName ? "العقد باسم العميل: {$clientName}" : null,
                $clientNationalId ? "رقم هوية العميل: {$clientNationalId}" : null,
                $maidName ? "اسم العاملة: {$maidName}" : null,
                $maidPassport ? "رقم جواز العاملة: {$maidPassport}" : null,
                "مرحلة العقد الحالية: {$status}",
                "تفاصيل المرحلة الحالية: {$stageDetails}",
                "رقم العقد: {$contract->id}",
                $msanedNumber ? "رقم عقد مساند: {$msanedNumber}" : null,
                $visaNumber ? "رقم التأشيرة: {$visaNumber}" : null,
            ]);

            return [
                "status" => 1,
                "data" => implode(PHP_EOL, $details)
            ];
        } else {
            return [
                "status" => 0,
            ];
        }
    }

    private function resolveContractSearchInput(Request $request): array
    {
        if (!empty($request->search_value)) {
            return ['الرقم المرجعي المدخل', trim((string) $request->search_value)];
        }

        if (!empty($request->inpuut_passport_id)) {
            return ['رقم جواز السفر', trim((string) $request->inpuut_passport_id)];
        }

        if (!empty($request->inpuut_musand_id)) {
            return ['رقم عقد مساند', trim((string) $request->inpuut_musand_id)];
        }

        if (!empty($request->inpuut_id)) {
            return ['رقم الهوية', trim((string) $request->inpuut_id)];
        }

        if (!empty($request->inpuut_visa_id)) {
            return ['رقم التأشيرة', trim((string) $request->inpuut_visa_id)];
        }

        return [null, null];
    }

    private function resolveMatchedContractSearchInput(Contract $contract, Request $request): array
    {
        [$searchLabel, $searchValue] = $this->resolveContractSearchInput($request);
        if (empty($request->search_value)) {
            return [$searchLabel, $searchValue];
        }

        $matches = [
            'رقم جواز السفر' => optional($contract->maid)->passport_num,
            'رقم الهوية' => optional($contract->user)->national_num,
            'رقم عقد مساند' => $contract->msaned_number ?: optional($contract->info)->msaned_number,
            'رقم التأشيرة' => $contract->visa_number ?: optional($contract->info)->visa_number,
        ];

        foreach ($matches as $label => $value) {
            if (!empty($value) && trim((string) $value) === trim((string) $request->search_value)) {
                return [$label, trim((string) $request->search_value)];
            }
        }

        return [$searchLabel, $searchValue];
    }

    private function getContractStageDescription(?string $status): string
    {
        return match ($status) {
            'new' => 'العقد بانتظار الاعتماد والمراجعة الأولية قبل الانتقال للمرحلة التالية.',
            'accreditation_contracts' => 'العقد في مرحلة اعتماد العقود واستكمال الموافقات المطلوبة.',
            'msaned' => 'العقد في مرحلة مساند وجارٍ استكمال إجراءاته على المنصة.',
            'tafwed' => 'العقد في مرحلة التفويض وجارٍ إنهاء المتطلبات المرتبطة بها.',
            'taffez' => 'العقد في مرحلة التفييز وجارٍ متابعة الإجراءات الخارجية.',
            'tazker' => 'العقد في مرحلة التذكرة وجارٍ ترتيب إجراءات السفر.',
            'arrival' => 'العقد في مرحلة الوصول وجارٍ متابعة وصول العاملة واستكمال إجراءات ما بعد الوصول.',
            'pending_maid_selection' => 'العقد بانتظار اختيار العاملة المناسبة لاستكمال باقي الإجراءات.',
            'delegate_selected' => 'تم اختيار الموظف أو المندوب المسؤول عن متابعة العقد.',
            'client_contracted' => 'تم التعاقد مع العميل وجارٍ استكمال بقية خطوات التنفيذ.',
            'in_progress' => 'العقد قيد التنفيذ وجارٍ العمل على استكمال إجراءاته.',
            'under_warranty' => 'العقد تحت الضمان وما زالت المتابعة مستمرة خلال فترة الضمان.',
            'sari' => 'العقد ساري والعاملة على رأس العمل.',
            'completed' => 'العقد مكتمل وتم الانتهاء من مراحله الأساسية.',
            'back' => 'العقد في حالة مرتجع ويتم التعامل مع الإجراءات المرتبطة بهذه الحالة.',
            'closed' => 'العقد مغلق وتم إقفال إجراءاته.',
            'shelter' => 'العقد في مرحلة الإيواء وتتم متابعة وضع العاملة عبر الإيواء.',
            'trial' => 'العقد في فترة التجربة وتتم المتابعة خلال هذه المرحلة.',
            default => 'يتم حاليًا متابعة العقد ضمن هذه المرحلة.',
        };
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
        $staffs = Admin::query()
            ->where('admin_type', AdminTypes::CustomerService->value)
            ->where(function ($query) {
                $query->whereNull('status')->orWhere('status', 'active');
            })
            ->orderBy('name')
            ->get();
        $users = $staffs->mapWithKeys(function ($staff) {
            $phone = $staff->formatted_whatsapp_phone ?: $staff->formatted_contact_phone;
            return [$staff->name => $phone];
        });
        return [
            "status" => 1,
            "data" => $users
        ];
    }
    public function storeTicket(Request $request)
    {
        $phone = trim((string) $request->phone);
        $name = trim((string) $request->name);
        $message = trim((string) ($request->message ?? $request->details));
        $subject = trim((string) $request->subject);
        $priority = $this->resolveComplaintPriority($request->priority ?? $request->priority_label);
        $complaintTypeId = $this->resolveComplaintTypeId($request->complaint_type_id, $request->complaint_type_name);
        $assignedToId = $this->resolveComplaintAssignedTo($request->assigned_to, $request->assigned_to_name);

        $user = User::where('phone', 'like', '%' . $phone . '%')->first();
        $ticket = new Complaint;
        if (!empty($user)) {
            $ticket->user_id = $request->user_id ?? $user->id;
        } else {
            $user = new User();
            $user->name = $name;
            $user->phone = $phone;
            $user->phone_code = $request->phone_code ?? '966';
            $user->password = bcrypt(Str::random(12));
            $user->register_from = 'api';
            $user->save();
            $ticket->user_id = $user->id;
        }

        $ticket->subject = !empty($subject) ? $subject : 'شكوى واردة من المحادثة';
        $ticket->details = $message;
        $ticket->message = $message;
        $ticket->address = $request->link ?? $request->address ?? '';
        $ticket->complainant_name = !empty($name) ? $name : $user->name;
        $ticket->complainant_phone = !empty($phone) ? $phone : $user->phone;
        $ticket->priority = $priority;
        $ticket->status = 'pending';
        if (!empty($complaintTypeId)) {
            $ticket->complaint_type_id = $complaintTypeId;
        }
        if (!empty($assignedToId)) {
            $ticket->assigned_to = $assignedToId;
        }

        if ($ticket->save()) {
            $ticketCode = $ticket->id;
            $msg = " عزيزنا العميل ,تم تسليم الشكوى لدى مكتب بلاتنيوم الشرقية للاستقدام بنجاح . وهى محل اهتمامنا و سيتم المتابعة و أعلامك بالمستجدات رقم الشكوى" . $ticketCode;
            if (!empty($user->phone)) {
                $result = $this->sendSMS(formatSaudiPhone($user->phone), $msg);

                if (isset($result['code']) && $result['code'] == "1") {
                    $this->storeMessage('ارسال رسالة نصية الى العميل', $msg, NotificationActionEnum::SMS->value, $user->id);
                }
            }
            return [
                "status" => 1,
                "data" => "تم تسجيل الشكوى بنجاح.",
                "ticket_code" => $ticketCode,
            ];
        } else {
            return [
                "status" => 0,
            ];
        }
    }

    private function resolveComplaintTypeId($complaintTypeId, $complaintTypeName): ?int
    {
        if (!empty($complaintTypeId) && ComplaintType::query()->whereKey($complaintTypeId)->exists()) {
            return (int) $complaintTypeId;
        }

        $normalizedComplaintTypeName = $this->normalizeArabicText($complaintTypeName);
        if (empty($normalizedComplaintTypeName)) {
            return null;
        }

        $complaintType = ComplaintType::query()
            ->get(['id', 'name'])
            ->first(function ($type) use ($normalizedComplaintTypeName) {
                return $this->normalizeArabicText($type->name) === $normalizedComplaintTypeName;
            });

        return $complaintType?->id;
    }

    private function resolveComplaintAssignedTo($assignedTo, $assignedToName): ?int
    {
        if (!empty($assignedTo) && Admin::query()->whereKey($assignedTo)->exists()) {
            return (int) $assignedTo;
        }

        $normalizedAssignedToName = $this->normalizeArabicText($assignedToName);
        if (empty($normalizedAssignedToName) || in_array($normalizedAssignedToName, ['بدون تحديد موظف', 'بدون تحديد'])) {
            return null;
        }

        $assignedAdmin = Admin::query()
            ->where('admin_type', AdminTypes::CustomerService->value)
            ->where(function ($query) {
                $query->whereNull('status')->orWhere('status', 'active');
            })
            ->get(['id', 'name'])
            ->first(function ($admin) use ($normalizedAssignedToName) {
                return $this->normalizeArabicText($admin->name) === $normalizedAssignedToName;
            });

        return $assignedAdmin?->id;
    }

    private function resolveComplaintPriority($priority): string
    {
        $normalizedPriority = $this->normalizeArabicText($priority);
        return match ($normalizedPriority) {
            'منخفضه', 'low' => 'low',
            'عاليه', 'high' => 'high',
            'عاجله', 'urgent' => 'urgent',
            default => 'medium',
        };
    }

    private function normalizeArabicText($value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $value = preg_replace('/\s+/u', ' ', $value);

        return strtr(mb_strtolower($value, 'UTF-8'), [
            'أ' => 'ا',
            'إ' => 'ا',
            'آ' => 'ا',
            'ى' => 'ي',
            'ة' => 'ه',
        ]);
    }
}
