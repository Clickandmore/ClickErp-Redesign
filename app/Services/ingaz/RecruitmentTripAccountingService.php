<?php

namespace App\Services\ingaz;

use App\Models\Contract;
use App\Models\TravelHistory;
use App\Models\ingaz\Invoice;
use App\Models\office\Office;
use App\Services\office\OfficeFinanceAccountService;
use App\Support\AutomaticJournalEntries;
use App\Support\RecruitmentRevenueRecognition;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Nami\Finance\Models\ConfirmSetting;
use Nami\Finance\Enums\CostCenterTypeEnum;
use Nami\Finance\Enums\PurchaseItemStatusEnum;
use Nami\Finance\Enums\PurchaseTypeEnum;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\Constraint;
use Nami\Finance\Models\ConstraintDetails;
use Nami\Finance\Models\CostCenter;
use Nami\Finance\Models\Item;
use Nami\Finance\Models\Purchase;
use Nami\Finance\Models\PurchaseItem;
use Nami\Finance\Models\Supplier;
use Nami\Finance\Services\ConstraintService;
use Nami\Finance\Services\YearService;

class RecruitmentTripAccountingService
{
    private const USD_TO_SAR_RATE = 3.75;

    public function __construct(
        protected ConstraintService $constraintService,
        protected YearService $yearService,
        protected OfficeFinanceAccountService $officeFinanceAccountService
    ) {
    }

