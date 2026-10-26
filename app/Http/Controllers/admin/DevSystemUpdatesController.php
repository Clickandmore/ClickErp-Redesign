<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\auth\Admin;
use App\Models\SystemUpdate;
use App\Models\SystemUpdateClient;
use App\Services\SystemUpdates\SystemUpdateEditor;
use App\Services\SystemUpdates\SystemUpdateSyncService;
use App\Support\SystemModuleManager;
use App\Support\SystemUpdatesSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * قسم «تحديثات النظام» في لوحة Super Admin.
 *
 * كل نسخة تملك القسم كاملًا: تكتب تحديثاتها، وتُصدر مفاتيح لنسخ أخرى تسحب منها،
 * وتحدد هي نفسها مصدرًا تسحب منه.
 */
class DevSystemUpdatesController extends Controller
{
    private const MAX_IMAGE_KB = 5120;

    private const MAX_VIDEO_KB = 102400;

    private const MAX_PDF_KB = 20480;

    public function index()
    {
        $updates = SystemUpdate::query()
            ->withCount([
                'media',
                'reads as acknowledged_count' => fn ($q) => $q->whereNotNull('acknowledged_at'),
                'reads as seen_count',
            ])
            ->orderByRaw("CASE WHEN status = 'draft' THEN 0 ELSE 1 END")
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(20);

        return $this->render('index', [
            'systemUpdates' => $updates,
            'systemUpdatesSettings' => SystemUpdatesSettings::all(),
            'systemUpdatesState' => SystemUpdatesSettings::state(),
            'systemUpdateClients' => SystemUpdateClient::query()->orderByDesc('id')->get(),
            'activeEmployeesCount' => $this->activeEmployees()->count(),
            'systemUpdateCounts' => [
                'published' => SystemUpdate::query()->where('status', SystemUpdate::STATUS_PUBLISHED)->count(),
                'drafts' => SystemUpdate::query()->where('status', SystemUpdate::STATUS_DRAFT)->count(),
                'remote' => SystemUpdate::query()->where('source', SystemUpdate::SOURCE_REMOTE)->count(),
            ],
        ]);
    }

    public function create()
    {
        return $this->render('form', ['systemUpdate' => new SystemUpdate([
            'category' => 'feature',
            'status' => SystemUpdate::STATUS_DRAFT,
            'is_shared' => true,
        ])]);
    }

    public function store(Request $request, SystemUpdateEditor $editor)
    {
        $data = $this->validated($request);
        $update = $editor->save(new SystemUpdate(), $data, $request->file('media', []));

        return redirect()
            ->route('dev-superadmin.system-updates.edit', $update)
            ->with('success', $update->isPublished() ? 'تم نشر التحديث وسيظهر للموظفين.' : 'تم حفظ التحديث كمسودة.');
    }

    public function edit(SystemUpdate $systemUpdate)
    {
        if ($systemUpdate->isRemote()) {
            return redirect()
                ->route('dev-superadmin.system-updates.report', $systemUpdate)
                ->withErrors(['system_update' => 'هذا التحديث قادم من مصدر التحديثات؛ يُعدَّل من نسخة المصدر فقط.']);
        }

        return $this->render('form', ['systemUpdate' => $systemUpdate->load('media')]);
    }

    public function update(Request $request, SystemUpdate $systemUpdate, SystemUpdateEditor $editor)
    {
        abort_if($systemUpdate->isRemote(), 403);

        $data = $this->validated($request);
        $editor->save($systemUpdate, $data, $request->file('media', []));

        return redirect()
            ->route('dev-superadmin.system-updates.edit', $systemUpdate)
            ->with('success', 'تم حفظ التحديث.');
    }

    public function destroy(SystemUpdate $systemUpdate, SystemUpdateEditor $editor)
    {
        $editor->delete($systemUpdate);

        return redirect()
            ->route('dev-superadmin.product-updates')
            ->with('success', 'تم حذف التحديث' . ($systemUpdate->isRemote() ? ' من هذه النسخة.' : ' وسيُحذف من نسخ العملاء في المزامنة القادمة.'));
    }

