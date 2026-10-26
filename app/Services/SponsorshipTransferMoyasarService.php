<?php

namespace App\Services;

use App\Models\SponsorshipTransfer;
use App\Services\ingaz\SponsorshipTransferPaymentService;
use App\Support\MoyasarPaymentSnapshot;
use App\Support\MoyasarSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * دفع طلب نقل الخدمات عبر فاتورة ميسر. تأكيد السداد يُصدر سند قبض عبر خدمة النقل المالية
 * نفسها، وكل استرداد يُصدر سند صرف مستقلاً؛ لا تُقبل حالة السداد من الإشعار أو المتصفح.
 */
class SponsorshipTransferMoyasarService
{
    public const CLOSED_TRANSFER_STATUSES = ['failed', 'rejected', 'cancelled'];
    public const CLOSED_INVOICE_STATUSES = ['expired', 'canceled', 'cancelled', 'voided'];

    public function __construct(private MoyasarService $gateway, private SponsorshipTransferPaymentService $finance) {}

    public static function isAvailable(): bool
    {
        return Schema::hasColumn('sponsorship_transfers', 'moyasar_invoice_id');
    }

    /** بيانات الدفع لطلب له فاتورة ميسر تُحدّث من البوابة فقط. */
    public static function validateManualPayment(array $input, ?SponsorshipTransfer $transfer = null): void
    {
        if ($transfer?->moyasar_invoice_id) {
            foreach (['payment_type', 'payment_status', 'paid_amount', 'contract_value'] as $field) {
                if (!array_key_exists($field, $input)) {
                    continue;
                }
                $changed = in_array($field, ['paid_amount', 'contract_value'], true)
                    ? abs((float) $input[$field] - (float) $transfer->$field) > 0.009
                    : (string) $input[$field] !== (string) $transfer->$field;
                if ($changed) {
                    throw ValidationException::withMessages([$field => 'بيانات دفع ميسر تُحدّث من البوابة فقط بعد إنشاء رابط الدفع.']);
                }
            }

            return;
        }

        if (($input['payment_type'] ?? null) !== 'moyasar') {
            return;
        }
        if ($transfer?->payment_type !== 'moyasar' && !MoyasarSettings::isEnabled()) {
            throw ValidationException::withMessages(['payment_type' => 'بوابة ميسر غير مفعلة حالياً من الإعدادات.']);
        }
        if (!self::isAvailable()) {
            throw ValidationException::withMessages(['payment_type' => 'يلزم تحديث قاعدة البيانات لتفعيل دفع نقل الخدمات عبر ميسر.']);
        }
        if ((float) ($input['paid_amount'] ?? 0) != 0 || (float) ($transfer?->paid_amount ?? 0) != 0
            || ($input['payment_status'] ?? '') !== 'pending' || (float) ($input['contract_value'] ?? 0) <= 0) {
            throw ValidationException::withMessages(['payment_status' => 'الدفع عبر ميسر يتطلب قيمة موجبة وحالة "معلق" ومبلغاً مدفوعاً صفراً؛ يُسجَّل السداد تلقائياً عند تأكيد ميسر.']);
        }
    }

    public static function canSend(?SponsorshipTransfer $transfer): bool
    {
        return $transfer && !$transfer->trashed() && $transfer->payment_type === 'moyasar'
            && !$transfer->isRentalWorkflow()
            && !in_array($transfer->status, self::CLOSED_TRANSFER_STATUSES, true)
            && $transfer->payment_status !== 'paid' && (float) $transfer->remaining_amount > 0.009
            && !$transfer->moyasar_paid_at;
    }

