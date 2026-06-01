<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\admin\Controller;
use App\Models\TranslationTerm;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

class TranslationTermController extends Controller
{
    public function index(Request $request)
    {
        $locales = $this->supportedLocales();
        $groups = $this->availableGroups($locales);
        $group = (string) $request->query('group', in_array('admin_hardcoded', $groups, true) ? 'admin_hardcoded' : ($groups[0] ?? 'admin'));
        $search = trim((string) $request->query('search', ''));

        if (!in_array($group, $groups, true)) {
            $groups[] = $group;
            sort($groups);
        }

        $terms = $this->termsForGroup($group, $locales);

        if ($search !== '') {
            $terms = array_filter($terms, function (array $term) use ($search) {
                if (stripos($term['key'], $search) !== false) {
                    return true;
                }

                foreach ($term['values'] as $value) {
                    if (stripos((string) $value, $search) !== false) {
                        return true;
                    }
                }

                return false;
            });
        }

        ksort($terms, SORT_NATURAL | SORT_FLAG_CASE);
        $terms = $this->paginateTerms($terms, $request);

        return view('admin.settings.translations.index', [
            'activeSection' => 'translations',
            'groups' => $groups,
            'group' => $group,
            'locales' => $locales,
            'search' => $search,
            'terms' => $terms,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'group' => ['required', 'string', 'max:120', 'regex:/^[A-Za-z0-9_\\-\\/]+$/'],
            'key' => ['required', 'string', 'max:500'],
            'values' => ['required', 'array'],
            'values.*' => ['nullable', 'string'],
        ]);

        $group = $data['group'];
        $key = trim($data['key']);
        $locales = $this->supportedLocales();

        foreach ($locales as $locale) {
            $value = $data['values'][$locale] ?? '';

            TranslationTerm::updateOrCreate(
                ['group' => $group, 'key_hash' => sha1($key), 'locale' => $locale],
                ['key' => $key, 'value' => $value]
            );

            $items = $this->loadGroup($locale, $group);
            $this->setTranslationValue($items, $key, $value);
            $this->writeGroup($locale, $group, $items);
        }

        return redirect()
            ->route('translation-terms.index', [
                'group' => $group,
                'search' => $request->input('return_search'),
                'page' => $request->input('return_page'),
            ])
            ->with('success', __('admin.translation_terms_saved_successfully'));
    }

    public function destroy(Request $request)
    {
        $data = $request->validate([
            'group' => ['required', 'string', 'max:120', 'regex:/^[A-Za-z0-9_\\-\\/]+$/'],
            'key' => ['required', 'string', 'max:500'],
        ]);

        $group = $data['group'];
        $key = $data['key'];

        foreach ($this->supportedLocales() as $locale) {
            TranslationTerm::query()
                ->where('group', $group)
                ->where('key_hash', sha1($key))
                ->where('locale', $locale)
                ->delete();

            $items = $this->loadGroup($locale, $group);
            $this->forgetTranslationValue($items, $key);
            $this->writeGroup($locale, $group, $items);
        }

        return redirect()
            ->route('translation-terms.index', [
                'group' => $group,
                'search' => $request->input('return_search'),
                'page' => $request->input('return_page'),
            ])
            ->with('success', __('admin.translation_terms_deleted_successfully'));
    }

    public function sync(Request $request)
    {
        $group = $request->input('group');
        $locales = $this->supportedLocales();
        $groups = $group ? [$group] : $this->availableGroups($locales);
        $synced = 0;

        foreach ($groups as $fileGroup) {
            foreach ($locales as $locale) {
                foreach (Arr::dot($this->loadGroup($locale, $fileGroup)) as $key => $value) {
                    if (is_array($value)) {
                        continue;
                    }

                    TranslationTerm::updateOrCreate(
                        ['group' => $fileGroup, 'key_hash' => sha1((string) $key), 'locale' => $locale],
                        ['key' => (string) $key, 'value' => (string) $value]
                    );
                    $synced++;
                }
            }
        }

        return redirect()
            ->route('translation-terms.index', ['group' => $group ?: ($groups[0] ?? 'admin')])
            ->with('success', __('admin.translation_terms_synced_successfully') . ' (' . $synced . ')');
    }

    private function supportedLocales(): array
    {
        $locales = array_keys(config('laravellocalization.supportedLocales', []));

        return $locales ?: ['ar', 'en'];
    }

    private function availableGroups(array $locales): array
    {
        $groups = [];

        foreach ($locales as $locale) {
            foreach (glob(resource_path("lang/{$locale}/*.php")) ?: [] as $path) {
                $groups[] = basename($path, '.php');
            }
        }

        foreach (array_keys($this->packageTranslationGroups()) as $group) {
            $groups[] = $group;
        }

        $groups = array_values(array_unique($groups));
        sort($groups, SORT_NATURAL | SORT_FLAG_CASE);

        return $groups;
    }

