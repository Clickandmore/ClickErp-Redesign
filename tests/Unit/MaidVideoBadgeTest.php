<?php

namespace Tests\Unit;

use App\Http\Controllers\ingaz\MaidController;
use App\Models\ingaz\Maid;
use ReflectionClass;
use Tests\TestCase;

class MaidVideoBadgeTest extends TestCase
{
    public function test_r2_video_badge_never_exposes_the_internal_pointer(): void
    {
        $maid = new Maid();
        $maid->setRawAttributes([
            'id' => 3492,
            'video' => 'r2://alrafedain/images/Maid/example.mp4',
            'video_link' => null,
        ], true);
        $maid->exists = true;

        $controller = (new ReflectionClass(MaidController::class))->newInstanceWithoutConstructor();
        $method = (new ReflectionClass(MaidController::class))->getMethod('maidVideoBadge');
        $method->setAccessible(true);
        $html = $method->invoke($controller, $maid);

        $this->assertStringContainsString('/admin/maids/3492/video', $html);
        $this->assertStringNotContainsString('r2://', $html);
    }

    public function test_external_video_link_remains_unchanged(): void
    {
        $maid = new Maid();
        $maid->setRawAttributes([
            'id' => 3492,
            'video' => null,
            'video_link' => 'https://www.youtube.com/watch?v=example',
        ], true);

        $controller = (new ReflectionClass(MaidController::class))->newInstanceWithoutConstructor();
        $method = (new ReflectionClass(MaidController::class))->getMethod('maidVideoBadge');
        $method->setAccessible(true);
        $html = $method->invoke($controller, $maid);

        $this->assertStringContainsString('https://www.youtube.com/watch?v=example', $html);
    }
}
