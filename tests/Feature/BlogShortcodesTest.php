<?php

namespace Tests\Feature;

use App\Models\ingaz\Setting;
use App\Support\BlogShortcodes;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * {{contact_info}} في محتوى المدونة يصل للموقع الخارجي كزرّي اتصال وواتساب.
 */
class BlogShortcodesTest extends TestCase
{
    use DatabaseTransactions;

    public function test_contact_info_paragraph_becomes_call_and_whatsapp_buttons(): void
    {
        $this->setContacts('0552987588', '');

        $html = BlogShortcodes::render('<p>قبل</p><p>&nbsp;<strong>{{ contact_info }}</strong></p><p>بعد</p>');

        $this->assertStringNotContainsString('contact_info}}', $html);
        $this->assertStringContainsString('<p>قبل</p><div class="blog-contact-cta"', $html, 'الفقرة كاملة تُستبدل بكتلة div.');
        $this->assertStringContainsString('href="tel:0552987588"', $html);
        // الواتساب يرجع للهاتف عند غيابه، بصيغة wa.me الدولية.
        $this->assertStringContainsString('href="https://wa.me/966552987588"', $html);
        $this->assertStringEndsWith('</div><p>بعد</p>', $html);
    }

    public function test_inline_shortcode_stays_valid_inside_the_paragraph(): void
    {
        $this->setContacts('0552987588', '+966 50 111 2222');

        $html = BlogShortcodes::render('<p>اتصل الآن {{contact_info}} للحجز</p>');

        $this->assertStringStartsWith('<p>اتصل الآن <span class="blog-contact-cta"', $html);
        $this->assertStringContainsString('href="https://wa.me/966501112222"', $html);
    }

    public function test_shortcode_disappears_when_no_numbers_are_configured(): void
    {
        $this->setContacts('', '');

        $this->assertSame('<p>أ</p><p>ب</p>', BlogShortcodes::render('<p>أ</p><p>{{contact_info}}</p><p>ب</p>'));
    }

    public function test_unknown_shortcodes_are_left_untouched(): void
    {
        $this->assertSame('<p>{{other}}</p>', BlogShortcodes::render('<p>{{other}}</p>'));
    }

    private function setContacts(string $phone, string $whatsapp): void
    {
        $settings = Setting::query()->first();
        if (! $settings) {
            $this->markTestSkipped('جدول settings فارغ في قاعدة الاختبار.');
        }

        $settings->forceFill(['phone' => $phone, 'whatsapp' => $whatsapp])->saveQuietly();
    }
}
