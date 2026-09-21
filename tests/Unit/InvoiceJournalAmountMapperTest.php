<?php

namespace Tests\Unit;

use App\Services\InvoiceJournalAmountMapper;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InvoiceJournalAmountMapperTest extends TestCase
{
    public function test_grouped_rows_keep_their_component_totals(): void
    {
        $rows = [
            ['id' => 1, 'debtor_value' => 115, 'creditor_value' => 0],
            ['id' => 2, 'debtor_value' => 0, 'creditor_value' => 100],
            ['id' => 3, 'debtor_value' => 0, 'creditor_value' => 15],
        ];
        $mapped = (new InvoiceJournalAmountMapper)->map($rows, ['direct' => 7000, 'other' => 3000, 'vat' => 1500], ['direct' => 8000, 'other' => 4000, 'vat' => 1800]);
        $this->assertEquals(138, $mapped[1]['debtor_value']);
        $this->assertEquals(120, $mapped[2]['creditor_value']);
        $this->assertEquals(18, $mapped[3]['creditor_value']);
    }

    public function test_equal_original_amounts_cannot_be_assigned_to_different_new_values(): void
    {
        $this->expectException(ValidationException::class);
        (new InvoiceJournalAmountMapper)->map([
            ['id' => 1, 'debtor_value' => 200, 'creditor_value' => 0],
            ['id' => 2, 'debtor_value' => 0, 'creditor_value' => 100],
            ['id' => 3, 'debtor_value' => 0, 'creditor_value' => 100],
        ], ['direct' => 10000, 'other' => 10000], ['direct' => 20000, 'other' => 10000]);
    }

    public function test_new_component_is_allowed_when_only_one_account_distribution_is_possible(): void
    {
        $mapped = (new InvoiceJournalAmountMapper)->map([
            ['id' => 1, 'debtor_value' => 100, 'creditor_value' => 0],
            ['id' => 2, 'debtor_value' => 0, 'creditor_value' => 100],
        ], ['direct' => 10000, 'other' => 0], ['direct' => 10000, 'other' => 2000]);
        $this->assertEquals(120, $mapped[1]['debtor_value']);
        $this->assertEquals(120, $mapped[2]['creditor_value']);
    }
}
