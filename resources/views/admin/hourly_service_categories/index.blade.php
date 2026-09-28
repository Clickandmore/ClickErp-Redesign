@extends('admin.layout.indexs.index')

@section('page-title', __('hourly.categories'))

@section('style')
    @include('admin.hourly_teams._style')
    <style>
        .sc-icon { width: 38px; height: 38px; border-radius: 11px; display: grid; place-items: center; color: #fff; font-size: 1rem; flex: 0 0 38px; overflow: hidden; }
        .sc-icon img { width: 100%; height: 100%; object-fit: cover; }
        .sc-move { display: inline-flex; flex-direction: column; gap: 2px; }
        .sc-move button { width: 26px; height: 20px; padding: 0; line-height: 1; font-size: .7rem; }
        .sc-publication-table .hourly-publication-tip { position: fixed; inset: auto; }
    </style>
@endsection

@section('content')
    @php
        $canCreate = AdminGuardHasPerm('hourly_service_categories_create');
        $canEdit = AdminGuardHasPerm('hourly_service_categories_edit');
        $canDelete = AdminGuardHasPerm('hourly_service_categories_delete');
        $fromModal = in_array(old('_form'), ['category-create', 'category-edit'], true);
        $editingId = old('_form') === 'category-edit' ? (int) old('_record_id') : null;
    @endphp
    <div class="container-fluid ht-page" dir="rtl">
        <div id="category-feedback" class="alert border-0 rounded-3 {{ session('success') ? 'alert-success' : 'd-none' }}" role="status">{{ session('success') }}</div>
        @if ($errors->has('category'))
            <div class="alert alert-danger border-0 rounded-3"><i class="fa fa-circle-xmark ms-1"></i> {{ $errors->first('category') }}</div>
        @endif

        <div class="ht-card">
            <div class="ht-head">
                <div class="ht-head-icon"><i class="fa fa-layer-group"></i></div>
                <div>
                    <h5>{{ __('hourly.categories') }}</h5>
                    <p>{{ __('hourly.categories_hint') }}</p>
                </div>
                <div class="ht-actions">
                    @if ($canCreate)
                        <button type="button" class="btn btn-primary rounded-3" data-bs-toggle="modal" data-bs-target="#categoryModal"><i class="fa fa-plus ms-1"></i> {{ __('hourly.category_add') }}</button>
                    @endif
                </div>
            </div>
            <div class="ht-body">
                @if ($categories->isEmpty())
                    <div class="ht-empty"><i class="fa fa-layer-group"></i> {{ __('hourly.no_categories') }}</div>
                @else
                    <div class="table-responsive sc-publication-table">
                        <table class="ht-table">
                            <thead>
                                <tr>
                                    <th style="width: 50px"></th>
                                    <th style="width: 70px">ID</th>
                                    <th>{{ __('hourly.name') }}</th>
                                    <th class="text-center">{{ __('hourly.category_items') }}</th>
                                    <th>{{ __('hourly.status') }}</th>
                                    <th>الظهور في الموقع</th>
                                    <th>{{ __('hourly.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($categories as $category)
                                    <tr>
                                        <td>
                                            @if ($canEdit)
                                                <div class="sc-move">
                                                    @foreach (['up' => 'fa-chevron-up', 'down' => 'fa-chevron-down'] as $direction => $icon)
                                                        <form method="POST" action="{{ route('admin.hourly-service-categories.move', [$category, $direction]) }}" class="m-0">
                                                            @csrf @method('PUT')
                                                            <button class="btn btn-light border" title="{{ __('hourly.move_'.$direction) }}" aria-label="{{ __('hourly.move_'.$direction) }}" {{ ($direction === 'up' && $loop->parent->first) || ($direction === 'down' && $loop->parent->last) ? 'disabled' : '' }}><i class="fa {{ $icon }}"></i></button>
                                                        </form>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </td>
                                        <td class="text-muted ht-num">{{ $category->id }}</td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="sc-icon" style="background: {{ $category->color ?: '#6861ee' }}">
                                                    @if ($category->icon_url)<img src="{{ $category->icon_url }}" alt="">@else<i class="fa {{ $category->icon ?: 'fa-layer-group' }}"></i>@endif
                                                </span>
                                                <div>
                                                    <strong>{{ $category->name_ar }}</strong>
                                                    @if ($category->name_en)<div class="small text-muted" dir="ltr" style="text-align: right">{{ $category->name_en }}</div>@endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center ht-num">{{ $category->packages_count }}</td>
                                        <td>
                                            <button type="button" class="ht-toggle" role="switch" aria-checked="{{ $category->is_active ? 'true' : 'false' }}" aria-label="{{ __('hourly.status') }} {{ $category->name_ar }}"
                                                    data-status-url="{{ route('admin.hourly-service-categories.status', $category) }}" {{ $canEdit ? '' : 'disabled' }}><span>{{ $category->is_active ? '✓' : '' }}</span></button>
                                        </td>
                                        <td class="text-nowrap">
                                            @include('admin.hourly._publication-badge',['report'=>$publication[$category->id],'badgeId'=>'website-category-'.$category->id])
                                        </td>
                                        <td class="text-nowrap">
                                            <a class="btn btn-sm btn-primary rounded-3" href="{{ route('admin.hourly-service-categories.show', $category) }}"><i class="fa fa-sliders ms-1"></i> {{ __('hourly.category_manage') }}</a>
                                            @if ($canEdit)
                                                <button type="button" class="btn btn-sm btn-outline-secondary ht-icon-btn" title="{{ __('hourly.edit') }}" data-bs-toggle="modal" data-bs-target="#categoryModal"
                                                        data-record="{{ json_encode(['id' => $category->id, 'url' => route('admin.hourly-service-categories.update', $category), 'name_ar' => $category->name_ar, 'name_en' => $category->name_en, 'slug' => $category->slug, 'description_ar' => $category->description_ar, 'description_en' => $category->description_en, 'icon_type' => $category->icon_type, 'icon' => $category->icon_type === 'library' ? $category->icon : '', 'color' => $category->color ?: '#6861ee', 'is_active' => $category->is_active, 'has_image' => (bool) $category->image]) }}"><i class="fa fa-pen"></i></button>
                                            @endif
                                            @if ($canDelete)
                                                <form method="POST" action="{{ route('admin.hourly-service-categories.destroy', $category) }}" class="d-inline" data-confirm-title="حذف الفئة" data-confirm="{{ __('hourly.category_delete_confirm') }}" data-confirm-button="نعم، احذف">
                                                    @csrf @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger ht-icon-btn" title="{{ __('hourly.delete') }}" {{ $category->packages_count ? 'disabled' : '' }}><i class="fa fa-trash"></i></button>
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
        </div>
    </div>

    @if ($canCreate || $canEdit)
        <div class="modal fade ht-page" id="categoryModal" tabindex="-1" aria-labelledby="categoryModalLabel" aria-hidden="true" dir="rtl">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <form method="POST" action="{{ $editingId ? route('admin.hourly-service-categories.update', $editingId) : route('admin.hourly-service-categories.store') }}" enctype="multipart/form-data" class="modal-content border-0 rounded-4">
                    @csrf
                    <input type="hidden" name="_method" value="PUT" {{ $editingId ? '' : 'disabled' }}>
                    <input type="hidden" name="_form" value="{{ $editingId ? 'category-edit' : 'category-create' }}">
                    <input type="hidden" name="_record_id" value="{{ $editingId }}">
                    <div class="modal-header ht-head">
                        <div class="ht-head-icon"><i data-modal-icon class="fa {{ $editingId ? 'fa-pen-to-square' : 'fa-layer-group' }}"></i></div>
                        <div><h5 id="categoryModalLabel" data-modal-title>{{ $editingId ? __('hourly.category_edit') : __('hourly.category_add') }}</h5></div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('hourly.cancel') }}"></button>
                    </div>
                    <div class="modal-body ht-body">
                        @if ($fromModal && $errors->any())
                            <div class="alert alert-danger border-0 rounded-3" data-modal-errors>
                                <ul class="mb-0 ps-3">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                            </div>
                        @endif
                        @include('admin.hourly_service_categories._fields', ['category' => new \App\Models\Hourly\HourlyServiceCategory(['is_active' => true, 'icon_type' => 'library', 'icon' => 'fa-broom'])])
                    </div>
                    <div class="modal-footer ht-foot">
                        <button type="submit" class="btn btn-primary rounded-3 px-4"><i class="fa fa-floppy-disk ms-1"></i> <span data-modal-submit>{{ __('hourly.save') }}</span></button>
                        <button type="button" class="btn btn-outline-secondary rounded-3" data-bs-dismiss="modal">{{ __('hourly.cancel') }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection

@push('js')
@include('admin.hourly._confirm-script')
@if ($canCreate || $canEdit)
    @include('admin.hourly_districts._modal-form-script')
    <script>
        hourlyModalForm('categoryModal', {
            key: 'category', icon: 'fa-layer-group', reopen: @json($fromModal),
            createAction: @json(route('admin.hourly-service-categories.store')), createTitle: @json(__('hourly.category_add')), createSubmit: @json(__('hourly.save')),
            editTitle: record => @json(__('hourly.category_edit')) + ' — ' + record.name_ar,
            defaults: {name_ar: '', name_en: '', slug: '', description_ar: '', description_en: '', icon_type: 'library', icon: 'fa-broom', color: '#6861ee', is_active: true},
        });
        (() => {
            const modal = document.getElementById('categoryModal');
            const type = modal.querySelector('[data-icon-type]'), icon = modal.querySelector('#icon'), preview = modal.querySelector('[data-icon-preview]');
            const sync = () => {
                modal.querySelectorAll('[data-icon-pane]').forEach(pane => pane.classList.toggle('d-none', pane.dataset.iconPane !== type.value));
                preview.className = 'fa ' + (/^fa-[a-z0-9-]+$/.test(icon.value) ? icon.value : 'fa-broom');
            };
            type.addEventListener('change', sync);
            icon.addEventListener('input', sync);
            modal.addEventListener('show.bs.modal', event => {
                const record = event.relatedTarget && event.relatedTarget.dataset.record ? JSON.parse(event.relatedTarget.dataset.record) : null;
                modal.querySelector('[data-remove-image]').classList.toggle('d-none', !(record && record.has_image));
                modal.querySelectorAll('input[type=file], input[name=remove_image]').forEach(el => { el.value = el.type === 'checkbox' ? '1' : ''; el.checked = false; });
                setTimeout(sync);
            });
            sync();
        })();
    </script>
@endif
<script>
// Fixed tooltips remain visible outside the horizontally scrollable table.
document.querySelectorAll('.sc-publication-table .hourly-publication').forEach(badge => {
    const position = () => {
        const tip = badge.querySelector('[role=tooltip]'), rect = badge.getBoundingClientRect();
        tip.style.left = Math.max(8, Math.min(rect.right - tip.offsetWidth, window.innerWidth - tip.offsetWidth - 8)) + 'px';
        tip.style.top = Math.max(8, rect.bottom + tip.offsetHeight > window.innerHeight - 8 ? rect.top - tip.offsetHeight : rect.bottom) + 'px';
    };
    badge.addEventListener('mouseenter', position);
    badge.addEventListener('focus', position);
    window.addEventListener('scroll', () => { if (badge.matches(':hover, :focus')) position(); }, true);
    window.addEventListener('resize', () => { if (badge.matches(':hover, :focus')) position(); });
});
document.querySelectorAll('.ht-toggle[data-status-url]').forEach(button => button.addEventListener('click', async () => {
    const feedback = document.getElementById('category-feedback');
    button.disabled = true;
    try {
        const response = await fetch(button.dataset.statusUrl, {method: 'PUT', credentials: 'same-origin', headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': @json(csrf_token())}, body: JSON.stringify({is_active: button.getAttribute('aria-checked') !== 'true'})});
        const data = await response.json().catch(() => ({}));
        if (!response.ok || typeof data.is_active !== 'boolean') throw new Error(data.message || 'Error');
        button.setAttribute('aria-checked', String(data.is_active));
        button.querySelector('span').textContent = data.is_active ? '✓' : '';
        const badge = button.closest('tr').querySelector('.hourly-publication');
        if (badge && data.publication) {
            const report = data.publication;
            badge.classList.toggle('is-hidden', !report.visible);
            badge.dataset.websiteVisible = report.visible ? '1' : '0';
            badge.querySelector('[data-publication-label]').textContent = report.visible ? 'يظهر في الموقع' : 'لا يظهر في الموقع';
            badge.querySelector('i').className = 'fa ' + (report.visible ? 'fa-eye' : 'fa-eye-slash');
            badge.querySelector('[role=tooltip]').replaceChildren(...report.reasons.map(reason => {
                const line = document.createElement('span'); line.textContent = reason; return line;
            }));
        }
        feedback.className = 'alert alert-success border-0 rounded-3';
        feedback.textContent = data.message;
    } catch (e) {
        feedback.className = 'alert alert-danger border-0 rounded-3';
        feedback.textContent = e.message;
    } finally {
        button.disabled = false;
    }
}));
</script>
@endpush
