<?php

namespace App\Services\ingaz;

use App\Models\Contract;
use App\Models\ContractReturnSettlementLine;
use App\Models\DeportationProcedure;
use App\Models\ShelterCostEntry;
use App\Models\ShelterLivingExpense;
use App\Models\SponsorshipTransfer;
use App\Models\ingaz\Maid;
use App\Models\ingaz\Nationality;
use App\Support\AutomaticJournalEntries;
use App\Support\NationalityAccountResolver;
use App\Support\ShelterAccountingSettings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Nami\Finance\Enums\CostCenterTypeEnum;
use Nami\Finance\Models\Constraint;
use Nami\Finance\Models\CostCenter;
use Nami\Finance\Services\ConstraintService;
use Nami\Finance\Services\YearService;

/**
 * دفتر الإيواء المستقل.
 *
 * يملك أصل الإيواء «مصروفات مدفوعة مقدماً - إيواء (الجنسية)» لكل عاملة (مركز تكلفة باسمها)،
 * ويكتب كل دخول وخروج بقيد مستقل:
 *   S1  capitalize_office_cost   مدين أصل الإيواء / دائن مردودات تكلفة (الجنسية)   — عند الإرجاع داخل الضمان
 *   S2  living_expense           مدين أصل الإيواء / دائن بنك أو صندوق أو عهدة       — مصروف الإعاشة (حين تُفعَّل الرسملة)
 *   S3  release_to_trial         مدين إيواء التجربة / دائن أصل الإيواء               — بدء نقل الخدمات
 *       return_from_trial        عكس S3                                              — فشل التجربة
 *   18  release_to_deportation   مدين المكتب الخارجي (أو خسائر الإيواء) / دائن الأصل — إنهاء الترحيل
 *   S4  release_to_sale          مدين تكاليف مبيعات (الجنسية) / دائن الأصل            — فوترة عقد جديد لعاملة في الإيواء
 *       write_off                مدين حساب التصفية / دائن الأصل                       — إخراج يدوي بلا مسار
 *
 * الرصيد = مجموع shelter_cost_entries.amount للعاملة. المبلغ موجب يزيد الأصل وسالب يُفرج عنه.
 * الحماية من التكرار عبر (source_type, source_id, kind) فريدة.
 */
class ShelterCostLedgerService
{
    public function __construct(
        protected ConstraintService $constraintService,
        protected YearService $yearService
    ) {
    }

    // ── الرصيد ───────────────────────────────────────────────────────────────

    public function balance(int $maidId): float
    {
        if (!$maidId || !Schema::hasTable('shelter_cost_entries')) {
            return 0.0;
        }

        return $this->roundMoney((float) ShelterCostEntry::query()->where('maid_id', $maidId)->sum('amount'));
    }

    public function balanceForContract(Contract $contract): float
    {
        return $this->balance((int) $contract->maid_id);
    }

    public function entriesFor(int $maidId)
    {
        return ShelterCostEntry::query()
            ->where('maid_id', $maidId)
            ->with('constraint')
            ->orderBy('id')
            ->get();
    }

    public function findEntry(Model $source, string $kind): ?ShelterCostEntry
    {
        return ShelterCostEntry::query()
            ->where('source_type', get_class($source))
            ->where('source_id', $source->getKey())
            ->where('kind', $kind)
            ->first();
    }

    // ── S1: استلام عاملة في الإيواء ──────────────────────────────────────────

    /**
     * ينقل تكلفة المكتب الخارجي إلى أصل الإيواء عند الموافقة على الإرجاع داخل الضمان.
     * @return array{entry: ?ShelterCostEntry, constraint: ?Constraint}
     */
    public function capitalizeOfficeCost(Contract $contract, float $amount, string $date, Model $source, string $statement): array
    {
        return DB::transaction(function () use ($contract, $amount, $date, $source, $statement) {
            if ($contract->maid_id) { Maid::query()->whereKey($contract->maid_id)->lockForUpdate()->firstOrFail(); }
            return $this->capitalizeOfficeCostLocked($contract, $amount, $date, $source, $statement);
        });
    }

