<?php

namespace Tests\Unit;

use App\Models\ingaz\Maid;
use App\Models\MaidExpertise;
use App\Services\ingaz\MaidCvPdfGenerator;
use App\Services\ManagedFileStorage;
use Barryvdh\DomPDF\Facade\Pdf;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

class MaidCvPdfGeneratorTest extends TestCase
{
    public function test_rental_cv_hides_salary_even_when_request_type_is_custom_spec(): void
    {
        $rentalId = \App\Services\Rental\RentalMaidQuery::rentalJobTypeId();
        $maid = new Maid(['request_type' => 'custom_spec']);
        $maid->setRelation('jobTypes', collect([(object) ['id' => $rentalId]]));
        $generator = app(MaidCvPdfGenerator::class);
        $method = new ReflectionMethod($generator, 'cvServiceType');
        $method->setAccessible(true);
        $type = $method->invoke($generator, $maid);
        $this->assertSame('egar', $type);
        $template = '@if (($type ?? $maid->request_type ?? "") !== "egar")' . "\nSALARY_MARKER\n" . '@endif';
        $this->assertStringNotContainsString('SALARY_MARKER', $generator->renderTemplate($template, compact('type', 'maid')));
        $maid->setRelation('jobTypes', collect());
        $type = $method->invoke($generator, $maid);
        $this->assertStringContainsString('SALARY_MARKER', $generator->renderTemplate($template, compact('type', 'maid')));
    }

    public function test_it_passes_complete_expertise_rows_to_the_cv_template(): void
    {
        $expertise = new MaidExpertise([
            'work_city' => 'Riyadh',
            'start_date' => '2020-01-01',
            'end_date' => '2022-01-01',
            'number_of_years' => 2,
        ]);
        $maid = new Maid(['experience_status' => 'has_experience']);
        $maid->setRelation('expertises', collect([$expertise]));

        $method = new ReflectionMethod(MaidCvPdfGenerator::class, 'experienceViewData');
        $method->setAccessible(true);
        $experience = $method->invoke(app(MaidCvPdfGenerator::class), $maid);

        $this->assertSame('has_experience', $experience['status']);
        $this->assertSame($expertise, $experience['countries']->first());
        $this->assertSame('Riyadh', $experience['countries']->first()->work_city);
        $this->assertNotNull($experience['countries']->first()->period_label);
        $this->assertNotNull($experience['countries']->first()->duration_label);
    }

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

    public function test_it_uses_the_cv_public_site_url_while_rendering_and_restores_the_environment_value(): void
    {
        config(['app.public_site_url' => 'https://environment.example']);

        $html = app(MaidCvPdfGenerator::class)->renderTemplate(
            '<span>{{ config("app.public_site_url") }}</span>',
            ['settings' => (object) ['cv_public_site_url' => 'https://website.example/']]
        );

        $this->assertStringContainsString('<span>https://website.example</span>', $html);
        $this->assertSame('https://environment.example', config('app.public_site_url'));
    }

