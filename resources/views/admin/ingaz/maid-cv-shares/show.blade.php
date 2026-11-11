@extends('admin.layout.indexs.index')
@section('page-title', 'مشاركة السير عبر واتساب')
@section('content')
@php
    $labels = ['draft' => 'مسودة — لم ترسل', 'queued' => 'بانتظار التنفيذ', 'processing' => 'جارٍ الإرسال',
        'accepted' => 'قبل المزود الإرسال', 'partial' => 'إرسال جزئي', 'failed' => 'فشل الإرسال',
        'needs_review' => 'تحتاج مراجعة النتيجة مع المزود', 'pending' => 'بانتظار الإرسال', 'sending' => 'جارٍ الإرسال',
        'unknown' => 'نتيجة غير مؤكدة — راجع منصة واتساب', 'sent' => 'أرسلت', 'delivered' => 'تم التسليم', 'read' => 'تمت القراءة'];
    $colors = ['draft' => 'secondary', 'queued' => 'info', 'processing' => 'info', 'pending' => 'secondary', 'sending' => 'info',
        'accepted' => 'success', 'sent' => 'success', 'delivered' => 'success', 'read' => 'primary',
        'partial' => 'warning', 'needs_review' => 'warning', 'unknown' => 'warning', 'failed' => 'danger'];
    $fieldLabels = \App\Services\ingaz\MaidCvShareService::FIELDS;
    $files = $share->messages->whereNotNull('maid_id')->values();
    $step = $share->status === 'draft' ? 2 : 3;