    private function capitalizeOfficeCostLocked(Contract $contract, float $amount, string $date, Model $source, string $statement): array
    {
        $kind = ShelterCostEntry::KIND_CAPITALIZE_OFFICE_COST;
        if ($existing = $this->findEntry($source, $kind)) {
            return ['entry' => $existing, 'constraint' => $existing->constraint];
        }

        $amount = $this->roundMoney($amount);
        if ($amount <= 0 || !$contract->maid_id) {
            return ['entry' => null, 'constraint' => null];
        }

        $nationality = $this->nationalityOf($contract);
        $assetAccountId = $this->assetAccountId($nationality);
        $costReturnsAccountId = NationalityAccountResolver::forNationality($nationality, 'recruitment_cost_return');
        if (!$costReturnsAccountId) {
            throw new \RuntimeException('لا يوجد حساب مردودات تكلفة مرتبط بجنسية العقد: ' . $this->nationalityName($nationality));
        }

        $constraint = null;
        if (AutomaticJournalEntries::enabled()) {
            $constraint = $this->storeConstraint([
                $this->line($assetAccountId, 'debtor', $amount, $statement, $this->ensureMaidCostCenter($contract)->id),
                $this->line($costReturnsAccountId, 'creditor', $amount, $statement),
            ], $statement, $date);
        }

        $entry = $this->record($contract, $kind, $amount, $constraint, $source);
        if (Schema::hasColumn('shelter_cost_entries', 'office_account_id')) {
            $provenance = app(ShelterAgentCostRecoveryService::class)->provenance($entry);
            $entry->update([
                'office_account_id' => $provenance['office_account_id'],
                'supplier_constraint_id' => $provenance['supplier_constraint_id'],
                'asset_account_id' => $assetAccountId,
            ]);
        }
        return ['entry' => $entry, 'constraint' => $constraint];
    }

    // ── S2: مصروف الإعاشة ───────────────────────────────────────────────────

    /**
     * تسجيل نصيب العاملة من مصروف إعاشة قُيِّد بالفعل على أصل الإيواء (حين تُفعَّل الرسملة).
     */
    public function recordLivingExpenseAllocation(Contract $contract, float $amount, ?Constraint $constraint, Model $allocation): ?ShelterCostEntry
    {
        return DB::transaction(function () use ($contract, $amount, $constraint, $allocation) {
            if ($contract->maid_id) { Maid::query()->whereKey($contract->maid_id)->lockForUpdate()->firstOrFail(); }
            return $this->recordLivingExpenseAllocationLocked($contract, $amount, $constraint, $allocation);
        });
    }

    private function recordLivingExpenseAllocationLocked(Contract $contract, float $amount, ?Constraint $constraint, Model $allocation): ?ShelterCostEntry
    {
        if ($this->findEntry($allocation, ShelterCostEntry::KIND_LIVING_EXPENSE)) {
            return null;
        }

        return $this->record($contract, ShelterCostEntry::KIND_LIVING_EXPENSE, $amount, $constraint, $allocation);
    }

    public function recordLivingExpenseReversal(Contract $contract, float $amount, ?Constraint $constraint, Model $allocation): ?ShelterCostEntry
    {
        return DB::transaction(function () use ($contract, $amount, $constraint, $allocation) {
            if ($contract->maid_id) { Maid::query()->whereKey($contract->maid_id)->lockForUpdate()->firstOrFail(); }
            return $this->recordLivingExpenseReversalLocked($contract, $amount, $constraint, $allocation);
        });
    }

    private function recordLivingExpenseReversalLocked(Contract $contract, float $amount, ?Constraint $constraint, Model $allocation): ?ShelterCostEntry
    {
        if ($this->findEntry($allocation, ShelterCostEntry::KIND_LIVING_EXPENSE_REVERSAL)) {
            return null;
        }

        return $this->record($contract, ShelterCostEntry::KIND_LIVING_EXPENSE_REVERSAL, -abs($amount), $constraint, $allocation);
    }

    // ── S3: نقل الخدمات ──────────────────────────────────────────────────────

    /**
     * خروج للتجربة: مدين إيواء التجربة / دائن أصل الإيواء برصيد العاملة كاملاً.
     * @return array{amount: float, constraint: ?Constraint}
     */
    public function releaseToTrial(SponsorshipTransfer $transfer, string $date): array
    {
        return DB::transaction(function () use ($transfer, $date) {
            if ($transfer->contract?->maid_id) { Maid::query()->whereKey($transfer->contract?->maid_id)->lockForUpdate()->firstOrFail(); }
            return $this->releaseToTrialLocked($transfer, $date);
        });
    }

