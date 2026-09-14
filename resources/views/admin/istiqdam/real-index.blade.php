@extends('admin.layout.indexs.index')

@section('page-title', 'منصة الاستقدام')

@section('content')
<div class="page-body p-4 text-dark" dir="rtl">
    <div class="container-fluid istiqdam-dashboard">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <h3 class="mb-0">منصة الاستقدام</h3>
                    <span class="badge {{ $environment === 'production' ? 'bg-danger' : 'bg-warning text-dark' }} px-3 py-2">{{ $environment === 'production' ? 'الإنتاج' : 'بيئة ' . $environment }}</span>
                </div>
                <p class="text-muted mb-0">إدارة الربط مع تكامل: الباقات، العقود، تفاصيل العقد، الإلغاء، والتنزيل.</p>
            </div>
            <div class="d-flex gap-2">
                <a class="btn btn-outline-secondary" href="{{ route('istiqdam.settings') }}"><i class="fas fa-cog me-1"></i> إعدادات الربط</a>
                <button class="btn btn-outline-primary" id="connection-test" type="button" {{ $isConfigured ? '' : 'disabled' }}><i class="fas fa-plug me-1"></i> فحص الاتصال</button>
                <a class="btn btn-primary {{ $isConfigured ? '' : 'disabled' }}" href="{{ $isConfigured ? route('istiqdam.contracts.create') : '#' }}"><i class="fas fa-plus me-1"></i> إنشاء عقد</a>
            </div>
        </div>

        @if ($environment === 'production')
            <div class="alert alert-warning d-flex gap-2 align-items-center" role="alert"><i class="fas fa-triangle-exclamation"></i><span>أنت تعمل على بيئة الإنتاج؛ إنشاء العقد أو إلغاؤه يؤثر في بيانات المنصة الفعلية.</span></div>
        @endif
        <div id="connection-result" class="d-none mb-4" role="status"></div>
        @unless ($isConfigured)<div class="alert alert-info">لم تكتمل إعدادات الربط بعد. <a href="{{ route('istiqdam.settings') }}" class="alert-link">انتقل إلى صفحة الإعدادات</a> ثم احفظ البيانات قبل تنفيذ العمليات.</div>@endunless

        <div class="row g-3 mb-4">
            <div class="col-md-6 col-xl-3"><div class="card metric-card"><div class="card-body"><small class="text-muted">كل العقود المحلية</small><div class="metric-value">{{ number_format($contractSummary['total']) }}</div></div></div></div>
            <div class="col-md-6 col-xl-3"><div class="card metric-card"><div class="card-body"><small class="text-muted">بانتظار الموافقة</small><div class="metric-value text-info">{{ number_format($contractSummary['pending']) }}</div></div></div></div>
            <div class="col-md-6 col-xl-3"><div class="card metric-card"><div class="card-body"><small class="text-muted">العقود النشطة</small><div class="metric-value text-success">{{ number_format($contractSummary['active']) }}</div></div></div></div>
            <div class="col-md-6 col-xl-3"><div class="card metric-card"><div class="card-body"><small class="text-muted">العقود الملغاة</small><div class="metric-value text-danger">{{ number_format($contractSummary['cancelled']) }}</div></div></div></div>
        </div>

        <div class="row g-4">
            <div class="col-xl-8"><div class="card h-100"><div class="card-header bg-white d-flex justify-content-between align-items-center"><strong>العمليات المتاحة</strong><small class="text-muted">وفق وثائق تكامل المرفقة</small></div><div class="list-group list-group-flush">
                <button class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3" id="packages-action" type="button" {{ $isConfigured ? '' : 'disabled' }}><span><i class="fas fa-boxes-stacked text-primary me-2"></i><strong>الباقات</strong><small class="d-block text-muted me-4">جلب الباقات المتاحة واختيار باقة للعقد</small></span><i class="fas fa-chevron-left"></i></button>
                <button class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3" id="contract-action" type="button" {{ $isConfigured ? '' : 'disabled' }}><span><i class="fas fa-file-contract text-info me-2"></i><strong>استعلام عن عقد</strong><small class="d-block text-muted me-4">التفاصيل، تنزيل ملف العقد، أو إلغاء العقد</small></span><i class="fas fa-chevron-left"></i></button>
                <button class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3" id="permit-action" type="button" {{ $isConfigured ? '' : 'disabled' }}><span><i class="fas fa-id-card text-warning me-2"></i><strong>إسناد تصريح عمل</strong><small class="d-block text-muted me-4">ربط تصريح العمل والعاملة بعقد قائم في المنصة</small></span><i class="fas fa-chevron-left"></i></button>
                <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3 {{ $isConfigured ? '' : 'disabled' }}" href="{{ $isConfigured ? route('istiqdam.contracts.create') : '#' }}"><span><i class="fas fa-file-circle-plus text-success me-2"></i><strong>إنشاء عقد</strong><small class="d-block text-muted me-4">يرسل بيانات المستفيد والباقة والالتزامات إلى تكامل</small></span><i class="fas fa-chevron-left"></i></a>
            </div></div></div>
            <div class="col-xl-4"><div class="card h-100"><div class="card-header bg-white"><strong>حالة الإعداد</strong></div><div class="card-body"><dl class="row mb-0 small"><dt class="col-5 text-muted">البيئة</dt><dd class="col-7">{{ $environment }}</dd><dt class="col-5 text-muted">المهلة</dt><dd class="col-7">{{ $connectionTimeout }} ثانية</dd><dt class="col-5 text-muted">المصادقة</dt><dd class="col-7"><span id="auth-state" class="badge bg-secondary">لم تُفحص</span></dd></dl><button class="btn btn-sm btn-outline-secondary mt-3" id="diagnose-action" type="button">تشخيص آمن للإعداد</button></div></div></div>
        </div>

        <div class="card mt-4"><div class="card-header bg-white"><strong>أحدث العقود المحفوظة محلياً</strong></div><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>رقم المنصة</th><th>المستفيد</th><th>الحالة</th><th>آخر مزامنة</th><th></th></tr></thead><tbody>
            @forelse ($recentContracts as $contract)
                <tr><td>{{ $contract->istiqdam_contract_id }}</td><td>{{ $contract->beneficiary_name ?: $contract->beneficiary_id }}</td><td><span class="badge {{ $contract->status_badge_class }}">{{ $contract->status_text }}</span></td><td>{{ optional($contract->last_synced_at)->format('Y-m-d H:i') ?: '—' }}</td><td><button class="btn btn-sm btn-outline-primary inspect-contract" data-contract-id="{{ $contract->istiqdam_contract_id }}" type="button">عرض</button></td></tr>
            @empty
                <tr><td class="text-center text-muted py-4" colspan="5">لا توجد عقود محفوظة محلياً بعد.</td></tr>
            @endforelse
        </tbody></table></div></div>
        <div class="modal fade" id="istiqdam-modal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="modal-title">منصة الاستقدام</h5><button type="button" class="btn-close m-0" data-bs-dismiss="modal" aria-label="إغلاق"></button></div><div class="modal-body" id="modal-content"></div></div></div></div>
    </div>
