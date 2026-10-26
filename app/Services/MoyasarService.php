<?php

namespace App\Services;

use App\Support\MoyasarSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MoyasarService
{
    private string $secretKey;
    private string $baseUrl;

    public function __construct()
    {
        $settings = MoyasarSettings::current();

        $this->secretKey = $settings['secret_key'] ?? '';
        $this->baseUrl   = $settings['base_url'] ?? 'https://api.moyasar.com/v1';
    }

    /**
     * إنشاء رابط دفع (Invoice) في ميسر
     *
     * @param  float  $amount        المبلغ بالريال
     * @param  string $description   وصف الدفعة
     * @param  string $callbackUrl   رابط الاستجابة بعد الدفع
     * @param  array  $metadata      بيانات إضافية تُحفظ مع الدفعة
     * @return array{id: string, url: string}|null
     */
    public function createPaymentLink(float $amount, string $description, string $callbackUrl, array $metadata = [], ?string $successUrl = null): ?array
    {
        if (!MoyasarSettings::isEnabled()) {
            Log::info('Moyasar createPaymentLink skipped because gateway is disabled');

            return null;
        }

        // ميسر تستقبل المبلغ بالهللات (× 100)
        $amountInHalala = (int) round($amount * 100);

        $payload = [
            'amount'       => $amountInHalala,
            'currency'     => 'SAR',
            'description'  => MoyasarSettings::invoiceDescription($description),
            'callback_url' => $callbackUrl,
            'metadata'     => $metadata,
        ];
        // يعيد العميل بعد السداد إلى صفحة التأكيد بدل بقائه في صفحة ميسر.
        if ($successUrl !== null) {
            $payload['success_url'] = $successUrl;
        }

        try {
            $response = Http::withBasicAuth($this->secretKey, '')
                ->timeout(30)
                ->post("{$this->baseUrl}/invoices", $payload);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'id'  => $data['id'] ?? null,
                    'url' => $data['url'] ?? null,
                ];
            }

            Log::error('Moyasar createPaymentLink failed', [
                'status'  => $response->status(),
                'body'    => $response->body(),
                'payload' => $payload,
            ]);

            return null;

        } catch (\Throwable $e) {
            Log::error('Moyasar createPaymentLink exception', [
                'message' => $e->getMessage(),
                'payload' => $payload,
            ]);

            return null;
        }
    }

    /**
     * الاستعلام عن حالة دفعة موجودة
     */
    public function getPayment(string $paymentId): ?array
    {
        try {
            $response = Http::withBasicAuth($this->secretKey, '')
                ->timeout(15)
                ->get("{$this->baseUrl}/payments/{$paymentId}");

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('Moyasar getPayment failed', [
                'payment_id' => $paymentId,
                'status'     => $response->status(),
            ]);

            return null;

        } catch (\Throwable $e) {
            Log::error('Moyasar getPayment exception', [
                'payment_id' => $paymentId,
                'message'    => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * الاستعلام عن حالة فاتورة (Invoice)
     */
    public function getInvoice(string $invoiceId): ?array
    {
        try {
            $response = Http::withBasicAuth($this->secretKey, '')
                ->timeout(15)
                ->get("{$this->baseUrl}/invoices/{$invoiceId}");

            if ($response->successful()) {
                return $response->json();
            }

            return null;

        } catch (\Throwable $e) {
            Log::error('Moyasar getInvoice exception', [
                'invoice_id' => $invoiceId,
                'message'    => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * يتحقق من المفتاح السري المحفوظ بطلب قراءة خفيف؛ لا يُنشئ أي فاتورة.
     *
     * @return array{ok: bool, message: string}
     */
    public function testConnection(): array
    {
        if ($this->secretKey === '') {
            return ['ok' => false, 'message' => 'لا يوجد مفتاح سري محفوظ. احفظ المفاتيح أولاً ثم أعد الاختبار.'];
        }

        try {
            $response = Http::withBasicAuth($this->secretKey, '')
                ->timeout(15)
                ->get("{$this->baseUrl}/invoices", ['per' => 1]);
        } catch (\Throwable $e) {
            Log::warning('Moyasar testConnection exception', ['message' => $e->getMessage()]);

            return ['ok' => false, 'message' => 'تعذر الوصول إلى ميسر. تحقق من رابط API واتصال الخادم بالإنترنت.'];
        }

        return match (true) {
            $response->successful() => ['ok' => true, 'message' => 'تم الاتصال بميسر بنجاح، والمفتاح السري صالح.'],
            in_array($response->status(), [401, 403], true) => ['ok' => false, 'message' => 'رفضت ميسر المفتاح السري. تأكد من نسخه كاملاً من لوحة ميسر.'],
            default => ['ok' => false, 'message' => 'ردت ميسر برمز ' . $response->status() . '. راجع رابط API.'],
        };
    }

    /**
     * يميّز الفاتورة غير الموجودة لدى الحساب الحالي (404، كتغيير المفاتيح) عن تعذر الاتصال.
     *
     * @return array{found: ?bool, invoice: ?array} found=null عند تعذر التحقق
     */
    public function lookupInvoice(string $invoiceId): array
    {
        try {
            $response = Http::withBasicAuth($this->secretKey, '')
                ->timeout(15)
                ->get("{$this->baseUrl}/invoices/{$invoiceId}");

            if ($response->successful()) {
                return ['found' => true, 'invoice' => $response->json()];
            }

            return ['found' => $response->status() === 404 ? false : null, 'invoice' => null];
        } catch (\Throwable $e) {
            Log::error('Moyasar lookupInvoice exception', [
                'invoice_id' => $invoiceId,
                'message'    => $e->getMessage(),
            ]);

            return ['found' => null, 'invoice' => null];
        }
    }

    /**
     * إلغاء فاتورة ميسر غير مدفوعة حتى لا يبقى رابطها صالحًا بعد إغلاق السجل.
     */
    public function cancelInvoice(string $invoiceId): ?array
    {
        try {
            $response = Http::withBasicAuth($this->secretKey, '')
                ->timeout(15)
                ->put("{$this->baseUrl}/invoices/{$invoiceId}/cancel");

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('Moyasar cancelInvoice failed', [
                'invoice_id' => $invoiceId,
                'status'     => $response->status(),
                'body'       => $response->body(),
            ]);

            return null;

        } catch (\Throwable $e) {
            Log::error('Moyasar cancelInvoice exception', [
                'invoice_id' => $invoiceId,
                'message'    => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * التحقق من توقيع الـ Webhook القادم من ميسر
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $secret = MoyasarSettings::current()['webhook_secret'] ?? '';

        if (empty($secret)) {
            return false;
        }

        $data = json_decode($payload, true);
        $token = is_array($data) ? ($data['secret_token'] ?? null) : null;
        if (is_string($token) && hash_equals($secret, $token)) return true;

        $expected = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expected, $signature);
    }

    /**
     * التحقق من صحة حالة الدفع المُعادة من callback
     * لا تثق بالـ query string — تحقق دائماً من API مباشرة
     */
    public function verifyPaymentStatus(string $paymentId): string
    {
        $data = $this->getPayment($paymentId);

        if (!empty($data['status'])) {
            return $data['status'];
        }

        $invoice = $this->getInvoice($paymentId);

        return $invoice['status'] ?? 'unknown';
    }
}