    private function releaseToTrialLocked(SponsorshipTransfer $transfer, string $date): array
    {
        $kind = ShelterCostEntry::KIND_RELEASE_TO_TRIAL;
        if ($existing = $this->findEntry($transfer, $kind)) {
            return ['amount' => $this->roundMoney(abs((float) $existing->amount)), 'constraint' => $existing->constraint];
        }

        $contract = $transfer->contract;
        if (!$contract || !$contract->maid_id) {
            return ['amount' => 0.0, 'constraint' => null];
        }

        $amount = $this->balance((int) $contract->maid_id);
        if ($amount < 0) { throw new \RuntimeException('رصيد الإيواء سالب؛ يلزم مطابقة القيود قبل أي خروج أو تصفية.'); }
        $statement = 'إيواء — خروج للتجربة — نقل الخدمات #' . $transfer->id;
        $constraint = null;

        if ($amount > 0 && AutomaticJournalEntries::enabled()) {
            $nationality = $this->nationalityOf($contract);
            $constraint = $this->storeConstraint([
                $this->line($this->trialPrepaidAccountId(), 'debtor', $amount, $statement),
                $this->line($this->assetAccountId($nationality), 'creditor', $amount, $statement, $this->ensureMaidCostCenter($contract)->id),
            ], $statement, $date);
        }

        $this->record($contract, $kind, -$amount, $constraint, $transfer);

        return ['amount' => $amount, 'constraint' => $constraint];
    }

    /**
     * فشل التجربة: يعيد إلى أصل الإيواء نفس المبلغ الذي خرج به في S3.
     * @return array{amount: float, constraint: ?Constraint}
     */
    public function returnFromTrial(SponsorshipTransfer $transfer, string $date): array
    {
        return DB::transaction(function () use ($transfer, $date) {
            if ($transfer->contract?->maid_id) { Maid::query()->whereKey($transfer->contract?->maid_id)->lockForUpdate()->firstOrFail(); }
            return $this->returnFromTrialLocked($transfer, $date);
        });
    }

    private function returnFromTrialLocked(SponsorshipTransfer $transfer, string $date): array
    {
        $kind = ShelterCostEntry::KIND_RETURN_FROM_TRIAL;
        if ($existing = $this->findEntry($transfer, $kind)) {
            return ['amount' => $this->roundMoney((float) $existing->amount), 'constraint' => $existing->constraint];
        }

        $released = $this->findEntry($transfer, ShelterCostEntry::KIND_RELEASE_TO_TRIAL);
        $contract = $transfer->contract;
        if (!$released || !$contract || !$contract->maid_id) {
            return ['amount' => 0.0, 'constraint' => null];
        }

        $amount = $this->roundMoney(abs((float) $released->amount));
        $statement = 'إيواء — رجوع من التجربة — نقل الخدمات #' . $transfer->id;
        $constraint = null;

        if ($amount > 0 && AutomaticJournalEntries::enabled()) {
            $nationality = $this->nationalityOf($contract);
            $constraint = $this->storeConstraint([
                $this->line($this->assetAccountId($nationality), 'debtor', $amount, $statement, $this->ensureMaidCostCenter($contract)->id),
                $this->line($this->trialPrepaidAccountId(), 'creditor', $amount, $statement),
            ], $statement, $date);
        }

        $this->record($contract, $kind, $amount, $constraint, $transfer);

        return ['amount' => $amount, 'constraint' => $constraint];
    }

    /**
     * المبلغ الذي خرج به رصيد العاملة للتجربة (لإقفال إيواء التجربة عند الإتمام).
     */
    public function trialReleasedAmount(SponsorshipTransfer $transfer): float
    {
        $released = $this->findEntry($transfer, ShelterCostEntry::KIND_RELEASE_TO_TRIAL);

        return $released ? $this->roundMoney(abs((float) $released->amount)) : 0.0;
    }

    // ── القيد 18: الترحيل ───────────────────────────────────────────────────

