<?php

namespace App\Services\ingaz;

use App\Models\Contract;
use App\Models\ContractReturnRequest;
use App\Models\TravelHistory;
use App\Support\AutomationSettings;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\Constraint;
use Nami\Finance\Services\ConstraintService;
use Nami\Finance\Services\YearService;

class RecruitmentContractWarrantyReturnService
{
    private const USD_TO_SAR_RATE = 3.75;

    public function __construct(
        protected ConstraintService $constraintService,
        protected YearService $yearService
    ) {
    }

    /**
     * ينشئ القيد المحاسبي عند الموافقة على طلب رجوع العاملة داخل فترة الضمان.
     * القيد #9: يعكس المبيعات والضريبة، يُعيد قيمة العقد لذمة العميل،
     * يُثبت خصم مدة البقاء، وينقل تكلفة المكتب الخارجي لحساب الإيواء.
     */
    public function record(Contract $contract, ContractReturnRequest $returnRequest): ?Constraint
    {
        $contract->loadMissing([
            'user',
            'invoice',
            'maid.nationality',
            'requiredMaidNationality',
            'externalOfficeDispatch',
            'info',
        ]);

        $invoice = $contract->invoice;
        if (!$invoice) {
            return null;
        }

        $returnDate = $returnRequest->processed_at?->toDateString() ?? now()->toDateString();
        $activeYear = $this->yearService->checkDateRange($returnDate);
        if (!$activeYear) {
            throw new \RuntimeException('لا يوجد سنة مالية نشطة لتاريخ معالجة طلب الاسترجاع.');
        }

        $nationalityName = $this->resolveNationalityName($contract);
        if ($nationalityName === '') {
            throw new \RuntimeException('لا يمكن إنشاء القيد المحاسبي لأن جنسية العقد غير محددة.');
        }

        $clientAccountId = (int) ($contract->user?->account_id ?: 0);
        if (!$clientAccountId) {
            throw new \RuntimeException('لا يوجد حساب محاسبي للعميل المرتبط بالعقد.');
        }

        $totalDue      = $this->roundMoney((float) $invoice->total_due);
        $vatAmount     = $this->roundMoney((float) $invoice->vat_amount);
        $netInvoice    = $this->roundMoney($totalDue - $vatAmount);
        $stayAmount    = $this->calculateStayAmount($contract, $returnDate, $netInvoice);
        $officeCost    = $this->resolveExternalOfficeCostAmount($contract);

        $accommodationAccountId  = $this->resolveAccommodationAccountId($nationalityName);
        $salesReturnsAccountId   = $this->resolveSalesReturnsAccountId($nationalityName);
        $vatAccountId            = $this->resolveVatAccountId();
        $salesRecoveryAccountId  = $this->resolveSalesRecoveryAccountId();
        $costReturnsAccountId    = $this->resolveCostReturnsAccountId($nationalityName);

        if (!$accommodationAccountId) {
            throw new \RuntimeException('لا يوجد حساب إيواء مرتبط بجنسية العقد: ' . $nationalityName);
        }
        if (!$salesReturnsAccountId) {
            throw new \RuntimeException('لا يوجد حساب مردود مبيعات مرتبط بجنسية العقد: ' . $nationalityName);
        }
        if (!$vatAccountId) {
            throw new \RuntimeException('لا يوجد حساب ضريبة إيرادات في النظام.');
        }
        if (!$salesRecoveryAccountId) {
            throw new \RuntimeException('لا يوجد حساب إيراد استرداد مبيعات في النظام.');
        }
        if (!$costReturnsAccountId) {
            throw new \RuntimeException('لا يوجد حساب مردودات تكلفة مرتبط بجنسية العقد: ' . $nationalityName);
        }

        $statement = 'رجوع عاملة داخل الضمان — عقد استقدام #' . $contract->id;
        $lines = [];

        // 1. نقل تكلفة المكتب الخارجي إلى حساب الإيواء (أصل — عاملة متاحة لإعادة التسكين)
        if ($officeCost > 0) {
            $this->appendLine($lines, $accommodationAccountId, 'debtor', $officeCost, $statement);
        }

        // 2. إلغاء قيمة البيع عبر مردود مبيعات
        if ($netInvoice > 0) {
            $this->appendLine($lines, $salesReturnsAccountId, 'debtor', $netInvoice, $statement);
        }

        // 3. عكس ضريبة القيمة المضافة
        if ($vatAmount > 0) {
            $this->appendLine($lines, $vatAccountId, 'debtor', $vatAmount, $statement);
        }

        // 4. إعادة كامل قيمة العقد لذمة العميل (دائن)
        if ($totalDue > 0) {
            $this->appendLine($lines, $clientAccountId, 'creditor', $totalDue, $statement);
        }

        // 5. خصم مدة البقاء على العميل (مدين)
        if ($stayAmount > 0) {
            $this->appendLine($lines, $clientAccountId, 'debtor', $stayAmount, $statement);
        }

        // 6. إيراد مدة البقاء
        if ($stayAmount > 0) {
            $this->appendLine($lines, $salesRecoveryAccountId, 'creditor', $stayAmount, $statement);
        }

        // 7. عكس التكلفة عبر مردودات تكلفة
        if ($officeCost > 0) {
            $this->appendLine($lines, $costReturnsAccountId, 'creditor', $officeCost, $statement);
        }

        if (empty($lines)) {
            return null;
        }

        $value = $this->roundMoney(array_sum(array_column($lines, 'debtor_value')));
        $data = [
            'code'               => $this->constraintService->lastCode(),
            'date'               => $returnDate,
            'statement'          => $statement,
            'account_id'         => array_column($lines, 'account_id'),
            'account_type'       => array_column($lines, 'account_type'),
            'debtor_value'       => array_column($lines, 'debtor_value'),
            'creditor_value'     => array_column($lines, 'creditor_value'),
            'statement_account'  => array_column($lines, 'statement'),
            'cost_center_required' => 'off',
            'cost_center_id'     => array_column($lines, 'cost_center_id'),
            'value'              => $value,
        ];

        $constraint = $this->constraintService->storeConstraint($data, $activeYear);
        $this->constraintService->storeConstraintDetails($data['account_id'], $constraint->id, $data);
        $this->constraintService->storeFiles($constraint, $data);

        return $constraint;
    }

