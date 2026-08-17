@extends('admin.layout.indexs.index')

@section('page-title')
إعدادات التنبيهات الآلية
@endsection

@section('content')
@if (@AdminGuardHasPerm(\App\PermKeys::NOTIFICATIONS_CONTROL))
    @php
        $oldSection = old('section');
        $alertDefinitions = $alertDefinitions ?? \App\Support\NotificationAlertSettings::definitions();
        $alertSettings = $alertSettings ?? \App\Support\NotificationAlertSettings::all();
        $customAlertSettings = $customAlertSettings ?? \App\Support\NotificationAlertSettings::custom();
        $alertChannelOptions = $alertChannelOptions ?? \App\Support\NotificationAlertSettings::channelOptions();
        $alertRecipientOptions = $alertRecipientOptions ?? \App\Support\NotificationAlertSettings::recipientOptions();
        $alertUnitOptions = $alertUnitOptions ?? \App\Support\NotificationAlertSettings::unitOptions();
        $alertTemplateOptions = $alertTemplateOptions ?? \App\Support\NotificationAlertSettings::templateOptions();
        $automationSettings = $automationSettings ?? \App\Support\AutomationSettings::all();

        if ($oldSection === 'alerts') {
            $alertSettings = array_replace_recursive($alertSettings, old('alerts', []));
            $customAlertSettings = old('custom_alerts', $customAlertSettings);
            $automationSettings = array_replace($automationSettings, old());
        }

        $weekdayOptions = [
            0 => 'الأحد',
            1 => 'الإثنين',
            2 => 'الثلاثاء',
            3 => 'الأربعاء',
            4 => 'الخميس',
            5 => 'الجمعة',
            6 => 'السبت',
        ];
    @endphp

    <div class="container-fluid" id="alert-rules-settings">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h4 class="mb-1">
                    <i class="fa fa-bell me-2"></i>
                    إعدادات التنبيهات الآلية
                </h4>
                <div class="text-muted small">تحكم في قواعد التنبيه، قنوات الإرسال، والمدد التي تعمل عليها مهام الجدولة.</div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('notifications.settings') }}" class="btn btn-outline-secondary">
                    <i class="fa fa-plug me-1"></i>
                    إعدادات القنوات
                </a>
                <a href="{{ route('admin.sms-templates.index') }}" class="btn btn-outline-info">
                    <i class="fa fa-file-text-o me-1"></i>
                    قوالب الرسائل
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="post" action="{{ route('notifications.settings.update') }}">
            @csrf
            <input type="hidden" name="section" value="alerts">

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-1">إعدادات الـ Cron والمهام الآلية</h5>
                    <div class="text-muted small">هذه القيم تتحكم في مواعيد تشغيل المهام المجدولة الموجودة في النظام.</div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label">مدة الضمان بالأيام</label>
                            <input type="number" name="warranty_period_days" class="form-control" min="1" max="3650"
                                value="{{ old('warranty_period_days', $automationSettings['warranty_period_days'] ?? 90) }}" required>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label">وقت تحديث حالة الضمان</label>
                            <input type="time" name="warranty_status_update_time" class="form-control"
                                value="{{ old('warranty_status_update_time', $automationSettings['warranty_status_update_time'] ?? '01:00') }}" required>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label">تهيئة الضمان كل</label>
                            <div class="input-group">
                                <input type="number" name="warranty_initialize_interval_hours" class="form-control" min="1" max="24"
                                    value="{{ old('warranty_initialize_interval_hours', $automationSettings['warranty_initialize_interval_hours'] ?? 6) }}" required>
                                <span class="input-group-text">ساعة</span>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label">تايمر الطلبات كل</label>
                            <div class="input-group">
                                <input type="number" name="orders_timer_interval_minutes" class="form-control" min="1" max="60"
                                    value="{{ old('orders_timer_interval_minutes', $automationSettings['orders_timer_interval_minutes'] ?? 30) }}" required>
                                <span class="input-group-text">دقيقة</span>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label">مهلة الطلب الافتراضية</label>
                            <div class="input-group">
                                <input type="number" name="order_expiration_hours" class="form-control" min="1" max="720"
                                    value="{{ old('order_expiration_hours', $automationSettings['order_expiration_hours'] ?? 24) }}" required>
                                <span class="input-group-text">ساعة</span>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label">مهلة الطلب الجديد</label>
                            <div class="input-group">
                                <input type="number" name="order_new_deadline_hours" class="form-control" min="1" max="720"
                                    value="{{ old('order_new_deadline_hours', $automationSettings['order_new_deadline_hours'] ?? 24) }}" required>
                                <span class="input-group-text">ساعة</span>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label">مهلة الطلب تحت الإجراء</label>
                            <div class="input-group">
                                <input type="number" name="order_under_process_deadline_hours" class="form-control" min="1" max="720"
                                    value="{{ old('order_under_process_deadline_hours', $automationSettings['order_under_process_deadline_hours'] ?? 48) }}" required>
                                <span class="input-group-text">ساعة</span>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label">حد الطلب العاجل</label>
                            <div class="input-group">
                                <input type="number" name="order_urgent_threshold_hours" class="form-control" min="1" max="720"
                                    value="{{ old('order_urgent_threshold_hours', $automationSettings['order_urgent_threshold_hours'] ?? 2) }}" required>
                                <span class="input-group-text">ساعة</span>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label">الطلبات المعلقة القديمة</label>
                            <div class="input-group">
                                <input type="number" name="old_pending_orders_days" class="form-control" min="1" max="3650"
                                    value="{{ old('old_pending_orders_days', $automationSettings['old_pending_orders_days'] ?? 30) }}" required>
                                <span class="input-group-text">يوم</span>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label">معالجة الطلبات المنتهية كل</label>
                            <div class="input-group">
                                <input type="number" name="orders_expired_handler_interval_minutes" class="form-control" min="1" max="60"
                                    value="{{ old('orders_expired_handler_interval_minutes', $automationSettings['orders_expired_handler_interval_minutes'] ?? 5) }}" required>
                                <span class="input-group-text">دقيقة</span>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label">إلغاء الطلبات تلقائيا كل</label>
                            <div class="input-group">
                                <input type="number" name="orders_auto_cancel_interval_minutes" class="form-control" min="1" max="60"
                                    value="{{ old('orders_auto_cancel_interval_minutes', $automationSettings['orders_auto_cancel_interval_minutes'] ?? 60) }}" required>
                                <span class="input-group-text">دقيقة</span>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label">تحديث توفر العاملات</label>
                            <input type="time" name="maids_availability_update_time" class="form-control"
                                value="{{ old('maids_availability_update_time', $automationSettings['maids_availability_update_time'] ?? '02:00') }}" required>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label">الاحتفاظ بالسجلات</label>
                            <div class="input-group">
                                <input type="number" name="log_clear_keep_days" class="form-control" min="1" max="365"
                                    value="{{ old('log_clear_keep_days', $automationSettings['log_clear_keep_days'] ?? 30) }}" required>
                                <span class="input-group-text">يوم</span>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label">تنظيف السجلات يوم</label>
                            <select name="log_clear_weekday" class="form-control" required>
                                @foreach ($weekdayOptions as $weekdayValue => $weekdayLabel)
                                    <option value="{{ $weekdayValue }}" {{ (int) old('log_clear_weekday', $automationSettings['log_clear_weekday'] ?? 0) === (int) $weekdayValue ? 'selected' : '' }}>
                                        {{ $weekdayLabel }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label">وقت تنظيف السجلات</label>
                            <input type="time" name="log_clear_time" class="form-control"
                                value="{{ old('log_clear_time', $automationSettings['log_clear_time'] ?? '03:00') }}" required>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label">تحسين قاعدة البيانات يوم</label>
                            <select name="database_optimize_weekday" class="form-control" required>
                                @foreach ($weekdayOptions as $weekdayValue => $weekdayLabel)
                                    <option value="{{ $weekdayValue }}" {{ (int) old('database_optimize_weekday', $automationSettings['database_optimize_weekday'] ?? 0) === (int) $weekdayValue ? 'selected' : '' }}>
                                        {{ $weekdayLabel }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label">وقت تحسين قاعدة البيانات</label>
                            <input type="time" name="database_optimize_time" class="form-control"
                                value="{{ old('database_optimize_time', $automationSettings['database_optimize_time'] ?? '04:00') }}" required>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-1">تنبيهات النظام الأساسية</h5>
                    <div class="text-muted small">حدد القناة، المستلم، والمدة قبل الحدث لكل جزء في النظام.</div>
                </div>
                <div class="card-body">
                    @include('admin.ingaz.notifications.partials.alert-rules-table', [
                        'rules' => $alertDefinitions,
                        'settings' => $alertSettings,
                        'inputPrefix' => 'alerts',
                        'canEditMeta' => false,
                    ])
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h5 class="mb-1">تنبيهات مخصصة</h5>
                        <div class="text-muted small">أضف تنبيها جديدا ليكون محفوظا ضمن إعدادات النظام وقابلا للاستخدام في أي مهمة لاحقة.</div>
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="addCustomAlert">
                        <i class="fa fa-plus me-1"></i>
                        إضافة تنبيه
                    </button>
                </div>
                <div class="card-body">
                    <div id="customAlertsContainer">
                        @forelse ($customAlertSettings as $index => $customRule)
                            @include('admin.ingaz.notifications.partials.custom-alert-row', [
                                'index' => $index,
                                'rule' => $customRule,
                            ])
                        @empty
                            <div class="text-muted small" id="emptyCustomAlertsHint">لا توجد تنبيهات مخصصة حتى الآن.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end mb-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-save me-1"></i>
                    حفظ إعدادات التنبيهات
                </button>
            </div>
        </form>
    </div>
@else
    @include('components.no_permissions')
@endif
@endsection

@section('js')
<script>
    const alertChannelOptions = @json($alertChannelOptions ?? []);
    const alertRecipientOptions = @json($alertRecipientOptions ?? []);
    const alertUnitOptions = @json($alertUnitOptions ?? []);
    const alertTemplateOptions = @json($alertTemplateOptions ?? []);

    function nextCustomAlertIndex() {
        let maxIndex = -1;

        $('#customAlertsContainer [data-custom-alert-row]').each(function () {
            maxIndex = Math.max(maxIndex, Number($(this).data('custom-alert-row')));
        });

        return maxIndex + 1;
    }

    function optionList(options, selectedValue) {
        return Object.entries(options).map(([value, label]) => {
            return `<option value="${value}" ${selectedValue === value ? 'selected' : ''}>${label}</option>`;
        }).join('');
    }

    function channelChecks(index) {
        return Object.entries(alertChannelOptions).map(([value, label], channelIndex) => {
            const id = `customAlert${index}Channel${channelIndex}`;
            return `
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="custom_alerts[${index}][channels][]" value="${value}" id="${id}" ${value === 'notification' ? 'checked' : ''}>
                    <label class="form-check-label" for="${id}">${label}</label>
                </div>
            `;
        }).join('');
    }

    function buildCustomAlertRow(index) {
        return `
            <div class="border rounded p-3 mb-3" data-custom-alert-row="${index}">
                <input type="hidden" name="custom_alerts[${index}][key]" value="custom_${Date.now()}_${index}">
                <div class="row g-3">
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label">اسم التنبيه</label>
                        <input type="text" name="custom_alerts[${index}][label]" class="form-control" required>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label">كود الحدث</label>
                        <input type="text" name="custom_alerts[${index}][event_key]" class="form-control" placeholder="مثال: custom_contract_review">
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label">رابط الصفحة</label>
                        <input type="text" name="custom_alerts[${index}][source_url]" class="form-control" placeholder="/admin/...">
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label class="form-label">المستلم</label>
                        <select name="custom_alerts[${index}][recipient]" class="form-control">${optionList(alertRecipientOptions, 'admin')}</select>
                    </div>
                    <div class="col-lg-1 col-md-6">
                        <label class="form-label">مفعل</label>
                        <input type="hidden" name="custom_alerts[${index}][enabled]" value="0">
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="custom_alerts[${index}][enabled]" value="1" checked>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label">الوصف</label>
                        <input type="text" name="custom_alerts[${index}][description]" class="form-control">
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label">قنوات الإرسال</label>
                        <div class="d-flex flex-wrap gap-2">${channelChecks(index)}</div>
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label class="form-label">التنبيه قبل</label>
                        <div class="input-group">
                            <input type="number" min="0" max="3650" name="custom_alerts[${index}][lead_time_value]" class="form-control" value="1">
                            <select name="custom_alerts[${index}][lead_time_unit]" class="form-control">${optionList(alertUnitOptions, 'days')}</select>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label class="form-label">إعادة التذكير</label>
                        <div class="input-group">
                            <input type="number" min="0" max="3650" name="custom_alerts[${index}][repeat_every_value]" class="form-control" value="0">
                            <select name="custom_alerts[${index}][repeat_every_unit]" class="form-control">${optionList(alertUnitOptions, 'days')}</select>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label">قالب الرسالة</label>
                        <select name="custom_alerts[${index}][template_id]" class="form-control">
                            <option value="">بدون قالب محدد</option>
                            ${optionList(alertTemplateOptions, '')}
                        </select>
                    </div>
                    <div class="col-lg-8 d-flex align-items-end justify-content-end">
                        <button type="button" class="btn btn-outline-danger btn-sm js-remove-custom-alert">
                            <i class="fa fa-trash"></i>
                            حذف
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    $(document).ready(function () {
        $('#addCustomAlert').on('click', function () {
            $('#emptyCustomAlertsHint').remove();
            $('#customAlertsContainer').append(buildCustomAlertRow(nextCustomAlertIndex()));
        });

        $(document).on('click', '.js-remove-custom-alert', function () {
            $(this).closest('[data-custom-alert-row]').remove();
        });
    });
</script>
@endsection
