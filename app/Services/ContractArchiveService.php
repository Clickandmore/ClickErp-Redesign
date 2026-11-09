<?php

namespace App\Services;

use App\Enums\ingaz\ContractStatusEnum;
use App\Models\Contract;
use App\Models\ContractArchiveLog;
use App\Models\ContractReturnRequest;
use App\Models\ContractReturnSettlement;
use App\Models\DeportationProcedure;
use App\Models\FinancialRequest;
use App\Models\RentalContract;
use App\Models\ingaz\Complaint;
use App\Models\ingaz\Invoice;
use App\Support\BranchQueryContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Nami\Finance\Services\RentalReceivablesLedger;

/**
 * أرشفة واسترجاع عقود الاستقدام والتأجير.
 *
 * الأرشفة لا تحذف شيئاً ولا تلمس أي جدول مرتبط: الفواتير والسندات والقيود
 * تبقى كما هي وتظل تشير للعقد. الشروط تضمن أن العقد منتهٍ وليس عليه أي
 * شيء مفتوح، فتصبح كل ارتباطاته التشغيلية تاريخاً مغلقاً تابعاً له.
 */
class ContractArchiveService
{
    public const TYPE_RECRUITMENT = 'recruitment';
    public const TYPE_RENTAL = 'rental';

    public const MAX_BATCH = 200;

    /** حالات الاستقدام النهائية التي يجوز أرشفتها. */
    public const RECRUITMENT_FINAL_STATUSES = [
        ContractStatusEnum::Completed->value,
        ContractStatusEnum::Refunded->value,
        ContractStatusEnum::Back->value,
        ContractStatusEnum::Closed->value,
    ];

    public const RENTAL_FINAL_STATUSES = ['completed', 'cancelled'];

    /** الطلب المالي في هذه الحالات ما زال قيد العمل. */
    private const OPEN_FINANCIAL_REQUEST_STATUSES = [
        FinancialRequest::STATUS_PREPARING,
        FinancialRequest::STATUS_FOR_APPROVAL,
        FinancialRequest::STATUS_APPROVED,
        FinancialRequest::STATUS_REJECTED,
    ];

    public static function types(): array
    {
        return [self::TYPE_RECRUITMENT, self::TYPE_RENTAL];
    }

    public static function modelClass(string $type): string
    {
        return match ($type) {
            self::TYPE_RECRUITMENT => Contract::class,
            self::TYPE_RENTAL => RentalContract::class,
            default => throw new InvalidArgumentException("Unknown contract archive type [{$type}]."),
        };
    }

    public static function typeLabel(string $type): string
    {
        return $type === self::TYPE_RENTAL ? 'عقود الإيجار' : 'عقود الاستقدام';
    }

    public static function contractNumber(Model $contract): string
    {
        if ($contract instanceof RentalContract) {
            return (string) ($contract->contract_number ?: ($contract->legacy_order_id ?: $contract->id));
        }

        return (string) $contract->id;
    }

    /**
     * سبب رفض الأرشفة، أو null إذا كان العقد قابلاً للأرشفة.
     * الفحص يتجاوز عزل الفروع حتى لا يخفي ارتباطاً مفتوحاً في فرع آخر.
     */
    public function archiveBlocker(Model $contract): ?string
    {
        if ($contract->isArchived()) {
            return 'العقد مؤرشف مسبقاً.';
        }

        return BranchQueryContext::withoutBranchScope(function () use ($contract) {
            return $contract instanceof RentalContract
                ? $this->rentalBlocker($contract)
                : $this->recruitmentBlocker($contract);
        });
    }

    public function canArchive(Model $contract): bool
    {
        return $this->archiveBlocker($contract) === null;
    }