@endphp
<style>
    .cvs-page .step{display:flex;align-items:center;gap:.5rem;color:#9aa5b1;font-weight:600}
    .cvs-page .step .num{width:30px;height:30px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;background:#e9ecef;color:#6c757d}
    .cvs-page .step.done .num{background:#25D366;color:#fff}.cvs-page .step.active{color:#128C7E}
    .cvs-page .step.active .num{background:#128C7E;color:#fff;box-shadow:0 0 0 4px rgba(18,140,126,.15)}
    .cvs-page .step-line{flex:1;height:2px;background:#e9ecef;min-width:24px}
    .cvs-page .info-tile{border:1px solid #eef0f2;border-radius:.75rem;padding:.75rem 1rem;background:#fff;height:100%}
    .cvs-page .info-tile .lbl{font-size:.78rem;color:#8a94a0}.cvs-page .info-tile .val{font-weight:700}
    .cvs-page .cv-card{border:1px solid #e6e9ec;border-radius:.85rem;transition:opacity .2s,border-color .2s}
    .cvs-page .cv-card.excluded{opacity:.5;border-style:dashed}
    .cvs-page .cv-card .cv-body{display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:.65rem}
    .cvs-page .field-chip{border:1px solid #dfe3e7;border-radius:2rem;padding:.35rem .9rem;cursor:pointer;user-select:none;margin:0}
    .cvs-page .field-chip:has(input:checked){background:#e7f7ef;border-color:#25D366;color:#0f6b4f}
    .cvs-page .field-chip input{margin-inline-end:.35rem}
    .cvs-page .wa-phone{border-radius:1.25rem;overflow:hidden;border:1px solid #d9dde1;box-shadow:0 6px 24px rgba(0,0,0,.06)}
    .cvs-page .wa-head{background:#075E54;color:#fff;padding:.7rem 1rem;display:flex;align-items:center;gap:.6rem}
    .cvs-page .wa-head .avatar{width:36px;height:36px;border-radius:50%;background:#ffffff33;display:flex;align-items:center;justify-content:center}
    .cvs-page .wa-body{background:#efeae2;padding:1rem;max-height:62vh;overflow-y:auto;display:flex;flex-direction:column;gap:.5rem}
    .cvs-page .wa-bubble{background:#d9fdd3;border-radius:.6rem;padding:.5rem .7rem;max-width:92%;align-self:flex-start;box-shadow:0 1px .5px rgba(0,0,0,.13);white-space:pre-wrap;word-break:break-word;font-size:.9rem;line-height:1.55}
    .cvs-page .wa-doc{background:#d9fdd3;border-radius:.6rem;padding:.45rem;max-width:92%;align-self:flex-start;box-shadow:0 1px .5px rgba(0,0,0,.13);min-width:240px}
    .cvs-page .wa-doc .inner{background:#cbeec4;border-radius:.45rem;padding:.55rem .7rem;display:flex;align-items:center;gap:.6rem}
    .cvs-page .wa-doc .pdf{background:#e53935;color:#fff;font-size:.65rem;font-weight:800;border-radius:.25rem;padding:.35rem .3rem}
    .cvs-page .sticky-preview{position:sticky;top:80px}
    .cvs-page .action-bar{position:sticky;bottom:0;z-index:5;background:#fff;border-top:1px solid #e9ecef;box-shadow:0 -4px 16px rgba(0,0,0,.05)}
</style>

<div class="container-fluid py-4 cvs-page" dir="rtl">
    {{-- العنوان والحالة --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h3 class="mb-0"><i class="fab fa-whatsapp text-success"></i> مشاركة السير #{{ $share->id }}</h3>
        <span class="badge fs-6 bg-{{ $colors[$share->status] ?? 'secondary' }}">{{ $labels[$share->status] ?? $share->status }}</span>
    </div>

    {{-- خطوات العملية --}}
    <div class="card card-body mb-3 py-3">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="step done"><span class="num"><i class="fas fa-check"></i></span> اختيار السير والعميل</span>
            <span class="step-line"></span>
            <span class="step {{ $step > 2 ? 'done' : 'active' }}"><span class="num">@if ($step > 2)<i class="fas fa-check"></i>@else 2 @endif</span> مراجعة وتعديل الرسالة</span>
            <span class="step-line"></span>
            <span class="step {{ $step === 3 ? 'active' : '' }}"><span class="num">3</span> الإرسال والمتابعة</span>
        </div>
    </div>

    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if ($errors->any())
        <div class="alert alert-danger">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
    @endif

    {{-- بيانات المشاركة --}}
    <div class="row g-2 mb-3">
        <div class="col-6 col-lg-3"><div class="info-tile"><div class="lbl"><i class="fas fa-user"></i> العميل</div><div class="val">{{ $share->client_name }}</div></div></div>
        <div class="col-6 col-lg-3"><div class="info-tile"><div class="lbl"><i class="fab fa-whatsapp"></i> رقم واتساب</div><div class="val"><bdi>{{ $share->phone }}</bdi></div></div></div>
        <div class="col-6 col-lg-3"><div class="info-tile"><div class="lbl"><i class="fas fa-user-tie"></i> الموظف</div><div class="val">{{ $share->admin_name }}</div></div></div>
        <div class="col-6 col-lg-3"><div class="info-tile"><div class="lbl"><i class="far fa-clock"></i> تاريخ الطلب / التأكيد</div>
            <div class="val small">{{ $share->created_at }} — {{ $share->confirmed_at ?: 'لم يؤكد بعد' }}</div></div></div>
    </div>

    @if ($editable)
        {{-- ===================== شاشة المراجعة والتعديل قبل الإرسال ===================== --}}
        <form method="POST" action="{{ route('maid-cv-shares.update', $share->id) }}" id="cvShareReview" novalidate>
            @csrf @method('PUT')
            <div class="row g-3">
                <div class="col-xl-7">
                    {{-- الحقول الظاهرة --}}
                    <div class="card mb-3"><div class="card-body">
                        <h6 class="fw-bold mb-1"><i class="fas fa-sliders-h text-success"></i> البيانات التي تظهر للعميل</h6>
                        <p class="small text-muted mb-2">اختر الحقول التي تضاف لكل سيرة في الرسالة. الحقول الفارغة لا تظهر.</p>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach ($fieldLabels as $key => $label)
                                <label class="field-chip"><input type="checkbox" class="form-check-input js-field" name="fields[]" value="{{ $key }}"
                                    @checked(in_array($key, (array) $options['fields'], true))>{{ $label }}</label>
                            @endforeach
                        </div>
                    </div></div>

                    {{-- نص البداية والختام --}}
                    <div class="card mb-3"><div class="card-body">
                        <h6 class="fw-bold mb-2"><i class="fas fa-comment-dots text-success"></i> مقدمة وخاتمة الرسالة <span class="small text-muted fw-normal">(اختياري)</span></h6>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label small text-muted">نص البداية</label>
                                <textarea name="intro" rows="2" maxlength="1000" class="form-control js-live" placeholder="مثال: مرحبًا {{ $share->client_name }}، هذه السير المقترحة لك:">{{ old('intro', $options['intro']) }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-muted">نص الختام</label>
                                <textarea name="outro" rows="2" maxlength="1000" class="form-control js-live" placeholder="مثال: للحجز أو الاستفسار تواصل معنا على هذا الرقم.">{{ old('outro', $options['outro']) }}</textarea>
                            </div>
                        </div>
                    </div></div>

                    {{-- السير --}}
                    <h6 class="fw-bold mb-2 d-flex justify-content-between">
                        <span><i class="fas fa-id-card text-success"></i> السير المرسلة وقيمها</span>
                        <span class="badge bg-success-subtle text-success border border-success"><span class="js-count">{{ $files->count() }}</span> / {{ $files->count() }}</span>
                    </h6>
                    @foreach ($files as $item)
                        @php($s = (array) $item->snapshot)
                        @php($rental = ($s['cv_type'] ?? 'recruitment') === 'rental')
                        <div class="card cv-card mb-3 js-cv" data-id="{{ $item->id }}" data-type="{{ $s['cv_type'] ?? 'recruitment' }}" data-filename="{{ $item->filename }}">
                            <div class="card-body">
                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge {{ $rental ? 'bg-info' : 'bg-primary' }}">{{ $rental ? 'إيجار' : 'استقدام' }}</span>
                                        <strong class="js-title">{{ $s['name'] ?? '' }}</strong>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-secondary js-pdf" data-src="{{ route('maid-cv-shares.download', ['message' => $item->id, 'inline' => 1]) }}" data-title="{{ $item->filename }}">
                                            <i class="far fa-file-pdf"></i> معاينة الملف</button>
                                        <div class="form-check form-switch m-0">
                                            <input type="hidden" name="items[{{ $item->id }}][include]" value="0">
                                            <input class="form-check-input js-include" type="checkbox" role="switch" id="inc{{ $item->id }}" name="items[{{ $item->id }}][include]" value="1" checked>
                                            <label class="form-check-label small" for="inc{{ $item->id }}">ضمن الإرسال</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="cv-body">
                                    @foreach (['name' => 'اسم العاملة', 'reference' => 'الرقم المرجعي', 'nationality' => 'الجنسية', 'job' => 'المهنة', 'religion' => 'الديانة', 'age' => 'العمر', 'experience' => 'الخبرة', 'salary' => 'الراتب'] as $key => $label)
                                        @continue($key === 'salary' && $rental)
                                        <div>
                                            <label class="form-label small text-muted mb-1">{{ $label }}</label>
                                            <input type="text" class="form-control form-control-sm js-live" data-key="{{ $key }}" name="items[{{ $item->id }}][{{ $key }}]"
                                                value="{{ old("items.{$item->id}.{$key}", $s[$key] ?? '') }}" maxlength="150" @if ($key === 'name') required @endif
                                                @if ($key === 'salary') placeholder="مثال: 1500" @endif>
                                        </div>
                                    @endforeach
                                </div>
                                <label class="form-label small text-muted mt-2 mb-1">ملاحظة خاصة بهذه السيرة</label>
                                <input type="text" class="form-control form-control-sm js-live" data-key="note" name="items[{{ $item->id }}][note]" maxlength="500"
                                    value="{{ old("items.{$item->id}.note", $s['note'] ?? '') }}" placeholder="مثال: متاحة للوصول خلال 30 يومًا">
                            </div>
                        </div>
                    @endforeach

                    {{-- النص النهائي اليدوي --}}
                    <div class="card mb-3"><div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold mb-0"><i class="fas fa-pen text-success"></i> نص الرسالة النهائي</h6>
                            <div class="form-check form-switch m-0">
                                <input type="hidden" name="custom" value="0">
                                <input class="form-check-input" type="checkbox" role="switch" id="customToggle" name="custom" value="1" @checked($options['custom'])>
                                <label class="form-check-label small" for="customToggle">تعديل النص يدويًا</label>
                            </div>
                        </div>
                        <p class="small text-muted mb-2 js-custom-hint">يُبنى النص تلقائيًا من القيم أعلاه. فعّل التعديل اليدوي لكتابة النص بحرية (لن يتحدث تلقائيًا بعدها).</p>
                        <textarea name="message" id="customMessage" rows="8" class="form-control" maxlength="{{ \App\Services\ingaz\MaidCvShareService::MAX_LENGTH }}" @disabled(!$options['custom'])>{{ old('message', $share->message) }}</textarea>
                        <button type="button" class="btn btn-link btn-sm px-0 js-reset">إعادة بناء النص من القيم</button>
                    </div></div>
                </div>

                {{-- معاينة واتساب --}}
                <div class="col-xl-5">
                    <div class="sticky-preview">
                        <div class="wa-phone">
                            <div class="wa-head">
                                <span class="avatar"><i class="fas fa-user"></i></span>
                                <div><div class="fw-bold">{{ $share->client_name }}</div><div class="small opacity-75"><bdi>{{ $share->phone }}</bdi></div></div>
                            </div>
                            <div class="wa-body" id="waPreview"></div>
                        </div>
                        <div class="d-flex justify-content-between small mt-2 px-1">
                            <span class="text-muted">هذا ما سيصل للعميل بالترتيب: الرسالة ثم ملفات السير.</span>
                            <span id="charCount" class="fw-bold"></span>
                        </div>
                        <div class="alert alert-light border small mt-2 mb-0">
                            <i class="fas fa-info-circle text-primary"></i> تعديل القيم يغيّر نص الرسالة فقط؛ ملف PDF يُرسل كما هو في السيرة.
                            الإرسال عبر Meta أو Widers يتطلب نافذة محادثة متاحة مع العميل.
                        </div>
                    </div>
                </div>
            </div>

            <div class="action-bar mt-3 p-3 rounded-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <a class="btn btn-outline-secondary" href="{{ route('maid-cv-shares.index', ['client_id' => $share->client_id]) }}"><i class="fas fa-history"></i> مشاركات العميل</a>
                <div class="d-flex gap-2">
                    <button type="submit" name="send" value="0" class="btn btn-outline-success js-submit"><i class="far fa-save"></i> حفظ التعديلات</button>
                    <button type="submit" name="send" value="1" class="btn btn-success px-4 js-submit js-send"><i class="fab fa-whatsapp"></i> تأكيد وإرسال للعميل</button>
                </div>
            </div>
        </form>

        {{-- معاينة ملف PDF --}}
        <div class="modal fade" id="cvPdfModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered"><div class="modal-content">
                <div class="modal-header"><h6 class="modal-title" id="cvPdfTitle"></h6><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button></div>
                <div class="modal-body p-0"><iframe id="cvPdfFrame" title="معاينة السيرة" style="width:100%;height:78vh;border:0"></iframe></div>
            </div></div>
        </div>

        <script>
        (function () {
            const form = document.getElementById('cvShareReview');
            const labels = @json($fieldLabels);
            const order = Object.keys(labels);
            const max = {{ \App\Services\ingaz\MaidCvShareService::MAX_LENGTH }};
            const preview = document.getElementById('waPreview'), counter = document.getElementById('charCount');
            const custom = document.getElementById('customToggle'), customText = document.getElementById('customMessage');
            const esc = t => t.replace(/[&<>"']/g, c => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]));
            const bold = t => esc(t).replace(/\*([^*\n]+)\*/g, '<strong>$1</strong>');

            // نفس منطق MaidCvShareService::message في الخادم.
            const valueOf = (cv, s, key) => {
                const rental = cv.dataset.type === 'rental';
                if (key === 'cv_type') return rental ? 'إيجار' : 'استقدام';
                let v = (s[key] || '').trim();
                if (key === 'age' && /^[\d.]+$/.test(v) && Number(v) <= 0) return '';
                if (key === 'salary') {
                    if (rental) return '';
                    if (/^\d+(\.\d+)?$/.test(v)) v = Math.round(Number(v)).toLocaleString('en-US') + ' ريال';
                }
                return v;
            };
            const build = () => {
                const fields = order.filter(k => form.querySelector('.js-field[value="' + k + '"]')?.checked);
                const blocks = included().map(cv => {
                    const s = {};
                    cv.querySelectorAll('[data-key]').forEach(i => s[i.dataset.key] = i.value);
                    const lines = ['السيرة الذاتية للعاملة ' + (s.name || '').trim()];
                    fields.forEach(k => { const v = valueOf(cv, s, k); if (v) lines.push(labels[k] + ': ' + v); });
                    if ((s.note || '').trim()) lines.push(s.note.trim());
                    return lines.join('\n');
                });
                return [form.intro.value.trim(), ...blocks, form.outro.value.trim()].filter(Boolean).join('\n\n');
            };
            const included = () => [...form.querySelectorAll('.js-cv')].filter(cv => cv.querySelector('.js-include').checked);

            const render = () => {
                form.querySelectorAll('.js-cv').forEach(cv => {
                    cv.classList.toggle('excluded', !cv.querySelector('.js-include').checked);
                    cv.querySelector('.js-title').textContent = cv.querySelector('[data-key="name"]').value;
                });
                if (!custom.checked) customText.value = build();
                const text = customText.value;
                preview.innerHTML = '';
                const bubble = document.createElement('div');
                bubble.className = 'wa-bubble';
                bubble.innerHTML = bold(text) || '<span class="text-muted">— الرسالة فارغة —</span>';
                preview.appendChild(bubble);
                included().forEach(cv => {
                    const doc = document.createElement('div');
                    doc.className = 'wa-doc';
                    doc.innerHTML = '<div class="inner"><span class="pdf">PDF</span><span class="small fw-semibold text-truncate"></span></div>';
                    doc.querySelector('.text-truncate').textContent = cv.dataset.filename;
                    preview.appendChild(doc);
                });
                form.querySelector('.js-count').textContent = included().length;
                counter.textContent = text.length.toLocaleString('en-US') + ' / ' + max.toLocaleString('en-US') + ' حرف';
                counter.className = 'fw-bold ' + (text.length > max ? 'text-danger' : 'text-muted');
            };

            form.addEventListener('input', render);
            form.addEventListener('change', render);
            custom.addEventListener('change', () => { customText.disabled = !custom.checked; render(); if (custom.checked) customText.focus(); });
            form.querySelector('.js-reset').addEventListener('click', () => { custom.checked = false; customText.disabled = true; render(); });

            const submitForm = button => {
                // الأزرار المعطلة لا تُرسل قيمتها، فنضيف قيمة send كحقل مخفي.
                form.querySelectorAll('.js-submit').forEach(b => b.disabled = true);
                form.appendChild(Object.assign(document.createElement('input'), {type: 'hidden', name: 'send', value: button.value}));
                button.innerHTML = '<span class="spinner-border spinner-border-sm"></span> '
                    + (button.value === '1' ? 'جارٍ الإرسال…' : 'جارٍ الحفظ…');
                form.submit();
            };

            form.addEventListener('submit', async event => {
                event.preventDefault();
                const button = event.submitter || form.querySelector('.js-submit');
                const problems = [];
                if (!included().length) problems.push('أبقِ سيرة واحدة على الأقل ضمن الإرسال.');
                if (customText.value.length > max) problems.push('نص الرسالة أطول من حد واتساب.');
                if (included().some(cv => !cv.querySelector('[data-key="name"]').value.trim())) problems.push('اسم العاملة مطلوب.');
                if (problems.length) {
                    const list = document.createElement('ul');
                    list.className = 'text-end mb-0';
                    problems.forEach(p => list.appendChild(Object.assign(document.createElement('li'), {textContent: p})));
                    Swal.fire({icon: 'error', title: 'راجع البيانات', html: list, confirmButtonText: 'حسنًا'});
                    return;
                }
                if (button.value !== '1') return submitForm(button);

                const count = included().length;
                const summary = document.createElement('div');
                summary.innerHTML = '<div class="mb-2">سيتم إرسال <b>رسالة البيانات</b> و<b class="js-n"></b> إلى:</div>'
                    + '<div class="fw-bold fs-5"></div><div class="text-muted" dir="ltr"></div>';
                summary.querySelector('.js-n').textContent = count + (count === 1 ? ' ملف سيرة' : ' ملفات سير');
                summary.children[1].textContent = @json($share->client_name);
                summary.children[2].textContent = @json($share->phone);
                const result = await Swal.fire({
                    icon: 'question', title: 'تأكيد الإرسال عبر واتساب', html: summary,
                    showCancelButton: true, reverseButtons: true, focusCancel: true,
                    confirmButtonText: '<i class="fab fa-whatsapp"></i> نعم، أرسل الآن', cancelButtonText: 'مراجعة مرة أخرى',
                    confirmButtonColor: '#25D366', cancelButtonColor: '#6c757d',
                });
                if (result.isConfirmed) submitForm(button);
            });

            form.querySelectorAll('.js-pdf').forEach(btn => btn.addEventListener('click', () => {
                document.getElementById('cvPdfTitle').textContent = btn.dataset.title;
                document.getElementById('cvPdfFrame').src = btn.dataset.src;
                bootstrap.Modal.getOrCreateInstance(document.getElementById('cvPdfModal')).show();
            }));

            render();
        })();
        </script>
    @else
        {{-- ===================== متابعة حالة الإرسال ===================== --}}
        <div class="row g-3">
            <div class="col-xl-5">
                <div class="wa-phone">
                    <div class="wa-head">
                        <span class="avatar"><i class="fas fa-user"></i></span>
                        <div><div class="fw-bold">{{ $share->client_name }}</div><div class="small opacity-75"><bdi>{{ $share->phone }}</bdi></div></div>
                    </div>
                    <div class="wa-body">
                        <div class="wa-bubble">{{ $share->message }}</div>
                        @foreach ($files as $item)
                            <div class="wa-doc"><div class="inner"><span class="pdf">PDF</span><span class="small fw-semibold text-truncate">{{ $item->filename }}</span></div></div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="col-xl-7">
                <div class="card"><div class="card-body">
                    <h6 class="fw-bold mb-3"><i class="fas fa-tasks text-success"></i> حالة كل رسالة</h6>
                    <p class="small text-muted">قبول المزود للرسالة لا يؤكد تسليمها. يظهر التسليم والقراءة عند ورود تحديث من المزود.</p>
                    <div class="list-group">
                        @foreach ($share->messages as $message)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div>
                                        @if ($message->file_path)
                                            <i class="far fa-file-pdf text-danger"></i>
                                            <a href="{{ route('maid-cv-shares.download', $message->id) }}" target="_blank" rel="noopener">{{ $message->snapshot['name'] ?? '' }} — {{ $message->snapshot['reference'] ?? '' }}</a>
                                            @if (isset($message->snapshot['cv_type']))<span class="badge bg-light text-dark border">{{ $message->snapshot['cv_type'] === 'rental' ? 'إيجار' : 'استقدام' }}</span>@endif
                                        @else
                                            <i class="far fa-comment-alt text-success"></i> رسالة البيانات المجمعة
                                        @endif
                                        <div class="small text-muted mt-1">
                                            محاولات: {{ $message->attempts }}
                                            @if ($message->accepted_at) · قبول: {{ $message->accepted_at }}@endif
                                            @if ($message->delivered_at) · تسليم: {{ $message->delivered_at }}@endif
                                            @if ($message->read_at) · قراءة: {{ $message->read_at }}@endif
                                        </div>
                                        @if ($message->error)<div class="small text-danger mt-1">{{ $message->error }}</div>@endif
                                    </div>
                                    <span class="badge bg-{{ $colors[$message->status] ?? 'secondary' }}">{{ $labels[$message->status] ?? $message->status }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div></div>

                <div class="d-flex flex-wrap gap-2 mt-3">
                    @if ((int) $share->admin_id === (int) auth('admin')->id() && in_array($share->status, ['failed', 'partial'], true))
                        <form method="POST" action="{{ route('maid-cv-shares.retry', $share->id) }}" onsubmit="this.querySelector('button').disabled=true">@csrf
                            <button class="btn btn-warning"><i class="fas fa-redo"></i> إعادة محاولة الرسائل التي فشلت فقط</button>
                        </form>
                    @endif
                    @if ($share->status === 'draft')
                        <div class="alert alert-secondary mb-0 py-2">انتهت صلاحية هذه المسودة أو أنها لموظف آخر؛ جهّز مشاركة جديدة للإرسال.</div>
                    @endif
                    <a class="btn btn-outline-secondary" href="{{ route('maid-cv-shares.index', ['client_id' => $share->client_id]) }}"><i class="fas fa-history"></i> تاريخ مشاركات العميل</a>
                </div>
            </div>
        </div>
        @if (in_array($share->status, ['queued', 'processing'], true))
            <script>setTimeout(() => window.location.reload(), 10000);</script>
        @endif
    @endif
</div>
@endsection
