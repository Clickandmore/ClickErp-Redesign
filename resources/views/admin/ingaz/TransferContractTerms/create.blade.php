@extends('admin.layout.indexs.index')

@section('title', 'إضافة بند جديد - عقد نقل الخدمات')
<style>
    .loader {
        border: 3px solid #f3f3f3;
        border-top: 3px solid #fff;
        border-radius: 50%;
        width: 18px;
        height: 18px;
        animation: spin 0.8s linear infinite;
        display: inline-block;
        vertical-align: middle;
        margin-left: 5px;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }
        100% {
            transform: rotate(360deg);
        }
    }

</style>
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <!-- Header -->
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">الرئيسية</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('transfer-contract-terms.index') }}">بنود عقد
                                    نقل الخدمات</a></li>
                            <li class="breadcrumb-item active">إضافة بند جديد</li>
                        </ol>
                    </div>
                    <h4 class="page-title">إضافة بند جديد</h4>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title mb-0">
                            <i class="fas fa-plus"></i> إضافة بند جديد لعقد نقل الخدمات
                        </h4>
                    </div>
                    <div class="card-body">
                        <form id="transfer-contract-terms-form" action="{{ route('transfer-contract-terms.store') }}"
                              method="POST">
                            @csrf

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="section_title" class="form-label">عنوان القسم <span
                                                class="text-danger">*</span></label>
                                        <input type="text"
                                               class="form-control @error('section_title') is-invalid @enderror"
                                               id="section_title" name="section_title"
                                               value="{{ old('section_title') }}"
                                               placeholder="مثال: أولاً: موضوع العقد">
                                        @error('section_title')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <div class="form-text">عنوان القسم الذي ينتمي إليه البند</div>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="section_order" class="form-label">ترتيب القسم <span
                                                class="text-danger">*</span></label>
                                        <input type="number"
                                               class="form-control @error('section_order') is-invalid @enderror"
                                               id="section_order" name="section_order"
                                               value="{{ old('section_order', 1) }}" min="1">
                                        @error('section_order')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <div class="form-text">ترتيب القسم في العقد</div>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="term_number" class="form-label">رقم البند</label>
                                        <input type="number"
                                               class="form-control @error('term_number') is-invalid @enderror"
                                               id="term_number" name="term_number" value="{{ old('term_number') }}"
                                               min="1">
                                        @error('term_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <div class="form-text">رقم البند داخل القسم (اختياري)</div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="term_order" class="form-label">ترتيب البند <span
                                                class="text-danger">*</span></label>
                                        <input type="number"
                                               class="form-control @error('term_order') is-invalid @enderror"
                                               id="term_order" name="term_order" value="{{ old('term_order', 1) }}"
                                               min="1">
                                        @error('term_order')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <div class="form-text">ترتيب البند داخل القسم</div>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="page_number" class="form-label">رقم الصفحة <span
                                                class="text-danger">*</span></label>
                                        <select class="form-select @error('page_number') is-invalid @enderror"
                                                id="page_number" name="page_number">
                                            <option value="">اختر الصفحة</option>
                                            <option value="1" {{ old('page_number') == 1 ? 'selected' : '' }}>الصفحة
                                                الأولى
                                            </option>
                                            <option value="2" {{ old('page_number') == 2 ? 'selected' : '' }}>الصفحة
                                                الثانية
                                            </option>
                                            <option value="3" {{ old('page_number') == 3 ? 'selected' : '' }}>الصفحة
                                                الثالثة
                                            </option>
                                        </select>
                                        @error('page_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="is_active"
                                                   name="is_active"
                                                {{ old('is_active', true) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="is_active">
                                                البند نشط
                                            </label>
                                        </div>
                                        <div class="form-text">البنود غير النشطة لن تظهر في العقد المطبوع</div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="term_content" class="form-label">محتوى البند <span
                                        class="text-danger">*</span></label>
                                <textarea class="form-control @error('term_content') is-invalid @enderror"
                                          id="term_content" name="term_content" rows="6"
                                          placeholder="اكتب محتوى البند هنا...">{{ old('term_content') }}</textarea>
                                @error('term_content')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror

                                <div class="form-text mt-2">
                                    <strong>المتغيرات المتاحة:</strong><br>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <code>[اسم_العاملة]</code> - اسم العاملة<br>
                                            <code>[جنسية_العاملة]</code> - جنسية العاملة<br>
                                            <code>[رقم_الهوية]</code> - رقم هوية العميل<br>
                                            <code>[رقم_الجواز]</code> - رقم جواز العاملة<br>
                                            <code>[المهنة]</code> - مهنة العاملة<br>
                                            <code>[مدة_العقد]</code> - مدة العقد
                                        </div>
                                        <div class="col-md-6">
                                            <code>[تاريخ_البداية]</code> - تاريخ بداية العقد<br>
                                            <code>[فترة_التجربة]</code> - مدة فترة التجربة<br>
                                            <code>[قيمة_العقد]</code> - قيمة العقد<br>
                                            <code>[طريقة_الدفع]</code> - طريقة الدفع<br>
                                            <code>[تاريخ_التحرير]</code> - تاريخ تحرير العقد
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-12">
                                    <div class="text-end">
                                        <a href="{{ route('transfer-contract-terms.index') }}"
                                           class="btn btn-secondary me-2">
                                            <i class="fas fa-arrow-left"></i> إلغاء
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> حفظ البند
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
<!-- jQuery CDN -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"
        integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo="
        crossorigin="anonymous"></script>

<script>
    $(document).ready(function () {
        $('#transfer-contract-terms-form').on('submit', function (e) {
            e.preventDefault();
            let form = $(this);
            let formData = new FormData(this);
            let url = $(this).attr('action');
            let btn = form.find('button[type="submit"]');
            let originalBtnHtml = btn.html();
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> جاري إرسال البيانات...');
            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function () {
                    btn.prop('disabled', true)
                        .html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> جاري إرسال البيانات...');
                },
                success: function (response) {
                    btn.prop('disabled', false).html(originalBtnHtml);
                    Swal.fire({
                        icon: 'success',
                        title: '{{ __("ingaz.success") }}',
                        html: response.message,
                    });
                    $('#transfer-contract-terms-form')[0].reset();
                    setTimeout(function () {
                        location.href = response.data.redirect
                    }, 1000)
                },
                error: function (xhr) {
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        let errorMessages = '';
                        for (let key in errors) {
                            errorMessages += `<p style="margin: 0 0 5px; color: red; text-align: center;">${errors[key][0]}</p>`;
                        }
                        Swal.fire({
                            icon: 'error',
                            title: '{{ __("ingaz.error") }}',
                            html: `<div>${errorMessages}</div>`,
                        });
                    } else {
                        Swal.fire('{{ __("ingaz.error") }}', '{{ __("ingaz.error_from_server") }}', 'error');
                    }
                    btn.prop('disabled', false).html(originalBtnHtml);

                }
            });
        })
    });
</script>
@section('css')
    <style>
        .form-text {
            font-size: 0.875em;
        }

        .form-text code {
            background-color: #f8f9fa;
            padding: 2px 4px;
            border-radius: 3px;
            font-size: 0.85em;
        }

        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }

        .form-check-input:checked {
            background-color: #198754;
            border-color: #198754;
        }
    </style>
@endsection
