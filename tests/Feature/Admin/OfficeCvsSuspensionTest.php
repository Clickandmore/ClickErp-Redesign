<?php

namespace Tests\Feature\Admin;

use App\Models\auth\Admin;
use App\Models\ingaz\Maid;
use App\Models\office\Office;
use App\PermKeys;
use App\Services\office\OfficeCvsSuspensionService;
use App\Support\AdminRoutePermissionMatrix;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * تعليق السير الذاتية الخاصة بمكتب خارجي.
 *
 * التعليق يخفي كل سير المكتب عن الموقع والتطبيق، وفك التعليق يعيد فقط السير
 * التي كانت ظاهرة قبله — سيرة أخفاها موظف عمداً يجب ألا يعيدها فك التعليق.
 * الزر محمي بصلاحية مستقلة (office_suspend_cvs) لا تُمنح ضمن إدارة المكاتب.
 */
class OfficeCvsSuspensionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_matrix_gates_the_button_with_its_own_permission(): void
    {
        $this->assertSame(
            [PermKeys::OFFICE_SUSPEND_CVS],
            AdminRoutePermissionMatrix::resolve('offices.cvs-suspension.update', Request::create('/')),
            'زر تعليق السير يجب أن يبقى بصلاحية مستقلة، لا ضمن صلاحيات إدارة المكاتب.'
        );
    }

    public function test_office_control_alone_cannot_suspend_the_office_cvs(): void
    {
        $office = $this->makeOffice();
        $actor = $this->actorWith([PermKeys::OFFICE_VIEW, PermKeys::OFFICE_CONTROL]);

        $this->actingAs($actor, 'admin')
            ->post(route('offices.cvs-suspension.update', $office->id))
            ->assertForbidden();

        $this->assertNull($office->fresh()->cvs_suspended_at);
    }

    public function test_the_dedicated_permission_suspends_and_resumes_the_office_cvs(): void
    {
        $office = $this->makeOffice();
        $visible = $this->makeMaid($office, true);
        $hiddenOnPurpose = $this->makeMaid($office, false);
        $actor = $this->actorWith([PermKeys::OFFICE_SUSPEND_CVS]);

        $this->actingAs($actor, 'admin')
            ->post(route('offices.cvs-suspension.update', $office->id))
            ->assertOk()
            ->assertJsonPath('data.cvs_suspended', true);

        $this->assertNotNull($office->fresh()->cvs_suspended_at);
        $this->assertFalse((bool) $visible->fresh()->is_show);
        $this->assertFalse((bool) $hiddenOnPurpose->fresh()->is_show);

        $this->actingAs($actor, 'admin')
            ->post(route('offices.cvs-suspension.update', $office->id))
            ->assertOk()
            ->assertJsonPath('data.cvs_suspended', false);

        $this->assertNull($office->fresh()->cvs_suspended_at);
        $this->assertTrue((bool) $visible->fresh()->is_show);
        $this->assertFalse(
            (bool) $hiddenOnPurpose->fresh()->is_show,
            'فك التعليق أعاد إظهار سيرة كانت مخفية عمداً قبل التعليق.'
        );
    }

    public function test_a_cv_added_while_the_office_is_suspended_stays_hidden_then_returns(): void
    {
        $office = $this->makeOffice();
        $service = app(OfficeCvsSuspensionService::class);

        $service->suspend($office);

        $newMaid = $this->makeMaid($office, true);

        $this->assertFalse(
            (bool) $newMaid->fresh()->is_show,
            'سيرة أُضيفت لمكتب معلّق ظهرت على الموقع رغم التعليق.'
        );

        $service->resume($office->fresh());

        $this->assertTrue((bool) $newMaid->fresh()->is_show);
    }

    public function test_the_button_appears_in_the_office_list_only_for_the_dedicated_permission(): void
    {
        $office = $this->makeOffice();

        $withPermission = $this->actorWith([PermKeys::OFFICE_VIEW, PermKeys::OFFICE_SUSPEND_CVS]);
        $actions = $this->officeRowActions($withPermission, $office);

        $this->assertStringContainsString('toggleOfficeCvsSuspension', $actions);
        $this->assertStringContainsString('data-suspended="0"', $actions);

        $withoutPermission = $this->actorWith([PermKeys::OFFICE_VIEW, PermKeys::OFFICE_CONTROL]);

        $this->assertStringNotContainsString(
            'toggleOfficeCvsSuspension',
            $this->officeRowActions($withoutPermission, $office),
            'زر تعليق السير ظهر لمن لا يملك الصلاحية المستقلة.'
        );
    }

    public function test_suspension_does_not_touch_the_cvs_of_another_office(): void
    {
        $office = $this->makeOffice();
        $otherOffice = $this->makeOffice();
        $otherMaid = $this->makeMaid($otherOffice, true);

        app(OfficeCvsSuspensionService::class)->suspend($office);

        $this->assertTrue((bool) $otherMaid->fresh()->is_show);
        $this->assertNull($otherOffice->fresh()->cvs_suspended_at);
    }

    /**
     * عمود الإجراءات لصف هذا المكتب كما يراه المستخدم في جدول الوكلاء الخارجيين.
     */
    private function officeRowActions(Admin $actor, Office $office): string
    {
        // الشاشة تُرجع الجدول كـ JSON فقط لطلب ajax، ويحدده رأس X-Requested-With.
        $payload = $this->actingAs($actor, 'admin')
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('offices.index'))
            ->assertOk()
            ->json('data');

        $row = collect($payload)->firstWhere('id', $office->id);

        $this->assertNotNull($row, 'المكتب غير موجود في جدول الوكلاء الخارجيين.');

        return (string) ($row['actions'] ?? '');
    }

    private function actorWith(array $permissions): Admin
    {
        foreach ([PermKeys::OFFICE_VIEW, PermKeys::OFFICE_CONTROL, PermKeys::OFFICE_SUSPEND_CVS] as $name) {
            Permission::findOrCreate($name, 'admin');
        }

        $actor = Admin::factory()->create();

        if ($permissions !== []) {
            $actor->givePermissionTo($permissions);
        }

        return $actor;
    }

    private function makeOffice(): Office
    {
        $office = Office::query()->create([
            'nationality_id' => $this->makeNationality(),
            'status' => 'active',
        ]);

        return Office::withoutBranchScope()->findOrFail($office->id);
    }

    private function makeMaid(Office $office, bool $isShow): Maid
    {
        $maid = Maid::query()->create([
            'name' => 'Office CV ' . uniqid(),
            'passport_num' => 'OCS-' . str_replace('.', '', uniqid('', true)),
            'nationality_id' => $this->makeNationality(),
            'religion_id' => $this->makeReligion(),
            'age' => 30,
            'cv_file' => '',
            'height' => '',
            'weight' => '',
            'status' => 'available',
            'available' => true,
            'is_show' => $isShow,
            'office_id' => $office->id,
        ]);

        return Maid::withoutBranchScope()->findOrFail($maid->id);
    }

    private function makeNationality(): int
    {
        return DB::table('nationalities')->insertGetId([
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeReligion(): int
    {
        return DB::table('religions')->insertGetId([
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
