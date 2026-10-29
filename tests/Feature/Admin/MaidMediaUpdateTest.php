<?php

namespace Tests\Feature\Admin;

use App\Models\auth\Admin;
use App\Models\ingaz\Maid;
use App\Models\office\Office;
use App\PermKeys;
use App\Services\ManagedFileStorage;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * نافذة «الصور والفيديو» في شاشة العاملة (maids.media.update) بعد نقل منطقها إلى
 * MaidMediaUpdater المشترك مع رابط رفع المساعد الذكي.
 */
class MaidMediaUpdateTest extends TestCase
{
    use DatabaseTransactions;

    private Maid $maid;

    protected function setUp(): void
    {
        parent::setUp();
        $nationality = DB::table('nationalities')->insertGetId(['created_at' => now(), 'updated_at' => now()]);
        $religion = DB::table('religions')->insertGetId(['created_at' => now(), 'updated_at' => now()]);
        $this->maid = Maid::create([
            'name' => 'Media test', 'passport_num' => 'MED-' . uniqid(),
            'nationality_id' => $nationality, 'religion_id' => $religion,
            'age' => 30, 'height' => '', 'weight' => '', 'cv_file' => '', 'image' => 'images/Maid/old.jpg',
            'office_id' => Office::create(['nationality_id' => $nationality, 'status' => 'active'])->id,
            'status' => 'available', 'available' => true, 'is_show' => true, 'maid_accommodation_type' => 1,
        ]);

        $admin = Admin::factory()->create();
        foreach ([PermKeys::MAIDS_VIEW, PermKeys::MAIDS_EDIT] as $name) {
            $admin->givePermissionTo(Permission::findOrCreate($name, 'admin'));
        }
        $this->actingAs($admin, 'admin');
    }

    /** @test */
    public function a_new_photo_replaces_the_old_one_and_deletes_it(): void
    {
        $deleted = [];
        $this->mock(ManagedFileStorage::class, function ($mock) use (&$deleted) {
            $mock->shouldReceive('uploadMaidFile')->andReturn('images/Maid/new.jpg');
            $mock->shouldReceive('delete')->andReturnUsing(function ($path) use (&$deleted) {
                $deleted[] = $path;

                return true;
            });
        });

        $this->post(route('maids.media.update', $this->maid->id), ['image' => UploadedFile::fake()->image('new.jpg')])
            ->assertRedirect()->assertSessionHas('success');

        $this->assertSame('images/Maid/new.jpg', $this->maid->refresh()->image);
        $this->assertSame(['images/Maid/old.jpg'], $deleted);
    }

    /** @test */
    public function nothing_selected_returns_the_media_error_bag(): void
    {
        $this->post(route('maids.media.update', $this->maid->id), [])
            ->assertRedirect()->assertSessionHasErrorsIn('mediaUpdate', 'media');

        $this->assertSame('images/Maid/old.jpg', $this->maid->refresh()->image);
    }
}
