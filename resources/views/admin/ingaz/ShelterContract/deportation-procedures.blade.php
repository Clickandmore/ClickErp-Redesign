@extends('admin.layout.indexs.index')

@php
use Illuminate\Support\Facades\Storage;
@endphp

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
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .attachments-section h6 {
            color: #495057;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .attachments-section .list-group-item {
            border: 1px solid #dee2e6;
            margin-bottom: 5px;
            transition: all 0.3s ease;
        }

        .attachments-section .list-group-item:hover {
            background-color: #f1f3f4;
            transform: translateX(-2px);
        }

        .attachments-section .list-group-item i {
            margin-left: 5px;
        }

        /* ملاحظات */
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
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">إجراءات الترحيل - {{ $contract->maid->name ?? 'غير محدد' }}</h4>
            </div>
        </div>
    </div>
    <div class="row g-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">إجراءات الترحيل</h4>
                </div>
                <div class="card-body">
                    <div class="page-header mb-4">
                        <h4><i class="fas fa-clipboard-list"></i> إجراءات ترحيل العاملة</h4>

                    </div>
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

                    <!-- معلومات حالة الإجراءات -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="info-card">
                                <h5><i class="fas fa-info-circle"></i> حالة الإجراءات</h5>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="info-item">
                                            <span class="info-label">تاريخ البدء</span>
                                            <span class="info-value">{{ $procedure->started_at->format('Y-m-d H:i') }}</span>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label">بدأها</span>
                                            <span class="info-value">{{ $procedure->createdBy->name ?? 'غير محدد' }}</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        @if($procedure->procedures_confirmed)
                                        <div class="info-item">
                                            <span class="info-label">تم التأكيد</span>
                                            <span class="info-value">
                                                <span class="badge bg-info">
                                                    <i class="fas fa-check-double"></i> {{ $procedure->confirmed_at->format('Y-m-d H:i') }}
                                                </span>
                                            </span>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label">أكدها</span>
                                            <span class="info-value">{{ $procedure->confirmedBy->name ?? 'غير محدد' }}</span>
                                        </div>
                                        @endif
                                    </div>
                                    <div class="col-md-4">
                                        @if($procedure->trip_scheduled)
                                        <div class="info-item">
                                            <span class="info-label">الرحلة</span>
                                            <span class="info-value">
                                                <span class="badge bg-warning">
                                                    <i class="fas fa-plane"></i> تم جدولة رحلة
                                                </span>
                                            </span>
                                        </div>
                                        @endif
                                        @if($procedure->completed_at)
                                        <div class="info-item">
                                            <span class="info-label">تاريخ الإكمال</span>
                                            <span class="info-value">{{ $procedure->completed_at->format('Y-m-d H:i') }}</span>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label">أكملها</span>
                                            <span class="info-value">{{ $procedure->completedBy->name ?? 'غير محدد' }}</span>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- نسبة الإكمال -->
                    <div class="progress-container">
                        <h5 class="mb-3">نسبة إكمال الإجراءات</h5>
                        @php
                            $percentage = $procedure->completion_percentage ?? 0;
                            $progressClass = 'bg-danger';
                            if ($percentage >= 50 && $percentage < 100) {
                                $progressClass = 'bg-warning';
                            } elseif ($percentage == 100) {
                                $progressClass = 'bg-success';
                            }
                        @endphp
                        <div class="progress">
                            <div class="progress-bar {{ $progressClass }}" role="progressbar" style="width: {{ $percentage }}%" aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100">
                                {{ $percentage }}%
                            </div>
                        </div>
                        <small class="text-muted mt-2 d-block">
                            @if($percentage == 0)
                                لم يتم الإجابة على أي أسئلة
                            @elseif($percentage < 100)
                                تم الإجابة على {{ round($percentage / 20) }} من 5 أسئلة
                            @else
                                تم الإجابة على جميع الأسئلة
                            @endif
                        </small>
                    </div>

                    <!-- حالة الإجراءات -->
                    <div class="mb-4">
                        <span class="procedure-status status-{{ $procedure->status ?? 'pending' }}">
                            <i class="fas fa-info-circle"></i> {{ $procedure->status_text ?? 'في الانتظار' }}
                        </span>
                    </div>

                    @if($procedure->procedures_confirmed && $procedure->status !== 'completed')
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> تم تأكيد الإجراءات ولا يمكن التعديل عليها. يمكنك الآن جدولة رحلة للعاملة.
                    </div>
                    @endif

                    <form id="deportationForm" action="{{ route('ShelterContract.deportation.update', $procedure->id) }}" method="POST" enctype="multipart/form-data">
                            @csrf

                            <div class="procedure-card">
                                <h5 class="mb-4"><i class="fas fa-clipboard-check"></i> قائمة الإجراءات المطلوبة</h5>

                                <!-- استلام الراتب -->
                                <div class="procedure-item">
                                    <div class="row align-items-center">
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">
                                                <i class="fas fa-money-bill-wave text-success"></i> هل تم استلام الراتب؟
                                            </label>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="btn-group w-100" role="group">
                                                <input type="radio" class="btn-check" name="salary_received" id="salary_yes" value="1"
                                                    {{ $procedure->salary_received == 1 ? 'checked' : '' }}
                                                    {{ $procedure->status === 'completed' || $procedure->procedures_confirmed ? 'disabled' : '' }}>
                                                <label class="btn btn-outline-success" for="salary_yes">نعم</label>

                                                <input type="radio" class="btn-check" name="salary_received" id="salary_no" value="0"
                                                    {{ $procedure->salary_received == 0 && $procedure->salary_received !== null ? 'checked' : '' }}
                                                    {{ $procedure->status === 'completed' || $procedure->procedures_confirmed ? 'disabled' : '' }}>
                                                <label class="btn btn-outline-danger" for="salary_no">لا</label>
                                            </div>
                                        </div>
                                        <div class="col-md-3" id="salary_date_container" style="{{ $procedure->salary_received == 1 ? '' : 'display:none;' }}">
                                            <input type="date" class="form-control" name="salary_received_date" id="salary_received_date"
                                                value="{{ $procedure->salary_received_date ? \Carbon\Carbon::parse($procedure->salary_received_date)->format('Y-m-d') : date('Y-m-d') }}"
                                                {{ $procedure->status === 'completed' || $procedure->procedures_confirmed ? 'disabled' : '' }}>
                                        </div>
                                    </div>
                                </div>

                                <!-- وثيقة السفر -->
                                <div class="procedure-item">
                                    <div class="row align-items-center">
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">
                                                <i class="fas fa-passport text-primary"></i> هل معها وثيقة السفر/جواز السفر؟
                                            </label>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="btn-group w-100" role="group">
                                                <input type="radio" class="btn-check" name="has_passport" id="passport_yes" value="1"
                                                    {{ $procedure->has_passport == 1 ? 'checked' : '' }}
                                                    {{ $procedure->status === 'completed' || $procedure->procedures_confirmed ? 'disabled' : '' }}>
                                                <label class="btn btn-outline-success" for="passport_yes">نعم</label>

                                                <input type="radio" class="btn-check" name="has_passport" id="passport_no" value="0"
                                                    {{ $procedure->has_passport == 0 && $procedure->has_passport !== null ? 'checked' : '' }}
                                                    {{ $procedure->status === 'completed' || $procedure->procedures_confirmed ? 'disabled' : '' }}>
                                                <label class="btn btn-outline-danger" for="passport_no">لا</label>
                                            </div>
                                        </div>
                                        <div class="col-md-3" id="passport_date_container" style="{{ $procedure->has_passport == 1 ? '' : 'display:none;' }}">
                                            <input type="date" class="form-control" name="passport_date"
                                                value="{{ date('Y-m-d') }}">
                                        </div>
                                    </div>
                                </div>

                                <!-- خطاب الترحيل -->
                                <div class="procedure-item">
                                    <div class="row align-items-center">
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">
                                                <i class="fas fa-file-alt text-warning"></i> هل معها خطاب الترحيل؟
                                            </label>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="btn-group w-100" role="group">
                                                <input type="radio" class="btn-check" name="has_deportation_letter" id="letter_yes" value="1"
                                                    {{ $procedure->has_deportation_letter == 1 ? 'checked' : '' }}
                                                    {{ $procedure->status === 'completed' || $procedure->procedures_confirmed ? 'disabled' : '' }}>
                                                <label class="btn btn-outline-success" for="letter_yes">نعم</label>

                                                <input type="radio" class="btn-check" name="has_deportation_letter" id="letter_no" value="0"
                                                    {{ $procedure->has_deportation_letter == 0 && $procedure->has_deportation_letter !== null ? 'checked' : '' }}
                                                    {{ $procedure->status === 'completed' || $procedure->procedures_confirmed ? 'disabled' : '' }}>
                                                <label class="btn btn-outline-danger" for="letter_no">لا</label>
                                            </div>
                                        </div>
                                        <div class="col-md-3" id="letter_date_container" style="{{ $procedure->has_deportation_letter == 1 ? '' : 'display:none;' }}">
                                            <input type="date" class="form-control" name="deportation_letter_date"
                                                value="{{ date('Y-m-d') }}">
                                        </div>
                                    </div>
                                </div>

                                <!-- جلسة التسوية -->
                                <div class="procedure-item">
                                    <div class="row align-items-center">
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">
                                                <i class="fas fa-handshake text-info"></i> هل تمت جلسة التسوية؟
                                            </label>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="btn-group w-100" role="group">
                                                <input type="radio" class="btn-check" name="has_settlement_session" id="settlement_yes" value="1"
                                                    {{ $procedure->has_settlement_session == 1 ? 'checked' : '' }}
                                                    {{ $procedure->status === 'completed' || $procedure->procedures_confirmed ? 'disabled' : '' }}>
                                                <label class="btn btn-outline-success" for="settlement_yes">نعم</label>

                                                <input type="radio" class="btn-check" name="has_settlement_session" id="settlement_no" value="0"
                                                    {{ $procedure->has_settlement_session == 0 && $procedure->has_settlement_session !== null ? 'checked' : '' }}
                                                    {{ $procedure->status === 'completed' || $procedure->procedures_confirmed ? 'disabled' : '' }}>
                                                <label class="btn btn-outline-danger" for="settlement_no">لا</label>
                                            </div>
                                        </div>
                                        <div class="col-md-3" id="settlement_date_container" style="{{ $procedure->has_settlement_session == 1 ? '' : 'display:none;' }}">
                                            <input type="date" class="form-control" name="settlement_date"
                                                value="{{ date('Y-m-d') }}">
                                        </div>
                                    </div>
                                </div>

                                <!-- الخروج النهائي -->
                                <div class="procedure-item">
                                    <div class="row align-items-center">
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">
                                                <i class="fas fa-plane-departure text-danger"></i> هل تم الخروج النهائي؟
                                            </label>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="btn-group w-100" role="group">
                                                <input type="radio" class="btn-check" name="has_final_exit" id="exit_yes" value="1"
                                                    {{ $procedure->has_final_exit == 1 ? 'checked' : '' }}
                                                    {{ $procedure->status === 'completed' || $procedure->procedures_confirmed ? 'disabled' : '' }}>
                                                <label class="btn btn-outline-success" for="exit_yes">نعم</label>

                                                <input type="radio" class="btn-check" name="has_final_exit" id="exit_no" value="0"
                                                    {{ $procedure->has_final_exit == 0 && $procedure->has_final_exit !== null ? 'checked' : '' }}
                                                    {{ $procedure->status === 'completed' || $procedure->procedures_confirmed ? 'disabled' : '' }}>
                                                <label class="btn btn-outline-danger" for="exit_no">لا</label>
                                            </div>
                                        </div>
                                        <div class="col-md-3" id="exit_date_container" style="{{ $procedure->has_final_exit == 1 ? '' : 'display:none;' }}">
                                            <input type="date" class="form-control" name="final_exit_date"
                                                value="{{ date('Y-m-d') }}">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- المرفقات -->
                            <div class="attachments-section">
                                <h6><i class="fa fa-paperclip"></i> المرفقات</h6>

                                @if(!$procedure->procedures_confirmed && $procedure->status !== 'completed')
                                <input type="file" class="form-control" name="attachments[]" multiple accept=".pdf,.jpg,.jpeg,.png">
                                <small class="text-muted">يمكنك رفع ملفات PDF أو صور</small>
                                @endif

                                @if($procedure->attachments && count($procedure->attachments) > 0)
                                    <div class="mt-3">
                                        <strong>المرفقات الحالية:</strong>
                                        <div class="list-group mt-2">
                                            @foreach($procedure->attachments as $index => $attachment)
                                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <i class="fa fa-file-alt text-primary me-2"></i>
                                                        @if(is_array($attachment))
                                                            {{ $attachment['name'] ?? basename($attachment['path'] ?? 'ملف ' . ($index + 1)) }}
                                                        @else
                                                            {{ basename($attachment) }}
                                                        @endif
                                                    </div>
                                                    <div>
                                                        @php
                                                            $filePath = is_array($attachment) ? ($attachment['path'] ?? $attachment) : $attachment;
                                                            $fullPath = Storage::exists($filePath) ? Storage::url($filePath) : asset($filePath);
                                                        @endphp
                                                        <a href="{{ $fullPath }}" target="_blank" class="btn btn-sm btn-primary">
                                                            <i class="fa fa-download"></i> عرض
                                                        </a>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @else
                                    @if($procedure->procedures_confirmed || $procedure->status === 'completed')
                                    <div class="alert alert-info mt-2">
                                        <i class="fa fa-info-circle"></i> لا توجد مرفقات
                                    </div>
                                    @endif
                                @endif
                            </div>

                            <!-- الملاحظات -->
                            <div class="notes-section">
                                <h6><i class="fas fa-sticky-note"></i> ملاحظات إضافية</h6>
                                <textarea name="notes" class="form-control" placeholder="أدخل أي ملاحظات إضافية..."
                                    {{ $procedure->status === 'completed' || $procedure->procedures_confirmed ? 'disabled' : '' }}>{{ $procedure->notes }}</textarea>
                            </div>

                            <!-- أزرار الإجراءات -->
                            <div class="action-buttons">
                                @if($procedure->status !== 'completed' && !$procedure->procedures_confirmed)
                                <button type="button" class="btn btn-save" onclick="confirmUpdate()">
                                    <i class="fa fa-save"></i> حفظ التحديثات
                                </button>
                                @endif

                                @if($procedure->areAllQuestionsAnswered() && !$procedure->procedures_confirmed && $procedure->status !== 'completed')
                                <button type="button" class="btn btn-info" onclick="confirmProcedures()">
                                    <i class="fa fa-check-double"></i> تأكيد اكتمال الإجراءات
                                </button>
                                @endif

                                @if($procedure->procedures_confirmed && !$procedure->trip_scheduled && $procedure->status !== 'completed')
                                <button type="button" class="btn btn-warning" onclick="scheduleTrip()">
                                    <i class="fa fa-plane"></i> جدولة رحلة
                                </button>
                                @endif

                                @if($procedure->procedures_confirmed && $procedure->trip_scheduled && $procedure->status !== 'completed')
                                <button type="button" class="btn btn-success" onclick="confirmCompleteDeportation()">
                                    <i class="fa fa-check-circle"></i> إكمال الترحيل
                                </button>
                                @endif

                                <a href="{{ route('ShelterContract.show', $contract->id) }}" class="btn btn-cancel">
                                    <i class="fa fa-times"></i> {{ $procedure->status === 'completed' ? 'رجوع' : 'إلغاء' }}
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

