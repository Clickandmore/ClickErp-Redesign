<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\ContractReturnRequest;
use App\Models\DeportationProcedure;
use App\Models\ShelterCostEntry;
use App\Models\ShelterLivingExpenseAllocation;
use App\Models\SponsorshipTransfer;
use App\Services\ingaz\DeportationCompletionService;
use App\Services\ingaz\ShelterCostLedgerService;
use App\Support\ShelterAccountingSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Nami\Finance\Models\ConstraintDetails;

/**
 * تصحيح القيود السابقة: يبني دفتر الإيواء من القيود التي أُنشئت قبل وجوده، ثم يعرض
 * العاملات ذوات الرصيد المفتوح رغم خروجهن، ويستطيع (مع --apply) إنشاء قيد الترحيل
 * الناقص (القيد 18) للعاملات اللواتي اكتمل ترحيلهن بلا قيد.
 *
 * بلا --apply الأمر للقراءة فقط.
 */
class ReconcileShelterLedger extends Command
{
    protected $signature = 'finance:shelter-ledger-reconcile
                            {--apply : بناء الدفتر من القيود المثبتة (بدونه عرض فقط)}
                            {--post-missing-deportations : إنشاء قيود الترحيل الناقصة، مع --apply وبعد المراجعة}
                            {--maid= : حصر التشغيل على عاملة واحدة}';

    protected $description = 'بناء دفتر الإيواء من القيود القديمة وكشف الأرصدة المفتوحة وإنشاء قيود الترحيل الناقصة';

    private array $report = ['seeded' => 0, 'skipped' => 0, 'deportation_posted' => 0, 'open' => []];

    /** أرصدة محاكاة في وضع العرض فقط (لا تُكتب الحركات فلا يراها balance()). */
    private array $simulated = [];

    private function simulate(int $maidId, float $amount): void
    {
        $this->simulated[$maidId] = round(($this->simulated[$maidId] ?? 0) + $amount, 2);
    }

    private function effectiveBalance(ShelterCostLedgerService $ledger, int $maidId): float
    {
        return round($ledger->balance($maidId) + ($this->simulated[$maidId] ?? 0), 2);
    }

    public function handle(ShelterCostLedgerService $ledger, DeportationCompletionService $completion): int
    {
        if (!Schema::hasTable('shelter_cost_entries')) {
            $this->error('جدول shelter_cost_entries غير موجود. شغّل php artisan migrate أولاً.');

            return self::FAILURE;
        }

        $apply = (bool) $this->option('apply');
        $maidFilter = $this->option('maid') ? (int) $this->option('maid') : null;

        $this->line($apply ? 'وضع التنفيذ' : 'وضع العرض فقط (أضف --apply للتنفيذ)');

        try {
            DB::transaction(function () use ($ledger, $completion, $apply, $maidFilter) {
                $this->seedFromWarrantyReturns($ledger, $apply, $maidFilter);
                $this->seedFromLivingExpenses($apply, $maidFilter);
                $this->seedFromTransfers($ledger, $apply, $maidFilter);
                if ($this->option('post-missing-deportations')) {
                    $this->postMissingDeportationEntries($ledger, $completion, $apply, $maidFilter);
                } else {
                    $this->line('لم تُنشأ قيود ترحيل: يلزم --post-missing-deportations بعد مراجعة المحاسب.');
                }
                $this->collectOpenBalances($ledger, $maidFilter);

                if (!$apply) {
                    // العرض فقط: نتراجع عن أي كتابة عرضية ونُبقي المخرجات.
                    throw new \RuntimeException('__dry_run__');
                }
            }, 1);
        } catch (\RuntimeException $e) {
            if ($e->getMessage() !== '__dry_run__') {
                throw $e;
            }
        }

        return self::SUCCESS;
    }

