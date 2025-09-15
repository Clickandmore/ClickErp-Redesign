@extends('admin.layout.indexs.index')

@section('title')
    إنشاء عقد نقل الخدمات
@endsection

@section('style')
    <link rel="stylesheet" href="{{asset("admin")}}/custom/admin-tables.css">
    <style>
        .info-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #495057;
        }

        .info-value {
            color: #212529;
        }

        .form-section {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .section-title {
            color: #495057;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #007bff;
        }
    </style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">إنشاء عقد نقل الخدمات</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">الرئيسية</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('SponsorshipTransfer.index') }}">طلبات نقل الكفالة</a></li>
                        <li class="breadcrumb-item active">إنشاء عقد نقل الخدمات</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- معلومات طلب النقل -->
    <div class="row">
        <div class="col-md-6">
            <div class="info-card">
                <h5 class="mb-3"><i class="fas fa-info-circle"></i> معلومات طلب النقل</h5>
                <div class="info-item">
                    <span class="info-label">رقم الطلب:</span>
                    <span class="info-value">#{{ $transfer->id }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">رقم العقد الأصلي:</span>
                    <span class="info-value">#{{ $transfer->contract_id }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">تاريخ الطلب:</span>
                    <span class="info-value">{{ $transfer->created_at->format('Y-m-d H:i') }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">الحالة:</span>
                    <span class="info-value">
                        <span class="badge bg-info">{{ $transfer->status_text }}</span>
                    </span>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="info-card">
                <h5 class="mb-3"><i class="fas fa-users"></i> معلومات الأطراف</h5>
                <div class="info-item">
                    <span class="info-label">الكفيل القديم:</span>
                    <span class="info-value">{{ $transfer->oldSponsor->name ?? 'غير محدد' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">الكفيل الجديد:</span>
                    <span class="info-value">{{ $transfer->newSponsor->name ?? 'غير محدد' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">العاملة:</span>
                    <span class="info-value">{{ $transfer->contract->maid->name ?? 'غير محدد' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">الجنسية:</span>
                    <span class="info-value">{{ $transfer->contract->maid->nationality->translations->first()->sub_title ?? 'غير محدد' }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- نموذج إنشاء العقد -->
    <form id="contractForm" action="{{ route('TransferServiceContract.store') }}" method="POST">
        @csrf
        <input type="hidden" name="sponsorship_transfer_id" value="{{ $transfer->id }}">

        <!-- بيانات العقد المالية -->
        <div class="form-section">
            <h5 class="section-title"><i class="fas fa-money-bill-wave"></i> البيانات المالية</h5>
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="contract_value" class="form-label">قيمة العقد (ريال) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="contract_value" name="contract_value"
                               value="{{ old('contract_value', $transfer->contract_value) }}"
                               step="0.01" min="0" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="paid_amount" class="form-label">المبلغ المدفوع (ريال) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="paid_amount" name="paid_amount"
                               value="{{ old('paid_amount', $transfer->paid_amount) }}"
                               step="0.01" min="0" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="remaining_amount" class="form-label">المبلغ المتبقي (ريال)</label>
                        <input type="number" class="form-control" id="remaining_amount" name="remaining_amount"
                               value="{{ old('remaining_amount', $transfer->remaining_amount) }}"
                               step="0.01" min="0" readonly>
                    </div>
                </div>
            </div>
        </div>

        <!-- بيانات فترة التجربة -->
        <div class="form-section">
            <h5 class="section-title"><i class="fas fa-calendar-alt"></i> بيانات فترة التجربة</h5>
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="trial_days" class="form-label">عدد أيام التجربة <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="trial_days" name="trial_days"
                               value="{{ old('trial_days', $transfer->trial_days) }}"
                               min="1" max="365" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="trial_start_date" class="form-label">تاريخ بداية التجربة <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="trial_start_date" name="trial_start_date"
                               value="{{ old('trial_start_date', $transfer->trial_start_date?->format('Y-m-d')) }}"
                               min="{{ date('Y-m-d') }}" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="trial_end_date" class="form-label">تاريخ انتهاء التجربة</label>
                        <input type="date" class="form-control" id="trial_end_date" readonly>
                    </div>
                </div>
            </div>
        </div>

        <!-- بيانات العقد -->
        <div class="form-section">
            <h5 class="section-title"><i class="fas fa-file-contract"></i> بيانات العقد</h5>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="contract_start_date" class="form-label">تاريخ بداية العقد <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="contract_start_date" name="contract_start_date"
                               value="{{ old('contract_start_date', date('Y-m-d')) }}"
                               min="{{ date('Y-m-d') }}" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="contract_end_date" class="form-label">تاريخ انتهاء العقد</label>
                        <input type="date" class="form-control" id="contract_end_date" name="contract_end_date"
                               value="{{ old('contract_end_date') }}">
                    </div>
                </div>
            </div>
        </div>

        <!-- الشروط والملاحظات -->
        <div class="form-section">
            <h5 class="section-title"><i class="fas fa-clipboard-list"></i> الشروط والملاحظات</h5>
            <div class="row">
                <div class="col-12">
                    <div class="mb-3">
                        <label for="special_conditions" class="form-label">الشروط الخاصة</label>
                        <textarea class="form-control" id="special_conditions" name="special_conditions"
                                  rows="4" placeholder="أدخل أي شروط خاصة للعقد...">{{ old('special_conditions') }}</textarea>
                    </div>
                </div>
                <div class="col-12">
                    <div class="mb-3">
                        <label for="notes" class="form-label">ملاحظات إضافية</label>
                        <textarea class="form-control" id="notes" name="notes"
                                  rows="3" placeholder="أدخل أي ملاحظات إضافية...">{{ old('notes', $transfer->notes) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- أزرار التحكم -->
        <div class="row">
            <div class="col-12">
                <div class="text-center">
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="fas fa-save"></i> إنشاء العقد
                    </button>
                    <a href="{{ route('SponsorshipTransfer.index') }}" class="btn btn-secondary btn-lg ms-2">
                        <i class="fas fa-times"></i> إلغاء
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    // حساب المبلغ المتبقي تلقائياً
    function calculateRemainingAmount() {
        const contractValue = parseFloat($('#contract_value').val()) || 0;
        const paidAmount = parseFloat($('#paid_amount').val()) || 0;
        const remaining = contractValue - paidAmount;
        $('#remaining_amount').val(remaining.toFixed(2));
    }

    // حساب تاريخ انتهاء التجربة
    function calculateTrialEndDate() {
        const startDate = $('#trial_start_date').val();
        const trialDays = parseInt($('#trial_days').val()) || 0;

        if (startDate && trialDays > 0) {
            const start = new Date(startDate);
            const end = new Date(start.getTime() + (trialDays * 24 * 60 * 60 * 1000));
            const endDateString = end.toISOString().split('T')[0];
            $('#trial_end_date').val(endDateString);
        }
    }

    // ربط الأحداث
    $('#contract_value, #paid_amount').on('input', calculateRemainingAmount);
    $('#trial_start_date, #trial_days').on('change', calculateTrialEndDate);

    // حساب القيم الأولية
    calculateRemainingAmount();
    calculateTrialEndDate();

    // معالجة إرسال النموذج
    $('#contractForm').on('submit', function(e) {
        e.preventDefault();

        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();

        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> جاري الحفظ...');

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: 'نجح!',
                        text: response.message,
                        icon: 'success',
                        confirmButtonText: 'حسناً'
                    }).then(() => {
                        if (response.redirect) {
                            window.location.href = response.redirect;
                        } else {
                            window.location.href = '{{ route("SponsorshipTransfer.index") }}';
                        }
                    });
                } else {
                    Swal.fire('خطأ!', response.message || 'حدث خطأ أثناء الحفظ', 'error');
                    submitBtn.prop('disabled', false).html(originalText);
                }
            },
            error: function(xhr) {
                let errorMessage = 'حدث خطأ أثناء الحفظ';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    const errors = Object.values(xhr.responseJSON.errors).flat();
                    errorMessage = errors.join('\n');
                }

                Swal.fire('خطأ!', errorMessage, 'error');
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });

    // التحقق من التواريخ المستقبلية
    $('input[type="date"]').on('change', function() {
        const today = new Date().toISOString().split('T')[0];
        const selectedDate = $(this).val();

        if ($(this).attr('id') !== 'contract_end_date' && $(this).attr('id') !== 'trial_end_date' && selectedDate < today) {
            alert('لا يمكن اختيار تاريخ في الماضي');
            $(this).val(today);
        }
    });

    // التحقق من تاريخ انتهاء العقد
    $('#contract_end_date').on('change', function() {
        const startDate = $('#contract_start_date').val();
        const endDate = $(this).val();

        if (startDate && endDate && endDate <= startDate) {
            alert('تاريخ انتهاء العقد يجب أن يكون بعد تاريخ البداية');
            $(this).val('');
        }
    });
});
</script>
@endsection
