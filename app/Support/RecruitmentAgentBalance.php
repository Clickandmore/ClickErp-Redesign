<?php

namespace App\Support;

use App\Models\Contract;
use App\Models\office\Office;
use App\Services\office\OfficeFinanceAccountService;
use Illuminate\Support\Facades\Schema;
use Nami\Finance\Enums\BondTypes;
use Nami\Finance\Models\Bond;
use Nami\Finance\Models\ConstraintDetails;

/**
 * رصيد فاتورة الوكيل الخارجي على عقد بعينه.
 *
 * مستخرج هنا ليشترك فيه متحكم الدفعات ومتحكم الملف المالي للعقد، فلا ينشأ حسابان
 * للرصيد نفسه يتباعدان مع الوقت.
 *
 * المثبت = الطرف الدائن على حساب الوكيل في قيد التكلفة. والمسدَّد = سندات الصرف
 * المربوطة بالعقد على نفس حساب الوكيل.
 */
class RecruitmentAgentBalance
{
    public static function for(Contract $contract): array
    {
        $contract->loadMissing([
            'user',
            'maid.nationality',
            'maid.office',
            'maid.recruitmentOffice',
            'requiredMaidNationality',
            'externalOfficeDispatch.recruitmentOffice',
            'info.manualMaidOffice',
        ]);

        $nationality = $contract->maid?->nationality ?? $contract->requiredMaidNationality;
        $nationalityName = trim((string) ($nationality?->sub_title ?? $nationality?->title ?? ''));
        $officeAccountId = self::resolveOfficeAccountId($contract);

        $constraintId = $contract->external_office_cost_constraint_id
            ?: $contract->invoice?->external_office_cost_constraint_id;

        $invoiced = 0.0;
        if ($constraintId && $officeAccountId) {
            $invoiced = (float) ConstraintDetails::query()
                ->where('constraint_id', $constraintId)
                ->where('account_id', $officeAccountId)
                ->sum('creditor_value');
        }

        $paid = 0.0;
        if ($officeAccountId && Schema::hasColumn('fn_bonds', 'recruitment_contract_id')) {
            $paid = (float) Bond::query()
                ->where('type', BondTypes::Sarf->value)
                ->where('recruitment_contract_id', $contract->id)
                ->where('debit_account_id', $officeAccountId)
                ->sum('value');
        }

        return [
            'contract_id' => (int) $contract->id,
            'client' => trim((string) ($contract->user?->name ?? '-')),
            'worker' => trim((string) ($contract->maid?->name ?? $contract->info?->manual_maid_name ?? '-')),
            'nationality' => $nationalityName,
            'office' => trim((string) ($contract->resolveExternalOfficeDispatchOffice()?->title ?? '-')),
            'office_account_id' => $officeAccountId,
            'cost_constraint_id' => $constraintId ? (int) $constraintId : null,
            'invoiced' => round($invoiced, 2),
            'paid' => round($paid, 2),
            'remaining' => round($invoiced - $paid, 2),
        ];
    }

    public static function payments(Contract $contract, ?int $officeAccountId)
    {
        if (!$officeAccountId || !Schema::hasColumn('fn_bonds', 'recruitment_contract_id')) {
            return collect();
        }

        return Bond::query()
            ->where('type', BondTypes::Sarf->value)
            ->where('recruitment_contract_id', $contract->id)
            ->where('debit_account_id', $officeAccountId)
            ->orderByDesc('date')
            ->get();
    }

    private static function resolveOfficeAccountId(Contract $contract): ?int
    {
        $office = $contract->resolveExternalOfficeDispatchOffice();
        if (!$office?->id) {
            return null;
        }

        $model = Office::query()->with(['translations', 'account'])->find($office->id);
        if (!$model) {
            return null;
        }

        if ($model->account_id) {
            return (int) $model->account_id;
        }

        return (int) app(OfficeFinanceAccountService::class)->ensureForOffice($model)->id;
    }
}