    /**
     * القيد 9 القديم كان يحمل سطر أصل الإيواء مديناً؛ نسجّله كاستلام في الدفتر.
     */
    private function seedFromWarrantyReturns(ShelterCostLedgerService $ledger, bool $apply, ?int $maidFilter): void
    {
        $requests = ContractReturnRequest::query()
            ->withoutGlobalScopes()
            ->where('status', 'approved')
            ->with(['contract.maid.nationality', 'contract.requiredMaidNationality'])
            ->get();

        foreach ($requests as $request) {
            $contract = $request->contract;
            if (!$contract || !$contract->maid_id || ($maidFilter && (int) $contract->maid_id !== $maidFilter)) {
                continue;
            }
            if ($ledger->findEntry($request, ShelterCostEntry::KIND_CAPITALIZE_OFFICE_COST)) {
                $this->report['skipped']++;
                continue;
            }

            $assetAccountId = $this->safeAssetAccount($ledger, $contract);
            if (!$assetAccountId) {
                $this->warn("طلب إرجاع #{$request->id}: لا حساب إيواء للجنسية، تخطٍّ.");
                continue;
            }

            $statement = 'رجوع عاملة داخل الضمان — عقد استقدام #' . $contract->id;
            $detail = ConstraintDetails::query()
                ->where('account_id', $assetAccountId)
                ->where('debtor_value', '>', 0)
                ->whereHas('constraint', fn ($q) => $q->where('statement', $statement))
                ->orderBy('id')
                ->first();

            $shelterConstraintId = Schema::hasColumn('contract_return_requests', 'shelter_constraint_id') ? $request->shelter_constraint_id : null;
            if (!$detail && $shelterConstraintId) {
                $detail = ConstraintDetails::query()->where('constraint_id', $shelterConstraintId)
                    ->where('account_id', $assetAccountId)->where('debtor_value', '>', 0)->first();
            }
            if (!$detail) {
                $this->warn('طلب إرجاع #' . $request->id . ': لا قيد تكلفة إيواء مثبت؛ لن تُنشأ تكلفة من قيمة العقد.');
                continue;
            }

            $amount = $detail ? round((float) $detail->debtor_value, 2) : 0.0;
            $this->line("طلب إرجاع #{$request->id} / عاملة #{$contract->maid_id}: استلام {$amount}");
            if (!$apply) { $this->simulate((int) $contract->maid_id, $amount); }
            if ($apply && $amount > 0) {
                $entry = ShelterCostEntry::query()->create([
                    'maid_id' => $contract->maid_id,
                    'contract_id' => $contract->id,
                    'kind' => ShelterCostEntry::KIND_CAPITALIZE_OFFICE_COST,
                    'amount' => $amount,
                    'constraint_id' => $detail?->constraint_id,
                    'source_type' => ContractReturnRequest::class,
                    'source_id' => $request->id,
                    'notes' => 'مبني من القيد 9 القديم',
                ]);
                if (Schema::hasColumn('shelter_cost_entries', 'office_account_id')) {
                    $origin = app(\App\Services\ingaz\ShelterAgentCostRecoveryService::class)->provenance($entry);
                    $entry->update(['office_account_id' => $origin['office_account_id'],
                        'supplier_constraint_id' => $origin['supplier_constraint_id'], 'asset_account_id' => $origin['asset_account_id']]);
                }
            }
            $this->report['seeded']++;
        }
    }

