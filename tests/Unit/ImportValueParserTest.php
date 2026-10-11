<?php

namespace Tests\Unit;

use App\Services\ingaz\ImportValueParser;
use PHPUnit\Framework\TestCase;

class ImportValueParserTest extends TestCase
{
    public function test_supported_dates_and_arabic_amounts_are_normalized(): void
    {
        $this->assertSame('2026-10-11', ImportValueParser::date('١١/١٠/٢٠٢٦')->toDateString());
        $this->assertSame('2024-02-29', ImportValueParser::date('20240229')->toDateString());
        $this->assertSame(1250.5, ImportValueParser::decimal('١٬٢٥٠٫٥٠'));
        $this->assertNull(ImportValueParser::decimal(''));
    }

    /** @dataProvider invalidDateProvider */
    public function test_invalid_dates_are_not_silently_changed(string $date): void
    {
        $this->expectException(\InvalidArgumentException::class);
        ImportValueParser::date($date, true);
    }

    public static function invalidDateProvider(): array
    {
        return [['2026-02-31'], ['20260231'], ['2026-13-01'], ['not a date'], ['2026-10-11 25:00:00']];
    }

    /** @dataProvider invalidNumberProvider */
    public function test_invalid_amounts_do_not_become_zero(string $number): void
    {
        $this->expectException(\InvalidArgumentException::class);
        ImportValueParser::decimal($number);
    }

    public static function invalidNumberProvider(): array { return [['abc'], ['12,34'], ['1.2.3'], ['INF']]; }
}
