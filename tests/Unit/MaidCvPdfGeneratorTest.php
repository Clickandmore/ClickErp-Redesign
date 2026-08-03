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

    public function test_it_replaces_cairo_with_embedded_tajawal_and_removes_remote_font_links(): void
    {
        $html = app(MaidCvPdfGenerator::class)->renderTemplate(
            '<html><head><link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400..900" rel="stylesheet"></head><body>CV</body></html>',
            []
        );

        $this->assertStringContainsString('data-cv-local-font="tajawal"', $html);
        $this->assertStringContainsString('data:font/ttf;base64,', $html);
        $this->assertStringContainsString('font-family:"Tajawal"', $html);
        $this->assertStringNotContainsString('Cairo', $html);
        $this->assertStringNotContainsString('fonts.googleapis.com', $html);
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
