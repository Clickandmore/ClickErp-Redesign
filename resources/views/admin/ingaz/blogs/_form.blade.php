@php
    $locales = config('translatable.locales', ['ar']);
    $defaultLocale = config('app.locale', 'ar');
    $localeLabels = ['ar' => 'العربية', 'en' => 'English'];
@endphp

<div class="col-md-6">
    <label class="form-label">رابط المقال (Slug)</label>
    <input type="text" name="slug" class="form-control" value="{{ $obj->slug ?? '' }}"
        placeholder="يُولَّد تلقائيًا من العنوان عند تركه فارغًا" dir="ltr">
    <small class="text-muted">يظهر في رابط الصفحة: /blog/<span class="fw-bold">slug</span> — تغييره بعد النشر يفقد ترتيب الصفحة في محركات البحث.</small>
</div>

<div class="col-md-3">
    <label class="form-label">التصنيف</label>
    <input type="text" name="category" class="form-control" value="{{ $obj->category ?? '' }}"
        placeholder="مثال: نصائح الاستقدام">
</div>

<div class="col-md-3">
    <label class="form-label">تاريخ النشر</label>
    <input type="datetime-local" name="published_at" class="form-control"
        value="{{ isset($obj) && $obj->published_at ? $obj->published_at->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i') }}">
    <small class="text-muted">تاريخ مستقبلي يبقي المقال مخفيًا حتى موعده.</small>
</div>

<div class="col-md-4">
    <label class="form-label">الحالة</label>
    <select name="is_active" class="form-control">
        <option value="1" {{ (bool) ($obj->is_active ?? true) ? 'selected' : '' }}>منشور</option>
        <option value="0" {{ isset($obj) && ! $obj->is_active ? 'selected' : '' }}>مخفي</option>
    </select>
</div>

<div class="col-md-4">
    <label class="form-label">مقال مميّز</label>
    <select name="is_featured" class="form-control">
        <option value="0" {{ isset($obj) && $obj->is_featured ? '' : 'selected' }}>لا</option>
        <option value="1" {{ isset($obj) && $obj->is_featured ? 'selected' : '' }}>نعم</option>
    </select>
    <small class="text-muted">المقالات المميّزة تُعرض أولًا في سكشن الصفحة الرئيسية.</small>
</div>

<div class="col-md-4">
    <label class="form-label">ترتيب العرض</label>
    <input type="number" name="sort_order" class="form-control" min="0" value="{{ $obj->sort_order ?? 0 }}">
</div>

<div class="col-12">
    <label class="form-label">صورة المقال @if (empty($obj)) <span class="text-danger">*</span> @endif</label>
    <input type="file" name="image" class="dropify"
        data-default-file="{{ isset($obj) && $obj->image ? getFile($obj->image) : '' }}" {{ empty($obj) ? 'required' : '' }}>
    <small class="text-muted">المقاس المفضّل 1200×630 بكسل — نفس المقاس الذي تعرضه منصات التواصل عند مشاركة الرابط.</small>
</div>

