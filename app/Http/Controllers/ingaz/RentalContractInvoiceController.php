<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\admin\Controller;
use App\Models\ingaz\Setting;
use App\Models\RentalContractExtension;
use App\Models\RentalContractInvoice;
use App\Models\RentalFinancialAdjustment;
use App\PermKeys;
use App\Services\ingaz\InvoiceCalculationService;
use App\Services\ingaz\RentalContractFinanceService;
use App\Support\InvoiceQrCode;
use App\Support\RentalContractPricing;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDF;

class RentalContractInvoiceController extends Controller
{
    public function createCreditNote(int $id, RentalContractFinanceService $financeService)
    {
        $this->authorizeCreditNoteCreation();
        $invoice = RentalContractInvoice::query()
            ->with(['rentalContract.user', 'rentalContract.info.package'])
            ->findOrFail($id);
        abort_unless($invoice->rentalContract, 422, 'فاتورة التأجير غير مرتبطة بعقد صالح.');

        $remaining = $this->creditNoteRemaining($invoice);
        $breakdown = $financeService->closureAdjustmentAmounts(
            $invoice,
            $invoice->rentalContract,
            $remaining
        );

        return view('admin.ingaz.invoices.rental-credit-note-create', compact('invoice', 'remaining', 'breakdown'));
    }

