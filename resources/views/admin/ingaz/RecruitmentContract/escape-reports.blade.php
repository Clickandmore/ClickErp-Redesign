@extends('admin.layout.indexs.index')
@section('content')
<div class="card" dir="rtl">
    <div class="card-header"><h4>بلاغات الهروب</h4></div>
    <div class="card-body">
        <form method="GET" class="row mb-3">
            @include('admin.partials.branch-filter', ['id' => 'branchFilter', 'containerClass' => 'col-md-4'])
            <div class="col-auto"><button class="btn btn-primary">بحث</button></div>
        </form>
        <div class="table-responsive"><table class="table table-striped">
            <thead><tr><th>العقد</th><th>مصدر البلاغ</th><th>العميل</th><th>العاملة</th><th>تاريخ الهروب</th><th>مسجل البلاغ</th><th>الإجراءات</th></tr></thead>
            <tbody>@forelse($reports as $report)
                <tr>
                    <td>{{ $report->contract_id }}</td>
                    <td><span class="badge {{ $report->source === 'shelter' ? 'bg-warning text-dark' : 'bg-primary' }}">{{ $report->source === 'shelter' ? 'الإيواء' : 'الاستقدام' }}</span></td>
                    <td>{{ $report->contract?->user?->name }}</td>
                    <td>{{ $report->contract?->maid?->name }}</td>
                    <td>{{ $report->escape_date?->format('Y-m-d') ?? '—' }}</td>
                    <td>{{ $report->reporter?->name ?? '—' }}</td>
                    <td><a class="btn btn-sm btn-primary" href="{{ route($report->source === 'shelter' ? 'ShelterContract.show' : 'RecruitmentContract.escapeReports.show', $report->contract_id) }}">{{ $report->source === 'shelter' ? 'عرض البلاغ' : 'إدارة البلاغ' }}</a></td>
                </tr>
            @empty<tr><td colspan="7" class="text-center">لا توجد بلاغات هروب</td></tr>@endforelse</tbody>
        </table></div>
        {{ $reports->withQueryString()->links() }}
    </div>
</div>
@endsection
