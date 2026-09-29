<div class="col-md-6">
    <label class="form-label fw-semibold" for="{{ $field['name'] }}">{{ $field['label'] }}</label>
    <div class="input-group">
        <input type="number" class="form-control @error($field['name']) is-invalid @enderror"
            id="{{ $field['name'] }}" name="{{ $field['name'] }}" value="{{ $field['value'] }}"
            min="{{ $field['min'] }}" max="{{ $field['max'] }}" required>
        <span class="input-group-text">{{ $field['unit'] }}</span>
    </div>
    <small class="text-muted">{{ $field['hint'] }}</small>
    @error($field['name'])
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