</div>
@endsection

@section('js')
<script>
(() => {
    const modalEl = document.getElementById('istiqdam-modal'), modal = window.bootstrap ? new bootstrap.Modal(modalEl) : null, content = document.getElementById('modal-content'), title = document.getElementById('modal-title');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const escapeHtml = value => $('<div>').text(value ?? '—').html();
    const open = (heading, html) => { title.textContent = heading; content.innerHTML = html; modal?.show(); };
    const error = xhr => xhr.responseJSON?.message || xhr.responseJSON?.error || 'تعذر إتمام الطلب. تحقق من الاتصال والإعدادات.';
    const notice = (kind, message) => { const box = document.getElementById('connection-result'); box.className = `alert alert-${kind} mb-4`; box.textContent = message; };
    const contractUrl = id => '{{ route('istiqdam.contracts.show', ':contractId') }}'.replace(':contractId', encodeURIComponent(id));

    const supportReportText = (message, provider, steps) => {
        const lines = ['فشل الاتصال بمنصة تكامل', message || '', ''];
        const labels = {stage_label: 'المرحلة التي فشلت', environment: 'البيئة', endpoint: 'الرابط المستدعى', client_id: 'Client ID', http_status: 'رمز HTTP', provider_message: 'رسالة تكامل', raw_error: 'خطأ الشبكة الخام', server_ip: 'عنوان الخادم (SERVER_ADDR)', captured_at: 'وقت المحاولة'};
        Object.keys(labels).forEach(key => { if (provider?.[key] !== undefined && provider[key] !== null) lines.push(`${labels[key]}: ${provider[key]}`); });
        if (provider?.api_key_sent !== undefined) lines.push(`تم إرسال API KEY: ${provider.api_key_sent ? 'نعم' : 'لا'}`);
        Object.entries(provider?.gateway_trace || {}).forEach(([key, value]) => lines.push(`${key}: ${value}`));
        if (provider?.provider_body) lines.push('', 'رد البوابة الحرفي:', provider.provider_body);
        if (steps?.length) lines.push('', 'الخطوات المقترحة:', ...steps.map(step => `- ${step}`));
        return lines.join('\n');
    };

    const renderConnectionFailure = xhr => {
        const box = document.getElementById('connection-result');
        const payload = xhr.responseJSON || {};
        const provider = payload.provider_error || {};
        const steps = payload.next_steps || [];
        const rows = [
            ['المرحلة التي فشلت', provider.stage_label],
            ['رمز HTTP', provider.http_status],
            ['رسالة تكامل', provider.provider_message],
            ['خطأ الشبكة الخام', provider.raw_error],
            ['الرابط المستدعى', provider.endpoint],
            ['Client ID المستخدم', provider.client_id],
            ['تم إرسال API KEY', provider.api_key_sent === undefined ? null : (provider.api_key_sent ? 'نعم' : 'لا')],
            ['عنوان الخادم (SERVER_ADDR)', provider.server_ip],
            ['وقت المحاولة', provider.captured_at],
            ...Object.entries(provider.gateway_trace || {}),
        ].filter(([, value]) => value !== null && value !== undefined && value !== '')
         .map(([label, value]) => `<dt class="col-sm-4 text-muted">${escapeHtml(label)}</dt><dd class="col-sm-8" dir="auto">${escapeHtml(value)}</dd>`).join('');

        box.className = 'alert alert-danger mb-4';
        box.innerHTML = `<div class="d-flex justify-content-between align-items-start gap-2">
            <div><strong>${escapeHtml(error(xhr))}</strong></div>
            <button class="btn btn-sm btn-outline-dark flex-shrink-0" id="copy-support-report" type="button"><i class="fas fa-copy me-1"></i> نسخ التقرير للدعم</button>
        </div>
        ${steps.length ? `<ul class="small mt-2 mb-0">${steps.map(step => `<li>${escapeHtml(step)}</li>`).join('')}</ul>` : ''}
        ${rows ? `<hr><dl class="row small mb-0">${rows}</dl>` : ''}
        ${provider.provider_body ? `<div class="small mt-2"><strong>رد بوابة تكامل الحرفي:</strong><pre class="bg-white border rounded p-2 mt-1 mb-0 small" dir="ltr" style="max-height:220px;overflow:auto">${escapeHtml(provider.provider_body)}</pre></div>` : ''}`;

        box.querySelector('#copy-support-report')?.addEventListener('click', function () {
            navigator.clipboard?.writeText(supportReportText(error(xhr), provider, steps)).then(() => { this.innerHTML = '<i class="fas fa-check me-1"></i> تم النسخ'; }, () => { this.textContent = 'تعذّر النسخ'; });
        });
    };

    document.getElementById('connection-test').addEventListener('click', function () {
        this.disabled = true; notice('info', 'جاري اختبار المصادقة وجلب باقة واحدة…');
        $.get('{{ route('istiqdam.test.connection') }}').done(response => { notice('success', response.message); $('#auth-state').attr('class', 'badge bg-success').text('متصل'); }).fail(xhr => { renderConnectionFailure(xhr); $('#auth-state').attr('class', 'badge bg-danger').text('فشل'); }).always(() => this.disabled = false);
    });
    document.getElementById('packages-action').addEventListener('click', () => {
        open('الباقات المتاحة', '<div class="text-center py-4 text-muted"><span class="spinner-border spinner-border-sm"></span> جارِ جلب الباقات…</div>');
        $.get('{{ route('istiqdam.packages.data') }}', {length: 10, start: 0}).done(response => {
            const rows = (response.data || []).map(pkg => `<tr><td>${escapeHtml(pkg.id || pkg.packageId)}</td><td>${escapeHtml(pkg.nationality?.name?.ar || pkg.nationality)}</td><td>${escapeHtml(pkg.occupation?.name?.ar || pkg.occupation)}</td><td>${escapeHtml(pkg.duration?.value || pkg.duration)}</td><td>${escapeHtml(pkg.price?.gross || pkg.priceGross)}</td></tr>`).join('');
            content.innerHTML = rows ? `<p class="text-muted small">المعروض 10 باقات. تظهر الباقات كاملة عند إنشاء العقد.</p><div class="table-responsive"><table class="table table-sm"><thead><tr><th>المعرف</th><th>الجنسية</th><th>المهنة</th><th>المدة</th><th>السعر</th></tr></thead><tbody>${rows}</tbody></table></div><a class="btn btn-primary" href="{{ route('istiqdam.contracts.create') }}">إنشاء عقد بهذه الباقات</a>` : '<p class="text-muted mb-0">لا توجد باقات متاحة حالياً.</p>';
        }).fail(xhr => content.innerHTML = `<div class="alert alert-danger mb-0">${escapeHtml(error(xhr))}</div>`);
    });
    const inspect = id => {
        open('تفاصيل العقد', '<div class="text-center py-4 text-muted"><span class="spinner-border spinner-border-sm"></span> جارِ جلب التفاصيل…</div>');
        $.get(contractUrl(id)).done(response => content.innerHTML = `<pre class="small bg-light border rounded p-3 mb-3" dir="ltr">${escapeHtml(JSON.stringify(response.data, null, 2))}</pre><div class="d-flex flex-wrap gap-2"><a class="btn btn-outline-primary" href="{{ route('istiqdam.contracts.download', [':contractId', 'pdf']) }}" target="_blank">تنزيل PDF</a><button class="btn btn-outline-danger" id="cancel-contract" data-id="${escapeHtml(id)}">إلغاء العقد</button></div>`).fail(xhr => content.innerHTML = `<div class="alert alert-danger mb-0">${escapeHtml(error(xhr))}</div>`);
    };
    document.getElementById('contract-action').addEventListener('click', () => open('استعلام عن عقد', '<label class="form-label" for="contract-id">رقم العقد في المنصة</label><div class="input-group"><input class="form-control" id="contract-id" inputmode="numeric"><button class="btn btn-primary" id="find-contract">بحث</button></div>'));
    document.getElementById('permit-action').addEventListener('click', () => open('إسناد تصريح عمل', '<div class="row g-3"><div class="col-md-6"><label class="form-label">رقم العقد</label><input class="form-control" id="permit-contract-id"></div><div class="col-md-6"><label class="form-label">رقم تصريح العمل</label><input class="form-control" id="permit-number"></div><div class="col-md-6"><label class="form-label">هوية العاملة</label><input class="form-control" id="laborer-id"></div><div class="col-md-6"><label class="form-label">تاريخ الميلاد</label><input class="form-control" type="date" id="laborer-birthdate"></div></div><button class="btn btn-primary mt-3" id="assign-permit">إسناد التصريح</button>'));
    document.addEventListener('click', event => {
        const inspectButton = event.target.closest('.inspect-contract'); if (inspectButton) inspect(inspectButton.dataset.contractId);
        if (event.target.id === 'find-contract') { const id = document.getElementById('contract-id').value.trim(); if (id) inspect(id); }
        if (event.target.id === 'assign-permit') { const id = document.getElementById('permit-contract-id').value.trim(), number = document.getElementById('permit-number').value.trim(), laborerId = document.getElementById('laborer-id').value.trim(), birthdate = document.getElementById('laborer-birthdate').value; if (!id || !number || !laborerId || !birthdate) { notice('warning', 'أكمل بيانات تصريح العمل والعاملة أولاً.'); return; } event.target.disabled = true; $.ajax({url: '{{ route('istiqdam.work-permit.assign', ':contractId') }}'.replace(':contractId', encodeURIComponent(id)), method: 'POST', headers: {'X-CSRF-TOKEN': csrf}, data: {work_permit_number: number, laborer_id: laborerId, laborer_birthdate: birthdate}}).done(response => { notice('success', response.message); modal?.hide(); }).fail(xhr => { notice('danger', error(xhr)); event.target.disabled = false; }); }
        if (event.target.id === 'cancel-contract' && confirm('هل أنت متأكد من إلغاء هذا العقد في المنصة؟')) { const id = event.target.dataset.id; event.target.disabled = true; $.ajax({url: '{{ route('istiqdam.contracts.cancel', ':contractId') }}'.replace(':contractId', encodeURIComponent(id)), method: 'DELETE', headers: {'X-CSRF-TOKEN': csrf}}).done(response => { notice('success', response.message); modal?.hide(); }).fail(xhr => { notice('danger', error(xhr)); event.target.disabled = false; }); }
    });
    document.getElementById('diagnose-action').addEventListener('click', () => { open('تشخيص الإعداد', '<div class="text-center py-4 text-muted"><span class="spinner-border spinner-border-sm"></span> جارِ التشخيص…</div>'); $.get('{{ route('istiqdam.diagnose') }}').done(response => { const data = response.diagnosis; content.innerHTML = `<dl class="row mb-0"><dt class="col-sm-5">البيئة</dt><dd class="col-sm-7">${escapeHtml(data.environment)}</dd><dt class="col-sm-5">بيانات الاعتماد</dt><dd class="col-sm-7">${data.credentials_configured ? 'مهيأة' : 'ناقصة'}</dd><dt class="col-sm-5">اختبار الاتصال</dt><dd class="col-sm-7">${data.connection_test?.success ? 'نجح' : 'فشل'}</dd></dl>`; }).fail(xhr => content.innerHTML = `<div class="alert alert-danger mb-0">${escapeHtml(error(xhr))}</div>`); });
})();
</script>
<style>.istiqdam-dashboard .metric-card{border:0;box-shadow:0 2px 10px rgba(0,0,0,.06)}.istiqdam-dashboard .metric-value{font-size:2rem;font-weight:700;margin-top:.25rem}.istiqdam-dashboard .list-group-item{border-left:0;border-right:0}.istiqdam-dashboard .list-group-item:first-child{border-top:0}</style>
@endsection
