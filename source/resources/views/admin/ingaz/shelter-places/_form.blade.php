<div class="col-12">
    <h6 class="mb-2">{{ helperTrans('ingaz.basic_data') }}</h6>
</div>

@foreach (config('translatable.locales') as $locale)
    <div class="col-md-6">
        <label class="form-label">{{ helperTrans("ingaz.title") }} ({{ helperTrans('ingaz.' . $locale) }})</label>
        <input type="text" name="title:{{ $locale }}"
               value="{{ old('title:' . $locale, $obj?->translate($locale)?->title) }}"
               class="form-control" data-validation="required">
    </div>
@endforeach

<div class="col-12">
    <hr class="my-2">
    <h6 class="mb-2">{{ helperTrans('ingaz.shelter_capacity') }}</h6>
</div>

<div class="col-md-6">
    <label class="form-label">{{ helperTrans('ingaz.female_capacity') }}</label>
    <input type="number" name="female_capacity" min="0"
           value="{{ old('female_capacity', $obj?->female_capacity) }}"
           class="form-control" placeholder="0">
</div>

<div class="col-md-6">
    <label class="form-label">{{ helperTrans('ingaz.male_capacity') }}</label>
    <input type="number" name="male_capacity" min="0"
           value="{{ old('male_capacity', $obj?->male_capacity) }}"
           class="form-control" placeholder="0">
</div>

<div class="col-12">
    <hr class="my-2">
    <h6 class="mb-2">{{ helperTrans('ingaz.shelter_location') }}</h6>
</div>

<div class="col-12">
    <label class="form-label">{{ helperTrans('ingaz.address') }}</label>
    <input type="text" name="address"
           value="{{ old('address', $obj?->address) }}"
           class="form-control" placeholder="{{ helperTrans('ingaz.address') }}">
</div>

<div class="col-12">
    <label class="form-label">{{ helperTrans('ingaz.map_url') }}</label>
    <input type="text" name="map_url"
           value="{{ old('map_url', $obj?->map_url) }}"
           class="form-control" placeholder="https://maps.google.com/...">
</div>

<div class="col-md-6">
    <label class="form-label">{{ helperTrans('ingaz.latitude') }}</label>
    <input type="number" step="0.0000001" name="latitude"
           value="{{ old('latitude', $obj?->latitude) }}"
           class="form-control" placeholder="24.7135517">
</div>

<div class="col-md-6">
    <label class="form-label">{{ helperTrans('ingaz.longitude') }}</label>
    <input type="number" step="0.0000001" name="longitude"
           value="{{ old('longitude', $obj?->longitude) }}"
           class="form-control" placeholder="46.6752957">
</div>

<div class="col-12">
    <hr class="my-2">
    <h6 class="mb-2">{{ helperTrans('ingaz.shelter_management') }}</h6>
</div>

<div class="col-md-6">
    <label class="form-label">{{ helperTrans('ingaz.shelter_supervisor') }}</label>
    <select name="supervisor_admin_id" class="form-control">
        <option value="">{{ helperTrans('ingaz.not_selected') }}</option>
        @foreach ($admins as $admin)
            @php
                $adminLabel = trim($admin->name ?: ('#' . $admin->id));
                $adminLabel .= $admin->phone ? ' - ' . $admin->phone : '';
                $isUnavailable = $admin->trashed() || $admin->status !== 'active';
            @endphp
            <option value="{{ $admin->id }}" @selected((string) old('supervisor_admin_id', $obj?->supervisor_admin_id) === (string) $admin->id)>
                {{ $adminLabel }}{{ $isUnavailable ? ' - ' . helperTrans('ingaz.currently_unavailable') : '' }}
            </option>
        @endforeach
    </select>
</div>

<div class="col-md-6">
    <label class="form-label">{{ helperTrans('ingaz.shelter_responsible') }}</label>
    <select name="responsible_admin_id" class="form-control">
        <option value="">{{ helperTrans('ingaz.not_selected') }}</option>
        @foreach ($admins as $admin)
            @php
                $adminLabel = trim($admin->name ?: ('#' . $admin->id));
                $adminLabel .= $admin->phone ? ' - ' . $admin->phone : '';
                $isUnavailable = $admin->trashed() || $admin->status !== 'active';
            @endphp
            <option value="{{ $admin->id }}" @selected((string) old('responsible_admin_id', $obj?->responsible_admin_id) === (string) $admin->id)>
                {{ $adminLabel }}{{ $isUnavailable ? ' - ' . helperTrans('ingaz.currently_unavailable') : '' }}
            </option>
        @endforeach
    </select>
</div>

<div class="col-12">
    <label class="form-label">{{ helperTrans('ingaz.notes') }}</label>
    <textarea name="notes" rows="3" class="form-control">{{ old('notes', $obj?->notes) }}</textarea>
</div>
