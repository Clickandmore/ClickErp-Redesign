{{-- عناوين العميل: نفس جدول user_addresses، مع حقول الساعات (الحي والإحداثيات وجهة التواصل) وتحديد الموقع على الخريطة. --}}
@php
    $c = fn (string $key, array $replace = []) => __('hourly_customers.'.$key, $replace);
    $cityNames = $cities->mapWithKeys(fn ($city) => [$city->id => $city->name]);
    $districtNames = $districts->mapWithKeys(fn ($d) => [$d->id => $d->name]);
@endphp
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <span class="hc-muted">{{ $addresses->count() }}</span>
    @if($canEdit)<button type="button" class="hc-btn is-green" data-address-new><i class="fa fa-plus"></i> {{ $c('addresses.add') }}</button>@endif
</div>
<div class="d-grid gap-2">
    @forelse($addresses as $address)
        <div class="hc-address">
            <div>
                <b><i class="fa fa-location-dot" style="color:var(--brand)"></i> {{ $address->address }}</b>
                <div class="hc-muted">{{ collect([$cityNames[$address->city_id] ?? null, $districtNames[$address->hourly_district_id] ?? $address->district, $address->building, $address->unit])->filter(fn ($v) => trim((string) $v) !== '')->implode(' · ') }}</div>
                @if(trim(($address->contact_first_name ?? '').($address->contact_phone ?? '')) !== '')
                    <div class="hc-muted"><i class="fa fa-user"></i> {{ trim($address->contact_first_name.' '.$address->contact_last_name) }} <span dir="ltr">{{ $address->contact_phone }}</span></div>
                @endif
            </div>
            <div class="d-flex gap-2 align-items-start">
                @if($address->latitude !== null)
                    <a class="hc-btn is-sm" href="https://www.google.com/maps/search/?api=1&query={{ $address->latitude }},{{ $address->longitude }}" target="_blank" rel="noopener"><i class="fa fa-map-location-dot"></i> {{ $c('addresses.open_map') }}</a>
                @else
                    <span class="hc-badge is-blocked">{{ $c('addresses.no_location') }}</span>
                @endif
                @if($canEdit)
                    <button type="button" class="hc-btn is-primary is-sm" data-address-edit="{{ json_encode(['url' => route('admin.hourly-customers.addresses.update', [$customer->id, $address->id]), 'city_id' => $address->city_id, 'hourly_district_id' => $address->hourly_district_id, 'address' => $address->address, 'building' => trim((string) $address->building), 'unit' => trim((string) $address->unit), 'contact_first_name' => $address->contact_first_name, 'contact_last_name' => $address->contact_last_name, 'contact_phone' => $address->contact_phone, 'contact_phone2' => $address->contact_phone2, 'latitude' => $address->latitude, 'longitude' => $address->longitude]) }}"><i class="fa fa-pen"></i> {{ $c('addresses.edit') }}</button>
                @endif
            </div>
        </div>
    @empty
        <div class="hc-empty">{{ $c('addresses.empty') }}</div>
    @endforelse
</div>

@if($canEdit)
<div class="modal fade om-modal" id="hc-address-modal" tabindex="-1" aria-labelledby="hc-address-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered"><form class="modal-content" method="POST" action="{{ route('admin.hourly-customers.addresses.store', $customer->id) }}" id="hc-address-form" data-store="{{ route('admin.hourly-customers.addresses.store', $customer->id) }}">
        @csrf
        <input type="hidden" name="_method" value="POST" id="hc-address-method">
        <div class="modal-header"><span class="om-m-icon"><i class="fa fa-location-dot"></i></span><div><h5 id="hc-address-title" data-add="{{ $c('addresses.add') }}" data-edit="{{ $c('addresses.edit') }}">{{ $c('addresses.add') }}</h5><p>{{ $customer->name }}</p></div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ $c('deposit.close') }}"></button></div>
        <div class="modal-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="hc-a-city"><i class="fa fa-city"></i> {{ $c('addresses.city') }}</label>
                    <select class="form-select" id="hc-a-city" name="city_id" required>
                        <option value="">{{ $c('form.choose') }}</option>
                        @foreach($cities as $city)<option value="{{ $city->id }}" data-lat="{{ $city->latitude }}" data-lng="{{ $city->longitude }}">{{ $city->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="hc-a-district"><i class="fa fa-map-pin"></i> {{ $c('addresses.district') }}</label>
                    <select class="form-select" id="hc-a-district" name="hourly_district_id">
                        <option value="">{{ $c('none') }}</option>
                        @foreach($districts as $district)<option value="{{ $district->id }}" data-city="{{ $district->city_id }}">{{ $district->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label for="hc-a-address"><i class="fa fa-road"></i> {{ $c('addresses.address') }}</label>
                    <input class="form-control" id="hc-a-address" name="address" maxlength="500" required>
                </div>
                @foreach(['building' => 'fa-building', 'unit' => 'fa-door-closed', 'contact_first_name' => 'fa-user', 'contact_last_name' => 'fa-user', 'contact_phone' => 'fa-phone', 'contact_phone2' => 'fa-phone'] as $field => $icon)
                    <div class="col-md-6">
                        <label for="hc-a-{{ $field }}"><i class="fa {{ $icon }}"></i> {{ $c('addresses.'.$field) }}</label>
                        <input class="form-control" id="hc-a-{{ $field }}" name="{{ $field }}" maxlength="{{ str_starts_with($field, 'contact_phone') ? 30 : 255 }}" @if(str_starts_with($field, 'contact_phone')) dir="ltr" @endif>
                    </div>
                @endforeach
                <div class="col-12">
                    <label><i class="fa fa-crosshairs"></i> {{ $c('addresses.location') }} <small>{{ $c('addresses.location_hint') }}</small></label>
                    <div id="hc-address-map"></div>
                    <div class="row g-2 mt-1">
                        <div class="col-6"><input class="form-control" id="hc-a-lat" name="latitude" type="number" step="0.0000001" min="-90" max="90" dir="ltr" placeholder="lat" required></div>
                        <div class="col-6"><input class="form-control" id="hc-a-lng" name="longitude" type="number" step="0.0000001" min="-180" max="180" dir="ltr" placeholder="lng" required></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ $c('deposit.close') }}</button><button class="btn btn-primary"><i class="fa fa-floppy-disk"></i> {{ $c('addresses.save') }}</button></div>
    </form></div>
</div>
@endif
