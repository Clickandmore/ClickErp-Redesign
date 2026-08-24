@extends('admin.settings.sections.layout')

@section('settings-title', __('admin.settings_section_announcements'))

@section('settings-description', __('admin.settings_section_announcements_hint'))

@section('settings-alerts')
    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
@endsection

@section('settings-content')
    @if (@AdminGuardHasPerm(\App\PermKeys::SETTINGS_CONTROL))
        <style>
            .announcements-grid {
                display: grid;
                gap: 1rem;
            }

            .announcements-card {
                border: 1px solid #e9ecef;
                border-radius: 16px;
                padding: 1.25rem;
                background: linear-gradient(180deg, #ffffff 0%, #fbfcfe 100%);
            }

            .announcements-card__title {
                font-size: 1rem;
                font-weight: 700;
                margin-bottom: 0.25rem;
            }

            .announcements-card__hint {
                color: #6c757d;
                font-size: 0.875rem;
                margin-bottom: 1rem;
            }

            .announcements-news-list {
                list-style: none;
                padding: 0;
                margin: 0;
                display: grid;
                gap: 0.75rem;
            }

            .announcements-news-list li {
                border: 1px solid #eef1f5;
                border-radius: 12px;
                padding: 0.85rem 1rem;
                background: #fff;
            }

            .popup-preview {
                border: 1px dashed #d5dbe3;
                border-radius: 12px;
                padding: 0.75rem;
                background: #fff;
                display: inline-flex;
            }

            .popup-preview img {
                max-width: 240px;
                border-radius: 10px;
            }
        </style>

        <div class="announcements-grid">
            <div class="announcements-card">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                    <div>
                        <div class="announcements-card__title">{{ __('admin.fast_news_settings') }}</div>
                        <div class="announcements-card__hint">{{ __('admin.fast_news_settings_hint') }}</div>
                    </div>
                    <div class="d-flex gap-2">
                        <span class="badge bg-light text-dark border">{{ __('admin.total_items') }}: {{ $fastNewsCount }}</span>
                        <a href="{{ route('fast-news.index') }}" class="btn btn-primary btn-sm">
                            {{ __('admin.manage_fast_news') }}
                        </a>
                    </div>
                </div>

                @if($recentFastNews->isNotEmpty())
                    <ul class="announcements-news-list">
                        @foreach($recentFastNews as $news)
                            <li>
                                <div class="fw-semibold">{{ $news->title }}</div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="alert alert-light border mb-0">{{ __('admin.no_fast_news_items') }}</div>
                @endif
            </div>

            <div class="announcements-card">
                <div class="announcements-card__title">{{ __('admin.popup_settings') }}</div>
                <div class="announcements-card__hint">{{ __('admin.popup_settings_hint') }}</div>

                <form class="row g-3" action="{{ route('dash.settings.store') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="section" value="announcements">

                    <div class="col-lg-4">
                        <label class="form-label">{{ __('admin.show_popup') }}</label>
                    </div>
                    <div class="col-lg-8">
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="show_popup" value="1"
                                {{ @$data['show_popup'] === '1' ? 'checked' : '' }}>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <label class="form-label">{{ __('admin.popup_image') }}</label>
                    </div>
                    <div class="col-lg-8">
                        <input type="file"
                               name="popup_image"
                               class="form-control @error('popup_image') is-invalid @enderror"
                               accept="image/*">
                        @error('popup_image')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror

                        @if(@$data['popup_image'])
                            <div class="popup-preview mt-3">
                                <img src="{{ showFile($data['popup_image']) }}" alt="Current Popup Image">
                            </div>
                        @endif
                    </div>

                    <div class="col-lg-4">
                        <label class="form-label">{{ __('admin.popup_link') }}</label>
                    </div>
                    <div class="col-lg-8">
                        <input type="url"
                               name="popup_link"
                               class="form-control @error('popup_link') is-invalid @enderror"
                               value="{{ old('popup_link', $data['popup_link'] ?? '') }}"
                               placeholder="https://example.com/offer">
                        <div class="form-text">{{ __('admin.popup_link_hint') }}</div>
                        @error('popup_link')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-primary">{{ __('admin.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection
