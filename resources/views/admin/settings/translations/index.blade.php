@extends('admin.layout.indexs.index_empty')

@php
    $errors = $errors ?? new \Illuminate\Support\ViewErrorBag;
@endphp

@section('page-title', __('admin.settings_section_translations'))

@section('style')
    <style>
        .translation-terms-page {
            padding: 20px;
        }

        .translation-terms-page .translation-card {
            background: #fff;
            border: 1px solid #e6e9f0;
            border-radius: 8px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);
        }

        .translation-terms-page .translation-table textarea {
            min-width: 240px;
        }
    </style>
@endsection

@section('content')
    <div class="translation-terms-page">
        <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
            <div>
                <h4 class="mb-1">{{ __('admin.settings_section_translations') }}</h4>
                <div class="text-muted small">{{ __('admin.settings_section_translations_hint') }}</div>
            </div>
            <a href="{{ route('settings.section', 'general') }}" class="btn btn-outline-secondary">
                {{ __('admin.settings') }}
            </a>
        </div>

        <div class="translation-card p-3">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="d-flex flex-wrap align-items-end gap-2 mb-3">
                <form method="get" action="{{ route('translation-terms.index') }}" class="row g-2 flex-grow-1">
                    <div class="col-md-4">
                        <label class="form-label">{{ __('admin.translation_group') }}</label>
                        <select name="group" class="form-control">
                            @foreach ($groups as $availableGroup)
                                <option value="{{ $availableGroup }}" @selected($availableGroup === $group)>{{ $availableGroup }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admin.search') }}</label>
                        <input type="text" name="search" value="{{ $search }}" class="form-control"
                               placeholder="{{ __('admin.translation_search_placeholder') }}">
                    </div>
                    <div class="col-md-2 d-grid">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-outline-primary">{{ __('admin.search') }}</button>
                    </div>
                </form>

                <form method="post" action="{{ route('translation-terms.sync') }}">
                    @csrf
                    <input type="hidden" name="group" value="{{ $group }}">
                    <button type="submit" class="btn btn-outline-secondary">{{ __('admin.sync_translation_terms') }}</button>
                </form>
            </div>

            <div class="border rounded p-3 mb-3">
                <h6 class="mb-3">{{ __('admin.add_translation_term') }}</h6>
                <form method="post" action="{{ route('translation-terms.store') }}" class="row g-2">
                    @csrf
                    <input type="hidden" name="group" value="{{ $group }}">
                    <input type="hidden" name="return_search" value="{{ $search }}">
                    <input type="hidden" name="return_page" value="{{ $terms->currentPage() }}">

                    <div class="col-12">
                        <label class="form-label">{{ __('admin.translation_key') }}</label>
                        <input type="text" name="key" class="form-control" required
                               placeholder="{{ __('admin.translation_key_placeholder') }}">
                    </div>

                    @foreach ($locales as $locale)
                        <div class="col-md-6">
                            <label class="form-label">{{ strtoupper($locale) }} {{ getFieldLanguage($locale) }}</label>
                            <textarea name="values[{{ $locale }}]" class="form-control" rows="2"></textarea>
                        </div>
                    @endforeach

                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-primary">{{ __('buttons.save') }}</button>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-sm align-middle translation-table">
                    <thead>
                    <tr>
                        <th style="min-width: 220px;">{{ __('admin.translation_key') }}</th>
                        @foreach ($locales as $locale)
                            <th style="min-width: 260px;">{{ strtoupper($locale) }} {{ getFieldLanguage($locale) }}</th>
                        @endforeach
                        <th class="text-end" style="width: 160px;">{{ __('admin.actions') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($terms as $term)
                        @php
                            $formId = 'term-' . md5($group . '|' . $term['key']);
                            $deleteFormId = 'delete-' . md5($group . '|' . $term['key']);
                        @endphp
                        <tr>
                            <td>
                                <code class="text-wrap">{{ $term['key'] }}</code>
                                <input form="{{ $formId }}" type="hidden" name="group" value="{{ $group }}">
                                <input form="{{ $formId }}" type="hidden" name="key" value="{{ $term['key'] }}">
                                <input form="{{ $formId }}" type="hidden" name="return_search" value="{{ $search }}">
                                <input form="{{ $formId }}" type="hidden" name="return_page" value="{{ $terms->currentPage() }}">
                            </td>
                            @foreach ($locales as $locale)
                                <td>
                                    <textarea form="{{ $formId }}" name="values[{{ $locale }}]" class="form-control" rows="2">{{ $term['values'][$locale] ?? '' }}</textarea>
                                </td>
                            @endforeach
                            <td class="text-end">
                                <form id="{{ $formId }}" method="post" action="{{ route('translation-terms.store') }}" class="d-inline">
                                    @csrf
                                </form>
                                <form id="{{ $deleteFormId }}" method="post" action="{{ route('translation-terms.destroy') }}" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="group" value="{{ $group }}">
                                    <input type="hidden" name="key" value="{{ $term['key'] }}">
                                    <input type="hidden" name="return_search" value="{{ $search }}">
                                    <input type="hidden" name="return_page" value="{{ $terms->currentPage() }}">
                                </form>
                                <button form="{{ $formId }}" type="submit" class="btn btn-sm btn-primary">{{ __('buttons.save') }}</button>
                                <button form="{{ $deleteFormId }}" type="submit" class="btn btn-sm btn-outline-danger">{{ __('admin.delete') }}</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($locales) + 2 }}" class="text-center text-muted py-4">
                                {{ __('admin.no_data') }}
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if ($terms->hasPages())
                <div class="d-flex justify-content-end mt-3">
                    {{ $terms->onEachSide(1)->links('pagination::bootstrap-4') }}
                </div>
            @endif
        </div>
    </div>
@endsection
