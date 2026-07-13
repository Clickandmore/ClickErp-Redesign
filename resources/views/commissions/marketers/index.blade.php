@extends('admin.layout.indexs.index')

@section('page-title')
    نظام العمولات — المسوّقون
@endsection

@section('page-links')
    <li class="breadcrumb-item"><a href="{{ route('commissions.marketers.index') }}">نظام العمولات</a></li>
    <li class="breadcrumb-item active">المسوّقون</li>
@endsection

@section('content')
@php
    $totalMarketers      = $marketers->count();
    $totalDelegations    = $marketers->sum('total_delegations');
    $totalPendingBalance = $marketers->sum('commission_balance');
    $marketersWithDues   = $marketers->where('commission_balance', '>', 0)->count();
@endphp

<div class="row g-3 mb-3">
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center me-3"
                     style="width:52px;height:52px;">
                    <i class="fa fa-users fa-lg"></i>
                </div>
                <div>
                    <div class="text-muted small">إجمالي المسوّقين</div>
                    <div class="fs-4 fw-bold">{{ number_format($totalMarketers) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="rounded-circle bg-info bg-opacity-10 text-info d-flex align-items-center justify-content-center me-3"
                     style="width:52px;height:52px;">
                    <i class="fa fa-file-signature fa-lg"></i>
                </div>
                <div>
                    <div class="text-muted small">إجمالي التفويضات</div>
                    <div class="fs-4 fw-bold">{{ number_format($totalDelegations) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="rounded-circle bg-warning bg-opacity-10 text-warning d-flex align-items-center justify-content-center me-3"
                     style="width:52px;height:52px;">
                    <i class="fa fa-user-clock fa-lg"></i>
                </div>
                <div>
                    <div class="text-muted small">لديهم أرصدة مستحقّة</div>
                    <div class="fs-4 fw-bold">{{ number_format($marketersWithDues) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="rounded-circle bg-danger bg-opacity-10 text-danger d-flex align-items-center justify-content-center me-3"
                     style="width:52px;height:52px;">
                    <i class="fa fa-sack-dollar fa-lg"></i>
                </div>
                <div>
                    <div class="text-muted small">إجمالي المستحقات (ريال)</div>
                    <div class="fs-4 fw-bold text-danger">{{ number_format($totalPendingBalance, 2) }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-info border-0 shadow-sm d-flex align-items-start" role="alert">
    <i class="fa fa-lightbulb fa-lg text-warning mt-1 me-3"></i>
    <div class="flex-grow-1">
        <h6 class="alert-heading mb-2">كيف يظهر المسوّق في هذه القائمة؟</h6>
        <p class="mb-2 small">
            المسوّقون <strong>لا يُضافون يدوياً</strong> من هذه الصفحة — بل يظهرون تلقائياً بمجرّد ربطهم بتفويض إنجاز واحد على الأقل عبر حقل «المسوّق» في نموذج التفويض.
        </p>
        <div class="d-flex flex-wrap gap-3 small">
            <div><span class="badge bg-primary rounded-pill me-1">1</span> افتح <a href="{{ route('ingaz-delegations.create') }}" class="alert-link">إنشاء تفويض إنجاز جديد</a> أو عدّل تفويضاً موجوداً.</div>
            <div><span class="badge bg-primary rounded-pill me-1">2</span> حدّد الأدمن المسوّق من قائمة <strong>«المسوّق»</strong> داخل النموذج.</div>
            <div><span class="badge bg-primary rounded-pill me-1">3</span> احفظ التفويض ليظهر المسوّق فوراً في هذه الصفحة.</div>
        </div>
    </div>
    <button type="button" class="btn-close ms-2" data-bs-dismiss="alert" aria-label="Close"></button>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h5 class="mb-0">
            <i class="fa fa-users text-primary me-2"></i>قائمة المسوّقين وأرصدة العمولات
        </h5>
        <div class="d-flex gap-2">
            <a href="{{ route('ingaz-delegations.create') }}" class="btn btn-sm btn-primary">
                <i class="fa fa-plus me-1"></i>تفويض جديد
            </a>
            <a href="{{ route('commissions.settings.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fa fa-cog me-1"></i>إعدادات العمولات
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:60px;">#</th>
                        <th>اسم المسوّق</th>
                        <th>الدور</th>
                        <th class="text-center">إجمالي التفويضات</th>
                        <th class="text-end">الرصيد المستحق (ريال)</th>
                        <th class="text-center" style="width:220px;">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($marketers as $i => $marketer)
                    @php $bal = $marketer->commission_balance; @endphp
                    <tr>
                        <td class="text-muted">{{ $i + 1 }}</td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center me-2 fw-bold"
                                     style="width:38px;height:38px;">
                                    {{ mb_substr($marketer->name, 0, 1) }}
                                </div>
                                <div>
                                    <div class="fw-bold">{{ $marketer->name }}</div>
                                    <div class="text-muted small">{{ $marketer->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            @if(optional($marketer->role)->name)
                                <span class="badge bg-light text-dark border">{{ $marketer->role->name }}</span>
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="badge bg-info bg-opacity-10 text-info fs-6">
                                {{ $marketer->total_delegations }}
                            </span>
                        </td>
                        <td class="text-end">
                            @if($bal > 0)
                                <span class="text-danger fw-bold fs-6">
                                    <i class="fa fa-arrow-up small"></i>
                                    {{ number_format($bal, 2) }}
                                </span>
                            @else
                                <span class="text-muted">{{ number_format($bal, 2) }}</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <a href="{{ route('commissions.marketers.show', $marketer->id) }}"
                               class="btn btn-sm btn-outline-primary" title="عرض ملف المسوّق واحتساب العمولات">
                                <i class="fa fa-eye me-1"></i>الملف
                            </a>
                            @if($bal > 0)
                            <a href="{{ route('commissions.marketers.payments.index', $marketer->id) }}"
                               class="btn btn-sm btn-success" title="إصدار سند صرف العمولة">
                                <i class="fa fa-money-bill me-1"></i>سند صرف
                            </a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6">
                            <div class="text-center py-5">
                                <div class="mb-3">
                                    <i class="fa fa-users-slash text-muted" style="font-size:64px;opacity:0.4;"></i>
                                </div>
                                <h5 class="text-muted mb-2">لا يوجد مسوّقون حتى الآن</h5>
                                <p class="text-muted mb-4">
                                    لم يتم ربط أي أدمن كمسوّق على أي تفويض إنجاز حتى الآن.<br>
                                    ابدأ بإنشاء تفويض جديد وحدّد المسوّق داخله ليظهر هنا.
                                </p>
                                <a href="{{ route('ingaz-delegations.create') }}" class="btn btn-primary">
                                    <i class="fa fa-plus me-1"></i>إنشاء تفويض إنجاز
                                </a>
                                <a href="{{ route('commissions.settings.index') }}" class="btn btn-outline-secondary ms-2">
                                    <i class="fa fa-cog me-1"></i>ضبط إعدادات العمولات
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($marketers->count())
    <div class="card-footer bg-white text-muted small">
        <i class="fa fa-info-circle me-1"></i>
        الرصيد المستحق يمثّل ما تراكم للمسوّق من عمولات معتمدة ولم يُدفع بعد. اضغط «سند صرف» لإصدار سند دفع.
    </div>
    @endif
</div>
@endsection
