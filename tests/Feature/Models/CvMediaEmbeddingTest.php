<?php

namespace Tests\Feature\Models;

use App\Models\ingaz\Maid;
use App\Models\ingaz\Setting;
use App\Services\ingaz\MaidCvPdfGenerator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * قالب السيرة يستدعي showFile() فيخرج رابطًا — محليًا أو موقّعًا من R2 — والاثنان
 * يحتاجان طلب شبكة أثناء التحويل. المولّد يستبدلهما بـ data URI مقروء عبر
 * ManagedFileStorage قبل تسليم الـ HTML للمتصفح أو mPDF.
 */
class CvMediaEmbeddingTest extends TestCase
{
    use DatabaseTransactions;

    private function embed(string $html, Maid $maid, Setting $settings): string
    {
        $method = new \ReflectionMethod(MaidCvPdfGenerator::class, 'embedManagedMedia');
        $method->setAccessible(true);

        return $method->invoke(app(MaidCvPdfGenerator::class), $html, $maid, $settings);
    }

    private function mediaPaths(Maid $maid, Setting $settings): array
    {
        $method = new \ReflectionMethod(MaidCvPdfGenerator::class, 'mediaSourcePaths');
        $method->setAccessible(true);

        return $method->invoke(app(MaidCvPdfGenerator::class), $maid, $settings);
    }

    public function test_media_paths_are_collected_from_the_maid_and_the_settings(): void
    {
        $maid = new Maid();
        $maid->setRawAttributes([
            'id' => 1,
            'image' => 'images/Maid/a.png',
            'cv_file' => 'images/Maid/full-body.jpg',
        ], true);

        $settings = new Setting();
        $settings->setRawAttributes([
            'id' => 1,
            'logo_header' => 'images/settings/logo.svg',
            'favicon' => '',
            'qr_image' => 'null',
        ], true);

        $paths = $this->mediaPaths($maid, $settings);

        $this->assertContains('images/Maid/a.png', $paths);
        $this->assertContains('images/Maid/full-body.jpg', $paths);
        $this->assertContains('images/settings/logo.svg', $paths);
        // القيم الفارغة و"null" النصية لا تُعد ملفات
        $this->assertNotContains('', $paths);
        $this->assertNotContains('null', $paths);
    }

    public function test_html_without_matching_urls_is_returned_untouched(): void
    {
        $maid = new Maid();
        $maid->setRawAttributes(['id' => 1, 'image' => 'images/Maid/missing.png'], true);

        $settings = new Setting();
        $settings->setRawAttributes(['id' => 1], true);

        $html = '<html><body><img src="https://example.com/other.png" /></body></html>';

        $this->assertSame($html, $this->embed($html, $maid, $settings));
    }

    /**
     * الرابط الموقّع يحمل توقيعًا مختلفًا في كل استدعاء، فالمطابقة تتم على ما قبل "?"
     * ثم يُبتلع ما بعده. هذا يثبّت التعبير النمطي المستخدم في embedManagedMedia().
     */
    public function test_signed_and_local_urls_are_both_replaced_including_the_query_string(): void
    {
        $dataUri = 'data:image/png;base64,AA$BB';
        $replacement = str_replace(['\\', '$'], ['\\\\', '\\$'], $dataUri);

        $bases = [
            'https://acc.r2.cloudflarestorage.com/bucket/hajar/images/Maid/a.png',
            'http://127.0.0.1:8000/storage/images/Maid/b.png',
        ];

        $html = '<img src="' . $bases[0] . '?X-Amz-Signature=OLD&Expires=1" />'
            . '<img src="' . $bases[1] . '" />';

        foreach ($bases as $base) {
            $html = preg_replace(
                '~' . preg_quote($base, '~') . '(\?[^"\'\s>]*)?~',
                $replacement,
                $html
            );
        }

        $this->assertSame('<img src="' . $dataUri . '" /><img src="' . $dataUri . '" />', $html);
        $this->assertStringNotContainsString('X-Amz-Signature', $html);
        $this->assertStringNotContainsString('r2.cloudflarestorage.com', $html);
    }
}