    public function report(SystemUpdate $systemUpdate)
    {
        $reads = $systemUpdate->reads()->get()->keyBy('admin_id');
        $employees = $this->activeEmployees()
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'username', 'role_id'])
            ->map(function (Admin $admin) use ($reads) {
                $read = $reads->get($admin->id);

                return [
                    'admin' => $admin,
                    'seen_at' => $read?->seen_at,
                    'acknowledged_at' => $read?->acknowledged_at,
                ];
            })
            ->sortBy(fn (array $row) => $row['acknowledged_at'] ? 2 : ($row['seen_at'] ? 1 : 0))
            ->values();

        return $this->render('report', [
            'systemUpdate' => $systemUpdate->load('media'),
            'reportRows' => $employees,
        ]);
    }

    public function updateSettings(Request $request, SystemUpdateSyncService $sync)
    {
        $data = $request->validate([
            'banner_days' => ['required', 'integer', 'min:1', 'max:365'],
            'source_url' => ['nullable', 'string', 'max:500', 'regex:#^https?://#i'],
            'source_key' => ['nullable', 'string', 'max:200'],
            'sync_interval_minutes' => ['required', 'integer', Rule::in(SystemUpdatesSettings::INTERVAL_OPTIONS)],
        ], [
            'source_url.regex' => 'رابط المصدر يجب أن يبدأ بـ https://',
        ]);

        if ($request->boolean('sync_enabled') && blank($data['source_url'] ?? null)) {
            return back()->withErrors(['source_url' => 'لتفعيل المزامنة حدد رابط مصدر التحديثات.'])->withInput();
        }

        SystemUpdatesSettings::persist([
            ...$data,
            'banner_enabled' => $request->boolean('banner_enabled'),
            'sync_enabled' => $request->boolean('sync_enabled'),
            'clear_source_key' => $request->boolean('clear_source_key'),
        ]);

        $redirect = redirect()->route('dev-superadmin.product-updates');

        // لا ننتظر أول دورة مجدولة: نسحب فور الحفظ
        if (SystemUpdatesSettings::syncConfigured()) {
            try {
                $result = $sync->sync();
            } catch (\Throwable $exception) {
                return $redirect->withErrors(['sync' => 'تم حفظ الإعدادات، لكن فشلت المزامنة الأولى: ' . $exception->getMessage()]);
            }

            return $redirect->with('success', sprintf('تم حفظ الإعدادات وسحب التحديثات الآن (وصل %d تحديث).', $result['received']));
        }

        return $redirect->with('success', 'تم حفظ إعدادات التحديثات.');
    }

    public function sync(Request $request, SystemUpdateSyncService $sync)
    {
        try {
            $result = $sync->sync($request->boolean('full'));
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withErrors(['sync' => 'فشلت المزامنة: ' . $exception->getMessage()]);
        }

        return back()->with('success', sprintf(
            'تمت المزامنة: وصل %d تحديث، حُفظ %d، وحُذف %d.',
            $result['received'],
            $result['saved'],
            $result['removed']
        ));
    }

    public function testConnection(Request $request, SystemUpdateSyncService $sync)
    {
        $data = $request->validate([
            'source_url' => ['nullable', 'string', 'max:500'],
            'source_key' => ['nullable', 'string', 'max:200'],
        ]);

        $url = filled($data['source_url'] ?? null) ? $data['source_url'] : SystemUpdatesSettings::all()['source_url'];
        $key = filled($data['source_key'] ?? null) ? $data['source_key'] : SystemUpdatesSettings::sourceKey();

        if (blank($url) || blank($key)) {
            return back()->withErrors(['source_url' => 'أدخل رابط المصدر ومفتاح الربط لاختبار الاتصال.'])->withInput();
        }

        try {
            $result = $sync->testConnection($url, $key);
        } catch (\Throwable $exception) {
            return back()->withErrors(['source_url' => 'فشل الاتصال: ' . $exception->getMessage()])->withInput();
        }

        return back()->withInput()->with('success', 'الاتصال ناجح' . ($result['source_name'] ? ' بنسخة «' . $result['source_name'] . '»' : '') . '. لا تنسَ حفظ الإعدادات.');
    }

    public function storeClient(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
        ], [
            'name.required' => 'اكتب اسم العميل أو النسخة التي ستستخدم المفتاح.',
        ]);

        [$client, $token] = SystemUpdateClient::issue(trim($data['name']));

        return redirect()
            ->route('dev-superadmin.product-updates')
            ->with('success', 'تم إنشاء مفتاح «' . $client->name . '». انسخه الآن، لن يظهر مرة أخرى.')
            ->with('system_update_client_token', $token)
            ->with('system_update_client_name', $client->name);
    }

    public function toggleClient(SystemUpdateClient $client)
    {
        $client->update(['is_active' => ! $client->is_active]);

        return back()->with('success', $client->is_active ? 'تم تفعيل المفتاح.' : 'تم إيقاف المفتاح؛ لن تستطيع تلك النسخة سحب التحديثات.');
    }

    public function destroyClient(SystemUpdateClient $client)
    {
        $client->delete();

        return back()->with('success', 'تم حذف المفتاح.');
    }

    private function validated(Request $request): array
    {
        $validator = validator($request->all(), [
            'title' => ['required', 'string', 'max:250'],
            'summary' => ['nullable', 'string', 'max:500'],
            'body' => ['nullable', 'string', 'max:20000'],
            'category' => ['required', Rule::in(array_keys(SystemUpdate::CATEGORIES))],
            'module_key' => ['nullable', Rule::in(array_keys(SystemModuleManager::modules()))],
            'link_url' => ['nullable', 'string', 'max:1000'],
            'link_label' => ['nullable', 'string', 'max:80'],
            'version' => ['nullable', 'string', 'max:40'],
            'status' => ['required', Rule::in([SystemUpdate::STATUS_DRAFT, SystemUpdate::STATUS_PUBLISHED])],
            'published_at' => ['nullable', 'date'],
            'banner_until' => ['nullable', 'date'],
            'is_shared' => ['nullable', 'boolean'],
            'media' => ['nullable', 'array', 'max:10'],
            'media.*' => [
                'file',
                'mimes:' . implode(',', [...SystemUpdateEditor::IMAGE_EXTENSIONS, ...SystemUpdateEditor::VIDEO_EXTENSIONS, ...SystemUpdateEditor::DOCUMENT_EXTENSIONS]),
                'max:' . self::MAX_VIDEO_KB,
            ],
            'embed_urls' => ['nullable', 'string', 'max:3000'],
            'remove_media' => ['nullable', 'array'],
            'remove_media.*' => ['integer'],
            'media_captions' => ['nullable', 'array'],
            'media_captions.*' => ['nullable', 'string', 'max:250'],
        ], [
            'title.required' => 'اكتب عنوان التحديث.',
            'media.*.mimes' => 'الملفات المسموحة: صور (jpg, png, webp, gif) أو فيديو (mp4, webm, mov) أو ملف PDF.',
            'media.*.max' => 'حجم الفيديو يجب ألا يزيد على 100 ميجابايت.',
            'media.*.uploaded' => 'تعذر رفع الملف؛ غالبًا حجمه أكبر من المسموح في إعدادات PHP على السيرفر.',
        ]);

        $validator->after(function (Validator $validator) use ($request) {
            $url = trim((string) $request->input('link_url', ''));
            $internal = Str::startsWith($url, '/') && ! Str::startsWith($url, '//');
            $external = Str::startsWith($url, ['https://', 'http://']);

            if ($url !== '' && ! $internal && ! $external) {
                $validator->errors()->add('link_url', 'أدخل رابطًا داخليًا يبدأ بـ / مثل /dashboard/invoices أو رابطًا كاملًا يبدأ بـ https://.');
            }

            foreach ((array) $request->file('media', []) as $index => $file) {
                $extension = strtolower((string) $file?->getClientOriginalExtension());
                if (in_array($extension, SystemUpdateEditor::IMAGE_EXTENSIONS, true) && $file->getSize() > self::MAX_IMAGE_KB * 1024) {
                    $validator->errors()->add("media.$index", 'حجم الصورة يجب ألا يزيد على 5 ميجابايت.');
                }

                if (in_array($extension, SystemUpdateEditor::DOCUMENT_EXTENSIONS, true) && $file->getSize() > self::MAX_PDF_KB * 1024) {
                    $validator->errors()->add("media.$index", 'حجم ملف PDF يجب ألا يزيد على 20 ميجابايت.');
                }
            }

            foreach (app(SystemUpdateEditor::class)->embedLines((string) $request->input('embed_urls', '')) as $line) {
                if (! Str::startsWith($line, 'https://')) {
                    $validator->errors()->add('embed_urls', 'روابط الفيديو يجب أن تبدأ بـ https:// (YouTube أو Vimeo أو Loom).');
                    break;
                }
            }

            $publishedAt = $request->input('published_at');
            $bannerUntil = $request->input('banner_until');
            if (filled($publishedAt) && filled($bannerUntil) && strtotime($bannerUntil) < strtotime($publishedAt)) {
                $validator->errors()->add('banner_until', 'نهاية الظهور في الشريط يجب أن تكون بعد تاريخ النشر.');
            }
        });

        return $validator->validate();
    }

    private function activeEmployees()
    {
        return Admin::query()
            ->notExternalOfficeAgents()
            ->where(fn ($q) => $q->whereNull('status')->orWhere('status', '!=', 'suspended'));
    }

    private function render(string $view, array $data)
    {
        if ($view === 'form') {
            // لصندوق «من سيرى هذا التحديث؟» في نموذج التحرير
            $data['audience'] = [
                'employees' => $this->activeEmployees()->count(),
                'clients' => SystemUpdateClient::query()->where('is_active', true)->pluck('name')->all(),
                'enabled_modules' => SystemModuleManager::enabledMap(),
            ];
        }

        return view('admin.superadmin.modules', array_merge($data, [
            'activeSection' => 'product-updates',
            'systemUpdatesView' => $view,
            'systemUpdateModules' => SystemModuleManager::modules(),
        ]));
    }
}
