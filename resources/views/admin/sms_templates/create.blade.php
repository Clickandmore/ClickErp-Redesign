@extends('admin.layout.indexs.index')

@section('title', 'إضافة قالب رسالة نصية جديد')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="card-title">
                                <i class="fas fa-plus me-2"></i>
                                إضافة قالب رسالة نصية جديد
                            </h3>
                            <a href="/admin/sms-templates" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i>
                                العودة للقائمة
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        @if (session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>خطأ في البيانات:</strong>
                                <ul class="mb-0 mt-2">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <form action="/admin/sms-templates" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">
                                            <i class="fas fa-tag me-1"></i>
                                            اسم القالب <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" name="name" id="name" class="form-control"
                                            value="{{ old('name') }}" required placeholder="مثال: new_status, reminder">
                                        <small class="text-muted">اسم فريد للقالب (بالإنجليزية، بدون مسافات)</small>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="title" class="form-label">
                                            <i class="fas fa-heading me-1"></i>
                                            عنوان القالب <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" name="title" id="title" class="form-control"
                                            value="{{ old('title') }}" required placeholder="مثال: رسالة تذكير">
                                        <small class="text-muted">عنوان وصفي للقالب</small>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-12">
                                    <div class="mb-3">
                                        <label for="message" class="form-label">
                                            <i class="fas fa-comment me-1"></i>
                                            نص الرسالة <span class="text-danger">*</span>
                                        </label>
                                        <textarea name="message" id="message" class="form-control" rows="6" required
                                            placeholder="أدخل نص الرسالة هنا...">{{ old('message') }}</textarea>
                                        <small class="text-muted">
                                            يمكنك استخدام المتغيرات التالية:
                                            <br>
                                            <strong>معلومات العميل:</strong>
                                            <span class="badge bg-info mx-1">{client_name}</span>
                                            <span class="badge bg-info mx-1">{client_phone}</span>
                                            <span class="badge bg-info mx-1">{client_email}</span>
                                            <br>
                                            <strong>معلومات العقد:</strong>
                                            <span class="badge bg-success mx-1">{contract_number}</span>
                                            <br>
                                            <strong>معلومات الشركة:</strong>
                                            <span class="badge bg-warning mx-1">{company_name}</span>
                                            <span class="badge bg-warning mx-1">{company_phone}</span>
                                            <span class="badge bg-warning mx-1">{company_email}</span>
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input type="hidden" name="is_active" value="0">
                                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
                                                value="1" {{ old('is_active', 1) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="is_active">
                                                <i class="fas fa-toggle-on me-1"></i>
                                                قالب نشط
                                            </label>
                                        </div>
                                        <small class="text-muted">إذا كان غير نشط، لن يتم استخدامه في إرسال الرسائل</small>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-12">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>نصائح لإنشاء قالب فعال:</strong>
                                        <ul class="mb-0 mt-2">
                                            <li>استخدم لغة واضحة ومفهومة</li>
                                            <li>اجعل الرسالة قصيرة ومفيدة</li>
                                            <li>تأكد من استخدام المتغيرات بالشكل الصحيح</li>
                                            <li>اختبر القالب بعد الإنشاء للتأكد من صحته</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <hr>

                            <div class="d-flex justify-content-between">
                                <a href="/admin/sms-templates" class="btn btn-secondary">
                                    <i class="fas fa-times me-1"></i>
                                    إلغاء
                                </a>
                                <div>
                                    {{-- <button type="button" class="btn btn-info me-2" onclick="previewMessage()">
                                        <i class="fas fa-eye me-1"></i>
                                        معاينة
                                    </button> --}}
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-save me-1"></i>
                                        إنشاء القالب
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal معاينة الرسالة -->
    {{-- <div class="modal fade" id="previewModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">معاينة الرسالة</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">اسم العميل:</label>
                                <input type="text" id="previewClientName" class="form-control" value="أحمد محمد">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">رقم جوال العميل:</label>
                                <input type="text" id="previewClientPhone" class="form-control" value="0501234567">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">إيميل العميل:</label>
                                <input type="text" id="previewClientEmail" class="form-control"
                                    value="client@example.com">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">رقم العقد:</label>
                                <input type="text" id="previewContractNumber" class="form-control" value="12345">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الرسالة النهائية:</label>
                        <div class="alert alert-success" id="previewResult"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                    <button type="button" class="btn btn-primary" onclick="updatePreview()">تحديث المعاينة</button>
                </div>
            </div>
        </div>
    </div> --}}
@endsection

@section('js')
    <script>
        $(document).ready(function() {
            $(document).on('change', '#is_active', function(e) {
                console.log("value", $(this).val());

            });
            // معالج زر المعاينة
            window.previewMessage = function() {
                const message = $('#message').val();
                if (!message) {
                    alert('يرجى كتابة نص الرسالة أولاً');
                    return;
                }
                updatePreview();
                $('#previewModal').modal('show');
            }

            // تحديث المعاينة
            window.updatePreview = function() {
                const message = $('#message').val() || '';
                const clientName = $('#previewClientName').val() || 'أحمد محمد';
                const clientPhone = $('#previewClientPhone').val() || '0501234567';
                const clientEmail = $('#previewClientEmail').val() || 'client@example.com';
                const contractNumber = $('#previewContractNumber').val() || '12345';
                const companyName = @json(documentSetting('document_company_name', documentSetting('website_name', config('app.name'))));
                const companyPhone = @json(documentSetting('phone', ''));
                const companyEmail = @json(documentSetting('email', ''));

                let processedMessage = message
                    .replace(/{client_name}/g, clientName)
                    .replace(/{client_phone}/g, clientPhone)
                    .replace(/{client_email}/g, clientEmail)
                    .replace(/{contract_number}/g, contractNumber)
                    .replace(/{company_name}/g, companyName)
                    .replace(/{company_phone}/g, companyPhone)
                    .replace(/{company_email}/g, companyEmail);

                $('#previewResult').html(`
            <div class="p-3 bg-light border rounded">
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-sms text-primary me-2"></i>
                    <strong>الرسالة النهائية:</strong>
                </div>
                <div class="text-dark">${processedMessage}</div>
                <hr>
                <small class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    عدد الأحرف: ${processedMessage.length} |
                    عدد الكلمات: ${processedMessage.split(' ').length}
                </small>
            </div>
        `);
            }

            // تحديث المعاينة عند تغيير النص
            $('#message').on('input', function() {
                if ($('#previewModal').hasClass('show')) {
                    updatePreview();
                }
            });

            // تحديث المعاينة عند تغيير قيم المعاينة
            $('#previewModal input').on('input', function() {
                updatePreview();
            });

            // تحويل اسم القالب إلى lowercase وإزالة المسافات
            $('#name').on('input', function() {
                this.value = this.value.toLowerCase().replace(/\s+/g, '_').replace(/[^a-z0-9_]/g, '');
            });
        });
    </script>
@endsection
