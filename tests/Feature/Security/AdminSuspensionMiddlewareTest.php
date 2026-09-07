<?php

namespace Tests\Feature\Security;

use App\Http\Middleware\Admin;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Auth;
use Mockery;
use Tests\TestCase;

class AdminSuspensionMiddlewareTest extends TestCase
{
    public function test_suspended_admin_is_logged_out_immediately_even_when_session_check_is_fresh(): void
    {
        $request = Request::create('/dashboard', 'GET');
        $session = new Store('testing', new ArraySessionHandler(120));
        $session->start();
        $session->put('admin_session_checked_at', time());
        $session->put('sensitive_value', 'must-be-removed');
        $request->setLaravelSession($session);

        $guard = Mockery::mock();
        $guard->shouldReceive('check')->once()->andReturnTrue();
        $guard->shouldReceive('user')->once()->andReturn((object) ['status' => 'suspended']);
        $guard->shouldReceive('logout')->once();

        Auth::shouldReceive('shouldUse')->once()->with('admin');
        Auth::shouldReceive('guard')->times(3)->with('admin')->andReturn($guard);

        $nextWasCalled = false;
        $response = app(Admin::class)->handle($request, function () use (&$nextWasCalled) {
            $nextWasCalled = true;

            return response('unexpected');
        });

        $this->assertFalse($nextWasCalled);
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(route('admin.form.login'), $response->headers->get('Location'));
        $this->assertFalse($session->has('sensitive_value'));
    }

    public function test_active_admin_can_continue_to_the_requested_page(): void
    {
        $request = Request::create('/dashboard', 'GET');
        $session = new Store('testing', new ArraySessionHandler(120));
        $session->start();
        $session->put('admin_session_checked_at', time());
        $request->setLaravelSession($session);

        $guard = Mockery::mock();
        $guard->shouldReceive('check')->once()->andReturnTrue();
        $guard->shouldReceive('user')->once()->andReturn((object) [
            'status' => 'active',
            'current_session_id' => null,
        ]);

        Auth::shouldReceive('shouldUse')->once()->with('admin');
        Auth::shouldReceive('guard')->twice()->with('admin')->andReturn($guard);

        $response = app(Admin::class)->handle($request, fn () => response('allowed'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('allowed', $response->getContent());
    }
}
