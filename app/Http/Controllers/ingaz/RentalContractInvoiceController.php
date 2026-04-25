<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\admin\Controller;
use App\Models\ingaz\Setting;
use App\Models\RentalContractInvoice;
use App\Services\ingaz\InvoiceCalculationService;
use Illuminate\Support\Facades\Auth;
use PDF;

class RentalContractInvoiceController extends Controller
{
    public function download(int $id)
    {
        $viewData = $this->prepareInvoiceViewData($id);

        if (request()->query('driver') !== 'dompdf') {
            return response()->view('admin.ingaz.invoices.print', $viewData, 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
            ]);
        }

        $pdf = PDF::loadView('admin.ingaz.invoices.print', $viewData)->setPaper('a4', 'portrait');
        $pdf->setOptions([
            'defaultFont' => 'dejavu sans',
            'isHtml5ParserEnabled' => true,
        ]);

        if (request()->boolean('download')) {
            return $pdf->download('rental-invoice-' . $id . '.pdf');
        }

        return $pdf->stream('rental-invoice-' . $id . '.pdf');
    }

    private function prepareInvoiceViewData(int $id): array
    {
        $invoice = RentalContractInvoice::query()
            ->with([
                'package.translations',
                'rentalContract.user',
                'rentalContract.maid.nationality',
                'rentalContract.maid.religion',
                'rentalContract.info',
                'receiptAccount',
            ])
            ->findOrFail($id);

        $contract = $invoice->rentalContract;
        $settings = Setting::query()->first();
        $documentBranding = documentBranding();
        $settingsLogo = documentLogoUrl();
        $settingsZatcaLogo = ($settings && $settings->zatch_logo) ? showFile($settings->zatch_logo) : null;
        $settingsQr = ($settings && $settings->qr_image) ? showFile($settings->qr_image) : null;
        $settingsSecondQr = ($settings && $settings->second_qr_image) ? showFile($settings->second_qr_image) : null;

        $supplierName = (string) documentSetting('document_company_name', $settings?->website_name ?: 'شركة بلاتنيوم الشرقية للاستقدام');
        $supplierAddress = (string) ($settings?->location ?: 'الرياض، المملكة العربية السعودية');
        $supplierPhone = (string) ($settings?->phone ?: '-');
        $supplierOtherPhone = (string) ($settings?->other_phone ?: '-');
        $supplierEmail = (string) ($settings?->email ?: '-');
        $supplierTaxNumber = (string) ($settings?->tax_number ?: '314232024200003');
        $supplierLogo = $settingsLogo ?: $documentBranding['logo_url'];
        $zatcaLogo = $settingsZatcaLogo ?: asset('site/assets/images/zatca-logo.png');
        $qrImage = $settingsQr ?: ($settingsSecondQr ?: asset('site/assets/images/qr1.png'));
        $invoiceTitle = (string) documentSetting('invoice_title', 'فاتورة ضريبية مبسطة');
        $invoiceSubtitle = (string) documentSetting('invoice_subtitle', 'Simplified Tax Invoice');
        $invoiceFooterText = (string) replaceDocumentPlaceholders(documentSetting('invoice_footer_text', ''));

        $customer = $contract?->user;
        $customerPhone = $this->formatPhone(
            (string) ($customer?->phone_code ?? ''),
            (string) ($customer?->phone ?? '')
        );

        $invoiceStatus = match (true) {
            (bool) $invoice->is_paid => 'مدفوعة',
            (float) $invoice->paid_amount > 0 => 'مدفوعة جزئياً',
            default => 'غير مدفوعة',
        };

        $serviceLongDescription = (string) ($invoice->service_description ?: 'عقد تأجير عاملة منزلية');
        $items = $this->buildInvoiceItems($invoice);
        $printedBy = optional(Auth::guard('admin')->user())->name
            ?? optional(Auth::user())->name
            ?? 'النظام';
        $printedAt = now();
        $subtotalBeforeDiscount = (float) $invoice->subtotal;
        $discountTotal = (float) $invoice->discount;
        $netSubtotal = round(max(0, $subtotalBeforeDiscount - $discountTotal), 2);
        $vatAmount = (float) $invoice->vat_amount;
        $grandTotal = (float) $invoice->total_due;

        return [
            'invoice' => $invoice,
            'settings' => $settings,
            'supplierName' => $supplierName,
            'supplierAddress' => $supplierAddress,
            'supplierPhone' => $supplierPhone,
            'supplierOtherPhone' => $supplierOtherPhone,
            'supplierEmail' => $supplierEmail,
            'supplierTaxNumber' => $supplierTaxNumber,
            'supplierLogo' => $supplierLogo,
            'invoiceTitle' => $invoiceTitle,
            'invoiceSubtitle' => $invoiceSubtitle,
            'invoiceFooterText' => $invoiceFooterText,
            'zatcaLogo' => $zatcaLogo,
            'qrImage' => $qrImage,
            'serviceDesc' => 'عقد تأجير',
            'serviceLongDescription' => $serviceLongDescription,
            'items' => $items,
            'invoiceStatus' => $invoiceStatus,
            'printedBy' => $printedBy,
            'printedAt' => $printedAt,
            'customerName' => $customer?->name ?: '---',
            'customerNationalId' => $customer?->national_num ?: '---',
            'customerPhone' => $customerPhone,
            'customerAddress' => $customer?->address ?: '---',
            'accountNumber' => $customer?->account_id ?: ($customer?->id ?: '---'),
            'matchedHijriDate' => '-',
            'invoiceDate' => optional($invoice->created_at)->format('Y-m-d') ?? now()->format('Y-m-d'),
            'invoiceDateTime' => optional($invoice->created_at)->format('Y-m-d H:i:s') ?? now()->format('Y-m-d H:i:s'),
            'invoiceUrl' => route('rental.invoice.download', ['id' => $invoice->id]),
            'subtotalBeforeDiscount' => $subtotalBeforeDiscount,
            'discountTotal' => $discountTotal,
            'netSubtotal' => $netSubtotal,
            'vatAmount' => $vatAmount,
            'grandTotal' => $grandTotal,
            'backUrl' => route('RentContract.show', $invoice->rental_contract_id),
        ];
    }

    private function buildInvoiceItems(RentalContractInvoice $invoice): array
    {
        $contract = $invoice->rentalContract;
        $durationMonths = (int) ($contract?->info?->contract_duration_months ?? 0);
        $durationDays = (int) ($contract?->info?->contract_duration_days ?? 0);
        $maidName = $contract?->maid?->name ?: 'غير محدد';
        $packageName = $invoice->package?->title ?: 'باقة إيجار';

        $label = 'خدمة تأجير عاملة منزلية';
        if ($durationMonths > 0 || $durationDays > 0) {
            $label .= ' - ' . $packageName
                . ' - ' . ($durationMonths > 0 ? $durationMonths . ' شهر' : '')
                . ($durationMonths > 0 && $durationDays > 0 ? ' / ' : '')
                . ($durationDays > 0 ? $durationDays . ' يوم' : '');
        }
        $label .= ' - العاملة: ' . $maidName;

        return [[
            'key' => 'rental_service',
            'label' => $label,
            'qty' => 1,
            'unit' => (float) $invoice->subtotal,
            'line_subtotal' => (float) $invoice->subtotal,
            'line_discount' => (float) $invoice->discount,
            'line_total' => round((float) $invoice->subtotal - (float) $invoice->discount, 2),
            'vat_rate' => (float) $invoice->vat_amount > 0 ? InvoiceCalculationService::VAT_RATE : 0.0,
            'vat_amount' => (float) $invoice->vat_amount,
            'line_grand_total' => (float) $invoice->total_due,
        ]];
    }

    private function formatPhone(string $code, string $phone): string
    {
        $code = trim($code);
        $phone = trim($phone);

        if ($code === '' && $phone === '') {
            return '---';
        }

        if ($code === '') {
            return $phone;
        }

        if ($phone === '') {
            return $code;
        }

        return $code . $phone;
    }
}
