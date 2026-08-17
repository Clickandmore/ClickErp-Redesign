@php
    $sales = collect($sales ?? []);
    $purchases = collect($purchases ?? []);
    $salesTax = (float) $sales->sum('sum_total_tax_value');
    $purchasesTax = (float) $purchases->sum('sum_total_tax_value');
    $salesAmount = (float) $sales->sum('sum_value');
    $purchasesAmount = (float) $purchases->sum('sum_value');
    $netTax = $salesTax - $purchasesTax;
    $fromDate = request('from_date') ?: ($min_date ?? null);
    $toDate = request('to_date') ?: ($max_date ?? null);
@endphp

<div class="col d-flex justify-content-end no-print" id="print_excel">
    <button onclick="handlePrint()" class="btn btn-success ml-2"><i class="fa fa-print"></i></button>
</div>

<div id="print_section">
    @include('finance::finance.print-header')

    <div class="vat-box text-center w-100">
        <div class="date-top">{{ now()->format('d/m/Y H:i') }}</div>
        <div class="title-center">{{ __('finance::finance.VAT tax return') }}</div>

        <div class="row">
            <div class="col" style="text-align: right">
                <div class="item">
                    <span class="label">{{ __('finance::finance.from_date') }} :</span>
                    <span>{{ $fromDate ?: '-' }}</span>
                </div>
                <div class="item">
                    <span class="label">{{ __('finance::finance.name') }} :</span>
                    <span>{{ @$setting_web->website_name ?: '-' }}</span>
                </div>
                <div class="item">
                    <span class="label">{{ __('finance::finance.address') }} :</span>
                    <span>{{ @$setting_web->location ?: '-' }}</span>
                </div>
            </div>

            <div class="col" style="text-align: left">
                <div class="item">
                    <span class="label">{{ __('finance::finance.to_date') }} :</span>
                    <span>{{ $toDate ?: '-' }}</span>
                </div>
                <div class="item">
                    <span class="label">{{ __('finance::finance.Tax number') }} :</span>
                    <span>{{ @$setting_web->tax_number ?: '-' }}</span>
                </div>
            </div>
        </div>
    </div>

    @if ($sales->isEmpty() && $purchases->isEmpty())
        <div role="alert" class="alert alert-danger text-center">{{ __('finance::finance.no_data_found') }}</div>
    @else
        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <div class="alert alert-info text-center mb-0">
                    <strong>{{ number_format($salesAmount, 2) }}</strong><br>
                    {{ __('finance::finance.sales') }}
                </div>
            </div>
            <div class="col-md-3">
                <div class="alert alert-warning text-center mb-0">
                    <strong>{{ number_format($purchasesAmount, 2) }}</strong><br>
                    {{ __('finance::finance.purchases') }}
                </div>
            </div>
            <div class="col-md-3">
                <div class="alert alert-success text-center mb-0">
                    <strong>{{ number_format($salesTax, 2) }}</strong><br>
                    {{ __('finance::finance.tax_sales') }}
                </div>
            </div>
            <div class="col-md-3">
                <div class="alert alert-secondary text-center mb-0">
                    <strong>{{ number_format($purchasesTax, 2) }}</strong><br>
                    {{ __('finance::finance.tax_purchases') }}
                </div>
            </div>
        </div>

        <table class="table table-hover table-bordered table-striped" id="toExcelTable" style="width:100%">
            <thead>
                <tr class="header">
                    <th scope="col">{{ __('finance::finance.type') }}</th>
                    <th scope="col">{{ __('finance::finance.VAT category') }}</th>
                    <th scope="col">{{ __('finance::finance.Amount') }}</th>
                    <th scope="col">{{ __('finance::finance.Value Added Tax') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sales as $key => $sale)
                    <tr style="background-color: #cbfff4">
                        @if($key == 0)
                            <td rowspan="{{ max($sales->count(), 1) }}">{{ __('finance::finance.sales') }}</td>
                        @endif
                        <td>{{ $sale->tax_per ?? 0 }} %</td>
                        <td>{{ number_format((float) ($sale->sum_value ?? 0), 2) }}</td>
                        <td>{{ number_format((float) ($sale->sum_total_tax_value ?? 0), 2) }}</td>
                    </tr>
                @empty
                    <tr style="background-color: #cbfff4">
                        <td>{{ __('finance::finance.sales') }}</td>
                        <td>0 %</td>
                        <td>0.00</td>
                        <td>0.00</td>
                    </tr>
                @endforelse

                @forelse($purchases as $key => $purchase)
                    <tr style="background-color: #ffc3c3">
                        @if($key == 0)
                            <td rowspan="{{ max($purchases->count(), 1) }}">{{ __('finance::finance.purchases') }}</td>
                        @endif
                        <td>{{ $purchase->tax_per ?? 0 }} %</td>
                        <td>{{ number_format((float) ($purchase->sum_value ?? 0), 2) }}</td>
                        <td>{{ number_format((float) ($purchase->sum_total_tax_value ?? 0), 2) }}</td>
                    </tr>
                @empty
                    <tr style="background-color: #ffc3c3">
                        <td>{{ __('finance::finance.purchases') }}</td>
                        <td>0 %</td>
                        <td>0.00</td>
                        <td>0.00</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr style="background-color: #ffffc3">
                    <td colspan="3">{{ __('finance::finance.Net tax due') }}</td>
                    <td>{{ number_format($netTax, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    @endif
</div>

<script>
    function handlePrint() {
        const printSection = document.getElementById('print_section');

        const printWindow = window.open('', '_blank');

        if (!printSection || !printWindow) {
            return;
        }

        const printDocument = printWindow.document;
        printDocument.open();
        printDocument.write('<!doctype html><html dir="rtl"><head><meta charset="utf-8"><title></title></head><body></body></html>');
        printDocument.close();
        printDocument.title = @json(__('finance::finance.tax-declaration'));

        const bootstrapLink = printDocument.createElement('link');
        bootstrapLink.rel = 'stylesheet';
        bootstrapLink.href = @json(asset('admin') . '/assets/css/bootstrap.rtl.min.css');
        printDocument.head.appendChild(bootstrapLink);

        const printStyle = printDocument.createElement('style');
        printStyle.textContent = [
            'body { direction: rtl; font-family: Arial, sans-serif; padding: 20px; }',
            'table { width: 100%; border-collapse: collapse; }',
            'th, td { border: 1px solid #000; padding: 6px 8px; text-align: center; vertical-align: middle; font-size: 12px; }',
            'h5 { text-align: center; margin-bottom: 20px; }',
            '.alert { border: 1px solid #999; padding: 10px; }'
        ].join('\n');
        printDocument.head.appendChild(printStyle);
        printDocument.body.innerHTML = printSection.innerHTML;

        setTimeout(function() {
            printWindow.focus();
            printWindow.print();
        }, 250);
    }
</script>
