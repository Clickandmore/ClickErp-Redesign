<?php

namespace App\Observers;

use App\Models\Contract;
use App\Services\ingaz\RecruitmentTripAccountingService;
use App\Support\RecruitmentSupplierCostRecognition;

/**
 * يشغّل قيد تكلفة الوكيل الخارجي عند إنشاء العقد، في وضع on_contract فقط.
 *
 * مربوط على المراقِب لا على نقاط إنشاء العقد السبع (ContractController و
 * RecruitmentContractController و MaidAction و OrderAction)، حتى لا يُلمس أي مسار
 * تشغيلي قائم. أول سطر فيه يخرج فوراً في الوضع الافتراضي، فلا يتغير شيء إلا بعد
 * تبديل الإعداد عن قصد.
 *
 * المكتب الخارجي يأتي من العاملة، وقد تُربط العاملة بعد إنشاء العقد، فيُعاد المحاولة
 * عند أول تغيّر في maid_id. والدالة المستدعاة لا ترمي استثناءً بحال.
 */
class ContractSupplierCostObserver
{
    public function created(Contract $contract): void
    {
        $this->record($contract);
    }

    public function updated(Contract $contract): void
    {
        // ربط عاملة بعقد قائم هو اللحظة التي يصبح فيها المكتب والسعر معروفين.
        if (!$contract->wasChanged('maid_id')) {
            return;
        }

        if ($contract->external_office_cost_constraint_id) {
            return;
        }

        $this->record($contract);
    }

    private function record(Contract $contract): void
    {
        if (!RecruitmentSupplierCostRecognition::isOnContract()) {
            return;
        }

        app(RecruitmentTripAccountingService::class)->recordContractCostEntries($contract);
    }
}