    /**
     * ترحيل العاملة: مدين المكتب الخارجي (أو خسائر الإيواء) / دائن أصل الإيواء برصيد العاملة.
     * @return array{amount: float, constraint: ?Constraint, debit: string}
     */
    public function releaseToDeportation(DeportationProcedure $procedure, string $date): array
    {
        return DB::transaction(function () use ($procedure, $date) {
            if ($procedure->contract?->maid_id) { Maid::query()->whereKey($procedure->contract?->maid_id)->lockForUpdate()->firstOrFail(); }
            return $this->releaseToDeportationLocked($procedure, $date);
        });
    }

    private function releaseToDeportationLocked(DeportationProcedure $procedure, string $date): array
    {
        $kind = ShelterCostEntry::KIND_RELEASE_TO_DEPORTATION;
        if ($existing = $this->findEntry($procedure, $kind)) {
            return ['amount' => $this->roundMoney(abs((float) $existing->amount)), 'constraint' => $existing->constraint, 'debit' => 'existing'];
        }

        $contract = $procedure->contract;
        if (!$contract || !$contract->maid_id) {
            return ['amount' => 0.0, 'constraint' => null, 'debit' => 'none'];
        }

        $amount = $this->balance((int) $contract->maid_id);
        if ($amount < 0) { throw new \RuntimeException('رصيد الإيواء سالب؛ يلزم مطابقة القيود قبل أي خروج أو تصفية.'); }
        $statement = 'ترحيل عاملة — استرداد التكلفة — عقد #' . $contract->id;
        $constraint = null;
        $debit = 'none';

        if ($amount > 0) {
            if (!AutomaticJournalEntries::enabled()) {
                throw new \RuntimeException('يلزم تفعيل القيود الآلية لتصفية رصيد الإيواء عند الترحيل.');
            }
            $summary = app(ShelterAgentCostRecoveryService::class)->summary($contract);
            if ($summary['issues']) { throw new \RuntimeException(implode(' ', $summary['issues'])); }
            $lines = []; $debits = [];
            $lossId = ShelterAccountingSettings::deportationLossAccountId();
            foreach ($summary['lots'] as $lot) {
                if ($lot['remaining'] <= 0) { continue; }
                if (!$lot['asset_account_id']) { throw new \RuntimeException('حساب أصل تكلفة الوكيل غير مثبت.'); }
                if (ShelterAccountingSettings::deportationChargesExternalOffice()) {
                    if (!$lot['valid']) { throw new \RuntimeException('تعذر إثبات المكتب الأصلي أو سبق عكس تكلفة المورد؛ راجع التكلفة قبل الترحيل.'); }
                    $debitId = $lot['office_account_id']; $debits[] = 'المكتب الخارجي';
                } else {
                    $debitId = $lossId; $debits[] = 'خسائر الإيواء';
                }
                if (!$debitId) { throw new \RuntimeException('حدد حساب خسائر الإيواء في إعدادات الحسابات.'); }
                $lines[] = $this->line($debitId, 'debtor', $lot['remaining'], $statement . ' — تكلفة الوكيل');
                $lines[] = $this->line($lot['asset_account_id'], 'creditor', $lot['remaining'], $statement . ' — تكلفة الوكيل', $this->ensureMaidCostCenter($contract)->id);
            }
            if ($summary['living'] > 0) {
                $livingDebit = $lossId;
                $livingLabel = 'خسائر الإيواء';
                if (ShelterAccountingSettings::deportationLivingChargesOffice()) {
                    $offices = array_unique(array_filter(array_column($summary['lots'], 'office_account_id')));
                    if (count($offices) !== 1) { throw new \RuntimeException('لا مكتب أصلي وحيد مثبت لتحميل الإعاشة؛ اختر حساب الخسائر.'); }
                    $livingDebit = reset($offices); $livingLabel = 'المكتب الخارجي';
                }
                if (!$livingDebit) { throw new \RuntimeException('حدد حساب خسائر الإيواء لمعالجة الإعاشة المتبقية.'); }
                $lines[] = $this->line($livingDebit, 'debtor', $summary['living'], $statement . ' — إعاشة');
                $lines[] = $this->line($this->assetAccountIdForContract($contract), 'creditor', $summary['living'], $statement . ' — إعاشة', $this->ensureMaidCostCenter($contract)->id);
                $debits[] = $livingLabel;
            }
            $constraint = $this->storeConstraint($lines, $statement, $date);
            $debit = implode(' + ', array_unique($debits));
        }

        $this->record($contract, $kind, -$amount, $constraint, $procedure, $debit === 'none' ? null : 'المدين: ' . $debit);

        return ['amount' => $amount, 'constraint' => $constraint, 'debit' => $debit];
    }

