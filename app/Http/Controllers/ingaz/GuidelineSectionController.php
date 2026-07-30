<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\admin\Controller;
use App\Http\Requests\ingaz\GuidelineSectionRequest;
use App\Models\GuidelineSection;
use App\Models\NationalityGuideline;
use App\Models\NationalityGuidelineVersion;
use App\Models\ingaz\Nationality;
use App\PermKeys;
use App\Support\AdminRoutePermissionMatrix;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GuidelineSectionController extends Controller
{
    public function edit()
    {
        return view('admin.ingaz.guidelines.edit', [
            'section' => $this->section(),
            'nationalities' => Nationality::query()
                ->orderByTranslation('sub_title')
                ->get(),
        ]);
    }

    public function update(GuidelineSectionRequest $request)
    {
        $section = $this->section();
        $this->validateRequiredFiles($request, $section);
        $this->authorizeChanges($request, $section);

        DB::transaction(function () use ($request, $section) {
            $section ??= new GuidelineSection();
            $this->replaceFile($section, 'client_file_path', $request->file('client_file'), 'client');
            $this->replaceFile($section, 'worker_file_path', $request->file('worker_file'), 'worker');
            $section->save();

            $this->syncVideos($section, $request->input('videos', []));
            $this->syncVersions($section, $request->input('versions', []), $request);
        });

        return redirect()
            ->route('guidelines.edit')
            ->with('success', __('guidelines.saved'));
    }

    private function section(): ?GuidelineSection
    {
        return GuidelineSection::with([
            'videos',
            'nationalityGuidelines.nationality.translations',
            'nationalityGuidelines.versions',
        ])->first();
    }

    private function validateRequiredFiles(
        GuidelineSectionRequest $request,
        ?GuidelineSection $section
    ): void {
        foreach (['client', 'worker'] as $kind) {
            if (!$request->hasFile("{$kind}_file") && (!$section || !$section->getAttribute("{$kind}_file_path"))) {
                throw ValidationException::withMessages([
                    "{$kind}_file" => __('guidelines.global_file_required'),
                ]);
            }
        }

        $existingVersions = $section
            ? $section->nationalityGuidelines->flatMap->versions->keyBy('id')
            : collect();

        foreach ($request->input('versions', []) as $index => $row) {
            $existing = !empty($row['id']) ? $existingVersions->get((int) $row['id']) : null;

            foreach (['client', 'worker'] as $kind) {
                if (
                    !$request->hasFile("versions.$index.{$kind}_file")
                    && (!$existing || !$existing->getAttribute("{$kind}_file_path"))
                ) {
                    throw ValidationException::withMessages([
                        "versions.$index.{$kind}_file" => __('guidelines.version_files_required'),
                    ]);
                }
            }
        }
    }

    private function authorizeChanges(
        GuidelineSectionRequest $request,
        ?GuidelineSection $section
    ): void {
        $admin = auth('admin')->user();

        if (AdminRoutePermissionMatrix::adminHasAnyPermission($admin, [PermKeys::GUIDELINES_CONTROL])) {
            return;
        }

        if (!$section) {
            abort_unless(
                AdminRoutePermissionMatrix::adminHasAnyPermission($admin, [PermKeys::GUIDELINES_CREATE]),
                403
            );

            return;
        }

        $submittedVideoIds = collect($request->input('videos', []))->pluck('id')->filter()->map(fn ($id) => (int) $id);
        $submittedVersionIds = collect($request->input('versions', []))->pluck('id')->filter()->map(fn ($id) => (int) $id);
        $existingVersionIds = $section->nationalityGuidelines->flatMap->versions->pluck('id');
        $hasCreate = collect($request->input('videos', []))->contains(fn ($row) => empty($row['id']))
            || collect($request->input('versions', []))->contains(fn ($row) => empty($row['id']));
        $hasDelete = $section->videos->pluck('id')->diff($submittedVideoIds)->isNotEmpty()
            || $existingVersionIds->diff($submittedVersionIds)->isNotEmpty();
        $hasEdit = $request->hasFile('client_file')
            || $request->hasFile('worker_file')
            || collect($request->input('videos', []))->contains(fn ($row) => !empty($row['id']))
            || collect($request->input('versions', []))->contains(fn ($row) => !empty($row['id']));

        if ($hasCreate) {
            abort_unless(AdminRoutePermissionMatrix::adminHasAnyPermission($admin, [PermKeys::GUIDELINES_CREATE]), 403);
        }
        if ($hasDelete) {
            abort_unless(AdminRoutePermissionMatrix::adminHasAnyPermission($admin, [PermKeys::GUIDELINES_DELETE]), 403);
        }
        if ($hasEdit) {
            abort_unless(AdminRoutePermissionMatrix::adminHasAnyPermission($admin, [PermKeys::GUIDELINES_EDIT]), 403);
        }
    }

    private function replaceFile($model, string $attribute, $file, string $prefix): void
    {
        if (!$file) {
            return;
        }

        if ($model->{$attribute}) {
            deleteFile($model->{$attribute});
        }

        $model->{$attribute} = uploadFile($file, "files/guidelines/{$prefix}/", 1);
    }

    private function syncVideos(GuidelineSection $section, array $rows): void
    {
        $keptIds = [];

        foreach ($rows as $row) {
            $video = !empty($row['id'])
                ? $section->videos()->findOrFail($row['id'])
                : $section->videos()->make();
            $video->youtube_url = trim($row['youtube_url']);
            $video->save();
            $keptIds[] = $video->id;
        }

        $section->videos()->whereNotIn('id', $keptIds)->delete();
    }

    private function syncVersions(
        GuidelineSection $section,
        array $rows,
        GuidelineSectionRequest $request
    ): void {
        $keptIds = [];

        foreach ($rows as $index => $row) {
            $nationalityGuideline = $section->nationalityGuidelines()
                ->firstOrCreate(['nationality_id' => $row['nationality_id']]);
            $version = !empty($row['id'])
                ? NationalityGuidelineVersion::findOrFail($row['id'])
                : new NationalityGuidelineVersion();
            $version->nationality_guideline_id = $nationalityGuideline->id;
            $version->language = trim($row['language']);
            $this->replaceFile(
                $version,
                'client_file_path',
                $request->file("versions.$index.client_file"),
                'nationalities'
            );
            $this->replaceFile(
                $version,
                'worker_file_path',
                $request->file("versions.$index.worker_file"),
                'nationalities'
            );
            $version->save();
            $keptIds[] = $version->id;
        }

        $deleted = NationalityGuidelineVersion::query()
            ->whereHas('nationalityGuideline', fn ($query) => $query->where('guideline_section_id', $section->id))
            ->whereNotIn('id', $keptIds)
            ->get();

        foreach ($deleted as $version) {
            deleteFile($version->client_file_path);
            deleteFile($version->worker_file_path);
            $version->delete();
        }

        $section->nationalityGuidelines()
            ->whereDoesntHave('versions')
            ->get()
            ->each(fn (NationalityGuideline $guideline) => $guideline->delete());
    }
}
