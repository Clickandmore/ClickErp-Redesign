@extends('admin.layout.indexs.index')

@section('page-title')
    {{ __('home_hero.page_title') }}
@endsection

@section('style')
    <style>
        .home-hero-image {
            width: 180px;
            height: 120px;
            object-fit: cover;
            border-radius: 10px;
        }
    </style>
@endsection

@section('content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $canEdit = @AdminGuardHasAnyPerm([\App\PermKeys::HOME_HERO_EDIT, \App\PermKeys::HOME_HERO_CONTROL]);
        $canCreate = @AdminGuardHasAnyPerm([\App\PermKeys::HOME_HERO_CREATE, \App\PermKeys::HOME_HERO_CONTROL]);
        $canDelete = @AdminGuardHasAnyPerm([\App\PermKeys::HOME_HERO_DELETE, \App\PermKeys::HOME_HERO_CONTROL]);
        $videoType = strtolower(trim((string) ($section->video_type ?? '')));

        if (!in_array($videoType, ['upload', 'youtube'], true)) {
            if ($section && !empty($section->video_path)) {
                $videoType = 'upload';
            } elseif ($section && !empty($section->video_url)) {
                $videoType = 'youtube';
            } else {
                $videoType = '';
            }
        }
    @endphp

    <form method="post" action="{{ route('home-hero.update') }}"
        enctype="multipart/form-data" class="row g-4">
        @csrf
        @method('PUT')

        <div class="col-12">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">{{ __('home_hero.content') }}</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        @foreach (config('translatable.locales') as $locale)
                            <div class="col-md-6">
                                <label class="form-label">{{ __('home_hero.title') }} ({{ strtoupper($locale) }})</label>
                                <input type="text" class="form-control" name="title:{{ $locale }}"
                                    value="{{ old('title:' . $locale, $section ? optional($section->translate($locale))->title : '') }}"
                                    maxlength="255" required @readonly(!$canEdit && $section)>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('home_hero.subtitle') }} ({{ strtoupper($locale) }})</label>
                                <textarea class="form-control" name="subtitle:{{ $locale }}" rows="3"
                                    maxlength="2000" required @readonly(!$canEdit && $section)>{{ old('subtitle:' . $locale, $section ? optional($section->translate($locale))->subtitle : '') }}</textarea>
                            </div>
                        @endforeach

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_active"
                                    value="{{ ($canEdit || !$section) ? 0 : (($section->is_active ?? true) ? 1 : 0) }}">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                    id="home-hero-active" @disabled(!$canEdit && $section)
                                    {{ old('is_active', $section->is_active ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="home-hero-active">{{ __('home_hero.active') }}</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">{{ __('home_hero.images') }}</h5></div>
                <div class="card-body">
                    <input type="file" name="images[]" class="form-control"
                        accept=".jpg,.jpeg,.png,.webp" multiple @disabled(!$canCreate)>
                    <small class="text-muted">{{ __('home_hero.images_hint') }}</small>

                    @if ($section && $section->images->isNotEmpty())
                        <div class="d-flex flex-wrap gap-3 mt-4">
                            @foreach ($section->images as $image)
                                <label class="border rounded p-2 text-center">
                                    @if ($image->url)
                                        <img src="{{ $image->url }}" class="home-hero-image d-block mb-2" alt="">
                                    @endif
                                    <input type="checkbox" name="delete_image_ids[]"
                                        value="{{ $image->id }}" @disabled(!$canDelete)>
                                    {{ __('home_hero.delete_image') }}
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">{{ __('home_hero.video') }}</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">{{ __('home_hero.video_type') }}</label>
                            <select class="form-select" name="video_type" id="hero-video-type"
                                @disabled(!$canCreate && !$canEdit && !$canDelete)>
                                <option value="" {{ $videoType === '' ? 'selected' : '' }}>{{ __('home_hero.no_video') }}</option>
                                <option value="upload" {{ $videoType === 'upload' ? 'selected' : '' }}>{{ __('home_hero.uploaded_video') }}</option>
                                <option value="youtube" {{ $videoType === 'youtube' ? 'selected' : '' }}>{{ __('home_hero.youtube_video') }}</option>
                            </select>
                        </div>

                        @foreach (config('translatable.locales') as $locale)
                            <div class="col-md-4">
                                <label class="form-label">
                                    {{ __('home_hero.video_title') }} ({{ strtoupper($locale) }})
                                </label>
                                <input type="text" class="form-control" name="video_title:{{ $locale }}"
                                    value="{{ old('video_title:' . $locale, $section ? optional($section->translate($locale))->video_title : '') }}"
                                    maxlength="255" @readonly(!$canEdit && $section)>
                            </div>
                        @endforeach

                        <div class="col-12 hero-video-field" data-video-type="upload">
                            <label class="form-label">{{ __('home_hero.video_file') }}</label>
                            <input type="file" class="form-control" name="video_file"
                                accept=".mp4,.webm,.mov" @disabled(!$canCreate)>
                            @if ($section && $videoType === 'upload' && $section->video_path)
                                <a href="{{ showFile($section->video_path) }}" target="_blank"
                                    rel="noopener noreferrer" class="d-inline-block mt-2">
                                    {{ __('home_hero.current_video') }}
                                </a>
                            @endif
                        </div>

                        <div class="col-12 hero-video-field" data-video-type="youtube">
                            <label class="form-label">{{ __('home_hero.youtube_url') }}</label>
                            <input type="url" class="form-control" name="video_url"
                                value="{{ old('video_url', $section->video_url ?? '') }}"
                                maxlength="2048" placeholder="https://www.youtube.com/..."
                                @readonly(!$canEdit && !$canCreate)>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if ($canEdit || $canCreate || $canDelete)
            <div class="col-12 text-end">
                <button type="submit" class="btn btn-primary px-5">{{ __('buttons.save') }}</button>
            </div>
        @endif
    </form>
@endsection

@section('js')
    <script>
        (function () {
            const typeInput = document.getElementById('hero-video-type');
            const fields = document.querySelectorAll('.hero-video-field');
            const storedVideoType = @json($videoType);

            typeInput.value = storedVideoType;

            function toggleVideoFields() {
                fields.forEach(function (field) {
                    field.style.display = field.dataset.videoType === typeInput.value ? '' : 'none';
                });
            }

            typeInput.addEventListener('change', toggleVideoFields);
            toggleVideoFields();
        })();
    </script>
@endsection
