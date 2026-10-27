<?php

namespace App\Services\Complaints;

use App\Models\Contract;
use App\Models\ingaz\Maid;
use App\Models\ingaz\Order;
use App\Support\MaidFieldRules;
use Illuminate\Validation\ValidationException;

class ComplaintWorkerResolver
{
    public function resolve(array &$data): void
    {
        $passport = trim((string) ($data['maid_passport'] ?? ''));
        $mode = $data['maid_registration_status'] ?? 'registered';
        $linked = null;
        if (!empty($data['contract_id'])) {
            $linked = Contract::findOrFail($data['contract_id'])->maid;
        } elseif (!empty($data['order_id'])) {
            $order = Order::with('contracts.maid')->findOrFail($data['order_id']);
            $linked = $order->maid ?: $order->contracts->first(fn ($contract) => $contract->maid)?->maid;
        }

        if ($mode === 'unregistered') {
            if ($linked || !empty($data['maid_id'])) {
                $this->reject('العاملة مرتبطة بسجل في النظام؛ اختر عاملة مسجلة.');
            }
            if ($passport === '' || trim((string) ($data['maid_name'] ?? '')) === '') {
                $this->reject('أدخل اسم العاملة غير المسجلة ورقم جوازها.');
            }
            $passport = strtoupper($passport);
            if (!preg_match(MaidFieldRules::PASSPORT_REGEX, $passport)) {
                $this->reject('رقم الجواز يقبل الحروف الإنجليزية والأرقام فقط، بطول من ' . MaidFieldRules::PASSPORT_MIN_LENGTH . ' إلى ' . MaidFieldRules::PASSPORT_MAX_LENGTH . ' خانة.');
            }
            if (Maid::where('passport_num', $passport)->exists()) {
                $this->reject('رقم الجواز مسجل بالفعل؛ ابحث عن العاملة واخترها من القائمة.');
            }
            $data['maid_id'] = null;
            $data['maid_passport'] = $passport;
            $data['maid_name'] = trim($data['maid_name']);
            return;
        }

        $maid = null;
        if (!empty($data['maid_id'])) {
            $maid = Maid::find($data['maid_id']);
            if (!$maid || ($passport !== '' && strcasecmp($passport, trim((string) $maid->passport_num)) !== 0)) {
                $this->reject('رقم الجواز لا يطابق العاملة المختارة أو أنها غير متاحة لك.');
            }
        } elseif ($passport !== '') {
            $matches = Maid::where('passport_num', $passport)->limit(2)->get();
            if ($matches->count() !== 1) {
                $this->reject($matches->isEmpty()
                    ? 'لم يتم العثور على عاملة بهذا الجواز. اختر عاملة مسجلة أو حدد «العاملة غير مسجلة بالنظام».'
                    : 'يوجد أكثر من سجل بهذا الجواز؛ اختر العاملة من نتائج البحث.');
            }
            $maid = $matches->first();
        } elseif ($linked) {
            $maid = $linked;
        }

        if ($linked && $maid && (int) $linked->id !== (int) $maid->id) {
            $this->reject('العاملة المختارة لا تطابق العاملة المرتبطة بالعقد أو الطلب.');
        }
        $data['maid_id'] = $maid?->id;
        $data['maid_name'] = $maid?->name;
        $data['maid_passport'] = $maid?->passport_num;
        $data['maid_registration_status'] = $maid ? 'registered' : null;
        if ($maid) {
            $data['maid_nationality_id'] = $maid->nationality_id;
        }
    }

    private function reject(string $message): void
    {
        throw ValidationException::withMessages(['maid_passport' => $message]);
    }
}