    public function recordCompletionEntries(Contract $contract, ?TravelHistory $travelHistory = null): array
    {
        if (!AutomaticJournalEntries::enabled()) {
            return [
                'skipped_reason' => 'automatic_entries_disabled',
                'revenue_constraint_id' => null,
                'external_office_cost_constraint_id' => null,
                'external_office_cost_constraint_code' => null,
                'created_revenue_constraint' => false,
                'created_external_office_cost_constraint' => false,
                'supplier_purchase_id' => null,
                'created_supplier_purchase' => false,
                'revenue_skipped_reason' => null,
                'warnings' => [],
            ];
        }

        $contract->loadMissing([
            'user',
            'invoice',
            'maid.nationality',
            'requiredMaidNationality',
            'externalOfficeDispatch',
            'externalOfficeDispatch.recruitmentOffice',
            'info.manualMaidOffice',
        ]);

        /** @var Invoice|null $invoice */
        $invoice = $contract->invoice;

        if (!$invoice) {
            return [
                'skipped_reason' => 'missing_invoice',
                'revenue_constraint_id' => null,
                'external_office_cost_constraint_id' => null,
                'external_office_cost_constraint_code' => null,
                'created_revenue_constraint' => false,
                'created_external_office_cost_constraint' => false,
                'supplier_purchase_id' => null,
                'created_supplier_purchase' => false,
                'revenue_skipped_reason' => null,
                'warnings' => [],
            ];
        }

        $date = $this->resolveEntryDate($travelHistory);
        $activeYear = $this->yearService->checkDateRange($date);

        if (!$activeYear) {
            throw new \RuntimeException('لا يوجد سنة مالية نشطة بتاريخ الوصول المحدد.');
        }

        $nationalityName = $this->resolveNationalityName($contract);
        if ($nationalityName === '') {
            throw new \RuntimeException('لا يمكن إنشاء القيود المحاسبية لأن جنسية العقد غير محددة.');
        }

        $result = [
            'skipped_reason' => null,
            'revenue_constraint_id' => $invoice->arrival_revenue_constraint_id ? (int) $invoice->arrival_revenue_constraint_id : null,
            'external_office_cost_constraint_id' => $invoice->external_office_cost_constraint_id ? (int) $invoice->external_office_cost_constraint_id : null,
            'external_office_cost_constraint_code' => null,
            'created_revenue_constraint' => false,
            'created_external_office_cost_constraint' => false,
            'supplier_purchase_id' => $invoice->external_office_purchase_id ? (int) $invoice->external_office_purchase_id : null,
            'supplier_purchase_code' => null,
            'created_supplier_purchase' => false,
            'revenue_skipped_reason' => null,
            'warnings' => [],
        ];

        if ($invoice->arrival_revenue_constraint_id) {
            $this->syncArrivalRevenueConstraintCostCenter(
                (int) $invoice->arrival_revenue_constraint_id
            );
        }

        if ($invoice->external_office_cost_constraint_id) {
            $this->syncConstraintStatement(
                (int) $invoice->external_office_cost_constraint_id,
                $this->buildExternalOfficeCostStatement($contract, $invoice)
            );

            $this->syncExternalOfficeCostConstraintCostCenter(
                (int) $invoice->external_office_cost_constraint_id,
                $contract,
                $nationalityName
            );
        }

        if (RecruitmentRevenueRecognition::invoiceRecognizesOnInvoice($invoice)) {
            // الإيراد أُثبت في قيد الفاتورة نفسه (إعداد «عند إنشاء الفاتورة»)، فلا قيد تحويل عند الوصول.
            $result['revenue_skipped_reason'] = 'revenue_recognized_on_invoice';
        } elseif (!$invoice->arrival_revenue_constraint_id) {
            $liabilityAccountId = $this->resolveLiabilityAccountId($nationalityName);

            if (!$liabilityAccountId) {
                $result['revenue_skipped_reason'] = 'missing_liability_account';
                $result['warnings'][] = 'قيد إيراد الوصول لم يُنشأ: حساب الالتزام غير موجود، ويُرجى مراجعة إثبات الإيراد السابق.';
            } elseif (!$this->hasInvoiceLiabilityConstraint($contract, $invoice, $liabilityAccountId)) {
                $result['revenue_skipped_reason'] = 'missing_liability_constraint';
                $result['warnings'][] = 'قيد إيراد الوصول لم يُنشأ: قيد الالتزام السابق غير موجود، ويُرجى مراجعة إثبات الإيراد السابق.';
            } else {
                $revenueConstraint = $this->findExistingConstraint(
                    $this->buildArrivalRevenueStatement($contract, $invoice),
                    $date,
                    $this->roundMoney($this->buildRevenueBreakdown($invoice)['net_revenue_total'])
                );

                if (!$revenueConstraint) {
                    $revenueConstraint = $this->createArrivalRevenueConstraint(
                        $contract,
                        $invoice,
                        $nationalityName,
                        $liabilityAccountId,
                        $date,
                        $activeYear
                    );
                    $result['created_revenue_constraint'] = (bool) $revenueConstraint;
                }

                if ($revenueConstraint) {
                    $this->syncArrivalRevenueConstraintCostCenter($revenueConstraint->id);
                    $invoice->arrival_revenue_constraint_id = $revenueConstraint->id;
                    $result['revenue_constraint_id'] = (int) $revenueConstraint->id;
                }
            }
        }

        if (!$invoice->external_office_cost_constraint_id) {
            $officeCostAmount = $this->resolveExternalOfficeCostAmount($contract);
            if ($officeCostAmount > 0) {
                $officeCostStatement = $this->buildExternalOfficeCostStatement($contract, $invoice);
                $costCenterId = $this->ensureRecruitmentContractCostCenter($contract, $nationalityName)->id;

                $officeCostConstraint = $this->findExistingConstraint(
                    $officeCostStatement,
                    $date,
                    $officeCostAmount
                );

                if (!$officeCostConstraint) {
                    $officeCostConstraint = $this->createExternalOfficeCostConstraint(
                        $contract,
                        $invoice,
                        $nationalityName,
                        $costCenterId,
                        $date,
                        $activeYear,
                        $officeCostAmount,
                        $officeCostStatement
                    );
                    $result['created_external_office_cost_constraint'] = (bool) $officeCostConstraint;
                }

                if ($officeCostConstraint) {
                    $this->syncExternalOfficeCostConstraintCostCenter(
                        $officeCostConstraint->id,
                        $contract,
                        $nationalityName
                    );
                    $invoice->external_office_cost_constraint_id = $officeCostConstraint->id;
                    $result['external_office_cost_constraint_id'] = (int) $officeCostConstraint->id;
                }
            } else {
                $result['warnings'][] = 'لم يُنشأ قيد تكلفة مكتب خارجي لأن التكلفة المسجلة على الإرسالية صفر.';
            }
        }

        if ($result['external_office_cost_constraint_id']) {
            $result['external_office_cost_constraint_code'] = Constraint::query()
                ->whereKey($result['external_office_cost_constraint_id'])
                ->value('code');

            [$supplierPurchase, $createdSupplierPurchase] = $this->ensureExternalOfficePurchase(
                $contract,
                $invoice,
                $nationalityName,
                (int) $result['external_office_cost_constraint_id'],
                $date
            );

            $result['supplier_purchase_id'] = (int) $supplierPurchase->id;
            $result['supplier_purchase_code'] = $supplierPurchase->code;
            $result['created_supplier_purchase'] = $createdSupplierPurchase;
        }

        if ($invoice->isDirty([
            'arrival_revenue_constraint_id',
            'external_office_cost_constraint_id',
            'external_office_purchase_id',
        ])) {
            $invoice->save();
        }

        return $result;
    }

