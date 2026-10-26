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
                                {{ __('find_us.map_input_label') }}
                            </label>
                            <button type="button" id="pasteMapCode" class="btn btn-sm btn-outline-primary">
                                <i class="fa fa-clipboard me-1"></i> {{ __('find_us.paste') }}
                            </button>
                        </div>
                        <textarea id="mapEmbedInput" name="map_embed_url" rows="5"
                                  class="form-control font-monospace @error('map_embed_url') is-invalid @enderror"
                                  placeholder="{{ __('find_us.map_placeholder') }}">{{ old('map_embed_url', $location->map_embed_url) }}</textarea>
                        @error('map_embed_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text"><i class="fa fa-circle-info me-1"></i>{{ __('find_us.map_or_coordinates_hint') }}</div>
                        <div class="form-text"><i class="fa fa-shield-halved me-1"></i>{{ __('find_us.security_hint') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card find-us-form-card mt-4">
            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between gap-2 flex-wrap">
                <strong><i class="fa fa-location-crosshairs me-1 text-primary"></i> {{ __('find_us.coordinates_title') }}</strong>
                <div class="d-flex gap-2">
                    <button type="button" id="useMyLocation" class="btn btn-sm btn-outline-primary">
                        <i class="fa fa-location-arrow me-1"></i> {{ __('find_us.use_my_location') }}
                    </button>
                    <button type="button" id="clearCoordinates" class="btn btn-sm btn-outline-secondary">
                        <i class="fa fa-eraser me-1"></i> {{ __('find_us.clear_coordinates') }}
                    </button>
                </div>
            </div>
            <div class="card-body p-4">
                <p class="text-muted small">{{ __('find_us.coordinates_hint') }}</p>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="findUsLatitude">{{ __('find_us.latitude') }}</label>
                        <input type="number" step="0.0000001" min="-90" max="90" id="findUsLatitude" name="latitude"
                               value="{{ old('latitude', $location->latitude) }}" dir="ltr" placeholder="24.7135517"
                               class="form-control @error('latitude') is-invalid @enderror">
                        @error('latitude') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="findUsLongitude">{{ __('find_us.longitude') }}</label>
                        <input type="number" step="0.0000001" min="-180" max="180" id="findUsLongitude" name="longitude"
                               value="{{ old('longitude', $location->longitude) }}" dir="ltr" placeholder="46.6752957"
                               class="form-control @error('longitude') is-invalid @enderror">
                        @error('longitude') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div id="findUsPickerMap" class="find-us-picker-map mt-3"></div>
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
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
    .find-us-picker-map { width: 100%; height: 340px; border: 1px solid #d8dee4; border-radius: 12px; }
</style>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    (function () {
        const input = document.getElementById('mapEmbedInput');
        const frame = document.getElementById('mapPreviewFrame');
        const message = document.getElementById('mapPreviewMessage');
        const pasteButton = document.getElementById('pasteMapCode');
        const form = input.closest('form');
        const latInput = document.getElementById('findUsLatitude');
        const lngInput = document.getElementById('findUsLongitude');
        const DEFAULT_CENTER = [24.7135517, 46.6752957];

        function coordinatesEmbedUrl(lat, lng) {
            return 'https://maps.google.com/maps?q=' + lat.toFixed(7) + ',' + lng.toFixed(7) + '&z=16&hl=ar&output=embed';
        }

        // نفس شرط FindUsLocation::isCoordinatesEmbedUrl في الخادم
        function isCoordinatesEmbed(url) {
            try {
                const parsed = new URL(url);
                return parsed.hostname.toLowerCase() === 'maps.google.com' &&
                    parsed.pathname.replace(/\/+$/, '') === '/maps' &&
                    parsed.searchParams.get('output') === 'embed';
            } catch (error) {
                return false;
            }
        }

        function readCoordinates() {
            const lat = parseFloat(latInput.value);
            const lng = parseFloat(lngInput.value);
            if (Number.isNaN(lat) || Number.isNaN(lng) || Math.abs(lat) > 90 || Math.abs(lng) > 180) {
                return null;
            }
            return { lat: lat, lng: lng };
        }

        // لا نستبدل كود تضمين لصقه المستخدم يدويًا؛ نحدّث فقط الحقل الفارغ أو المولَّد من الإحداثيات
        function syncEmbedFromCoordinates() {
            const current = extractUrl(input.value);
            if (current !== '' && !isCoordinatesEmbed(current)) {
                return;
            }
            const point = readCoordinates();
            input.value = point ? coordinatesEmbedUrl(point.lat, point.lng) : '';
            updatePreview();
        }

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
            if (isCoordinatesEmbed(url)) {
                return true;
            }
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

        let marker = null;
        let pickerMap = null;

        function setCoordinates(lat, lng) {
            latInput.value = Number(lat).toFixed(7);
            lngInput.value = Number(lng).toFixed(7);
            placeMarker();
            syncEmbedFromCoordinates();
        }

        function placeMarker() {
            if (!pickerMap) {
                return;
            }
            const point = readCoordinates();
            if (!point) {
                if (marker) {
                    pickerMap.removeLayer(marker);
                    marker = null;
                }
                return;
            }
            if (!marker) {
                marker = L.marker([point.lat, point.lng], { draggable: true }).addTo(pickerMap);
                marker.on('dragend', function () {
                    const latlng = marker.getLatLng();
                    setCoordinates(latlng.lat, latlng.lng);
                });
            } else {
                marker.setLatLng([point.lat, point.lng]);
            }
            pickerMap.setView([point.lat, point.lng], Math.max(pickerMap.getZoom(), 15));
        }

        if (typeof L !== 'undefined') {
            const initial = readCoordinates();
            pickerMap = L.map('findUsPickerMap').setView(initial ? [initial.lat, initial.lng] : DEFAULT_CENTER, initial ? 15 : 11);
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                // خوادم OSM ترفض الطلبات بدون Referer (403 Access blocked)
                referrerPolicy: 'strict-origin-when-cross-origin',
                attribution: '&copy; OpenStreetMap'
            }).addTo(pickerMap);
            pickerMap.on('click', function (event) {
                setCoordinates(event.latlng.lat, event.latlng.lng);
            });
            placeMarker();
        } else {
            document.getElementById('findUsPickerMap').classList.add('d-none');
        }

        [latInput, lngInput].forEach(function (field) {
            field.addEventListener('input', function () {
                placeMarker();
                syncEmbedFromCoordinates();
            });
        });

        document.getElementById('useMyLocation').addEventListener('click', function () {
            if (!navigator.geolocation) {
                alert(@js(__('find_us.geolocation_failed')));
                return;
            }
            navigator.geolocation.getCurrentPosition(function (position) {
                setCoordinates(position.coords.latitude, position.coords.longitude);
            }, function () {
                alert(@js(__('find_us.geolocation_failed')));
            }, { enableHighAccuracy: true, timeout: 10000 });
        });

        document.getElementById('clearCoordinates').addEventListener('click', function () {
            latInput.value = '';
            lngInput.value = '';
            placeMarker();
            syncEmbedFromCoordinates();
        });

        updatePreview();
    })();
</script>
@endpush
