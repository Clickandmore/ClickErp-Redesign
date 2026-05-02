@extends('admin.layout.indexs.index')

@section('page-title', 'قوالب الرسائل')
@section('title', 'قوالب الرسائل')

@push('style')
    <link rel="stylesheet" type="text/css" href="{{ asset('plugins/sweetalert/sweetalert.css') }}">
@endpush

@section('content')
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm mb-4" style="border-radius: 24px; overflow: hidden;">
        <div class="card-body p-4" style="background: linear-gradient(135deg, #16324f 0%, #0f766e 100%); color: #fff;">
            <div class="row align-items-center g-3">
                <div class="col-lg-8">
                    <span class="badge bg-light text-dark mb-3">Template Library</span>
                    <h2 class="mb-2">إدارة قوالب الرسائل</h2>
                    <p class="mb-0 text-white-50">المكتبة الموحدة لكل قوالب SMS وWhatsApp والإشعارات الداخلية، مع إمكانية تعديل النص وترتيب القوالب وتغيير قناة الإرسال لكل قالب.</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a href="{{ route('notifications.settings') }}" class="btn btn-outline-light me-2 mb-2">إعدادات القنوات</a>
                    <a href="{{ route('messages.index') }}" class="btn btn-outline-light me-2 mb-2">سجل الرسائل</a>
                    <a href="{{ route('admin.sms-templates.create') }}" class="btn btn-light mb-2">إضافة قالب جديد</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-2 col-6">
            <div class="card h-100 border-0 shadow-sm" style="border-radius: 18px;">
                <div class="card-body">
                    <small class="text-muted d-block mb-1">إجمالي القوالب</small>
                    <h3 class="mb-0">{{ number_format($stats['total']) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card h-100 border-0 shadow-sm" style="border-radius: 18px;">
                <div class="card-body">
                    <small class="text-muted d-block mb-1">القوالب النشطة</small>
                    <h3 class="mb-0">{{ number_format($stats['active']) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card h-100 border-0 shadow-sm" style="border-radius: 18px;">
                <div class="card-body">
                    <small class="text-muted d-block mb-1">قوالب SMS</small>
                    <h3 class="mb-0">{{ number_format($stats['sms']) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card h-100 border-0 shadow-sm" style="border-radius: 18px;">
                <div class="card-body">
                    <small class="text-muted d-block mb-1">قوالب واتساب</small>
                    <h3 class="mb-0">{{ number_format($stats['whatsapp']) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-12">
            <div class="card h-100 border-0 shadow-sm" style="border-radius: 18px;">
                <div class="card-body">
                    <small class="text-muted d-block mb-1">قوالب الإشعارات</small>
                    <h3 class="mb-0">{{ number_format($stats['notification']) }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm" style="border-radius: 22px;">
        <div class="card-body p-4">
            <form method="get" action="{{ route('admin.sms-templates.index') }}" class="row g-2 align-items-end mb-4">
                <div class="col-md-3">
                    <label class="form-label">القناة</label>
                    <select name="channel" class="form-control">
                        <option value="">الكل</option>
                        @foreach($channels as $channelKey => $channelLabel)
                            <option value="{{ $channelKey }}" {{ request('channel') === $channelKey ? 'selected' : '' }}>{{ $channelLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">الحالة</label>
                    <select name="status" class="form-control">
                        <option value="">الكل</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>نشط</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>غير نشط</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">بحث</label>
                    <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="اسم، عنوان، أو نص القالب">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100" type="submit">فلترة</button>
                </div>
            </form>

            <div class="alert alert-info d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    عدّل <strong>الترتيب</strong> و<strong>القناة</strong> و<strong>الحالة</strong> مباشرة من الجدول، ثم افتح صفحة التحرير إذا أردت تعديل نص القالب نفسه.
                </div>
                @if($templates->isNotEmpty())
                    <span class="badge bg-primary">الترتيب الحالي يعتمد على حقل sort_order</span>
                @endif
            </div>

            <form method="post" action="{{ route('admin.sms-templates.bulk-update') }}">
                @csrf
                <input type="hidden" name="channel_filter" value="{{ request('channel') }}">
                <input type="hidden" name="status_filter" value="{{ request('status') }}">
                <input type="hidden" name="search_filter" value="{{ request('search') }}">

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <div class="text-muted small">
                        أي تغيير في القناة هنا سيؤثر على مسارات الإرسال المرتبطة بهذا القالب، ويتم استخدام بيانات الربط من
                        <a href="{{ route('notifications.settings') }}">إعدادات القنوات</a>.
                    </div>
                    @if($templates->isNotEmpty())
                        <button class="btn btn-primary" type="submit">حفظ الترتيب والإعدادات</button>
                    @endif
                </div>

                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 84px;">الترتيب</th>
                                <th>القالب</th>
                                <th style="width: 190px;">القناة</th>
                                <th>الرسالة</th>
                                <th>المتغيرات</th>
                                <th style="width: 120px;">الحالة</th>
                                <th style="width: 230px;">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($templates as $template)
                                <tr>
                                    <td>
                                        <input type="number"
                                            name="sort_order[{{ $template->id }}]"
                                            class="form-control form-control-sm"
                                            min="0"
                                            value="{{ old('sort_order.' . $template->id, $template->sort_order ?? $loop->iteration) }}">
                                    </td>
                                    <td>
                                        <div class="fw-bold">{{ $template->title }}</div>
                                        <small class="text-muted d-block">{{ $template->name }}</small>
                                        <span class="badge {{ $template->channel === 'sms' ? 'bg-info text-dark' : ($template->channel === 'whatsapp' ? 'bg-success' : 'bg-primary') }} mt-2">
                                            {{ $template->channel_label }}
                                        </span>
                                    </td>
                                    <td>
                                        <select name="channel[{{ $template->id }}]" class="form-control form-control-sm">
                                            @foreach($channels as $channelKey => $channelLabel)
                                                <option value="{{ $channelKey }}" {{ old('channel.' . $template->id, $template->channel) === $channelKey ? 'selected' : '' }}>
                                                    {{ $channelLabel }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td style="min-width: 280px;">
                                        <div class="fw-semibold mb-1">{{ $template->title }}</div>
                                        <div class="text-muted small" style="white-space: pre-line;">{{ \Illuminate\Support\Str::limit($template->message, 140) }}</div>
                                    </td>
                                    <td>
                                        @forelse(($template->variables ?? []) as $variable)
                                            <span class="badge bg-light text-dark me-1 mb-1">{{ $variable }}</span>
                                        @empty
                                            <span class="text-muted">لا توجد متغيرات</span>
                                        @endforelse
                                    </td>
                                    <td>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="is_active[{{ $template->id }}]" id="is_active_{{ $template->id }}" {{ old('is_active.' . $template->id, $template->is_active) ? 'checked' : '' }}>
                                            <label class="form-check-label small" for="is_active_{{ $template->id }}">
                                                {{ $template->is_active ? 'نشط' : 'موقوف' }}
                                            </label>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-1">
                                            <button type="button" class="btn btn-sm btn-primary" onclick="openSendTemplate({{ $template->id }})" title="إرسال">
                                                <i class="fas fa-paper-plane"></i>
                                            </button>
                                            <a href="{{ route('admin.sms-templates.show', $template) }}" class="btn btn-sm btn-info" title="عرض">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.sms-templates.edit', $template) }}" class="btn btn-sm btn-warning" title="تعديل">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-success" onclick="testTemplate({{ $template->id }})" title="اختبار">
                                                <i class="fas fa-vial"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger" onclick="deleteTemplate({{ $template->id }}, '{{ addslashes($template->title) }}')" title="حذف">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-5">لا توجد قوالب مطابقة.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($templates->isNotEmpty())
                    <div class="d-flex justify-content-end mt-3">
                        <button class="btn btn-primary" type="submit">حفظ الترتيب والإعدادات</button>
                    </div>
                @endif
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="testTemplateModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">اختبار قالب الرسالة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="testTemplateForm">
                    <div id="sendControlsBox" class="d-none">
                        <div class="mb-3">
                            <label class="form-label">رقم الجوال:</label>
                            <input type="text" id="sendPhone" class="form-control" placeholder="5xxxxxxxx">
                            <small class="text-muted">سيتم إرسال الرسالة لهذا الرقم</small>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">قناة الإرسال:</label>
                                <select id="sendChannel" class="form-control" onchange="toggleSendChannelOptions()">
                                    <option value="sms" selected>SMS</option>
                                    <option value="whatsapp">واتساب</option>
                                </select>
                                <small class="text-muted">يتم الإرسال باستخدام إعدادات القناة المحفوظة في صفحة إعدادات القنوات.</small>
                            </div>
                            <div class="col-md-4 mb-3" id="smsSenderTypeBox">
                                <label class="form-label">اسم مرسل SMS:</label>
                                <select id="senderNameType" class="form-control">
                                    <option value="advertising" selected>إعلاني</option>
                                    <option value="verification">تحقق</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3 d-none" id="whatsappPurposeBox">
                                <label class="form-label">نوع واتساب:</label>
                                <select id="whatsappPurpose" class="form-control">
                                    <option value="verification" selected>تحقق</option>
                                    <option value="follow_up">متابعة</option>
                                    <option value="advertising">إعلاني</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div id="variablesContainer"></div>
                    <hr>
                    <div class="mb-3">
                        <label class="form-label">الرسالة النهائية:</label>
                        <div class="alert alert-info" id="processedMessage"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                <button type="button" class="btn btn-primary" onclick="processTemplate()">معالجة الرسالة</button>
                <button type="button" id="sendTemplateButton" class="btn btn-success d-none" onclick="sendTemplate()">إرسال الرسالة</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
let currentTemplateId = null;

function openSendTemplate(templateId) {
    testTemplate(templateId, true);
    setTimeout(() => {
        const phoneInput = document.getElementById('sendPhone');
        if (phoneInput) {
            phoneInput.focus();
        }
    }, 300);
}

function toggleSendChannelOptions() {
    const channel = document.getElementById('sendChannel')?.value || 'sms';
    document.getElementById('smsSenderTypeBox')?.classList.toggle('d-none', channel !== 'sms');
    document.getElementById('whatsappPurposeBox')?.classList.toggle('d-none', channel !== 'whatsapp');
}

function testTemplate(templateId, withSendControls = false) {
    currentTemplateId = templateId;

    fetch(`/admin/sms-templates/${templateId}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => response.json())
        .then(data => {
            const template = data.template;
            const variablesContainer = document.getElementById('variablesContainer');

            variablesContainer.innerHTML = '';
            if (template.variables && template.variables.length > 0) {
                template.variables.forEach(variable => {
                    const div = document.createElement('div');
                    div.className = 'mb-3';
                    div.innerHTML = `
                        <label class="form-label">${variable}:</label>
                        <input type="text" class="form-control" name="${variable}" placeholder="أدخل قيمة ${variable}">
                    `;
                    variablesContainer.appendChild(div);
                });
            } else {
                variablesContainer.innerHTML = '<p class="text-muted">لا توجد متغيرات في هذا القالب</p>';
            }

            document.getElementById('processedMessage').textContent = template.message;
            document.getElementById('sendControlsBox')?.classList.toggle('d-none', !withSendControls);
            document.getElementById('sendTemplateButton')?.classList.toggle('d-none', !withSendControls);

            const phoneInput = document.getElementById('sendPhone');
            if (phoneInput) {
                phoneInput.value = '';
            }

            const channelInput = document.getElementById('sendChannel');
            if (channelInput) {
                channelInput.value = template.channel === 'whatsapp' ? 'whatsapp' : 'sms';
            }
            toggleSendChannelOptions();

            new bootstrap.Modal(document.getElementById('testTemplateModal')).show();
        });
}

function processTemplate() {
    const form = document.getElementById('testTemplateForm');
    const formData = new FormData(form);
    const variables = {};

    for (let [key, value] of formData.entries()) {
        variables[key] = value;
    }

    fetch(`/admin/sms-templates/${currentTemplateId}/test`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            test_variables: variables
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('processedMessage').textContent = data.message;
        }
    });
}

function sendTemplate() {
    if (!currentTemplateId) {
        Swal.fire('خطأ', 'يرجى اختيار القالب أولاً', 'error');
        return;
    }

    const phone = (document.getElementById('sendPhone')?.value || '').trim();
    if (!phone) {
        Swal.fire('خطأ', 'يرجى إدخال رقم الجوال', 'error');
        return;
    }

    const form = document.getElementById('testTemplateForm');
    const formData = new FormData(form);
    const variables = {};

    for (let [key, value] of formData.entries()) {
        variables[key] = value;
    }

    fetch(`/admin/sms-templates/${currentTemplateId}/send`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            phone: phone,
            send_channel: document.getElementById('sendChannel')?.value || 'sms',
            sender_name_type: document.getElementById('senderNameType')?.value || 'advertising',
            whatsapp_purpose: document.getElementById('whatsappPurpose')?.value || 'verification',
            test_variables: variables
        })
    })
    .then(async response => {
        const data = await response.json();
        if (!response.ok) {
            throw data;
        }
        return data;
    })
    .then(data => {
        Swal.fire('نجح', data.message || 'تم إرسال الرسالة بنجاح', 'success');
    })
    .catch(error => {
        Swal.fire('خطأ', error.message || 'فشل في إرسال الرسالة', 'error');
    });
}

function deleteTemplate(templateId, templateTitle) {
    Swal.fire({
        title: "{{ helperTrans('buttons.Are You Sure') }}",
        text: `هل أنت متأكد من حذف القالب: ${templateTitle}؟`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'تأكيد',
        cancelButtonText: 'إلغاء',
    }).then((result) => {
        if (!result.isConfirmed) {
            return;
        }

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/admin/sms-templates/${templateId}`;
        form.innerHTML = `
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <input type="hidden" name="_method" value="DELETE">
        `;

        document.body.appendChild(form);
        form.submit();
    });
}
</script>
@endsection
