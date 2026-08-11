<?php

namespace App\Support;

use App\Models\ingaz\MaidLanguage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class MaidLanguageCatalog
{
    public const LEGACY_LANGUAGES = [
        ['code' => 'arabic', 'title_ar' => 'اللغة العربية', 'title_en' => 'Arabic Language', 'sort_order' => 10],
        ['code' => 'english', 'title_ar' => 'اللغة الإنجليزية', 'title_en' => 'English Language', 'sort_order' => 20],
        ['code' => 'amharic', 'title_ar' => 'اللغة الأمهرية', 'title_en' => 'Amharic Language', 'sort_order' => 30],
        ['code' => 'mother', 'title_ar' => 'اللغة الأم', 'title_en' => 'Mother Language', 'sort_order' => 40],
    ];

    public function active(): Collection
    {
        if (!$this->tableExists()) {
            return $this->legacyCollection();
        }

        $languages = MaidLanguage::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($languages->isEmpty() && !MaidLanguage::withTrashed()->exists()) {
            return $this->legacyCollection();
        }

        return $languages;
    }

    public function forForm(array $selectedCodes = []): Collection
    {
        $selectedCodes = collect($selectedCodes)
            ->map(fn ($code) => trim((string) $code))
            ->filter()
            ->unique()
            ->values();

        if (!$this->tableExists()) {
            return $this->legacyCollection($selectedCodes->all());
        }

        $languages = MaidLanguage::withTrashed()
            ->where(function ($query) use ($selectedCodes) {
                $query->where(function ($activeQuery) {
                    $activeQuery->where('is_active', true)->whereNull('deleted_at');
                });

                if ($selectedCodes->isNotEmpty()) {
                    $query->orWhereIn('code', $selectedCodes->all());
                }
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $languages->each(function (MaidLanguage $language) {
            if ($language->trashed()) {
                $language->is_active = false;
            }
        });

        $knownCodes = $languages->pluck('code');
        $missingSelected = $selectedCodes->diff($knownCodes);

        foreach ($missingSelected as $code) {
            $legacy = collect(self::LEGACY_LANGUAGES)->firstWhere('code', $code);
            $languages->push($this->makeLanguage($legacy ?? [
                'code' => $code,
                'title_ar' => $code,
                'title_en' => $code,
                'sort_order' => 9999,
            ], false));
        }

        return $languages->sortBy(fn ($language) => sprintf('%08d-%s', $language->sort_order, $language->code))->values();
    }

    private function legacyCollection(array $selectedCodes = []): Collection
    {
        $languages = collect(self::LEGACY_LANGUAGES)
            ->map(fn (array $language) => $this->makeLanguage($language));

        foreach (array_diff($selectedCodes, $languages->pluck('code')->all()) as $code) {
            $languages->push($this->makeLanguage([
                'code' => $code,
                'title_ar' => $code,
                'title_en' => $code,
                'sort_order' => 9999,
            ], false));
        }

        return $languages;
    }

    private function makeLanguage(array $attributes, bool $active = true): MaidLanguage
    {
        $language = new MaidLanguage($attributes);
        $language->is_active = $active;

        return $language;
    }

    private function tableExists(): bool
    {
        try {
            return Schema::hasTable('maid_languages');
        } catch (\Throwable $exception) {
            return false;
        }
    }
}
