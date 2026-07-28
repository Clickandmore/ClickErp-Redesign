<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\admin\Controller;
use App\Http\Requests\ingaz\HomeHeroSectionRequest;
use App\Models\HomeHeroSection;
use App\PermKeys;
use App\Support\AdminRoutePermissionMatrix;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HomeHeroSectionController extends Controller
{
    public function edit()
    {
        return view('admin.ingaz.home_hero.edit', [
            'section' => HomeHeroSection::with('images')->first(),
        ]);
    }

    public function update(HomeHeroSectionRequest $request)
    {
        $section = HomeHeroSection::with('images')->first();
        $this->authorizeRequestedChanges($request, $section);
        $this->validateMediaState($request, $section);

        DB::transaction(function () use ($request, $section) {
            $section ??= new HomeHeroSection();
            $section->is_active = $request->boolean('is_active');
            $section->video_type = $request->input('video_type');

            foreach (config('translatable.locales') as $locale) {
                $translation = $section->translateOrNew($locale);
                $translation->title = $request->input("title:$locale");
                $translation->subtitle = $request->input("subtitle:$locale");
                $translation->video_title = $request->input("video_title:$locale");
            }

            $this->syncVideo($section, $request);
            $section->save();
            $this->deleteImages($section, $request->input('delete_image_ids', []));
            $this->storeImages($section, $request->file('images', []));
        });

        return redirect()
            ->route('home-hero.edit')
            ->with('success', __('home_hero.saved'));
    }

    private function validateMediaState(
        HomeHeroSectionRequest $request,
        ?HomeHeroSection $section
    ): void {
        $deletedIds = collect($request->input('delete_image_ids', []))->map(fn ($id) => (int) $id);
        $remainingImages = $section
            ? $section->images->whereNotIn('id', $deletedIds)->count()
            : 0;

        if ($remainingImages + count($request->file('images', [])) < 1) {
            throw ValidationException::withMessages([
                'images' => __('home_hero.at_least_one_image'),
            ]);
        }

        if (
            $request->input('video_type') === 'upload'
            && !$request->hasFile('video_file')
            && (!$section || !$section->video_path)
        ) {
            throw ValidationException::withMessages([
                'video_file' => __('home_hero.video_file_required'),
            ]);
        }
    }

    private function authorizeRequestedChanges(
        HomeHeroSectionRequest $request,
        ?HomeHeroSection $section
    ): void {
        $admin = auth('admin')->user();

        if (AdminRoutePermissionMatrix::adminHasAnyPermission($admin, [PermKeys::HOME_HERO_CONTROL])) {
            return;
        }

        if (!$section) {
            abort_unless(
                AdminRoutePermissionMatrix::adminHasAnyPermission($admin, [PermKeys::HOME_HERO_CREATE]),
                403
            );

            return;
        }

        $requestedVideoType = $request->input('video_type');
        $hasCreate = $request->hasFile('images')
            || $request->hasFile('video_file')
            || ($requestedVideoType && $requestedVideoType !== $section->video_type);
        $hasDelete = !empty($request->input('delete_image_ids'))
            || ($section->video_type && $requestedVideoType !== $section->video_type)
            || ($section->video_type === 'upload' && $request->hasFile('video_file'));

        if ($hasCreate) {
            abort_unless(
                AdminRoutePermissionMatrix::adminHasAnyPermission($admin, [PermKeys::HOME_HERO_CREATE]),
                403
            );
        }

        if ($hasDelete) {
            abort_unless(
                AdminRoutePermissionMatrix::adminHasAnyPermission($admin, [PermKeys::HOME_HERO_DELETE]),
                403
            );
        }

        if ($this->mainContentChanged($section, $request)) {
            abort_unless(
                AdminRoutePermissionMatrix::adminHasAnyPermission($admin, [PermKeys::HOME_HERO_EDIT]),
                403
            );
        }
    }

    private function mainContentChanged(
        HomeHeroSection $section,
        HomeHeroSectionRequest $request
    ): bool {
        foreach (config('translatable.locales') as $locale) {
            $translation = $section->translate($locale);

            foreach (['title', 'subtitle', 'video_title'] as $field) {
                if ((string) optional($translation)->{$field} !== (string) $request->input("$field:$locale")) {
                    return true;
                }
            }
        }

        return (bool) $section->is_active !== $request->boolean('is_active')
            || (
                $section->video_type === 'youtube'
                && $request->input('video_type') === 'youtube'
                && (string) $section->video_url !== (string) $request->input('video_url')
            );
    }

    private function syncVideo(HomeHeroSection $section, HomeHeroSectionRequest $request): void
    {
        $type = $request->input('video_type');

        if ($section->video_path && (
            $type !== 'upload' || $request->hasFile('video_file')
        )) {
            deleteFile($section->video_path);
            $section->video_path = null;
        }

        if ($type === 'upload' && $request->hasFile('video_file')) {
            $section->video_path = uploadFile(
                $request->file('video_file'),
                'videos/home-hero/',
                1
            );
        }

        $section->video_url = $type === 'youtube'
            ? $request->input('video_url')
            : null;

        if (!$type) {
            $section->video_path = null;
        }
    }

    private function deleteImages(HomeHeroSection $section, array $imageIds): void
    {
        $section->images()
            ->whereIn('id', $imageIds)
            ->get()
            ->each(function ($image) {
                deleteFile($image->path);
                $image->delete();
            });
    }

    private function storeImages(HomeHeroSection $section, array $images): void
    {
        foreach ($images as $image) {
            $section->images()->create([
                'path' => uploadFile($image, 'images/home-hero/', 1),
            ]);
        }
    }
}
