<?php

namespace Nami\Finance\Services;

use Nami\Finance\Models\ZatcaInvoice;
use App\Services\MainService;
use Nami\Finance\Services\ZatcaSettingService;
use Nami\Finance\Models\Sale;
use Nami\Finance\Traits\ZatcaHelperTrait;
use Nami\Finance\Traits\ZatcaInvoicesTrait;
use DOMDocument;
use DB;
use Salla\ZATCA\Helpers\Certificate;
use Nami\Finance\Enums\SalesTypeEnum;

class ShareInvoiceZatcaService extends MainService
{
    use ZatcaInvoicesTrait;

    function __construct(Sale $model, protected ZatcaSettingService $zatcaSettingService)
    {
        $this->model = $model;
    }


    public function getLastHash()
    {
        // Query to get the last invoice record with a hash
        $lastInvoice = ZatcaInvoice::latest('id')->first();

        // If no invoice is found, return base64-encoded "0" for the first invoice PIH
        if (!$lastInvoice || !$lastInvoice->invoice_hash) {
            return base64_encode('0');
        }

        // Return the previous invoice's hash if found
        return $lastInvoice->invoice_hash;
    }


    public function shareInvoice($invoice_id)
    {
        $invoice = $this->model->with(['client', 'items.item', 'originalSale'])->findOrFail($invoice_id);
        $this->validateInvoiceForSharing($invoice);
        $this->validateZatcaConfiguration();

        // Set temporary attributes (do not save them to the database)
        $invoice->setAttribute('is_B2B', (bool) $invoice->client->is_company);
        $invoice->setAttribute('is_invoice', $invoice->type !== SalesTypeEnum::BackSales->value);
        $invoice->setAttribute('PIH', $this->getLastHash());
        $invoice->setAttribute('billing_reference', $invoice->original_sale_id ? (string) $invoice->original_sale_id : null);

        $customer = $invoice->client;
        $items = $invoice->items;

        $resultOfShare = $this->sendToZatca($invoice, $customer, $items);
        $resultArray = json_decode($resultOfShare['result'], true);

        if (!is_array($resultArray)) {
            $httpStatus = (int) ($resultOfShare['http_status'] ?? 0);
            $connectionError = trim((string) ($resultOfShare['curl_error'] ?? ''));
            $details = $connectionError !== ''
                ? $connectionError
                : ($httpStatus > 0 ? 'HTTP ' . $httpStatus : 'لم يصل رد صالح من خادم الهيئة');

            throw new \RuntimeException('تعذر الاتصال بهيئة الزكاة: ' . $details . '.');
        }

        $status = $resultArray['reportingStatus']
            ?? $resultArray['clearanceStatus']
            ?? ($invoice->is_B2B ? 'NOT_CLEARED' : 'NOT_REPORTED');

        // Use updateOrCreate to handle both update and create in a single call
        $zatcaInvoice = ZatcaInvoice::updateOrCreate(
            ['sale_id' => $invoice->id],
            [
                'invoice_hash'            => $resultOfShare['invoice_hash'] ?? null,
                'invoice_zatca_category'  => $resultOfShare['invoice_zatca_category'] ?? null,
                'invoice_zatca_type_code' => $resultOfShare['invoice_zatca_type_code'] ?? null,
                'zatca_response'          => json_encode($resultArray) ?? null,
                'qr'                      => $resultOfShare['qr'] ?? null,
                'xml_file'                => $resultOfShare['file_path'] ?? null,
                'zatca_status'            => $status,
                'uuid'                    => $resultOfShare['uuid'] ?? null,
                'pih'                     => $this->getLastHash() ?? null,
            ]
        );

        // Remove temporary attributes before updating
        $invoice->unsetRelation('client');
        $invoice->unsetRelation('items');
        unset($invoice->is_B2B, $invoice->is_invoice, $invoice->PIH, $invoice->billing_reference);

        $invoice->update(['zatca_invoice_id' => $zatcaInvoice->id]);

        if (!in_array($status, ['REPORTED', 'CLEARED'], true)) {
            throw new \RuntimeException($this->zatcaRejectionMessage($resultArray));
        }

        return $zatcaInvoice;
    }

    private function validateInvoiceForSharing(Sale $invoice): void
    {
        if (!$invoice->client) {
            throw new \RuntimeException('الفاتورة غير مرتبطة بعميل.');
        }

        if ($invoice->items->isEmpty()) {
            throw new \RuntimeException('لا توجد بنود في الفاتورة.');
        }

        $itemsTax = round((float) $invoice->items->sum('tax_value'), 2);
        $invoiceTax = round((float) $invoice->total_tax_value, 2);
        if (abs($itemsTax - $invoiceTax) > 0.01) {
            throw new \RuntimeException('إجمالي ضريبة البنود لا يطابق ضريبة الفاتورة.');
        }

        foreach ($invoice->items as $index => $item) {
            $taxRate = (float) $item->tax_per;
            if ($taxRate <= 0) {
                continue;
            }

            $taxableAmount = round(max(0, ((float) $item->price * (float) $item->amount) - (float) $item->discount_value), 2);
            $expectedTax = round($taxableAmount * ($taxRate / 100), 2);
            if (abs($expectedTax - (float) $item->tax_value) > 0.01) {
                throw new \RuntimeException(
                    'ضريبة البند رقم ' . ($index + 1) . ' غير صحيحة؛ المتوقع '
                    . number_format($expectedTax, 2) . ' والمسجل '
                    . number_format((float) $item->tax_value, 2) . ' ريال.'
                );
            }
        }
    }

    private function validateZatcaConfiguration(): void
    {
        $setting = $this->zatcaSettingService->getSetting();
        $required = [
            'VAT_name' => 'اسم المنشأة',
            'CRN_number' => 'رقم السجل التجاري',
            'VAT_number' => 'الرقم الضريبي',
            'city' => 'المدينة',
            'street' => 'الشارع',
            'postal_zone' => 'الرمز البريدي',
        ];
        $missing = [];

        foreach ($required as $field => $label) {
            if (blank($setting->{$field})) {
                $missing[] = $label;
            }
        }

        if ($missing !== []) {
            throw new \RuntimeException('أكمل إعدادات هيئة الزكاة أولاً: ' . implode('، ', $missing) . '.');
        }

        if (!is_readable(base_path('certificate/private_key.pem'))
            || !is_readable(base_path('certificate/response.json'))) {
            throw new \RuntimeException('شهادة توقيع هيئة الزكاة أو المفتاح الخاص غير متوفرين.');
        }
    }

    private function zatcaRejectionMessage(array $response): string
    {
        $messages = collect(data_get($response, 'validationResults.errorMessages', []))
            ->map(fn ($error) => is_array($error) ? ($error['message'] ?? $error['status'] ?? null) : $error)
            ->filter()
            ->take(3)
            ->implode(' | ');

        return $messages !== ''
            ? 'رفضت هيئة الزكاة الفاتورة: ' . $messages
            : 'رفضت هيئة الزكاة الفاتورة. راجع بيانات الفاتورة وبيانات العميل والمنشأة.';
    }
}
