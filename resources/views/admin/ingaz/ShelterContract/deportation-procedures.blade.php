@extends('admin.layout.indexs.index')

@section('title')
    إجراءات الترحيل - {{ $contract->maid->name ?? 'غير محدد' }}
@endsection

@section('style')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="{{asset("admin")}}/custom/admin-tables.css">
    <style>
        .procedure-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
        }

        .procedure-item {
            padding: 15px;
            background: white;
            border-radius: 8px;
            margin-bottom: 15px;
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
        }

        .procedure-item:hover {
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .procedure-item label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .procedure-item .form-check-input {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .procedure-status {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-in_progress {
            background: #cfe2ff;
            color: #084298;
        }

        .status-completed {
            background: #d1e7dd;
            color: #0f5132;
        }

        .progress-container {
            background: #e9ecef;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .progress {
            height: 30px;
            border-radius: 15px;
        }

        .progress-bar {
            font-size: 1rem;
            font-weight: 600;
        }

        .info-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .info-card h5 {
            color: #495057;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f1f3f4;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #6c757d;
            font-weight: 500;
        }

        .info-value {
            color: #212529;
            font-weight: 600;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }

        .btn-save {
            background: #28a745;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-save:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        }

        .btn-cancel {
            background: #6c757d;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-cancel:hover {
            background: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
        }

        .attachments-section {
            margin-top: 20px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .attachments-section h6 {
            color: #495057;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .notes-section {
            margin-top: 20px;
        }

        .notes-section textarea {
            width: 100%;
            min-height: 120px;
            padding: 12px;
            border: 1px solid #ced4da;
            border-radius: 8px;
            resize: vertical;
        }

        .date-input {
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            width: 100%;
        }
    </style>
@endsection

@section('content')
<div class="row clearfix g-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">إجراءات الترحيل</h4>
            </div>
            <div class="card-body">
                <!-- معلومات العقد والعاملة -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="info-card">
                            <h5><i class="fas fa-file-contract"></i> معلومات العقد</h5>
                            <div class="info-item">
                                <span class="info-label">رقم العقد</span>
                                <span class="info-value">#{{ $contract->id }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">العميل</span>
                                <span class="info-value">{{ $contract->user->name ?? 'غير محدد' }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">تاريخ الوصول</span>
                                <span class="info-value">{{ $contract->arrival_date ? \Carbon\Carbon::parse($contract->arrival_date)->format('Y-m-d') : 'غير محدد' }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-card">
                            <h5><i class="fas fa-user"></i> معلومات العاملة</h5>
                            <div class="info-item">
                                <span class="info-label">اسم العاملة</span>
                                <span class="info-value">{{ $contract->maid->name ?? 'غير محدد' }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">رقم الجواز</span>
                                <span class="info-value">{{ $contract->maid->passport ?? 'غير محدد' }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">الجنسية</span>
                                <span class="info-value">{{ $contract->maid->nationality->translations->first()->sub_title ?? 'غير محدد' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- نسبة الإكمال -->
                <div class="progress-container">
                    <h5 class="mb-3">نسبة إكمال الإجراءات</h5>
                    <div class="progress">
                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $procedure->completion_percentage ?? 0 }}%">
                            {{ $procedure->completion_percentage ?? 0 }}%
                        </div>
                    </div>
                </div>

                <!-- حالة الإجراءات -->
                <div class="mb-4">
                    <span class="procedure-status status-{{ $procedure->status ?? 'pending' }}">
                        <i class="fas fa-info-circle"></i> {{ $procedure->status_text ?? 'في الانتظار' }}
                    </span>
                </div>

                <!-- نموذج الإجراءات -->
                <form id="deportationForm" action="{{ route('ShelterContract.deportation.update', $procedure->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="procedure-card">
                        <h5 class="mb-4"><i class="fas fa-clipboard-check"></i> قائمة الإجراءات المطلوبة</h5>

                        <!-- استلام الراتب -->
                        <div class="procedure-item">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="salary_received" id="salary_received"
                                    {{ $procedure->salary_received ? 'checked' : '' }}>
                                <label class="form-check-label" for="salary_received">
                                    <i class="fas fa-money-bill-wave"></i> هل تم استلام الراتب؟
                                </label>
                            </div>
                            <div class="mt-2" id="salary_date_container" style="{{ $procedure->salary_received ? '' : 'display:none;' }}">
                                <label for="salary_received_date">تاريخ استلام الراتب</label>
                                <input type="date" class="date-input" name="salary_received_date" id="salary_received_date"
                                    value="{{ $procedure->salary_received_date ? \Carbon\Carbon::parse($procedure->salary_received_date)->format('Y-m-d') : '' }}">
                            </div>
                        </div>

                        <!-- وثيقة السفر -->
                        <div class="procedure-item">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="has_passport" id="has_passport"
                                    {{ $procedure->has_passport ? 'checked' : '' }}>
                                <label class="form-check-label" for="has_passport">
                                    <i class="fas fa-passport"></i> هل معها وثيقة السفر/جواز السفر؟
                                </label>
                            </div>
                        </div>

                        <!-- خطاب الترحيل -->
                        <div class="procedure-item">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="has_deportation_letter" id="has_deportation_letter"
                                    {{ $procedure->has_deportation_letter ? 'checked' : '' }}>
                                <label class="form-check-label" for="has_deportation_letter">
                                    <i class="fas fa-file-alt"></i> هل معها خطاب الترحيل؟
                                </label>
                            </div>
                        </div>

                        <!-- جلسة التسوية -->
                        <div class="procedure-item">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="has_settlement_session" id="has_settlement_session"
                                    {{ $procedure->has_settlement_session ? 'checked' : '' }}>
                                <label class="form-check-label" for="has_settlement_session">
                                    <i class="fas fa-handshake"></i> هل تمت جلسة التسوية؟
                                </label>
                            </div>
                        </div>

                        <!-- الخروج النهائي -->
                        <div class="procedure-item">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="has_final_exit" id="has_final_exit"
                                    {{ $procedure->has_final_exit ? 'checked' : '' }}>
                                <label class="form-check-label" for="has_final_exit">
                                    <i class="fas fa-plane-departure"></i> هل تم الخروج النهائي؟
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- المرفقات -->
                    <div class="attachments-section">
                        <h6><i class="fas fa-paperclip"></i> المرفقات</h6>
                        <input type="file" class="form-control" name="attachments[]" multiple accept=".pdf,.jpg,.jpeg,.png">
                        <small class="text-muted">يمكنك رفع ملفات PDF أو صور</small>

                        @if($procedure->attachments && count($procedure->attachments) > 0)
                            <div class="mt-3">
                                <strong>المرفقات الحالية:</strong>
                                <ul>
                                    @foreach($procedure->attachments as $attachment)
                                        <li>{{ basename($attachment) }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>

                    <!-- الملاحظات -->
                    <div class="notes-section">
                        <h6><i class="fas fa-sticky-note"></i> ملاحظات إضافية</h6>
                        <textarea name="notes" class="form-control" placeholder="أدخل أي ملاحظات إضافية...">{{ $procedure->notes }}</textarea>
                    </div>

                    <!-- أزرار الإجراءات -->
                    <div class="action-buttons">
                        <button type="button" class="btn btn-save" onclick="confirmUpdate()">
                            <i class="fas fa-save"></i> حفظ التحديثات
                        </button>

                        @if($procedure->isAllProceduresCompleted())
                        <button type="button" class="btn btn-success" onclick="confirmCompleteDeportation()">
                            <i class="fas fa-check-circle"></i> إكمال الترحيل
                        </button>
                        @endif

                        <a href="{{ route('ShelterContract.show', $contract->id) }}" class="btn btn-cancel">
                            <i class="fas fa-times"></i> إلغاء
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// إظهار/إخفاء تاريخ الراتب
document.getElementById('salary_received').addEventListener('change', function() {
    const dateContainer = document.getElementById('salary_date_container');
    if (this.checked) {
        dateContainer.style.display = 'block';
    } else {
        dateContainer.style.display = 'none';
        document.getElementById('salary_received_date').value = '';
    }
});

// تأكيد التحديث
function confirmUpdate() {
    Swal.fire({
        title: 'تأكيد التحديث',
        text: 'هل أنت متأكد من حفظ التحديثات؟',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'نعم، احفظ',
        cancelButtonText: 'إلغاء'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('deportationForm').submit();
        }
    });
}

// معالجة استجابة الحفظ
$('#deportationForm').on('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    $.ajax({
        url: $(this).attr('action'),
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            Swal.fire({
                title: 'نجح!',
                text: response.message || 'تم حفظ التحديثات بنجاح',
                icon: 'success',
                confirmButtonText: 'حسناً'
            }).then(() => {
                if (response.redirect) {
                    window.location.href = response.redirect;
                }
            });
        },
        error: function(xhr) {
            Swal.fire('خطأ!', xhr.responseJSON?.message || 'حدث خطأ أثناء الحفظ', 'error');
        }
    });
});

// تأكيد إكمال الترحيل
function confirmCompleteDeportation() {
    Swal.fire({
        title: 'تأكيد إكمال الترحيل',
        text: 'هل أنت متأكد من إكمال جميع إجراءات الترحيل؟ سيتم نقل العقد إلى قائمة "تم الترحيل"',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'نعم، إكمال الترحيل',
        cancelButtonText: 'إلغاء'
    }).then((result) => {
        if (result.isConfirmed) {
            // إرسال طلب لإكمال الترحيل
            $.ajax({
                url: '{{ route("ShelterContract.deportation.update", $procedure->id) }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    complete_deportation: true
                },
                success: function(response) {
                    Swal.fire({
                        title: 'نجح!',
                        text: response.message || 'تم إكمال إجراءات الترحيل بنجاح',
                        icon: 'success',
                        confirmButtonText: 'حسناً'
                    }).then(() => {
                        if (response.redirect) {
                            window.location.href = response.redirect;
                        } else {
                            window.location.href = '{{ route("ShelterContract.deported") }}';
                        }
                    });
                },
                error: function(xhr) {
                    Swal.fire('خطأ!', xhr.responseJSON?.message || 'حدث خطأ أثناء إكمال الترحيل', 'error');
                }
            });
        }
    });
}
</script>
@endsection
