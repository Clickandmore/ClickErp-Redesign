<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\admin\Controller;
use App\Http\Requests\ingaz\MaidLanguageRequest;
use App\Models\ingaz\MaidLanguage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MaidLanguageController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:maid_languages_view,admin')->only('index');
        $this->middleware('permission:maid_languages_create,admin')->only(['create', 'store']);
        $this->middleware('permission:maid_languages_edit,admin')->only(['edit', 'update']);
        $this->middleware('permission:maid_languages_delete,admin')->only('destroy');
        $this->middleware('permission:maid_languages_toggle_visibility,admin')->only('toggleVisibility');
    }

    public function index(): View
    {
        $languages = MaidLanguage::query()->orderBy('sort_order')->orderBy('id')->get();
        $stats = [
            'total' => $languages->count(),
            'active' => $languages->where('is_active', true)->count(),
            'hidden' => $languages->where('is_active', false)->count(),
        ];

        return view('admin.ingaz.maid-languages.index', compact('languages', 'stats'));
    }

    public function create(): View
    {
        return view('admin.ingaz.maid-languages.create');
    }

    public function store(MaidLanguageRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['code'] = $this->nextCode($data['title_en']);
        $data['sort_order'] = ((int) MaidLanguage::withTrashed()->max('sort_order')) + 10;
        $data['is_active'] = $request->boolean('is_active');

        MaidLanguage::create($data);

        return redirect()->route('languages.index')->with('success', 'تمت إضافة اللغة بنجاح.');
    }

    public function edit(MaidLanguage $language): View
    {
        return view('admin.ingaz.maid-languages.edit', compact('language'));
    }

    public function update(MaidLanguageRequest $request, MaidLanguage $language): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');
        $language->update($data);

        return redirect()->route('languages.index')->with('success', 'تم تحديث اللغة بنجاح دون تغيير رمز التوافق الخاص بها.');
    }

    public function toggleVisibility(MaidLanguage $language): RedirectResponse
    {
        $language->update(['is_active' => !$language->is_active]);

        return back()->with('success', $language->is_active ? 'تم إظهار اللغة.' : 'تم إخفاء اللغة.');
    }

    public function destroy(MaidLanguage $language): RedirectResponse
    {
        $language->delete();

        return back()->with('success', 'تم حذف اللغة من الإعدادات مع الإبقاء على قيم السير القديمة والـAPI كما هي.');
    }

    private function nextCode(string $englishTitle): string
    {
        $base = trim(Str::lower(Str::snake(Str::ascii($englishTitle))), '_');
        $base = $base !== '' ? Str::limit($base, 65, '') : 'language';
        $code = $base;
        $suffix = 2;

        while (MaidLanguage::withTrashed()->where('code', $code)->exists()) {
            $code = $base . '_' . $suffix++;
        }

        return $code;
    }
}