    private function calculateStayAmount(Contract $contract, string $returnDate, float $netInvoice): float
    {
        $arrivalDate = $this->resolveArrivalDate($contract);
        if (!$arrivalDate) {
            return 0.0;
        }

        $daysStayed = max(0, Carbon::parse($arrivalDate)->startOfDay()->diffInDays(
            Carbon::parse($returnDate)->startOfDay(),
            false
        ));

        if ($daysStayed <= 0 || $netInvoice <= 0) {
            return 0.0;
        }

        $warrantyDays = AutomationSettings::warrantyPeriodDays();
        if ($warrantyDays <= 0) {
            return 0.0;
        }

        return $this->roundMoney(($netInvoice / $warrantyDays) * $daysStayed);
    }

    private function resolveArrivalDate(Contract $contract): ?string
    {
        $travelHistory = TravelHistory::query()
            ->where('contract_id', $contract->id)
            ->whereNotNull('arrival_date')
            ->orderByDesc('arrival_date')
            ->first();

        if ($travelHistory?->arrival_date) {
            return Carbon::parse($travelHistory->arrival_date)->toDateString();
        }

        $contractInfo = $contract->info;
        if ($contractInfo?->arrival_date) {
            return Carbon::parse($contractInfo->arrival_date)->toDateString();
        }

        return null;
    }

    private function resolveExternalOfficeCostAmount(Contract $contract): float
    {
        $usdAmount = $this->roundMoney(
            (float) ($contract->externalOfficeDispatch?->office_contract_cost_usd ?? 0)
        );

        if ($usdAmount <= 0) {
            return 0.0;
        }

        return $this->roundMoney($usdAmount * self::USD_TO_SAR_RATE);
    }

    private function resolveNationalityName(Contract $contract): string
    {
        $nationality = $contract->maid?->nationality ?? $contract->requiredMaidNationality;
        return trim((string) ($nationality?->sub_title ?? $nationality?->title ?? ''));
    }

    // ── Account lookups ────────────────────────────────────────────────────────

