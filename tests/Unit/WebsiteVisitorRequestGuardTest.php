<?php

namespace Tests\Unit;

use App\Support\WebsiteVisitorRequestGuard;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class WebsiteVisitorRequestGuardTest extends TestCase
{
    /** @dataProvider automatedUserAgents */
    public function test_it_rejects_automated_user_agents(string $userAgent): void
    {
        $request = Request::create('/heartbeat', 'POST', [], [], [], [
            'HTTP_USER_AGENT' => $userAgent,
        ]);

        try {
            app(WebsiteVisitorRequestGuard::class)->ensureHumanBrowser($request);
            $this->fail('The automated request was not rejected.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_it_accepts_a_regular_browser_user_agent(): void
    {
        $request = Request::create('/heartbeat', 'POST', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/126.0.0.0 Safari/537.36',
        ]);

        app(WebsiteVisitorRequestGuard::class)->ensureHumanBrowser($request);

        $this->addToAssertionCount(1);
    }

    public function automatedUserAgents(): array
    {
        return [
            'empty' => [''],
            'search crawler' => ['Mozilla/5.0 (compatible; Googlebot/2.1)'],
            'command line client' => ['curl/8.5.0'],
            'headless browser' => ['Mozilla/5.0 HeadlessChrome/126.0.0.0'],
        ];
    }
}
