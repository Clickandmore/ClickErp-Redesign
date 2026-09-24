<?php

namespace App\Services\ingaz;

use App\Http\Controllers\ingaz\InvoiceController;
use App\Models\Contract;
use App\Models\ContractReturnRequest;
use App\Models\TravelHistory;
use App\Support\AutomationSettings;
use App\Support\AutomaticJournalEntries;
use App\Support\RecruitmentRevenueRecognition;
use App\Support\ShelterAccountingSettings;
use App\Support\PostArrivalRefundSettings;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\Constraint;
use Nami\Finance\Services\ConstraintService;
use Nami\Finance\Services\YearService;

/**
 * الإرجاع داخل الضمان (القيد 9 في دليل القيود) بعد فصله إلى ثلاثة مستندات:
 *
 *  1. إشعار دائن بكامل قيمة الفاتورة (InvoiceController::createSystemCreditNoteForRecruitmentRefund):
 *     مدين مردودات مبيعات استقدام (الجنسية) + الضريبة / دائن العميل. يُرسل لزاتكا كإشعار دائن.
 *     في وضع «عند الفاتورة» يُعكس معه قيد فاتورة المورد الخارجي.
 *  2. قيد تسويات الإرجاع: خصم مدة البقاء (مدين العميل / دائن إيراد استرداد مبيعات).
 *  3. قيد إيواء S1 عبر ShelterCostLedgerService: مدين أصل الإيواء / دائن مردودات تكلفة
 *     بتكلفة المكتب الخارجي (الوضع الافتراضي فقط؛ في وضع «عند الفاتورة» تُعكس فاتورة المورد بدلاً منه).
 *
 * الأرقام الثلاثة تُحفظ على طلب الإرجاع (credit_note_id, return_constraint_id, shelter_constraint_id)
 * فلا تتكرر عند إعادة المعالجة.
 */
class RecruitmentContractWarrantyReturnService
{
    private const USD_TO_SAR_RATE = 3.75;

    public function __construct(
        protected ConstraintService $constraintService,
        protected YearService $yearService,
        protected RecruitmentTripAccountingService $tripAccountingService,
        protected ShelterCostLedgerService $shelterLedger
    ) {
    }

    public static function statementFor(Contract $contract): string
    {
        return 'رجوع عاملة داخل الضمان — عقد استقدام #' . $contract->id;
    }

    public static function shelterStatementFor(Contract $contract): string
    {
        return 'إيواء — استلام عاملة من إرجاع داخل الضمان — عقد #' . $contract->id;
    }

    public static function externalOfficeReversalStatement(Contract $contract): string
    {
        return 'استرداد فاتورة المورد الخارجي - رجوع عاملة داخل الضمان - عقد استقدام #' . $contract->id;
    }

    /**
     * يُستدعى عند الموافقة على طلب الإرجاع. يعيد قيد التسويات إن وُجد، وإلا قيد إشعار الدائن.
     */
    public function record(Contract $contract, ContractReturnRequest $returnRequest): ?Constraint
    {
        // في الوضع المعتمد، الموافقة حدث تشغيلي فقط. المحاسب ينشئ المستند المالي
        // لاحقاً من شاشة «الاسترداد بعد الوصول» ولا تُكتب هنا أي قيود.
        if (PostArrivalRefundSettings::manualScreen()) {
            return null;
        }

        if (!AutomaticJournalEntries::enabled()) {
            return null;
        }

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

        $totalDue   = $this->roundMoney((float) $invoice->total_due);
        $vatAmount  = $this->roundMoney((float) $invoice->vat_amount);
        $netInvoice = $this->roundMoney($totalDue - $vatAmount);
        $stayAmount = $this->calculateStayAmount($contract, $returnDate, $netInvoice);
        $officeCost = $this->resolveExternalOfficeCostAmount($contract);
        $onInvoiceMode = RecruitmentRevenueRecognition::invoiceRecognizesOnInvoice($invoice);
        $hasTracking = Schema::hasColumn('contract_return_requests', 'credit_note_id');

        // 1. إشعار دائن بكامل قيمة الفاتورة (يعكس المبيعات أو الالتزام + الضريبة على العميل).
        $creditNote = null;
        if (!$hasTracking || !$returnRequest->credit_note_id) {
            $creditNote = app(InvoiceController::class)->createSystemCreditNoteForRecruitmentRefund(
                $invoice,
                $returnDate,
                'إرجاع داخل الضمان — عقد استقدام #' . $contract->id . ': ' . trim((string) ($returnRequest->admin_notes ?: $returnRequest->notes ?: 'إرجاع العاملة')),
                $this->constraintService,
                $this->yearService
            );
            if ($creditNote && $hasTracking) {
                $returnRequest->forceFill(['credit_note_id' => $creditNote->id])->saveQuietly();
            }
        }

        // 2. قيد تسويات الإرجاع: خصم مدة البقاء.
        $adjustments = null;
        if (!$hasTracking || !$returnRequest->return_constraint_id) {
            $adjustments = $this->recordStayDeduction($contract, $returnDate, $activeYear, $clientAccountId, $stayAmount);
            if ($adjustments && $hasTracking) {
                $returnRequest->forceFill(['return_constraint_id' => $adjustments->id])->saveQuietly();
            }
        }

        // 3. تكلفة المكتب الخارجي: أصل إيواء في الوضع الافتراضي، أو عكس فاتورة المورد في وضع «عند الفاتورة».
        if ($onInvoiceMode) {
            if ($invoice->external_office_cost_constraint_id) {
                $this->tripAccountingService->reverseExternalOfficeCostEntries(
                    $contract,
                    $invoice,
                    $returnDate,
                    self::externalOfficeReversalStatement($contract)
                );
            }
        } elseif ($officeCost > 0 && (!$hasTracking || !$returnRequest->shelter_constraint_id)) {
            $shelter = $this->shelterLedger->capitalizeOfficeCost(
                $contract,
                $officeCost,
                $returnDate,
                $returnRequest,
                self::shelterStatementFor($contract)
            );
            if (($shelter['constraint'] ?? null) && $hasTracking) {
                $returnRequest->forceFill(['shelter_constraint_id' => $shelter['constraint']->id])->saveQuietly();
            }
        }

        if ($adjustments) {
            return $adjustments;
        }

        return $creditNote?->constraint_id ? Constraint::query()->find($creditNote->constraint_id) : null;
    }

