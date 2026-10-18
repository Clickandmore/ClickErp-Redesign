<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\ApiFastNewController;
use App\Models\ingaz\FastNew;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FastNewsVisibilityTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_hide_and_show_news_and_api_returns_only_visible_items(): void
    {
        $this->withoutMiddleware();
        $data = ['title:ar' => 'خبر تجريبي', 'title:en' => 'Test news', 'is_visible' => '0'];
        $this->postJson(route('fast-news.store'), $data)->assertSuccessful();
        $news = FastNew::query()->latest('id')->firstOrFail();
        $this->assertFalse($news->is_visible);
        $ids = fn () => collect(app(ApiFastNewController::class)->index()->getData(true)['data'])->pluck('id');
        $this->assertFalse($ids()->contains($news->id));

        $data['is_visible'] = '1';
        $this->putJson(route('fast-news.update', $news->id), $data)->assertSuccessful();
        $this->assertTrue($news->fresh()->is_visible);
        $this->assertTrue($ids()->contains($news->id));

        $data['is_visible'] = '0';
        $this->putJson(route('fast-news.update', $news->id), $data)->assertSuccessful();
        $this->assertFalse($ids()->contains($news->id));
        $this->assertDatabaseHas('fast_news', ['id' => $news->id, 'is_visible' => false]);
    }

    public function test_toggle_visibility_flips_the_flag_without_touching_other_fields(): void
    {
        $news = FastNew::query()->create(['ar' => ['title' => 'خبر'], 'en' => ['title' => 'News']]);
        $route = route('fast-news.toggle-visibility', $news->id);

        $this->withoutMiddleware()->patchJson($route)
            ->assertSuccessful()
            ->assertJsonPath('data.is_visible', false);
        $this->assertFalse($news->fresh()->is_visible);

        $this->withoutMiddleware()->patchJson($route)
            ->assertSuccessful()
            ->assertJsonPath('data.is_visible', true);
        $this->assertTrue($news->fresh()->is_visible);
        $this->assertSame('خبر', $news->fresh()->translate('ar')->title);
    }

    public function test_news_defaults_to_visible_and_rejects_invalid_visibility(): void
    {
        $news = FastNew::query()->create(['ar' => ['title' => 'خبر'], 'en' => ['title' => 'News']]);
        $this->assertTrue($news->fresh()->is_visible);
        $this->withoutMiddleware()->putJson(route('fast-news.update', $news->id), [
            'title:ar' => 'خبر', 'title:en' => 'News', 'is_visible' => 'invalid',
        ])->assertUnprocessable()->assertJsonValidationErrors('is_visible');
        $this->assertTrue($news->fresh()->is_visible);
    }
}