    // ── S4: إعادة بيع من الإيواء ────────────────────────────────────────────

    /**
     * عند فوترة عقد جديد لعاملة داخل الإيواء: مدين تكاليف مبيعات (الجنسية) على مركز تكلفة
     * العقد الجديد / دائن أصل الإيواء برصيد العاملة.
     * @return array{amount: float, constraint: ?Constraint}
     */
    public function releaseToSale(Contract $newContract, string $date, ?int $costCenterId = null): array
    {
        return DB::transaction(function () use ($newContract, $date, $costCenterId) {
            if ($newContract->maid_id) { Maid::query()->whereKey($newContract->maid_id)->lockForUpdate()->firstOrFail(); }
            return $this->releaseToSaleLocked($newContract, $date, $costCenterId);
        });
    }

    private function releaseToSaleLocked(Contract $newContract, string $date, ?int $costCenterId = null): array
    {
        $kind = ShelterCostEntry::KIND_RELEASE_TO_SALE;
        if ($existing = $this->findEntry($newContract, $kind)) {
            return ['amount' => $this->roundMoney(abs((float) $existing->amount)), 'constraint' => $existing->constraint];
        }

        if (!$newContract->maid_id) {
            return ['amount' => 0.0, 'constraint' => null];
        }

        $amount = $this->balance((int) $newContract->maid_id);
        if ($amount < 0) { throw new \RuntimeException('رصيد الإيواء سالب؛ يلزم مطابقة القيود قبل أي خروج أو تصفية.'); }
        if ($amount <= 0) {
            if (ShelterCostEntry::query()->where('maid_id', $newContract->maid_id)
                ->whereIn('kind', [ShelterCostEntry::KIND_AGENT_RECOVERY, ShelterCostEntry::KIND_POST_REFUND_RECOVERY])->exists()) {
                $this->record($newContract, $kind, 0, null, $newContract, 'بيع بعد استرداد تكلفة الوكيل');
            }
            return ['amount' => 0.0, 'constraint' => null];
        }

        $statement = 'إيواء — إعادة بيع عاملة — عقد #' . $newContract->id;
        $constraint = null;

        if (AutomaticJournalEntries::enabled()) {
            $nationality = $this->nationalityOf($newContract);
            $costAccountId = app(RecruitmentTripAccountingService::class)
                ->resolveCostOfSalesAccountId($this->nationalityName($nationality), $nationality?->id ? (int) $nationality->id : null);
            if (!$costAccountId) {
                throw new \RuntimeException('لا يوجد حساب تكلفة مبيعات مرتبط بجنسية العقد: ' . $this->nationalityName($nationality));
            }

            $constraint = $this->storeConstraint([
                $this->line($costAccountId, 'debtor', $amount, $statement, $costCenterId),
                $this->line($this->assetAccountId($nationality), 'creditor', $amount, $statement, $this->ensureMaidCostCenter($newContract)->id),
            ], $statement, $date);
        }

        $this->record($newContract, $kind, -$amount, $constraint, $newContract);

        return ['amount' => $amount, 'constraint' => $constraint];
    }

    // ── تصفية ────────────────────────────────────────────────────────────────

    public function writeOff(Contract $contract, string $date, string $reason, Model $source): array
    {
        return DB::transaction(function () use ($contract, $date, $reason, $source) {
            if ($contract->maid_id) { Maid::query()->whereKey($contract->maid_id)->lockForUpdate()->firstOrFail(); }
            return $this->writeOffLocked($contract, $date, $reason, $source);
        });
    }

