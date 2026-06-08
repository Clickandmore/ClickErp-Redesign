@php
    $workingDays = [
        'saturday' => 'السبت',
        'sunday' => 'الأحد',
        'monday' => 'الإثنين',
        'tuesday' => 'الثلاثاء',
        'wednesday' => 'الأربعاء',
        'thursday' => 'الخميس',
        'friday' => 'الجمعة',
    ];

    $hours = old('working_hours', $branch->working_hours ?? []);
    $defaultLatitude = old('latitude', $branch->latitude ?? '24.7135517');
    $defaultLongitude = old('longitude', $branch->longitude ?? '46.6752957');
    $zohoSettings = $branch->zoho_salesiq_settings ?? [];
@endphp

<div class="row">
    <div class="col-12">
        <h5 class="mb-3">البيانات الأساسية</h5>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label for="code">كود الفرع</label>
            <input type="text" class="form-control @error('code') is-invalid @enderror"
                   id="code" name="code" value="{{ old('code', $branch->code ?? '') }}"
                   placeholder="يتم إنشاؤه تلقائيًا" readonly>
            @error('code')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label for="name_ar">اسم الفرع بالعربية <span class="text-danger">*</span></label>
            <input type="text" class="form-control @error('name_ar') is-invalid @enderror"
                   id="name_ar" name="name_ar" value="{{ old('name_ar', $branch->name_ar ?? '') }}" required>
            @error('name_ar')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label for="name_en">اسم الفرع بالإنجليزية</label>
            <input type="text" class="form-control @error('name_en') is-invalid @enderror"
                   id="name_en" name="name_en" value="{{ old('name_en', $branch->name_en ?? '') }}">
            @error('name_en')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-3">
        <div class="form-group">
            <label for="manager_name">مدير الفرع</label>
            <input type="text" class="form-control @error('manager_name') is-invalid @enderror"
                   id="manager_name" name="manager_name" value="{{ old('manager_name', $branch->manager_name ?? '') }}">
            @error('manager_name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-3">
        <div class="form-group">
            <label for="phone">رقم الجوال</label>
            <input type="text" class="form-control @error('phone') is-invalid @enderror"
                   id="phone" name="phone" value="{{ old('phone', $branch->phone ?? '') }}">
            @error('phone')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-3">
        <div class="form-group">
            <label for="whatsapp">واتساب</label>
            <input type="text" class="form-control @error('whatsapp') is-invalid @enderror"
                   id="whatsapp" name="whatsapp" value="{{ old('whatsapp', $branch->whatsapp ?? '') }}">
            @error('whatsapp')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-3">
        <div class="form-group">
            <label for="email">البريد الإلكتروني</label>
            <input type="email" class="form-control @error('email') is-invalid @enderror"
                   id="email" name="email" value="{{ old('email', $branch->email ?? '') }}">
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-12 mt-3">
        <h5 class="mb-3">العنوان والموقع</h5>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label for="city">المدينة</label>
            <input type="text" class="form-control @error('city') is-invalid @enderror"
                   id="city" name="city" value="{{ old('city', $branch->city ?? '') }}">
            @error('city')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label for="district">الحي</label>
            <input type="text" class="form-control @error('district') is-invalid @enderror"
                   id="district" name="district" value="{{ old('district', $branch->district ?? '') }}">
            @error('district')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label for="postal_code">الرمز البريدي</label>
            <input type="text" class="form-control @error('postal_code') is-invalid @enderror"
                   id="postal_code" name="postal_code" value="{{ old('postal_code', $branch->postal_code ?? '') }}">
            @error('postal_code')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-12">
        <div class="form-group">
            <label for="address">العنوان التفصيلي</label>
            <textarea class="form-control @error('address') is-invalid @enderror"
                      id="address" name="address" rows="3">{{ old('address', $branch->address ?? '') }}</textarea>
            @error('address')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            <label for="latitude">خط العرض</label>
            <input type="number" step="0.0000001" class="form-control @error('latitude') is-invalid @enderror"
                   id="latitude" name="latitude" value="{{ $defaultLatitude }}">
            @error('latitude')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            <label for="longitude">خط الطول</label>
            <input type="number" step="0.0000001" class="form-control @error('longitude') is-invalid @enderror"
                   id="longitude" name="longitude" value="{{ $defaultLongitude }}">
            @error('longitude')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-12">
        <div id="branch-map" class="branch-map"></div>
    </div>

    <div class="col-12 mt-3">
        <h5 class="mb-3">ساعات العمل</h5>
    </div>

    @foreach ($workingDays as $dayKey => $dayLabel)
        @php
            $dayHours = $hours[$dayKey] ?? [];
            $isClosed = (bool) ($dayHours['is_closed'] ?? false);
            $from = $dayHours['from'] ?? '09:00';
            $to = $dayHours['to'] ?? '18:00';
        @endphp
        <div class="col-lg-6">
            <div class="branch-hours-row">
                <strong>{{ $dayLabel }}</strong>
                <input type="hidden" name="working_hours[{{ $dayKey }}][is_closed]" value="0">
                <label class="branch-hours-closed">
                    <input type="checkbox" name="working_hours[{{ $dayKey }}][is_closed]" value="1" {{ $isClosed ? 'checked' : '' }}>
                    مغلق
                </label>
                <input type="time" class="form-control" name="working_hours[{{ $dayKey }}][from]" value="{{ $from }}">
                <span>إلى</span>
                <input type="time" class="form-control" name="working_hours[{{ $dayKey }}][to]" value="{{ $to }}">
            </div>
        </div>
    @endforeach

    <div class="col-12 mt-3">
        <h5 class="mb-1">إعدادات Zoho SalesIQ</h5>
        <small class="text-muted d-block mb-3">
            اترك الحقول فارغة لاستخدام الإعداد العام. في التعديل، الحقول السرية الفارغة تحتفظ بالقيمة المحفوظة.
        </small>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label for="zoho_screen_name">Portal / Screen Name</label>
            <input type="text" class="form-control @error('zoho_salesiq_settings.screen_name') is-invalid @enderror"
                   id="zoho_screen_name" name="zoho_salesiq_settings[screen_name]"
                   value="{{ old('zoho_salesiq_settings.screen_name', data_get($zohoSettings, 'screen_name', '')) }}">
            @error('zoho_salesiq_settings.screen_name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label for="zoho_salesiq_base_url">SalesIQ Base URL</label>
            <input type="url" class="form-control @error('zoho_salesiq_settings.salesiq_base_url') is-invalid @enderror"
                   id="zoho_salesiq_base_url" name="zoho_salesiq_settings[salesiq_base_url]"
                   value="{{ old('zoho_salesiq_settings.salesiq_base_url', data_get($zohoSettings, 'salesiq_base_url', '')) }}"
                   placeholder="https://salesiq.zoho.sa">
            @error('zoho_salesiq_settings.salesiq_base_url')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label for="zoho_accounts_base_url">Accounts Base URL</label>
            <input type="url" class="form-control @error('zoho_salesiq_settings.accounts_base_url') is-invalid @enderror"
                   id="zoho_accounts_base_url" name="zoho_salesiq_settings[accounts_base_url]"
                   value="{{ old('zoho_salesiq_settings.accounts_base_url', data_get($zohoSettings, 'accounts_base_url', '')) }}"
                   placeholder="https://accounts.zoho.sa">
            @error('zoho_salesiq_settings.accounts_base_url')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label for="zoho_client_id">Client ID</label>
            <input type="text" class="form-control @error('zoho_salesiq_settings.client_id') is-invalid @enderror"
                   id="zoho_client_id" name="zoho_salesiq_settings[client_id]"
                   value="{{ old('zoho_salesiq_settings.client_id', data_get($zohoSettings, 'client_id', '')) }}">
            @error('zoho_salesiq_settings.client_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label for="zoho_client_secret">Client Secret</label>
            <input type="password" class="form-control @error('zoho_salesiq_settings.client_secret') is-invalid @enderror"
                   id="zoho_client_secret" name="zoho_salesiq_settings[client_secret]"
                   value="{{ old('zoho_salesiq_settings.client_secret') }}"
                   placeholder="{{ data_get($zohoSettings, 'client_secret') ? 'محفوظ - اتركه فارغا للإبقاء عليه' : '' }}">
            @error('zoho_salesiq_settings.client_secret')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label for="zoho_refresh_token">Refresh Token</label>
            <input type="password" class="form-control @error('zoho_salesiq_settings.refresh_token') is-invalid @enderror"
                   id="zoho_refresh_token" name="zoho_salesiq_settings[refresh_token]"
                   value="{{ old('zoho_salesiq_settings.refresh_token') }}"
                   placeholder="{{ data_get($zohoSettings, 'refresh_token') ? 'محفوظ - اتركه فارغا للإبقاء عليه' : '' }}">
            @error('zoho_salesiq_settings.refresh_token')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-8">
        <div class="form-group">
            <label for="zoho_widget_url">Widget URL</label>
            <input type="url" class="form-control @error('zoho_salesiq_settings.widget_url') is-invalid @enderror"
                   id="zoho_widget_url" name="zoho_salesiq_settings[widget_url]"
                   value="{{ old('zoho_salesiq_settings.widget_url', data_get($zohoSettings, 'widget_url', '')) }}"
                   placeholder="https://salesiq.zohopublic.sa/widget?wc=...">
            @error('zoho_salesiq_settings.widget_url')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label for="zoho_timeout">Timeout (seconds)</label>
            <input type="number" min="5" max="120" class="form-control @error('zoho_salesiq_settings.timeout') is-invalid @enderror"
                   id="zoho_timeout" name="zoho_salesiq_settings[timeout]"
                   value="{{ old('zoho_salesiq_settings.timeout', data_get($zohoSettings, 'timeout', '')) }}"
                   placeholder="20">
            @error('zoho_salesiq_settings.timeout')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-12 mt-3">
        <h5 class="mb-3">تفاصيل إضافية</h5>
    </div>

    <div class="col-md-8">
        <div class="form-group">
            <label for="description">وصف الفرع</label>
            <textarea class="form-control @error('description') is-invalid @enderror"
                      id="description" name="description" rows="3">{{ old('description', $branch->description ?? '') }}</textarea>
            @error('description')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-2">
        <div class="form-group">
            <label for="sort_order">ترتيب العرض</label>
            <input type="number" class="form-control @error('sort_order') is-invalid @enderror"
                   id="sort_order" name="sort_order" value="{{ old('sort_order', $branch->sort_order ?? 0) }}">
            @error('sort_order')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-2">
        <div class="form-group">
            <input type="hidden" name="is_active" value="0">
            <div class="custom-control custom-switch mt-4">
                <input type="checkbox" class="custom-control-input" id="is_active"
                       name="is_active" value="1" {{ old('is_active', $branch->is_active ?? true) ? 'checked' : '' }}>
                <label class="custom-control-label" for="is_active">نشط</label>
            </div>
        </div>
    </div>
</div>

<div class="form-group mt-4">
    <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
    <a href="{{ route('branches.index') }}" class="btn btn-secondary">إلغاء</a>
</div>

@push('style')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <style>
        .branch-map {
            width: 100%;
            height: 360px;
            border: 1px solid #d8dee4;
            border-radius: 8px;
            margin-top: 8px;
            margin-bottom: 16px;
        }
        .branch-hours-row {
            display: grid;
            grid-template-columns: 86px 72px 1fr 32px 1fr;
            align-items: center;
            gap: 8px;
            padding: 10px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 10px;
            background: #fff;
        }
        .branch-hours-closed {
            display: flex;
            align-items: center;
            gap: 5px;
            margin: 0;
            white-space: nowrap;
        }
        @media (max-width: 575px) {
            .branch-hours-row {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
@endpush

@push('js')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof L === 'undefined') {
                return;
            }

            const latInput = document.getElementById('latitude');
            const lngInput = document.getElementById('longitude');
            const initialLat = parseFloat(latInput.value || '24.7135517');
            const initialLng = parseFloat(lngInput.value || '46.6752957');

            const map = L.map('branch-map').setView([initialLat, initialLng], 12);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap'
            }).addTo(map);

            const marker = L.marker([initialLat, initialLng], { draggable: true }).addTo(map);

            function updateInputs(latlng) {
                latInput.value = Number(latlng.lat).toFixed(7);
                lngInput.value = Number(latlng.lng).toFixed(7);
            }

            marker.on('dragend', function () {
                updateInputs(marker.getLatLng());
            });

            map.on('click', function (event) {
                marker.setLatLng(event.latlng);
                updateInputs(event.latlng);
            });

            [latInput, lngInput].forEach(function (input) {
                input.addEventListener('change', function () {
                    const lat = parseFloat(latInput.value);
                    const lng = parseFloat(lngInput.value);
                    if (!Number.isNaN(lat) && !Number.isNaN(lng)) {
                        marker.setLatLng([lat, lng]);
                        map.setView([lat, lng], map.getZoom());
                    }
                });
            });

            document.querySelectorAll('.branch-hours-closed input[type="checkbox"]').forEach(function (checkbox) {
                function syncRow() {
                    const row = checkbox.closest('.branch-hours-row');
                    row.querySelectorAll('input[type="time"]').forEach(function (timeInput) {
                        timeInput.disabled = checkbox.checked;
                    });
                }

                checkbox.addEventListener('change', syncRow);
                syncRow();
            });
        });
    </script>
@endpush