    /**
     * يعكس قيد فاتورة المورد الخارجي بقيد مقابل (مدين المكتب الخارجي / دائن تكلفة المبيعات)
     * بنفس الحسابات والمبالغ المرحّلة أصلاً، فتعود فاتورة المكتب إلى صفر.
     * يُستخدم عند استرداد العقد أو إرجاع العاملة في وضع «إثبات الإيراد عند إنشاء الفاتورة».
     */
    public function reverseExternalOfficeCostEntries(
        Contract $contract,
        Invoice $invoice,
        string $date,
        string $statement
    ): ?Constraint {
        if (!$invoice->external_office_cost_constraint_id) {
            return null;
        }

        $hasReversalColumn = Schema::hasColumn('invoices', 'external_office_cost_reversal_constraint_id');
        if ($hasReversalColumn && $invoice->external_office_cost_reversal_constraint_id) {
            return Constraint::query()->find($invoice->external_office_cost_reversal_constraint_id);
        }

        $activeYear = $this->yearService->checkDateRange($date);
        if (!$activeYear) {
            throw new \RuntimeException('لا يوجد سنة مالية نشطة لتاريخ عكس فاتورة المورد الخارجي.');
        }

        $lines = $this->buildExternalOfficeCostReversalLines($invoice, $statement);
        if (empty($lines)) {
            return null;
        }

        $value = $this->roundMoney(array_sum(array_column($lines, 'debtor_value')));
        $constraint = $this->findExistingConstraint($statement, $date, $value)
            ?: $this->storeConstraint($lines, $statement, $date, $value, $activeYear);

        if ($constraint && $hasReversalColumn) {
            $invoice->external_office_cost_reversal_constraint_id = $constraint->id;
            $invoice->save();
        }

        return $constraint;
    }

    /**
     * أسطر عكس قيد المورد الخارجي (للمعاينة أو الترحيل): كل طرف يُقلب إلى الطرف المقابل.
     */
    public function buildExternalOfficeCostReversalLines(Invoice $invoice, string $statement): array
    {
        if (!$invoice->external_office_cost_constraint_id) {
            return [];
        }

        return ConstraintDetails::query()
            ->where('constraint_id', $invoice->external_office_cost_constraint_id)
            ->orderBy('id')
            ->get()
            ->map(function (ConstraintDetails $detail) use ($statement) {
                $debtor = $this->roundMoney((float) $detail->creditor_value);
                $creditor = $this->roundMoney((float) $detail->debtor_value);

                return [
                    'account_id' => (int) $detail->account_id,
                    'account_type' => $debtor > 0 ? 'debtor' : 'creditor',
                    'debtor_value' => $debtor,
                    'creditor_value' => $creditor,
                    'statement' => $statement,
                    'cost_center_id' => $detail->cost_center_id,
                ];
            })
            ->filter(fn (array $line) => $line['debtor_value'] > 0 || $line['creditor_value'] > 0)
            // الطرف المدين (المكتب الخارجي) أولاً ثم الدائن (تكلفة المبيعات) كما تُعرض القيود.
            ->sortBy(fn (array $line) => $line['account_type'] === 'debtor' ? 0 : 1)
            ->values()
            ->all();
    }

    private function createArrivalRevenueConstraint(
        Contract $contract,
        Invoice $invoice,
        string $nationalityName,
        int $liabilityAccountId,
        string $date,
        $activeYear
    ): ?Constraint {
        $breakdown = $this->buildRevenueBreakdown($invoice);

        if ($breakdown['net_revenue_total'] <= 0) {
            return null;
        }

        $salesAccountId = $this->resolveSalesAccountId($nationalityName);

        if (!$salesAccountId) {
            throw new \RuntimeException('لا يوجد حساب مبيعات مرتبط بجنسية العقد: ' . $nationalityName);
        }

        $lines = [];

        if ($breakdown['direct_amount'] > 0) {
            $this->appendTransferPair(
                $lines,
                $liabilityAccountId,
                $salesAccountId,
                $breakdown['direct_amount'],
                $this->buildRevenueDetailStatement($contract, $invoice, 'تكاليف الاستقدام المباشر')
            );
        }

        if ($breakdown['other_amount'] > 0) {
            $this->appendTransferPair(
                $lines,
                $liabilityAccountId,
                $salesAccountId,
                $breakdown['other_amount'],
                $this->buildRevenueDetailStatement($contract, $invoice, 'تكاليف الاستقدام الخارجية')
            );
        }

        return $this->storeConstraint(
            $lines,
            $this->buildArrivalRevenueStatement($contract, $invoice),
            $date,
            $breakdown['net_revenue_total'],
            $activeYear
        );
    }

