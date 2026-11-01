<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** سجل كل طلب وصل إلى خادم MCP: من طلب، أي أداة، بأي مدخلات، وماذا كانت النتيجة. */
class McpAuditLog extends Model
{
    protected $table = 'mcp_audit_logs';

    public const UPDATED_AT = null;

    private static ?bool $hasResultCount = null;

    protected $guarded = [];

    protected $casts = [
        'arguments' => 'array',
    ];

    public const STATUS_LABELS = [
        'ok' => 'نجح',
        'denied' => 'بلا صلاحية شاشة',
        'disabled' => 'موقوفة من الإدارة',
        'blocked' => 'موظف غير مفعّل',
        'expired' => 'الاشتراك غير ساري',
        'error' => 'خطأ',
        'unknown' => 'أداة غير موجودة',
    ];

    private const SENSITIVE_KEY = '/passport|phone|mobile|whatsapp|iqama|identity|national|id_number|password|secret|token/i';

    /** السيرفر قد يعمل قبل تطبيق الرقعة؛ غياب العمود لا يجب أن يوقف السجل كله. */
    public static function hasResultCount(): bool
    {
        return self::$hasResultCount ??= \Illuminate\Support\Facades\Schema::hasColumn('mcp_audit_logs', 'result_count');
    }

    /**
     * السجل يُقرأ من شاشة Super Admin ويُحفظ شهوراً، فلا تُخزَّن فيه أرقام الجواز والجوال كاملة:
     * الحقول الحساسة بالاسم، وأي نص من 7 أرقام فأكثر (جوال أو هوية في خانة بحث) يبقى منه آخر 4.
     */
    public static function sanitizeArguments(array $arguments): array
    {
        $clean = [];

        foreach ($arguments as $key => $value) {
            if (is_array($value)) {
                $clean[$key] = self::sanitizeArguments($value);
            } elseif (is_string($value) || is_int($value)) {
                $text = (string) $value;
                $sensitiveKey = is_string($key) && preg_match(self::SENSITIVE_KEY, $key);
                $clean[$key] = ($sensitiveKey || preg_match('/^\+?\d{7,}$/', $text)) ? self::mask($text) : $value;
            } else {
                $clean[$key] = $value;
            }
        }

        return $clean;
    }

    private static function mask(string $value): string
    {
        $length = mb_strlen($value);

        return $length <= 4 ? str_repeat('*', $length) : str_repeat('*', $length - 4) . mb_substr($value, -4);
    }
}
