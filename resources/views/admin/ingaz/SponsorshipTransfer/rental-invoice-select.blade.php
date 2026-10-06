@extends('admin.layout.indexs.index')
@section('page-title', 'إصدار فاتورة نقل الخدمات')
@section('content')
<div class="card">
    <div class="card-header"><h4>إصدار فاتورة نقل الخدمات — طلب #{{ $transfer->id }}</h4></div>
    <div class="card-body">
        <p>العميل: {{ $transfer->newSponsor?->name }} — العاملة: {{ $transfer->contract?->maid?->name }}</p>
        <p>اختر عقد التأجير الذي سُجل عليه مبلغ نقل الخدمات لهذه العملية.</p>
        @forelse ($contracts as $contract)
            <div class="d-flex justify-content-between align-items-center border rounded p-3 mb-2">
                <span>{{ $contract->contract_number ?: $contract->id }} — مبلغ النقل: {{ number_format((float) ($contract->info?->sponsorship_transfer_total_amount ?? $contract->info?->sponsorship_transfer_amount), 2) }} ريال</span>
                <a class="btn btn-primary" href="{{ route('rental.transfer-invoice.create', ['id' => $contract->id, 'transfer_id' => $transfer->id]) }}">{{ $contract->transferInvoice ? 'ربط الفاتورة الموجودة' : 'إصدار فاتورة نقل الخدمات' }}</a>
            </div>
        @empty
            <div class="alert alert-warning">لم يُعثر على عقد تأجير بحالة «تم النقل» لنفس العاملة والعميل. راجع عقد التأجير الذي سُجل عليه مبلغ النقل.</div>
        @endforelse
        <a class="btn btn-outline-secondary" href="{{ route('SponsorshipTransfer.show', $transfer->id) }}">العودة لطلب النقل</a>
    </div>
</div>
@endsection