    private function writeOffLocked(Contract $contract, string $date, string $reason, Model $source): array
    {
        $kind = ShelterCostEntry::KIND_WRITE_OFF;
        if ($existing = $this->findEntry($source, $kind)) {
            return ['amount' => $this->roundMoney(abs((float) $existing->amount)), 'constraint' => $existing->constraint];
        }

        $amount = $this->balance((int) $contract->maid_id);
        if ($amount < 0) { throw new \RuntimeException('رصيد الإيواء سالب؛ يلزم مطابقة القيود قبل أي خروج أو تصفية.'); }
        if ($amount <= 0) {
            return ['amount' => 0.0, 'constraint' => null];
        }

        $writeOffAccountId = ShelterAccountingSettings::writeOffAccountId();
        if (!$writeOffAccountId) {
            throw new \RuntimeException('لم يُحدد حساب تصفية رصيد الإيواء في إعدادات الحسابات.');
        }

        $statement = 'إيواء — تصفية رصيد — عقد #' . $contract->id . ' — ' . $reason;
        $constraint = null;
        if (AutomaticJournalEntries::enabled()) {
            $nationality = $this->nationalityOf($contract);
            $constraint = $this->storeConstraint([
                $this->line($writeOffAccountId, 'debtor', $amount, $statement),
                $this->line($this->assetAccountId($nationality), 'creditor', $amount, $statement, $this->ensureMaidCostCenter($contract)->id),
            ], $statement, $date);
        }

        $this->record($contract, $kind, -$amount, $constraint, $source, $reason);

        return ['amount' => $amount, 'constraint' => $constraint];
    }

    // ── الحسابات ومراكز التكلفة ───────────────────────────────────────────────

    public function assetAccountId(?Nationality $nationality): int
    {
        $accountId = NationalityAccountResolver::forNationality($nationality, 'shelter_prepaid_expense');
        if (!$accountId) {
            throw new \RuntimeException('لا يوجد حساب إيواء (مصروفات مدفوعة مقدماً - إيواء) مرتبط بجنسية العقد: ' . $this->nationalityName($nationality));
        }

        return $accountId;
    }

    public function assetAccountIdForContract(Contract $contract): int
    {
        return $this->assetAccountId($this->nationalityOf($contract));
    }

    public function trialPrepaidAccountId(): int
    {
        $accountId = ShelterAccountingSettings::trialPrepaidAccountId()
            ?: NationalityAccountResolver::byName('التجربة', 'shelter_prepaid_expense');

        if (!$accountId) {
            throw new \RuntimeException('لم يُحدد حساب مصروفات مدفوعة مقدماً - إيواء التجربة في إعدادات الحسابات.');
        }

        return $accountId;
    }

    public function ensureMaidCostCenter(Contract $contract): CostCenter
    {
        $root = $this->ensureShelterRootCostCenter();
        $maidId = (int) $contract->maid_id;

        $costCenter = CostCenter::query()
            ->lockForUpdate()
            ->where('parent_id', $root->id)
            ->where('table_name', CostCenterTypeEnum::General->value)
            ->where('table_related_id', $maidId)
            ->first();

        if ($costCenter) {
            return $costCenter;
        }

        $title = 'إيواء عاملة #' . $maidId;
        $maidName = trim((string) ($contract->maid?->name ?? ''));
        if ($maidName !== '') {
            $title .= ' - ' . $maidName;
        }

        return CostCenter::query()->create([
            'title' => $title,
            'parent_id' => $root->id,
            'table_name' => CostCenterTypeEnum::General->value,
            'table_related_id' => $maidId,
            'code' => $this->nextCostCenterCode(),
            'created_by_id' => Auth::guard('admin')->id() ?: Auth::id() ?: 1,
        ]);
    }

    private function ensureShelterRootCostCenter(): CostCenter
    {
        $costCenter = CostCenter::query()
            ->lockForUpdate()
            ->whereNull('parent_id')
            ->where('title', 'عاملات الإيواء')
            ->first();

        if ($costCenter) {
            return $costCenter;
        }

        return CostCenter::query()->create([
            'title' => 'عاملات الإيواء',
            'parent_id' => null,
            'table_name' => CostCenterTypeEnum::General->value,
            'table_related_id' => 0,
            'code' => $this->nextCostCenterCode(),
            'created_by_id' => Auth::guard('admin')->id() ?: Auth::id() ?: 1,
        ]);
    }

    private function nextCostCenterCode(): string
    {
        return (string) (((int) CostCenter::query()->lockForUpdate()->max('code')) + 1);
    }

    public function nationalityOf(Contract $contract): ?Nationality
    {
        $contract->loadMissing(['maid.nationality', 'requiredMaidNationality']);

        return $contract->maid?->nationality ?? $contract->requiredMaidNationality;
    }

    private function nationalityName(?Nationality $nationality): string
    {
        return trim((string) ($nationality?->sub_title ?: $nationality?->title ?: ''));
    }

