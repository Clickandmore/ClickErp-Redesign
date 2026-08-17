@php
    $fileUrl = $obj->file ? showFile($obj->file) : null;
    $fileExtension = $fileUrl ? strtolower(pathinfo(parse_url($fileUrl, PHP_URL_PATH), PATHINFO_EXTENSION)) : null;
    $isImage = in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']);
@endphp

<div class="row g-3">
    <div class="col-12">
        <label class="form-label">{{ __("crm::crm.title") }}</label>
        <input type="text" class="form-control" value="{{ $obj->title }}" disabled>
    </div>

    <div class="col-6">
        <label class="form-label">{{ __("crm::crm.client") }}</label>
        <input type="text" class="form-control" value="{{ $obj->client_display_name }}" disabled>
    </div>

    <div class="col-6">
        <label class="form-label">{{ __("crm::crm.attach") }}</label>
        <input type="text" class="form-control" value="{{ $obj->attachment->title ?? '-' }}" disabled>
    </div>

    <div class="col-12">
        <label class="form-label">{{ __("crm::crm.file") }}</label>
        @if ($fileUrl)
            <div class="d-flex gap-2 mb-3">
                <a href="{{ $fileUrl }}" target="_blank" class="btn btn-sm btn-outline-primary">
                    <i class="fa fa-eye"></i>
                </a>
                <a href="{{ $fileUrl }}" download class="btn btn-sm btn-outline-secondary">
                    <i class="fa fa-download"></i>
                </a>
            </div>

            @if ($isImage)
                <img src="{{ $fileUrl }}" alt="{{ $obj->title }}" class="img-fluid rounded border">
            @else
                <div class="alert alert-light border mb-0">
                    {{ basename(parse_url($fileUrl, PHP_URL_PATH)) }}
                </div>
            @endif
        @else
            <div class="alert alert-light border mb-0">-</div>
        @endif
    </div>

    <div class="col-12 modal-footer">
        {!! closeButton() !!}
    </div>
</div>
