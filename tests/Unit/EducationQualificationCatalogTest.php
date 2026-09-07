<?php

namespace Tests\Unit;

use App\Models\ingaz\EducationQualification;
use App\Models\ingaz\Maid;
use App\Support\EducationQualificationCatalog;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EducationQualificationCatalogTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        view()->share('errors', new \Illuminate\Support\ViewErrorBag());
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        require_once database_path('migrations/2026_09_06_120000_create_education_qualifications_table.php');
        (new \CreateEducationQualificationsTable())->up();
        $visibilityMigration = require database_path('migrations/2026_09_06_130000_add_visibility_to_education_qualifications.php');
        $visibilityMigration->up();
    }

    public function test_selection_in_either_language_resolves_both_values(): void
    {
        $qualification = EducationQualification::first();
        $expected = ['education' => $qualification->title_ar, 'education_en' => $qualification->title_en];
        $this->assertEquals($expected, EducationQualificationCatalog::normalize(['education' => $qualification->title_ar]));
        $this->assertEquals($expected, EducationQualificationCatalog::normalize(['education_en' => $qualification->title_en]));
    }

    public function test_new_and_edited_qualifications_are_available_to_forms(): void
    {
        $qualification = EducationQualification::create(['title_ar' => 'مؤهل تجريبي', 'title_en' => 'Test qualification']);
        $qualification->update(['title_en' => 'Updated qualification']);
        $this->assertSame('Updated qualification', EducationQualificationCatalog::normalize(['education' => 'مؤهل تجريبي'])['education_en']);
        $html = view('shared.education-selects', ['educationMaid' => new Maid(['education' => 'مؤهل تجريبي'])])->render();
        $this->assertStringContainsString('Updated qualification', $html);
        $this->assertSame(2, preg_match_all('/<option[^>]* selected>/', $html));
    }

    public function test_legacy_values_remain_available_when_editing(): void
    {
        $legacy = ['education' => 'مؤهل قديم', 'education_en' => 'Legacy qualification'];
        $this->assertSame($legacy, EducationQualificationCatalog::normalize($legacy));
        $html = view('shared.education-selects', ['educationMaid' => new Maid($legacy)])->render();
        $this->assertStringContainsString('Legacy qualification', $html);
        $this->assertSame(2, substr_count($html, 'data-qualification="legacy" selected'));
    }

    public function test_management_routes_require_cv_settings_permission(): void
    {
        foreach (['index', 'create', 'store', 'edit', 'update', 'destroy', 'toggle-visibility'] as $action) {
            $route = app('router')->getRoutes()->getByName('education-qualifications.' . $action);
            $this->assertNotNull($route);
            $this->assertContains('permission:' . \App\PermKeys::SETTINGS_CV_OPTIONS . ',admin', $route->gatherMiddleware());
        }
    }

    public function test_visibility_and_deletion_preserve_existing_cv_values(): void
    {
        $qualification = EducationQualification::create(['title_ar' => 'مؤهل خاص', 'title_en' => 'Special qualification']);
        $qualification->refresh();
        $maid = new Maid(['education' => $qualification->title_ar, 'education_en' => $qualification->title_en]);
        $controller = app(\App\Http\Controllers\ingaz\EducationQualificationController::class);

        $controller->toggleVisibility($qualification);
        $this->assertFalse($qualification->fresh()->is_active);
        $this->assertStringNotContainsString('Special qualification', view('shared.education-selects', ['educationMaid' => null])->render());
        $this->assertStringContainsString('Special qualification', view('shared.education-selects', ['educationMaid' => $maid])->render());

        $controller->toggleVisibility($qualification);
        $this->assertTrue($qualification->fresh()->is_active);
        $this->assertStringContainsString('Special qualification', view('shared.education-selects', ['educationMaid' => null])->render());

        $controller->destroy($qualification);
        $this->assertNull(EducationQualification::find($qualification->id));
        $this->assertStringNotContainsString('Special qualification', view('shared.education-selects', ['educationMaid' => null])->render());
        $this->assertStringContainsString('Special qualification', view('shared.education-selects', ['educationMaid' => $maid])->render());
    }
}
