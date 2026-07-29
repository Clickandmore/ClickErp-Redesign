@extends('admin.layout.indexs.index')

@section('page-title')
    {{ __('guidelines.page_title') }}
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
        $canCreate = @AdminGuardHasAnyPerm([\App\PermKeys::GUIDELINES_CREATE, \App\PermKeys::GUIDELINES_CONTROL]);
        $canEdit = @AdminGuardHasAnyPerm([\App\PermKeys::GUIDELINES_EDIT, \App\PermKeys::GUIDELINES_CONTROL]);
        $canDelete = @AdminGuardHasAnyPerm([\App\PermKeys::GUIDELINES_DELETE, \App\PermKeys::GUIDELINES_CONTROL]);
        $videoRows = old('videos') !== null
            ? collect(old('videos'))
            : ($section ? $section->videos : collect());
        $versionRows = old('versions') !== null
            ? collect(old('versions'))
            : ($section
                ? $section->nationalityGuidelines->flatMap(function ($guideline) {
                    return $guideline->versions->map(function ($version) use ($guideline) {
                        $version->nationality_id = $guideline->nationality_id;
                        return $version;
                    });
                })
                : collect());
    @endphp

    <form method="post" action="{{ route('guidelines.update') }}"
        enctype="multipart/form-data" class="row g-4">
        @csrf
        @method('PUT')

        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('guidelines.global_files') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('guidelines.client_file') }}</label>
                            <input type="file" name="client_file" class="form-control"
                                accept=".pdf,.doc,.docx" {{ !$canEdit && !$canCreate ? 'disabled' : '' }}>
                            @if ($section?->client_file_path)
                                <a href="{{ showFile($section->client_file_path) }}" target="_blank"
                                    rel="noopener noreferrer" class="d-inline-block mt-2">
                                    {{ __('guidelines.current_file') }}
                                </a>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('guidelines.worker_file') }}</label>
                            <input type="file" name="worker_file" class="form-control"
                                accept=".pdf,.doc,.docx" {{ !$canEdit && !$canCreate ? 'disabled' : '' }}>
                            @if ($section?->worker_file_path)
                                <a href="{{ showFile($section->worker_file_path) }}" target="_blank"
                                    rel="noopener noreferrer" class="d-inline-block mt-2">
                                    {{ __('guidelines.current_file') }}
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ __('guidelines.youtube_links') }}</h5>
                    @if ($canCreate)
                        <button type="button" class="btn btn-sm btn-outline-primary" id="add-guideline-video">
                            <i class="fa fa-plus"></i> {{ __('guidelines.add_video') }}
                        </button>
                    @endif
                </div>
                <div class="card-body">
                    <div id="guideline-video-rows">
                        @foreach ($videoRows as $index => $video)
                            @php
                                $videoId = data_get($video, 'id');
                                $youtubeUrl = data_get($video, 'youtube_url');
                            @endphp
                            <div class="guideline-video-row row g-2 align-items-end mb-3">
                                <input type="hidden" name="videos[{{ $index }}][id]" value="{{ $videoId }}">
                                <div class="col">
                                    <label class="form-label">{{ __('guidelines.youtube_url') }}</label>
                                    <input type="url" name="videos[{{ $index }}][youtube_url]"
                                        class="form-control" value="{{ $youtubeUrl }}" required
                                        {{ $videoId ? (!$canEdit ? 'readonly' : '') : (!$canCreate ? 'readonly' : '') }}>
                                </div>
                                @if (($videoId && $canDelete) || (!$videoId && $canCreate))
                                    <div class="col-auto">
                                        <button type="button" class="btn btn-outline-danger remove-guideline-row">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    @if ($videoRows->isEmpty())
                        <p class="text-muted mb-0" id="no-guideline-videos">{{ __('guidelines.no_videos') }}</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1">{{ __('guidelines.nationality_versions') }}</h5>
                        <small class="text-muted">{{ __('guidelines.nationality_versions_hint') }}</small>
                    </div>
                    @if ($canCreate)
                        <button type="button" class="btn btn-sm btn-outline-primary" id="add-guideline-version">
                            <i class="fa fa-plus"></i> {{ __('guidelines.add_language_version') }}
                        </button>
                    @endif
                </div>
                <div class="card-body">
                    <div id="guideline-version-rows">
                        @foreach ($versionRows as $index => $version)
                            @php
                                $versionId = data_get($version, 'id');
                                $nationalityId = data_get($version, 'nationality_id');
                            @endphp
                            <div class="guideline-version-row border rounded p-3 mb-3">
                                <input type="hidden" name="versions[{{ $index }}][id]" value="{{ $versionId }}">
                                <div class="row g-3">
                                    <div class="col-md-5">
                                        <label class="form-label">{{ __('guidelines.nationality') }}</label>
                                        <select name="versions[{{ $index }}][nationality_id]"
                                            class="form-select" required
                                            {{ $versionId ? (!$canEdit ? 'disabled' : '') : (!$canCreate ? 'disabled' : '') }}>
                                            <option value="">{{ __('guidelines.choose_nationality') }}</option>
                                            @foreach ($nationalities as $nationality)
                                                <option value="{{ $nationality->id }}"
                                                    {{ (int) $nationalityId === (int) $nationality->id ? 'selected' : '' }}>
                                                    {{ $nationality->display_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @if ($versionId && !$canEdit)
                                            <input type="hidden" name="versions[{{ $index }}][nationality_id]"
                                                value="{{ $nationalityId }}">
                                        @endif
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label">{{ __('guidelines.language') }}</label>
                                        <input type="text" name="versions[{{ $index }}][language]"
                                            class="form-control" value="{{ data_get($version, 'language') }}"
                                            maxlength="100" required
                                            {{ $versionId ? (!$canEdit ? 'readonly' : '') : (!$canCreate ? 'readonly' : '') }}>
                                    </div>
                                    @if (($versionId && $canDelete) || (!$versionId && $canCreate))
                                        <div class="col-md-2 d-flex align-items-end justify-content-end">
                                            <button type="button" class="btn btn-outline-danger remove-guideline-row">
                                                <i class="fa fa-trash"></i> {{ __('guidelines.remove') }}
                                            </button>
                                        </div>
                                    @endif
                                    <div class="col-md-6">
                                        <label class="form-label">{{ __('guidelines.client_file') }}</label>
                                        <input type="file" name="versions[{{ $index }}][client_file]"
                                            class="form-control" accept=".pdf,.doc,.docx"
                                            {{ $versionId ? (!$canEdit ? 'disabled' : '') : (!$canCreate ? 'disabled' : '') }}>
                                        @if ($versionId && data_get($version, 'client_file_path'))
                                            <a href="{{ showFile(data_get($version, 'client_file_path')) }}"
                                                target="_blank" rel="noopener noreferrer" class="d-inline-block mt-2">
                                                {{ __('guidelines.current_file') }}
                                            </a>
                                        @endif
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">{{ __('guidelines.worker_file') }}</label>
                                        <input type="file" name="versions[{{ $index }}][worker_file]"
                                            class="form-control" accept=".pdf,.doc,.docx"
                                            {{ $versionId ? (!$canEdit ? 'disabled' : '') : (!$canCreate ? 'disabled' : '') }}>
                                        @if ($versionId && data_get($version, 'worker_file_path'))
                                            <a href="{{ showFile(data_get($version, 'worker_file_path')) }}"
                                                target="_blank" rel="noopener noreferrer" class="d-inline-block mt-2">
                                                {{ __('guidelines.current_file') }}
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        @if ($canCreate || $canEdit || $canDelete)
            <div class="col-12 text-end">
                <button type="submit" class="btn btn-primary px-5">{{ __('buttons.save') }}</button>
            </div>
        @endif
    </form>

    <template id="guideline-video-template">
        <div class="guideline-video-row row g-2 align-items-end mb-3">
            <div class="col">
                <label class="form-label">{{ __('guidelines.youtube_url') }}</label>
                <input type="url" name="videos[__INDEX__][youtube_url]" class="form-control" required>
            </div>
            <div class="col-auto">
                <button type="button" class="btn btn-outline-danger remove-guideline-row">
                    <i class="fa fa-trash"></i>
                </button>
            </div>
        </div>
    </template>

    <template id="guideline-version-template">
        <div class="guideline-version-row border rounded p-3 mb-3">
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label">{{ __('guidelines.nationality') }}</label>
                    <select name="versions[__INDEX__][nationality_id]" class="form-select" required>
                        <option value="">{{ __('guidelines.choose_nationality') }}</option>
                        @foreach ($nationalities as $nationality)
                            <option value="{{ $nationality->id }}">{{ $nationality->display_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label">{{ __('guidelines.language') }}</label>
                    <input type="text" name="versions[__INDEX__][language]"
                        class="form-control" maxlength="100" required>
                </div>
                <div class="col-md-2 d-flex align-items-end justify-content-end">
                    <button type="button" class="btn btn-outline-danger remove-guideline-row">
                        <i class="fa fa-trash"></i> {{ __('guidelines.remove') }}
                    </button>
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('guidelines.client_file') }}</label>
                    <input type="file" name="versions[__INDEX__][client_file]"
                        class="form-control" accept=".pdf,.doc,.docx" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('guidelines.worker_file') }}</label>
                    <input type="file" name="versions[__INDEX__][worker_file]"
                        class="form-control" accept=".pdf,.doc,.docx" required>
                </div>
            </div>
        </div>
    </template>
@endsection

@section('js')
    <script>
        (function () {
            let videoIndex = {{ $videoRows->count() }};
            let versionIndex = {{ $versionRows->count() }};

            document.getElementById('add-guideline-video')?.addEventListener('click', function () {
                const html = document.getElementById('guideline-video-template').innerHTML
                    .replaceAll('__INDEX__', videoIndex++);
                document.getElementById('guideline-video-rows').insertAdjacentHTML('beforeend', html);
                document.getElementById('no-guideline-videos')?.remove();
            });

            document.getElementById('add-guideline-version')?.addEventListener('click', function () {
                const html = document.getElementById('guideline-version-template').innerHTML
                    .replaceAll('__INDEX__', versionIndex++);
                document.getElementById('guideline-version-rows').insertAdjacentHTML('beforeend', html);
            });

            document.addEventListener('click', function (event) {
                const button = event.target.closest('.remove-guideline-row');
                if (button) {
                    button.closest('.guideline-video-row, .guideline-version-row').remove();
                }
            });
        })();
    </script>
@endsection
