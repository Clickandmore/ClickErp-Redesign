<?php

namespace Tests\Unit;

use App\Models\SponsorshipTransfer;
use App\Services\ingaz\SponsorshipTransferCompletionService;
use App\Services\ingaz\SponsorshipTransferFailureService;
use App\Services\ingaz\SponsorshipTransferPaymentService;
use App\Services\ingaz\SponsorshipTransferTrialStartService;
use Mockery;
use Nami\Finance\Services\BondService;
use Nami\Finance\Services\ConstraintService;
use Nami\Finance\Services\YearService;
use PHPUnit\Framework\TestCase;

class RentalSponsorshipTransferAccountingTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_rental_workflow_is_identified_explicitly(): void
    {
        $rental = new SponsorshipTransfer(['workflow_source' => 'rental']);
        $standard = new SponsorshipTransfer(['workflow_source' => 'standard']);

        $this->assertTrue($rental->isRentalWorkflow());
        $this->assertFalse($standard->isRentalWorkflow());
    }

    public function test_rental_workflow_skips_receipt_and_refund_bonds(): void
    {
        $service = new SponsorshipTransferPaymentService(
            Mockery::mock(BondService::class),
            Mockery::mock(YearService::class)
        );
        $transfer = new SponsorshipTransfer(['workflow_source' => 'rental']);

        $this->assertSame(
            ['bond' => null, 'constraint' => null],
            $service->createReceipt($transfer, 1)
        );
        $this->assertSame(
            ['bond' => null, 'constraint' => null],
            $service->createRefund($transfer, 100, 1)
        );
    }

    public function test_rental_workflow_skips_all_transfer_constraints(): void
    {
        $constraintService = Mockery::mock(ConstraintService::class);
        $yearService = Mockery::mock(YearService::class);
        $transfer = new SponsorshipTransfer(['workflow_source' => 'rental']);

        $this->assertNull((new SponsorshipTransferTrialStartService($constraintService, $yearService))->record($transfer));
        $this->assertNull((new SponsorshipTransferCompletionService($constraintService, $yearService))->record($transfer));
        $this->assertNull((new SponsorshipTransferFailureService($constraintService, $yearService))->record($transfer));
    }

    private static function projectPath(string $relative): string
    {
        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . $relative;
    }

    /**
     * قيد بداية التجربة كان يثبت الدفعة المحصّلة مرة ثانية بجانب سند القبض،
     * وكان يبحث عن حساب البنك بالاسم فيقع على «عمولات ورسوم بنكية».
     * التحصيل مصدره الوحيد سند القبض.
     */
    public function test_trial_start_journal_never_records_the_collected_payment_again(): void
    {
        $source = file_get_contents(
            self::projectPath('app/Services/ingaz/SponsorshipTransferTrialStartService.php')
        );

        $this->assertStringNotContainsString('وارد دفعة مقدمة من العميل الجديد', $source);
        $this->assertStringNotContainsString('سداد دفعة مقدمة', $source);
        $this->assertFalse(
            method_exists(SponsorshipTransferTrialStartService::class, 'resolveBankAccountId'),
            'حل حساب البنك بمطابقة الاسم يلتقط حساب «عمولات ورسوم بنكية»؛ لا يجوز إعادته.'
        );
        $this->assertStringNotContainsString("'%بنك%'", $source);
    }

    /**
     * عاملة الإيجار على المسار المالي تنتج إيراداً مؤجلاً وإيراد إتمام وفاتورة
     * بإيراد وضريبة فوق قيود عقد التأجير، أي تكرار كامل.
     */
    public function test_rental_worker_cannot_be_submitted_on_the_financial_workflow(): void
    {
        $source = file_get_contents(
            self::projectPath('app/Http/Controllers/ingaz/SponsorshipTransferController.php')
        );

        $this->assertStringContainsString(
            "\$workflowSource === 'standard' && \$this->contractIsRentalWorker(\$contract)",
            $source,
            'يجب رفض إنشاء طلب نقل خدمات مالي لعاملة على عقد تأجير.'
        );
    }

    /**
     * مسار الفاتورة كان بلا أي حارس للإيجار، فزر «إصدار فاتورة» يعيد ترحيل
     * الإيراد والضريبة المسجَّلين أصلاً على عقد التأجير.
     */
    public function test_sponsorship_transfer_invoice_is_blocked_for_the_rental_workflow(): void
    {
        $source = file_get_contents(
            self::projectPath('app/Http/Controllers/ingaz/InvoiceController.php')
        );

        $this->assertSame(
            2,
            substr_count($source, '$transfer->isRentalWorkflow()'),
            'يجب حماية عرض إنشاء الفاتورة وحفظها معاً من طلبات نقل الخدمات الصادرة عن عقود التأجير.'
        );
    }
}