    private function termsForGroup(string $group, array $locales): array
    {
        $terms = [];
        $loadedTerms = [];

        foreach ($locales as $locale) {
            $loadedTerms[$locale] = Arr::dot($this->loadGroup($locale, $group));
        }

        if ($group === 'admin_hardcoded' && isset($loadedTerms['ar'], $loadedTerms['en'])) {
            $terms = $this->hardcodedTerms($loadedTerms['ar'], $loadedTerms['en']);

            foreach ($loadedTerms as $locale => $localeTerms) {
                if (in_array($locale, ['ar', 'en'], true)) {
                    continue;
                }

                foreach ($localeTerms as $key => $value) {
                    $terms[(string) $key]['key'] = (string) $key;
                    $terms[(string) $key]['values'][$locale] = is_array($value) ? '' : (string) $value;
                }
            }
        } else {
            foreach ($loadedTerms as $locale => $localeTerms) {
                foreach ($localeTerms as $key => $value) {
                    $terms[(string) $key]['key'] = (string) $key;
                    $terms[(string) $key]['values'][$locale] = is_array($value) ? '' : (string) $value;
                }
            }
        }

        TranslationTerm::query()
            ->where('group', $group)
            ->get()
            ->each(function (TranslationTerm $term) use (&$terms) {
                $terms[$term->key]['key'] = $term->key;
                $terms[$term->key]['values'][$term->locale] = (string) $term->value;
            });

        foreach ($terms as &$term) {
            foreach ($locales as $locale) {
                $term['values'][$locale] = $term['values'][$locale] ?? '';
            }
        }

        return $terms;
    }

    private function hardcodedTerms(array $arabicTerms, array $englishTerms): array
    {
        $terms = [];
        $matchedEnglishKeys = [];

        foreach ($arabicTerms as $key => $value) {
            $key = (string) $key;
            $value = is_array($value) ? '' : (string) $value;

            $terms[$key]['key'] = $key;
            $terms[$key]['values']['ar'] = $value;

            if (array_key_exists($key, $englishTerms)) {
                $terms[$key]['values']['en'] = is_array($englishTerms[$key]) ? '' : (string) $englishTerms[$key];
                $matchedEnglishKeys[$key] = true;
                continue;
            }

            if ($value !== '' && array_key_exists($value, $englishTerms)) {
                $terms[$key]['values']['en'] = is_array($englishTerms[$value]) ? '' : (string) $englishTerms[$value];
                $matchedEnglishKeys[$value] = true;
            }
        }

        foreach ($englishTerms as $key => $value) {
            $key = (string) $key;

            if (isset($matchedEnglishKeys[$key])) {
                continue;
            }

            $terms[$key]['key'] = $key;
            $terms[$key]['values']['en'] = is_array($value) ? '' : (string) $value;
        }

        return $terms;
    }

    private function paginateTerms(array $terms, Request $request): LengthAwarePaginator
    {
        $perPage = 100;
        $total = count($terms);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, (int) $request->query('page', 1)), $lastPage);
        $items = array_slice($terms, ($page - 1) * $perPage, $perPage, true);

        return (new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->except('page'),
            ]
        ));
    }

    private function loadGroup(string $locale, string $group): array
    {
        $path = $this->groupPath($locale, $group);

        if (!File::exists($path)) {
            return [];
        }

        $items = include $path;

        return is_array($items) ? $items : [];
    }

    private function writeGroup(string $locale, string $group, array $items): void
    {
        $path = $this->groupPath($locale, $group);
        File::ensureDirectoryExists(dirname($path));
        File::put($path, "<?php\n\nreturn " . var_export($items, true) . ";\n");
    }

    private function groupPath(string $locale, string $group): string
    {
        $packageGroups = $this->packageTranslationGroups();

        if (isset($packageGroups[$group])) {
            return base_path(sprintf($packageGroups[$group], $locale));
        }

        return resource_path('lang/' . $locale . '/' . $group . '.php');
    }

    private function packageTranslationGroups(): array
    {
        return [
            'finance' => 'packages/finance/resources/lang/%s/finance.php',
            'hr' => 'packages/hr/resources/lang/%s/hr.php',
            'zatca' => 'packages/finance/resources/lang/%s/zatca.php',
        ];
    }

    private function setTranslationValue(array &$items, string $key, ?string $value): void
    {
        if (array_key_exists($key, $items)) {
            $items[$key] = $value;
            return;
        }

        Arr::set($items, $key, $value);
    }

    private function forgetTranslationValue(array &$items, string $key): void
    {
        if (array_key_exists($key, $items)) {
            unset($items[$key]);
            return;
        }

        Arr::forget($items, $key);
    }
}
