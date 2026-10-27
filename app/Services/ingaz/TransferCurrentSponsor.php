<?php

namespace App\Services\ingaz;

use App\Models\Contract;
use Illuminate\Validation\ValidationException;

class TransferCurrentSponsor
{
    public function validate(Contract $contract): void
    {
        if ($contract->company_profile_id && $contract->user_id) {
            $this->reject('بيانات الكفيل الحالي متعارضة: العقد مرتبط بعميل وشركة معًا. صحح تبعية العقد قبل نقل الخدمات.');
        }

        if ($contract->company_profile_id) {
            if (!$contract->companyProfile) {
                $this->reject('سجل الشركة الكفيلة المرتبط بالعقد غير موجود. صحح بيانات الشركة قبل نقل الخدمات.');
            }
            return;
        }

        if (!$contract->user_id || !$contract->getRelationValue('user')) {
            $this->reject('لا يوجد كفيل حالي صالح لهذا العقد. اربطه بالعميل الحالي أو بسجل الشركة الكفيلة قبل نقل الخدمات.');
        }
    }

    private function reject(string $message): void
    {
        throw ValidationException::withMessages(['contract_id' => $message]);
    }
}
