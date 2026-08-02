<?php

namespace App\Http\Controllers\ingaz;

use App\PermKeys;
use App\Http\Controllers\admin\Controller;
use App\Http\Requests\ingaz\WhyChooseUsRequest;
use App\Models\WhyChooseUs;
use App\Models\WhyChooseUsFeature;
use App\Support\AdminRoutePermissionMatrix;
use Illuminate\Support\Facades\DB;

class WhyChooseUsController extends Controller
{
    public function edit()
    {
        $section = WhyChooseUs::with(['images', 'features'])->first();

        return view('admin.ingaz.why_choose_us.edit', [
            'section' => $section,
            'icons' => $this->iconOptions(),
        ]);
    }

    public function update(WhyChooseUsRequest $request)
    {
        $this->authorizeRequestedChanges($request);
        $oldImageFiles = [];
        $newImageFiles = [];

        try {
            DB::transaction(function () use ($request, &$oldImageFiles, &$newImageFiles) {
                $section = WhyChooseUs::firstOrNew();
                $section->is_active = $request->boolean('is_active');
                $section->translateOrNew('ar')->title = $request->input('title:ar');
                $section->translateOrNew('ar')->description = $request->input('description:ar');
                $section->translateOrNew('en')->title = $request->input('title:en');
                $section->translateOrNew('en')->description = $request->input('description:en');
                $section->save();

                $this->deleteSelectedImages(
                    $section,
                    $request->input('delete_image_ids', []),
                    $oldImageFiles
                );
                $this->storeImages($section, $request->file('images', []), $newImageFiles);
                $this->syncFeatures($section, $request->input('features', []));
            });
        } catch (\Throwable $exception) {
            $this->deleteImageFiles($newImageFiles);
            throw $exception;
        }

        $this->deleteImageFiles($oldImageFiles);

        return redirect()
            ->route('why-choose-us.edit')
            ->with('success', __('why_choose_us.saved'));
    }

    private function authorizeRequestedChanges(WhyChooseUsRequest $request): void
    {
        $admin = auth('admin')->user();

        if (AdminRoutePermissionMatrix::adminHasAnyPermission($admin, [PermKeys::WHY_CHOOSE_US_CONTROL])) {
            return;
        }

        $section = WhyChooseUs::with('features')->first();

        if (!$section) {
            abort_unless(
                AdminRoutePermissionMatrix::adminHasAnyPermission($admin, [PermKeys::WHY_CHOOSE_US_CREATE]),
                403,
                __('ingaz.permission_denied')
            );

            return;
        }

        $submittedFeatures = collect($request->input('features', []));
        $existingFeatures = $section->features->keyBy('id');
        $submittedIds = $submittedFeatures->pluck('id')->filter()->map(fn ($id) => (int) $id);

        $requiresCreate = $request->hasFile('images')
            || $submittedFeatures->contains(fn (array $feature) => empty($feature['id']));

        $requiresDelete = !empty($request->input('delete_image_ids', []))
            || $existingFeatures->keys()->diff($submittedIds)->isNotEmpty();

        $requiresEdit = $this->mainContentChanged($section, $request)
            || $submittedFeatures->contains(function (array $data) use ($existingFeatures) {
                if (empty($data['id']) || !$existingFeatures->has((int) $data['id'])) {
                    return false;
                }

                $feature = $existingFeatures->get((int) $data['id']);

                return (string) $feature->icon !== (string) $data['icon']
                    || (int) $feature->sort_order !== (int) ($data['sort_order'] ?? 0)
                    || (string) optional($feature->translate('ar'))->title !== (string) $data['ar']['title']
                    || (string) optional($feature->translate('ar'))->description !== (string) $data['ar']['description']
                    || (string) optional($feature->translate('en'))->title !== (string) $data['en']['title']
                    || (string) optional($feature->translate('en'))->description !== (string) $data['en']['description'];
            });

        if ($requiresCreate) {
            abort_unless(
                AdminRoutePermissionMatrix::adminHasAnyPermission($admin, [PermKeys::WHY_CHOOSE_US_CREATE]),
                403,
                __('ingaz.permission_denied')
            );
        }

        if ($requiresDelete) {
            abort_unless(
                AdminRoutePermissionMatrix::adminHasAnyPermission($admin, [PermKeys::WHY_CHOOSE_US_DELETE]),
                403,
                __('ingaz.permission_denied')
            );
        }

        if ($requiresEdit) {
            abort_unless(
                AdminRoutePermissionMatrix::adminHasAnyPermission($admin, [PermKeys::WHY_CHOOSE_US_EDIT]),
                403,
                __('ingaz.permission_denied')
            );
        }
    }

