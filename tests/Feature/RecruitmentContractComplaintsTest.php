<?php

namespace Tests\Feature;

use App\Models\auth\Admin;
use App\Models\Contract;
use App\Models\ContractInfo;
use App\Models\ingaz\Complaint;
use App\Models\ingaz\User;
use App\Models\Permission;
use App\NewPermKeys;
use App\PermKeys;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RecruitmentContractComplaintsTest extends TestCase
{
    use DatabaseTransactions;

    private function viewer(bool $canViewComplaints = true): Admin
    {
        $admin = Admin::factory()->create();
        foreach (array_filter([NewPermKeys::REC_CONTRACTS_VIEW, $canViewComplaints ? PermKeys::COMPLAINT_VIEW : null]) as $name) {
            $permission = Permission::withoutGlobalScopes()->updateOrCreate(
                ['name' => $name, 'guard_name' => 'admin'],
                ['is_active' => true, 'display_name' => $name]
            );
            $admin->givePermissionTo($permission);
        }
        return $admin;
    }

    private function contract(): Contract
    {
        $user = User::withoutEvents(fn () => User::create([
            'name' => 'عميل شكاوى العقد', 'phone' => 'test-' . bin2hex(random_bytes(6)),
        ]));
        $contract = Contract::withoutEvents(fn () => Contract::create([
            'user_id' => $user->id, 'type' => 'recruitment', 'status' => 'new',
        ]));
        ContractInfo::withoutEvents(fn () => ContractInfo::create([
            'contract_id' => $contract->id, 'contract_date' => '2026-10-05',
        ]));
        return $contract;
    }

    private function complaint(array $attributes): Complaint
    {
        return Complaint::withoutEvents(fn () => Complaint::create($attributes + [
            'address' => 'الرياض', 'message' => 'وصف الشكوى', 'priority' => 'medium', 'status' => 'pending',
        ]));
    }

    public function test_details_show_all_direct_complaints_but_not_other_contracts_complaints(): void
    {
        $contract = $this->contract();
        $other = $this->contract();
        $first = $this->complaint(['contract_id' => $contract->id, 'subject' => 'شكوى أولى مرتبطة']);
        $second = $this->complaint(['contract_id' => $contract->id, 'subject' => 'شكوى محلولة مرتبطة', 'status' => 'resolved']);
        $this->complaint(['contract_id' => $other->id, 'subject' => 'شكوى عقد آخر']);
        $this->actingAs($this->viewer(), 'admin')->get(route('contracts.details', $contract->id))
            ->assertOk()->assertSee('الشكاوى المرتبطة بالعقد')->assertSee('شكوى أولى مرتبطة')
            ->assertSee('شكوى محلولة مرتبطة')->assertSee('تم الحل')
            ->assertSee(route('complaints.show', $first->id), false)
            ->assertSee(route('complaints.show', $second->id), false)
            ->assertDontSee('شكوى عقد آخر');
    }

    public function test_a_contract_without_complaints_has_an_empty_state(): void
    {
        $contract = $this->contract();
        $this->actingAs($this->viewer(), 'admin')->get(route('contracts.details', $contract->id))
            ->assertOk()->assertSee('لا توجد شكاوى مرتبطة بهذا العقد أو بطلبه.');
    }

    public function test_contract_view_permission_does_not_disclose_complaint_content(): void
    {
        $contract = $this->contract();
        $this->complaint(['contract_id' => $contract->id, 'subject' => 'عنوان شكوى محمي']);
        $this->actingAs($this->viewer(false), 'admin')->get(route('contracts.details', $contract->id))
            ->assertOk()->assertSee('يلزم امتلاك صلاحية عرض الشكاوى')
            ->assertDontSee('عنوان شكوى محمي');
    }

    public function test_order_level_complaints_do_not_include_a_different_explicit_contract(): void
    {
        // No order row is needed to exercise the relationship filter itself.
        $contract = new Contract(['order_id' => 998877]);
        $contract->id = 998876;
        $direct = $this->complaint(['contract_id' => $contract->id]);
        $orderOnly = $this->complaint(['order_id' => $contract->order_id]);
        $this->complaint(['order_id' => $contract->order_id, 'contract_id' => 998875]);
        $this->complaint(['order_id' => 998874]);
        $this->assertEqualsCanonicalizing([$direct->id, $orderOnly->id],
            Complaint::forRecruitmentContract($contract)->pluck('id')->all());
    }
}
