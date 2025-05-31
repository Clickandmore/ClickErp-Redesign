@extends('admin.layout.indexs.index')

@section('title', __('ingaz.select_maid'))

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ __('ingaz.select_maid') }}</h3>
                </div>
                <div class="card-body">
                    <!-- Filters Section -->
                    <form action="{{ route('admin.recruitment.select.maid', $client->id) }}" method="GET" class="mb-4">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="nationality_id">الجنسية</label>
                                    <select name="nationality_id" id="nationality_id" class="form-control select2">
                                        <option value="">الكل</option>
                                        @foreach($nationalities as $nationality)
                                            <option value="{{ $nationality->id }}" {{ request('nationality_id') == $nationality->id ? 'selected' : '' }}>
                                                {{ $nationality->sub_title }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="religion_id">الدين</label>
                                    <select name="religion_id" id="religion_id" class="form-control select2">
                                        <option value="">الكل</option>
                                        @foreach($religions as $religion)
                                            <option value="{{ $religion->id }}" {{ request('religion_id') == $religion->id ? 'selected' : '' }}>
                                                {{ $religion->title }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="job_type_id">نوع الوظيفة</label>
                                    <select name="job_type_id" id="job_type_id" class="form-control select2">
                                        <option value="">الكل</option>
                                        @foreach($job_types as $job_type)
                                            <option value="{{ $job_type->id }}" {{ request('job_type_id') == $job_type->id ? 'selected' : '' }}>
                                                {{ $job_type->title }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="experience_years">سنوات الخبرة</label>
                                    <select name="experience_years" id="experience_years" class="form-control select2">
                                        <option value="">الكل</option>
                                        <option value="1" {{ request('experience_years') == '1' ? 'selected' : '' }}>سنة واحدة</option>
                                        <option value="2" {{ request('experience_years') == '2' ? 'selected' : '' }}>سنتين</option>
                                        <option value="3" {{ request('experience_years') == '3' ? 'selected' : '' }}>3 سنوات</option>
                                        <option value="4" {{ request('experience_years') == '4' ? 'selected' : '' }}>4 سنوات</option>
                                        <option value="5" {{ request('experience_years') == '5' ? 'selected' : '' }}>5 سنوات وأكثر</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">بحث</button>
                                <a href="{{ route('admin.recruitment.select.maid', $client->id) }}" class="btn btn-secondary">إعادة تعيين</a>
                            </div>
                        </div>
                    </form>

                    <!-- Maids Grid -->
                    <div class="row">
                        @foreach($available_maids as $maid)
                        <div class="col-md-4 col-lg-3 mb-4">
                            <div class="card h-100">
                                <img src="{{ $maid->photo_url ?? asset('images/no-image.png') }}"
                                     class="card-img-top maid-image"
                                     alt="{{ $maid->name }}"
                                     onerror="this.onerror=null; this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'24\' height=\'24\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'currentColor\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'><rect x=\'3\' y=\'3\' width=\'18\' height=\'18\' rx=\'2\' ry=\'2\'/><circle cx=\'8.5\' cy=\'8.5\' r=\'1.5\'/><polyline points=\'21 15 16 10 5 21\'/></svg>';">
                                <div class="card-body">
                                    <h5 class="card-title">{{ $maid->name }}</h5>
                                    <div class="maid-details">
                                        <p class="mb-1">
                                            <strong>الجنسية:</strong>
                                            {{ $maid->nationality->sub_title ?? '' }}
                                        </p>
                                        <p class="mb-1">
                                            <strong>الدين:</strong>
                                            {{ $maid->religion->title ?? '' }}
                                        </p>
                                        <p class="mb-1">
                                            <strong>رقم جواز السفر:</strong>
                                            {{ $maid->passport_num }}
                                        </p>
                                        <p class="mb-1">
                                            <strong>سنوات الخبرة:</strong>
                                            {{ $maid->experience_years }}
                                        </p>
                                        <p class="mb-1">
                                            <strong>الراتب:</strong>
                                            {{ $maid->salary }}
                                        </p>
                                        <p class="mb-1">
                                            <strong>نوع الوظيفة:</strong>
                                            {{ $maid->jobTypes->pluck('title')->implode(', ') }}
                                        </p>
                                    </div>
                                    <button type="button"
                                            class="btn btn-primary btn-block mt-3 select-maid-btn"
                                            data-maid-id="{{ $maid->id }}"
                                            data-maid-name="{{ $maid->name }}"
                                            data-bs-toggle="modal"
                                            data-bs-target="#selectEmployeeModal"
                                            onclick="selectMaid({{ $maid->id }})">
                                        اختيار
                                    </button>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="selectEmployeeModal" tabindex="-1" aria-labelledby="selectEmployeeModalLabel">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="selectEmployeeModalLabel">اختيار الموظف المسؤول</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="employee_id">اختر الموظف المسؤول عن الطلب</label>
                    <select name="employee_id" id="employee_id" class="form-control select2" required>
                        <option value="">اختر الموظف المسؤول...</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-primary" id="confirmSelection" onclick="confirmSelection()">تأكيد الاختيار</button>
            </div>
        </div>
    </div>
</div>

<!-- Main Script -->
<script>
    // Select maid function
    function selectMaid(maidId) {
        $('#selectEmployeeModal').data('maid-id', maidId);
        $('#selectEmployeeModal').data('client-id', {{ $client->id }});
    }

    // Confirm selection function
    function confirmSelection() {
        var employeeId = $('#employee_id').val();
        if (!employeeId) {
            Swal.fire({
                title: 'تنبيه!',
                text: 'الرجاء اختيار الموظف المسؤول',
                icon: 'warning',
                confirmButtonText: 'حسناً'
            });
            return;
        }

        var maidId = $('#selectEmployeeModal').data('maid-id');
        var clientId = $('#selectEmployeeModal').data('client-id');

        // إظهار مربع حوار التأكيد
        Swal.fire({
            title: 'تأكيد الطلب',
            text: 'هل أنت متأكد من إنشاء طلب الاستقدام؟',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'نعم، قم بإنشاء الطلب',
            cancelButtonText: 'إلغاء',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                // إظهار مؤشر التحميل
                $('#confirmSelection').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> جاري المعالجة...');

                // إرسال طلب AJAX لإنشاء الطلب
                var selectedMaid = $('.select-maid-btn[data-maid-id="' + maidId + '"]').closest('.card');
                var nationality = selectedMaid.find('.maid-details p:contains("الجنسية:")').text().replace('الجنسية:', '').trim();
                var experienceYears = selectedMaid.find('.maid-details p:contains("سنوات الخبرة:")').text().replace('سنوات الخبرة:', '').trim();
                var salary = selectedMaid.find('.maid-details p:contains("الراتب:")').text().replace('الراتب:', '').trim();

                return $.ajax({
                    url: "{{ route('admin.recruitment.store') }}",
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: {
                        client_id: clientId,
                        maid_id: maidId,
                        employee_id: employeeId,
                        job_title: "طلب استقدام خادمة",
                        nationality: nationality,
                        experience_years: experienceYears,
                        salary: salary,
                        notes: "تم إنشاء الطلب تلقائياً"
                    }
                });
            }
        }).then((result) => {
            if (result.isConfirmed) {
                if (result.value.success) {
                    Swal.fire({
                        title: 'تم بنجاح!',
                        text: result.value.message,
                        icon: 'success',
                        confirmButtonText: 'حسناً'
                    }).then(() => {
                        $('#selectEmployeeModal').modal('hide');
                        window.location.href = "{{ route('orders.index') }}";
                    });
                } else {
                    Swal.fire({
                        title: 'خطأ!',
                        text: result.value.message || 'حدث خطأ أثناء إنشاء الطلب',
                        icon: 'error',
                        confirmButtonText: 'حسناً'
                    });
                }
            }
            $('#confirmSelection').prop('disabled', false).html('تأكيد الاختيار');
        }).catch(error => {
            console.error('Error:', error);
            Swal.fire({
                title: 'خطأ!',
                text: 'حدث خطأ أثناء معالجة الطلب',
                icon: 'error',
                confirmButtonText: 'حسناً'
            });
            $('#confirmSelection').prop('disabled', false).html('تأكيد الاختيار');
        });
    }

    // Initialize Select2
    $(document).ready(function() {
        $('.select2').select2({
            placeholder: "اختر...",
            allowClear: true
        });

        $('#employee_id').select2({
            dropdownParent: $('#selectEmployeeModal'),
            placeholder: "اختر الموظف المسؤول...",
            allowClear: true
        });
    });
</script>

@endsection

@push('styles')
<style>
    .maid-details {
        font-size: 0.9rem;
    }
    .card {
        transition: transform 0.2s;
    }
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    .select2-container {
        width: 100% !important;
    }
    .maid-image {
        height: 200px;
        object-fit: cover;
        background-color: #f8f9fa;
    }
    .maid-image.error {
        background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>');
        background-repeat: no-repeat;
        background-position: center;
        background-size: 50px;
    }
</style>
@endpush
