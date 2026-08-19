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
                    <textarea name="content:{{ $locale }}" class="form-control mytextarea"
                        rows="10">{{ $translation->content ?? '' }}</textarea>
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

<div class="col-12 modal-footer">
    <button class="btn btn-primary" type="submit">{{ __('buttons.save') }}</button>
    <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">{{ __('buttons.close') }}</button>
</div>
