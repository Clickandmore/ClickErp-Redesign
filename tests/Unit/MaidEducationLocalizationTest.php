<?php

namespace Tests\Unit;

use App\Http\Controllers\ingaz\MaidController;
use App\Http\Requests\ingaz\MaidRequest;
use App\Models\ingaz\Maid;
use Tests\TestCase;

class MaidEducationLocalizationTest extends TestCase
{
    public function test_admin_maid_flow_accepts_and_persists_both_education_languages(): void
    {
        $controller = app(MaidController::class);
        $request = new MaidRequest();

        $this->assertContains('education', $controller->postData);
        $this->assertContains('education_en', $controller->postData);
        $this->assertArrayHasKey('education', $request->rules());
        $this->assertArrayHasKey('education_en', $request->rules());
        $this->assertContains('education', (new Maid())->getFillable());
        $this->assertContains('education_en', (new Maid())->getFillable());
    }

    public function test_public_api_resources_expose_the_optional_english_value(): void
    {
        foreach ([
            app_path('Http/Resources/Api/MaidResource.php'),
            app_path('Http/Resources/Api/MaidCardResource.php'),
        ] as $resource) {
            $this->assertStringContainsString("'education' => \$this->education", file_get_contents($resource));
            $this->assertStringContainsString("'education_en' => \$this->education_en", file_get_contents($resource));
        }
    }
}
