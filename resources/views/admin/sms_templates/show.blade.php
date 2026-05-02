@extends('admin.layout.indexs.index')

@section('title', 'عرض قالب الرسالة النصية')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="card-title">
                                <i class="fas fa-eye me-2"></i>
                                عرض قالب الرسالة النصية: {{ $smsTemplate->title }}
                            </h3>
                            <div>
                                <a href="/admin/sms-templates/{{ $smsTemplate->id }}/edit" class="btn btn-warning me-2">
                                    <i class="fas fa-edit me-1"></i>
                                    تعديل
                                </a>
                                <a href="/admin/sms-templates" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-1"></i>
                                    العودة للقائمة
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <h5 class="text-primary">
                                        <i class="fas fa-info-circle me-2"></i>
                                        معلومات القالب
                                    </h5>
                                    <table class="table table-borderless">
                                        <tr>
                                            <td class="fw-bold" width="30%">اسم القالب:</td>
                                            <td><code class="bg-light p-1 rounded">{{ $smsTemplate->name }}</code></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">العنوان:</td>
                                            <td>{{ $smsTemplate->title }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">القناة:</td>
                                            <td>
                                                <span class="badge {{ $smsTemplate->channel === 'sms' ? 'bg-info text-dark' : ($smsTemplate->channel === 'whatsapp' ? 'bg-success' : 'bg-primary') }}">
                                                    {{ $smsTemplate->channel_label }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">الترتيب:</td>
                                            <td>{{ $smsTemplate->sort_order ?? 0 }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">الحالة:</td>
                                            <td>
                                                @if ($smsTemplate->is_active)
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check me-1"></i>نشط
                                                    </span>
                                                @else
                                                    <span class="badge bg-danger">
                                                        <i class="fas fa-times me-1"></i>غير نشط
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">تاريخ الإنشاء:</td>
                                            <td>{{ $smsTemplate->created_at->format('d/m/Y H:i') }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">آخر تحديث:</td>
                                            <td>{{ $smsTemplate->updated_at->format('d/m/Y H:i') }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-4">
                                    <h5 class="text-primary">
                                        <i class="fas fa-code me-2"></i>
                                        المتغيرات المتاحة
                                    </h5>
                                    @if ($smsTemplate->variables && count($smsTemplate->variables) > 0)
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach ($smsTemplate->variables as $variable)
                                                <span class="badge bg-info p-2">
                                                    <i class="fas fa-tag me-1"></i>
                                                    {{ $variable }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="text-muted">لا توجد متغيرات في هذا القالب</p>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-12">
                                <h5 class="text-primary mb-3">
                                    <i class="fas fa-comment me-2"></i>
                                    نص الرسالة
                                </h5>
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <pre class="mb-0" style="white-space: pre-wrap; font-family: inherit;">{{ $smsTemplate->message }}</pre>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- <hr>

                        <div class="row">
                            <div class="col-12">
                                <h5 class="text-primary mb-3">
                                    <i class="fas fa-eye me-2"></i>
                                    معاينة الرسالة
                                </h5>

                                <div class="card border-primary">
                                    <div class="card-header bg-primary text-white">
                                        <i class="fas fa-mobile-alt me-2"></i>
                                        معاينة كما ستظهر للعميل
                                    </div>
                                    <div class="card-body">
                                        <div class="row mb-3">
                                            <div class="col-md-4">
                                                <label class="form-label">اسم العميل:</label>
                                                <input type="text" id="clientName" class="form-control" value="أحمد محمد"
                                                    onchange="updatePreview()">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">رقم جوال العميل:</label>
                                                <input type="text" id="clientPhone" class="form-control"
                                                    value="0501234567" onchange="updatePreview()">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">إيميل العميل:</label>
                                                <input type="text" id="clientEmail" class="form-control"
                                                    value="client@example.com" onchange="updatePreview()">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-4">
                                                <label class="form-label">رقم العقد:</label>
                                                <input type="text" id="contractNumber" class="form-control"
                                                    value="12345" onchange="updatePreview()">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">اسم الشركة:</label>
                                                <input type="text" id="companyName" class="form-control"
                                                    value="شركة أسس الإنجاز للاستقدام" onchange="updatePreview()">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">رقم جوال الشركة:</label>
                                                <input type="text" id="companyPhone" class="form-control"
                                                    value="0112345678" onchange="updatePreview()">
                                            </div>
                                        </div>

                                        <div class="alert alert-success" id="previewResult">
                                            <!-- سيتم ملء المعاينة هنا -->
                                        </div>

                                        <div class="d-flex justify-content-end">
                                            <button type="button" class="btn btn-primary" onclick="updatePreview()">
                                                <i class="fas fa-sync me-1"></i>
                                                تحديث المعاينة
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div> --}}

                        {{-- <hr> --}}

                        <div class="d-flex justify-content-between">
                            <a href="/admin/sms-templates" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i>
                                العودة للقائمة
                            </a>
                            <div>
                                {{-- <button type="button" class="btn btn-success me-2" onclick="testTemplate()">
                                    <i class="fas fa-vial me-1"></i>
                                    اختبار القالب
                                </button> --}}
                                <a href="/admin/sms-templates/{{ $smsTemplate->id }}/edit" class="btn btn-warning">
                                    <i class="fas fa-edit me-1"></i>
                                    تعديل القالب
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        const originalMessage = @json($smsTemplate->message);

        $(document).ready(function() {
            // تحديث المعاينة
            window.updatePreview = function() {
                const clientName = $('#clientName').val() || 'أحمد محمد';
                const clientPhone = $('#clientPhone').val() || '0501234567';
                const clientEmail = $('#clientEmail').val() || 'client@example.com';
                const contractNumber = $('#contractNumber').val() || '12345';
                const companyName = $('#companyName').val() || 'شركة أسس الإنجاز للاستقدام';
                const companyPhone = $('#companyPhone').val() || '0112345678';
                const companyEmail = 'info@asas-alinjaz.com';

                let processedMessage = originalMessage
                    .replace(/{client_name}/g, clientName)
                    .replace(/{client_phone}/g, clientPhone)
                    .replace(/{client_email}/g, clientEmail)
                    .replace(/{contract_number}/g, contractNumber)
                    .replace(/{company_name}/g, companyName)
                    .replace(/{company_phone}/g, companyPhone)
                    .replace(/{company_email}/g, companyEmail);

                $('#previewResult').html(`
            <div class="d-flex align-items-start">
                <i class="fas fa-sms text-primary me-3 mt-1"></i>
                <div class="w-100">
                    <strong>الرسالة النهائية:</strong>
                    <div class="mt-2 p-3 bg-white border rounded text-dark">${processedMessage}</div>
                    <div class="mt-2 d-flex justify-content-between">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            عدد الأحرف: ${processedMessage.length}
                        </small>
                        <small class="text-muted">
                            عدد الكلمات: ${processedMessage.split(' ').length}
                        </small>
                    </div>
                </div>
            </div>
        `);
            }

            // اختبار القالب
            window.testTemplate = function() {
                if (originalMessage) {
                    updatePreview();
                    // alert('تم اختبار القالب! تحقق من المعاينة أدناه.');
                    Swal.fire('نجاح', 'تم اختبار القالب! تحقق من المعاينة أدناه.', 'success');
                } else {
                    // alert('لا يوجد نص للاختبار');
                    Swal.fire('نجاح', 'لا يوجد نص للاختبار', 'success');
                    
                }
            }

            // تحديث المعاينة عند تغيير أي حقل
            $('#previewResult').closest('.card-body').find('input').on('input change', function() {
                updatePreview();
            });

            // تحديث المعاينة عند تحميل الصفحة
            updatePreview();
        });
    </script>
@endsection
