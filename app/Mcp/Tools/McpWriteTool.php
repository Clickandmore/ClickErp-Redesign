<?php

namespace App\Mcp\Tools;

use App\Mcp\McpToolException;
use App\Mcp\PendingWrites;
use App\Models\auth\Admin;
use App\Support\McpSettings;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * أداة إضافة بخطوتين يفرضهما الخادم، لا العميل:
 *
 * 1. بدون confirmation_token: تحقق كامل بقواعد الشاشة نفسها، وترجع معاينة ورمز تأكيد. لا يُكتب شيء.
 * 2. مع confirmation_token: تُعاد المعاينة على المدخلات المحفوظة (قد يكون سجل مكرر أُضيف بينهما)، ثم الحفظ.
 *
 * لا تظهر الأداة إلا مع مفتاح الكتابة العام ومستوى «قراءة وإضافة» للموظف (McpAccess).
 */
abstract class McpWriteTool extends McpTool
{
    /**
     * يتحقق ويرجع ['data' => بيانات جاهزة للحفظ، 'preview' => ما يُعرض للمستخدم]. لا يكتب شيئاً.
     *
     * @throws ValidationException
     */
    abstract protected function prepare(array $arguments, Admin $admin): array;

    /** يحفظ ما أرجعه prepare ويرجع وصف السجل المنشأ. */
    abstract protected function commit(array $data, Admin $admin): array;

    /** خصائص المدخلات بدون confirmation_token (يُضاف تلقائياً). */
    abstract protected function fieldsSchema(): array;

    public function isWriteTool(): bool
    {
        return true;
    }

    public function inputSchema(): array
    {
        $schema = $this->fieldsSchema();
        $schema['properties']['confirmation_token'] = [
            'type' => 'string',
            'description' => 'لا ترسله في الاستدعاء الأول. بعد أن يرى المستخدم المعاينة ويوافق صراحةً، أعد الاستدعاء بالرمز الذي أرجعته المعاينة.',
        ];

        return $schema;
    }

    public function handle(array $arguments, Admin $admin): array
    {
        $pending = app(PendingWrites::class);
        $token = trim((string) ($arguments['confirmation_token'] ?? ''));
        unset($arguments['confirmation_token']);

        if ($token === '') {
            $prepared = $this->validated($arguments, $admin);

            if ($this->requiresHumanApproval()) {
                $token = $pending->issue($admin, $this->name(), $arguments, $prepared['preview']);

                return [
                    'status' => 'awaiting_approval',
                    'saved' => false,
                    'preview' => $prepared['preview'],
                    'confirm_url' => route('mcp.write-confirm.show', ['token' => $token]),
                    'confirmation_token' => $token,
                    'expires_in_minutes' => PendingWrites::TTL_MINUTES,
                    'next_step' => 'أعطِ المستخدم confirm_url ليراجع ويوافق بنفسه في ClickERP بحسابه؛ لا يمكنك الموافقة عنه. لم يُحفظ شيء بعد. '
                        . 'بعد موافقته يمكنك معرفة النتيجة باستدعاء الأداة نفسها مع confirmation_token.',
                ];
            }

            return [
                'status' => 'preview',
                'saved' => false,
                'preview' => $prepared['preview'],
                'confirmation_token' => $pending->issue($admin, $this->name(), $arguments),
                'expires_in_minutes' => PendingWrites::TTL_MINUTES,
                'next_step' => 'اعرض هذه المعاينة على المستخدم واطلب موافقته الصريحة. لا تؤكد نيابةً عنه. '
                    . 'بعد موافقته أعد استدعاء الأداة نفسها مع confirmation_token. لم يُحفظ شيء بعد.',
            ];
        }

        if ($previous = $pending->previousResult($admin, $this->name(), $token)) {
            unset($previous['_admin_id'], $previous['_tool']);

            return $previous + ['note' => 'هذا التأكيد نُفذ من قبل؛ هذه نتيجته، ولم يُنشأ سجل جديد.'];
        }

        // النموذج يحمل الرمز، فلا يكفي وحده لتنفيذ العملية عند تفعيل موافقة الموظف.
        if ($this->requiresHumanApproval() && $pending->peek($token)) {
            throw new McpToolException('لم يوافق المستخدم بعد. أعطه confirm_url ليوافق بنفسه في ClickERP، ثم أعد المحاولة.');
        }

        return $this->execute($token, $admin);
    }

    /** ينفذ معاينة محفوظة. تستدعيه الأداة نفسها، أو صفحة موافقة الموظف. */
    public function execute(string $token, Admin $admin): array
    {
        $pending = app(PendingWrites::class);
        $stored = $pending->claim($admin, $this->name(), $token);
        $this->hitWriteLimit($admin);
        $prepared = $this->validated($stored, $admin);

        $result = ['status' => 'created', 'saved' => true] + $this->commit($prepared['data'], $admin);
        $pending->rememberResult($token, $result + ['_admin_id' => (int) $admin->id, '_tool' => $this->name()]);

        return $result;
    }

    /** الإعداد العام، وتجعله الأداة إلزامياً إذا كان أثرها لا يمكن سحبه (إرسال رسائل). */
    protected function requiresHumanApproval(): bool
    {
        return McpSettings::humanConfirmation();
    }

    private function validated(array $arguments, Admin $admin): array
    {
        try {
            return $this->prepare($arguments, $admin);
        } catch (ValidationException $e) {
            $lines = collect($e->errors())->map(fn ($messages, $field) => "- {$field}: " . implode(' ', $messages))->implode("\n");

            throw new McpToolException("البيانات غير صالحة، صحّحها ثم أعد المعاينة:\n{$lines}");
        }
    }

    /** حد عمليات الإضافة في الساعة لكل موظف، من إعدادات Super Admin. */
    private function hitWriteLimit(Admin $admin): void
    {
        $key = 'mcp-write:' . $admin->id;
        $limit = McpSettings::writeLimitPerHour();

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            $minutes = (int) ceil(RateLimiter::availableIn($key) / 60);

            throw new McpToolException("تجاوزت حد الإضافة عبر المساعد ({$limit} عملية في الساعة). حاول بعد {$minutes} دقيقة.");
        }

        RateLimiter::hit($key, 3600);
    }

    /** يحوّل سجلات مثل الجنسية إلى اسم مقروء للمعاينة. */
    protected function titleOf(?string $modelClass, $id): ?string
    {
        if (!$modelClass || !$id) {
            return null;
        }

        $model = $modelClass::query()->find($id);

        return $model ? (string) ($model->title ?? $model->name ?? $model->name_ar ?? $id) : null;
    }
}
