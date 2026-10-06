<?php

namespace Tests\Feature\Admin;

use App\Models\auth\Admin;
use App\Models\ingaz\Maid;
use App\Models\office\Office;
use App\PermKeys;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class MaidSubmissionDetailsReviewTest extends TestCase
{
    use DatabaseTransactions;

    private Admin $actor;
    private Office $office;
    private int $nationality;
    private int $religion;

    protected function setUp(): void
    {
        parent::setUp();
        $this->nationality = DB::table('nationalities')->insertGetId(['created_at' => now(), 'updated_at' => now()]);
        $this->religion = DB::table('religions')->insertGetId(['created_at' => now(), 'updated_at' => now()]);
        $this->office = Office::create(['nationality_id' => $this->nationality, 'status' => 'active']);
        $this->actor = Admin::factory()->create();
        foreach ([PermKeys::MAIDS_VIEW, PermKeys::MAIDS_EDIT] as $name) {
            $this->actor->givePermissionTo(Permission::findOrCreate($name, 'admin'));
        }
        $this->actingAs($this->actor, 'admin');
    }

    private function maid(array $attributes = []): Maid
    {
        return Maid::create(array_merge([
            'name' => 'Submission review test', 'passport_num' => 'SUB-' . uniqid(),
            'nationality_id' => $this->nationality, 'religion_id' => $this->religion,
            'age' => 30, 'height' => '', 'weight' => '', 'cv_file' => '',
            'office_id' => $this->office->id, 'status' => 'available', 'available' => true,
            'is_show' => false, 'maid_accommodation_type' => 1,
            'submission_status' => Maid::SUBMISSION_PENDING,
        ], $attributes));
    }

    public function test_pending_submission_details_show_review_actions(): void
    {
        $maid = $this->maid();

        $this->get(route('showMaidDetails', $maid->id))
            ->assertOk()
            ->assertSee('id="approveSubmissionBtn"', false)
            ->assertSee(route('maids.approve-submission', $maid->id), false)
            ->assertSee('id="rejectSubmissionModal"', false);
    }

    public function test_back_button_returns_to_pending_submissions(): void
    {
        $pending = $this->maid();
        $this->get(route('showMaidDetails', $pending->id))
            ->assertOk()
            ->assertSee('href="' . route('maids.pending-submissions') . '" class="btn btn-outline-secondary"', false);

        // طلب تغيير حالة يفتح سيرة مقبولة من شاشة المراجعة.
        $approved = $this->maid(['submission_status' => Maid::SUBMISSION_APPROVED, 'is_show' => true]);
        $this->get(route('showMaidDetails', ['id' => $approved->id, 'from' => 'pending-submissions']))
            ->assertOk()
            ->assertSee('href="' . route('maids.pending-submissions') . '" class="btn btn-outline-secondary"', false);
        $this->get(route('showMaidDetails', $approved->id))
            ->assertOk()
            ->assertSee('href="' . route('maids.index') . '" class="btn btn-outline-secondary"', false);
    }

    public function test_review_actions_are_hidden_without_edit_permission_or_when_not_pending(): void
    {
        $approved = $this->maid(['submission_status' => Maid::SUBMISSION_APPROVED, 'is_show' => true]);
        $this->get(route('showMaidDetails', $approved->id))
            ->assertOk()
            ->assertDontSee('id="submissionReviewBar"', false);

        $pending = $this->maid();
        $this->actor->revokePermissionTo(PermKeys::MAIDS_EDIT);
        $this->get(route('showMaidDetails', $pending->id))
            ->assertOk()
            ->assertSee('id="submissionReviewBar"', false)
            ->assertDontSee('id="approveSubmissionBtn"', false);
    }
}
