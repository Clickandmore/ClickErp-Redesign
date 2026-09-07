<?php

namespace Tests\Unit;

use App\Http\Middleware\AgentLocalizationMiddleware;
use Illuminate\Http\Request;
use Tests\TestCase;

class AgentLocalizationMiddlewareTest extends TestCase
{
    /** @dataProvider languageSwitches */
    public function test_language_switch_redirects_to_selected_locale_and_preserves_filters(string $from, string $to): void
    {
        $session = app('session.store');
        $request = Request::create("http://localhost/{$from}/agent/maids?agent_locale={$to}&search=test&page=2");
        $request->setLaravelSession($session);

        $middleware = new AgentLocalizationMiddleware();
        $response = $middleware->handle($request, function () {
            $this->fail('A language switch must redirect before rendering links with the old route prefix.');
        });

        $this->assertSame("http://localhost/{$to}/agent/maids?search=test&page=2", $response->getTargetUrl());
        $this->assertSame($to, $session->get('agent_locale'));

        $nextPage = Request::create("http://localhost/{$to}/agent/contracts");
        $nextPage->setLaravelSession($session);
        $middleware->handle($nextPage, function () use ($to) {
            $this->assertSame($to, app()->getLocale());

            return response('OK');
        });
        $this->assertSame($to, $session->get('agent_locale'));
    }

    public function languageSwitches(): array
    {
        return [['ar', 'en'], ['en', 'ar']];
    }

    public function test_unprefixed_navigation_uses_the_saved_agent_language(): void
    {
        $session = app('session.store');
        $session->put('agent_locale', 'en');
        $request = Request::create('http://localhost/agent/dashboard');
        $request->setLaravelSession($session);

        $response = (new AgentLocalizationMiddleware())->handle($request, function () {
            $this->fail('Unprefixed navigation must redirect to the saved language.');
        });

        $this->assertSame('http://localhost/en/agent/dashboard', $response->getTargetUrl());
    }
}