    /**
     * @param  array<int, int|string>  $ids
     * @return array{done: array<int, array{id:int, number:string}>, rejected: array<int, array{id:int, number:string, reason:string}>}
     */
    public function archive(string $type, array $ids, string $reason, ?int $adminId): array
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw new InvalidArgumentException('سبب الأرشفة مطلوب.');
        }

        return $this->process($type, $ids, function (Model $contract) use ($reason, $adminId) {
            $blocker = $this->archiveBlocker($contract);
            if ($blocker !== null) {
                return $blocker;
            }

            $this->writeArchiveState($contract, [
                'archived_at' => now(),
                'archived_by' => $adminId,
                'archive_reason' => mb_substr($reason, 0, 500),
            ]);
            $this->log($contract, ContractArchiveLog::ACTION_ARCHIVED, $reason, $adminId);

            return null;
        });
    }

    /**
     * @param  array<int, int|string>  $ids
     * @return array{done: array<int, array{id:int, number:string}>, rejected: array<int, array{id:int, number:string, reason:string}>}
     */
    public function restore(string $type, array $ids, ?string $reason, ?int $adminId): array
    {
        return $this->process($type, $ids, function (Model $contract) use ($reason, $adminId) {
            if (!$contract->isArchived()) {
                return 'العقد غير مؤرشف.';
            }

            $this->writeArchiveState($contract, [
                'archived_at' => null,
                'archived_by' => null,
                'archive_reason' => null,
            ]);
            $this->log($contract, ContractArchiveLog::ACTION_RESTORED, $reason, $adminId);

            return null;
        });
    }

    /**
     * يحمّل كل عقد عبر استعلام النموذج العادي (فيطبّق عزل الفروع على من يملك
     * الصلاحية)، ثم يقفله داخل معاملة ويعيد الفحص قبل الكتابة.
     */
    private function process(string $type, array $ids, callable $action): array
    {
        $modelClass = self::modelClass($type);
        $ids = collect($ids)->map(fn ($id) => (int) $id)->filter(fn ($id) => $id > 0)->unique()->values();

        if ($ids->isEmpty()) {
            throw new InvalidArgumentException('اختر عقداً واحداً على الأقل.');
        }
        if ($ids->count() > self::MAX_BATCH) {
            throw new InvalidArgumentException('الحد الأقصى ' . self::MAX_BATCH . ' عقد في العملية الواحدة.');
        }

        $result = ['done' => [], 'rejected' => []];
        $visibleIds = $modelClass::query()->whereKey($ids->all())->pluck('id')->map(fn ($id) => (int) $id)->all();

        foreach ($ids as $id) {
            if (!in_array($id, $visibleIds, true)) {
                $result['rejected'][] = ['id' => $id, 'number' => (string) $id, 'reason' => 'العقد غير موجود أو خارج صلاحية فرعك.'];
                continue;
            }

            $outcome = DB::transaction(function () use ($modelClass, $id, $action) {
                $contract = $modelClass::query()->withoutGlobalScopes()->whereKey($id)->lockForUpdate()->first();
                if (!$contract) {
                    return [null, 'العقد غير موجود.'];
                }

                return [$contract, $action($contract)];
            });

            [$contract, $blocker] = $outcome;
            $number = $contract ? self::contractNumber($contract) : (string) $id;

            if ($blocker === null) {
                $result['done'][] = ['id' => $id, 'number' => $number];
            } else {
                $result['rejected'][] = ['id' => $id, 'number' => $number, 'reason' => $blocker];
            }
        }

        return $result;
    }

    /** كتابة مباشرة بلا أحداث النموذج: لا تتغير updated_at ولا يُعاد ختم الفرع. */
    private function writeArchiveState(Model $contract, array $values): void
    {
        DB::table($contract->getTable())->where($contract->getKeyName(), $contract->getKey())->update($values);
        $contract->setRawAttributes(array_merge($contract->getAttributes(), $values), true);
    }

    private function log(Model $contract, string $action, ?string $reason, ?int $adminId): void
    {
        ContractArchiveLog::query()->create([
            'contract_type' => $contract::archiveContractType(),
            'contract_id' => $contract->getKey(),
            'action' => $action,
            'reason' => $reason !== null && trim($reason) !== '' ? mb_substr(trim($reason), 0, 500) : null,
            'admin_id' => $adminId,
            'branch_id' => $contract->getAttribute('branch_id'),
        ]);
    }

    private function recruitmentBlocker(Contract $contract): ?string
    {
        if (!in_array((string) $contract->status, self::RECRUITMENT_FINAL_STATUSES, true)) {
            return 'العقد غير منتهٍ (' . getContractStatus($contract->status) . '). تُؤرشف العقود المكتملة أو المستردة أو المرتجعة أو المغلقة فقط.';
        }

        if ($contract->status === ContractStatusEnum::Completed->value
            && $contract->warranty_end_date
            && \Carbon\Carbon::parse($contract->warranty_end_date)->isFuture()) {
            return 'ضمان العقد ما زال سارياً حتى ' . \Carbon\Carbon::parse($contract->warranty_end_date)->toDateString() . '.';
        }

        if ($this->recruitmentHasUnpaidInvoice($contract)) {
            return 'توجد فاتورة غير مسددة على العقد.';
        }

        if ($this->recruitmentHasUntransferredMusanedInvoice($contract)) {
            return 'مبلغ فاتورة العقد لم يُحوَّل من مساند بعد.';
        }

        if ($this->hasOpenFinancialRequest($contract->id, self::TYPE_RECRUITMENT)) {
            return 'يوجد طلب مالي مفتوح على العقد.';
        }

        if ($this->recruitmentHasOpenComplaint($contract)) {
            return 'توجد شكوى مفتوحة على العقد.';
        }

        if (Schema::hasTable('contract_return_requests')
            && ContractReturnRequest::query()
                ->where('contract_id', $contract->id)->where('status', 'pending')->exists()) {
            return 'يوجد طلب إرجاع معلّق على العقد.';
        }

        if (Schema::hasTable('contract_return_settlements')
            && ContractReturnSettlement::query()->where('contract_id', $contract->id)
                ->whereNotIn('status', ['posted', 'cancelled', 'rejected'])->exists()) {
            return 'توجد تسوية إرجاع لم تُرحَّل بعد.';
        }

        if (Schema::hasTable('deportation_procedures')
            && DeportationProcedure::query()->where('contract_id', $contract->id)
                ->whereIn('status', ['pending', 'in_progress'])->exists()) {
            return 'يوجد إجراء ترحيل قيد التنفيذ على العقد.';
        }

        return null;
    }

    private function rentalBlocker(RentalContract $contract): ?string
    {
        if (!in_array((string) $contract->status, self::RENTAL_FINAL_STATUSES, true)) {
            return 'العقد غير منتهٍ (' . ($contract->status_text ?? $contract->status) . '). تُؤرشف العقود المكتملة أو الملغاة فقط.';
        }

        $remaining = $this->rentalRemainingBalance($contract);
        if ($remaining > 0.009) {
            return 'يوجد رصيد مستحق على العقد: ' . number_format($remaining, 2) . ' ريال.';
        }

        if ($this->hasOpenFinancialRequest($contract->id, self::TYPE_RENTAL)) {
            return 'يوجد طلب مالي مفتوح على العقد.';
        }

        return null;
    }

    /** فاتورة إيراد غير مسددة وغير ملغاة بالكامل بإشعار دائن. */
    protected function recruitmentHasUnpaidInvoice(Contract $contract): bool
    {
        if (!Schema::hasTable('invoices')) {
            return false;
        }

        return Invoice::query()
            ->where('contract_id', $contract->id)
            ->where(fn ($q) => $q->whereNull('is_paid')->orWhere('is_paid', false))
            ->blockingReplacement()
            ->exists();
    }

    /**
     * فاتورة عقد استقدام (غير عقود الشركات) لم تدخل في تحويل مساند بعد، أي أن
     * المبلغ ما زال لدى مساند. نفس شرط شاشة تحويلات مساند.
     *
     * مستحقات المكتب الخارجي لا تُفحص هنا: تُسدَّد على مستوى حساب المورد لا
     * لكل عقد، وتبقى صحيحة في كشف حسابه لأن الأرشفة لا تمس القيود.
     */
    protected function recruitmentHasUntransferredMusanedInvoice(Contract $contract): bool
    {
        if (!Schema::hasTable('musaned_transfer_items')
            || !Contract::query()->whereKey($contract->id)->forCompanyRecruitment(false)->exists()) {
            return false;
        }

        return Invoice::query()
            ->where('contract_id', $contract->id)
            ->blockingReplacement()
            ->whereDoesntHave('musanedTransferItem.transfer')
            ->exists();
    }

    /** @var array<int, float> أرصدة محسوبة مسبقاً لصفحة كاملة من العقود. */
    private array $rentalBalanceCache = [];

    /** كشوف عقود التأجير محسوبة مرة واحدة لكل طلب. */
    private ?\Illuminate\Support\Collection $rentalBalances = null;

    /**
     * حساب أرصدة مجموعة عقود إيجار باستعلام واحد بدل استعلام لكل عقد
     * (يُستدعى قبل فحص صفحة كاملة في شاشة الأرشيف).
     *
     * @param  array<int, int>  $ids
     */
    public function primeRentalBalances(array $ids): void
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if ($ids === []) {
            return;
        }

        $balances = $this->rentalBalances();
        foreach ($ids as $id) {
            $this->rentalBalanceCache[$id] = max(0.0, (float) ($balances->get($id)->remaining ?? 0));
        }
    }

    /** نفس رصيد شاشة المستحق على عملاء التأجير (كشف حركة العقد الفعلي). */
    protected function rentalRemainingBalance(RentalContract $contract): float
    {
        if (array_key_exists($contract->id, $this->rentalBalanceCache)) {
            return $this->rentalBalanceCache[$contract->id];
        }

        return max(0.0, (float) ($this->rentalBalances()->get($contract->id)->remaining ?? 0));
    }

    private function rentalBalances()
    {
        return $this->rentalBalances ??= app(RentalReceivablesLedger::class)->contracts(now()->toDateString());
    }

    private function hasOpenFinancialRequest(int $contractId, string $contractType): bool
    {
        if (!Schema::hasTable('financial_requests')) {
            return false;
        }

        return FinancialRequest::query()
            ->where('contract_id', $contractId)
            ->where('contract_type', $contractType)
            ->whereIn('status', self::OPEN_FINANCIAL_REQUEST_STATUSES)
            ->exists();
    }

    /** نفس تعريف "مفتوحة" المستخدم في لوحة التحكم. */
    private function recruitmentHasOpenComplaint(Contract $contract): bool
    {
        if (!Schema::hasTable('complaints')) {
            return false;
        }

        return Complaint::query()
            ->where('contract_id', $contract->id)
            ->whereNotIn('status', ['resolved', 'closed', 'rejected'])
            ->whereNull('closed_at')
            ->where(fn ($q) => $q->whereNull('source_status')->orWhereNotIn('source_status', ['مغلقة', 'closed']))
            ->exists();
    }
}
