<?php

namespace Tests\Unit;

use App\Services\ingaz\DataImportExecutionService;
use App\Services\ingaz\DataImportTemplateService;
use ReflectionClass;
use Tests\TestCase;

class AlrafedinFinanceBondsImportTest extends TestCase
{
    private function invoke(string $method, mixed ...$arguments): mixed
    {
        $service = app(DataImportExecutionService::class);
        $target = (new ReflectionClass($service))->getMethod($method);
        $target->setAccessible(true);

        return $target->invoke($service, ...$arguments);
    }

    public function test_all_source_receipt_and_disbursement_rows_are_read_without_column_loss(): void
    {
        $directory = base_path('migration_system/data/alrafedin/finance/pyment-documnet');
        $templates = app(DataImportTemplateService::class);
        $receiptColumns = $templates->template('receipt_bonds')['columns'];
        $disbursementColumns = $templates->template('disbursement_bonds')['columns'];

        $receiptFiles = array_values(array_filter(
            glob($directory . '/*.xlsx'),
            fn (string $path) => !str_contains(basename($path), '~$')
                && (str_contains(basename($path), 'القبض') || str_contains(basename($path), 'قبض_'))
        ));
        $receiptRows = 0;
        foreach ($receiptFiles as $path) {
            $receiptRows += iterator_count($this->invoke('readRows', $path, $receiptColumns));
        }

        $disbursementPath = glob($directory . '/سندات الصرف*.xlsx')[0];
        $disbursementRows = iterator_to_array($this->invoke('readRows', $disbursementPath, $disbursementColumns));

        $this->assertCount(7, $receiptFiles);
        $this->assertSame(6970, $receiptRows);
        $this->assertCount(837, $disbursementRows);
        $this->assertSame('843', (string) $disbursementRows[2]['bond_code']);
        $this->assertSame('117', (string) $disbursementRows[2]['paid_amount']);
        $this->assertSame('ريال سعودي', $disbursementRows[2]['financial_movement_currency']);
    }

    public function test_rental_order_reference_and_raw_source_payload_are_preserved(): void
    {
        $statement = 'تكلفة طلب تأجير رقم : 1936 (1587 :: Nahwera Jovullet)';

        $this->assertSame(1936, $this->invoke('extractLegacyRentalOrderNumber', $statement));
        $this->assertNull($this->invoke('extractLegacyRentalOrderNumber', 'دفعة طلب استقدام رقم 1936'));

        $payload = $this->invoke('legacyBondSourcePayload', [
            'bond_code' => '6273',
            'hijri_date' => '22-01-1448',
            'financial_movement' => '1900.01',
            '__source_file' => 'سندات القبض.xlsx',
            '__source_row' => 5,
        ]);

        $this->assertSame([
            'bond_code' => '6273',
            'hijri_date' => '22-01-1448',
            'financial_movement' => '1900.01',
        ], $payload);
    }
}
