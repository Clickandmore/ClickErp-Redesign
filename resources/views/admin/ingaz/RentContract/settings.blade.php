@extends('admin.layout.indexs.index')

@section('content')
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h3 class="card-title">
                                <i class="fas fa-cogs me-2"></i>
                                إعدادات عقد التأجير
                            </h3>
                            <div class="card-tools">
                                <a href="{{ route('RentContract.index') }}" class="btn btn-light btn-sm">
                                    <i class="fas fa-arrow-right me-1"></i>
                                    رجوع
                                </a>
                            </div>
                        </div>

                        <form id="form-rent-contract-settings" action="{{ route('rent-contract.settings.update') }}"
                              method="POST" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')

                            <div class="card-body">
                                <!-- بيانات الشركة -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <h4 class="border-bottom pb-2 mb-3">
                                            <i class="fas fa-building me-2"></i>
                                            بيانات الشركة
                                        </h4>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">اسم الشركة</label>
                                        <input type="text" name="company_name" class="form-control"
                                               value="{{ $settings['company_name'] ?? documentSetting('document_company_name', documentSetting('website_name', config('app.name'))) }}"
                                               data-validation="required">
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">رقم الترخيص</label>
                                        <input type="text" name="license_number" class="form-control"
                                               value="{{ $settings['license_number'] ?? '4001772' }}"
                                               data-validation="required">
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">تاريخ الترخيص</label>
                                        <input type="date" name="license_date" class="form-control"
                                               value="{{ $settings['license_date'] ?? '2022-10-13' }}"
                                               data-validation="required">
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">رقم السجل التجاري</label>
                                        <input type="text" name="commercial_register" class="form-control"
                                               value="{{ $settings['commercial_register'] ?? '1010601958' }}"
                                               data-validation="required">
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">تاريخ السجل التجاري</label>
                                        <input type="date" name="commercial_date" class="form-control"
                                               value="{{ $settings['commercial_date'] ?? '1441-03-03' }}">
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">المدينة</label>
                                        <input type="text" name="city" class="form-control"
                                               value="{{ $settings['city'] ?? 'الرياض' }}" data-validation="required">
                                    </div>
                                </div>

                                <!-- معلومات الاتصال -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <h4 class="border-bottom pb-2 mb-3">
                                            <i class="fas fa-phone me-2"></i>
                                            معلومات الاتصال
                                        </h4>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">العنوان</label>
                                        <input type="text" name="address" class="form-control"
                                               value="{{ $settings['address'] ?? 'الرياض' }}">
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">صندوق البريد</label>
                                        <input type="text" name="po_box" class="form-control"
                                               value="{{ $settings['po_box'] ?? '' }}">
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">الرمز البريدي</label>
                                        <input type="text" name="postal_code" class="form-control"
                                               value="{{ $settings['postal_code'] ?? '' }}">
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">رقم الهاتف</label>
                                        <input type="text" name="phone" class="form-control"
                                               value="{{ $settings['phone'] ?? '' }}">
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">رقم الجوال</label>
                                        <input type="text" name="mobile" class="form-control"
                                               value="{{ $settings['mobile'] ?? '' }}">
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">الفاكس</label>
                                        <input type="text" name="fax" class="form-control"
                                               value="{{ $settings['fax'] ?? '966550316770+' }}">
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">البريد الإلكتروني</label>
                                        <input type="email" name="email" class="form-control"
                                               value="{{ $settings['email'] ?? '' }}">
                                    </div>
                                </div>

                                <!-- إعدادات العقد -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <h4 class="border-bottom pb-2 mb-3">
                                            <i class="fas fa-file-contract me-2"></i>
                                            إعدادات العقد الافتراضية
                                        </h4>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">مدة الاختبار الافتراضية (بالأيام)</label>
                                        <input type="number" name="default_trial_period" class="form-control"
                                               value="{{ $settings['default_trial_period'] ?? '90' }}" min="1">
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">ساعات العمل اليومية الافتراضية</label>
                                        <input type="number" name="default_working_hours" class="form-control"
                                               value="{{ $settings['default_working_hours'] ?? '8' }}" min="1" max="24">
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">قيمة الخصم اليومي (ريال)</label>
                                        <input type="number" name="daily_deduction" class="form-control"
                                               value="{{ $settings['daily_deduction'] ?? '150' }}" min="0">
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">مدة الإشعار المسبق (بالأيام)</label>
                                        <input type="number" name="notice_period" class="form-control"
                                               value="{{ $settings['notice_period'] ?? '30' }}" min="1">
                                    </div>
                                </div>

                                <div class="row mb-4">
                                    <div class="col-12">
                                        <h4 class="border-bottom pb-2 mb-3">
                                            <i class="fas fa-bell me-2"></i>
                                            تنبيه انتهاء عقد التأجير
                                        </h4>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">تفعيل التنبيه</label>
                                        <select name="expiry_reminder_enabled" class="form-control">
                                            <option value="1" {{ (string)($settings['expiry_reminder_enabled'] ?? '1') === '1' ? 'selected' : '' }}>مفعل</option>
                                            <option value="0" {{ (string)($settings['expiry_reminder_enabled'] ?? '1') === '0' ? 'selected' : '' }}>غير مفعل</option>
                                        </select>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">إرسال التنبيه قبل الانتهاء بـ (يوم)</label>
                                        <input type="number" name="expiry_reminder_days" class="form-control"
                                               value="{{ $settings['expiry_reminder_days'] ?? '2' }}" min="1" max="60">
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">قناة إرسال التنبيه</label>
                                        <select name="expiry_reminder_channel" class="form-control">
                                            <option value="sms" {{ ($settings['expiry_reminder_channel'] ?? 'sms') === 'sms' ? 'selected' : '' }}>رسالة نصية</option>
                                            <option value="whatsapp" {{ ($settings['expiry_reminder_channel'] ?? 'sms') === 'whatsapp' ? 'selected' : '' }}>واتساب</option>
                                            <option value="both" {{ ($settings['expiry_reminder_channel'] ?? 'sms') === 'both' ? 'selected' : '' }}>رسالة نصية وواتساب</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- الشعار والتوقيع -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <h4 class="border-bottom pb-2 mb-3">
                                            <i class="fas fa-image me-2"></i>
                                            الشعار والتوقيع
                                        </h4>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">شعار الشركة</label>
                                        <input type="file" name="logo" class="form-control" accept="image/*">
                                        <small class="text-muted">اترك فارغاً إذا لم ترد تغيير الشعار</small>
                                        @if(!empty($settings['logo']))
                                            <div class="mt-2">
                                                <img src="{{ asset('storage/' . $settings['logo']) }}" alt="Logo"
                                                     style="max-height: 100px;">
                                            </div>
                                        @endif
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">توقيع الشركة</label>
                                        <input type="file" name="company_signature" class="form-control" accept="image/*">
                                        <small class="text-muted">اترك فارغاً إذا لم ترد تغيير توقيع الشركة</small>
                                        @if(!empty($settings['company_signature']))
                                            <div class="mt-2">
                                                <img src="{{ asset('storage/' . $settings['company_signature']) }}" alt="Company signature"
                                                     style="max-height: 100px;">
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="card-footer">
                                <button type="submit" id="submitBtn" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> حفظ الإعدادات
                                </button>

                                <a href="{{ route('RentContract.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times me-1"></i>
                                    إلغاء
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
    <script>
        $(document).ready(function () {
            $('#form-rent-contract-settings').on('submit', function (e) {
                e.preventDefault();

                let form = $(this);
                let formData = new FormData(this);
                let url = form.attr('action');
                let $btn = $('#submitBtn');
                let originalHtml = $btn.html();

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    beforeSend: function () {
                        $btn.prop('disabled', true).html(
                            '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> جاري الحفظ...'
                        );

                        let emptyFields = [];
                        form.find('[data-validation="required"]').each(function () {
                            if (!$(this).val().trim()) {
                                emptyFields.push($(this).attr('name') || this.id || 'حقل');
                                $(this).addClass('is-invalid');
                            } else {
                                $(this).removeClass('is-invalid');
                            }
                        });

                        if (emptyFields.length > 0) {
                            Swal.fire({
                                icon: 'error',
                                title: '{{ __("ingaz.error") }}',
                                html: `<p style="margin:0;color:red;text-align:center;">من فضلك املأ جميع الحقول المطلوبة</p>`
                            });

                            // خليه يرجع بعد 300ms عشان المستخدم يشوف اللودر لحظة
                            setTimeout(() => {
                                $btn.prop('disabled', false).html(originalHtml);
                            }, 300);

                            return false;
                        }

                    },

                    success: function (response) {
                        handleSuccessResponce(response);
                        form[0].reset();
                        form.find('.is-invalid').removeClass('is-invalid'); // يشيل الأحمر
                    },
                    error: function (xhr) {
                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;
                            let errorMessages = '';
                            for (let key in errors) {
                                errorMessages += `<p style="margin:0 0 5px; color:red; text-align:center;">${errors[key][0]}</p>`;
                                form.find('[name="' + key + '"]').addClass('is-invalid');
                            }
                            Swal.fire({
                                icon: 'error',
                                title: '{{ __("ingaz.error") }}',
                                html: `<div>${errorMessages}</div>`,
                            });
                        } else {
                            Swal.fire('{{ __("ingaz.error") }}', '{{ __("ingaz.error_from_server") }}', 'error');
                        }
                    },
                    complete: function () {
                        $btn.prop('disabled', false).html(originalHtml);
                    }
                });
            });
        });

        $(document).ready(function () {
            // تنسيق التواريخ
            $('input[type="date"]').each(function () {
                if ($(this).val()) {
                    // تحويل التاريخ الهجري إلى ميلادي إذا لزم
                    var val = $(this).val();
                    if (!val.includes('-')) {
                        $(this).val('');
                    }
                }
            });
        });
    </script>
@endsection
