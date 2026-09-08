<?php

namespace App\Services\ingaz;

use App\Support\BranchReportFilter;
use App\Models\RentalContract;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * يقرأ رصيد عقد التأجير من دفتر الأستاذ مباشرة بدل اشتقاقه حسابياً.
 *
 * الاشتقاق الحسابي (فاتورة + تمديدات + مستحقات - استرجاعات - سندات قبض) يتجاهل
 * أي مستند لم يُدرج في معادلته — الإشعارات الدائنة اليدوية مثلاً — فيعرض على
 * الشاشة رقماً أعلى من الرصيد الحقيقي. القراءة من القيود لا يمكن أن تكذب: كل
 * مستند مرحَّل يظهر فيها بالضرورة.
 */
class RentalContractLedgerService
{
    /**
     * Σ(مدين − دائن) على حساب العميل، مقصورة على القيود التي أنشأها هذا العقد.
     *
     * تُرجع null إذا تعذّر الحساب من الأستاذ — لا حساب محاسبي للعميل، أو لا قيود
     * مرحَّلة للعقد (القيود التلقائية معطّلة، أو عقد مستورد بلا قيد فاتورة).
     * على المستدعي حينها الرجوع للاشتقاق الحسابي.
     */
    public function balanceForContract(RentalContract $contract, bool $approvedOnly = false): ?float
    {
        $accountId = $this->clientAccountId($contract);

        if (!$accountId) {
            return null;
        }

        // بلا قيد فاتورة مرحَّل تكون صورة الأستاذ ناقصة الطرف المدين: سندات
        // القبض وحدها ترحّل دائناً فيخرج رصيد دائن ضخم لا معنى له. الاشتقاق
        // الحسابي أصدق في هذه الحالة لأنه يبدأ من قيمة الفاتورة المخزَّنة.
        if (!$contract->financeInvoice?->invoice_constraint_id) {
            return null;
        }

        $constraintIds = $this->constraintIdsForContract($contract);

        if ($constraintIds->isEmpty()) {
            return null;
        }

        $query = BranchReportFilter::apply(DB::table('fn_constraint_details as details'), 'details')
            ->join('fn_constraints as constraints', 'constraints.id', '=', 'details.constraint_id')
            ->whereIn('details.constraint_id', $constraintIds->all())
            ->where('details.account_id', $accountId)
            ->whereNull('details.deleted_at')
            ->whereNull('constraints.deleted_at');

        if ($approvedOnly && Schema::hasColumn('fn_constraints', 'approval_status')) {
            $query->where('constraints.approval_status', 'approved');
        }

        $balance = $query
            ->selectRaw('COALESCE(SUM(details.debtor_value - details.creditor_value), 0) AS balance')
            ->value('balance');

        return round((float) $balance, 2);
    }

    /**
     * معرّفات كل القيود التي يملكها العقد: الفاتورة، التمديدات وسنداتها،
     * تسويات الإنهاء والإشعارات اليدوية، وكل سندات القبض المرتبطة بالعقد.
     */
    public function constraintIdsForContract(RentalContract $contract): Collection
    {
        $contract->loadMissing([
            'financeInvoice',
            'extensions',
            'financialAdjustments',
            'financeBonds',
        ]);

        return collect([
            $contract->financeInvoice?->invoice_constraint_id,
            $contract->financeInvoice?->receipt_constraint_id,
        ])
            ->merge($contract->extensions->pluck('invoice_constraint_id'))
            ->merge($contract->extensions->pluck('receipt_constraint_id'))
            ->merge($contract->financialAdjustments->pluck('constraint_id'))
            ->merge($contract->financeBonds->pluck('constraint_id'))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }

    /**
     * مجموع سندات القبض المعتمدة للعقد. رسوم نقل الخدمات تُستبعد لأنها مقابل
     * خدمة أخرى لا علاقة لها بقيمة عقد الإيجار.
     */
    public function confirmedReceiptsTotal(RentalContract $contract, bool $excludeTransferFees = true): float
    {
        $contract->loadMissing('financeBonds');

        $total = $contract->financeBonds
            ->where('type', 'qabd')
            ->where('is_confirm', 1)
            ->reject(function ($bond) use ($excludeTransferFees) {
                return $excludeTransferFees
                    && str_contains((string) $bond->statement, 'نقل الخدمات');
            })
            ->sum('value');

        return round((float) $total, 2);
    }

    private function clientAccountId(RentalContract $contract): ?int
    {
        $contract->loadMissing('user');

        $accountId = (int) ($contract->user?->account_id ?? 0);

        return $accountId > 0 ? $accountId : null;
    }
}
