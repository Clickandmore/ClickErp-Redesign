<?php

namespace Nami\Finance\Http\Controllers;

use App\Models\ingaz\Invoice;
use App\Models\ingaz\Setting;
use App\Models\RentalContractInvoice;
use App\Http\Controllers\admin\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Nami\Finance\Services\TaxInvoiceStatementExportService;

class TaxInvoiceStatementController extends Controller
{
    public string $mainRoute = 'finance.tax-invoice-statement';
    public array $getData = ['from_date', 'to_date', 'source'];

    public function index(Request $request): JsonResponse|\Illuminate\View\View
    {
        $dataSearch = $this->filters($request);

        if ($request->ajax()) {
            $rows = $this->invoiceRows($dataSearch);
            $returnHTML = view('finance::finance.tax_invoice_statement.result')->with([
                'rows' => $rows,
                'filters' => $dataSearch,
                'supplierName' => $this->supplierName(),
                'supplierTaxNumber' => $this->supplierTaxNumber(),
            ])->render();

            return jsonSuccess([
                'html' => $returnHTML,
                'count' => $rows->count(),
            ]);
        }

        return view('finance::finance.tax_invoice_statement.index', [
            'bladeTitle' => __('finance::finance.tax-invoice-statement'),
            'filters' => $dataSearch,
            'dataTableRoute' => route($this->mainRoute . '.index'),
            'exportRouteTemplate' => route($this->mainRoute . '.export', ['format' => '__FORMAT__']),
        ]);
    }

    public function export(Request $request, TaxInvoiceStatementExportService $exporter, string $format)
    {
        $filters = $this->filters($request);

        return $exporter->download(
            $format,
            $this->invoiceRows($filters),
            $filters,
            $this->supplierName(),
            $this->supplierTaxNumber()
        );
    }

    private function filters(Request $request): array
    {
        $filters = array_merge([
            'from_date' => '',
            'to_date' => now()->format('Y-m-d'),
            'source' => 'all',
        ], array_filter($request->only($this->getData), fn ($value) => $value !== null && $value !== ''));

        if (! in_array($filters['source'], ['all', 'recruitment', 'rental'], true)) {
            $filters['source'] = 'all';
        }

        return $filters;
    }

    private function invoiceRows(array $filters): Collection
    {
        $rows = collect();

        if (in_array($filters['source'], ['all', 'recruitment'], true)) {
            $rows = $rows->merge(
                Invoice::query()
                    ->with(['contract.user', 'ingazDelegation.user', 'financeSale.zatca'])
                    ->when($filters['from_date'] !== '', fn ($query) => $query->whereDate('created_at', '>=', $filters['from_date']))
                    ->when($filters['to_date'] !== '', fn ($query) => $query->whereDate('created_at', '<=', $filters['to_date']))
                    ->get()
                    ->map(fn (Invoice $invoice) => $this->mapRecruitmentInvoice($invoice))
            );
        }

        if (in_array($filters['source'], ['all', 'rental'], true)) {
            $rows = $rows->merge(
                RentalContractInvoice::query()
                    ->with(['rentalContract.user'])
                    ->when($filters['from_date'] !== '', fn ($query) => $query->whereDate('created_at', '>=', $filters['from_date']))
                    ->when($filters['to_date'] !== '', fn ($query) => $query->whereDate('created_at', '<=', $filters['to_date']))
                    ->get()
                    ->map(fn (RentalContractInvoice $invoice) => $this->mapRentalInvoice($invoice))
            );
        }

        return $rows->sortBy([
            ['date', 'asc'],
            ['invoice_number', 'asc'],
        ])->values();
    }

    private function mapRecruitmentInvoice(Invoice $invoice): array
    {
        $client = $invoice->contract?->user ?: $invoice->ingazDelegation?->user;
        $sourceLabel = $invoice->ingaz_delegation_id ? 'فاتورة إيراد / تفويض إنجاز' : 'فاتورة إيراد / طلب استقدام';

        return [
            'invoice_number' => $invoice->id,
            'date' => optional($invoice->created_at)->format('Y-m-d H:i:s'),
            'source' => 'استقدام',
            'type' => $sourceLabel,
            'seller' => $this->supplierName(),
            'seller_tax_number' => $this->supplierTaxNumber(),
            'total_with_tax' => (float) $invoice->total_due,
            'total_without_tax' => max(0, (float) $invoice->subtotal - (float) $invoice->discount),
            'vat_amount' => (float) $invoice->vat_amount,
            'client_name' => $client?->name ?: '-',
            'client_phone' => $this->formatPhone((string) ($client?->phone_code ?? ''), (string) ($client?->phone ?? '')),
            'reference_number' => $invoice->contract_id ?: $invoice->ingaz_delegation_id,
            'created_by' => '-',
            'document_type' => 'فاتورة',
            'zatca_status' => $this->zatcaStatus($invoice->send_to_zatca, $invoice->financeSale?->zatca?->zatca_status),
        ];
    }

    private function mapRentalInvoice(RentalContractInvoice $invoice): array
    {
        $client = $invoice->rentalContract?->user;

        return [
            'invoice_number' => 'R-' . $invoice->id,
            'date' => optional($invoice->created_at)->format('Y-m-d H:i:s'),
            'source' => 'إيجار',
            'type' => 'فاتورة إيراد / عقد إيجار',
            'seller' => $this->supplierName(),
            'seller_tax_number' => $this->supplierTaxNumber(),
            'total_with_tax' => (float) $invoice->total_due,
            'total_without_tax' => max(0, (float) $invoice->subtotal - (float) $invoice->discount),
            'vat_amount' => (float) $invoice->vat_amount,
            'client_name' => $client?->name ?: '-',
            'client_phone' => $this->formatPhone((string) ($client?->phone_code ?? ''), (string) ($client?->phone ?? '')),
            'reference_number' => $invoice->rental_contract_id,
            'created_by' => '-',
            'document_type' => 'فاتورة',
            'zatca_status' => '-',
        ];
    }

    private function supplierName(): string
    {
        $settings = Setting::query()->first();

        return (string) documentSetting('document_company_name', $settings?->website_name ?: config('app.name'));
    }

    private function supplierTaxNumber(): string
    {
        $settings = Setting::query()->first();

        return filled($settings?->tax_number) ? (string) $settings->tax_number : '314232024200003';
    }

    private function formatPhone(string $code, string $phone): string
    {
        $code = trim($code);
        $phone = trim($phone);

        return trim($code . ' ' . $phone) ?: '-';
    }

    private function zatcaStatus(bool $sendToZatca, ?string $status): string
    {
        if (! $sendToZatca) {
            return 'غير مطلوب';
        }

        return match ($status) {
            'REPORTED' => 'مرحل للهيئة',
            'CLEARED' => 'معتمد من الهيئة',
            'NOT_REPORTED', 'NOT_CLEARED' => 'مرفوض من الهيئة',
            default => 'معلق',
        };
    }
}