    private function createExternalOfficeCostConstraint(
        Contract $contract,
        Invoice $invoice,
        string $nationalityName,
        int $costCenterId,
        string $date,
        $activeYear,
        float $amount,
        string $statement
    ): ?Constraint {
        if ($amount <= 0) {
            return null;
        }

        $costOfSalesAccountId = $this->resolveCostOfSalesAccountId($nationalityName);
        if (!$costOfSalesAccountId) {
            throw new \RuntimeException('لا يوجد حساب تكلفة مبيعات مرتبط بجنسية العقد: ' . $nationalityName);
        }

        $externalOfficeAccountId = $this->resolveExternalOfficeAccountId($contract);

        $lines = [
            [
                'account_id' => $costOfSalesAccountId,
                'account_type' => 'debtor',
                'debtor_value' => $amount,
                'creditor_value' => 0,
                'statement' => $statement,
                'cost_center_id' => $costCenterId,
            ],
            [
                'account_id' => $externalOfficeAccountId,
                'account_type' => 'creditor',
                'debtor_value' => 0,
                'creditor_value' => $amount,
                'statement' => $statement,
                'cost_center_id' => null,
            ],
        ];

        return $this->storeConstraint($lines, $statement, $date, $amount, $activeYear);
    }

    private function ensureExternalOfficePurchase(
        Contract $contract,
        Invoice $invoice,
        string $nationalityName,
        int $constraintId,
        string $date
    ): array {
        $office = $contract->resolveExternalOfficeDispatchOffice();
        if (!$office?->id) {
            throw new \RuntimeException('لا يمكن إنشاء فاتورة المورد لأن المكتب الخارجي غير محدد.');
        }

        $office = Office::query()
            ->with(['translations', 'account'])
            ->findOrFail($office->id);
        $officeAccount = $this->officeFinanceAccountService->ensureForOffice($office);
        $officeName = trim((string) ($office->title ?: $officeAccount->name));

        $supplier = Supplier::query()
            ->lockForUpdate()
            ->where('account_id', $officeAccount->id)
            ->first();

        if (!$supplier) {
            $supplier = Supplier::query()
                ->lockForUpdate()
                ->where('name', $officeName)
                ->first();
        }

        if ($supplier) {
            $supplier->forceFill([
                'name' => $officeName !== '' ? $officeName : $supplier->name,
                'code' => (string) $officeAccount->code,
                'account_id' => $officeAccount->id,
            ])->save();
        } else {
            $supplier = Supplier::query()->create([
                'name' => $officeName !== '' ? $officeName : ('مكتب خارجي #' . $office->id),
                'code' => (string) $officeAccount->code,
                'address' => '-',
                'phone' => '-',
                'email' => 'no email',
                'account_id' => $officeAccount->id,
                'created_by_id' => Auth::guard('admin')->id() ?: Auth::id() ?: 1,
            ]);
        }

        ConstraintDetails::query()
            ->where('constraint_id', $constraintId)
            ->where('type', 'creditor')
            ->update(['account_id' => $officeAccount->id]);

        $existingPurchase = null;
        if ($invoice->external_office_purchase_id) {
            $existingPurchase = Purchase::query()->find($invoice->external_office_purchase_id);
        }

        $existingPurchase ??= Purchase::query()
            ->where('constraint_id', $constraintId)
            ->first();

        if ($existingPurchase) {
            $existingPurchase->forceFill([
                'supplier_id' => $supplier->id,
                'credit_supplier_id' => $officeAccount->id,
            ])->save();
            $invoice->external_office_purchase_id = $existingPurchase->id;

            return [$existingPurchase, false];
        }

        $amount = $this->resolveExternalOfficeCostAmount($contract);
        $statement = $this->buildExternalOfficeCostStatement($contract, $invoice);
        $purchase = Purchase::query()->create([
            'created_by_id' => Auth::guard('admin')->id() ?: Auth::id() ?: 1,
            // ترقيم فواتير المشتريات يبقى متصلاً على مستوى الشركة رغم عزل الفروع.
            'code' => ((int) Purchase::query()->acrossBranches()->lockForUpdate()->max('code')) + 1,
            'supplier_id' => $supplier->id,
            'credit_supplier_id' => $officeAccount->id,
            'supplier_bill_number' => (string) ($contract->externalOfficeDispatch?->contract_number ?: ('CLOSE-' . $contract->id)),
            'date' => $date,
            'notes' => $statement,
            'total_item' => $amount,
            'tax_per' => 0,
            'tax_value' => 0,
            'discount' => 0,
            'final_total' => $amount,
            'paid_amount' => 0,
            'is_confirmed' => true,
            'constraint_id' => $constraintId,
            'type' => PurchaseTypeEnum::Purchase->value,
            'payment_method' => 'deferred',
            'file' => 'no_file',
        ]);

        $costAccountId = $this->resolveCostOfSalesAccountId($nationalityName);
        $item = $this->ensureExternalOfficeCostItem($costAccountId);

        PurchaseItem::query()->create([
            'purchase_id' => $purchase->id,
            'item_id' => $item->id,
            'price' => $amount,
            'tax_per' => 0,
            'tax_value' => 0,
            'amount' => 1,
            'discount_per' => 0,
            'discount_value' => 0,
            'notes' => $statement,
            'final_total' => $amount,
            'status' => PurchaseItemStatusEnum::Confirmed->value,
            'stock_id' => null,
        ]);

        $invoice->external_office_purchase_id = $purchase->id;

        return [$purchase, true];
    }

