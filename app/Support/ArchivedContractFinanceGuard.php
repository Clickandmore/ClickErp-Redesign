<?php

namespace App\Support;

use App\Exceptions\ArchivedContractException;
use App\Models\Contract;
use App\Models\ContractReturnSettlement;
use App\Models\FinancialRequest;
use App\Models\RecruitmentContractRefund;
use App\Models\RecruitmentInvoiceCreditNote;
use App\Models\RentalContract;
use App\Models\RentalContractExtension;
use App\Models\RentalContractInvoice;
use App\Models\RentalFinancialAdjustment;
use App\Models\ingaz\Invoice;
use Illuminate\Database\Eloquent\Model;
use Nami\Finance\Models\Bond;
use Nami\Finance\Models\ProcurementDocument;

/**
 * يمنع إنشاء مستند مالي جديد لعقد مؤرشف من أي شاشة إدارة (فواتير، سندات،
 * طلبات مالية، إشعارات دائنة، مستردات، تسويات، فواتير ومستندات الإيجار).
 *
 * يعمل فقط حين ينفّذ موظف من لوحة الإدارة: العمليات الآلية (إشعارات بوابة
 * الدفع، المهام المجدولة، أوامر artisan) لا تُمنع، حتى لا تضيع دفعة حقيقية.
 * المستندات الموجودة وتعديلها لا تتأثر.
 */
class ArchivedContractFinanceGuard
{
    private static int $bypass = 0;

    /**
     * النموذج => [عمود => نوع العقد]. نوع 'by_contract_type' يعني أن النوع
     * محفوظ في عمود contract_type بجانبه.
     */
    public static function links(): array
    {
        return [
            Invoice::class => ['contract_id' => 'recruitment', 'rental_transfer_contract_id' => 'rental'],
            FinancialRequest::class => ['contract_id' => 'by_contract_type'],
            Bond::class => ['recruitment_contract_id' => 'recruitment', 'rental_contract_id' => 'rental'],
            RecruitmentInvoiceCreditNote::class => ['contract_id' => 'recruitment'],
            RecruitmentContractRefund::class => ['contract_id' => 'recruitment'],
            ContractReturnSettlement::class => ['contract_id' => 'recruitment'],
            ProcurementDocument::class => ['contract_id' => 'recruitment'],
            RentalContractInvoice::class => ['rental_contract_id' => 'rental'],
            RentalFinancialAdjustment::class => ['rental_contract_id' => 'rental'],
            RentalContractExtension::class => ['rental_contract_id' => 'rental'],
        ];
    }

    public static function register(): void
    {
        foreach (array_keys(self::links()) as $modelClass) {
            if (class_exists($modelClass)) {
                $modelClass::creating(fn (Model $model) => self::check($model));
            }
        }
    }

    /** تنفيذ عملية بدون هذا المنع (لمعالجة إدارية مقصودة). */
    public static function withoutGuard(callable $callback)
    {
        self::$bypass++;

        try {
            return $callback();
        } finally {
            self::$bypass--;
        }
    }

    public static function check(Model $model): void
    {
        // بلا موظف مسجّل = عملية آلية (webhook، مهمة مجدولة، طابور، artisan).
        if (self::$bypass > 0 || !auth('admin')->check()) {
            return;
        }

        foreach (self::links()[get_class($model)] ?? [] as $column => $type) {
            $contractId = (int) $model->getAttribute($column);
            if ($contractId <= 0) {
                continue;
            }

            if ($type === 'by_contract_type') {
                $type = $model->getAttribute('contract_type') === 'rental' ? 'rental' : 'recruitment';
            }

            self::assertNotArchived($type, $contractId);
        }
    }

    public static function assertNotArchived(string $type, int $contractId): void
    {
        $modelClass = $type === 'rental' ? RentalContract::class : Contract::class;
        if (!$modelClass::archiveColumnExists()) {
            return;
        }

        $archived = $modelClass::query()->withoutGlobalScopes()
            ->whereKey($contractId)
            ->whereNotNull('archived_at')
            ->exists();

        if ($archived) {
            throw new ArchivedContractException($type, $contractId);
        }
    }
}
