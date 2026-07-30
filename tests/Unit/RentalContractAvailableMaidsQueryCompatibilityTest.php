<?php

namespace Tests\Unit;

use App\Http\Controllers\ingaz\RentContractController;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class RentalContractAvailableMaidsQueryCompatibilityTest extends TestCase
{
    public function test_current_maid_condition_uses_a_laravel_8_compatible_builder_method(): void
    {
        $method = new ReflectionMethod(RentContractController::class, 'getAvailableRentalMaidsQuery');
        $lines = file($method->getFileName());
        $source = implode('', array_slice(
            $lines,
            $method->getStartLine() - 1,
            $method->getEndLine() - $method->getStartLine() + 1
        ));

        $this->assertStringContainsString("\$query->orWhere('id', \$currentMaidId);", $source);
        $this->assertStringNotContainsString('orWhereKey(', $source);
    }
}
