<?php

namespace App\Support;

use App\Models\ingaz\Invoice;
use Illuminate\Support\Facades\Schema;
use Nami\Finance\Models\ConfirmSetting;

/**
 * مكان قيد التكاليف البنكية لمساند المسجلة عند إصدار الفاتورة («التسلسل الجديد»).
 *
 * - separate (الافتراضي): قيد مستقل، فتصدر الفاتورة بثلاثة قيود كما كانت.
 * - with_receipt: سطور العمولة وضريبتها داخل قيد سند القبض نفسه، ويُقيَّد مساند بالصافي،
 *   فتصدر الفاتورة بقيدين. كشف العميل وسند القبض والإقرار الضريبي لا تتغير.
 *
 * الوضع يُثبَّت على الفاتورة وقت التسجيل، فتعديلها لاحقاً أو استردادها يتبع الطريقة التي
 * قُيّدت بها لا الإعداد الحالي.
 */
class MusanedCommissionPosting
{
    public const SETTING_KEY = 'musaned_commission_posting';
    public const INVOICE_COLUMN = 'musaned_commission_posting';
    public const SEPARATE = 'separate';
    public const WITH_RECEIPT = 'with_receipt';

    public static function modes(): array
    {
        return [self::SEPARATE, self::WITH_RECEIPT];
    }

    public static function normalize($value): string
    {
        $value = is_string($value) ? trim($value) : '';

        return in_array($value, self::modes(), true) ? $value : self::SEPARATE;
    }

    public static function mode(): string
    {
        return self::normalize(ConfirmSetting::query()
            ->where('title', self::SETTING_KEY)->latest('updated_at')->value('value'));
    }

    public static function invoiceColumnExists(): bool
    {
        return Schema::hasTable('invoices') && Schema::hasColumn('invoices', self::INVOICE_COLUMN);
    }

    /**
     * الوضع المثبَّت على الفاتورة، وإلا الإعداد الحالي لفاتورة لم تُسجَّل عمولتها بعد.
     */
    public static function modeForInvoice(Invoice $invoice): string
    {
        $pinned = self::invoiceColumnExists() ? $invoice->getAttribute(self::INVOICE_COLUMN) : null;

        return $pinned ? self::normalize($pinned) : self::mode();
    }

    public static function isMergedIntoReceipt(Invoice $invoice): bool
    {
        return self::invoiceColumnExists()
            && $invoice->getAttribute(self::INVOICE_COLUMN) === self::WITH_RECEIPT
            && (bool) $invoice->musaned_commission_constraint_id;
    }
}
