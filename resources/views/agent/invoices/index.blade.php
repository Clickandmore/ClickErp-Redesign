@extends('agent.layout')

@php $isAr = app()->getLocale() === 'ar'; @endphp

@section('page-title', $isAr ? 'الفواتير' : 'Invoices')

@section('content')
    <section class="agent-section">
        <form action="{{ route('agent.invoices.index') }}" method="GET" class="agent-filter">
            <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ $isAr ? 'بحث برقم الفاتورة، العقد، أو العميل' : 'Search by invoice, contract, or client' }}">
            <select name="is_paid">
                <option value="">{{ $isAr ? 'كل الحالات' : 'All Payment Statuses' }}</option>
                <option value="1" {{ request('is_paid') === '1' ? 'selected' : '' }}>{{ $isAr ? 'مدفوعة' : 'Paid' }}</option>
                <option value="0" {{ request('is_paid') === '0' ? 'selected' : '' }}>{{ $isAr ? 'غير مدفوعة' : 'Unpaid' }}</option>
            </select>
            <button type="submit" class="agent-button">
                <i class="fa fa-magnifying-glass"></i>
                <span>{{ $isAr ? 'بحث' : 'Search' }}</span>
            </button>
        </form>

        <div class="agent-table-wrap">
            <table class="agent-table">
                <thead>
                    <tr>
                        <th>{{ $isAr ? 'رقم الفاتورة' : 'Invoice #' }}</th>
                        <th>{{ $isAr ? 'رقم العقد' : 'Contract #' }}</th>
                        <th>{{ $isAr ? 'العميل' : 'Client' }}</th>
                        <th>{{ $isAr ? 'الإجمالي' : 'Total' }}</th>
                        <th>{{ $isAr ? 'المسدد' : 'Paid' }}</th>
                        <th>{{ $isAr ? 'المستحق' : 'Due' }}</th>
                        <th>{{ $isAr ? 'الحالة' : 'Status' }}</th>
                        <th>{{ $isAr ? 'تاريخ الدفع' : 'Paid At' }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($contracts as $contract)
                        @php $invoice = $contract->invoice; @endphp
                        <tr>
                            <td><strong>#{{ $invoice->id }}</strong></td>
                            <td>#{{ $contract->id }}</td>
                            <td>{{ $contract->user?->name ?: '-' }}</td>
                            <td><strong>{{ formatMoney($invoice->total_due) }}</strong></td>
                            <td><strong style="color: #10b981;">{{ formatMoney($invoice->is_paid ? $invoice->total_due : 0) }}</strong></td>
                            <td><strong style="color: #ef4444;">{{ formatMoney($invoice->is_paid ? 0 : $invoice->total_due) }}</strong></td>
                            <td>
                                <span class="agent-badge {{ $invoice->is_paid ? 'success' : 'warning' }}">
                                    {{ $invoice->is_paid ? ($isAr ? 'مدفوعة' : 'Paid') : ($isAr ? 'غير مدفوعة' : 'Unpaid') }}
                                </span>
                            </td>
                            <td>{{ $invoice->paid_at ? $invoice->paid_at->format('Y-m-d') : '-' }}</td>
                            <td>
                                <div style="display: flex; gap: 5px;">
                                    <a href="{{ route('agent.contracts.show', $contract) }}" class="agent-button">
                                        <i class="fa fa-eye"></i>
                                        <span>{{ $isAr ? 'عرض العقد' : 'View' }}</span>
                                    </a>
                                    @if(Route::has('invoice.download'))
                                        <a href="{{ route('invoice.download', $invoice->id) }}" class="agent-button" target="_blank" style="background: var(--agent-accent);">
                                            <i class="fa fa-download"></i>
                                            <span>{{ $isAr ? 'تحميل' : 'Download' }}</span>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">{{ $isAr ? 'لا توجد فواتير مطابقة.' : 'No matching invoices were found.' }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="agent-pagination">
            {{ $contracts->links() }}
        </div>
    </section>
@endsection
