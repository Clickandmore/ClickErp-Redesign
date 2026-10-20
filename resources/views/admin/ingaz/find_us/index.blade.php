@extends('admin.layout.indexs.index')

@section('page-title', __('find_us.title'))

@section('style')
    <style>
        .find-us-card { border: 0; border-radius: 18px; box-shadow: 0 8px 30px rgba(20, 42, 74, .08); }
        .find-us-card .table > :not(caption) > * > * { padding: 1rem .85rem; vertical-align: middle; }
        .find-us-order { display: inline-grid; place-items: center; width: 36px; height: 36px; border-radius: 11px; background: #eaf5ff; color: #0869b5; font-weight: 700; }
        .find-us-address { max-width: 470px; color: #6b7280; line-height: 1.7; }
        .find-us-empty { padding: 70px 20px; text-align: center; }
        .find-us-empty-icon { display: inline-grid; place-items: center; width: 74px; height: 74px; border-radius: 22px; background: #eaf5ff; color: #0869b5; font-size: 30px; margin-bottom: 18px; }
        #findUsMapPreview { min-height: 480px; border: 0; border-radius: 12px; }
    </style>
@endsection

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="mb-1">{{ __('find_us.title') }}</h4>
            <p class="text-muted mb-0">{{ __('find_us.subtitle') }}</p>
        </div>
        @if (@AdminGuardHasAnyPerm([\App\PermKeys::FIND_US_CREATE, \App\PermKeys::FIND_US_CONTROL]))
            <a href="{{ route('find-us.create') }}" class="btn btn-primary px-4">
                <i class="fa fa-plus me-1"></i> {{ __('find_us.add') }}
            </a>
        @endif
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa fa-circle-check me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @php
        $branchesMode = $findUsMode === \App\Support\FindUsSettings::MODE_BRANCHES;
        $canEditMode = @AdminGuardHasAnyPerm([\App\PermKeys::FIND_US_EDIT, \App\PermKeys::FIND_US_CONTROL]);
    @endphp

    <div class="card find-us-card mb-4">
        <div class="card-body">
            <h6 class="fw-bold mb-1">{{ __('find_us.mode') }}</h6>
            <p class="text-muted small mb-3">{{ __('find_us.mode_hint') }}</p>

            <form method="POST" action="{{ route('find-us.mode') }}" class="row g-3">
                @csrf
                @method('PUT')

                <div class="col-md-6">
                    <label class="card h-100 border p-3 mb-0" for="find_us_mode_single" style="cursor: pointer;">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="find_us_mode" id="find_us_mode_single"
                                   value="{{ \App\Support\FindUsSettings::MODE_SINGLE }}"
                                   {{ $branchesMode ? '' : 'checked' }} {{ $canEditMode ? '' : 'disabled' }}>
                            <span class="form-check-label fw-bold">{{ __('find_us.mode_single') }}</span>
                        </div>
                        <small class="text-muted d-block mt-2">{{ __('find_us.mode_single_hint') }}</small>
                    </label>
                </div>

                <div class="col-md-6">
                    <label class="card h-100 border p-3 mb-0" for="find_us_mode_branches" style="cursor: pointer;">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="find_us_mode" id="find_us_mode_branches"
                                   value="{{ \App\Support\FindUsSettings::MODE_BRANCHES }}"
                                   {{ $branchesMode ? 'checked' : '' }} {{ $canEditMode ? '' : 'disabled' }}>
                            <span class="form-check-label fw-bold">{{ __('find_us.mode_branches') }}</span>
                        </div>
                        <small class="text-muted d-block mt-2">{{ __('find_us.mode_branches_hint') }}</small>
                    </label>
                </div>

                @unless($branchesMode)
                    <div class="col-12">
                        <div class="border rounded-3 p-3 bg-light">
                            <div class="small fw-semibold mb-1">{{ __('find_us.single_link_title') }}</div>
                            @if($findUsSingleLink !== '')
                                <div class="small text-muted text-break">{{ $findUsSingleLink }}</div>
                            @else
                                <div class="small text-danger">{{ __('find_us.single_link_empty') }}</div>
                            @endif
                            @if (@AdminGuardHasAnyPerm([\App\PermKeys::SETTINGS_VIEW, \App\PermKeys::SETTINGS_CONTROL]))
                                <a href="{{ route('settings.section', 'general') }}" class="small">
                                    <i class="fa fa-pen me-1"></i>{{ __('find_us.single_link_edit') }}
                                </a>
                            @endif
                        </div>
                    </div>
                @endunless

                @if($canEditMode)
                    <div class="col-12">
                        <button class="btn btn-primary px-4">
                            <i class="fa fa-floppy-disk me-1"></i> {{ __('find_us.mode_save') }}
                        </button>
                    </div>
                @endif
            </form>
        </div>
    </div>

    @if(!$branchesMode && $locations->isNotEmpty())
        <div class="alert alert-warning d-flex align-items-start gap-2">
            <i class="fa fa-triangle-exclamation mt-1"></i>
            <span>{{ __('find_us.mode_single_notice') }}</span>
        </div>
    @endif

    <div class="card find-us-card">
        @if($locations->isEmpty())
            <div class="find-us-empty">
                <span class="find-us-empty-icon"><i class="fa fa-map-location-dot"></i></span>
                <h5>{{ __('find_us.no_locations') }}</h5>
                <p class="text-muted">{{ __('find_us.no_locations_hint') }}</p>
                @if (@AdminGuardHasAnyPerm([\App\PermKeys::FIND_US_CREATE, \App\PermKeys::FIND_US_CONTROL]))
                    <a href="{{ route('find-us.create') }}" class="btn btn-primary mt-2">{{ __('find_us.add') }}</a>
                @endif
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>{{ __('find_us.sort_order') }}</th>
                        <th>{{ __('find_us.branch_name') }}</th>
                        <th>{{ __('find_us.branch_address') }}</th>
                        <th>{{ __('find_us.map_embed_url') }}</th>
                        <th class="text-end">{{ __('find_us.actions') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($locations as $location)
                        <tr>
                            <td><span class="find-us-order">{{ $location->sort_order }}</span></td>
                            <td class="fw-semibold">{{ $location->branch_name }}</td>
                            <td>
                                <div class="find-us-address">{{ $location->branch_address }}</div>
                                @if (!is_null($location->latitude) && !is_null($location->longitude))
                                    <small class="text-muted d-block mt-1" dir="ltr" title="{{ __('find_us.coordinates_column') }}">
                                        <i class="fa fa-location-dot me-1"></i>{{ $location->latitude }}, {{ $location->longitude }}
                                    </small>
                                @endif
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-primary js-map-preview"
                                        data-map-url="{{ $location->map_embed_url }}"
                                        data-branch-name="{{ $location->branch_name }}">
                                    <i class="fa fa-map me-1"></i> {{ __('find_us.preview') }}
                                </button>
                            </td>
                            <td class="text-end text-nowrap">
                                @if (@AdminGuardHasAnyPerm([\App\PermKeys::FIND_US_EDIT, \App\PermKeys::FIND_US_CONTROL]))
                                    <a href="{{ route('find-us.edit', $location) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="fa fa-pen"></i>
                                    </a>
                                @endif
                                @if (@AdminGuardHasAnyPerm([\App\PermKeys::FIND_US_DELETE, \App\PermKeys::FIND_US_CONTROL]))
                                    <form method="POST" action="{{ route('find-us.destroy', $location) }}" class="d-inline"
                                          onsubmit="return confirm(@js(__('find_us.delete_confirm')))">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" title="{{ __('find_us.delete') }}">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="modal fade" id="findUsPreviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="findUsPreviewTitle">{{ __('find_us.preview') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-2">
                    <iframe id="findUsMapPreview" class="w-100" src="about:blank" loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        document.querySelectorAll('.js-map-preview').forEach(function (button) {
            button.addEventListener('click', function () {
                document.getElementById('findUsPreviewTitle').textContent = button.dataset.branchName;
                document.getElementById('findUsMapPreview').src = button.dataset.mapUrl;
                new bootstrap.Modal(document.getElementById('findUsPreviewModal')).show();
            });
        });

        document.getElementById('findUsPreviewModal')?.addEventListener('hidden.bs.modal', function () {
            document.getElementById('findUsMapPreview').src = 'about:blank';
        });
    </script>
@endsection
