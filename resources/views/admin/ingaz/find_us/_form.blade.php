@csrf
@if($location->exists)
    @method('PUT')
@endif

<div class="row g-4">
    <div class="col-xl-7">
        <div class="card find-us-form-card">
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">{{ __('find_us.branch_name') }} <span class="text-danger">*</span></label>
                        <input name="branch_name" value="{{ old('branch_name', $location->branch_name) }}"
                               class="form-control @error('branch_name') is-invalid @enderror" required autofocus>
                        @error('branch_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('find_us.sort_order') }} <span class="text-danger">*</span></label>
                        <input type="number" min="0" max="9999" name="sort_order"
                               value="{{ old('sort_order', $location->sort_order) }}"
                               class="form-control @error('sort_order') is-invalid @enderror" required>
                        @error('sort_order') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">{{ __('find_us.branch_address') }} <span class="text-danger">*</span></label>
                        <textarea name="branch_address" rows="3"
                                  class="form-control @error('branch_address') is-invalid @enderror"
                                  required>{{ old('branch_address', $location->branch_address) }}</textarea>
                        @error('branch_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12">
                        <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                            <label class="form-label mb-0">
                                {{ __('find_us.map_input_label') }} <span class="text-danger">*</span>
                            </label>
                            <button type="button" id="pasteMapCode" class="btn btn-sm btn-outline-primary">
                                <i class="fa fa-clipboard me-1"></i> {{ __('find_us.paste') }}
                            </button>
                        </div>
                        <textarea id="mapEmbedInput" name="map_embed_url" rows="5"
                                  class="form-control font-monospace @error('map_embed_url') is-invalid @enderror"
                                  placeholder="{{ __('find_us.map_placeholder') }}"
                                  required>{{ old('map_embed_url', $location->map_embed_url) }}</textarea>
                        @error('map_embed_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text"><i class="fa fa-shield-halved me-1"></i>{{ __('find_us.security_hint') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card find-us-form-card mt-4">
            <div class="card-header bg-white py-3">
                <strong><i class="fa fa-map me-1 text-primary"></i> {{ __('find_us.preview') }}</strong>
            </div>
            <div class="card-body p-3">
                <div id="mapPreviewMessage" class="map-preview-message">{{ __('find_us.preview_empty') }}</div>
                <iframe id="mapPreviewFrame" class="w-100 d-none" src="about:blank" loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
            </div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="card find-us-guide-card">
            <div class="card-body p-4">
                <div class="guide-icon"><i class="fa fa-map-location-dot"></i></div>
                <h5>{{ __('find_us.how_to_title') }}</h5>
                <p class="text-muted">{{ __('find_us.how_to_intro') }}</p>
                <ol class="find-us-steps">
                    <li><span>1</span><div>{{ __('find_us.step_1') }}</div></li>
                    <li><span>2</span><div>{{ __('find_us.step_2') }}</div></li>
                    <li><span>3</span><div>{{ __('find_us.step_3') }}</div></li>
                    <li><span>4</span><div>{{ __('find_us.step_4') }}</div></li>
                </ol>
                <a href="https://www.google.com/maps" target="_blank" rel="noopener"
                   class="btn btn-outline-primary w-100 mt-2">
                    <i class="fa fa-arrow-up-right-from-square me-1"></i> {{ __('find_us.open_google_maps') }}
                </a>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4">
    <a href="{{ route('find-us.index') }}" class="btn btn-light px-4">{{ __('find_us.cancel') }}</a>
    <button class="btn btn-primary px-5"><i class="fa fa-check me-1"></i> {{ __('find_us.save') }}</button>
</div>

@push('scripts')
<script>
    (function () {
        const input = document.getElementById('mapEmbedInput');
        const frame = document.getElementById('mapPreviewFrame');
        const message = document.getElementById('mapPreviewMessage');
        const pasteButton = document.getElementById('pasteMapCode');
        const form = input.closest('form');

        function decodeEntities(value) {
            const area = document.createElement('textarea');
            area.innerHTML = value;
            return area.value;
        }

        function extractUrl(value) {
            value = (value || '').trim();
            const match = value.match(/<iframe\b[^>]*\bsrc\s*=\s*(["'])(.*?)\1/is);
            return decodeEntities(match && match[2] ? match[2] : value).trim();
        }

        function isSafeGoogleEmbed(url) {
            try {
                const parsed = new URL(url);
                return parsed.protocol === 'https:' &&
                    /(^|\.)google\.[a-z.]+$/i.test(parsed.hostname) &&
                    parsed.pathname.toLowerCase().includes('/maps/embed');
            } catch (error) {
                return false;
            }
        }

        function updatePreview() {
            const value = input.value.trim();
            const url = extractUrl(value);

            if (!value) {
                frame.src = 'about:blank';
                frame.classList.add('d-none');
                message.className = 'map-preview-message';
                message.textContent = @js(__('find_us.preview_empty'));
                return;
            }

            if (!isSafeGoogleEmbed(url)) {
                frame.src = 'about:blank';
                frame.classList.add('d-none');
                message.className = 'map-preview-message is-invalid';
                message.textContent = @js(__('find_us.preview_invalid'));
                return;
            }

            frame.src = url;
            frame.classList.remove('d-none');
            message.className = 'map-preview-message is-valid';
            message.textContent = @js(__('find_us.preview_ready'));
        }

        input.addEventListener('input', updatePreview);
        input.addEventListener('paste', function () { setTimeout(updatePreview, 0); });

        pasteButton.addEventListener('click', async function () {
            if (!navigator.clipboard) {
                input.focus();
                return;
            }
            try {
                input.value = await navigator.clipboard.readText();
                updatePreview();
            } catch (error) {
                input.focus();
            }
        });

        form.addEventListener('submit', function () {
            input.value = extractUrl(input.value);
        });

        updatePreview();
    })();
</script>
@endpush