    private function resolveAccommodationAccountId(string $nationalityName): ?int
    {
        return $this->resolveAccountByCandidates(
            [
                'إيواء (' . $nationalityName . ')',
                'إيواء ' . $nationalityName,
                'مصروفات مدفوعة مقدماً - إيواء (' . $nationalityName . ')',
                'مصروفات مدفوعة مقدماً - إيواء ' . $nationalityName,
            ],
            ['إيواء الفلبين', 'إيواء'],
            fn ($query) => $query->where('name', 'like', '%إيواء%')
                                 ->where('name', 'like', '%' . $nationalityName . '%')
        );
    }

    private function resolveSalesReturnsAccountId(string $nationalityName): ?int
    {
        return $this->resolveAccountByCandidates(
            [
                'مردود مبيعات استقدام (' . $nationalityName . ')',
                'مردود مبيعات استقدام ' . $nationalityName,
                'مردود مبيعات (' . $nationalityName . ')',
                'مردود مبيعات ' . $nationalityName,
            ],
            ['مردود مبيعات الاستقدام', 'مردود مبيعات'],
            fn ($query) => $query->where('name', 'like', '%مردود مبيعات%')
                                 ->where('name', 'like', '%' . $nationalityName . '%')
        );
    }

    private function resolveVatAccountId(): ?int
    {
        return $this->resolveAccountByCandidates(
            [
                'حساب الضريبة على الإيرادات',
                'الضريبة على الإيرادات',
                'ضريبة القيمة المضافة',
                'ضريبة الإيرادات',
            ],
            [],
            fn ($query) => $query->where(function ($q) {
                $q->where('name', 'like', '%ضريبة%إيراد%')
                  ->orWhere('name', 'like', '%ضريبة القيمة المضافة%');
            })
        );
    }

    private function resolveSalesRecoveryAccountId(): ?int
    {
        return $this->resolveAccountByCandidates(
            [
                'إيراد استرداد مبيعات',
                'إيراد استرداد',
                'إيراد الاسترداد',
            ],
            [],
            fn ($query) => $query->where('name', 'like', '%إيراد استرداد%')
        );
    }

    private function resolveCostReturnsAccountId(string $nationalityName): ?int
    {
        return $this->resolveAccountByCandidates(
            [
                'مردودات تكلفة (' . $nationalityName . ')',
                'مردودات تكلفة ' . $nationalityName,
                'مردودات تكلفة الفلبين',
            ],
            ['مردودات تكلفة'],
            fn ($query) => $query->where('name', 'like', '%مردودات تكلفة%')
                                 ->where('name', 'like', '%' . $nationalityName . '%')
        );
    }

    private function resolveAccountByCandidates(array $directCandidates, array $rootCandidates, callable $descendantFilter): ?int
    {
        foreach ($directCandidates as $candidate) {
            $candidate = trim((string) $candidate);
            if ($candidate === '') {
                continue;
            }

            $accountId = Account::query()->where('name', $candidate)->value('id');
            if ($accountId) {
                return (int) $accountId;
            }
        }

        foreach ($rootCandidates as $rootName) {
            $root = Account::query()
                ->with('childrenRecursive')
                ->where('name', $rootName)
                ->first();

            if (!$root) {
                continue;
            }

            $relatedIds = array_merge([$root->id], $root->getAllChildrenIds());
            $query = Account::query()->whereIn('id', $relatedIds);
            $descendantFilter($query);

            $accountId = $query->orderBy('id')->value('id');
            if ($accountId) {
                return (int) $accountId;
            }
        }

        // Fallback: no root restriction
        if (!empty($directCandidates)) {
            $query = Account::query()->whereDoesntHave('children');
            $descendantFilter($query);
            $accountId = $query->orderBy('id')->value('id');
            if ($accountId) {
                return (int) $accountId;
            }
        }

        return null;
    }

    private function appendLine(array &$lines, int $accountId, string $type, float $amount, string $statement, ?int $costCenterId = null): void
    {
        $amount = $this->roundMoney($amount);
        if ($amount <= 0) {
            return;
        }

        $lines[] = [
            'account_id'     => $accountId,
            'account_type'   => $type,
            'debtor_value'   => $type === 'debtor'   ? $amount : 0,
            'creditor_value' => $type === 'creditor' ? $amount : 0,
            'statement'      => $statement,
            'cost_center_id' => $costCenterId,
        ];
    }

    private function roundMoney(float $amount): float
    {
        return round($amount, 2);
    }
}