    /**
     * تخصيصات الإعاشة القديمة: تُسجَّل فقط عند مطابقة قيد الأصل ومركز تكلفة العاملة،
     * دون الاعتماد على إعداد الرسملة الحالي.
     */
    private function seedFromLivingExpenses(bool $apply, ?int $maidFilter): void
    {
        $allocations = ShelterLivingExpenseAllocation::query()
            ->withoutGlobalScopes()
            ->with(['expense', 'contract.maid.nationality', 'contract.requiredMaidNationality'])
            ->when($maidFilter, fn ($q) => $q->where('maid_id', $maidFilter))
            ->get();

        foreach ($allocations as $allocation) {
            if (!$allocation->maid_id) {
                continue;
            }
            $exists = ShelterCostEntry::query()
                ->where('source_type', ShelterLivingExpenseAllocation::class)
                ->where('source_id', $allocation->id)
                ->exists();
            if ($exists) {
                $this->report['skipped']++;
                continue;
            }

            // Today's setting cannot turn a historical period expense into an asset.
            $assetId = $allocation->contract ? $this->safeAssetAccount(app(ShelterCostLedgerService::class), $allocation->contract) : null;
            $posted = $assetId && $allocation->expense?->constraint_id
                ? (float) ConstraintDetails::query()->where('constraint_id', $allocation->expense->constraint_id)
                    ->where('account_id', $assetId)->where('cost_center_id', $allocation->cost_center_id)->sum('debtor_value') : 0;
            if (!$allocation->cost_center_id || round($posted, 2) !== round((float) $allocation->amount, 2)
                || $allocation->expense?->reversed_at) {
                $this->warn('إعاشة #' . $allocation->id . ': ليست تكلفة أصل مثبتة ومطابقة أو سبق عكسها؛ مراجعة يدوية.');
                $this->report['skipped']++;
                continue;
            }
            if (!$apply) { $this->simulate((int) $allocation->maid_id, round((float) $allocation->amount, 2)); }
            if ($apply) {
                ShelterCostEntry::query()->create([
                    'maid_id' => $allocation->maid_id,
                    'contract_id' => $allocation->contract_id,
                    'kind' => ShelterCostEntry::KIND_LIVING_EXPENSE,
                    'amount' => round((float) $allocation->amount, 2),
                    'constraint_id' => $allocation->expense?->constraint_id,
                    'source_type' => ShelterLivingExpenseAllocation::class,
                    'source_id' => $allocation->id,
                    'notes' => 'مبني من مصروف إعاشة قديم',
                ]);
            }
            $this->report['seeded']++;
        }
    }

    /**
     * نقل الخدمات القديم أفرج عن مجموع الإعاشة عند بدء التجربة وأعاده عند الفشل.
     */
    private function seedFromTransfers(ShelterCostLedgerService $ledger, bool $apply, ?int $maidFilter): void
    {
        $transfers = SponsorshipTransfer::query()
            ->withoutGlobalScopes()
            ->with(['contract.maid.nationality', 'contract.requiredMaidNationality'])
            ->where(function ($q) {
                $q->whereNotNull('trial_start_constraint_id')->orWhereNotNull('failure_constraint_id');
            })
            ->get();

        foreach ($transfers as $transfer) {
            $contract = $transfer->contract;
            if (!$contract || !$contract->maid_id || ($maidFilter && (int) $contract->maid_id !== $maidFilter)) {
                continue;
            }
            $assetAccountId = $this->safeAssetAccount($ledger, $contract);
            if (!$assetAccountId) {
                continue;
            }

            foreach ([
                ['column' => 'trial_start_constraint_id', 'kind' => ShelterCostEntry::KIND_RELEASE_TO_TRIAL, 'side' => 'creditor_value', 'sign' => -1],
                ['column' => 'failure_constraint_id', 'kind' => ShelterCostEntry::KIND_RETURN_FROM_TRIAL, 'side' => 'debtor_value', 'sign' => 1],
            ] as $spec) {
                $constraintId = $transfer->{$spec['column']};
                if (!$constraintId || $ledger->findEntry($transfer, $spec['kind'])) {
                    continue;
                }
                $amount = round((float) ConstraintDetails::query()
                    ->where('constraint_id', $constraintId)
                    ->where('account_id', $assetAccountId)
                    ->sum($spec['side']), 2);
                if ($amount <= 0) {
                    continue;
                }

                $this->line("نقل خدمات #{$transfer->id} / عاملة #{$contract->maid_id}: {$spec['kind']} {$amount}");
                if (!$apply) { $this->simulate((int) $contract->maid_id, $spec['sign'] * $amount); }
                if ($apply) {
                    ShelterCostEntry::query()->create([
                        'maid_id' => $contract->maid_id,
                        'contract_id' => $contract->id,
                        'kind' => $spec['kind'],
                        'amount' => $spec['sign'] * $amount,
                        'constraint_id' => $constraintId,
                        'source_type' => SponsorshipTransfer::class,
                        'source_id' => $transfer->id,
                        'notes' => 'مبني من قيد نقل خدمات قديم',
                    ]);
                }
                $this->report['seeded']++;
            }
        }
    }