    public function ensureLink(SponsorshipTransfer $transfer): SponsorshipTransfer
    {
        return DB::transaction(function () use ($transfer) {
            $locked = SponsorshipTransfer::query()->whereKey($transfer->id)->lockForUpdate()->firstOrFail();
            if ($locked->moyasar_invoice_id && !in_array((string) $locked->moyasar_payment_status, self::CLOSED_INVOICE_STATUSES, true)) {
                return $locked;
            }
            if (!self::canSend($locked)) {
                throw new \RuntimeException('لا يمكن إنشاء رابط دفع لهذا الطلب؛ تأكد أن طريقة الدفع ميسر وأن الطلب غير مغلق وعليه مبلغ متبقٍ.');
            }
            if (!MoyasarSettings::isEnabled()) {
                throw new \RuntimeException('بوابة ميسر غير مفعلة.');
            }
            // فاتورة منتهية سُدد منها شيء لا تُستبدل؛ ما سُدد سُجل بسند قبض مستقل.
            if ($locked->moyasar_invoice_id && $locked->moyasar_paid_at) {
                throw new \RuntimeException('سُددت فاتورة ميسر لهذا الطلب.');
            }

            $amount = round((float) $locked->remaining_amount, 2);
            $token = (string) Str::uuid();
            $callback = route('transfer.moyasar.callback', ['token' => $token]);
            $result = $this->gateway->createPaymentLink($amount, 'سداد طلب نقل الخدمات رقم ' . $locked->id,
                $callback, ['sponsorship_transfer_id' => (string) $locked->id], $callback);
            if (empty($result['id']) || empty($result['url']) || parse_url($result['url'], PHP_URL_SCHEME) !== 'https') {
                throw new \RuntimeException('تعذر إنشاء رابط الدفع من ميسر.');
            }

            $locked->update([
                'moyasar_callback_token' => $token,
                'moyasar_invoice_id' => $result['id'],
                'moyasar_payment_url' => $result['url'],
                'moyasar_amount' => $amount,
                'moyasar_payment_status' => 'initiated',
                'moyasar_payment_id' => null,
                'payment_link_sent_at' => null,
                'payment_link_sent_channel' => null,
            ]);

            return $locked;
        });
    }

