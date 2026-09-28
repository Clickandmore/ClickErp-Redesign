@php
    $type = $type ?? 'text';
    $required = $required ?? false;
    $value = old($name, $driver->$name);
@endphp
<label for="{{ $name }}" class="form-label">{{ $label }} @if ($required)<span class="text-danger">*</span>@endif</label>
<input type="{{ $type }}" id="{{ $name }}" name="{{ $name }}" value="{{ $value }}"
       class="form-control @error($name) is-invalid @enderror"
       @if ($required) required @endif
       @foreach ($attrs ?? [] as $attr => $attrValue) {{ $attr }}="{{ $attrValue }}" @endforeach>
@error($name)<div class="invalid-feedback">{{ $message }}</div>@enderror
@if (isset($expiring[$name]))
    @php($left = $expiring[$name])
    <div class="form-text {{ $left < 0 ? 'text-danger' : 'text-warning' }}"><i class="fa {{ $left < 0 ? 'fa-circle-exclamation' : 'fa-hourglass-half' }} ms-1"></i>{{ $left < 0 ? 'منتهية منذ '.abs($left).' يوم' : ($left === 0 ? 'تنتهي اليوم' : 'تنتهي خلال '.$left.' يوم') }}</div>
@endif
@isset($help)<div class="form-text">{{ $help }}</div>@endisset
