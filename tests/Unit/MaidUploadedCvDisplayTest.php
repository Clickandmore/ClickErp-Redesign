<?php

namespace Tests\Unit;

use App\Http\Controllers\ingaz\CvOptionsSettingsController;
use App\Http\Requests\ingaz\MaidRequest;
use App\Http\Resources\Api\MaidCardResource;
use App\Models\ingaz\Maid;
use App\Models\ingaz\Setting;
use App\Support\CvOptionsSettings;
use App\Support\MaidFormFieldSettings;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * "السيرة التي يراها العميل": المولّدة من القالب أو ملف السيرة الذي رفعته الشركة (maid_cv_file).
 */
class MaidUploadedCvDisplayTest extends TestCase
{
    use DatabaseTransactions;

    private const UPLOADED = 'images/Maid/office-cv.jpg';
    private const GENERATED = 'images/Maid/generated-cv.png';

    protected function tearDown(): void
    {
        CvOptionsSettings::fakeDisplaySource(null);
        MaidFormFieldSettings::fake(null);
        parent::tearDown();
    }

    public function test_generated_mode_keeps_showing_the_template_cv(): void
    {
        CvOptionsSettings::fakeDisplaySource(CvOptionsSettings::DISPLAY_GENERATED);
        $maid = $this->maid(['maid_cv_file' => self::UPLOADED, 'cv_preview' => self::GENERATED]);

        $this->assertNull($maid->uploadedClientCvPath());
        $this->assertSame(showFile(self::GENERATED), $maid->cvPreviewUrl());
        $this->assertSame(showFile(self::GENERATED), $maid->toArray()['cv_preview']);
    }

    public function test_uploaded_mode_shows_the_company_cv_everywhere_the_client_reads_it(): void
    {
        CvOptionsSettings::fakeDisplaySource(CvOptionsSettings::DISPLAY_UPLOADED);
        $maid = $this->maid(['maid_cv_file' => self::UPLOADED, 'cv_preview' => self::GENERATED]);
        $expected = showFile(self::UPLOADED);

        $this->assertSame($expected, $maid->cvPreviewUrl());
        $this->assertSame($expected, $maid->toArray()['cv_preview']);
        $this->assertSame($expected, $maid->cv_preview_url);
        $this->assertSame($expected, $maid->cv_download_url);
        $this->assertSame($expected, (new MaidCardResource($maid))->toArray(request())['cv_preview']);
    }

    public function test_uploaded_cv_reaches_the_api_even_without_a_generated_cv(): void
    {
        CvOptionsSettings::fakeDisplaySource(CvOptionsSettings::DISPLAY_UPLOADED);
        $maid = $this->maid(['maid_cv_file' => self::UPLOADED, 'cv_preview' => null]);

        $this->assertSame(showFile(self::UPLOADED), $maid->toArray()['cv_preview']);
    }

    public function test_uploaded_mode_falls_back_to_the_generated_cv_when_nothing_was_uploaded(): void
    {
        CvOptionsSettings::fakeDisplaySource(CvOptionsSettings::DISPLAY_UPLOADED);
        $maid = $this->maid(['maid_cv_file' => null, 'cv_preview' => self::GENERATED]);

        $this->assertSame(showFile(self::GENERATED), $maid->cvPreviewUrl());
        $this->assertSame(showFile(self::GENERATED), $maid->toArray()['cv_preview']);
    }

    public function test_site_image_slots_only_take_an_uploaded_cv_that_is_an_image(): void
    {
        CvOptionsSettings::fakeDisplaySource(CvOptionsSettings::DISPLAY_UPLOADED);

        $this->assertSame(self::UPLOADED, $this->maid(['maid_cv_file' => self::UPLOADED])->uploadedClientCvImagePath());
        $this->assertNull($this->maid(['maid_cv_file' => 'images/Maid/office-cv.pdf'])->uploadedClientCvImagePath());
        // ملف PDF يبقى متاحًا للـ API
        $this->assertSame(
            showFile('images/Maid/office-cv.pdf'),
            $this->maid(['maid_cv_file' => 'images/Maid/office-cv.pdf'])->cvPreviewUrl()
        );
    }

