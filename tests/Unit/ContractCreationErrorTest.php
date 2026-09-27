<?php

namespace Tests\Unit;

use App\Support\ContractCreationError;
use Illuminate\Database\QueryException;
use PDOException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ContractCreationErrorTest extends TestCase
{
    public function test_missing_column_is_explained_without_sql_or_customer_data(): void
    {
        $error = $this->databaseError(1054, "Unknown column 'created_by_admin_id' in 'field list'");

        $message = ContractCreationError::message($error);

        $this->assertStringContainsString('created_by_admin_id', $message);
        $this->assertStringContainsString('تحديث', $message);
        $this->assertStringNotContainsString('private-customer', $message);
        $this->assertStringNotContainsString('insert into', $message);
    }

    public function test_duplicate_value_is_not_exposed(): void
    {
        $message = ContractCreationError::message($this->databaseError(1062, "Duplicate entry 'private-customer' for key 'users_phone_unique'"));

        $this->assertStringContainsString('مكررة', $message);
        $this->assertStringNotContainsString('private-customer', $message);
    }

    public function test_storage_failure_has_an_actionable_message(): void
    {
        $message = ContractCreationError::message($this->databaseError(14, 'No space left on device'));

        $this->assertStringContainsString('مساحة', $message);
    }

    public function test_unexpected_exception_does_not_expose_internal_details(): void
    {
        $message = ContractCreationError::message(new RuntimeException('secret-password /var/www/private'));

        $this->assertStringContainsString('مرجع الخطأ', $message);
        $this->assertStringNotContainsString('secret-password', $message);
        $this->assertStringNotContainsString('/var/www', $message);
    }

    private function databaseError(int $code, string $message): QueryException
    {
        $previous = new PDOException($message);
        $previous->errorInfo = ['HY000', $code, $message];

        return new QueryException('insert into orders values (?)', ['private-customer'], $previous);
    }
}