    private function recordStayDeduction(Contract $contract, string $returnDate, $activeYear, int $clientAccountId, float $stayAmount): ?Constraint
    {
        if ($stayAmount <= 0) {
            return null;
        }

        $salesRecoveryAccountId = $this->resolveSalesRecoveryAccountId();
        if (!$salesRecoveryAccountId) {
            throw new \RuntimeException('لا يوجد حساب إيراد استرداد مبيعات في النظام.');
        }

        $statement = self::statementFor($contract) . ' — خصم مدة البقاء';
        $lines = [];
        $this->appendLine($lines, $clientAccountId, 'debtor', $stayAmount, $statement);
        $this->appendLine($lines, $salesRecoveryAccountId, 'creditor', $stayAmount, $statement);

        return $this->storeLines($lines, $statement, $returnDate, $activeYear);
    }

    private function storeLines(array $lines, string $statement, string $returnDate, $activeYear): ?Constraint
    {
        if (empty($lines)) {
            return null;
        }

        $value = $this->roundMoney(array_sum(array_column($lines, 'debtor_value')));
        $data = [
            'code'                 => $this->constraintService->lastCode(),
            'date'                 => $returnDate,
            'statement'            => $statement,
            'account_id'           => array_column($lines, 'account_id'),
            'account_type'         => array_column($lines, 'account_type'),
            'debtor_value'         => array_column($lines, 'debtor_value'),
            'creditor_value'       => array_column($lines, 'creditor_value'),
            'statement_account'    => array_column($lines, 'statement'),
            'cost_center_required' => 'off',
            'cost_center_id'       => array_column($lines, 'cost_center_id'),
            'value'                => $value,
        ];

        $constraint = $this->constraintService->storeConstraint($data, $activeYear);
        $this->constraintService->storeConstraintDetails($data['account_id'], $constraint->id, $data);
        $this->constraintService->storeFiles($constraint, $data);

        return $constraint;
    }

    public function calculateStayAmount(Contract $contract, string $returnDate, float $netInvoice): float
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
        // travel_histories يربط الرحلة بالعقد عبر order_id (انظر Contract::travelHistory).
        $travelHistory = TravelHistory::query()
            ->where('order_id', $contract->id)
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

    public function resolveExternalOfficeCostAmount(Contract $contract): float
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

    /**
     * إيراد استرداد المبيعات (4217): من إعدادات الحسابات أولاً ثم بالاسم.
     */
    private function resolveSalesRecoveryAccountId(): ?int
    {
        $configured = ShelterAccountingSettings::salesRecoveryIncomeAccountId();
        if ($configured) {
            return $configured;
        }

        foreach (['إيراد استرداد مبيعات', 'إيراد استرداد', 'إيراد الاسترداد'] as $candidate) {
            $accountId = Account::query()->where('name', $candidate)->value('id');
            if ($accountId) {
                return (int) $accountId;
            }
        }

        $accountId = Account::query()
            ->whereDoesntHave('children')
            ->where('name', 'like', '%إيراد استرداد%')
            ->orderBy('id')
            ->value('id');

        return $accountId ? (int) $accountId : null;
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
            'debtor_value'   => $type === 'debtor' ? $amount : 0,
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
