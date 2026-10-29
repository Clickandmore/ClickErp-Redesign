@php
    $locales = config('translatable.locales', ['ar']);
    $localeLabels = ['ar' => 'العربية', 'en' => 'English'];
@endphp

<div class="row g-3">
    <div class="col-md-4">
        @if ($obj->image)
            <img src="{{ getFile($obj->image) }}" class="img-fluid rounded border" alt="{{ $obj->title }}">
        @else
            <div class="border rounded p-4 text-center text-muted">لا توجد صورة</div>
        @endif
    </div>

    <div class="col-md-8">
        <table class="table table-sm table-bordered mb-0">
            <tbody>
                <tr>
                    <th style="width: 180px;">رابط المقال</th>
                    <td dir="ltr">{{ $obj->slug }}</td>
                </tr>
                <tr>
                    <th>التصنيف</th>
                    <td>{{ $obj->category ?: '-' }}</td>
                </tr>
                <tr>
                    <th>تاريخ النشر</th>
                    <td>{{ $obj->published_at ? $obj->published_at->format('Y-m-d H:i') : '-' }}</td>
                </tr>
                <tr>
                    <th>الحالة</th>
                    <td>{{ $obj->is_active ? 'منشور' : 'مخفي' }}{{ $obj->is_featured ? ' — مميّز' : '' }}</td>
                </tr>
                <tr>
                    <th>عدد المشاهدات</th>
                    <td>{{ (int) $obj->views_count }}</td>
                </tr>
                <tr>
                    <th>ترتيب العرض</th>
                    <td>{{ (int) $obj->sort_order }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    @foreach ($locales as $locale)
        @php $translation = $obj->translate($locale); @endphp

        <div class="col-12">
            <div class="card border">
                <div class="card-header bg-light fw-bold">{{ $localeLabels[$locale] ?? $locale }}</div>
                <div class="card-body">
                    @if ($translation && filled($translation->title))
                        <h5>{{ $translation->title }}</h5>

                        @if (filled($translation->excerpt))
                            <p class="text-muted">{{ $translation->excerpt }}</p>
                        @endif

                        <hr>
                        <div class="blog-content">{!! \App\Support\BlogShortcodes::render($translation->content) !!}</div>

                        @if (filled($translation->meta_title) || filled($translation->meta_description))
                            <hr>
                            <div class="small text-muted">
                                <div><strong>عنوان SEO:</strong> {{ $translation->meta_title ?: '-' }}</div>
                                <div><strong>وصف SEO:</strong> {{ $translation->meta_description ?: '-' }}</div>
                            </div>
                        @endif
                    @else
                        <div class="text-muted">لا توجد ترجمة لهذه اللغة.</div>
                    @endif
                </div>
            </div>
        </div>
    @endforeach

    @if ($obj->faqs->isNotEmpty())
        <div class="col-12">
            <div class="card border">
                <div class="card-header bg-light fw-bold">الأسئلة الشائعة ({{ $obj->faqs->count() }})</div>
                <div class="card-body">
                    @foreach ($obj->faqs as $faq)
                        <div class="border rounded p-3 {{ $loop->last ? '' : 'mb-3' }}">
                            @foreach ($locales as $locale)
                                @php $faqTranslation = $faq->translate($locale); @endphp

                                @if ($faqTranslation && filled($faqTranslation->question))
                                    <div class="{{ $loop->first ? '' : 'mt-3 pt-3 border-top' }}">
                                        <span class="badge bg-light text-dark border mb-1">{{ $localeLabels[$locale] ?? $locale }}</span>
                                        <div class="fw-bold">{{ $faqTranslation->question }}</div>
                                        <div class="text-muted">{!! nl2br(e($faqTranslation->answer)) !!}</div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>

<div class="modal-footer">
    <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">{{ __('buttons.close') }}</button>
</div>
