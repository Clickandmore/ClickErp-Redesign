@extends('admin.layout.master')

@section('title', 'إعدادات الرسائل النصية للعقود')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-sms me-2"></i>
                        إعدادات الرسائل النصية للعقود
                    </h3>
                </div>
                <div class="card-body">
                    <form id="smsSettingsForm" method="POST">
                        @csrf

                        <div class="row">
                            <div class="col-md-12">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    يمكنك تخصيص الرسائل النصية التي يتم إرسالها للعملاء عند تحديث حالة العقود. استخدم المتغيرات التالية:
                                    <br>
                                    <code>{client_name}</code> - اسم العميل
                                    <br>
                                    <code>{contract_number}</code> - رقم العقد
                                    <br>
                                    <code>{company_name}</code> - اسم الشركة
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- رسالة الإسناد -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="msaned_message" class="form-label">
                                        <i class="fas fa-check-circle text-primary me-1"></i>
                                        رسالة الإسناد
                                    </label>
                                    <textarea name="msaned_message" id="msaned_message" class="form-control" rows="4"
                                        placeholder="أدخل رسالة الإسناد...">عزيزي {client_name}، نود إعلامكم بأن عقدكم رقم {contract_number} قد تم إسناده بنجاح. سنتواصل معكم قريباً لمتابعة الإجراءات. {company_name}</textarea>
                                </div>
                            </div>

                            <!-- رسالة التفويض -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="tafwed_message" class="form-label">
                                        <i class="fas fa-file-signature text-warning me-1"></i>
                                        رسالة التفويض
                                    </label>
                                    <textarea name="tafwed_message" id="tafwed_message" class="form-control" rows="4"
                                        placeholder="أدخل رسالة التفويض...">عزيزي {client_name}، تم تفويض عقدكم رقم {contract_number} بنجاح. الآن في مرحلة المعالجة والمراجعة. {company_name}</textarea>
                                </div>
                            </div>

                            <!-- رسالة التجهيز -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="taffez_message" class="form-label">
                                        <i class="fas fa-cogs text-info me-1"></i>
                                        رسالة التجهيز
                                    </label>
                                    <textarea name="taffez_message" id="taffez_message" class="form-control" rows="4"
                                        placeholder="أدخل رسالة التجهيز...">عزيزي {client_name}، تم تجهيز عقدكم رقم {contract_number} وهو الآن في مرحلة الإعداد النهائي. {company_name}</textarea>
                                </div>
                            </div>

                            <!-- رسالة التذكرة -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="tazker_message" class="form-label">
                                        <i class="fas fa-plane text-primary me-1"></i>
                                        رسالة التذكرة
                                    </label>
                                    <textarea name="tazker_message" id="tazker_message" class="form-control" rows="4"
                                        placeholder="أدخل رسالة التذكرة...">عزيزي {client_name}، تم إصدار تذكرة السفر لعقدكم رقم {contract_number}. سنتواصل معكم قريباً لتحديد موعد الاستلام. {company_name}</textarea>
                                </div>
                            </div>

                            <!-- رسالة الإكمال -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="completed_message" class="form-label">
                                        <i class="fas fa-check-double text-success me-1"></i>
                                        رسالة الإكمال
                                    </label>
                                    <textarea name="completed_message" id="completed_message" class="form-control" rows="4"
                                        placeholder="أدخل رسالة الإكمال...">عزيزي {client_name}، تم إكمال عقدكم رقم {contract_number} بنجاح وتم تسليم العاملة. نتمنى لكم تجربة مميزة. للاستفسار: اتصل بنا. {company_name}</textarea>
                                </div>
                            </div>

                            <!-- رسالة الإرجاع -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="back_message" class="form-label">
                                        <i class="fas fa-undo text-danger me-1"></i>
                                        رسالة الإرجاع
                                    </label>
                                    <textarea name="back_message" id="back_message" class="form-control" rows="4"
                                        placeholder="أدخل رسالة الإرجاع...">عزيزي {client_name}، نعتذر لإبلاغكم بأن عقدكم رقم {contract_number} قد تم إرجاعه. سنتواصل معكم لتوضيح الأسباب والحلول البديلة. {company_name}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <hr>
                                <div class="d-flex justify-content-between">
                                    <button type="button" class="btn btn-secondary" onclick="resetToDefaults()">
                                        <i class="fas fa-undo me-1"></i>
                                        استعادة الافتراضي
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i>
                                        حفظ الإعدادات
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

@section('scripts')
<script>
    function resetToDefaults() {
        $('#msaned_message').val('عزيزي {client_name}، نود إعلامكم بأن عقدكم رقم {contract_number} قد تم إسناده بنجاح. سنتواصل معكم قريباً لمتابعة الإجراءات. {company_name}');
        $('#tafwed_message').val('عزيزي {client_name}، تم تفويض عقدكم رقم {contract_number} بنجاح. الآن في مرحلة المعالجة والمراجعة. {company_name}');
        $('#taffez_message').val('عزيزي {client_name}، تم تجهيز عقدكم رقم {contract_number} وهو الآن في مرحلة الإعداد النهائي. {company_name}');
        $('#tazker_message').val('عزيزي {client_name}، تم إصدار تذكرة السفر لعقدكم رقم {contract_number}. سنتواصل معكم قريباً لتحديد موعد الاستلام. {company_name}');
        $('#completed_message').val('عزيزي {client_name}، تم إكمال عقدكم رقم {contract_number} بنجاح وتم تسليم العاملة. نتمنى لكم تجربة مميزة. للاستفسار: اتصل بنا. {company_name}');
        $('#back_message').val('عزيزي {client_name}، نعتذر لإبلاغكم بأن عقدكم رقم {contract_number} قد تم إرجاعه. سنتواصل معكم لتوضيح الأسباب والحلول البديلة. {company_name}');
    }

    $('#smsSettingsForm').on('submit', function(e) {
        e.preventDefault();

        Swal.fire({
            title: 'حفظ الإعدادات',
            text: 'هل أنت متأكد من حفظ إعدادات الرسائل النصية؟',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'نعم، احفظ',
            cancelButtonText: 'إلغاء'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire(
                    'تم الحفظ!',
                    'تم حفظ إعدادات الرسائل النصية بنجاح.',
                    'success'
                );
            }
        });
    });
</script>
@endsection
