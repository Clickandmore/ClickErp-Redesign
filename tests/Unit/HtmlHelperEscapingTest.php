<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * مساعدات تبني HTML يُعرض بـ {!! !!} في لوحة التحكم، وقيمها (هاتف/بريد/اسم عميل)
 * يتحكم فيها العميل من الموقع العام أو التطبيق — يجب أن تُهرَّب ضد XSS.
 */
class HtmlHelperEscapingTest extends TestCase
{
    private const PAYLOAD = '"><script>alert(1)</script>';

    public function test_phone_tag_escapes_payload(): void
    {
        $html = getPhoneWithTag('0551' . self::PAYLOAD);

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('href="tel:05511"', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_phone_tag_keeps_normal_number_working(): void
    {
        $html = getPhoneWithTag('+966 55-123 4567');

        $this->assertStringContainsString('<a href="tel:+966551234567">', $html);
        $this->assertStringContainsString('+966 55-123 4567', $html);
        $this->assertSame('لم يسجل هاتف', getPhoneWithTag(''));
    }

    public function test_email_tag_escapes_payload(): void
    {
        $html = getEmailWithTag('a@b.com' . self::PAYLOAD);

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('"><', $html);
        $this->assertStringContainsString('&quot;&gt;&lt;script&gt;', $html);
    }

    public function test_email_tag_keeps_normal_email_working(): void
    {
        $this->assertSame(
            '<a href="mailto:user@example.com">user@example.com</a>',
            getEmailWithTag('user@example.com')
        );
        $this->assertSame('لم يسجل ايميل', getEmailWithTag(null));
    }

    public function test_select_element_escapes_text_and_value(): void
    {
        $rows = [
            (object) ['id' => '1" onmouseover="x', 'name' => 'Ali' . self::PAYLOAD],
        ];

        $html = showSelectElement($rows, 'name');

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('" onmouseover="', $html);
        $this->assertStringContainsString('Ali&quot;&gt;&lt;script&gt;', $html);
    }

    public function test_select_element_keeps_normal_options_and_selection(): void
    {
        $rows = [
            (object) ['id' => 7, 'name' => 'محمد'],
            (object) ['id' => 8, 'name' => 'Sara'],
        ];

        $html = showSelectElement($rows, 'name', 8);

        $this->assertStringContainsString('<option value="7"    >محمد</option>', $html);
        $this->assertStringContainsString('<option value="8"    selected>Sara</option>', $html);
    }
}