    public function test_it_identifies_only_generated_cv_preview_paths(): void
    {
        $generator = app(MaidCvPdfGenerator::class);

        $this->assertTrue($generator->isGeneratedPath(
            MaidCvPdfGenerator::STORAGE_DIRECTORY . '/maid-10-example.pdf'
        ));
        $this->assertTrue($generator->isGeneratedPath(
            'r2://clickerp/' . MaidCvPdfGenerator::STORAGE_DIRECTORY . '/maid-10-example.pdf'
        ));
        $this->assertTrue($generator->isGeneratedPath(
            'r2://another/root/prefix/' . MaidCvPdfGenerator::STORAGE_DIRECTORY . '/maid-10-example.png?version=2'
        ));
        $this->assertTrue($generator->isGeneratedPath(
            'https://cdn.example.com/company/' . MaidCvPdfGenerator::STORAGE_DIRECTORY . '/maid-10-example.svg?token=abc#preview'
        ));
        $this->assertTrue($generator->isGeneratedPath(
            'C:\\storage\\app\\public\\' . MaidCvPdfGenerator::STORAGE_DIRECTORY . '\\maid-10-example.pdf'
        ));
        $this->assertFalse($generator->isGeneratedPath('images/Maid/uploaded-preview.pdf'));
        $this->assertFalse($generator->isGeneratedPath(
            'r2://clickerp/' . MaidCvPdfGenerator::STORAGE_DIRECTORY . '/unsupported.jpg'
        ));
        $this->assertFalse($generator->isGeneratedPath(
            'r2://clickerp/images/Maid/not-generated-cvs/maid-10-example.pdf'
        ));
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

    public function test_it_wraps_the_stored_cv_image_in_a_pdf_without_rerendering_the_template(): void
    {
        $path = MaidCvPdfGenerator::STORAGE_DIRECTORY . '/maid-10-example.png';
        $imageContents = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
            true
        );
        $storage = Mockery::mock(ManagedFileStorage::class);
        $storage->shouldReceive('exists')->once()->with($path)->andReturnTrue();
        $storage->shouldReceive('contents')->once()->with($path)->andReturn($imageContents);
        $storage->shouldReceive('mimeType')->once()->with($path)->andReturn('image/png');
        $this->app->instance(ManagedFileStorage::class, $storage);

        $contents = app(MaidCvPdfGenerator::class)->renderStoredImageAsPdfContents($path);

        $this->assertStringStartsWith('%PDF-', $contents);
        $this->assertStringContainsString('/Subtype /Image', $contents);
    }

    public function test_it_wraps_the_stored_svg_cv_in_a_pdf_without_rerendering_the_template(): void
    {
        $path = MaidCvPdfGenerator::STORAGE_DIRECTORY . '/maid-10-example.svg';
        $imageContents = '<svg xmlns="http://www.w3.org/2000/svg" width="794" height="1123">'
            . '<rect width="794" height="1123" fill="#102447"/>'
            . '<text x="40" y="80" fill="#ffffff">CV</text></svg>';
        $storage = Mockery::mock(ManagedFileStorage::class);
        $storage->shouldReceive('exists')->once()->with($path)->andReturnTrue();
        $storage->shouldReceive('contents')->once()->with($path)->andReturn($imageContents);
        $storage->shouldReceive('mimeType')->once()->with($path)->andReturn('image/svg+xml');
        $this->app->instance(ManagedFileStorage::class, $storage);

        $contents = app(MaidCvPdfGenerator::class)->renderStoredImageAsPdfContents($path);

        $this->assertStringStartsWith('%PDF-', $contents);
    }

    public function test_it_routes_foreign_object_svg_through_browser_rendering(): void
    {
        $path = MaidCvPdfGenerator::STORAGE_DIRECTORY . '/maid-10-example.svg';
        $imageContents = '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject>'
            . '<div xmlns="http://www.w3.org/1999/xhtml">CV</div>'
            . '</foreignObject></svg>';
        $maid = new Maid(['name' => 'Test Worker']);
        $storage = Mockery::mock(ManagedFileStorage::class);
        $storage->shouldReceive('exists')->once()->with($path)->andReturnTrue();
        $storage->shouldReceive('contents')->once()->with($path)->andReturn($imageContents);
        $storage->shouldReceive('mimeType')->once()->with($path)->andReturn('image/svg+xml');
        $this->app->instance(ManagedFileStorage::class, $storage);

        $generator = Mockery::mock(MaidCvPdfGenerator::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $generator->shouldReceive('renderForeignObjectSvgAsPdfContents')
            ->once()
            ->with($imageContents, $maid)
            ->andReturn('%PDF-browser-render');

        $contents = $generator->renderStoredImageAsPdfContents($path, $maid);

        $this->assertSame('%PDF-browser-render', $contents);
    }
}
