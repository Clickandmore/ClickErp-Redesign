@include('admin.superadmin.system-updates._styles')

@php
    $update = $systemUpdate;
    $isNew = ! $update->exists;
    $dateValue = fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('Y-m-d\TH:i') : '';
    $currentCategory = old('category', $update->category ?: 'feature');
    $currentStatus = old('status', $update->status ?: 'draft');
    $isShared = (bool) old('is_shared', $update->is_shared);
    $categoryHelp = [
        'feature' => 'شيء جديد لم يكن موجودًا. يظهر في الشريط باللون الأخضر.',
        'improvement' => 'ميزة موجودة أصبحت أسهل أو أسرع.',
        'fix' => 'خطأ كان يحدث وتم إصلاحه.',
        'important' => 'تغيير يؤثر على طريقة العمل أو الحسابات. يجعل الشريط أحمر ويظهر أولًا.',
    ];
@endphp

<form class="su-editor" method="POST" enctype="multipart/form-data" id="suEditor"
      action="{{ $isNew ? route('dev-superadmin.system-updates.store') : route('dev-superadmin.system-updates.update', $update) }}">
    @csrf
    @unless ($isNew) @method('PUT') @endunless

    <div class="su-editor-head">
        <div>
            <a class="su-back" href="{{ route('dev-superadmin.product-updates') }}">→ كل التحديثات</a>
            <h2>{{ $isNew ? 'إضافة تحديث جديد' : $update->title }}</h2>
            <p>املأ الأقسام بالترتيب. الحقول المعلَّمة بـ <span class="required">*</span> إجبارية، والباقي اختياري.</p>
        </div>
        @unless ($isNew)
            <a class="btn" href="{{ route('dev-superadmin.system-updates.report', $update) }}">من اطلع على التحديث؟</a>
        @endunless
    </div>

    <div class="su-editor-grid">
        <div class="su-editor-main">

            {{-- ① المحتوى --}}
            <section class="su-card">
                <header class="su-card-head">
                    <span class="su-step">1</span>
                    <div>
                        <h3>محتوى التحديث</h3>
                        <p>ما الذي تغيّر؟ اكتب بلغة يفهمها الموظف، لا بلغة المبرمج.</p>
                    </div>
                </header>

                <div class="su-field">
                    <label for="suTitle">عنوان التحديث <span class="required">*</span></label>
                    <p class="su-hint">جملة قصيرة تصف التغيير. تظهر في شريط التحديثات أعلى اللوحة وفي رأس البطاقة.</p>
                    <input id="suTitle" name="title" type="text" required maxlength="250" value="{{ old('title', $update->title) }}" placeholder="مثال: إنشاء إشعار دائن مباشرة من الفاتورة">
                    @error('title')<p class="su-field-error">{{ $message }}</p>@enderror
                </div>

                <div class="su-field">
                    <label for="suSummary">الملخص</label>
                    <p class="su-hint">سطر واحد يشرح <b>الفائدة</b> للموظف. يظهر تحت العنوان مباشرة.</p>
                    <input id="suSummary" name="summary" type="text" maxlength="500" value="{{ old('summary', $update->summary) }}" placeholder="مثال: لم تعد بحاجة للذهاب لشاشة الحسابات لإلغاء جزء من الفاتورة">
                </div>

                <div class="su-field">
                    <label for="suBody">التفاصيل والخطوات</label>
                    <p class="su-hint">اشرح أين يجد الموظف الميزة وكيف يستخدمها خطوة بخطوة. استخدم أزرار التنسيق أو اكتب مباشرة.</p>
                    <div class="su-md-toolbar" role="toolbar" aria-label="تنسيق النص">
                        <button type="button" data-md="bold" title="نص عريض"><b>B</b> عريض</button>
                        <button type="button" data-md="heading" title="عنوان فرعي">عنوان فرعي</button>
                        <button type="button" data-md="list" title="قائمة نقطية">• قائمة</button>
                        <button type="button" data-md="numbered" title="خطوات مرقمة">1. خطوات</button>
                    </div>
                    <textarea id="suBody" name="body" rows="10" maxlength="20000" placeholder="### أين أجدها؟&#10;من شاشة الفاتورة اضغط زر «إشعار دائن».&#10;&#10;### الخطوات&#10;1. افتح الفاتورة&#10;2. اضغط «إشعار دائن»&#10;3. حدد المبلغ واحفظ">{{ old('body', $update->body) }}</textarea>
                </div>
            </section>

            {{-- ② الوسائط --}}
            <section class="su-card">
                <header class="su-card-head">
                    <span class="su-step">2</span>
                    <div>
                        <h3>الصور والفيديو والملفات</h3>
                        <p>صورة واحدة توضح الزر الجديد تغني عن فقرة كاملة.</p>
                    </div>
                </header>

                @if (! $isNew && $update->media->isNotEmpty())
                    <div class="su-field">
                        <label>المرفقات الحالية</label>
                        <p class="su-hint">اكتب وصفًا يظهر تحت كل مرفق، أو علّم «حذف» لإزالته عند الحفظ.</p>
                        <div class="su-media-list">
                            @foreach ($update->media as $media)
                                <div class="su-media-item">
                                    @if ($media->type === 'image')
                                        <img src="{{ $media->publicUrl() }}" alt="" loading="lazy">
                                    @elseif ($media->type === 'video')
                                        <video src="{{ $media->publicUrl() }}" preload="metadata" muted></video>
                                    @elseif ($media->type === 'pdf')
                                        <a class="su-media-embed su-media-pdf" href="{{ $media->publicUrl() }}" target="_blank" rel="noopener">PDF<small>فتح الملف</small></a>
                                    @else
                                        <div class="su-media-embed su-code">{{ $media->url }}</div>
                                    @endif
                                    <div class="su-media-meta">
                                        <input type="text" name="media_captions[{{ $media->id }}]" maxlength="250" value="{{ old('media_captions.' . $media->id, $media->caption) }}" placeholder="{{ $media->type === 'pdf' ? 'اسم الملف كما يراه الموظف' : 'وصف (اختياري)' }}">
                                        <label><input type="checkbox" name="remove_media[]" value="{{ $media->id }}"> حذف</label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="su-field">
                    <label for="suMedia">رفع ملفات جديدة</label>
                    <p class="su-hint">صور (حتى 5MB) أو فيديو قصير mp4 (حتى 100MB) أو ملف <b>PDF</b> كدليل استخدام (حتى 20MB). حتى 10 ملفات في المرة.</p>
                    <label class="su-dropzone" for="suMedia" id="suDropzone">
                        <strong>اسحب الملفات هنا أو اضغط للاختيار</strong>
                        <span>JPG · PNG · WEBP · GIF · MP4 · WEBM · MOV · PDF</span>
                        <input id="suMedia" name="media[]" type="file" multiple accept=".jpg,.jpeg,.png,.webp,.gif,.mp4,.webm,.mov,.pdf">
                    </label>
                    <ul class="su-file-list" id="suFileList"></ul>
                    @error('media.*')<p class="su-field-error">{{ $message }}</p>@enderror
                </div>

                <div class="su-field">
                    <label for="suEmbeds">روابط فيديو خارجية</label>
                    <p class="su-hint">للفيديو الطويل ارفعه على YouTube (غير مُدرج) أو Vimeo أو Loom وضع الرابط هنا؛ يظهر كمشغّل داخل البطاقة. رابط في كل سطر.</p>
                    <textarea id="suEmbeds" name="embed_urls" rows="2" dir="ltr" placeholder="https://youtu.be/xxxxxxxx">{{ old('embed_urls') }}</textarea>
                    @error('embed_urls')<p class="su-field-error">{{ $message }}</p>@enderror
                </div>
            </section>

            {{-- ③ الرابط --}}
            <section class="su-card">
                <header class="su-card-head">
                    <span class="su-step">3</span>
                    <div>
                        <h3>زر «جرّبه الآن»</h3>
                        <p>اختياري: يأخذ الموظف مباشرة إلى الشاشة التي تغيّرت.</p>
                    </div>
                </header>

                <div class="su-row">
                    <div class="su-field">
                        <label for="suLink">رابط الشاشة</label>
                        <p class="su-hint">انسخ الجزء بعد الدومين من شريط المتصفح، مثل <span class="su-code">/dashboard/invoices</span>. يعمل في كل نسخ العملاء.</p>
                        <input id="suLink" name="link_url" type="text" dir="ltr" maxlength="1000" value="{{ old('link_url', $update->link_url) }}" placeholder="/dashboard/invoices">
                        @error('link_url')<p class="su-field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="su-field">
                        <label for="suLinkLabel">نص الزر</label>
                        <p class="su-hint">إن تركته فارغًا يظهر «جرّبه الآن».</p>
                        <input id="suLinkLabel" name="link_label" type="text" maxlength="80" value="{{ old('link_label', $update->link_label) }}" placeholder="افتح الفواتير">
                    </div>
                </div>
            </section>
        </div>

        <aside class="su-editor-side">
            {{-- النشر --}}
            <section class="su-card">
                <header class="su-card-head compact"><div><h3>النشر</h3></div></header>

                <div class="su-field">
                    <label>حالة التحديث</label>
                    <div class="su-choice-list">
                        <label class="su-choice">
                            <input type="radio" name="status" value="draft" {{ $currentStatus === 'draft' ? 'checked' : '' }}>
                            <span><b>مسودة</b><small>محفوظ عندك فقط، لا يراه أي موظف.</small></span>
                        </label>
                        <label class="su-choice">
                            <input type="radio" name="status" value="published" {{ $currentStatus === 'published' ? 'checked' : '' }}>
                            <span><b>منشور</b><small>يظهر للموظفين في موعد النشر.</small></span>
                        </label>
                    </div>
                </div>

                <div class="su-field">
                    <label for="suPublishedAt">موعد النشر</label>
                    <p class="su-hint">اتركه فارغًا للنشر فورًا. اختر وقتًا لاحقًا ليظهر تلقائيًا مع رفع التحديث على السيرفر.</p>
                    <input id="suPublishedAt" name="published_at" type="datetime-local" value="{{ old('published_at', $dateValue($update->published_at)) }}">
                </div>

                <div class="su-field">
                    <label for="suBannerUntil">يختفي من الشريط في</label>
                    <p class="su-hint">بعده لا يظهر في الشريط حتى لمن لم يؤكد، ويبقى في صفحة التحديثات. فارغ = بعد {{ \App\Support\SystemUpdatesSettings::bannerDays() }} يومًا من النشر.</p>
                    <input id="suBannerUntil" name="banner_until" type="datetime-local" value="{{ old('banner_until', $dateValue($update->banner_until)) }}">
                    @error('banner_until')<p class="su-field-error">{{ $message }}</p>@enderror
                </div>

                <button class="btn btn-primary su-submit" type="submit">{{ $isNew ? 'حفظ التحديث' : 'حفظ التعديلات' }}</button>
                @unless ($isNew)
                    <p class="su-hint" style="text-align:center; margin-top:8px">آخر تعديل {{ $update->updated_at?->diffForHumans() }}</p>
                @endunless
            </section>

            {{-- التصنيف --}}
            <section class="su-card">
                <header class="su-card-head compact"><div><h3>نوع التحديث</h3><p>يحدد لون الشارة وترتيب الظهور.</p></div></header>
                <div class="su-choice-list">
                    @foreach (\App\Models\SystemUpdate::CATEGORIES as $key => $meta)
                        <label class="su-choice su-choice-{{ $key }}">
                            <input type="radio" name="category" value="{{ $key }}" {{ $currentCategory === $key ? 'checked' : '' }} required>
                            <span><b>{{ $meta['label'] }}</b><small>{{ $categoryHelp[$key] }}</small></span>
                        </label>
                    @endforeach
                </div>
            </section>

            {{-- الاستهداف --}}
            <section class="su-card">
                <header class="su-card-head compact"><div><h3>لمن هذا التحديث؟</h3></div></header>

                <div class="su-field">
                    <label for="suModule">يخص موديل</label>
                    <p class="su-hint">إن اخترت موديلًا فلن يظهر التحديث في أي نسخة الموديل فيها مغلق (مثلًا: تحديث في إيجار الساعات لا يظهر لعميل لا يستخدمه).</p>
                    <select id="suModule" name="module_key">
                        <option value="">كل النسخ — بدون شرط</option>
                        @foreach ($systemUpdateModules as $key => $module)
                            <option value="{{ $key }}" data-enabled-here="{{ ($audience['enabled_modules'][$key] ?? true) ? '1' : '0' }}" {{ old('module_key', $update->module_key) === $key ? 'selected' : '' }}>{{ $module['name'] ?? $key }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="su-field">
                    <label class="switch-row su-switch" for="suShared">
                        <span>إرسال لنسخ العملاء المرتبطة</span>
                        <span class="switch">
                            <input type="hidden" name="is_shared" value="0">
                            <input id="suShared" name="is_shared" type="checkbox" value="1" {{ $isShared ? 'checked' : '' }}>
                            <span class="slider"></span>
                        </span>
                    </label>
                    <p class="su-hint">أوقفه إن كان التحديث خاصًا بهذه النسخة فقط.</p>
                </div>

                <div class="su-field">
                    <label for="suVersion">رقم الإصدار</label>
                    <p class="su-hint">اختياري، للرجوع إليه عند الدعم الفني.</p>
                    <input id="suVersion" name="version" type="text" dir="ltr" maxlength="40" value="{{ old('version', $update->version) }}" placeholder="2.4">
                </div>

                <div class="su-audience" id="suAudience"
                     data-employees="{{ $audience['employees'] }}"
                     data-clients="{{ count($audience['clients']) }}">
                    <b>من سيرى هذا التحديث؟</b>
                    <ul>
                        <li data-audience="here"></li>
                        <li data-audience="clients"></li>
                        <li>لا يظهر لعملاء الموقع العام ولا لتطبيق الجوال.</li>
                    </ul>
                    @if (count($audience['clients']))
                        <details>
                            <summary>النسخ المرتبطة ({{ count($audience['clients']) }})</summary>
                            <p>{{ implode('، ', $audience['clients']) }}</p>
                        </details>
                    @endif
                </div>
            </section>
        </aside>
    </div>
</form>

<script>
(function () {
    const body = document.getElementById('suBody');
    document.querySelectorAll('[data-md]').forEach(function (button) {
        button.addEventListener('click', function () {
            const start = body.selectionStart, end = body.selectionEnd;
            const selected = body.value.slice(start, end) || 'نص';
            const lines = selected.split('\n');
            const formats = {
                bold: '**' + selected + '**',
                heading: '### ' + selected,
                list: lines.map(function (line) { return '- ' + line; }).join('\n'),
                numbered: lines.map(function (line, i) { return (i + 1) + '. ' + line; }).join('\n'),
            };
            const prefix = start > 0 && body.value[start - 1] !== '\n' && button.dataset.md !== 'bold' ? '\n' : '';
            body.setRangeText(prefix + formats[button.dataset.md], start, end, 'end');
            body.focus();
        });
    });

    const input = document.getElementById('suMedia');
    const list = document.getElementById('suFileList');
    const dropzone = document.getElementById('suDropzone');
    function renderFiles() {
        list.innerHTML = '';
        Array.from(input.files || []).forEach(function (file) {
            const item = document.createElement('li');
            const ext = (file.name.split('.').pop() || '').toLowerCase();
            const kind = ext === 'pdf' ? 'PDF' : (['mp4', 'webm', 'mov'].includes(ext) ? 'فيديو' : 'صورة');
            item.innerHTML = '<span class="su-file-kind"></span><span class="su-file-name"></span><small></small>';
            item.querySelector('.su-file-kind').textContent = kind;
            item.querySelector('.su-file-name').textContent = file.name;
            item.querySelector('small').textContent = (file.size / 1048576).toFixed(1) + ' MB';
            list.appendChild(item);
        });
    }
    input.addEventListener('change', renderFiles);
    ['dragenter', 'dragover'].forEach(function (name) {
        dropzone.addEventListener(name, function (event) { event.preventDefault(); dropzone.classList.add('is-over'); });
    });
    ['dragleave', 'drop'].forEach(function (name) {
        dropzone.addEventListener(name, function () { dropzone.classList.remove('is-over'); });
    });
    dropzone.addEventListener('drop', function (event) {
        event.preventDefault();
        input.files = event.dataTransfer.files;
        renderFiles();
    });

    const audience = document.getElementById('suAudience');
    const moduleSelect = document.getElementById('suModule');
    const shared = document.getElementById('suShared');
    const status = document.querySelectorAll('input[name="status"]');
    function renderAudience() {
        const isDraft = document.querySelector('input[name="status"]:checked')?.value !== 'published';
        const option = moduleSelect.selectedOptions[0];
        const moduleName = moduleSelect.value ? option.textContent.trim() : '';
        const enabledHere = !moduleSelect.value || option.dataset.enabledHere === '1';
        const here = audience.querySelector('[data-audience="here"]');
        const clients = audience.querySelector('[data-audience="clients"]');
        const clientCount = parseInt(audience.dataset.clients, 10);

        if (isDraft) {
            here.textContent = 'لا أحد الآن — التحديث مسودة.';
        } else if (!enabledHere) {
            here.textContent = 'لا أحد في هذه النسخة: موديل «' + moduleName + '» مغلق هنا.';
        } else {
            here.textContent = 'كل موظفي هذه النسخة (' + audience.dataset.employees + ' موظف نشط) بغض النظر عن صلاحياتهم.';
        }

        if (!shared.checked) {
            clients.textContent = 'لن يُرسل لنسخ العملاء.';
        } else if (clientCount === 0) {
            clients.textContent = 'لا توجد نسخ عملاء مرتبطة حاليًا.';
        } else {
            clients.textContent = 'كل موظفي ' + clientCount + ' نسخة عميل مرتبطة' + (moduleName ? ' (التي فيها موديل «' + moduleName + '» مفعّل)' : '') + (isDraft ? ' — عند النشر' : '') + '.';
        }
    }
    [moduleSelect, shared].forEach(function (el) { el.addEventListener('change', renderAudience); });
    status.forEach(function (el) { el.addEventListener('change', renderAudience); });
    renderAudience();
})();
</script>