@foreach ($locales as $locale)
    @php
        $translation = isset($obj) && $obj ? $obj->translate($locale) : null;
        $isDefault = $locale === $defaultLocale;
    @endphp

    <div class="col-12">
        <div class="card border">
            <div class="card-header bg-light fw-bold">
                محتوى المقال — {{ $localeLabels[$locale] ?? $locale }}
                @if ($isDefault)
                    <span class="text-danger">*</span>
                @else
                    <span class="text-muted small fw-normal">(اختياري — يُعرض بدلًا منه المحتوى العربي إذا تُرك فارغًا)</span>
                @endif
            </div>
            <div class="card-body row g-3">
                <div class="col-12">
                    <label class="form-label">العنوان @if ($isDefault) <span class="text-danger">*</span> @endif</label>
                    <input type="text" name="title:{{ $locale }}" class="form-control"
                        value="{{ $translation->title ?? '' }}" {{ $isDefault ? 'required' : '' }}>
                </div>

                <div class="col-12">
                    <label class="form-label">الملخص</label>
                    <textarea name="excerpt:{{ $locale }}" class="form-control" rows="2"
                        maxlength="1000">{{ $translation->excerpt ?? '' }}</textarea>
                    <small class="text-muted">يظهر في بطاقة المقال وفي سكشن الصفحة الرئيسية.</small>
                </div>

                <div class="col-12">
                    <label class="form-label">المحتوى @if ($isDefault) <span class="text-danger">*</span> @endif</label>
                    {{-- mytextarea هو ما يلتقطه TinyMCE تلقائيًا بعد فتح المودال. --}}
                    <textarea name="content:{{ $locale }}" id="blog-content-{{ $locale }}" class="form-control mytextarea"
                        rows="10">{{ $translation->content ?? '' }}</textarea>
                    {{-- الأكواد المختصرة تُستبدل عند الإرسال للموقع الخارجي — App\Support\BlogShortcodes. --}}
                    <div class="small text-muted mt-2 d-flex flex-wrap align-items-center gap-2">
                        <span>أكواد مختصرة:</span>
                        @foreach (\App\Support\BlogShortcodes::available() as $shortcode => $shortcodeLabel)
                            {{-- الأقواس تُبنى في PHP لأن Blade يفسّر {{ داخل القالب. --}}
                            @php $shortcodeTag = '{' . '{' . $shortcode . '}' . '}'; @endphp
                            <button type="button" class="btn btn-sm btn-outline-secondary" dir="ltr"
                                title="{{ $shortcodeLabel }}"
                                onclick="window.tinymce && tinymce.get('blog-content-{{ $locale }}') && tinymce.get('blog-content-{{ $locale }}').insertContent('<p>{{ $shortcodeTag }}</p>')">
                                {{ $shortcodeTag }}
                            </button>
                            <span>{{ $shortcodeLabel }} — اكتبه في سطر مستقل في المكان المطلوب.</span>
                        @endforeach
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">عنوان SEO</label>
                    <input type="text" name="meta_title:{{ $locale }}" class="form-control" maxlength="255"
                        value="{{ $translation->meta_title ?? '' }}"
                        placeholder="يُستخدم عنوان المقال عند تركه فارغًا">
                </div>

                <div class="col-md-6">
                    <label class="form-label">وصف SEO</label>
                    <input type="text" name="meta_description:{{ $locale }}" class="form-control" maxlength="500"
                        value="{{ $translation->meta_description ?? '' }}"
                        placeholder="يُستخدم الملخص عند تركه فارغًا">
                </div>
            </div>
        </div>
    </div>
@endforeach

@php
    // الصفوف المحفوظة مسبقًا؛ الصف الأول يُعرض فارغًا عند إنشاء مقال جديد
    // حتى يجد المحرر حقلًا جاهزًا للكتابة بدل الضغط على "إضافة" أولًا.
    $existingFaqs = isset($obj) && $obj ? $obj->faqs : collect();
@endphp

<div class="col-12">
    <div class="card border">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span class="fw-bold">الأسئلة الشائعة للمقال</span>
            <button type="button" class="btn btn-sm btn-primary" id="blog-faq-add">
                <i class="fa fa-plus me-1"></i> إضافة سؤال
            </button>
        </div>
        <div class="card-body">
            <small class="text-muted d-block mb-3">
                تظهر أسفل المقال في الموقع الخارجي كأكورديون، وتُرسل أيضًا لمحركات البحث كوسم
                FAQ. الصف الذي يُترك فارغًا لا يُحفظ، والسؤال بلا نسخة إنجليزية يُعرض بالعربية.
            </small>

            <div id="blog-faq-rows">
                @foreach ($existingFaqs as $index => $faq)
                    <div class="border rounded p-3 mb-3 blog-faq-row">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold text-muted">سؤال <span class="blog-faq-number">{{ $index + 1 }}</span></span>
                            <button type="button" class="btn btn-sm btn-outline-danger blog-faq-remove">
                                <i class="fa fa-trash me-1"></i> حذف
                            </button>
                        </div>

                        <input type="hidden" name="faqs[{{ $index }}][id]" value="{{ $faq->id }}">

                        <div class="row g-3">
                            @foreach ($locales as $locale)
                                @php $faqTranslation = $faq->translate($locale); @endphp

                                <div class="col-md-6">
                                    <label class="form-label">السؤال — {{ $localeLabels[$locale] ?? $locale }}</label>
                                    <input type="text" class="form-control" maxlength="500"
                                        name="faqs[{{ $index }}][question:{{ $locale }}]"
                                        value="{{ $faqTranslation->question ?? '' }}">

                                    <label class="form-label mt-2">الإجابة — {{ $localeLabels[$locale] ?? $locale }}</label>
                                    <textarea class="form-control" rows="3" maxlength="5000"
                                        name="faqs[{{ $index }}][answer:{{ $locale }}]">{{ $faqTranslation->answer ?? '' }}</textarea>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="text-muted small blog-faq-empty {{ $existingFaqs->count() ? 'd-none' : '' }}">
                لا توجد أسئلة شائعة لهذا المقال بعد.
            </div>
        </div>
    </div>
</div>

{{-- قالب الصف الجديد. داخل <template> حتى لا تُرسل حقوله مع النموذج،
     و__INDEX__ يُستبدل بالرقم التالي عند الإضافة حتى لا تتصادم أسماء الحقول. --}}
<template id="blog-faq-template">
    <div class="border rounded p-3 mb-3 blog-faq-row">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="fw-bold text-muted">سؤال <span class="blog-faq-number"></span></span>
            <button type="button" class="btn btn-sm btn-outline-danger blog-faq-remove">
                <i class="fa fa-trash me-1"></i> حذف
            </button>
        </div>

        <input type="hidden" name="faqs[__INDEX__][id]" value="">

        <div class="row g-3">
            @foreach ($locales as $locale)
                <div class="col-md-6">
                    <label class="form-label">السؤال — {{ $localeLabels[$locale] ?? $locale }}</label>
                    <input type="text" class="form-control" maxlength="500"
                        name="faqs[__INDEX__][question:{{ $locale }}]" value="">

                    <label class="form-label mt-2">الإجابة — {{ $localeLabels[$locale] ?? $locale }}</label>
                    <textarea class="form-control" rows="3" maxlength="5000"
                        name="faqs[__INDEX__][answer:{{ $locale }}]"></textarea>
                </div>
            @endforeach
        </div>
    </div>
</template>

<div class="col-12 modal-footer">
    <button class="btn btn-primary" type="submit">{{ __('buttons.save') }}</button>
    <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">{{ __('buttons.close') }}</button>
</div>

<script>
    // النموذج يُحمَّل داخل مودال عبر أجاكس، فلا يمر على $(document).ready —
    // لذلك يُنفَّذ السكربت فور حقنه، ويُربط على العنصر الجذري لهذه النسخة فقط
    // حتى لا يتضاعف السلوك عند فتح المودال أكثر من مرة.
    (function () {
        var container = document.getElementById('blog-faq-rows');
        var template = document.getElementById('blog-faq-template');
        var addButton = document.getElementById('blog-faq-add');

        if (!container || !template || !addButton) {
            return;
        }

        function renumber() {
            var rows = container.querySelectorAll('.blog-faq-row');

            rows.forEach(function (row, index) {
                var label = row.querySelector('.blog-faq-number');

                if (label) {
                    label.textContent = index + 1;
                }

                // إعادة ترقيم أسماء الحقول بعد الحذف حتى تصل المصفوفة للخادم
                // بمفاتيح متتابعة بلا فجوات.
                row.querySelectorAll('[name]').forEach(function (field) {
                    field.name = field.name.replace(/^faqs\[[^\]]*\]/, 'faqs[' + index + ']');
                });
            });

            var empty = document.querySelector('.blog-faq-empty');

            if (empty) {
                empty.classList.toggle('d-none', rows.length > 0);
            }
        }

        addButton.addEventListener('click', function () {
            var markup = template.innerHTML.replace(/__INDEX__/g, container.querySelectorAll('.blog-faq-row').length);
            container.insertAdjacentHTML('beforeend', markup);
            renumber();
            container.lastElementChild.querySelector('input[type="text"]').focus();
        });

        container.addEventListener('click', function (event) {
            var button = event.target.closest('.blog-faq-remove');

            if (!button) {
                return;
            }

            button.closest('.blog-faq-row').remove();
            renumber();
        });

        renumber();
    })();
</script>