    public function synchronize(SponsorshipTransfer $transfer): SponsorshipTransfer
    {
        if (!$transfer->moyasar_invoice_id) {
            return $transfer;
        }
        // الإشعار أو رجوع العميل مجرد تنبيه؛ نجلب الفاتورة المحفوظة بأنفسنا.
        $invoice = $this->gateway->getInvoice($transfer->moyasar_invoice_id);
        if (!$invoice) {
            throw new \RuntimeException('تعذر التحقق من حالة الدفع لدى ميسر.');
        }

        return DB::transaction(function () use ($transfer, $invoice) {
            $locked = SponsorshipTransfer::withTrashed()->whereKey($transfer->id)->lockForUpdate()->firstOrFail();
            if ($locked->moyasar_invoice_id !== ($invoice['id'] ?? null)) {
                throw new \RuntimeException('فاتورة ميسر لا تطابق طلب نقل الخدمات.');
            }
            $snapshot = MoyasarPaymentSnapshot::fromInvoice($invoice, $locked->moyasar_invoice_id,
                (float) $locked->moyasar_amount, $locked->moyasar_payment_id);
            if (!$snapshot) {
                if (!$locked->moyasar_paid_at) {
                    $locked->update(['moyasar_payment_status' => (string) ($invoice['status'] ?? 'unknown')]);
                }

                return $locked;
            }

            $refunded = max((float) $locked->moyasar_refunded_amount, (float) $snapshot['refunded']);
            $delta = round($refunded - (float) $locked->moyasar_refunded_amount, 2);
            $paid = (float) $locked->paid_amount;

            if (!$locked->moyasar_paid_at) {
                $receipt = $this->finance->recordGatewayReceipt($locked, (float) $locked->moyasar_amount, $locked->moyasar_invoice_id);
                $locked->refresh();
                $locked->update(['moyasar_receipt_bond_id' => $receipt['bond']->id]);
                $paid = round($paid + (float) $locked->moyasar_amount, 2);
            }

            if ($delta > 0) {
                $receiptBondId = (int) $locked->moyasar_receipt_bond_id;
                if (!$receiptBondId) {
                    throw new \RuntimeException('لا يوجد سند قبض لعكس استرداد ميسر عليه.');
                }
                $bond = $this->finance->recordGatewayRefund($locked, $delta, $locked->moyasar_invoice_id, $receiptBondId);
                DB::table('sponsorship_transfer_moyasar_refunds')->insert([
                    'sponsorship_transfer_id' => $locked->id, 'amount' => $delta, 'cumulative_amount' => $refunded,
                    'bond_id' => $bond->id, 'created_at' => now(), 'updated_at' => now(),
                ]);
                $paid = round($paid - $delta, 2);
                if ($paid < 0) {
                    throw new \RuntimeException('الاسترداد يتجاوز المبلغ المدفوع المسجل؛ راجع سندات الطلب.');
                }
            }

            $remaining = max(0, round((float) $locked->contract_value - $paid, 2));
            $locked->update([
                'paid_amount' => $paid,
                'remaining_amount' => $remaining,
                'payment_status' => $remaining <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'pending'),
                'moyasar_payment_status' => $refunded > 0
                    ? ($refunded >= (float) $locked->moyasar_amount ? 'refunded' : 'partially_refunded') : 'paid',
                'moyasar_refunded_amount' => $refunded,
                'moyasar_payment_id' => $snapshot['id'] ?: $locked->moyasar_payment_id,
                'moyasar_paid_at' => $locked->moyasar_paid_at ?: now(),
                'payment_confirmed_at' => $locked->payment_confirmed_at ?: now(),
            ]);

            return $locked;
        }, 3);
    }

    /**
     * يُلغي فاتورة ميسر غير المدفوعة قبل إغلاق الطلب؛ ما سُدد للتو يُسجل أولاً ويُوقف الإغلاق للمراجعة.
     * @throws ValidationException
     */
    public function cancelOpenInvoice(SponsorshipTransfer $transfer): void
    {
        if (!$transfer->moyasar_invoice_id || $transfer->moyasar_paid_at
            || in_array((string) $transfer->moyasar_payment_status, self::CLOSED_INVOICE_STATUSES, true)) {
            return;
        }

        try {
            $transfer = $this->synchronize($transfer);
        } catch (\Throwable $exception) {
            // فاتورة غير موجودة لدى الحساب الحالي لا يمكن سدادها؛ لا تمنع الإغلاق.
            if ($this->gateway->lookupInvoice($transfer->moyasar_invoice_id)['found'] === false) {
                SponsorshipTransfer::withTrashed()->whereKey($transfer->id)->update(['moyasar_payment_status' => 'canceled']);

                return;
            }
            report($exception);
            throw ValidationException::withMessages(['payment' => 'تعذر التحقق من فاتورة ميسر قبل الإغلاق. أعد المحاولة بعد قليل.']);
        }
        if ($transfer->moyasar_paid_at) {
            throw ValidationException::withMessages(['payment' => 'سدد العميل فاتورة ميسر للتو وسُجل سند القبض. حدّث الصفحة وراجع التسوية ثم أعد المحاولة.']);
        }
        if (in_array((string) $transfer->moyasar_payment_status, self::CLOSED_INVOICE_STATUSES, true)) {
            return;
        }

        $result = $this->gateway->cancelInvoice($transfer->moyasar_invoice_id);
        if (!in_array($result['status'] ?? null, ['canceled', 'cancelled'], true)) {
            throw ValidationException::withMessages(['payment' => 'تعذر إلغاء فاتورة ميسر. حدّث حالة الدفع ثم أعد محاولة الإغلاق.']);
        }
        SponsorshipTransfer::withTrashed()->whereKey($transfer->id)->update(['moyasar_payment_status' => 'canceled']);
    }
}
