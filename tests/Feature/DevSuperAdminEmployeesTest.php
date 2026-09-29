<?php

namespace Tests\Feature;

use App\Models\RecruitmentOffices;
use App\Models\auth\Admin;
use App\Models\office\AdminOffice;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DevSuperAdminEmployeesTest extends TestCase
{
    use DatabaseTransactions;

    public function test_employees_page_excludes_external_office_only_agents(): void
    {
        $systemUser = Admin::factory()->create([
            'name' => 'System Employee ' . uniqid(),
            'role_id' => null,
        ]);
        $externalAgent = Admin::factory()->create([
            'name' => 'External Agent ' . uniqid(),
            'role_id' => null,
        ]);
        $office = RecruitmentOffices::create([
            'nationality_id' => 1,
            'ar' => ['title' => 'مكتب اختبار ' . uniqid()],
            'en' => ['title' => 'Test Office ' . uniqid()],
        ]);

        AdminOffice::create([
            'admin_id' => $externalAgent->id,
            'office_id' => $office->id,
        ]);

        $this->withSession(['dev_superadmin_authenticated' => true])
            ->get(route('dev-superadmin.employees'))
            ->assertOk()
            ->assertSeeText($systemUser->name)
            ->assertDontSeeText($externalAgent->name);
    }
}