    private function ensureExternalOfficeCostItem(?int $costAccountId): Item
    {
        if (!$costAccountId) {
            throw new \RuntimeException('لا يمكن إنشاء بند فاتورة المورد لأن حساب تكلفة الجنسية غير موجود.');
        }

        $item = Item::query()
            ->where('name', 'تكلفة استقدام مكتب خارجي')
            ->first();

        if ($item) {
            return $item;
        }

        $template = Item::query()
            ->where('type', 'service')
            ->first();

        if (!$template) {
            throw new \RuntimeException('لا يوجد بند خدمة يمكن استخدامه لإنشاء فاتورة المورد.');
        }

        return Item::query()->create([
            'name' => 'تكلفة استقدام مكتب خارجي',
            'main_category_id' => $template->main_category_id,
            'sub_category_id' => $template->sub_category_id,
            'type' => 'service',
            'code_string' => 'EXT-OFFICE-COST',
            'code' => ((int) Item::query()->lockForUpdate()->max('code')) + 1,
            'min_order' => 0,
            'sale_unit_id' => $template->sale_unit_id,
            'sale_price' => 0,
            'purchase_unit_id' => $template->purchase_unit_id,
            'purchase_price' => 0,
            'details' => 'تكلفة استقدام مكتب خارجي حسب جنسية العقد',
            'sale_account_id' => $costAccountId,
            'stock_account_id' => $costAccountId,
            'stock_tracking' => false,
            'show_pos' => false,
            'show_website' => false,
        ]);
    }

    private function syncArrivalRevenueConstraintCostCenter(int $constraintId): void
    {
        \Nami\Finance\Models\ConstraintDetails::query()
            ->where('constraint_id', $constraintId)
            ->update(['cost_center_id' => null]);
    }

    private function syncExternalOfficeCostConstraintCostCenter(
        int $constraintId,
        Contract $contract,
        string $nationalityName
    ): void {
        $costOfSalesAccountId = $this->resolveCostOfSalesAccountId($nationalityName);
        if (!$costOfSalesAccountId) {
            throw new \RuntimeException('لا يوجد حساب تكلفة مبيعات مرتبط بجنسية العقد: ' . $nationalityName);
        }

        $costCenterId = $this->ensureRecruitmentContractCostCenter($contract, $nationalityName)->id;

        \Nami\Finance\Models\ConstraintDetails::query()
            ->where('constraint_id', $constraintId)
            ->update(['cost_center_id' => null]);

        \Nami\Finance\Models\ConstraintDetails::query()
            ->where('constraint_id', $constraintId)
            ->where('account_id', $costOfSalesAccountId)
            ->update(['cost_center_id' => $costCenterId]);
    }

