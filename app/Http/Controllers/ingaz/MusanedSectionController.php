<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\admin\Controller;
use App\Http\Requests\ingaz\MusanedSectionRequest;
use App\Models\MusanedSection;
use App\Models\MusanedSectionMetric;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class MusanedSectionController extends Controller
{
    public function edit()
    {
        return view('admin.ingaz.musaned_section.edit', [
            'section' => MusanedSection::with('metrics.translations')->first(),
        ]);
    }

    public function update(MusanedSectionRequest $request)
    {
        $oldImageFiles = [];
        $newImageFiles = [];

        try {
            DB::transaction(function () use ($request, &$oldImageFiles, &$newImageFiles) {
                $section = MusanedSection::firstOrNew();
                $translatedFields = [
                    'title',
                    'subtitle',
                    'overall_performance',
                ];
                $translatedInputs = collect($translatedFields)
                    ->flatMap(fn (string $field) => ["$field:ar", "$field:en"])
                    ->all();

                $section->fill($request->safe()->except($translatedInputs));

                foreach (config('translatable.locales') as $locale) {
                    $translation = $section->translateOrNew($locale);

                    foreach ($translatedFields as $field) {
                        $translation->{$field} = $request->input("$field:$locale");
                    }
                }

                $section->save();

                $oldMetrics = $section->metrics()->get()->keyBy('id');
                $oldImageFiles = $oldMetrics
                    ->pluck('icon')
                    ->filter()
                    ->mapWithKeys(fn (string $path) => [$path => $path])
                    ->all();

                $section->metrics()->delete();
                $this->storeMetrics(
                    $section,
                    $request->validated('ratios', []),
                    MusanedSectionMetric::TYPE_RATIO,
                    $oldMetrics,
                    $oldImageFiles,
                    $newImageFiles
                );
                $this->storeMetrics(
                    $section,
                    $request->validated('star_ratings', []),
                    MusanedSectionMetric::TYPE_RATING,
                    $oldMetrics,
                    $oldImageFiles,
                    $newImageFiles
                );
            });
        } catch (\Throwable $exception) {
            $this->deleteImageFiles($newImageFiles);

            throw $exception;
        }

        $this->deleteImageFiles(array_values($oldImageFiles));

        return redirect()
            ->route('musaned-section.edit')
            ->with('success', __('musaned_section.saved'));
    }

    private function storeMetrics(
        MusanedSection $section,
        array $items,
        string $type,
        Collection $oldMetrics,
        array &$oldImageFiles,
        array &$newImageFiles
    ): void {
        foreach (array_values($items) as $sortOrder => $item) {
            $oldMetric = !empty($item['id'])
                ? $oldMetrics->get((int) $item['id'])
                : null;
            $oldIcon = $oldMetric?->icon;
            $icon = !empty($item['remove_icon']) ? null : $oldIcon;

            if (($item['icon'] ?? null) instanceof UploadedFile) {
                $icon = uploadFile($item['icon'], 'images/musaned-metrics/', 1);
                $newImageFiles[] = $icon;
            }

            if ($icon && $icon === $oldIcon) {
                unset($oldImageFiles[$oldIcon]);
            }

            $metric = $section->metrics()->create([
                'type' => $type,
                'value' => $item['value'],
                'icon' => $icon,
                'is_visible' => $item['is_visible'],
                'sort_order' => $sortOrder,
            ]);

            $titles = is_array($item['title'] ?? null) ? $item['title'] : [];

            foreach (config('translatable.locales') as $locale) {
                $metric->translateOrNew($locale)->title = $titles[$locale] ?? '';
            }

            $metric->save();
        }
    }

    private function deleteImageFiles(array $paths): void
    {
        foreach (array_unique(array_filter($paths)) as $path) {
            deleteFile($path);
        }
    }
}
