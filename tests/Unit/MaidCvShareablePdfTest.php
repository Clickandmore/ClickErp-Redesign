<?php

namespace Tests\Unit;

use App\Models\ingaz\Maid;
use App\Services\ManagedFileStorage;
use App\Services\ingaz\MaidCvPdfGenerator;
use App\Support\CvOptionsSettings;
use Mockery;
use Tests\TestCase;

/**
 * ملف السيرة المرسل للعميل يجب أن يطابق القالب: الصورة المولّدة بالمتصفح تُغلَّف في PDF
 * بدل إعادة رسم القالب بـ mPDF (الذي يُخرج صفحات مشوّهة).
 */
class MaidCvShareablePdfTest extends TestCase
{
    protected function tearDown(): void
    {
        CvOptionsSettings::fakeDisplaySource(null);
        parent::tearDown();
    }

    private function maid(array $attributes): Maid
    {
        $maid = new Maid();
        $maid->setRawAttributes(['id' => 5, 'name' => 'Worker'] + $attributes, true);
        return $maid;
    }

    public function test_stored_generated_image_is_wrapped_instead_of_rendering_with_mpdf(): void
    {
        CvOptionsSettings::fakeDisplaySource(CvOptionsSettings::DISPLAY_GENERATED);
        $path = MaidCvPdfGenerator::STORAGE_DIRECTORY . '/cv.png';
        $storage = Mockery::mock(ManagedFileStorage::class);
        $storage->shouldReceive('exists')->with($path)->andReturn(true);
        $this->app->instance(ManagedFileStorage::class, $storage);

        $maid = $this->maid(['cv_preview' => $path]);
        $generator = Mockery::mock(MaidCvPdfGenerator::class)->makePartial();
        $generator->shouldReceive('renderStoredImageAsPdfContents')->once()->with($path, $maid)->andReturn('%PDF-image');
        $generator->shouldNotReceive('renderPdfContents');
        $generator->shouldNotReceive('generate');

        $this->assertSame('%PDF-image', $generator->shareablePdfContents($maid));
    }

    public function test_stored_generated_pdf_is_used_as_is(): void
    {
        CvOptionsSettings::fakeDisplaySource(CvOptionsSettings::DISPLAY_GENERATED);
        $path = MaidCvPdfGenerator::STORAGE_DIRECTORY . '/cv.pdf';
        $storage = Mockery::mock(ManagedFileStorage::class);
        $storage->shouldReceive('exists')->with($path)->andReturn(true);
        $storage->shouldReceive('contents')->with($path)->andReturn('%PDF-stored');
        $this->app->instance(ManagedFileStorage::class, $storage);

        $generator = Mockery::mock(MaidCvPdfGenerator::class)->makePartial();
        $generator->shouldNotReceive('renderPdfContents');

        $this->assertSame('%PDF-stored', $generator->shareablePdfContents($this->maid(['cv_preview' => $path])));
    }

    public function test_uploaded_cv_is_preferred_when_the_company_shows_uploaded_cvs(): void
    {
        CvOptionsSettings::fakeDisplaySource(CvOptionsSettings::DISPLAY_UPLOADED);
        $uploaded = 'images/Maid/office-cv.jpg';
        $storage = Mockery::mock(ManagedFileStorage::class);
        $storage->shouldReceive('exists')->with($uploaded)->andReturn(true);
        $this->app->instance(ManagedFileStorage::class, $storage);

        $maid = $this->maid(['maid_cv_file' => $uploaded, 'cv_preview' => MaidCvPdfGenerator::STORAGE_DIRECTORY . '/cv.png']);
        $generator = Mockery::mock(MaidCvPdfGenerator::class)->makePartial();
        $generator->shouldReceive('renderStoredImageAsPdfContents')->once()->with($uploaded, $maid)->andReturn('%PDF-uploaded');

        $this->assertSame('%PDF-uploaded', $generator->shareablePdfContents($maid));
    }
}