    private function syncConstraintStatement(int $constraintId, string $statement): void
    {
        Constraint::query()
            ->whereKey($constraintId)
            ->update(['statement' => $statement]);

        \Nami\Finance\Models\ConstraintDetails::query()
            ->where('constraint_id', $constraintId)
            ->update(['statement' => $statement]);
    }

    private function storeConstraint(array $lines, string $statement, string $date, float $value, $activeYear): ?Constraint
    {
        if (empty($lines) || $this->roundMoney($value) <= 0) {
            return null;
        }

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
            'value' => $this->roundMoney($value),
        ];

        $constraint = $this->constraintService->storeConstraint($data, $activeYear);
        $this->constraintService->storeConstraintDetails($data['account_id'], $constraint->id, $data);
        $this->constraintService->storeFiles($constraint, $data);

        return $constraint;
    }

    private function appendTransferPair(
        array &$lines,
        int $debitAccountId,
        int $creditAccountId,
        float $amount,
        string $statement,
        ?int $costCenterId = null
    ): void {
        $amount = $this->roundMoney($amount);

        if ($amount <= 0) {
            return;
        }

        $lines[] = [
            'account_id' => $debitAccountId,
            'account_type' => 'debtor',
            'debtor_value' => $amount,
            'creditor_value' => 0,
            'statement' => $statement,
            'cost_center_id' => $costCenterId,
        ];

        $lines[] = [
            'account_id' => $creditAccountId,
            'account_type' => 'creditor',
            'debtor_value' => 0,
            'creditor_value' => $amount,
            'statement' => $statement,
            'cost_center_id' => $costCenterId,
        ];
    }

    private function buildRevenueBreakdown(Invoice $invoice): array
    {
        $directGross = $this->roundMoney((float) $invoice->direct_recruitment_cost * (int) $invoice->direct_recruitment_count);
        $otherGross = $this->roundMoney(
            ((float) $invoice->external_recruitment_cost * (int) $invoice->external_recruitment_count)
            + ((float) $invoice->government_cost * (int) $invoice->government_cost_count)
            + ((float) $invoice->internal_ticket_cost * (int) $invoice->internal_ticket_count)
        );
        $vatAmount = $this->roundMoney((float) $invoice->vat_amount);
        $totalDue = $this->roundMoney((float) $invoice->total_due);
        $serviceGross = $this->roundMoney($directGross + $otherGross);
        $serviceNet = max(0, $this->roundMoney($totalDue - $vatAmount));

        if ($serviceGross <= 0) {
            return [
                'direct_amount' => 0.0,
                'other_amount' => 0.0,
                'net_revenue_total' => $serviceNet,
            ];
        }

        if ($directGross <= 0) {
            return [
                'direct_amount' => 0.0,
                'other_amount' => $serviceNet,
                'net_revenue_total' => $serviceNet,
            ];
        }

        if ($otherGross <= 0) {
            return [
                'direct_amount' => $serviceNet,
                'other_amount' => 0.0,
                'net_revenue_total' => $serviceNet,
            ];
        }

        $directAmount = $this->roundMoney($serviceNet * ($directGross / $serviceGross));

        return [
            'direct_amount' => $directAmount,
            'other_amount' => $this->roundMoney($serviceNet - $directAmount),
            'net_revenue_total' => $serviceNet,
        ];
    }

    private function resolveExternalOfficeCostAmount(Contract $contract): float
    {
        $contract->loadMissing('externalOfficeDispatch');

        $usdAmount = $this->roundMoney((float) ($contract->externalOfficeDispatch?->office_contract_cost_usd ?? 0));
        return $this->roundMoney($usdAmount * self::USD_TO_SAR_RATE);
    }

    private function resolveExternalOfficeAccountId(Contract $contract): int
    {
        $resolvedOffice = $contract->resolveExternalOfficeDispatchOffice();
        if (!$resolvedOffice?->id) {
            throw new \RuntimeException('لا يمكن إنشاء قيد المكتب الخارجي لأن المكتب غير محدد على العقد.');
        }

        $office = Office::query()
            ->with(['translations', 'account'])
            ->find($resolvedOffice->id);

        if (!$office) {
            throw new \RuntimeException('المكتب الخارجي المرتبط بالعقد غير موجود.');
        }

        $account = $this->officeFinanceAccountService->ensureForOffice($office);

        return (int) $account->id;
    }

    public function resolveLiabilityAccountId(string $nationalityName, ?int $nationalityId = null): ?int
    {
        return $this->resolveConfiguredNationalityAccountId($nationalityId, 'contract_liability')
            ?? $this->resolveAccountByCandidates(
            [
                'التزامات عقود (' . $nationalityName . ')',
                'التزامات عقود ' . $nationalityName,
                'إلتزامات عقود ' . $nationalityName,
                'التزامات عقود الاستقدام ' . $nationalityName,
                'إلتزامات عقود الاستقدام ' . $nationalityName,
            ],
            ['التزامات عقود الاستقدام', 'التزامات عقود'],
            fn ($query) => $query
                ->where('name', 'like', '%التزامات عقود%')
                ->where('name', 'like', '%' . $nationalityName . '%')
        );
    }

    private function hasInvoiceLiabilityConstraint(
        Contract $contract,
        Invoice $invoice,
        int $liabilityAccountId
    ): bool {
        $invoiceReference = '%الفاتورة #' . $invoice->id . '%';
        $contractReference = '%العقد #' . $contract->id . '%';

        return ConstraintDetails::query()
            ->where('account_id', $liabilityAccountId)
            ->where('creditor_value', '>', 0)
            ->where(function ($query) use ($invoiceReference, $contractReference) {
                $query->where('statement', 'like', $invoiceReference)
                    ->orWhere(function ($query) use ($contractReference) {
                        $query->where('statement', 'like', '%فاتورة%')
                            ->where('statement', 'like', $contractReference);
                    })
                    ->orWhereHas('constraint', function ($query) use ($invoiceReference, $contractReference) {
                        $query->where('statement', 'like', $invoiceReference)
                            ->orWhere(function ($query) use ($contractReference) {
                                $query->where('statement', 'like', '%فاتورة%')
                                    ->where('statement', 'like', $contractReference);
                            });
                    });
            })
            ->exists();
    }

    public function resolveSalesAccountId(string $nationalityName, ?int $nationalityId = null): ?int
    {
        return $this->resolveConfiguredNationalityAccountId($nationalityId, 'recruitment_sales')
            ?? $this->resolveAccountByCandidates(
            [
                'مبيعات (' . $nationalityName . ')',
                'مبيعات ' . $nationalityName,
            ],
            ['مبيعات الاستقدام', 'مبيعات'],
            fn ($query) => $query
                ->where(function ($nameQuery) {
                    $nameQuery->where('name', 'like', 'مبيعات (%')
                        ->orWhere('name', 'like', 'مبيعات %');
                })
                ->where('name', 'like', '%' . $nationalityName . '%')
        );
    }

    public function resolveCostOfSalesAccountId(string $nationalityName, ?int $nationalityId = null): ?int
    {
        return $this->resolveConfiguredNationalityAccountId($nationalityId, 'recruitment_cost')
            ?? $this->resolveAccountByCandidates(
            [
                'تكاليف مبيعات (' . $nationalityName . ')',
                'تكاليف مبيعات ' . $nationalityName,
                'تكلفة مبيعات ' . $nationalityName,
            ],
            ['تكاليف مبيعات الاستقدام', 'تكاليف مبيعات'],
            fn ($query) => $query
                ->where(function ($nameQuery) {
                    $nameQuery->where('name', 'like', 'تكاليف مبيعات (%')
                        ->orWhere('name', 'like', 'تكاليف مبيعات %')
                        ->orWhere('name', 'like', 'تكلفة مبيعات %');
                })
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

            $accountId = Account::query()
                ->where('name', $candidate)
                ->value('id');

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

            $relatedAccountIds = array_merge([$root->id], $root->getAllChildrenIds());

            $query = Account::query()
                ->whereIn('id', $relatedAccountIds);

            $descendantFilter($query);

            $accountId = $query->orderBy('id')->value('id');

            if ($accountId) {
                return (int) $accountId;
            }
        }

        return null;
    }

    private function ensureRecruitmentContractCostCenter(Contract $contract, string $nationalityName): CostCenter
    {
        $rootCostCenter = $this->ensureRecruitmentContractsRootCostCenter();

        $costCenter = CostCenter::query()
            ->lockForUpdate()
            ->where('parent_id', $rootCostCenter->id)
            ->where('table_name', CostCenterTypeEnum::General->value)
            ->where('table_related_id', $contract->id)
            ->first();

        if ($costCenter) {
            return $costCenter;
        }

        $title = 'عقد استقدام #' . $contract->id;
        if ($nationalityName !== '') {
            $title .= ' - ' . $nationalityName;
        }

        return CostCenter::query()->create([
            'title' => $title,
            'parent_id' => $rootCostCenter->id,
            'table_name' => CostCenterTypeEnum::General->value,
            'table_related_id' => $contract->id,
            'code' => $this->nextCostCenterCode(),
            'created_by_id' => Auth::guard('admin')->id() ?: Auth::id() ?: 1,
        ]);
    }

    private function ensureRecruitmentContractsRootCostCenter(): CostCenter
    {
        $costCenter = CostCenter::query()
            ->lockForUpdate()
            ->whereNull('parent_id')
            ->where('title', 'عقود الاستقدام')
            ->first();

        if ($costCenter) {
            return $costCenter;
        }

        return CostCenter::query()->create([
            'title' => 'عقود الاستقدام',
            'parent_id' => null,
            'table_name' => CostCenterTypeEnum::General->value,
            'table_related_id' => 0,
            'code' => $this->nextCostCenterCode(),
            'created_by_id' => Auth::guard('admin')->id() ?: Auth::id() ?: 1,
        ]);
    }

    private function nextCostCenterCode(): string
    {
        $lastCode = CostCenter::query()
            ->lockForUpdate()
            ->max('code');

        return (string) (((int) $lastCode) + 1);
    }

    /**
     * الحساب المربوط صراحةً بالجنسية من صفحة إعدادات الحسابات (nationality_account_{id}_{type}).
     * يُستخدم فقط عندما يُمرَّر رقم الجنسية (وضع «إثبات الإيراد عند الفاتورة»)؛ مسار الوصول
     * الافتراضي يبقى على المطابقة بالاسم كما كان.
     */
    private function resolveConfiguredNationalityAccountId(?int $nationalityId, string $accountType): ?int
    {
        if (!$nationalityId) {
            return null;
        }

        $accountId = ConfirmSetting::query()
            ->where('title', 'nationality_account_' . $nationalityId . '_' . $accountType)
            ->latest('updated_at')
            ->value('value');

        if (!$accountId || !is_numeric($accountId)) {
            return null;
        }

        return Account::query()->whereKey((int) $accountId)->exists() ? (int) $accountId : null;
    }

    private function resolveNationalityName(Contract $contract): string
    {
        $nationality = $contract->maid?->nationality ?? $contract->requiredMaidNationality;

        return trim((string) ($nationality?->sub_title ?? $nationality?->title ?? ''));
    }

    private function resolveEntryDate(?TravelHistory $travelHistory): string
    {
        if ($travelHistory?->arrival_date) {
            return Carbon::parse($travelHistory->arrival_date)->toDateString();
        }

        return now()->toDateString();
    }

    private function buildArrivalRevenueStatement(Contract $contract, Invoice $invoice): string
    {
        return 'ايراد طلب استقدام: ' . $contract->id
            . ', فاتورة رقم: ' . $invoice->id
            . ' العميل: ' . $this->resolveClientReference($contract);
    }

    private function buildRevenueDetailStatement(Contract $contract, Invoice $invoice, string $label): string
    {
        return 'فاتورة: ' . $invoice->id
            . ' / طلب استقدام: ' . $contract->id
            . ' / ' . $label;
    }

    private function buildExternalOfficeCostStatement(Contract $contract, Invoice $invoice): string
    {
        return 'فاتورة مورد: إغلاق طلب: ' . $contract->id
            . ', فاتورة رقم: ' . $invoice->id
            . ' العميل: ' . $this->resolveClientReference($contract);
    }

    private function resolveClientReference(Contract $contract): string
    {
        $clientAccount = (string) ($contract->user?->account_id ?: $contract->user_id ?: '-');
        $clientName = trim((string) ($contract->user?->name ?? 'غير محدد'));

        return $clientAccount . '::' . $clientName;
    }

    private function findExistingConstraint(string $statement, string $date, float $value): ?Constraint
    {
        if ($this->roundMoney($value) <= 0) {
            return null;
        }

        return Constraint::query()
            ->where('statement', $statement)
            ->whereDate('date', $date)
            ->where('value', $this->roundMoney($value))
            ->latest('id')
            ->first();
    }

    private function roundMoney(float $amount): float
    {
        return round($amount, 2);
    }
}
