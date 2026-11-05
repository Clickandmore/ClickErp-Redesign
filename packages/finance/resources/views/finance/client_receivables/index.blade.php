@extends('click.layout.indexs.index')
@section('page-title', $bladeTitle)
@section('page-links')<li class="breadcrumb-item active">{{ $bladeTitle }}</li>@endsection
@section('style')
<style>
.receivables{direction:rtl;color:#183b49}.receivables .panel{background:#fff;border:1px solid #dce6e9;border-radius:12px;padding:18px;margin-bottom:16px}
.receivables .kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px}.receivables .kpi{background:#f3f7f8;border-radius:8px;padding:12px}.receivables .kpi strong{display:block;font-size:20px}
.receivables table{width:100%;font-size:13px}.receivables th,.receivables td{padding:10px;border-bottom:1px solid #e6edef;text-align:right;vertical-align:top}.receivables th{background:#f3f7f8}.receivables .money{white-space:nowrap;direction:ltr;text-align:left;font-variant-numeric:tabular-nums}.receivables summary{cursor:pointer;color:#216b78}.receivables .details{background:#f8fafb;padding:12px;min-width:550px}.receivables .badge{white-space:normal}.receivables .pagination{flex-wrap:wrap}
</style>
@endsection
@section('content')
<div class="receivables p-3">
    <h3>{{ $bladeTitle }}</h3>
    <p class="text-muted">مستحقات التأجير الحالية من الفواتير والتمديدات بعد التسويات، بما فيها العقود المنتهية والمغلقة. انتهاء العقد لا يعني سداد المبلغ. آخر تحديث: {{ now()->format('Y-m-d H:i') }}.</p>
    <form class="panel" method="GET" action="{{ route('finance.client-receivables.index') }}">
        @if(request('branch_id'))<input type="hidden" name="branch_id" value="{{ request('branch_id') }}">@endif
        @if(request('client_id'))<input type="hidden" name="client_id" value="{{ request('client_id') }}"><p>عرض عقود العميل المحدد — <a href="{{ route('finance.client-receivables.index', request()->only('branch_id')) }}">كل العملاء</a></p>@endif
        <div class="row align-items-end">
            <div class="col-md-3 mb-2"><label for="cr-search">العميل / الجوال / رقم العقد</label><input id="cr-search" class="form-control" name="search" value="{{ $filters['search'] }}"></div>
            <div class="col-md-2 mb-2"><label for="cr-state">حالة العقد</label><select id="cr-state" class="form-control" name="state">@foreach(\Nami\Finance\Services\ClientReceivablesService::STATES as $key => $label)<option value="{{ $key }}" @selected($filters['state'] === $key)>{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-2 mb-2"><label for="cr-extended">التمديد</label><select id="cr-extended" class="form-control" name="extended"><option value="">الكل</option><option value="1" @selected($filters['extended'] === '1')>تم تمديدها</option><option value="0" @selected($filters['extended'] === '0')>دون تمديد</option></select></div>
            <div class="col-md-2 mb-2"><label for="cr-balance">السداد</label><select id="cr-balance" class="form-control" name="balance"><option value="due" @selected($filters['balance'] === 'due')>عليها مستحقات</option><option value="all" @selected($filters['balance'] === 'all')>كل العقود</option></select></div>
            <div class="col-md-1 mb-2"><label for="cr-per">لكل صفحة</label><select id="cr-per" class="form-control" name="per_page">@foreach([25,50,100] as $size)<option value="{{ $size }}" @selected((int)$filters['per_page'] === $size)>{{ $size }}</option>@endforeach</select></div>
            <div class="col-md-2 mb-2"><button class="btn btn-success">عرض</button> <a class="btn btn-outline-secondary" href="{{ route('finance.client-receivables.index') }}">مسح</a></div>
        </div>
    </form>
    <div class="panel kpis">
        <div class="kpi">العملاء<strong>{{ number_format($totals->clients) }}</strong></div>
        <div class="kpi">العقود<strong>{{ number_format($totals->contracts) }}</strong></div>
        <div class="kpi">إجمالي الفواتير والتمديدات<strong>{{ number_format($totals->billed, 2) }}</strong></div>
        <div class="kpi">المسدّد<strong>{{ number_format($totals->paid, 2) }}</strong></div>
        <div class="kpi">التسويات المخصومة<strong>{{ number_format($totals->adjustments, 2) }}</strong></div>
        <div class="kpi">المستحق على العملاء (ريال)<strong>{{ number_format($totals->due, 2) }}</strong></div>
    </div>
    <p class="text-muted">الإجماليات لكل النتائج المطابقة للفلاتر. اضغط اسم العميل لعرض جميع عقوده، أو تفاصيل المبلغ لعرض الفواتير والتمديدات. لا تتم مقاصة أرصدة العقود المختلفة تلقائيًا.</p>
    <div class="panel">
    <div class="table-responsive"><table>
        <thead><tr><th>العميل</th><th>العقد / الحالة</th><th>المدة</th><th>التمديدات</th><th>المفوتر</th><th>المسدّد</th><th>التسويات المخصومة</th><th>المتبقي</th><th>التفاصيل</th></tr></thead>
        <tbody>
        @forelse($rows as $row)
            @php
                $ended = $row->effective_end && \Carbon\Carbon::parse($row->effective_end)->startOfDay()->lt(now()->startOfDay());
                $closed = $row->closed_at || $row->status === 'completed';
                $status = (new \App\Models\RentalContract(['status' => $row->status]))->status_text;
            @endphp
            <tr>
                <td>@if($row->user_id)<a href="{{ route('finance.client-receivables.index', ['client_id' => $row->user_id, 'balance' => 'all', 'branch_id' => request('branch_id')]) }}">{{ $row->client_name ?: 'عميل غير متاح' }}</a>@else عميل غير متاح @endif<div class="text-muted">{{ $row->phone }}</div></td>
                <td>{{ $row->contract_number ?: $row->id }}<div>{{ $status }}</div>@if(!$closed && $row->status !== 'cancelled')<small>{{ $row->effective_end ? ($ended ? 'منتهي المدة' : 'ساري المدة') : 'تاريخ الانتهاء غير محدد' }}</small>@endif</td>
                <td>من: {{ $row->start_date ?: '—' }}<br>النهاية الأصلية: {{ $row->original_end ?: '—' }}<br>النهاية بعد التمديد: {{ $row->effective_end ?: '—' }}@if($row->closed_at)<br>الإغلاق: {{ $row->closed_at }}@endif</td>
                <td>{{ $row->extension_count ? 'ممدد (' . $row->extension_count . ')' : 'دون تمديد' }}</td>
                <td class="money">{{ number_format($row->billed, 2) }}</td><td class="money">{{ number_format($row->paid, 2) }}</td><td class="money">{{ number_format($row->adjustments, 2) }}</td>
                <td class="money">{{ number_format($row->remaining, 2) }}<div>{{ $row->remaining > 0 ? 'مستحق' : ($row->remaining < 0 ? 'رصيد للعميل' : 'لا يوجد مستحق') }}</div></td>
                <td><details><summary>تفاصيل المبلغ</summary><div class="details">
                    <table><thead><tr><th>المستند</th><th>التاريخ</th><th>القيمة</th><th>المسدّد</th><th>التسوية</th><th>المتبقي</th></tr></thead><tbody>
                    @forelse($documents->get($row->id, collect()) as $document)
                        <tr><td>{{ $document->document_type === 'extension' ? 'تمديد' : 'فاتورة' }} #{{ $document->id }}</td><td>{{ $document->created_at }}</td><td>{{ number_format($document->total_due, 2) }}</td><td>{{ number_format($document->paid_amount, 2) }}</td><td>{{ number_format($document->adjustment_amount, 2) }}</td><td>{{ number_format($document->remaining_amount, 2) }}</td></tr>
                    @empty<tr><td colspan="6">لا توجد مستندات مالية صادرة.</td></tr>@endforelse
                    </tbody></table>
                    <h6 class="mt-3">سجل التمديدات</h6>
                    @forelse($extensions->get($row->id, collect()) as $extension)<p>#{{ $extension->id }}: {{ $extension->start_date ?: '—' }} إلى {{ $extension->end_date ?: '—' }} — {{ $extension->extension_value !== null ? number_format($extension->extension_value, 2) . ' ريال' : 'القيمة غير مسجلة' }}</p>@empty<p>لا توجد تمديدات.</p>@endforelse
                </div></details></td>
            </tr>
        @empty<tr><td colspan="9">لا توجد عقود تطابق الفلاتر.</td></tr>@endforelse
        </tbody>
    </table></div>
    @if($rows->total())<p class="mt-3">عرض {{ $rows->firstItem() }} – {{ $rows->lastItem() }} من {{ $rows->total() }} عقد</p>@endif
    {{ $rows->onEachSide(1)->links('pagination::bootstrap-4') }}
    </div>
</div>
@endsection
