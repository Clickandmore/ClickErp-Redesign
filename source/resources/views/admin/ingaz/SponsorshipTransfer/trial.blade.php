@extends('admin.layout.indexs.index')

@section("style")
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
@endsection

@section('title')
    {{ helperTrans('ingaz.trial_period') }}
@endsection

@section('content')
<div class="container-xxl">
    <div class="row align-items-center">
        <div class="border-0 mb-4">
            <div class="card-header py-3 no-bg bg-transparent d-flex align-items-center px-0 justify-content-between border-bottom flex-wrap">
                <h3 class="fw-bold mb-0">{{ helperTrans('ingaz.trial_period') }}</h3>
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
                                        <th>فترة التجربة</th>
                                        <th>نهاية فترة التجربة</th>
                                        <th>المبلغ المتبقي</th>
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
                                                    <small class="text-muted">{{ $transfer->oldSponsor?->documentNumber() ?? '' }}</small>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong>{{ $transfer->newSponsor->name ?? 'غير محدد' }}</strong><br>
                                                    <small class="text-muted">{{ $transfer->newSponsor?->documentNumber() ?? '' }}</small>
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
                                            <td>{{ $transfer->trial_days }} يوم</td>
                                            <td>
                                                @php
                                                    $trialEndDate = $transfer->trial_started_at ? $transfer->trial_started_at->addDays($transfer->trial_days) : null;
                                                    $daysRemaining = $trialEndDate ? now()->diffInDays($trialEndDate, false) : 0;
                                                @endphp
                                                @if($trialEndDate)
                                                    <div>
                                                        <strong>{{ $trialEndDate->format('Y-m-d') }}</strong><br>
                                                        @if($daysRemaining > 0)
                                                            <small class="badge bg-primary">باقي {{ $daysRemaining }} يوم</small>
                                                        @elseif($daysRemaining == 0)
                                                            <small class="badge bg-warning">تنتهي اليوم</small>
                                                        @else
                                                            <small class="badge bg-danger">انتهت منذ {{ abs($daysRemaining) }} يوم</small>
                                                        @endif
                                                    </div>
                                                @else
                                                    <span class="text-muted">غير محدد</span>
                                                @endif
                                            </td>
                                            <td>{{ number_format($transfer->remaining_amount, 2) }} ريال</td>
                                            <td>
                                                <span class="badge bg-primary">{{ $transfer->status_text }}</span>
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
                                                        <li>
                                                            <a class="dropdown-item confirm-action" href="#"
                                                               data-action="confirm-transfer"
                                                               data-id="{{ $transfer->id }}"
                                                               data-text="تأكيد نقل الكفالة">
                                                                <i class="fas fa-check-circle"></i> تأكيد النقل
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item confirm-action" href="#"
                                                               data-action="cancel-transfer"
                                                               data-id="{{ $transfer->id }}"
                                                               data-text="إلغاء النقل وإعادة العقد إلى الإيواء">
                                                                <i class="fas fa-times-circle"></i> إلغاء النقل
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
                            <i class="fas fa-clock-o fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">لا توجد طلبات في فترة التجربة</h5>
                            <p class="text-muted">عندما يتم الموافقة على طلبات نقل الكفالة، ستظهر هنا في فترة التجربة</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
{{-- <!-- Success Statistics -->
@if($transfers->count() > 0)
<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                <h4 class="mb-0">{{ $transfers->total() }}</h4>
                <small class="text-muted">إجمالي النقل المكتمل</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <i class="fas fa-calendar fa-2x text-info mb-2"></i>
                <h4 class="mb-0">{{ $transfers->where('transferred_at', '>=', now()->startOfMonth())->count() }}</h4>
                <small class="text-muted">هذا الشهر</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <i class="fas fa-money-bill-wave fa-2x text-warning mb-2"></i>
                <h4 class="mb-0">{{ number_format($transfers->sum('contract_value')) }}</h4>
                <small class="text-muted">إجمالي المبالغ</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <i class="fas fa-users fa-2x text-primary mb-2"></i>
                <h4 class="mb-0">{{ $transfers->unique('new_sponsor_id')->count() }}</h4>
                <small class="text-muted">كفلاء جدد</small>
            </div>
        </div>
    </div>
</div>
@endif --}}
@endsection
@section("js")
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{asset("admin")}}/custom/admin-tables.js"></script>

    <script>
    $(document).ready(function() {
        // Handle confirm actions
        $('.confirm-action').on('click', function(e) {
            e.preventDefault();

            const action = $(this).data('action');
            const id = $(this).data('id');
            const text = $(this).data('text');

            Swal.fire({
                title: 'تأكيد العملية',
                text: text,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'تأكيد',
                cancelButtonText: 'إلغاء'
            }).then((result) => {
                if (result.isConfirmed) {
                    performAction(action, id);
                }
            });
        });

        function performAction(action, id) {
            const url = `/admin/SponsorshipTransfer/${id}/${action}`;

            $.ajax({
                url: url,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire('نجح!', response.message, 'success').then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('خطأ!', response.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('خطأ!', 'حدث خطأ غير متوقع', 'error');
                }
            });
        }
    });
    </script>
@endsection
