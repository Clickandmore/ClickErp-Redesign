@extends('admin.layout.indexs.index')

@push('style')
    <style>
        .perm-keys-toolbar {
            position: sticky;
            top: 0;
            z-index: 5;
            background: var(--bs-body-bg, #fff);
            padding: .75rem 0;
        }

        .perm-keys-table-wrap {
            max-height: 70vh;
            overflow: auto;
        }

        .perm-keys-table-wrap thead th {
            position: sticky;
            top: 0;
            z-index: 2;
        }

        .perm-keys-table td,
        .perm-keys-table th {
            vertical-align: middle;
            white-space: nowrap;
        }

        .perm-keys-table .perm-key-name {
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: .82rem;
            direction: ltr;
            display: inline-block;
        }

        .perm-keys-form-card {
            position: sticky;
            top: 1rem;
        }

        .perm-stat {
            border: 1px solid var(--bs-border-color, #e5e7eb);
            border-radius: .75rem;
            padding: .6rem 1rem;
            min-width: 7rem;
        }

        .perm-stat .perm-stat-value {
            font-size: 1.35rem;
            font-weight: 700;
            line-height: 1.1;
        }

        .perm-stat .perm-stat-label {
            font-size: .75rem;
            color: var(--bs-secondary-color, #6b7280);
        }

        .perm-keys-empty td {
            padding: 2rem;
            color: var(--bs-secondary-color, #6b7280);
        }
    </style>
@endpush

@section('content')

    @if (@AdminGuardHasPerm(\App\PermKeys::DEVELOPERS_PERMS))

        @php
            $rows = collect($data ?? []);
            $activeCount = $rows->where('is_active', true)->count();
            $inactiveCount = $rows->count() - $activeCount;
            $sectionOptions = $rows
                ->map(fn ($row) => $row->display_section_name ?? $row->section_name)
                ->filter()
                ->unique()
                ->sort()
                ->values();
            $groupOptions = $rows
                ->map(fn ($row) => $row->display_group_name ?? $row->group_name)
                ->filter()
                ->unique()
                ->sort()
                ->values();
        @endphp

        <div class="page_content">
            <div class="card">
                <div class="card-body">

                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                        <div>
                            <h5 class="mb-1">مفاتيح الصلاحيات</h5>
                            <div class="text-muted small">{{ __('admin.perms_keys_devs_only') }}</div>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <div class="perm-stat text-center">
                                <div class="perm-stat-value">{{ $rows->count() }}</div>
                                <div class="perm-stat-label">الإجمالي</div>
                            </div>
                            <div class="perm-stat text-center">
                                <div class="perm-stat-value text-success">{{ $activeCount }}</div>
                                <div class="perm-stat-label">مفعّلة</div>
                            </div>
                            <div class="perm-stat text-center">
                                <div class="perm-stat-value text-secondary">{{ $inactiveCount }}</div>
                                <div class="perm-stat-label">معطّلة</div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info py-2 small mb-3">
                        يمكنك إيقاف الصلاحية مؤقتاً دون حذفها. الصلاحية المعطلة لا تظهر في شاشة تعيين الصلاحيات ولا
                        تُستخدم في التحقق حتى تتم إعادة تفعيلها.
                    </div>

                    @include('components.session-messages')

                    <div class="row g-3">

                        <div class="col-12 col-xl-4 col-xxl-3">
                            <div class="card border perm-keys-form-card" style="border-radius: 16px">
                                <div class="card-header bg-transparent">
                                    <b>{{ @$editData ? __('admin.edit') : __('admin.add') }}</b>
                                </div>
                                <div class="card-body">
                                    <form method="post" action='{{ route("dash.developers.perms.store") }}'
                                          enctype="multipart/form-data">
                                        {{ csrf_field() }}

                                        @if (@$editData)
                                            <input type="hidden" name="edit_id" value="{{ @$editData->id }}">
                                        @endif

                                        <div class="mb-3">
                                            <label for="name" class="form-label">{{ __('admin.perms_name') }}</label>
                                            <input id="name" name="name" type="text" dir="ltr"
                                                   class="form-control cu-form-design"
                                                   value="{{ @$editData->name }}" required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="group_name"
                                                   class="form-label">{{ __('admin.perms_group_name') }}</label>
                                            <input id="group_name" name="group_name" type="text" dir="ltr"
                                                   class="form-control cu-form-design" list="perm-group-options"
                                                   value="{{ @$editData->group_name }}" required>
                                            <datalist id="perm-group-options">
                                                @foreach ($groupOptions as $groupOption)
                                                    <option value="{{ $groupOption }}"></option>
                                                @endforeach
                                            </datalist>
                                        </div>

                                        <div class="mb-3">
                                            <label for="section_name"
                                                   class="form-label">{{ __('admin.perms_section_name') }}</label>
                                            <input id="section_name" name="section_name" type="text" dir="ltr"
                                                   class="form-control cu-form-design" list="perm-section-options"
                                                   value="{{ @$editData->section_name }}" required>
                                            <datalist id="perm-section-options">
                                                @foreach ($sectionOptions as $sectionOption)
                                                    <option value="{{ $sectionOption }}"></option>
                                                @endforeach
                                            </datalist>
                                        </div>

                                        <div class="d-flex justify-content-end gap-2 mt-4">
                                            @if (@$editData)
                                                <a href="{{ route('dash.developers.perms.index') }}"
                                                   class="btn btn-fr-bg-light">{{ __('admin.cancel') }}</a>
                                            @endif
                                            <button type="submit"
                                                    class="btn btn-fr-bg">{{ __('admin.confirm') }}</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-xl-8 col-xxl-9">
                            <div class="card border" style="border-radius: 16px">
                                <div class="card-body">

                                    <div class="perm-keys-toolbar">
                                        <div class="row g-2 align-items-end">
                                            <div class="col-12 col-md-4">
                                                <label for="perm-filter-search" class="form-label small mb-1">بحث</label>
                                                <input type="search" id="perm-filter-search"
                                                       class="form-control cu-form-design"
                                                       placeholder="اسم الصلاحية أو الترجمة…" autocomplete="off">
                                            </div>
                                            <div class="col-6 col-md-3">
                                                <label for="perm-filter-section"
                                                       class="form-label small mb-1">{{ __('admin.perms_section_name') }}</label>
                                                <select id="perm-filter-section" class="form-select cu-form-design">
                                                    <option value="">الكل</option>
                                                    @foreach ($sectionOptions as $sectionOption)
                                                        <option value="{{ $sectionOption }}">{{ $sectionOption }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-6 col-md-3">
                                                <label for="perm-filter-group"
                                                       class="form-label small mb-1">{{ __('admin.perms_group_name') }}</label>
                                                <select id="perm-filter-group" class="form-select cu-form-design">
                                                    <option value="">الكل</option>
                                                    @foreach ($groupOptions as $groupOption)
                                                        <option value="{{ $groupOption }}">{{ $groupOption }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-6 col-md-2">
                                                <label for="perm-filter-status" class="form-label small mb-1">الحالة</label>
                                                <select id="perm-filter-status" class="form-select cu-form-design">
                                                    <option value="">الكل</option>
                                                    <option value="active">مفعّلة</option>
                                                    <option value="inactive">معطّلة</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-2">
                                            <span class="text-muted small">
                                                عرض <b id="perm-visible-count">{{ $rows->count() }}</b>
                                                من <b>{{ $rows->count() }}</b>
                                            </span>
                                            <button type="button" id="perm-filter-reset"
                                                    class="btn btn-sm btn-fr-bg-light">إعادة ضبط الفلاتر</button>
                                        </div>
                                    </div>

                                    <div class="perm-keys-table-wrap mt-2">
                                        <table
                                            class="table table-striped table-row-bordered cu-table-adj perm-keys-table mb-0">
                                            <thead>
                                            <tr class="seal-dark-bg">
                                                <th style="width:5%">#</th>
                                                <th>{{ __('admin.perms_name') }}</th>
                                                <th>{{ __('admin.perms_group_name') }}</th>
                                                <th>{{ __('admin.perms_section_name') }}</th>
                                                <th>{{ __('admin.perms_translation') }}</th>
                                                <th>الحالة</th>
                                                <th style="width:5%">{{ __('admin.edit') }}</th>
                                                <th style="width:5%">{{ __('admin.delete') }}</th>
                                            </tr>
                                            </thead>

                                            <tbody id="perm-keys-tbody">
                                            @foreach ($rows as $singleRow)
                                                @php
                                                    $rowGroup = $singleRow->display_group_name ?? $singleRow->group_name;
                                                    $rowSection = $singleRow->display_section_name ?? $singleRow->section_name;
                                                    $rowTranslation = $singleRow->translated_name ?? __("permissions.$singleRow->name");
                                                @endphp
                                                <tr class="perm-row {{ $singleRow->is_active ? '' : 'table-warning' }}"
                                                    data-name="{{ Str::lower($singleRow->name) }}"
                                                    data-translation="{{ Str::lower($rowTranslation) }}"
                                                    data-group="{{ $rowGroup }}"
                                                    data-section="{{ $rowSection }}"
                                                    data-status="{{ $singleRow->is_active ? 'active' : 'inactive' }}">
                                                    <td>{{ $singleRow->id }}</td>
                                                    <td><span class="perm-key-name">{{ $singleRow->name }}</span></td>
                                                    <td>{{ $rowGroup }}</td>
                                                    <td>{{ $rowSection }}</td>
                                                    <td style="white-space: normal">{{ $rowTranslation }}</td>
                                                    <td>
                                                        <form
                                                            action="{{ route('dash.developers.perms.toggleStatus', ['id' => $singleRow->id]) }}"
                                                            method="post" class="d-inline">
                                                            {!! csrf_field() !!}
                                                            @method('PATCH')
                                                            <button type="submit"
                                                                    class="btn btn-sm {{ $singleRow->is_active ? 'btn-success' : 'btn-secondary' }}"
                                                                    title="{{ $singleRow->is_active ? 'إيقاف مؤقت' : 'إعادة تفعيل' }}">
                                                                {{ $singleRow->is_active ? 'مفعّلة' : 'معطّلة' }}
                                                            </button>
                                                        </form>
                                                    </td>
                                                    <td>
                                                        <a href="{{ route('dash.developers.perms.index', ['id' => $singleRow->id]) }}"
                                                           title="{{ __('admin.edit') }}">
                                                            <i class="fa fa-edit"></i>
                                                        </a>
                                                    </td>
                                                    <td>
                                                        <form
                                                            action="{{ route('dash.developers.perms.delete', ['id' => $singleRow->id]) }}"
                                                            method="post" class="d-inline">
                                                            {!! csrf_field() !!}
                                                            <input type="hidden" name="_method" value="delete">
                                                            <a onclick="delete_confirm($(this),event)" style="color:#dd2c2c"
                                                               title="{{ __('admin.delete') }}">
                                                                <i class="fa fa-trash"></i>
                                                            </a>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endforeach
                                            <tr id="perm-keys-empty" class="perm-keys-empty d-none">
                                                <td colspan="8" class="text-center">لا توجد صلاحيات مطابقة للفلاتر
                                                    المحددة.
                                                </td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                </div>
                            </div>
                        </div>

                    </div>

                </div>
            </div>
        </div>

        <script>
            function delete_confirm(element, event) {
                var form = $(element).closest("form");
                event.preventDefault();
                swal.fire({
                    title: `{!! __('admin.confirm') !!}`,
                    text: `{!! __('admin.perms_keys_devs_only') !!}`,
                    icon: "warning",
                    buttons: true,
                    dangerMode: true,
                    showCloseButton: true,
                }).then((willDelete) => {
                    if (willDelete.isConfirmed) {
                        form.submit();
                    }
                });
            }

            (function () {
                var searchInput = document.getElementById('perm-filter-search');
                var sectionSelect = document.getElementById('perm-filter-section');
                var groupSelect = document.getElementById('perm-filter-group');
                var statusSelect = document.getElementById('perm-filter-status');
                var resetButton = document.getElementById('perm-filter-reset');
                var visibleCount = document.getElementById('perm-visible-count');
                var emptyRow = document.getElementById('perm-keys-empty');
                var rows = Array.prototype.slice.call(document.querySelectorAll('#perm-keys-tbody .perm-row'));

                if (!searchInput || !rows.length) {
                    return;
                }

                function applyFilters() {
                    var term = searchInput.value.trim().toLowerCase();
                    var section = sectionSelect.value;
                    var group = groupSelect.value;
                    var status = statusSelect.value;
                    var shown = 0;

                    rows.forEach(function (row) {
                        var matches =
                            (term === '' ||
                                row.dataset.name.indexOf(term) !== -1 ||
                                row.dataset.translation.indexOf(term) !== -1) &&
                            (section === '' || row.dataset.section === section) &&
                            (group === '' || row.dataset.group === group) &&
                            (status === '' || row.dataset.status === status);

                        row.classList.toggle('d-none', !matches);

                        if (matches) {
                            shown++;
                        }
                    });

                    visibleCount.textContent = shown;
                    emptyRow.classList.toggle('d-none', shown !== 0);
                }

                var debounceTimer = null;
                searchInput.addEventListener('input', function () {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(applyFilters, 150);
                });

                [sectionSelect, groupSelect, statusSelect].forEach(function (control) {
                    control.addEventListener('change', applyFilters);
                });

                resetButton.addEventListener('click', function () {
                    searchInput.value = '';
                    sectionSelect.value = '';
                    groupSelect.value = '';
                    statusSelect.value = '';
                    applyFilters();
                });
            })();
        </script>

    @else
        @include('components.no_permissions')
    @endif

@endsection
