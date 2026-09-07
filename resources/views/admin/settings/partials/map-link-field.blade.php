@php
    $mapLinkValue = $settings->map_link ?? '';
    $mapLinkMessages = [
        'empty' => __('admin.settings_map_status_empty'),
        'valid' => __('admin.settings_map_status_valid'),
        'share' => __('admin.settings_map_status_share_link'),
        'other' => __('admin.settings_map_status_other_url'),
        'invalid' => __('admin.settings_map_status_invalid'),
        'preview_empty' => __('admin.settings_map_preview_empty'),
    ];
@endphp

<style>
    .map-embed-field {
        border: 1px solid var(--bs-border-color, #e6e9f0);
        border-radius: 16px;
        background: linear-gradient(180deg, rgba(var(--bs-primary-rgb, 13, 110, 253), 0.04) 0%, transparent 140px);
        padding: 1.25rem;
    }

    .map-embed-field__head {
        display: flex;
        align-items: flex-start;
        gap: 0.85rem;
        margin-bottom: 1rem;
    }

    .map-embed-field__icon {
        flex: 0 0 auto;
        width: 42px;
        height: 42px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        color: #fff;
        background: var(--bs-primary, #0d6efd);
        box-shadow: 0 8px 18px rgba(var(--bs-primary-rgb, 13, 110, 253), 0.25);
    }

    .map-embed-field__steps {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(215px, 1fr));
        gap: 0.65rem;
        margin-bottom: 1rem;
    }

    .map-embed-field__step {
        display: flex;
        align-items: flex-start;
        gap: 0.6rem;
        padding: 0.7rem 0.8rem;
        border: 1px solid var(--bs-border-color, #e6e9f0);
        border-radius: 12px;
        background: #fff;
        font-size: 0.85rem;
        line-height: 1.6;
    }

    .map-embed-field__step-num {
        flex: 0 0 auto;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.78rem;
        font-weight: 700;
        color: var(--bs-primary, #0d6efd);
        background: rgba(var(--bs-primary-rgb, 13, 110, 253), 0.12);
    }

    .map-embed-field__step code {
        background: rgba(var(--bs-primary-rgb, 13, 110, 253), 0.08);
        border-radius: 6px;
        padding: 0 0.25rem;
        color: inherit;
    }

    .map-embed-field__example {
        border: 1px dashed var(--bs-border-color, #e6e9f0);
        border-radius: 12px;
        background: #f8f9fb;
        padding: 0.7rem 0.85rem;
        margin-bottom: 1rem;
    }

    .map-embed-field__example pre {
        direction: ltr;
        text-align: left;
        margin: 0;
        white-space: pre-wrap;
        word-break: break-all;
        font-size: 0.74rem;
        color: #55607a;
    }

    .map-embed-field textarea.map-embed-field__input {
        direction: ltr;
        text-align: left;
        font-size: 0.82rem;
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        border-radius: 12px;
    }

    .map-embed-field__status {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        border-radius: 999px;
        padding: 0.3rem 0.75rem;
        font-size: 0.78rem;
        font-weight: 600;
    }

    .map-embed-field__status[data-state="empty"] {
        background: rgba(108, 117, 125, 0.12);
        color: #55607a;
    }

    .map-embed-field__status[data-state="valid"] {
        background: rgba(25, 135, 84, 0.12);
        color: #157347;
    }

    .map-embed-field__status[data-state="warning"] {
        background: rgba(255, 193, 7, 0.18);
        color: #997404;
    }

    .map-embed-field__status[data-state="invalid"] {
        background: rgba(220, 53, 69, 0.12);
        color: #b02a37;
    }

    .map-embed-field__preview {
        border: 1px solid var(--bs-border-color, #e6e9f0);
        border-radius: 12px;
        overflow: hidden;
        background: #f8f9fb;
        min-height: 220px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .map-embed-field__preview iframe {
        display: block;
        width: 100%;
        height: 260px;
        border: 0;
    }

    .map-embed-field__preview-empty {
        color: #8a93a8;
        font-size: 0.85rem;
        text-align: center;
        padding: 1.5rem;
    }
</style>

<div class="map-embed-field" id="mapEmbedField">
    <div class="map-embed-field__head">
        <span class="map-embed-field__icon"><i class="fa fa-map-marker"></i></span>
        <div class="flex-grow-1">
            <h6 class="mb-1 fw-bold">{{ __('admin.settings_map_link') }}</h6>
            <p class="text-muted small mb-0">{{ __('admin.settings_map_link_subtitle') }}</p>
        </div>
        <a href="https://www.google.com/maps" target="_blank" rel="noopener"
           class="btn btn-sm btn-outline-primary flex-shrink-0">
            <i class="fa fa-external-link me-1"></i>{{ __('admin.settings_map_open_google_maps') }}
        </a>
    </div>

    @php
        // القسم يظهر بعد تشغيل الترحيل الذي يضيف العمود، حتى لا تنكسر النسخ غير المُرحّلة.
        $findUsModeAvailable = \Illuminate\Support\Facades\Schema::hasColumn('settings', 'find_us_mode');
        $findUsMode = old(
            'find_us_mode',
            ($findUsModeAvailable ? $settings->find_us_mode : null) ?: \App\Support\FindUsSettings::MODE_SINGLE
        );
    @endphp

    @if ($findUsModeAvailable)
        <div class="row g-2 mb-3">
            <div class="col-12">
                <div class="small fw-semibold">{{ __('admin.settings_find_us_mode') }}</div>
                <div class="text-muted small mb-1">{{ __('admin.settings_find_us_mode_hint') }}</div>
            </div>

            <div class="col-md-6">
                <label class="card h-100 border p-3 mb-0" for="find_us_mode_single" style="cursor: pointer;">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="find_us_mode" id="find_us_mode_single"
                               value="{{ \App\Support\FindUsSettings::MODE_SINGLE }}"
                               {{ $findUsMode === \App\Support\FindUsSettings::MODE_SINGLE ? 'checked' : '' }}>
                        <span class="form-check-label fw-bold">{{ __('admin.settings_find_us_mode_single') }}</span>
                    </div>
                    <small class="text-muted d-block mt-2">{{ __('admin.settings_find_us_mode_single_hint') }}</small>
                </label>
            </div>

            <div class="col-md-6">
                <label class="card h-100 border p-3 mb-0" for="find_us_mode_branches" style="cursor: pointer;">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="find_us_mode" id="find_us_mode_branches"
                               value="{{ \App\Support\FindUsSettings::MODE_BRANCHES }}"
                               {{ $findUsMode === \App\Support\FindUsSettings::MODE_BRANCHES ? 'checked' : '' }}>
                        <span class="form-check-label fw-bold">{{ __('admin.settings_find_us_mode_branches') }}</span>
                    </div>
                    <small class="text-muted d-block mt-2">{{ __('admin.settings_find_us_mode_branches_hint') }}</small>
                    @if (@AdminGuardHasAnyPerm([\App\PermKeys::FIND_US_VIEW, \App\PermKeys::FIND_US_CONTROL]))
                        <a href="{{ route('find-us.index') }}" class="small mt-2">
                            <i class="fa fa-map-location-dot me-1"></i>{{ __('admin.settings_find_us_mode_manage_branches') }}
                        </a>
                    @endif
                </label>
            </div>
        </div>
    @endif

    <div class="map-embed-field__steps">
        <div class="map-embed-field__step">
            <span class="map-embed-field__step-num">1</span>
            <div>{{ __('admin.settings_map_step_1') }}</div>
        </div>
        <div class="map-embed-field__step">
            <span class="map-embed-field__step-num">2</span>
            <div>{{ __('admin.settings_map_step_2') }}</div>
        </div>
        <div class="map-embed-field__step">
            <span class="map-embed-field__step-num">3</span>
            <div>{{ __('admin.settings_map_step_3') }}</div>
        </div>
        <div class="map-embed-field__step">
            <span class="map-embed-field__step-num">4</span>
            <div>{{ __('admin.settings_map_step_4') }}</div>
        </div>
    </div>

    <div class="alert alert-warning d-flex align-items-start gap-2 py-2 px-3 small mb-3">
        <i class="fa fa-exclamation-triangle mt-1"></i>
        <span>{{ __('admin.settings_map_share_link_warning') }}</span>
    </div>

    <div class="map-embed-field__example">
        <div class="small fw-semibold text-muted mb-1">{{ __('admin.settings_map_example_title') }}</div>
        <pre>{{ '<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3624.4..." width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy"></iframe>' }}</pre>
    </div>

    <label class="form-label fw-semibold" for="mapEmbedInput">{{ __('admin.settings_map_paste_label') }}</label>
    <textarea name="map_link" id="mapEmbedInput" rows="3"
              class="form-control map-embed-field__input"
              placeholder="{{ __('admin.settings_map_placeholder') }}">{{ $mapLinkValue }}</textarea>
    <div class="form-text">{{ __('admin.settings_map_auto_extract_hint') }}</div>

    <div class="d-flex flex-wrap align-items-center gap-2 mt-3 mb-3">
        <span class="map-embed-field__status" id="mapEmbedStatus" data-state="empty">
            <i class="fa fa-info-circle"></i>
            <span id="mapEmbedStatusText">{{ __('admin.settings_map_status_empty') }}</span>
        </span>
        <div class="ms-auto d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="mapEmbedRefresh">
                <i class="fa fa-refresh me-1"></i>{{ __('admin.settings_map_refresh_preview') }}
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger" id="mapEmbedClear">
                <i class="fa fa-trash me-1"></i>{{ __('admin.settings_map_clear') }}
            </button>
        </div>
    </div>

    <div class="small fw-semibold text-muted mb-2">{{ __('admin.settings_map_preview_title') }}</div>
    <div class="map-embed-field__preview" id="mapEmbedPreview">
        <div class="map-embed-field__preview-empty">
            <i class="fa fa-map-o fa-2x d-block mb-2"></i>
            {{ __('admin.settings_map_preview_empty') }}
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const input = document.getElementById('mapEmbedInput');
        if (!input) {
            return;
        }

        const status = document.getElementById('mapEmbedStatus');
        const statusText = document.getElementById('mapEmbedStatusText');
        const preview = document.getElementById('mapEmbedPreview');
        const messages = @json($mapLinkMessages);

        // يقبل الحقل كود iframe كاملاً أو الرابط وحده — هنا نستخرج قيمة src دائماً.
        function extractSrc(raw) {
            const value = (raw || '').trim();
            if (!value) {
                return '';
            }
            const match = value.match(/<iframe[^>]*\ssrc\s*=\s*["']([^"']+)["']/i);
            return (match ? match[1] : value).trim();
        }

        function classify(url) {
            if (!url) {
                return 'empty';
            }
            if (!/^https?:\/\//i.test(url)) {
                return 'invalid';
            }
            if (/^https?:\/\/(www\.)?google\.[a-z.]+\/maps\/embed/i.test(url)) {
                return 'valid';
            }
            if (/(maps\.app\.goo\.gl|goo\.gl\/maps|google\.[a-z.]+\/maps)/i.test(url)) {
                return 'share';
            }
            return 'other';
        }

        function renderPreview(url, state) {
            preview.innerHTML = '';
            if (state === 'valid' || state === 'other') {
                const frame = document.createElement('iframe');
                frame.setAttribute('loading', 'lazy');
                frame.setAttribute('referrerpolicy', 'no-referrer-when-downgrade');
                frame.setAttribute('allowfullscreen', '');
                frame.src = url;
                preview.appendChild(frame);
                return;
            }
            const empty = document.createElement('div');
            empty.className = 'map-embed-field__preview-empty';
            empty.textContent = messages.preview_empty;
            preview.appendChild(empty);
        }

        function sync(updatePreview) {
            const url = extractSrc(input.value);
            if (url !== input.value.trim()) {
                input.value = url;
            }
            const state = classify(url);
            status.dataset.state = state === 'share' || state === 'other' ? 'warning' : state;
            statusText.textContent = messages[state] || messages.invalid;
            if (updatePreview) {
                renderPreview(url, state);
            }
        }

        input.addEventListener('input', function () {
            sync(false);
        });
        input.addEventListener('change', function () {
            sync(true);
        });
        input.addEventListener('paste', function () {
            setTimeout(function () {
                sync(true);
            }, 0);
        });

        document.getElementById('mapEmbedRefresh').addEventListener('click', function () {
            sync(true);
        });

        document.getElementById('mapEmbedClear').addEventListener('click', function () {
            input.value = '';
            sync(true);
        });

        sync(true);
    });
</script>