    public function test_cv_file_accepts_images_and_pdf_but_not_other_documents(): void
    {
        MaidFormFieldSettings::fake([]);

        $this->assertFalse($this->cvFileValidator(UploadedFile::fake()->image('cv.jpg'))->fails());
        $this->assertFalse($this->cvFileValidator(UploadedFile::fake()->image('cv.png'))->fails());
        $this->assertFalse($this->cvFileValidator(UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf'))->fails());
        $this->assertTrue($this->cvFileValidator(UploadedFile::fake()->create('cv.docx', 100))->fails());
        $this->assertFalse($this->cvFileValidator(null)->fails(), 'optional by default');
    }

    public function test_cv_file_can_be_made_required_from_the_settings(): void
    {
        MaidFormFieldSettings::fake(['maid_cv_file' => MaidFormFieldSettings::REQUIRED]);

        $this->assertTrue($this->cvFileValidator(null)->fails());
        $this->assertFalse($this->cvFileValidator(UploadedFile::fake()->image('cv.jpg'))->fails());
        $this->assertFalse(MaidFormFieldSettings::isRelaxed('maid_cv_file'));
        $this->assertSame(['default', 'hidden', 'required'], MaidFormFieldSettings::allowedStates('maid_cv_file'));
        // باقي الحقول لا تقبل "إلزامي دائمًا"
        $this->assertNotContains('required', MaidFormFieldSettings::allowedStates('height_weight'));
    }

    public function test_settings_page_saves_the_display_source(): void
    {
        Setting::query()->firstOrCreate([]);

        foreach ([CvOptionsSettings::DISPLAY_UPLOADED, CvOptionsSettings::DISPLAY_GENERATED] as $source) {
            $request = Request::create('/settings/cv-options', 'PUT', [
                'cv_site_sort_order' => 'random',
                'cv_display_source' => $source,
                'maid_form_fields' => ['maid_cv_file' => 'required'],
            ]);
            app()->instance('request', $request);
            app(CvOptionsSettingsController::class)->update($request);

            $this->assertSame($source, Setting::query()->value(CvOptionsSettings::DISPLAY_SOURCE_COLUMN));
            $this->assertSame($source, CvOptionsSettings::displaySource());
            $this->assertSame('required', MaidFormFieldSettings::state('maid_cv_file'));
        }
    }

    public function test_external_site_links_stay_signed_and_serve_the_uploaded_cv(): void
    {
        // المواقع الخارجية (مثل زر "عرض السيرة الذاتية" وزر التحميل) تستخدم الرابط الموقّع الثابت.
        Storage::disk('public')->put('images/Maid/qa-uploaded-cv.png', 'PNG-UPLOADED');
        Storage::disk('public')->put('images/Maid/generated-cvs/qa-generated.png', 'PNG-GENERATED');

        try {
            $maid = new Maid();
            $maid->forceFill([
                'name' => 'QA Uploaded CV',
                'passport_num' => 'QA' . substr(uniqid(), -8),
                'gender' => 'female',
                'status' => 'available',
                'available' => 1,
                'is_show' => 1,
                'price' => 0,
                'salary' => 0,
                'cv_file' => '',
                'image' => '',
                'maid_cv_file' => 'images/Maid/qa-uploaded-cv.png',
                'cv_preview' => 'images/Maid/generated-cvs/qa-generated.png',
            ])->save();

            CvOptionsSettings::fakeDisplaySource(CvOptionsSettings::DISPLAY_UPLOADED);
            $view = $maid->cvPreviewUrl();
            $download = $maid->cv_download_url;

            $this->assertStringContainsString('/shared/maids/' . $maid->id . '/cv-preview', $view);
            $this->assertStringContainsString('download=1', $download);
            $this->assertSame('PNG-UPLOADED', $this->get($view)->assertOk()->streamedContent());
            $downloadResponse = $this->get($download)->assertOk();
            $this->assertStringContainsString('attachment', (string) $downloadResponse->headers->get('content-disposition'));
            $this->assertSame('PNG-UPLOADED', $downloadResponse->streamedContent());

            // نفس الرابط يرجع للسيرة المولّدة عند تغيير الإعداد
            CvOptionsSettings::fakeDisplaySource(CvOptionsSettings::DISPLAY_GENERATED);
            $this->assertSame('PNG-GENERATED', $this->get($view)->assertOk()->streamedContent());
        } finally {
            Storage::disk('public')->delete(['images/Maid/qa-uploaded-cv.png', 'images/Maid/generated-cvs/qa-generated.png']);
        }
    }

    private function maid(array $attributes): Maid
    {
        return (new Maid())->setRawAttributes($attributes, true);
    }

    private function cvFileValidator(?UploadedFile $file): \Illuminate\Contracts\Validation\Validator
    {
        $data = array_filter(['maid_cv_file' => $file]);
        $request = MaidRequest::create('/maids', 'POST', [], [], $data);
        $rules = $request->rules();

        return Validator::make($data, ['maid_cv_file' => $rules['maid_cv_file']]);
    }
}
