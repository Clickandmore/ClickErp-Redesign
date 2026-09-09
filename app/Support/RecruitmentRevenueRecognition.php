<?php

namespace App\Support;

use App\Models\ingaz\Invoice;
use Illuminate\Support\Facades\Schema;
use Nami\Finance\Models\ConfirmSetting;

/**
 * طريقة إثبات إيراد عقود الاستقدام (إعداد من صفحة إعدادات الحسابات).
 *
 * - on_arrival (الافتراضي): قيد الفاتورة يُثبت التزاماً على حساب «التزامات عقود (الجنسية)»،
 *   ويتحول إلى مبيعات عند وصول العاملة. هذا هو السلوك الأصلي للنظام.
 * - on_invoice: قيد الفاتورة يُثبت المبيعات والضريبة مباشرة، وتُثبت فاتورة المورد
 *   الخارجي عند الفاتورة، ويُعكس الاثنان عند الاسترداد أو الإرجاع.
 *
 * الوضع يُحفظ على الفاتورة وقت إنشائها، فالعقود المفوترة قبل تغيير الإعداد تُقفل
 * بالطريقة التي فُوترت بها.
 */
class RecruitmentRevenueRecognition
{
    public const SETTING_KEY = 'recruitment_revenue_recognition';

    public const MODE_ON_ARRIVAL = 'on_arrival';
    public const MODE_ON_INVOICE = 'on_invoice';

    public const INVOICE_COLUMN = 'revenue_recognition_mode';

    public static function modes(): array
    {
        return [self::MODE_ON_ARRIVAL, self::MODE_ON_INVOICE];
    }

    /**
     * الوضع المختار حالياً في الإعدادات (يُطبَّق على الفواتير الجديدة فقط).
     */
    public static function mode(): string
    {
        $value = ConfirmSetting::query()
            ->where('title', self::SETTING_KEY)
            ->latest('updated_at')
            ->value('value');

        return self::normalize($value);
    }

    public static function isOnInvoice(): bool
    {
        return self::mode() === self::MODE_ON_INVOICE;
    }

    /**
     * الوضع الذي فُوترت به فاتورة بعينها. الفواتير القديمة (بلا قيمة محفوظة)
     * تُعامل على أنها التزام عند الوصول لأن قيودها أُنشئت بهذه الطريقة.
     */
    public static function modeForInvoice(?Invoice $invoice): string
    {
        if (!$invoice) {
            return self::MODE_ON_ARRIVAL;
        }

        $stored = $invoice->getAttribute(self::INVOICE_COLUMN);

        return self::normalize($stored);
    }

    public static function invoiceRecognizesOnInvoice(?Invoice $invoice): bool
    {
        return self::modeForInvoice($invoice) === self::MODE_ON_INVOICE;
    }

    public static function invoiceColumnExists(): bool
    {
        return Schema::hasColumn('invoices', self::INVOICE_COLUMN);
    }

    public static function normalize($value): string
    {
        $value = is_string($value) ? trim($value) : '';

        return in_array($value, self::modes(), true) ? $value : self::MODE_ON_ARRIVAL;
    }
}
