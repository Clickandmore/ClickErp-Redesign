<?php

namespace Tests\Unit;

use App\Services\ingaz\ArabiaClientsImportService;
use App\Services\ingaz\ArabiaRecruitmentOrdersImportService;
use ReflectionMethod;
use Tests\TestCase;

class ArabiaClientPhoneNormalizationTest extends TestCase
{
    /** @dataProvider saudiPhoneProvider */
    public function test_both_arabia_imports_use_the_same_canonical_phone($input, string $expected): void
    {
        $clientsMethod = new ReflectionMethod(ArabiaClientsImportService::class, 'normalizePhone');
        $clientsMethod->setAccessible(true);
        $ordersMethod = new ReflectionMethod(ArabiaRecruitmentOrdersImportService::class, 'phone');
        $ordersMethod->setAccessible(true);

        $this->assertSame($expected, $clientsMethod->invoke(app(ArabiaClientsImportService::class), $input));
        $this->assertSame($expected, $ordersMethod->invoke(app(ArabiaRecruitmentOrdersImportService::class), $input));
    }

    public static function saudiPhoneProvider(): array
    {
        return [
            'local' => ['0552281700', '552281700'],
            'country code' => ['+966 55 228 1700', '552281700'],
            'unicode direction marks' => ["⁦+966 55 228 1700⁩", '552281700'],
            'arabic digits' => ['٠٥٥٢٢٨١٧٠٠', '552281700'],
            'invalid' => ['12345', ''],
            'missing' => [null, ''],
        ];
    }
}
