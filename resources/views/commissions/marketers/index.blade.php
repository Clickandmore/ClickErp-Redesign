@extends('admin.layout.indexs.index')

@section('page-title')
    نظام العمولات — المسوّقون
@endsection

@section('page-links')
    <li class="breadcrumb-item"><a href="{{ route('commissions.marketers.index') }}">نظام العمولات</a></li>
    <li class="breadcrumb-item active">المسوّقون</li>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fa fa-users me-2"></i>قائمة المسوّقين وأرصدة العمولات</h5>
                <a href="{{ route('commissions.settings.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fa fa-cog me-1"></i>إعدادات العمولات
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>اسم المسوّق</th>
                                <th>الدور</th>
                                <th class="text-center">إجمالي التفويضات</th>
                                <th class="text-end">الرصيد المستحق (ريال)</th>
                                <th class="text-center">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($marketers as $i => $marketer)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>
                                    <strong>{{ $marketer->name }}</strong>
                                    <div class="text-muted small">{{ $marketer->email }}</div>
                                </td>
                                <td>{{ optional($marketer->role)->name ?? '-' }}</td>
                                <td class="text-center">{{ $marketer->total_delegations }}</td>
                                <td class="text-end">
                                    @php $bal = $marketer->commission_balance; @endphp
                                    <span class="{{ $bal > 0 ? 'text-danger fw-bold' : 'text-muted' }}">
                                        {{ number_format($bal, 2) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('commissions.marketers.show', $marketer->id) }}"
                                       class="btn btn-sm btn-primary">
                                        <i class="fa fa-eye me-1"></i>ملف الطلبات
                                    </a>
                                    @if($bal > 0)
                                    <a href="{{ route('commissions.marketers.payments.index', $marketer->id) }}"
                                       class="btn btn-sm btn-success ms-1">
                                        <i class="fa fa-money-bill me-1"></i>سندات الصرف
                                    </a>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    لا يوجد مسوّقون لديهم تفويضات حتى الآن.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
