<?php

namespace App\Mcp;

use App\Models\auth\Admin;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * رموز التأكيد لأدوات الكتابة: المعاينة تحفظ المدخلات وترجع رمزاً، والتنفيذ لا يتم إلا به.
 *
 * الرمز مربوط بالموظف والأداة، صالح 15 دقيقة، ويُستهلك مرة واحدة. نتيجة التنفيذ تُحفظ
 * بنفس الرمز، فإعادة المحاولة بعد انقطاع الاتصال ترجع السجل نفسه ولا تنشئ سجلاً مكرراً.
 */
class PendingWrites
{
    public const TTL_MINUTES = 15;

    public function issue(Admin $admin, string $tool, array $arguments, array $preview = []): string
    {
        $token = 'cerp_confirm_' . Str::random(32);

        Cache::put($this->key($token), [
            'admin_id' => (int) $admin->id,
            'tool' => $tool,
            'arguments' => $arguments,
            'preview' => $preview,
        ], now()->addMinutes(self::TTL_MINUTES));

        return $token;
    }

    /** المدخلات المحفوظة عند المعاينة؛ المدخلات المرسلة مع التأكيد تُتجاهل حتى لا تتغير البيانات بعد موافقة المستخدم. */
    public function claim(Admin $admin, string $tool, string $token): array
    {
        $pending = Cache::pull($this->key($token));

        if (!$pending || $pending['admin_id'] !== (int) $admin->id || $pending['tool'] !== $tool) {
            throw new McpToolException('رمز التأكيد غير صالح أو انتهت صلاحيته (15 دقيقة) أو استُخدم. أعد المعاينة بدون confirmation_token ثم اطلب تأكيد المستخدم من جديد.');
        }

        return $pending['arguments'];
    }

    /** قراءة بلا استهلاك: صفحة الموافقة تعرض المعاينة قبل أن يوافق الموظف. */
    public function peek(string $token): ?array
    {
        $pending = Cache::get($this->key($token));

        return is_array($pending) ? $pending : null;
    }

    public function rememberResult(string $token, array $result): void
    {
        Cache::put($this->key($token) . ':result', $result, now()->addMinutes(self::TTL_MINUTES));
    }

    public function previousResult(Admin $admin, string $tool, string $token): ?array
    {
        $result = Cache::get($this->key($token) . ':result');

        return is_array($result) && ($result['_admin_id'] ?? null) === (int) $admin->id && ($result['_tool'] ?? null) === $tool
            ? $result
            : null;
    }

    private function key(string $token): string
    {
        return 'mcp:pending-write:' . hash('sha256', $token);
    }
}