</div>
@endsection

@section('js')
<!-- تحميل SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- JavaScript للدوال المطلوبة -->
<script>
// تعريف الدوال بشكل global قبل استخدامها
window.confirmUpdate = function() {
    console.log('confirmUpdate called - inline');

    if (typeof $ === 'undefined') {
        alert('jQuery غير محمل!');
        return;
    }

    if (typeof Swal === 'undefined') {
        if (confirm('هل أنت متأكد من حفظ التحديثات؟')) {
            $('#deportationForm').submit();
        }
        return;
    }

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
            // استخدام AJAX بدلاً من submit العادي
            const form = document.getElementById('deportationForm');
            const formData = new FormData(form);

            $.ajax({
                url: form.action,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    console.log('Success response:', response);
                    Swal.fire({
                        title: 'نجح!',
                        text: response.message || 'تم حفظ التحديثات بنجاح',
                        icon: 'success',
                        confirmButtonText: 'حسناً'
                    }).then(() => {
                        if (response.redirect) {
                            window.location.href = response.redirect;
                        } else {
                            location.reload();
                        }
                    });
                },
                error: function(xhr) {
                    console.log('Error response:', xhr);
                    Swal.fire('خطأ!', xhr.responseJSON?.message || 'حدث خطأ أثناء الحفظ', 'error');
                }
            });
        }
    });
}