    public function postAgentRecovery(Contract $contract, \App\Models\ShelterAgentCostRecovery $recovery, string $date, bool $reverse): Constraint
    {
        $amount = (float) $recovery->amount;
        $statement = ($reverse ? 'عكس استرداد تكلفة الوكيل' : 'استرداد تكلفة الوكيل') . ' — عقد #' . $contract->id . ' — ' . ($reverse ? $recovery->reversal_reason : $recovery->reason);
        $lines = [
            $this->line((int) ($reverse ? $recovery->asset_account_id : $recovery->office_account_id), 'debtor', $amount, $statement, $reverse ? $recovery->cost_center_id : null),
            $this->line((int) ($reverse ? $recovery->office_account_id : $recovery->asset_account_id), 'creditor', $amount, $statement, $reverse ? null : $recovery->cost_center_id),
        ];
        $constraint = $this->storeConstraint($lines, $statement, $date);
        $this->record($contract, $reverse ? ShelterCostEntry::KIND_AGENT_RECOVERY_REVERSAL : ShelterCostEntry::KIND_AGENT_RECOVERY,
            $reverse ? $amount : -$amount, $constraint, $recovery, $statement);
        return $constraint;
    }

    // ── التخزين ──────────────────────────────────────────────────────────────

    public function recordPostArrivalRecovery(Contract $contract, ContractReturnSettlementLine $line, Constraint $constraint, bool $reverse = false): ?ShelterCostEntry
    {
        $kind = $reverse ? ShelterCostEntry::KIND_POST_REFUND_REVERSAL : ShelterCostEntry::KIND_POST_REFUND_RECOVERY;
        if ($existing = $this->findEntry($line, $kind)) return $existing;
        $receiptId = $line->metadata['shelter_receipt_entry_id'] ?? null;
        if (!$receiptId) return null;
        return $this->record($contract, $kind, ($reverse ? 1 : -1) * (float) $line->approved_amount,
            $constraint, $line, 'مستند استرداد بعد الوصول #' . $line->settlement_id);
    }

    private function record(Contract $contract, string $kind, float $amount, ?Constraint $constraint, Model $source, ?string $notes = null): ShelterCostEntry
    {
        return ShelterCostEntry::query()->create([
            'maid_id' => (int) $contract->maid_id,
            'contract_id' => $contract->id,
            'kind' => $kind,
            'amount' => $this->roundMoney($amount),
            'constraint_id' => $constraint?->id,
            'source_type' => get_class($source),
            'source_id' => $source->getKey(),
            'created_by_id' => Auth::guard('admin')->id() ?: Auth::id(),
            'notes' => $notes,
        ]);
    }

    private function storeConstraint(array $lines, string $statement, string $date): Constraint
    {
        $activeYear = $this->yearService->checkDateRange($date);
        if (!$activeYear) {
            throw new \RuntimeException('لا توجد سنة مالية نشطة لتاريخ قيد الإيواء (' . $date . ').');
        }

        $value = $this->roundMoney(array_sum(array_column($lines, 'debtor_value')));
        $data = [
            'code' => $this->constraintService->lastCode(),
            'date' => $date,
            'statement' => $statement,
            'account_id' => array_column($lines, 'account_id'),
            'account_type' => array_column($lines, 'account_type'),
            'debtor_value' => array_column($lines, 'debtor_value'),
            'creditor_value' => array_column($lines, 'creditor_value'),
            'statement_account' => array_column($lines, 'statement'),
            'cost_center_required' => 'off',
            'cost_center_id' => array_column($lines, 'cost_center_id'),
            'value' => $value,
        ];

        $constraint = $this->constraintService->storeConstraint($data, $activeYear);
        $this->constraintService->storeConstraintDetails($data['account_id'], $constraint->id, $data);
        $this->constraintService->storeFiles($constraint, $data);

        return $constraint;
    }

    private function line(int $accountId, string $type, float $amount, string $statement, ?int $costCenterId = null): array
    {
        $amount = $this->roundMoney($amount);

        return [
            'account_id' => $accountId,
            'account_type' => $type,
            'debtor_value' => $type === 'debtor' ? $amount : 0,
            'creditor_value' => $type === 'creditor' ? $amount : 0,
            'statement' => $statement,
            'cost_center_id' => $costCenterId,
        ];
    }

    private function roundMoney(float $amount): float
    {
        return round($amount, 2);
    }
}
