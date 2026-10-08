<?php

namespace Tests\Unit;

use App\Http\Controllers\Agent\DashboardController;
use App\Models\ingaz\Maid;
use App\Models\ingaz\Setting;
use App\Services\ingaz\MaidCvPdfGenerator;
use App\Support\MaidCvTemplateSettings;
use Illuminate\Support\Facades\Schema;
use Mockery;
use ReflectionMethod;
use ReflectionProperty;
use Tests\TestCase;

class AgentMaidCvGenerationTest extends TestCase
{
    protected function tearDown(): void
    {
        MaidCvTemplateSettings::clearCache();
        parent::tearDown();
    }

    /** @dataProvider templateSettings */
    public function test_generation_respects_template_settings(bool $enabled, string $template, bool $shouldGenerate): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        \Illuminate\Support\Facades\DB::purge('sqlite');
        Schema::create('settings', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->boolean('cv_template_enabled');
            $table->text('cv_template_blade');
        });
        $cache = new ReflectionProperty(MaidCvTemplateSettings::class, 'cachedSetting');
        $cache->setAccessible(true);
        $cache->setValue(null, new Setting([
            'cv_template_enabled' => $enabled,
            'cv_template_blade' => $template,
        ]));

        $maid = new Maid(['submission_status' => Maid::SUBMISSION_PENDING, 'is_show' => 0]);
        $generator = Mockery::mock(MaidCvPdfGenerator::class);
        if ($shouldGenerate) {
            $generator->shouldReceive('generate')->once()->with($maid)->andReturn('images/Maid/generated-cvs/test.pdf');
        } else {
            $generator->shouldNotReceive('generate');
        }

        $method = new ReflectionMethod(DashboardController::class, 'generateCvAutomatically');
        $method->setAccessible(true);
        $method->invoke(app(DashboardController::class), $maid, $generator);

        $this->assertSame(Maid::SUBMISSION_PENDING, $maid->submission_status);
        $this->assertEquals(0, $maid->is_show);
    }

    public function templateSettings(): array
    {
        return [
            'enabled template' => [true, '<h1>CV</h1>', true],
            'disabled template' => [false, '<h1>CV</h1>', false],
            'missing template' => [true, '', false],
        ];
    }
}