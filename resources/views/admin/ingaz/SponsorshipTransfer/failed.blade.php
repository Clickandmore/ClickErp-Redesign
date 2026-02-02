@extends('admin.layout.indexs.index')

@section("style")
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="{{asset("admin")}}/custom/status-badges.css">

    <style>
        /* إصلاح مشكلة القائمة الجانبية المعكوسة */
        body {
            direction: rtl !important;
        }

        .sidebar {
            right: 0 !important;
            left: auto !important;
        }

        .main-content {
            margin-right: 250px !important;
            margin-left: 0 !important;
        }

        .container-xxl {
            direction: rtl !important;
        }

        /* إصلاح أي عناصر أخرى قد تكون معكوسة */
        .card, .table, .btn {
            direction: rtl !important;
        }
    </style>
@endsection

@section('title')
    {{ helperTrans('ingaz.trial_failed') }}
@endsection

@section('content')
<div class="container-xxl">
    <div class="row align-items-center">
        <div class="border-0 mb-4">
            <div class="card-header py-3 no-bg bg-transparent d-flex align-items-center px-0 justify-content-between border-bottom flex-wrap">
                <h3 class="fw-bold mb-0">{{ helperTrans('ingaz.trial_failed') }}</h3>
                <div class="col-auto d-flex w-sm-100">
                    <a href="{{ route('SponsorshipTransfer.index') }}" class="btn btn-dark btn-set-task w-sm-100">
                        <i class="icofont-arrow-left me-2 fs-6"></i>{{ helperTrans('ingaz.back') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row clearfix g-3">
        <div class="col-sm-12">
            <div class="card mb-3">
                <div class="card-body">
                    @if($transfers->count() > 0)

                            <table class="table table-hover align-middle mb-0" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th>رقم العقد</th>
                                        <th>الكفيل القديم</th>
                                        <th>الكفيل الجديد</th>
                                        <th>بيانات العاملة</th>
                                        <th>تاريخ الفشل</th>
                                        <th>سبب الفشل</th>
                                        <th>ألغي بواسطة</th>
                                        <th>الحالة</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($transfers as $transfer)
                                        <tr>
                                            <td><strong>عقد رقم: {{ $transfer->contract->id }}</strong></td>
                                            <td>
                                                <div>
                                                    <strong>{{ $transfer->oldSponsor->name ?? 'غير محدد' }}</strong><br>
                                                    <small class="text-muted">{{ $transfer->oldSponsor->national_num ?? '' }}</small>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong>{{ $transfer->newSponsor->name ?? 'غير محدد' }}</strong><br>
                                                    <small class="text-muted">{{ $transfer->newSponsor->national_num ?? '' }}</small>
                                                </div>
                                            </td>
                                            <td>
                                                @if($transfer->contract->maid)
                                                    <div>
                                                        <strong>{{ $transfer->contract->maid->name ?? 'غير محدد' }}</strong><br>
                                                        <small class="text-muted">{{ $transfer->contract->maid->passport ?? '' }}</small><br>
                                                        <small class="text-muted">{{ $transfer->contract->maid->nationality->name ?? '' }}</small>
                                                    </div>
                                                @else
                                                    <span class="text-muted">لا توجد بيانات</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($transfer->failed_at)
                                                    <div>
                                                        <strong>{{ $transfer->failed_at->format('Y-m-d') }}</strong><br>
                                                        <small class="text-muted">{{ $transfer->failed_at->format('H:i') }}</small>
                                                    </div>
                                                @else
                                                    <span class="text-muted">غير محدد</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($transfer->failure_reason)
                                                    <div class="text-wrap" style="max-width: 200px;">
                                                        {{ $transfer->failure_reason }}
                                                    </div>
                                                @else
                                                    <span class="text-muted">لم يتم تحديد السبب</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($transfer->failedBy)
                                                    <div>
                                                        <strong>{{ $transfer->failedBy->name ?? 'غير محدد' }}</strong><br>
                                                        <small class="text-muted">{{ $transfer->failed_at ? $transfer->failed_at->diffForHumans() : '' }}</small>
                                                    </div>
                                                @else
                                                    <span class="text-muted">غير محدد</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-danger">{{ $transfer->status_text }}</span>
                                            </td>
                                            <td>
                                                <div class="dropdown actions-dropdown">
                                                    <button class="btn btn-sm btn-primary dropdown-toggle actions-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="الإجراءات المتاحة">
                                                        <i class="fas fa-cogs me-1"></i> الإجراءات
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end shadow-lg">
                                                        <li>
                                                            <a class="dropdown-item" href="{{ route('SponsorshipTransfer.show', $transfer->id) }}">
                                                                <i class="fas fa-eye"></i> عرض التفاصيل
                                                            </a>
                                                        </li>
                                                        {{-- <li>
                                                            <a class="dropdown-item" href="{{ route('RecruitmentContract.show', $transfer->contract->id) }}">
                                                                <i class="fas fa-file-contract"></i> عرض العقد
                                                            </a>
                                                        </li> --}}
                                                        <li>
                                                            <a class="dropdown-item" href="{{ route('contracts.details', $transfer->contract->id) }}">
                                                                <i class="fas fa-file-contract"></i> عرض العقد
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" href="{{ route('ShelterContract.availableForTransfer') }}">
                                                                <i class="fas fa-home"></i> عرض في الإيواء
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                        <div class="mt-3">
                            {{ $transfers->links('pagination::bootstrap-4') }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-times-circle fa-3x text-danger mb-3"></i>
                            <h5 class="text-muted">لا توجد طلبات فاشلة</h5>
                            <p class="text-muted">عندما يتم إلغاء طلبات نقل الكفالة، ستظهر هنا</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Failure Statistics -->
@if($transfers->count() > 0)
<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <i class="fas fa-times-circle fa-2x text-danger mb-2"></i>
                <h4 class="mb-0">{{ $transfers->total() }}</h4>
                <small class="text-muted">إجمالي الطلبات الفاشلة</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <i class="fas fa-calendar fa-2x text-info mb-2"></i>
                <h4 class="mb-0">{{ $transfers->where('failed_at', '>=', now()->startOfMonth())->count() }}</h4>
                <small class="text-muted">هذا الشهر</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <i class="fas fa-home fa-2x text-warning mb-2"></i>
                <h4 class="mb-0">{{ $transfers->unique('contract_id')->count() }}</h4>
                <small class="text-muted">عقود عادت للإيواء</small>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Common Failure Reasons -->
@if($transfers->count() > 0)
<div class="row g-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">أسباب الفشل الشائعة</h5>
            </div>
            <div class="card-body">
                @php
                    $reasons = $transfers->whereNotNull('failure_reason')
                                       ->groupBy('failure_reason')
                                       ->map(function($group) {
                                           return $group->count();
                                       })
                                       ->sortDesc()
                                       ->take(5);
                @endphp

                @if($reasons->count() > 0)
                    <div class="list-group list-group-flush">
                        @foreach($reasons as $reason => $count)
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <span>{{ $reason }}</span>
                                <span class="badge bg-danger rounded-pill">{{ $count }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted mb-0">لا توجد أسباب محددة للفشل</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@section("script")
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{asset("admin")}}/custom/admin-tables.js"></script>
@endsection
