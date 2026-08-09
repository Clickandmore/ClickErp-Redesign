<?php

namespace Tests\Unit;

use App\Http\Middleware\TrackWebsiteVisitor;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class TrackWebsiteVisitorTest extends TestCase
{
    public function test_legacy_tracking_is_disabled_without_an_explicit_opt_in(): void
    {
        config()->set('website-visitors.legacy_internal_tracking', false);
        $request = Request::create('/public-page', 'GET');
        $nextWasCalled = false;

        $response = (new TrackWebsiteVisitor())->handle(
            $request,
            function () use (&$nextWasCalled) {
                $nextWasCalled = true;

                return new Response('<html></html>', 200, ['Content-Type' => 'text/html']);
            }
        );

        $this->assertTrue($nextWasCalled);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertFalse($request->hasSession());
    }
}