window.confirmProcedures = function() {
    console.log('confirmProcedures called - inline');
    if (typeof $ === 'undefined' || typeof Swal === 'undefined') {
        alert('المكتبات المطلوبة غير محملة!');
        return;
    }

    Swal.fire({
        title: 'تأكيد اكتمال الإجراءات',
        text: 'هل أنت متأكد من أن جميع الإجراءات قد تم مراجعتها واكتملت؟',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#17a2b8',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'نعم، تأكيد الإجراءات',
        cancelButtonText: 'إلغاء'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '{{ route("ShelterContract.deportation.update", $procedure->id) }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    confirm_procedures: true
                },
                success: function(response) {
                    Swal.fire({
                        title: 'نجح!',
                        text: response.message || 'تم تأكيد اكتمال الإجراءات بنجاح',
                        icon: 'success',
                        confirmButtonText: 'حسناً'
                    }).then(() => {
                        location.reload();
                    });
                },
                error: function(xhr) {
                    Swal.fire('خطأ!', xhr.responseJSON?.message || 'حدث خطأ أثناء التأكيد', 'error');
                }
            });
        }
    });
}

window.scheduleTrip = function() {
    console.log('scheduleTrip called - inline');
    if (confirm('هل تريد جدولة رحلة للعاملة؟')) {
        window.location.href = '{{ route("DeportationTravel.create") }}?contract_id={{ $contract->id }}';
    }
}

