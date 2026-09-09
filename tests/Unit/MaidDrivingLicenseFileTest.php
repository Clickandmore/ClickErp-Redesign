<?php

namespace Tests\Unit;

use App\Http\Actions\ingaz\MaidAction;
use App\Http\Controllers\ingaz\MaidController;
use App\Http\Requests\ingaz\MaidRequest;
use App\Models\ingaz\JobType;
use App\Models\ingaz\Maid;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * مرفق رخصة القيادة (اختياري) — يُرفع فقط مع "يحمل رخصة = نعم" ويُعرض في الموقع الخارجي مكان QR.
 */
class MaidDrivingLicenseFileTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_maid_flow_accepts_the_optional_driving_license_attachment(): void
    {
        $controller = app(MaidController::class);
        $request = new MaidRequest();

        $this->assertContains('driving_license_file', $controller->postData);
        $this->assertArrayHasKey('driving_license_file', $request->rules());
        $this->assertStringContainsString('nullable', $request->rules()['driving_license_file']);
        $this->assertContains('driving_license_file', (new Maid())->getFillable());
        $this->assertTrue(Schema::hasColumn('maids', 'driving_license_file'));
    }

    public function test_public_api_resources_expose_the_attachment_url(): void
    {
        foreach ([
            app_path('Http/Resources/Api/MaidResource.php'),
            app_path('Http/Resources/Api/MaidCardResource.php'),
        ] as $resource) {
            $this->assertStringContainsString("'driving_license_file' =>", file_get_contents($resource));
        }

        $maid = new Maid(['has_driving_license' => true, 'driving_license_file' => 'images/Maid/license.png']);
        $this->assertSame('images/Maid/license.png', $maid->toArray()['driving_license_file']);
    }

    public function test_attachment_is_cleared_when_the_worker_has_no_license(): void
    {
        $maid = $this->makeMaleMaidWithLicense();
        $maid->has_driving_license = false;
        $maid->save();

        $this->assertNull($maid->fresh()->driving_license_file);

        $maid = $this->makeMaleMaidWithLicense();
        $maid->gender = 'female';
        $maid->save();

        $this->assertNull($maid->fresh()->has_driving_license);
        $this->assertNull($maid->fresh()->driving_license_file);
    }

    public function test_update_removes_the_old_attachment_when_the_answer_changes_to_no(): void
    {
        Storage::fake('public');
        Storage::fake('r2_runtime'); // يغطي حالة تفعيل R2 في إعدادات قاعدة بيانات التطوير
        $maid = $this->makeMaleMaidWithLicense();

        $action = app(MaidAction::class);
        $request = Request::create('/', 'POST', ['has_driving_license' => '0', 'gender' => 'male']);
        $updated = $action->updateMaid($maid->id, ['has_driving_license' => '0', 'gender' => 'male'], $request);

        $this->assertNull($updated->fresh()->driving_license_file);
    }

    public function test_update_stores_a_new_attachment_when_the_worker_has_a_license(): void
    {
        Storage::fake('public');
        Storage::fake('r2_runtime'); // يغطي حالة تفعيل R2 في إعدادات قاعدة بيانات التطوير
        $maid = $this->makeMaleMaidWithLicense(null);

        $file = UploadedFile::fake()->image('license.png');
        $action = app(MaidAction::class);
        $request = Request::create('/', 'POST', ['has_driving_license' => '1', 'gender' => 'male']);
        $updated = $action->updateMaid(
            $maid->id,
            ['has_driving_license' => '1', 'gender' => 'male', 'driving_license_file' => $file],
            $request
        );

        $this->assertNotEmpty($updated->fresh()->driving_license_file);
    }

    private function makeMaleMaidWithLicense(?string $file = 'images/Maid/license.png'): Maid
    {
        $maid = new Maid();
        $maid->forceFill([
            'name' => 'Test Worker',
            'passport_num' => 'P' . uniqid(),
            'gender' => 'male',
            'has_driving_license' => true,
            'driving_license_file' => $file,
            'status' => 'available',
            'available' => 1,
            'is_show' => 1,
            'price' => 0,
            'salary' => 0,
            'age' => 0,
            'cv_file' => '',
            'image' => '',
        ]);
        $maid->save();

        // updateMaid يشترط وجود نوع وظيفي واحد على الأقل.
        $jobType = JobType::create(['ar' => ['title' => 'Test'], 'en' => ['title' => 'Test']]);
        $maid->jobTypes()->attach($jobType->id);

        return $maid->fresh();
    }
}