    /**
     * ترحيل مكتمل بلا قيد: يُنشأ القيد 18 برصيد العاملة الحالي (بعد البناء أعلاه).
     */
    private function postMissingDeportationEntries(ShelterCostLedgerService $ledger, DeportationCompletionService $completion, bool $apply, ?int $maidFilter): void
    {
        $procedures = DeportationProcedure::query()
            ->withoutGlobalScopes()
            ->where('status', 'completed')
            ->when(Schema::hasColumn('deportation_procedures', 'constraint_id'), fn ($q) => $q->whereNull('constraint_id'))
            ->with(['contract.maid', 'contract.info'])
            ->get();

        foreach ($procedures as $procedure) {
            $contract = $procedure->contract;
            if (!$contract || !$contract->maid_id || ($maidFilter && (int) $contract->maid_id !== $maidFilter)) {
                continue;
            }
            if ($ledger->findEntry($procedure, ShelterCostEntry::KIND_RELEASE_TO_DEPORTATION)) {
                continue;
            }

            $balance = $this->effectiveBalance($ledger, (int) $contract->maid_id);
            $this->line("ترحيل #{$procedure->id} / عاملة #{$contract->maid_id}: الرصيد {$balance}" . ($balance > 0 ? ' → سيُنشأ القيد 18' : ' → بلا قيد'));
            if (!$apply && $balance > 0) {
                $this->simulate((int) $contract->maid_id, -$balance);
                $this->report['deportation_posted']++;
            }

            if ($apply) {
                try {
                    $result = $completion->complete($procedure, now()->toDateString());
                    if ($result['constraint']) {
                        $this->report['deportation_posted']++;
                    }
                } catch (\Throwable $e) {
                    $this->warn("ترحيل #{$procedure->id}: {$e->getMessage()}");
                }
            }
        }
    }

    private function collectOpenBalances(ShelterCostLedgerService $ledger, ?int $maidFilter): void
    {
        $maidIds = ShelterCostEntry::query()
            ->when($maidFilter, fn ($q) => $q->where('maid_id', $maidFilter))
            ->select('maid_id')
            ->groupBy('maid_id')
            ->pluck('maid_id')
            ->merge(array_keys($this->simulated))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $rows = [];
        foreach ($maidIds as $maidId) {
            $balance = $this->effectiveBalance($ledger, (int) $maidId);
            if (abs($balance) < 0.005) {
                continue;
            }
            $inShelter = Contract::query()->withoutGlobalScopes()
                ->where('maid_id', $maidId)
                ->where('status', 'shelter')
                ->exists();
            $rows[] = [$maidId, number_format($balance, 2), $balance < 0 ? 'رصيد سالب: مطابقة القيود قبل أي تصفية' : ($inShelter ? 'داخل الإيواء' : 'خارج الإيواء (يحتاج تصفية أو قيد خروج)')];
        }

        $this->report['open'] = $rows;
        $this->newLine();
        $this->info("بُني: {$this->report['seeded']} · متخطّى: {$this->report['skipped']} · قيود ترحيل " . ($this->option('apply') ? 'أُنشئت' : 'ستُنشأ') . ": {$this->report['deportation_posted']}");
        if ($rows) {
            $this->table(['عاملة', 'رصيد الإيواء', 'الحالة'], $rows);
        } else {
            $this->line('لا أرصدة إيواء مفتوحة.');
        }
    }

    private function safeAssetAccount(ShelterCostLedgerService $ledger, Contract $contract): ?int
    {
        try {
            return $ledger->assetAccountIdForContract($contract);
        } catch (\RuntimeException $e) {
            return null;
        }
    }
}
