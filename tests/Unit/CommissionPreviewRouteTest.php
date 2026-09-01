<?php

namespace Tests\Unit;

use Tests\TestCase;

class CommissionPreviewRouteTest extends TestCase
{
    public function test_preview_route_accepts_direct_get_and_form_post(): void
    {
        $route = app('router')->getRoutes()->getByName('commissions.marketers.preview');

        $this->assertNotNull($route);
        $this->assertContains('GET', $route->methods());
        $this->assertContains('POST', $route->methods());
    }
}
