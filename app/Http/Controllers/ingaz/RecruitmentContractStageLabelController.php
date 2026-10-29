<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\Controller;
use App\PermKeys;
use App\Support\RecruitmentContractStageLabels;
use App\Support\RecruitmentOrderStatusLabels;
use App\Enums\ingaz\ContractStatusEnum;
use App\Models\ingaz\Nationality;
use App\Models\RecruitmentContractStageStep;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RecruitmentContractStageLabelController extends Controller
{
    public function edit()
    {
        abort_unless(AdminGuardHasPerm(PermKeys::SETTINGS_VIEW), 403);

        return view('admin.ingaz.recruitment_contract_stage_labels.edit', [
            'labels' => RecruitmentContractStageLabels::labels(),
            'defaults' => RecruitmentContractStageLabels::defaults(),
            'notificationSettings' => \App\Support\RecruitmentStageNotificationSettings::all(),
            'orderLabels' => RecruitmentOrderStatusLabels::labels(),
            'orderDefaults' => RecruitmentOrderStatusLabels::defaults(),
            'stageSteps' => RecruitmentContractStageStep::with('nationality.translations')
                ->orderBy('stage')->orderBy('sort_order')->orderBy('id')->get(),
            'nationalities' => Nationality::with('translations')->get()->sortBy('display_name')->values(),
        ]);
    }

    public function update(Request $request)
    {
        abort_unless(AdminGuardHasPerm(PermKeys::SETTINGS_CONTROL), 403);

        $validated = $request->validate([
            'labels' => ['required', 'array'],
            'labels.*.ar' => ['nullable', 'string', 'max:100'],
            'labels.*.en' => ['nullable', 'string', 'max:100'],
            'order_labels' => ['required', 'array'],
            'order_labels.*.ar' => ['nullable', 'string', 'max:100'],
            'order_labels.*.en' => ['nullable', 'string', 'max:100'],
            'steps' => ['nullable', 'array'],
            'steps.*.id' => ['nullable', 'integer', Rule::exists('recruitment_contract_stage_steps', 'id')->whereNull('deleted_at')],
            'steps.*.stage' => ['required', Rule::in(array_column(ContractStatusEnum::cases(), 'value'))],
            'steps.*.name_ar' => ['required', 'string', 'max:150'],
            'steps.*.name_en' => ['nullable', 'string', 'max:150'],
            'steps.*.nationality_id' => ['nullable', 'integer', 'exists:nationalities,id'],
            'steps.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'steps.*.is_active' => ['nullable', 'boolean'],
            'steps.*.is_required' => ['nullable', 'boolean'],
            'steps.*.notify_sms' => ['nullable', 'boolean'],
            'steps.*.notify_email' => ['nullable', 'boolean'],
            'steps.*.notification_template' => ['nullable', 'string', 'max:2000'],
            'steps.*._delete' => ['nullable', 'boolean'],
            'notifications' => ['nullable', 'array'],
            'notifications.enabled' => ['nullable', 'boolean'],
            'notifications.stages' => ['nullable', 'array'],
            'notifications.stages.*.sms' => ['nullable', 'boolean'],
            'notifications.stages.*.email' => ['nullable', 'boolean'],
            'notifications.stages.*.template' => ['nullable', 'string', 'max:2000'],
        ]);

        foreach ($validated['steps'] ?? [] as $index => $step) {
            if (empty($step['_delete']) && !\App\Support\RecruitmentStagePolicy::allowsSteps($step['stage'])) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "steps.$index.stage" => 'هذه المرحلة مرتبطة بإجراء مستقل ولا تقبل مراحل فرعية. انقل المرحلة الفرعية إلى مرحلة مناسبة أو احذفها.',
                ]);
            }
        }
        $templates = array_merge(
            array_column($validated['steps'] ?? [], 'notification_template'),
            array_column($validated['notifications']['stages'] ?? [], 'template')
        );
        foreach ($templates as $template) {
            preg_match_all('/\{([^{}]+)\}/u', (string) $template, $matches);
            if (array_diff($matches[1], \App\Support\RecruitmentStageNotificationSettings::VARIABLES)) {
                throw \Illuminate\Validation\ValidationException::withMessages(['notifications' => 'قالب الرسالة يحتوي متغيرات غير مدعومة.']);
            }
        }

        DB::transaction(function () use ($request, $validated) {
            if ($request->boolean('reset_defaults')) {
                RecruitmentContractStageLabels::reset();
                RecruitmentOrderStatusLabels::reset();
                \App\Support\RecruitmentStageNotificationSettings::save([]);
            } else {
                RecruitmentContractStageLabels::save($validated['labels']);
                RecruitmentOrderStatusLabels::save($validated['order_labels']);
                \App\Services\ingaz\RecruitmentContractStageStepService::freezeExistingContracts();
                $this->saveStageSteps($validated['steps'] ?? []);
                \App\Support\RecruitmentStageNotificationSettings::save($validated['notifications'] ?? []);
            }
        });

        return redirect()
            ->route('recruitment-contract-stages.edit')
            ->with('success', __('admin.recruitment_contract_stages_saved'));
    }

    private function saveStageSteps(array $steps): void
    {
        foreach ($steps as $stepData) {
            $id = isset($stepData['id']) ? (int) $stepData['id'] : null;

            if (!empty($stepData['_delete'])) {
                if ($id) {
                    RecruitmentContractStageStep::whereKey($id)->delete();
                }
                continue;
            }

            $payload = [
                'stage' => $stepData['stage'],
                'name_ar' => trim($stepData['name_ar']),
                'name_en' => trim((string) ($stepData['name_en'] ?? '')) ?: null,
                'nationality_id' => $stepData['nationality_id'] ?? null,
                'sort_order' => (int) ($stepData['sort_order'] ?? 0),
                'is_active' => (bool) ($stepData['is_active'] ?? false),
                'is_required' => (bool) ($stepData['is_required'] ?? true),
                'notify_sms' => (bool) ($stepData['notify_sms'] ?? false),
                'notify_email' => (bool) ($stepData['notify_email'] ?? false),
                'notification_template' => trim((string) ($stepData['notification_template'] ?? '')) ?: null,
            ];

            $id
                ? RecruitmentContractStageStep::whereKey($id)->update($payload)
                : RecruitmentContractStageStep::create($payload);
        }
    }
}
