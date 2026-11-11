@extends('admin.layout.indexs.index')

@section('page-title')
إرسال الرسائل النصية
@endsection

@section('content')
@if (\App\Support\MessagingPermissions::allows(\App\Support\MessagingPermissions::SEND))
    <div class="container-fluid">
        @include('admin.ingaz.messaging._nav')
        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h4 class="mb-0">
                    <i class="fa fa-sms me-2"></i>
                    إرسال رسالة نصية (SMS)
                </h4>
            </div>
            <div class="card-body">
                <div class="alert alert-warning mb-4">
                    <strong>تنبيه:</strong>
                    الإرسال الجماعي يتطلب تأكيدًا إضافيًا ويخضع لحد أقصى
                    <strong>{{ $smsConfig['bulk_limit'] }}</strong> رسالة لكل دفعة.
                </div>

                <form id="smsDispatchForm" class="row g-3 from-submit-global" method="post" action="{{ route('notifications.store') }}">
                    @csrf
                    <input type="hidden" name="type" value="sms">

                    <div class="col-md-6">
                        <label for="sms_template_id" class="form-label">قالب الرسالة النصية</label>
                        <select name="sms_template_id" id="sms_template_id" class="form-control" required>
                            <option value="none" selected>بدون قالب</option>
                            <option value="">اختر القالب</option>
                            @foreach ($smsTemplates as $template)
                                <option value="{{ $template->id }}">{{ $template->title }} ({{ $template->name }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="sender_name_type" class="form-label">اسم المرسل</label>
                        <select name="sender_name_type" id="sender_name_type" class="form-control" required>
                            <option value="advertising" selected>
                                إعلاني ({{ $smsConfig['advertising_sender'] ?: ($smsConfig['sender'] ?: 'غير محدد') }})
                            </option>
                            <option value="verification">
                                تحقق ({{ $smsConfig['verification_sender'] ?: ($smsConfig['sender'] ?: 'غير محدد') }})
                            </option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="all_or_some" class="form-label">نطاق الإرسال</label>
                        <select name="all_or_some" id="all_or_some" class="form-control" required>
                            <option value="some" selected>عملاء محددون</option>
                            <option value="all">كل العملاء (يتطلب تأكيد)</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <div class="card border">
                            <div class="card-body">
                                <h6 class="mb-3">متغيرات القالب</h6>
                                <div id="templateVariablesRow" class="row g-3">
                                    <div class="col-12 text-muted">اختر قالبًا أولًا لعرض المتغيرات المطلوبة.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <label for="message_override" class="form-label">نص الرسالة النهائي</label>
                        <textarea name="message_override" id="message_override" class="form-control" rows="5" required
                            placeholder="اكتب نص الرسالة، أو اختر قالبًا ليتم توليد النص تلقائيًا ثم تعديله قبل الإرسال."></textarea>
                    </div>

                    <div class="col-12">
                        <div class="d-flex align-items-center justify-content-between bg-light rounded p-3">
                            <div>
                                <strong>العدد المتوقع للإرسال:</strong>
                                <span id="recipientCount" class="badge bg-primary ms-2">0</span>
                            </div>
                            <small class="text-muted">إجمالي العملاء المتاحين للإرسال: {{ $totalEligibleUsers }}</small>
                        </div>
                    </div>

                    <div class="col-12" id="someRecipientsBox">
                        <label for="usersSelect" class="form-label">اختر العملاء</label>
                        <select id="usersSelect" name="user_id[]" class="form-control js-select-users" multiple>
                            @foreach ($appUsers as $user)
                                <option value="{{ $user->id }}" data-phone="{{ $user->phone }}">{{ $user->name }} - {{ $user->phone }}</option>
                            @endforeach
                        </select>
                        <div class="mt-3">
                            <label for="external_recipient_phones" class="form-label">أرقام عملاء خارج النظام</label>
                            <textarea id="external_recipient_phones" name="external_recipient_phones" class="form-control" rows="4"
                                placeholder="اكتب رقمًا في كل سطر، أو افصل الأرقام بفاصلة"></textarea>
                            <small class="text-muted">يمكن الجمع بين عملاء من النظام وأرقام خارجية في نفس الإرسال.</small>
                        </div>
                    </div>

                    <div class="col-12 d-none" id="allRecipientsBox">
                        <div class="border border-danger rounded p-3">
                            <h6 class="text-danger mb-2">
                                <i class="fa fa-triangle-exclamation me-1"></i>
                                حماية الإرسال الجماعي
                            </h6>
                            <p class="mb-3 text-muted">
                                سيتم الإرسال لكل العملاء الذين لديهم رقم جوال صالح. أكمل التأكيد أدناه قبل التنفيذ.
                            </p>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="confirm_bulk_send" id="confirm_bulk_send" value="1">
                                <label class="form-check-label" for="confirm_bulk_send">أؤكد أنني أريد الإرسال لكل العملاء</label>
                            </div>
                            <div>
                                <label for="bulk_confirmation_text" class="form-label">
                                    اكتب كلمة التأكيد: <code>{{ $smsConfig['bulk_keyword'] }}</code>
                                </label>
                                <input type="text" name="bulk_confirmation_text" id="bulk_confirmation_text" class="form-control"
                                    autocomplete="off" placeholder="اكتب كلمة التأكيد كما هي">
                            </div>
                        </div>
                    </div>

                    <div class="col-12 d-flex gap-2">
                        <button class="btn btn-primary" type="submit">
                            <i class="fa fa-paper-plane me-1"></i>
                            إرسال الرسائل النصية
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@else
    @include('components.no_permissions')
@endif
@endsection

@section('js')
@php
    $templatesById = $smsTemplates->mapWithKeys(function ($template) {
        return [
            $template->id => [
                'message' => $template->message,
                'variables' => $template->variables ?: [],
            ],
        ];
    })->toArray();
@endphp
<script>
    const templatesById = @json($templatesById);
    const totalEligibleUsers = {{ (int) $totalEligibleUsers }};

    function escapeRegex(text) {
        return text.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    function getSelectedTemplate() {
        const templateId = $('#sms_template_id').val();
        return templateId && templatesById[templateId] ? templatesById[templateId] : null;
    }

    function renderTemplateVariables() {
        const template = getSelectedTemplate();
        const templateId = $('#sms_template_id').val();
        const row = $('#templateVariablesRow');
        row.empty();

        if (!template) {
            row.append(
                templateId === 'none'
                    ? '<div class="col-12 text-muted">الإرسال بدون قالب؛ اكتب نص الرسالة مباشرة.</div>'
                    : '<div class="col-12 text-muted">اختر قالبًا أولًا لعرض المتغيرات المطلوبة.</div>'
            );

            if (templateId !== 'none') {
                $('#message_override').val('');
            }

            return;
        }

        const variables = Array.isArray(template.variables) ? template.variables : [];
        if (!variables.length) {
            row.append('<div class="col-12 text-muted">هذا القالب لا يحتوي على متغيرات.</div>');
        } else {
            variables.forEach((variable) => {
                row.append(`
                    <div class="col-md-4">
                        <label class="form-label">${variable}</label>
                        <input type="text" class="form-control template-variable-input"
                            name="template_variables[${variable}]"
                            data-variable="${variable}" placeholder="قيمة ${variable}">
                    </div>
                `);
            });
        }

        updateGeneratedMessage();
    }

    function updateGeneratedMessage() {
        const template = getSelectedTemplate();
        if (!template) {
            return;
        }

        let message = template.message || '';
        $('.template-variable-input').each(function() {
            const variableName = $(this).data('variable');
            const value = $(this).val() || '';
            const pattern = new RegExp('\\{' + escapeRegex(String(variableName)) + '\\}', 'g');
            message = message.replace(pattern, value);
        });

        $('#message_override').val(message.trim());
    }

    function normalizeSmsPhone(phone) {
        let digits = String(phone || '').replace(/\D+/g, '');

        if (digits.startsWith('00')) {
            digits = digits.substring(2);
        }

        if (digits.length === 10 && digits.startsWith('05')) {
            return '966' + digits.substring(1);
        }

        if (digits.length === 9 && digits.startsWith('5')) {
            return '966' + digits;
        }

        return digits;
    }

    function validSmsPhone(phone) {
        return /^\d{8,15}$/.test(phone);
    }

    function externalRecipientPhones() {
        const phones = ($('#external_recipient_phones').val() || '')
            .split(/[\r\n,;]+/)
            .map((phone) => normalizeSmsPhone(phone.trim()))
            .filter(validSmsPhone);

        return [...new Set(phones)];
    }

    function selectedUserPhones() {
        return ($('#usersSelect option:selected').map(function () {
            return normalizeSmsPhone($(this).data('phone') || '');
        }).get() || []).filter(validSmsPhone);
    }

    function updateRecipientCount() {
        const mode = $('#all_or_some').val();
        let count = 0;

        if (mode === 'all') {
            count = totalEligibleUsers;
        } else {
            const internalPhones = selectedUserPhones();
            const externalPhones = externalRecipientPhones()
                .filter((phone) => !internalPhones.includes(phone));

            count = ($('#usersSelect').val() || []).length + externalPhones.length;
        }

        $('#recipientCount').text(count);
    }

    function toggleRecipientMode() {
        const isAll = $('#all_or_some').val() === 'all';
        $('#someRecipientsBox').toggleClass('d-none', isAll);
        $('#allRecipientsBox').toggleClass('d-none', !isAll);

        $('#usersSelect').prop('required', false);
        $('#confirm_bulk_send').prop('required', isAll);
        $('#bulk_confirmation_text').prop('required', isAll);

        if (!isAll) {
            $('#confirm_bulk_send').prop('checked', false);
            $('#bulk_confirmation_text').val('');
        }

        updateRecipientCount();
    }

    $(document).ready(function () {
        $('.js-select-users').select2({
            placeholder: 'اختر العملاء',
            allowClear: true
        });

        $('#sms_template_id').on('change', renderTemplateVariables);
        $(document).on('input', '.template-variable-input', updateGeneratedMessage);
        $('#usersSelect, #all_or_some').on('change', function () {
            toggleRecipientMode();
            updateRecipientCount();
        });
        $('#external_recipient_phones').on('input', updateRecipientCount);

        $('#smsDispatchForm').on('submit', function (event) {
            if ($('#all_or_some').val() !== 'all') {
                return true;
            }

            const recipientCount = Number($('#recipientCount').text() || 0);
            const confirmed = window.confirm(`سيتم إرسال الرسالة إلى ${recipientCount} عميل. هل تريد المتابعة؟`);
            if (!confirmed) {
                event.preventDefault();
                event.stopPropagation();
                return false;
            }

            return true;
        });

        renderTemplateVariables();
        toggleRecipientMode();
        updateRecipientCount();
    });
</script>
@endsection
