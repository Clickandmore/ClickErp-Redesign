<?php

namespace App\Mcp\Tools;

use App\Mcp\McpToolException;
use App\Models\auth\Admin;
use App\Services\ingaz\MaidCvPdfGenerator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * إضافة عدة سير دفعة واحدة، غالباً من ملف Excel يقرؤه المساعد في المحادثة ويرسل صفوفه.
 *
 * كل صف يمر بتحقق create_maid_cv نفسه، والصف المرفوض لا يوقف الباقي. موافقة الموظف داخل ClickERP
 * إلزامية هنا حتى لو أُوقفت في الإعدادات، لأن المراجعة تشمل صفوفاً كثيرة قرأها النموذج من ملف.
 * الدفعة الموافق عليها تُحسب عملية واحدة في حد الإضافة بالساعة. إعادة إرسالها بعد انقطاع آمنة:
 * ما حُفظ منها يُرفض بسبب تكرار رقم الجواز.
 */
class CreateMaidCvBatchTool extends McpWriteTool
{
    public const MAX_ROWS = 50;

    public function name(): string
    {
        return 'create_maid_cvs_batch';
    }

    public function description(): string
    {
        return 'إضافة عدة سير ذاتية دفعة واحدة (حتى ' . self::MAX_ROWS . ' سيرة في الاستدعاء)، مثل صفوف ملف Excel أرسله المستخدم. '
            . 'حوّل الجنسية والديانة ونوع الخدمة والمهنة والمكتب إلى معرّفات من form_options (form=maid)، وإذا لم تتطابق قيمة فاسأل المستخدم ولا تخمّن. '
            . 'الملف الأكبر يُقسم إلى دفعات. يرجع معاينة بالصفوف الصالحة والمرفوضة وسبب الرفض، ولا يُحفظ شيء إلا بعد أن يوافق المستخدم بنفسه من رابط confirm_url، '
            . 'وعندها تُحفظ الصفوف الصالحة فقط. Create many maid CVs at once, after the user approves them in ClickERP.';
    }

    protected function fieldsSchema(): array
    {
        $row = app(CreateMaidCvTool::class)->inputSchema();
        unset($row['properties']['confirmation_token']);

        return [
            'type' => 'object',
            'properties' => [
                'rows' => ['type' => 'array', 'minItems' => 1, 'maxItems' => self::MAX_ROWS, 'items' => $row, 'description' => 'صف لكل سيرة'],
            ],
            'required' => ['rows'],
        ];
    }

    protected function gateRoute(): ?string
    {
        return 'maids.create';
    }

    protected function requiresHumanApproval(): bool
    {
        return true;
    }

    protected function prepare(array $arguments, Admin $admin): array
    {
        $rows = array_values((array) ($arguments['rows'] ?? []));

        if ($rows === []) {
            throw new McpToolException('أرسل صفاً واحداً على الأقل في rows.');
        }

        if (count($rows) > self::MAX_ROWS) {
            throw new McpToolException('الحد الأقصى ' . self::MAX_ROWS . ' سيرة في الدفعة الواحدة. قسّم الملف إلى دفعات.');
        }

        $single = app(CreateMaidCvTool::class);
        $accepted = [];
        $lines = [];
        $rejected = [];
        $seen = [];

        foreach ($rows as $index => $row) {
            $number = $index + 1;
            $row = (array) $row;
            $label = trim((string) ($row['name'] ?? '')) ?: trim((string) ($row['passport_num'] ?? '')) ?: 'بلا اسم';
            $passport = strtoupper(trim((string) ($row['passport_num'] ?? '')));

            if ($passport !== '' && isset($seen[$passport])) {
                $rejected[] = "صف {$number} ({$label}): رقم الجواز مكرر في الصف {$seen[$passport]}.";
                continue;
            }

            try {
                $prepared = $single->prepareRow($row, $admin);
            } catch (ValidationException $e) {
                $rejected[] = "صف {$number} ({$label}): " . collect($e->errors())->flatten()->implode(' ');
                continue;
            }

            $seen[$passport] = $number;
            $accepted[] = ['number' => $number, 'label' => $label, 'data' => $prepared['data']];
            $preview = $prepared['preview'];
            $lines[] = "صف {$number}: " . collect([
                $preview['name'] ?? null, $preview['passport_num'], $preview['nationality'] ?? null, $preview['religion'] ?? null,
                $preview['service_type'] ?? null, implode('، ', $preview['job_titles'] ?? []), $preview['office'] ?? null,
            ])->filter()->implode(' — ');
        }

        if ($accepted === []) {
            throw new McpToolException("لا يوجد صف صالح للحفظ:\n" . implode("\n", $rejected));
        }

        return [
            'data' => ['accepted' => $accepted, 'rejected' => $rejected],
            'preview' => array_filter([
                'total_rows' => count($rows),
                'will_save' => count($accepted),
                'will_skip' => count($rejected),
                'rows' => $lines,
                'rejected_rows' => $rejected,
            ]),
        ];
    }

    protected function commit(array $data, Admin $admin): array
    {
        $single = app(CreateMaidCvTool::class);
        $created = [];
        $maids = [];
        $failures = [];

        foreach ($data['accepted'] as $row) {
            try {
                $maid = $single->saveRow($row['data'], $admin);
                $maids[] = $maid;
                $created[] = [
                    'row' => $row['number'],
                    'id' => $maid->id,
                    'name' => $maid->name,
                    'passport_num' => $this->mask($maid->passport_num),
                    'cv_code' => $maid->cv_code,
                    'url' => route('maids.show', $maid->id),
                ];
            } catch (Throwable $e) {
                Log::error('MCP batch maid CV failed', ['row' => $row['number'], 'exception' => $e]);
                $failures[] = ['row' => "صف {$row['number']} ({$row['label']})", 'reason' => 'تعذر الحفظ بسبب خطأ داخلي.'];
            }
        }

        if ($created === []) {
            throw new McpToolException('لم تُحفظ أي سيرة بسبب خطأ داخلي. راجع السجل قبل إعادة المحاولة.');
        }

        $this->generateCvFilesAfterResponse($maids);

        return [
            'created_count' => count($created),
            'skipped_count' => count($data['rejected']),
            'failed_count' => count($failures),
            'message' => 'السير المحفوظة: ' . count($created) . '. الصفوف غير المحفوظة: ' . (count($data['rejected']) + count($failures)) . '.',
            'created' => $created,
            'rejected_rows' => $data['rejected'],
            'failures' => $failures,
            'next_step' => 'لرفع صور وفيديو أي عاملة استدعِ maid_media_upload_link برقمها.',
        ];
    }

    /** ملف السيرة يأخذ ثوانٍ لكل عاملة، فلو وُلّد أثناء الطلب لانتهت مهلة السيرفر قبل رد صفحة الموافقة. */
    private function generateCvFilesAfterResponse(array $maids): void
    {
        app()->terminating(function () use ($maids) {
            @set_time_limit(0);

            foreach ($maids as $maid) {
                app(MaidCvPdfGenerator::class)->generateIfEnabled($maid);
            }
        });
    }
}
