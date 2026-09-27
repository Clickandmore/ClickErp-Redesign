<?php

namespace Tests\Feature\Admin;

use App\Models\auth\Admin;
use App\Models\ingaz\Maid;
use App\Models\office\Office;
use App\PermKeys;
use App\Services\office\OfficeCvsSuspensionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class MaidVisibilityBatchTest extends TestCase
{
    use DatabaseTransactions;

    private int $nationality;
    private int $religion;
    private Office $office;
    private Admin $actor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->nationality = DB::table('nationalities')->insertGetId(['created_at' => now(), 'updated_at' => now()]);
        $this->religion = DB::table('religions')->insertGetId(['created_at' => now(), 'updated_at' => now()]);
        $this->office = Office::create(['nationality_id' => $this->nationality, 'status' => 'active']);
        $this->actor = Admin::factory()->create();
        foreach ([PermKeys::MAIDS_VIEW, PermKeys::MAIDS_BULK_VISIBILITY] as $name) {
            $this->actor->givePermissionTo(Permission::findOrCreate($name, 'admin'));
        }
        $this->actingAs($this->actor, 'admin');
    }

    private function maid(array $attributes = []): Maid
    {
        return Maid::create(array_merge([
            'name' => 'Visibility test', 'passport_num' => 'VIS-' . uniqid(),
            'nationality_id' => $this->nationality, 'religion_id' => $this->religion,
            'age' => 30, 'height' => '', 'weight' => '', 'cv_file' => '',
            'office_id' => $this->office->id, 'status' => 'available', 'available' => true,
            'is_show' => true, 'maid_accommodation_type' => 1,
        ], $attributes));
    }

    private function filters(array $attributes = []): array
    {
        return array_merge(['is_show' => 0, 'nationality_id' => $this->nationality, 'office_ids' => [$this->office->id], 'statuses' => ['available']], $attributes);
    }

    private function apply(array $filters): array
    {
        $preview = $this->postJson(route('maids.visibility-preview'), $filters)->assertOk()->json();
        return $this->postJson(route('maids.bulk-visibility'), $filters + ['preview_token' => $preview['preview_token']])->assertOk()->json();
    }

    public function test_preview_and_apply_match_filters_and_only_count_changed_cvs(): void
    {
        $visible = $this->maid();
        $hidden = $this->maid(['is_show' => false]);
        $reserved = $this->maid(['status' => 'reserved', 'available' => false]);
        $otherOffice = Office::create(['nationality_id' => $this->nationality, 'status' => 'active']);
        $excluded = $this->maid(['office_id' => $otherOffice->id]);
        $pending = $this->maid(['submission_status' => Maid::SUBMISSION_PENDING]);
        $otherNationality = $this->maid(['nationality_id' => DB::table('nationalities')->insertGetId(['created_at' => now()])]);
        $this->postJson(route('maids.visibility-preview'), $this->filters())->assertOk()->assertJsonPath('count', 1);
        $result = $this->apply($this->filters());
        $this->assertSame(1, $result['updated_count']);
        $this->assertFalse((bool) $visible->fresh()->is_show);
        $this->assertFalse((bool) $hidden->fresh()->is_show);
        foreach ([$reserved, $excluded, $pending, $otherNationality] as $maid) {
            $this->assertTrue((bool) $maid->fresh()->is_show);
        }
        $this->assertSame('available', $visible->fresh()->status);
        $this->postJson(route('maids.visibility-batches.undo', $result['batch_id']))->assertOk()->assertJsonPath('restored_count', 1);
        $this->assertTrue((bool) $visible->fresh()->is_show);
        $this->assertFalse((bool) $hidden->fresh()->is_show);
    }

    public function test_multiple_offices_and_statuses_are_supported(): void
    {
        $otherOffice = Office::create(['nationality_id' => $this->nationality, 'status' => 'active']);
        $this->maid();
        $this->maid(['office_id' => $otherOffice->id, 'status' => 'reserved', 'available' => false]);
        $result = $this->apply($this->filters(['office_ids' => [$this->office->id, $otherOffice->id], 'statuses' => ['available', 'reserved']]));
        $this->assertSame(2, $result['updated_count']);
    }

    public function test_contracts_are_excluded_and_new_contracts_block_undo(): void
    {
        $contracted = $this->maid();
        DB::table('contracts')->insert(['maid_id' => $contracted->id, 'status' => 'completed', 'created_at' => now(), 'updated_at' => now()]);
        $eligible = $this->maid();
        $this->postJson(route('maids.visibility-preview'), $this->filters())->assertOk()->assertJsonPath('count', 1);
        $result = $this->apply($this->filters());
        $this->assertTrue((bool) $contracted->fresh()->is_show);
        DB::table('contracts')->insert(['maid_id' => $eligible->id, 'status' => 'new', 'created_at' => now(), 'updated_at' => now()]);
        $this->postJson(route('maids.visibility-batches.undo', $result['batch_id']))->assertOk()->assertJsonPath('restored_count', 0);
        $this->assertFalse((bool) $eligible->fresh()->is_show);
    }

    public function test_older_batch_cannot_undo_a_later_hide_operation(): void
    {
        $maid = $this->maid();
        $first = $this->apply($this->filters());
        $this->apply($this->filters(['is_show' => 1]));
        $latest = $this->apply($this->filters());
        $this->postJson(route('maids.visibility-batches.undo', $first['batch_id']))->assertOk()->assertJsonPath('restored_count', 0);
        $this->assertFalse((bool) $maid->fresh()->is_show);
        $this->postJson(route('maids.visibility-batches.undo', $latest['batch_id']))->assertOk()->assertJsonPath('restored_count', 1);
    }

    public function test_stale_preview_is_rejected_without_partial_changes(): void
    {
        $maid = $this->maid();
        $preview = $this->postJson(route('maids.visibility-preview'), $this->filters())->assertOk()->json();
        $newMaid = $this->maid();
        $this->postJson(route('maids.bulk-visibility'), $this->filters() + ['preview_token' => $preview['preview_token']])->assertStatus(409);
        $this->assertTrue((bool) $maid->fresh()->is_show);
        $this->assertTrue((bool) $newMaid->fresh()->is_show);
    }

    public function test_undo_skips_later_changes_and_new_cvs_and_cannot_be_repeated(): void
    {
        $unchanged = $this->maid();
        $changed = $this->maid();
        $result = $this->apply($this->filters());
        $changed->refresh()->update(['name' => 'Edited after hiding']);
        $newMaid = $this->maid(['is_show' => false]);
        $this->postJson(route('maids.visibility-batches.undo', $result['batch_id']))->assertOk()->assertJsonPath('restored_count', 1)->assertJsonPath('skipped_count', 1);
        $this->assertTrue((bool) $unchanged->fresh()->is_show);
        $this->assertFalse((bool) $changed->fresh()->is_show);
        $this->assertFalse((bool) $newMaid->fresh()->is_show);
        $this->postJson(route('maids.visibility-batches.undo', $result['batch_id']))->assertStatus(422);
    }

    public function test_show_and_undo_do_not_override_office_suspension(): void
    {
        $maid = $this->maid();
        $result = $this->apply($this->filters());
        app(OfficeCvsSuspensionService::class)->suspend($this->office);
        $this->postJson(route('maids.visibility-preview'), $this->filters(['is_show' => 1]))->assertOk()->assertJsonPath('count', 0);
        $this->postJson(route('maids.visibility-batches.undo', $result['batch_id']))->assertOk()->assertJsonPath('restored_count', 0);
        $this->assertFalse((bool) $maid->fresh()->is_show);
    }

    public function test_permission_is_required_and_old_unfiltered_request_is_rejected(): void
    {
        $this->postJson(route('maids.bulk-visibility'), ['is_show' => 0])->assertStatus(422);
        $this->actor->revokePermissionTo(PermKeys::MAIDS_BULK_VISIBILITY);
        $this->postJson(route('maids.visibility-preview'), $this->filters())->assertForbidden();
        $this->postJson(route('maids.bulk-visibility'), $this->filters())->assertForbidden();
        $this->getJson(route('maids.visibility-batches'))->assertForbidden();
        $this->postJson(route('maids.visibility-batches.undo', 'fake'))->assertForbidden();
    }

    public function test_history_and_undo_are_limited_to_the_actor(): void
    {
        $this->maid();
        $batch = $this->apply($this->filters());
        $other = Admin::factory()->create();
        $other->givePermissionTo(PermKeys::MAIDS_BULK_VISIBILITY);
        $this->actingAs($other, 'admin');
        $this->getJson(route('maids.visibility-batches'))->assertOk()->assertJsonCount(0, 'batches');
        $this->postJson(route('maids.visibility-batches.undo', $batch['batch_id']))->assertNotFound();
    }

    public function test_modal_is_rendered_for_the_dedicated_permission(): void
    {
        $this->get(route('maids.index'))->assertOk()->assertSee('id="bulkVisibilityModal"', false)->assertSee('إظهار/إخفاء سير ذاتية');
        $this->actor->revokePermissionTo(PermKeys::MAIDS_BULK_VISIBILITY);
        $this->get(route('maids.index'))->assertOk()->assertDontSee('id="bulkVisibilityModal"', false);
    }
}