    public function storeCreditNote(Request $request, int $id, RentalContractFinanceService $financeService)
    {
        $this->authorizeCreditNoteCreation();
        $validated = $request->validate([
            'issue_date' => ['required', 'date', 'before_or_equal:today'],
            'reason' => ['required', 'string', 'max:1000'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'send_to_zatca' => ['nullable', 'boolean'],
        ]);

        try {
            $adjustment = DB::transaction(function () use ($validated, $id, $financeService) {
                $invoice = RentalContractInvoice::query()
                    ->with(['rentalContract.user', 'rentalContract.info.package', 'rentalContract.maid'])
                    ->lockForUpdate()
                    ->findOrFail($id);
                $contract = $invoice->rentalContract;
                if (!$contract) {
                    throw new \RuntimeException('فاتورة التأجير غير مرتبطة بعقد صالح.');
                }

                $issueDate = \Carbon\Carbon::parse($validated['issue_date'])->startOfDay();
                if ($invoice->created_at && $issueDate->lt($invoice->created_at->copy()->startOfDay())) {
                    throw new \RuntimeException('تاريخ إشعار الدائن لا يمكن أن يسبق تاريخ الفاتورة الأصلية.');
                }

                $amount = round((float) $validated['amount'], 2);
                $remaining = $this->creditNoteRemaining($invoice);
                if ($amount > $remaining + 0.01) {
                    throw new \RuntimeException('قيمة إشعار الدائن تتجاوز الرصيد المتبقي من فاتورة التأجير.');
                }

                $breakdown = $financeService->closureAdjustmentAmounts($invoice, $contract, $amount);
                $lineItems = $this->rentalCreditNoteItems($breakdown);
                $adjustment = $contract->financialAdjustments()->create([
                    'maid_id' => $contract->maid_id,
                    'type' => 'expense',
                    'label' => 'إشعار دائن يدوي لفاتورة التأجير #' . $invoice->id,
                    'amount' => $amount,
                    'taxable_amount' => $breakdown['taxable_subtotal'],
                    'non_taxable_amount' => $breakdown['salary_exempt_amount'],
                    'vat_amount' => $breakdown['vat_amount'],
                    'line_items' => $lineItems,
                    'send_to_zatca' => (bool) ($validated['send_to_zatca'] ?? true),
                    'notes' => trim($validated['reason']),
                    'context' => 'manual_credit_note',
                    'issue_date' => $issueDate->toDateString(),
                    'created_by' => Auth::guard('admin')->id(),
                ]);

                $constraint = $financeService->recordClosureAdjustment($contract, $adjustment, $issueDate);
                if (!$constraint) {
                    throw new \RuntimeException('تعذر إنشاء القيد المالي لإشعار الدائن. تأكد من تفعيل القيود التلقائية.');
                }

                if ($adjustment->send_to_zatca) {
                    app(InvoiceController::class)->queueRentalCreditNoteForZatcaSale($adjustment->fresh(), $invoice);
                }

                return $adjustment->fresh(['constraint', 'financeSale']);
            });

            return redirect()->route('rental.credit-note.download', ['id' => $adjustment->id, 'autoprint' => 1])
                ->with('success', 'تم إنشاء إشعار الدائن وقيده المالي دون إنهاء عقد التأجير أو تعديل الفاتورة الأصلية.');
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

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

    public function creditNote(int $id, RentalContractFinanceService $financeService)
    {
        $this->authorizeCreditNoteView();
        $adjustment = RentalFinancialAdjustment::query()
            ->with([
                'rentalContract.info.package',
                'rentalContract.financeInvoice',
            ])
            ->whereIn('context', ['closure', 'manual_credit_note'])
            ->findOrFail($id);
        $contract = $adjustment->rentalContract;
        $invoice = $contract?->financeInvoice;

        abort_unless($contract && $invoice, 404);

        $viewData = $this->prepareInvoiceViewData($invoice->id);
        $breakdown = $financeService->closureAdjustmentAmounts(
            $invoice,
            $contract,
            (float) $adjustment->amount
        );
        $isCreditNote = $adjustment->type === 'expense';
        $taxableSubtotal = (float) $breakdown['taxable_subtotal'];
        $salaryExemptAmount = (float) $breakdown['salary_exempt_amount'];
        $vatAmount = (float) $breakdown['vat_amount'];
        $grandTotal = (float) $breakdown['total'];
        $items = [];

        if ($taxableSubtotal > 0 || $vatAmount > 0) {
            $items[] = [
                'key' => 'rental_closure_service',
                'label' => ($isCreditNote ? 'عكس' : 'إضافة') . ' خدمات التأجير عند إنهاء العقد',
                'qty' => 1,
                'unit' => $taxableSubtotal,
                'line_subtotal' => $taxableSubtotal,
                'line_discount' => 0.0,
                'line_total' => $taxableSubtotal,
                'vat_rate' => $vatAmount > 0 ? InvoiceCalculationService::VAT_RATE : 0.0,
                'vat_amount' => $vatAmount,
                'line_grand_total' => round($taxableSubtotal + $vatAmount, 2),
            ];
        }

        if ($salaryExemptAmount > 0) {
            $items[] = [
                'key' => 'rental_closure_salary',
                'label' => ($isCreditNote ? 'عكس' : 'إضافة') . ' راتب العاملة المعفى من الضريبة',
                'qty' => 1,
                'unit' => $salaryExemptAmount,
                'line_subtotal' => $salaryExemptAmount,
                'line_discount' => 0.0,
                'line_total' => $salaryExemptAmount,
                'vat_rate' => 0.0,
                'vat_amount' => 0.0,
                'line_grand_total' => $salaryExemptAmount,
            ];
        }

        $documentDateTime = optional($adjustment->issue_date)->format('Y-m-d') . ' 00:00:00';
        if (!$adjustment->issue_date) {
            $documentDateTime = optional($adjustment->created_at)->format('Y-m-d H:i:s')
            ?: optional($contract->closed_at)->format('Y-m-d H:i:s')
            ?: now()->format('Y-m-d H:i:s');
        }
        $documentUrl = route('rental.credit-note.download', ['id' => $adjustment->id, 'autoprint' => 1]);
        $barcodeValue = $this->buildInvoiceQrSerial(
            $viewData['supplierName'],
            $viewData['supplierTaxNumber'],
            $documentDateTime,
            $grandTotal,
            $vatAmount
        );

        $viewData = array_merge($viewData, [
            'invoiceTitle' => $isCreditNote ? 'إشعار دائن ضريبي' : 'إشعار مدين ضريبي',
            'invoiceSubtitle' => $isCreditNote ? 'Tax Credit Note' : 'Tax Debit Note',
            'documentNumberLabel' => $isCreditNote ? 'رقم الإشعار الدائن' : 'رقم الإشعار المدين',
            'documentNumber' => $adjustment->id,
            'invoiceStatus' => 'معتمد',
            'serviceDesc' => $adjustment->context === 'manual_credit_note' ? 'مرتجع فاتورة تأجير' : 'تسوية إنهاء عقد تأجير',
            'serviceLongDescription' => ($isCreditNote ? 'استرجاع للعميل' : 'مستحق للشركة')
                . ($adjustment->context === 'manual_credit_note' ? ' دون إنهاء عقد التأجير رقم ' : ' عند إنهاء عقد التأجير رقم ')
                . ($contract->contract_number ?: $contract->id)
                . ' المرتبط بالفاتورة #'
                . $invoice->id,
            'items' => $items,
            'invoiceDate' => substr($documentDateTime, 0, 10),
            'invoiceDateTime' => $documentDateTime,
            'invoiceUrl' => $documentUrl,
            'invoiceBarcodeValue' => $barcodeValue,
            'qrImage' => InvoiceQrCode::render($barcodeValue, $viewData['qrImage']),
            'subtotalBeforeDiscount' => round($taxableSubtotal + $salaryExemptAmount, 2),
            'discountTotal' => 0.0,
            'netSubtotal' => round($taxableSubtotal + $salaryExemptAmount, 2),
            'vatAmount' => $vatAmount,
            'grandTotal' => $grandTotal,
            'backUrl' => route('RentContract.show', $contract->id),
            'originalInvoiceNumber' => $invoice->id,
            'creditNoteReason' => $adjustment->notes,
        ]);

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

        return request()->boolean('download')
            ? $pdf->download('rental-credit-note-' . $id . '.pdf')
            : $pdf->stream('rental-credit-note-' . $id . '.pdf');
    }

    private function creditNoteRemaining(RentalContractInvoice $invoice): float
    {
        $credited = RentalFinancialAdjustment::query()
            ->where('rental_contract_id', $invoice->rental_contract_id)
            ->where('type', 'expense')
            ->whereIn('context', ['closure', 'manual_credit_note'])
            ->sum('amount');

        return round(max(0, (float) $invoice->total_due - (float) $credited), 2);
    }

    private function rentalCreditNoteItems(array $breakdown): array
    {
        return array_values(array_filter([
            (float) $breakdown['taxable_subtotal'] > 0 ? [
                'label' => 'عكس خدمات التأجير', 'quantity' => 1,
                'unit_price' => (float) $breakdown['taxable_subtotal'],
                'tax_rate' => 15, 'vat_amount' => (float) $breakdown['vat_amount'],
                'line_total' => round((float) $breakdown['taxable_subtotal'] + (float) $breakdown['vat_amount'], 2),
            ] : null,
            (float) $breakdown['salary_exempt_amount'] > 0 ? [
                'label' => 'عكس راتب العاملة المعفى من الضريبة', 'quantity' => 1,
                'unit_price' => (float) $breakdown['salary_exempt_amount'],
                'tax_rate' => 0, 'vat_amount' => 0,
                'line_total' => (float) $breakdown['salary_exempt_amount'],
            ] : null,
        ]));
    }

    private function authorizeCreditNoteCreation(): void
    {
        abort_unless(@AdminGuardHasAnyPerm([
            PermKeys::INVOICE_CREDIT_NOTES_CREATE,
            PermKeys::INVOICE_CREDIT_NOTES_CONTROL,
            PermKeys::INVOICES_CONTROL,
        ]), 403);
    }

    private function authorizeCreditNoteView(): void
    {
        abort_unless(@AdminGuardHasAnyPerm([
            PermKeys::INVOICE_CREDIT_NOTES_VIEW,
            PermKeys::INVOICE_CREDIT_NOTES_PRINT,
            PermKeys::INVOICE_CREDIT_NOTES_CONTROL,
            PermKeys::INVOICES_CONTROL,
        ]), 403);
    }

    public function extension(int $id, RentalContractFinanceService $financeService)
    {
        $extension = RentalContractExtension::query()
            ->with(['contract.info.package', 'contract.financeInvoice', 'contract.user', 'contract.maid'])
            ->findOrFail($id);
        $contract = $extension->contract;
        $invoice = $contract?->financeInvoice;

        abort_unless($contract && $invoice, 404);

        $viewData = $this->prepareInvoiceViewData($invoice->id);
        $amounts = $financeService->extensionAmounts($contract, (int) $extension->extension_days);
        $items = [];

        if ((float) $amounts['taxable_subtotal'] > 0 || (float) $amounts['vat_amount'] > 0) {
            $items[] = [
                'key' => 'rental_extension_service',
                'label' => 'خدمة تمديد عقد التأجير - ' . (int) $extension->extension_days . ' يوم',
                'qty' => (int) $extension->extension_days,
                'unit' => (int) $extension->extension_days > 0
                    ? round((float) $amounts['taxable_subtotal'] / (int) $extension->extension_days, 2)
                    : 0,
                'line_subtotal' => (float) $amounts['taxable_subtotal'],
                'line_discount' => 0.0,
                'line_total' => (float) $amounts['taxable_subtotal'],
                'vat_rate' => (float) $amounts['vat_amount'] > 0 ? InvoiceCalculationService::VAT_RATE : 0.0,
                'vat_amount' => (float) $amounts['vat_amount'],
                'line_grand_total' => round(
                    (float) $amounts['taxable_subtotal'] + (float) $amounts['vat_amount'],
                    2
                ),
            ];
        }

        if ((float) $amounts['salary_exempt_amount'] > 0) {
            $items[] = [
                'key' => 'rental_extension_salary',
                'label' => 'راتب العاملة المعفى من الضريبة لفترة التمديد',
                'qty' => (int) $extension->extension_days,
                'unit' => (int) $extension->extension_days > 0
                    ? round((float) $amounts['salary_exempt_amount'] / (int) $extension->extension_days, 2)
                    : 0,
                'line_subtotal' => (float) $amounts['salary_exempt_amount'],
                'line_discount' => 0.0,
                'line_total' => (float) $amounts['salary_exempt_amount'],
                'vat_rate' => 0.0,
                'vat_amount' => 0.0,
                'line_grand_total' => (float) $amounts['salary_exempt_amount'],
            ];
        }

        $documentDateTime = optional($extension->created_at)->format('Y-m-d H:i:s')
            ?: now()->format('Y-m-d H:i:s');
        $documentUrl = route('rental.extension-invoice.download', ['id' => $extension->id, 'autoprint' => 1]);
        $barcodeValue = $this->buildInvoiceQrSerial(
            $viewData['supplierName'],
            $viewData['supplierTaxNumber'],
            $documentDateTime,
            (float) $amounts['total_due'],
            (float) $amounts['vat_amount']
        );
        $viewData = array_merge($viewData, [
            'documentNumberLabel' => 'رقم فاتورة التمديد',
            'documentNumber' => 'EXT-' . $extension->id,
            'invoiceStatus' => match ($extension->payment_status) {
                'paid' => 'مدفوعة',
                'partial' => 'مدفوعة جزئياً',
                default => 'غير مدفوعة',
            },
            'serviceDesc' => 'تمديد عقد تأجير',
            'serviceLongDescription' => 'تمديد عقد التأجير رقم '
                . ($contract->contract_number ?: $contract->id)
                . ' من ' . optional($extension->start_date)->format('Y-m-d')
                . ' إلى ' . optional($extension->end_date)->format('Y-m-d'),
            'items' => $items,
            'invoiceDate' => substr($documentDateTime, 0, 10),
            'invoiceDateTime' => $documentDateTime,
            'invoiceUrl' => $documentUrl,
            'invoiceBarcodeValue' => $barcodeValue,
            'qrImage' => InvoiceQrCode::render($barcodeValue, $viewData['qrImage']),
            'subtotalBeforeDiscount' => (float) $amounts['net_subtotal'],
            'discountTotal' => 0.0,
            'netSubtotal' => (float) $amounts['net_subtotal'],
            'vatAmount' => (float) $amounts['vat_amount'],
            'grandTotal' => (float) $amounts['total_due'],
            'backUrl' => route('RentContract.show', $contract->id),
        ]);

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

        return request()->boolean('download')
            ? $pdf->download('rental-extension-invoice-' . $id . '.pdf')
            : $pdf->stream('rental-extension-invoice-' . $id . '.pdf');
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

        $supplierName = (string) documentSetting('document_company_name', $settings?->website_name ?: config('app.name'));
        $supplierAddress = (string) documentSetting('location', 'الرياض، المملكة العربية السعودية');
        $supplierPhone = $this->printableSetting('phone', '-');
        $supplierOtherPhone = $this->printableSetting('other_phone', '-');
        $supplierEmail = $this->printableSetting('email', '-');
        $supplierTaxNumber = $this->printableSetting('tax_number', '314232024200003');
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
        $breakdown = $this->buildInvoiceBreakdown($invoice);
        $items = $this->buildInvoiceItems($invoice, $breakdown);
        $printedBy = optional(Auth::guard('admin')->user())->name
            ?? optional(Auth::user())->name
            ?? 'النظام';
        $printedAt = now();
        $subtotalBeforeDiscount = $breakdown['subtotal_before_discount'];
        $discountTotal = $breakdown['discount'];
        $netSubtotal = $breakdown['net_subtotal'];
        $vatAmount = $breakdown['vat_amount'];
        $grandTotal = $breakdown['grand_total'];
        $invoiceDate = optional($invoice->created_at)->format('Y-m-d') ?? now()->format('Y-m-d');
        $invoiceDateTime = optional($invoice->created_at)->format('Y-m-d H:i:s') ?? now()->format('Y-m-d H:i:s');
        $invoiceBarcodeValue = $this->buildInvoiceQrSerial(
            $supplierName,
            $supplierTaxNumber,
            $invoiceDateTime,
            $grandTotal,
            $vatAmount
        );
        $qrImage = InvoiceQrCode::render($invoiceBarcodeValue, $qrImage);

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
            'customerDocumentLabel' => $customer?->documentNumberLabel() ?: __('ingaz.national_num'),
            'customerNationalId' => $customer?->documentNumber() ?: '---',
            'customerPhone' => $customerPhone,
            'customerAddress' => $customer?->address ?: '---',
            'accountNumber' => $customer?->account_id ?: ($customer?->id ?: '---'),
            'matchedHijriDate' => '-',
            'invoiceDate' => $invoiceDate,
            'invoiceDateTime' => $invoiceDateTime,
            'invoiceUrl' => route('rental.invoice.download', ['id' => $invoice->id, 'autoprint' => 1]),
            'invoiceBarcodeValue' => $invoiceBarcodeValue,
            'subtotalBeforeDiscount' => $subtotalBeforeDiscount,
            'discountTotal' => $discountTotal,
            'netSubtotal' => $netSubtotal,
            'vatAmount' => $vatAmount,
            'grandTotal' => $grandTotal,
            'backUrl' => route('RentContract.show', $invoice->rental_contract_id),
        ];
    }

    private function buildInvoiceQrSerial(
        string $sellerName,
        string $vatNumber,
        string $invoiceDateTime,
        float $totalWithVat,
        float $vatAmount
    ): string {
        $fields = [
            1 => $sellerName,
            2 => $vatNumber,
            3 => date('Y-m-d\TH:i:s\Z', strtotime($invoiceDateTime)),
            4 => number_format($totalWithVat, 2, '.', ''),
            5 => number_format($vatAmount, 2, '.', ''),
        ];

        $tlv = '';
        foreach ($fields as $tag => $value) {
            $value = (string) $value;
            $tlv .= chr($tag) . chr(strlen($value)) . $value;
        }

        return base64_encode($tlv);
    }

    private function buildInvoiceItems(RentalContractInvoice $invoice, array $breakdown): array
    {
        $contract = $invoice->rentalContract;
        $durationMonths = (int) ($contract?->info?->contract_duration_months ?? 0);
        $durationDays = (int) ($contract?->info?->contract_duration_days ?? 0);
        $maidName = $contract?->maid?->name ?: 'غير محدد';
        $packageName = $invoice->package?->title ?: 'باقة إيجار';
        $baseLabel = $this->buildItemLabel($packageName, $maidName, $durationMonths, $durationDays);
        $salaryExemptAmount = (float) ($breakdown['salary_exempt_amount'] ?? 0);
        $taxableSubtotal = (float) ($breakdown['taxable_subtotal'] ?? 0);
        $vatAmount = (float) ($breakdown['vat_amount'] ?? 0);

        if ($salaryExemptAmount > 0) {
            $items = [];

            if ($taxableSubtotal > 0 || $vatAmount > 0) {
                $items[] = [
                    'key' => 'rental_service',
                    'label' => $baseLabel . ' - خدمة التأجير',
                    'qty' => 1,
                    'unit' => $taxableSubtotal,
                    'line_subtotal' => $taxableSubtotal,
                    'line_discount' => 0.0,
                    'line_total' => $taxableSubtotal,
                    'vat_rate' => $vatAmount > 0 ? InvoiceCalculationService::VAT_RATE : 0.0,
                    'vat_amount' => $vatAmount,
                    'line_grand_total' => round($taxableSubtotal + $vatAmount, 2),
                ];
            }

            $items[] = [
                'key' => 'worker_salary_exempt',
                'label' => 'راتب العاملة المعفى من الضريبة - العاملة: ' . $maidName,
                'qty' => 1,
                'unit' => $salaryExemptAmount,
                'line_subtotal' => $salaryExemptAmount,
                'line_discount' => 0.0,
                'line_total' => $salaryExemptAmount,
                'vat_rate' => 0.0,
                'vat_amount' => 0.0,
                'line_grand_total' => $salaryExemptAmount,
            ];

            return $items;
        }

        return [[
            'key' => 'rental_service',
            'label' => $baseLabel,
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

    private function buildInvoiceBreakdown(RentalContractInvoice $invoice): array
    {
        $subtotalBeforeDiscount = (float) $invoice->subtotal;
        $discount = (float) $invoice->discount;
        $netSubtotal = round(max(0, $subtotalBeforeDiscount - $discount), 2);
        $vatAmount = (float) $invoice->vat_amount;
        $grandTotal = (float) $invoice->total_due;
        $salaryExemptAmount = $this->resolveSalaryExemptAmount($invoice, $netSubtotal);
        $taxableSubtotal = round(max(0, $netSubtotal - $salaryExemptAmount), 2);

        return [
            'subtotal_before_discount' => $subtotalBeforeDiscount,
            'discount' => $discount,
            'net_subtotal' => $netSubtotal,
            'taxable_subtotal' => $taxableSubtotal,
            'salary_exempt_amount' => $salaryExemptAmount,
            'vat_amount' => $vatAmount,
            'grand_total' => $grandTotal,
        ];
    }

    private function buildItemLabel(string $packageName, string $maidName, int $durationMonths, int $durationDays): string
    {
        $label = 'خدمة تأجير عاملة منزلية';
        if ($durationMonths > 0 || $durationDays > 0) {
            $label .= ' - ' . $packageName
                . ' - ' . ($durationMonths > 0 ? $durationMonths . ' شهر' : '')
                . ($durationMonths > 0 && $durationDays > 0 ? ' / ' : '')
                . ($durationDays > 0 ? $durationDays . ' يوم' : '');
        }

        return $label . ' - العاملة: ' . $maidName;
    }

    private function resolveSalaryExemptAmount(RentalContractInvoice $invoice, float $netSubtotal): float
    {
        $contract = $invoice->rentalContract;
        $monthlySalary = (float) ($contract?->info?->monthly_salary ?? 0);

        if ($monthlySalary <= 0) {
            $monthlySalary = (float) ($contract?->maid?->resolved_salary ?? $contract?->maid?->salary ?? 0);
        }

        if ($monthlySalary <= 0 || $netSubtotal <= 0) {
            return 0.0;
        }

        $durationDays = RentalContractPricing::durationDays(
            (int) ($contract?->info?->contract_duration_days ?? 0),
            (int) ($contract?->info?->contract_duration_months ?? 0)
        );

        return RentalContractPricing::fromNetPrice(
            $netSubtotal,
            $monthlySalary,
            $durationDays,
            (int) ($contract?->info?->contract_duration_months ?? 0)
        )['salary_exempt_amount'];
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

    private function printableSetting(string $key, string $default = '-'): string
    {
        $value = trim((string) documentSetting($key, ''));

        return $value === '' || $value === '#' ? $default : $value;
    }
}