    private function mainContentChanged(WhyChooseUs $section, WhyChooseUsRequest $request): bool
    {
        return (bool) $section->is_active !== $request->boolean('is_active')
            || (string) optional($section->translate('ar'))->title !== (string) $request->input('title:ar')
            || (string) optional($section->translate('ar'))->description !== (string) $request->input('description:ar')
            || (string) optional($section->translate('en'))->title !== (string) $request->input('title:en')
            || (string) optional($section->translate('en'))->description !== (string) $request->input('description:en');
    }

    private function deleteSelectedImages(
        WhyChooseUs $section,
        array $imageIds,
        array &$oldImageFiles
    ): void
    {
        if (empty($imageIds)) {
            return;
        }

        $section->images()
            ->whereIn('id', $imageIds)
            ->get()
            ->each(function ($image) use (&$oldImageFiles) {
                if (!$image->is_public_asset) {
                    $oldImageFiles[] = $image->path;
                }

                $image->delete();
            });
    }

    private function storeImages(WhyChooseUs $section, array $images, array &$newImageFiles): void
    {
        $nextOrder = ((int) $section->images()->max('sort_order')) + 1;

        foreach ($images as $image) {
            $path = uploadFile($image, 'images/why-choose-us/', 1);
            $newImageFiles[] = $path;
            $section->images()->create([
                'path' => $path,
                'is_public_asset' => false,
                'sort_order' => $nextOrder++,
            ]);
        }
    }

    private function syncFeatures(WhyChooseUs $section, array $features): void
    {
        $keptIds = [];

        foreach (array_values($features) as $index => $data) {
            $feature = !empty($data['id'])
                ? $section->features()->findOrFail($data['id'])
                : new WhyChooseUsFeature(['why_choose_us_id' => $section->id]);

            $feature->why_choose_us_id = $section->id;
            $feature->icon = $data['icon'];
            $feature->sort_order = $data['sort_order'] ?? ($index + 1);
            $feature->translateOrNew('ar')->title = $data['ar']['title'];
            $feature->translateOrNew('ar')->description = $data['ar']['description'];
            $feature->translateOrNew('en')->title = $data['en']['title'];
            $feature->translateOrNew('en')->description = $data['en']['description'];
            $feature->save();

            $keptIds[] = $feature->id;
        }

        $section->features()
            ->whereNotIn('id', $keptIds)
            ->delete();
    }

    private function deleteImageFiles(array $paths): void
    {
        foreach (array_unique(array_filter($paths)) as $path) {
            deleteFile($path);
        }
    }

    private function iconOptions(): array
    {
        return [
            'fa fa-award',
            'fa fa-bank',
            'fa fa-bell',
            'fa fa-bolt',
            'fa fa-briefcase',
            'fa fa-building',
            'fa fa-certificate',
            'fa fa-check',
            'fa fa-check-circle',
            'fa fa-clock',
            'fa fa-cog',
            'fa fa-compass',
            'fa fa-diamond',
            'fa fa-exclamation-circle',
            'fa fa-eye',
            'fa fa-flag',
            'fa fa-globe',
            'fa fa-handshake-o',
            'fa fa-heart',
            'fa fa-home',
            'fa fa-lightbulb-o',
            'fa fa-lock',
            'fa fa-magic',
            'fa fa-refresh',
            'fa fa-rocket',
            'fa fa-shield',
            'fa fa-star',
            'fa fa-support',
            'fa fa-thumbs-up',
            'fa fa-trophy',
            'fa fa-university',
            'fa fa-users',
        ];
    }
}
