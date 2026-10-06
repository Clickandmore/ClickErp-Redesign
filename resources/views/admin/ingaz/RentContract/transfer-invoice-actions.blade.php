@if ($contract->closure_type === 'transferred')
    @if ($contract->transferInvoice)
        @if (\App\Support\InvoiceAuthorization::canView())
            <a class="btn btn-outline-primary" href="{{ route('invoices.show', $contract->transferInvoice->id) }}">عرض فاتورة نقل الخدمات</a>
        @endif
        @if (\App\Support\InvoiceAuthorization::canPrint())
            <a class="btn btn-outline-secondary" target="_blank" rel="noopener" href="{{ route('invoice.download', ['id' => $contract->transferInvoice->id, 'autoprint' => 1]) }}">طباعة فاتورة نقل الخدمات</a>
        @endif
    @elseif (\App\Support\InvoiceAuthorization::canCreateTransfer() && (float) ($contract->info?->sponsorship_transfer_total_amount ?? $contract->info?->sponsorship_transfer_amount) > 0)
        <a class="btn btn-primary" href="{{ route('rental.transfer-invoice.create', $contract->id) }}">إصدار فاتورة نقل الخدمات</a>
    @endif
@endif
