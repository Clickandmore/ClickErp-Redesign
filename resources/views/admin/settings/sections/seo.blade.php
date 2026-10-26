@extends('admin.settings.sections.layout')

@section('settings-title', __('admin.settings_section_seo'))

@section('settings-description', __('admin.settings_section_seo_hint'))

@section('settings-content')
    <form class="row g-3 from-submit-global" method="post"
          action="{{ route('settings.update', $settings->id) }}"
          enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="col-12">
            <h6 class="text-muted mb-2">{{ __('admin.settings_seo_meta_data') }}</h6>
            <div class="row g-3">
                @foreach (config('translatable.locales') as $locale)
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admin.settings_seo_article_tags') }} {{ getFieldLanguage($locale) }}</label>
                        <input type="text" name="seo_article_tags:{{ $locale }}"
                               value="{{ $settings->translate($locale)->seo_article_tags ?? '' }}"
                               class="form-control"
                               placeholder="{{ __('admin.settings_seo_article_tags_placeholder') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admin.settings_meta_title') }} {{ getFieldLanguage($locale) }}</label>
                        <input type="text" name="meta_title:{{ $locale }}"
                               value="{{ $settings->translate($locale)->meta_title ?? '' }}"
                               class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admin.settings_meta_keywords') }} {{ getFieldLanguage($locale) }}</label>
                        <input type="text" name="meta_keywords:{{ $locale }}"
                               value="{{ $settings->translate($locale)->meta_keywords ?? '' }}"
                               class="form-control"
                               placeholder="{{ __('admin.settings_meta_keywords_placeholder') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admin.settings_meta_description') }} {{ getFieldLanguage($locale) }}</label>
                        <textarea name="meta_description:{{ $locale }}" class="form-control" rows="2">{{ $settings->translate($locale)->meta_description ?? '' }}</textarea>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="col-12">
            <hr>
        </div>

        <div class="col-12">
            <hr>
        </div>

        <div class="col-12">
            <h6 class="text-muted mb-2">{{ __('admin.settings_seo_indexing') }}</h6>
            <p class="text-muted small mb-3">{{ __('admin.settings_seo_indexing_hint') }}</p>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="seo_canonical_base">{{ __('admin.settings_seo_canonical_base') }}</label>
                    <input type="url" name="seo_canonical_base" id="seo_canonical_base" dir="ltr"
                           value="{{ old('seo_canonical_base', $settings->seo_canonical_base ?? '') }}"
                           class="form-control" placeholder="https://example.com">
                    <small class="text-muted d-block mt-1">{{ __('admin.settings_seo_canonical_base_hint') }}</small>
                </div>

                <div class="col-md-6">
                    <label class="form-label d-block">{{ __('admin.settings_seo_redirect_host') }}</label>
                    <input type="hidden" name="seo_redirect_host" value="0">
                    <div class="form-check form-switch">
                        <input
                            type="checkbox"
                            name="seo_redirect_host"
                            id="seo_redirect_host"
                            class="form-check-input"
                            value="1"
                            {{ (int) ($settings->seo_redirect_host ?? 1) === 1 ? 'checked' : '' }}>
                        <label class="form-check-label" for="seo_redirect_host">
                            {{ (int) ($settings->seo_redirect_host ?? 1) === 1 ? __('admin.settings_enabled') : __('admin.settings_disabled') }}
                        </label>
                    </div>
                    <small class="text-muted d-block mt-1">{{ __('admin.settings_seo_redirect_host_hint') }}</small>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="seo_sitemap_cache_minutes">{{ __('admin.settings_seo_sitemap_cache_minutes') }}</label>
                    <input type="number" name="seo_sitemap_cache_minutes" id="seo_sitemap_cache_minutes"
                           min="1" max="10080"
                           value="{{ old('seo_sitemap_cache_minutes', $settings->seo_sitemap_cache_minutes ?? 360) }}"
                           class="form-control">
                    <small class="text-muted d-block mt-1">{{ __('admin.settings_seo_sitemap_cache_minutes_hint') }}</small>
                </div>

                <div class="col-md-6">
                    <label class="form-label d-block">{{ __('admin.settings_seo_sitemap_worker_pages') }}</label>
                    <input type="hidden" name="seo_sitemap_worker_pages" value="0">
                    <div class="form-check form-switch">
                        <input
                            type="checkbox"
                            name="seo_sitemap_worker_pages"
                            id="seo_sitemap_worker_pages"
                            class="form-check-input"
                            value="1"
                            {{ (int) ($settings->seo_sitemap_worker_pages ?? 1) === 1 ? 'checked' : '' }}>
                        <label class="form-check-label" for="seo_sitemap_worker_pages">
                            {{ (int) ($settings->seo_sitemap_worker_pages ?? 1) === 1 ? __('admin.settings_enabled') : __('admin.settings_disabled') }}
                        </label>
                    </div>
                    <small class="text-muted d-block mt-1">{{ __('admin.settings_seo_sitemap_worker_pages_hint') }}</small>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="seo_sitemap_worker_limit">{{ __('admin.settings_seo_sitemap_worker_limit') }}</label>
                    <input type="number" name="seo_sitemap_worker_limit" id="seo_sitemap_worker_limit"
                           min="0" max="50000"
                           value="{{ old('seo_sitemap_worker_limit', $settings->seo_sitemap_worker_limit ?? 2000) }}"
                           class="form-control">
                    <small class="text-muted d-block mt-1">{{ __('admin.settings_seo_sitemap_worker_limit_hint') }}</small>
                </div>

                <div class="col-md-6">
                    <label class="form-label d-block">{{ __('admin.settings_seo_sitemap_offer_pages') }}</label>
                    <input type="hidden" name="seo_sitemap_offer_pages" value="0">
                    <div class="form-check form-switch">
                        <input
                            type="checkbox"
                            name="seo_sitemap_offer_pages"
                            id="seo_sitemap_offer_pages"
                            class="form-check-input"
                            value="1"
                            {{ (int) ($settings->seo_sitemap_offer_pages ?? 1) === 1 ? 'checked' : '' }}>
                        <label class="form-check-label" for="seo_sitemap_offer_pages">
                            {{ (int) ($settings->seo_sitemap_offer_pages ?? 1) === 1 ? __('admin.settings_enabled') : __('admin.settings_disabled') }}
                        </label>
                    </div>
                    <small class="text-muted d-block mt-1">{{ __('admin.settings_seo_sitemap_offer_pages_hint') }}</small>
                </div>
            </div>
        </div>

        <div class="col-12">
            <hr>
        </div>

        <div class="col-12">
            <h6 class="text-muted mb-2">ربط تتبع زوار موقع العميل</h6>
            <p class="text-muted small mb-3">اختر كودًا تم إنشاؤه من صفحة زوار الموقع. سيعمل الكود فقط على النطاقات المسجلة له، وستعيده API الإعدادات للموقع الخارجي.</p>
            <a href="{{ route('website-visitors.index', ['tab' => 'tracking']) }}" class="btn btn-sm btn-outline-info mb-3">إدارة أكواد التتبع والنطاقات</a>
            <select name="website_visitor_site_id" class="form-select" dir="rtl">
                <option value="">بدون تتبع</option>
                @foreach($visitorSites as $visitorSite)
                    <option value="{{ $visitorSite->id }}" @selected((string) old('website_visitor_site_id', $settings->website_visitor_site_id) === (string) $visitorSite->id)>
                        {{ $visitorSite->name }} — {{ implode(', ', $visitorSite->allowed_domains ?? []) }}
                    </option>
                @endforeach
            </select>
            @if($settings->visitorTrackingPayload()['enabled'])
                <div class="mt-3">
                    <label class="form-label">قيمة <code>body</code> الحالية</label>
                    <textarea class="form-control" rows="5" dir="ltr" readonly>{{ $settings->seoBodyCode() }}</textarea>
                    <div class="form-text">تجمع تلقائيًا كود الفوتر/Body وكود Live Chat وكود تتبع الزوار المختار، وتتوفر داخل <code>seo.body</code> و<code>body</code> في API الإعدادات.</div>
                </div>
            @endif
        </div>

        <div class="col-12">
            <hr>
        </div>

        <div class="col-12">
            <h6 class="text-muted mb-2">{{ __('admin.settings_tag_manager') }}</h6>
            <p class="text-muted small mb-3">{{ __('admin.settings_tag_manager_hint') }}</p>
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">{{ __('admin.settings_tag_manager_head') }}</label>
                    <textarea name="tag_manager_head" class="form-control" rows="8" dir="ltr"
                              placeholder="<!-- Google Tag Manager head code -->">{{ old('tag_manager_head', $settings->tag_manager_head ?? '') }}</textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">{{ __('admin.settings_tag_manager_footer') }}</label>
                    <textarea name="tag_manager_footer" class="form-control" rows="8" dir="ltr"
                              placeholder="<!-- Google Tag Manager body/footer code -->">{{ old('tag_manager_footer', $settings->tag_manager_footer ?? '') }}</textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">{{ __('admin.settings_tiktok_tag') }}</label>
                    <textarea name="tiktok_tag" class="form-control" rows="6" dir="ltr"
                              placeholder="<!-- TikTok Pixel code -->">{{ old('tiktok_tag', $settings->tiktok_tag ?? '') }}</textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">{{ __('admin.settings_snapchat_tag') }}</label>
                    <textarea name="snapchat_tag" class="form-control" rows="6" dir="ltr"
                              placeholder="<!-- Snapchat Pixel code -->">{{ old('snapchat_tag', $settings->snapchat_tag ?? '') }}</textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">{{ __('admin.settings_zoho_tag') }}</label>
                    <textarea name="zoho_tag" class="form-control" rows="6" dir="ltr"
                              placeholder="<!-- Zoho code -->">{{ old('zoho_tag', $settings->zoho_tag ?? '') }}</textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">{{ __('admin.settings_hotjar_tag') }}</label>
                    <textarea name="hotjar_tag" class="form-control" rows="6" dir="ltr"
                              placeholder="<!-- Hotjar code -->">{{ old('hotjar_tag', $settings->hotjar_tag ?? '') }}</textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">{{ __('admin.settings_meta_pixel_tag') }}</label>
                    <textarea name="meta_pixel_tag" class="form-control" rows="6" dir="ltr"
                              placeholder="<!-- Meta Pixel code -->">{{ old('meta_pixel_tag', $settings->meta_pixel_tag ?? '') }}</textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">{{ __('admin.settings_live_chat_tag') }}</label>
                    <textarea name="live_chat_tag" class="form-control" rows="6" dir="ltr"
                              placeholder="<!-- Live chat widget code -->">{{ old('live_chat_tag', $settings->live_chat_tag ?? '') }}</textarea>
                </div>
            </div>
        </div>

        <div class="col-12 text-end">
            @if (@AdminGuardHasAnyPerm([\App\PermKeys::SETTINGS_CONTROL, \App\PermKeys::WEBSITE_SETTINGS_CONTROL]))
                <button class="btn btn-primary" type="submit">{{ __('buttons.save') }}</button>
            @endif
        </div>
    </form>
@endsection
