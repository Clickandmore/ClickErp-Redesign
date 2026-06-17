<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MoyasarService
{
    private string $secretKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->secretKey = config('services.moyasar.secret_key', '');
        $this->baseUrl   = config('services.moyasar.base_url', 'https://api.moyasar.com/v1');
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
    public function createPaymentLink(float $amount, string $description, string $callbackUrl, array $metadata = []): ?array
    {
        // ميسر تستقبل المبلغ بالهللات (× 100)
        $amountInHalala = (int) round($amount * 100);

        $payload = [
            'amount'       => $amountInHalala,
            'currency'     => 'SAR',
            'description'  => $description,
            'callback_url' => $callbackUrl,
            'metadata'     => $metadata,
        ];

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
     * التحقق من توقيع الـ Webhook القادم من ميسر
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $secret = config('services.moyasar.webhook_secret', '');

        if (empty($secret)) {
            return true; // بيئة اختبار بدون secret
        }

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
