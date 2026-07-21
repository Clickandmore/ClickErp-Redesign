<?php

namespace Tests\Feature\Security;

use App\Http\Controllers\ingaz\ComplaintController;
use App\Models\ingaz\ComplaintComment;
use App\PermKeys;
use App\Support\AdminRoutePermissionMatrix;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

class ComplaintCommentAuthorizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::put('superadmin.modules.enabled_map', ['branches' => false], 60);
    }

    public function test_complaint_cards_requires_view_or_control_permission(): void
    {
        $this->step('Resolve permissions for complaints.cards.');
        $permissions = AdminRoutePermissionMatrix::resolve('complaints.cards');
        $this->result('Required: ' . implode(', ', $permissions));

        $this->assertSame([PermKeys::COMPLAINT_VIEW, PermKeys::COMPLAINT_CONTROL], $permissions);

        $controllerMiddleware = collect(app(ComplaintController::class)->getMiddleware())
            ->first(fn (array $entry) => in_array('cards', $entry['options']['only'] ?? [], true));

        $this->assertNotNull($controllerMiddleware);
        $this->result('Controller middleware: ' . $controllerMiddleware['middleware']);
        $this->result('PASS: cards are protected by view/control permission.');
    }

    public function test_comment_delete_route_is_named_and_uses_admin_security_middleware(): void
    {
        $this->step('Inspect complaints.comments.destroy route.');
        $route = Route::getRoutes()->getByName('complaints.comments.destroy');

        $this->assertNotNull($route);
        $middleware = $route->gatherMiddleware();
        $this->result('Middleware: ' . implode(', ', $middleware));
        $this->assertContains('auth:admin', $middleware);
        $this->assertContains('admin', $middleware);
        $this->result('PASS: delete route requires an authenticated, active admin.');
    }

    public function test_comment_author_can_delete_their_own_comment(): void
    {
        $this->step('Admin #10 attempts to delete a comment authored by admin #10.');
        $this->mockAdmin(10, false, 1);

        $this->assertTrue($this->canDeleteComment(10));
        $this->result('PASS: comment author is allowed.');
    }

    public function test_complaint_controller_can_delete_another_admins_comment(): void
    {
        $this->step('Admin #20 with complaint_control attempts to delete admin #10 comment.');
        $this->mockAdmin(20, true, 2);

        $this->assertTrue($this->canDeleteComment(10));
        $this->result('PASS: complaint controller is allowed.');
    }

    public function test_another_regular_admin_cannot_delete_the_comment(): void
    {
        $this->step('Admin #20 without complaint_control attempts to delete admin #10 comment.');
        $this->mockAdmin(20, false, 2);

        $this->assertFalse($this->canDeleteComment(10));
        $this->result('PASS: unrelated regular admin is rejected.');
    }

    private function canDeleteComment(int $authorId): bool
    {
        $comment = new ComplaintComment(['added_by' => $authorId]);
        $method = new ReflectionMethod(ComplaintController::class, 'canDeleteComment');
        $method->setAccessible(true);

        return $method->invoke(app(ComplaintController::class), $comment);
    }

    private function mockAdmin(int $id, bool $canControl, int $expectedUserCalls): void
    {
        $admin = new class($id, $canControl) {
            public function __construct(public int $id, private bool $canControl)
            {
            }

            public function can(string $permission): bool
            {
                return $permission === PermKeys::COMPLAINT_CONTROL && $this->canControl;
            }
        };

        $guard = Mockery::mock();
        $guard->shouldReceive('user')->times($expectedUserCalls)->andReturn($admin);
        Auth::shouldReceive('guard')->times($expectedUserCalls)->with('admin')->andReturn($guard);
    }

    private function step(string $message): void
    {
        fwrite(STDOUT, "\n  STEP: {$message}\n");
    }

    private function result(string $message): void
    {
        fwrite(STDOUT, "  RESULT: {$message}\n");
    }
}
