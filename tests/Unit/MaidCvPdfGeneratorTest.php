<?php

namespace Tests\Unit;

use App\Models\ingaz\Maid;
use App\Services\ingaz\MaidCvPdfGenerator;
use Barryvdh\DomPDF\Facade\Pdf;
use Tests\TestCase;

class MaidCvPdfGeneratorTest extends TestCase
{
    public function test_it_renders_a_database_blade_template_with_maid_data(): void
    {
        $maid = new Maid([
            'name' => 'Test Worker',
            'passport_num' => 'P12345',
        ]);

        $html = app(MaidCvPdfGenerator::class)->renderTemplate(
            '<h1>{{ $maid->name }}</h1><span>{{ $maid->passport_num }}</span>',
            ['maid' => $maid]
        );

        $this->assertStringContainsString('<h1>Test Worker</h1>', $html);
        $this->assertStringContainsString('<span>P12345</span>', $html);
    }

    public function test_it_identifies_only_generated_cv_preview_paths(): void
    {
        $generator = app(MaidCvPdfGenerator::class);

        $this->assertTrue($generator->isGeneratedPath(
            MaidCvPdfGenerator::STORAGE_DIRECTORY . '/maid-10-example.pdf'
        ));
        $this->assertFalse($generator->isGeneratedPath('images/Maid/uploaded-preview.pdf'));
        $this->assertFalse($generator->isGeneratedPath(null));
    }

    public function test_the_degraded_mpdf_fallback_is_disabled_by_default(): void
    {
        // A silent fallback is what made server PDFs stop matching the
        // uploaded template while local PDFs still looked correct.
        $this->assertFalse(config('cv.pdf.allow_mpdf_fallback'));
        $this->assertFalse(app(MaidCvPdfGenerator::class)->mpdfFallbackEnabled());
    }

    public function test_the_browser_command_prints_the_page_without_chrome_headers(): void
    {
        $command = app(MaidCvPdfGenerator::class)
            ->browserCommand('/usr/bin/chromium', '/tmp/profile', '/tmp/cv.pdf', '/tmp/cv.html');

        $this->assertSame('/usr/bin/chromium', $command[0]);
        $this->assertContains('--headless=new', $command);
        $this->assertContains('--no-sandbox', $command);
        $this->assertContains('--no-pdf-header-footer', $command);
        $this->assertContains('--print-to-pdf=/tmp/cv.pdf', $command);
        $this->assertContains('--virtual-time-budget=15000', $command);
        $this->assertSame('file:///tmp/cv.html', end($command));
    }

    public function test_each_startup_profile_disables_one_more_isolation_layer(): void
    {
        $generator = app(MaidCvPdfGenerator::class);
        $command = fn (string $profile) => $generator
            ->browserCommand('/usr/bin/chromium', '/tmp/profile', '/tmp/cv.pdf', '/tmp/cv.html', $profile);

        // Restricted shared hosting kills the zygote process with SIGTRAP.
        $this->assertNotContains('--no-zygote', $command('default'));
        $this->assertContains('--no-zygote', $command('no-zygote'));
        $this->assertNotContains('--single-process', $command('no-zygote'));
        $this->assertContains('--single-process', $command('single-process'));
        $this->assertContains('--headless', $command('legacy-headless'));
        $this->assertNotContains('--headless=new', $command('legacy-headless'));
    }

    public function test_a_pinned_startup_profile_skips_the_other_attempts(): void
    {
        $generator = app(MaidCvPdfGenerator::class);

        $this->assertSame(MaidCvPdfGenerator::BROWSER_PROFILES, $generator->browserProfiles());

        config()->set('cv.pdf.browser_profile', 'single-process');
        $this->assertSame(['single-process'], $generator->browserProfiles());

        config()->set('cv.pdf.browser_profile', 'not-a-profile');
        $this->assertSame(MaidCvPdfGenerator::BROWSER_PROFILES, $generator->browserProfiles());
    }

    public function test_dompdf_can_create_a_pdf_from_rendered_blade_html(): void
    {
        $html = app(MaidCvPdfGenerator::class)->renderTemplate(
            '<html><body><h1>{{ $title }}</h1></body></html>',
            ['title' => 'Worker CV']
        );

        $contents = Pdf::loadHTML($html)->setPaper('a4')->output();

        $this->assertStringStartsWith('%PDF-', $contents);
    }
}
