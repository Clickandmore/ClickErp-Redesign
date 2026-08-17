<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\IngazDelegation;
use App\Models\IngazDelegationStage;
use App\PermKeys;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class IngazDelegationStageController extends Controller
{
    public function edit(): View
    {
        abort_unless(AdminGuardHasPerm(PermKeys::SETTINGS_VIEW), 403);

        return view('admin.ingaz_delegation_stages.edit', [
            'stages' => IngazDelegationStage::query()->orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless(AdminGuardHasPerm(PermKeys::SETTINGS_CONTROL), 403);

        $validated = $request->validate([
            'stages' => ['required', 'array', 'min:2'],
            'stages.*.id' => ['nullable', 'integer', Rule::exists('ingaz_delegation_stages', 'id')],
            'stages.*.name_ar' => ['required_unless:stages.*._delete,1', 'nullable', 'string', 'max:150'],
            'stages.*.name_en' => ['nullable', 'string', 'max:150'],
            'stages.*.sort_order' => ['required_unless:stages.*._delete,1', 'nullable', 'integer', 'min:0', 'max:9999'],
            'stages.*.is_active' => ['nullable', 'boolean'],
            'stages.*._delete' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated['stages'] as $stageData) {
                $stage = !empty($stageData['id'])
                    ? IngazDelegationStage::findOrFail((int) $stageData['id'])
                    : null;

                if (!empty($stageData['_delete'])) {
                    if (!$stage) {
                        continue;
                    }

                    if ($stage->is_system) {
                        throw ValidationException::withMessages([
                            'stages' => 'لا يمكن حذف مرحلتي تحت المعالجة وتم تنفيذ التفويض.',
                        ]);
                    }

                    if (IngazDelegation::where('status', $stage->key)->exists()) {
                        throw ValidationException::withMessages([
                            'stages' => "لا يمكن حذف المرحلة «{$stage->name_ar}» لأنها مستخدمة في تفاويض حالية.",
                        ]);
                    }

                    $stage->delete();
                    continue;
                }

                $payload = [
                    'name_ar' => trim((string) $stageData['name_ar']),
                    'name_en' => trim((string) ($stageData['name_en'] ?? '')) ?: null,
                    'sort_order' => (int) $stageData['sort_order'],
                    'is_active' => $stage?->is_system ?: (bool) ($stageData['is_active'] ?? false),
                ];

                if ($stage) {
                    $stage->update($payload);
                } else {
                    IngazDelegationStage::create($payload + [
                        'key' => 'stage_' . Str::lower(Str::random(12)),
                        'is_system' => false,
                    ]);
                }
            }
        });

        IngazDelegation::clearStatusOptionsCache();

        return redirect()
            ->route('ingaz-delegation-stages.edit')
            ->with('success', 'تم حفظ مراحل التفويض بنجاح.');
    }
}
