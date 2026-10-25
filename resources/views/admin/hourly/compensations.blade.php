@extends('admin.layout.indexs.index')
@section('page-title','تعويضات العملاء')
@push('css')
<style>
    .cr { direction: rtl; color: #16212e; }
    .cr-hero { border-radius: 18px; padding: 1.2rem 1.4rem; margin-bottom: 1.1rem; color: #fff; background: linear-gradient(105deg, #831843, #be185d 60%, #db2777); box-shadow: 0 10px 30px rgba(131, 24, 67, .15); }
    .cr-hero h3 { margin: 0; font-size: 1.25rem; font-weight: 700; display: flex; gap: .55rem; align-items: center; color: #fff; }
    .cr-hero p { margin: .35rem 0 0; font-size: .85rem; opacity: .92; }
    .cr-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: .85rem; margin-bottom: 1.1rem; }
    .cr-stat { background: #fff; border: 1px solid #eef1f5; border-radius: 14px; padding: .9rem 1.05rem; box-shadow: 0 6px 18px rgba(20, 80, 127, .05); }
    .cr-stat span { display: block; font-size: .78rem; color: #6b7a8c; }
    .cr-stat strong { font-size: 1.35rem; font-variant-numeric: tabular-nums; }
    .cr-stat.is-warn strong { color: #b45309; }
    .cr-reasons { display: flex; flex-wrap: wrap; gap: .45rem; margin-bottom: 1.1rem; }
    .cr-reasons span { background: #fff1f6; color: #9d174d; border: 1px solid #fbcfe0; border-radius: 99px; padding: .25rem .7rem; font-size: .78rem; font-weight: 700; }
    .cr-filter { display: flex; flex-wrap: wrap; gap: .55rem; align-items: end; background: #fff; border: 1px solid #eef1f5; border-radius: 14px; padding: .85rem 1rem; margin-bottom: 1rem; }
    .cr-filter label { display: grid; gap: .25rem; font-size: .76rem; font-weight: 700; margin: 0; }
    .cr-filter .form-control, .cr-filter .form-select { min-width: 150px; border-radius: 10px; }
    .cr-table { width: 100%; background: #fff; border: 1px solid #eef1f5; border-radius: 14px; overflow: hidden; border-collapse: separate; border-spacing: 0; }
    .cr-table th { background: #fdf2f7; color: #831843; font-size: .8rem; padding: .65rem .8rem; text-align: right; }
    .cr-table td { padding: .65rem .8rem; border-top: 1px solid #f3f4f6; font-size: .84rem; vertical-align: top; }
    .cr-table b { font-variant-numeric: tabular-nums; }
    .cr-badge { font-size: .72rem; font-weight: 800; padding: .12rem .5rem; border-radius: 99px; background: #f1f5f9; }
    .cr-badge.is-ok { background: #dcfce7; color: #166534; } .cr-badge.is-wait { background: #fef3c7; color: #92400e; }
    .cr-empty { text-align: center; padding: 2rem; color: #6b7a8c; background: #fff; border: 1px dashed #d6dde6; border-radius: 14px; }
</style>
@endpush
@section('content')
@php $statusTone = ['credited' => 'is-ok', 'pending_payout' => 'is-wait', 'paid_out' => 'is-ok']; @endphp
<div class="container-fluid cr">
    @include('admin.hourly.messages')
    <header class="cr-hero">
        <h3><i class="fa fa-hand-holding-heart"></i> تعويضات العملاء</h3>
        <p>كل تعويض مُنح لعميل بسبب مشكلة في الخدمة: المبلغ والسبب والموظف وطريقة الإيصال. التعويض مصروف مستقل لا يغيّر فواتير الطلبات.</p>
    </header>

    <form class="cr-filter" method="GET">
        <label>من<input type="date" name="from" class="form-control" value="{{ $from }}"></label>
        <label>إلى<input type="date" name="to" class="form-control" value="{{ $to }}"></label>
        <label>السبب<select name="reason" class="form-select"><option value="">الكل</option>@foreach(\App\Services\Hourly\OrderCompensation::REASONS as $k => $v)<option value="{{ $k }}" {{ ($filters['reason'] ?? '') === $k ? 'selected' : '' }}>{{ $v }}</option>@endforeach</select></label>
        <label>الإيصال<select name="destination" class="form-select"><option value="">الكل</option>@foreach(\App\Services\Hourly\OrderCompensation::DESTINATIONS as $k => $v)<option value="{{ $k }}" {{ ($filters['destination'] ?? '') === $k ? 'selected' : '' }}>{{ $v }}</option>@endforeach</select></label>
        <label>الحالة<select name="status" class="form-select"><option value="">الكل</option>@foreach(\App\Services\Hourly\OrderCompensation::STATUSES as $k => $v)<option value="{{ $k }}" {{ ($filters['status'] ?? '') === $k ? 'selected' : '' }}>{{ $v }}</option>@endforeach</select></label>
        <button class="btn btn-primary"><i class="fa fa-filter"></i> عرض</button>
    </form>

    <div class="cr-stats">
        <div class="cr-stat"><span>إجمالي التعويضات في الفترة</span><strong>{{ number_format((float) $totals->total, 2) }} ر.س</strong></div>
        <div class="cr-stat"><span>عدد التعويضات</span><strong>{{ (int) $totals->count }}</strong></div>
        <div class="cr-stat is-warn"><span>بانتظار الصرف (نقد/تحويل)</span><strong>{{ number_format((float) $pending, 2) }} ر.س</strong></div>
    </div>
    @if($byReason->isNotEmpty())
        <div class="cr-reasons">@foreach($byReason as $r)<span>{{ \App\Services\Hourly\OrderCompensation::REASONS[$r->reason] ?? $r->reason }}: {{ number_format((float) $r->total, 2) }} ر.س ({{ $r->count }})</span>@endforeach</div>
    @endif

    @if($rows->isEmpty())
        <div class="cr-empty"><i class="fa fa-circle-check"></i> لا توجد تعويضات في هذه الفترة.</div>
    @else
        <div class="table-responsive">
            <table class="cr-table">
                <thead><tr><th>التاريخ</th><th>الطلب</th><th>العميل</th><th>المبلغ</th><th>السبب</th><th>الإيصال والحالة</th><th>بواسطة</th></tr></thead>
                <tbody>
                @foreach($rows as $row)
                    <tr>
                        <td><bdi>{{ \Carbon\Carbon::parse($row->created_at)->format('Y-m-d H:i') }}</bdi></td>
                        <td><a href="{{ route('admin.hourly-orders.show', $row->hourly_rental_order_id) }}">{{ $row->order_number }}</a></td>
                        <td>{{ $row->customer_name ?? '—' }}<br><small class="text-muted"><bdi>{{ $row->customer_phone }}</bdi></small></td>
                        <td><b>{{ number_format((float) $row->amount, 2) }}</b> ر.س @if($row->over_paid_limit)<br><small class="text-warning">تجاوز المدفوع</small>@endif</td>
                        <td>{{ \App\Services\Hourly\OrderCompensation::REASONS[$row->reason] ?? $row->reason }}@if($row->note)<br><small class="text-muted">{{ $row->note }}</small>@endif</td>
                        <td>{{ \App\Services\Hourly\OrderCompensation::DESTINATIONS[$row->destination] ?? $row->destination }}<br><span class="cr-badge {{ $statusTone[$row->status] ?? '' }}">{{ \App\Services\Hourly\OrderCompensation::STATUSES[$row->status] ?? $row->status }}</span></td>
                        <td>{{ $row->created_by_name ?? '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $rows->links() }}</div>
    @endif
</div>
@endsection