window.confirmCompleteDeportation = function() {
    console.log('confirmCompleteDeportation called - inline');
    if (typeof $ === 'undefined' || typeof Swal === 'undefined') {
        alert('المكتبات المطلوبة غير محملة!');
        return;
    }

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

<script>
$(document).ready(function() {
    console.log('Document ready - jQuery version:', jQuery.fn.jquery);
    console.log('Form found:', $('#deportationForm').length);

    // التحقق من حالة الإجراءات عند تحميل الصفحة
    var proceduresConfirmed = {{ $procedure->procedures_confirmed ? 'true' : 'false' }};
    var status = '{{ $procedure->status }}';

    console.log('Procedures confirmed:', proceduresConfirmed);
    console.log('Status:', status);

    // إذا كانت الإجراءات مؤكدة أو الحالة مكتملة، تعطيل زر الحفظ
    if (proceduresConfirmed || status === 'completed') {
        $('.btn-save').prop('disabled', true).hide();
        console.log('Save button disabled and hidden');
    }

    // إظهار/إخفاء تاريخ الراتب
    $('input[name="salary_received"]').on('change', function() {
        console.log('Salary changed to:', $(this).val());
        if ($(this).val() === '1') {
            $('#salary_date_container').show();
        } else {
            $('#salary_date_container').hide();
        }
    });

    // إظهار/إخفاء تاريخ الجواز
    $('input[name="has_passport"]').on('change', function() {
        console.log('Passport changed to:', $(this).val());
        if ($(this).val() === '1') {
            $('#passport_date_container').show();
        } else {
            $('#passport_date_container').hide();
        }
    });

    // إظهار/إخفاء تاريخ خطاب الترحيل
    $('input[name="has_deportation_letter"]').on('change', function() {
        console.log('Letter changed to:', $(this).val());
        if ($(this).val() === '1') {
            $('#letter_date_container').show();
        } else {
            $('#letter_date_container').hide();
        }
    });

    // إظهار/إخفاء تاريخ جلسة التسوية
    $('input[name="has_settlement_session"]').on('change', function() {
        console.log('Settlement changed to:', $(this).val());
        if ($(this).val() === '1') {
            $('#settlement_date_container').show();
        } else {
            $('#settlement_date_container').hide();
        }
    });

    // إظهار/إخفاء تاريخ الخروج النهائي
    $('input[name="has_final_exit"]').on('change', function() {
        console.log('Exit changed to:', $(this).val());
        if ($(this).val() === '1') {
            $('#exit_date_container').show();
        } else {
            $('#exit_date_container').hide();
        }
    });

    // معالجة استجابة الحفظ
    $('#deportationForm').on('submit', function(e) {
        console.log('Form submit intercepted');
        e.preventDefault();

        const formData = new FormData(this);

        // طباعة البيانات المرسلة
        for (let pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log('Success response:', response);
                Swal.fire({
                    title: 'نجح!',
                    text: response.message || 'تم حفظ التحديثات بنجاح',
                    icon: 'success',
                    confirmButtonText: 'حسناً'
                }).then(() => {
                    if (response.redirect) {
                        window.location.href = response.redirect;
                    } else {
                        location.reload();
                    }
                });
            },
            error: function(xhr) {
                console.log('Error response:', xhr);
                Swal.fire('خطأ!', xhr.responseJSON?.message || 'حدث خطأ أثناء الحفظ', 'error');
            }
        });
    });
});
</script>
@endsection
