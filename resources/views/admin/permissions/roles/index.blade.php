@extends('admin.layout.indexs.index')

@section('page-title')
    {{ __('admin.roles_name') }}
@endsection

@section('content')
    @php
        $canViewRoles = @AdminGuardHasPerm(\App\PermKeys::ROLES_VIEW);
        $canControlRoles = @AdminGuardHasPerm(\App\PermKeys::ROLES_CONTROL) || @AdminGuardHasPerm(\App\PermKeys::ROLES_CREATE) || @AdminGuardHasPerm(\App\PermKeys::ROLES_EDIT);
        $canDeleteRoles = @AdminGuardHasPerm(\App\PermKeys::ROLES_DELETE);
        $canManagePermissions = @AdminGuardHasPerm(\App\PermKeys::SET_PERMS);
        $roles = $roles ?? collect();
    @endphp

    @if ($canViewRoles || $canControlRoles)
        <style>
            .roles-page {
                color: #1f2937;
                --roles-border: #dbe4ea;
                --roles-border-strong: #c5d2db;
                --roles-surface: #f8fbfc;
                --roles-text-muted: #64748b;
                --roles-accent: #0f766e;
                --roles-accent-strong: #115e59;
                --roles-accent-soft: #ccfbf1;
                --roles-warning: #b45309;
                --roles-warning-soft: #ffedd5;
                --roles-danger: #b91c1c;
            }

            .roles-shell {
                display: grid;
                gap: 16px;
            }

            .roles-panel {
                background: #ffffff;
                border: 1px solid var(--roles-border);
                border-radius: 20px;
                box-shadow: 0 12px 30px rgba(15, 23, 42, 0.05);
                overflow: hidden;
            }

            .roles-header,
            .roles-form-panel,
            .roles-list-panel {
                padding: 22px;
            }

            .roles-header {
                background:
                    radial-gradient(circle at top left, rgba(15, 118, 110, 0.10), transparent 32%),
                    linear-gradient(180deg, #fcffff 0%, #f6fafb 100%);
            }

            .roles-title {
                margin: 0;
                font-size: 1.4rem;
                font-weight: 900;
                color: #0f172a;
            }

            .roles-subtitle {
                margin: 8px 0 0;
                color: var(--roles-text-muted);
                line-height: 1.8;
            }

            .roles-section-head {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 12px;
                margin-bottom: 16px;
            }

            .roles-section-title {
                margin: 0;
                font-size: 1.02rem;
                font-weight: 900;
                color: #0f172a;
            }

            .roles-section-text {
                margin: 6px 0 0;
                color: var(--roles-text-muted);
                line-height: 1.8;
            }

            .roles-badge {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-height: 30px;
                padding: 0 12px;
                border-radius: 999px;
                border: 1px solid var(--roles-border);
                background: var(--roles-surface);
                color: #334155;
                font-size: 0.78rem;
                font-weight: 800;
                white-space: nowrap;
            }

            .roles-badge.is-editing {
                background: var(--roles-accent-soft);
                border-color: #8dd3c7;
                color: var(--roles-accent-strong);
            }

            .roles-form {
                display: grid;
                grid-template-columns: minmax(0, 1fr) auto;
                gap: 14px;
                align-items: end;
            }

            .roles-form__field {
                display: grid;
                gap: 8px;
            }

            .roles-form__field label {
                font-weight: 800;
                color: #0f172a;
            }

            .roles-form__field input,
            .roles-search {
                min-height: 48px;
                border: 1px solid var(--roles-border-strong);
                border-radius: 14px;
                padding: 0 16px;
                color: #111827;
                background: #ffffff;
            }

            .roles-form__field input:focus,
            .roles-search:focus {
                outline: none;
                border-color: #83c5be;
                box-shadow: 0 0 0 4px rgba(15, 118, 110, 0.08);
            }

            .roles-form__hint {
                color: var(--roles-text-muted);
                font-size: 0.84rem;
            }

            .roles-form__actions {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
            }

            .roles-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                min-height: 44px;
                padding: 0 16px;
                border-radius: 13px;
                border: 1px solid var(--roles-border-strong);
                background: #ffffff;
                color: #1f2937;
                font-weight: 800;
                text-decoration: none;
                transition: 0.18s ease;
            }

            .roles-btn:hover {
                color: #111827;
                background: var(--roles-surface);
            }

            .roles-btn--primary {
                background: var(--roles-accent);
                border-color: var(--roles-accent);
                color: #ffffff;
            }

            .roles-btn--primary:hover {
                background: var(--roles-accent-strong);
                border-color: var(--roles-accent-strong);
                color: #ffffff;
            }

            .roles-search-wrap {
                margin-bottom: 16px;
            }

            .roles-list {
                display: grid;
                gap: 12px;
            }

            .role-row {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 16px;
                padding: 16px 18px;
                border: 1px solid var(--roles-border);
                border-radius: 18px;
                background: #ffffff;
                transition: 0.18s ease;
            }

            .role-row:hover {
                border-color: #bfd5dd;
                box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
            }

            .role-row.is-editing {
                border-color: #83c5be;
                background: #fcfffe;
            }

            .role-row__main {
                display: flex;
                align-items: center;
                gap: 16px;
                min-width: 0;
                flex: 1;
            }

            .role-row__index {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 42px;
                min-width: 42px;
                height: 42px;
                border-radius: 12px;
                background: var(--roles-surface);
                border: 1px solid var(--roles-border);
                color: #475569;
                font-weight: 900;
            }

            .role-row__content {
                min-width: 0;
                flex: 1;
            }

            .role-row__name {
                margin: 0;
                font-size: 1rem;
                font-weight: 900;
                color: #0f172a;
                word-break: break-word;
            }

            .role-row__meta {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                margin-top: 8px;
            }

            .role-pill {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-height: 30px;
                padding: 0 12px;
                border-radius: 999px;
                border: 1px solid var(--roles-border);
                background: var(--roles-surface);
                color: #334155;
                font-size: 0.78rem;
                font-weight: 800;
            }

            .role-pill.is-protected {
                background: var(--roles-warning-soft);
                border-color: #fdba74;
                color: var(--roles-warning);
            }

            .role-pill.is-linked {
                background: #eef2ff;
                border-color: #c7d2fe;
                color: #4338ca;
            }

            .role-pill.is-editing {
                background: var(--roles-accent-soft);
                border-color: #8dd3c7;
                color: var(--roles-accent-strong);
            }

            .role-row__actions {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
                justify-content: flex-end;
            }

            .roles-action-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                min-height: 40px;
                padding: 0 14px;
                border-radius: 12px;
                border: 1px solid var(--roles-border-strong);
                background: #ffffff;
                color: #111827;
                font-weight: 800;
                text-decoration: none;
                transition: 0.18s ease;
            }

            .roles-action-btn:hover {
                background: var(--roles-surface);
                color: #111827;
            }

            .roles-action-btn--primary {
                background: var(--roles-accent-soft);
                border-color: #8dd3c7;
                color: var(--roles-accent-strong);
            }

            .roles-action-btn--danger {
                background: #fef2f2;
                border-color: #fecaca;
                color: var(--roles-danger);
            }

            .roles-action-btn.is-disabled {
                background: #f8fafc;
                color: #94a3b8;
                border-style: dashed;
                cursor: not-allowed;
            }

            .roles-empty {
                display: none;
                padding: 22px;
                border: 1px dashed var(--roles-border-strong);
                border-radius: 18px;
                text-align: center;
                color: var(--roles-text-muted);
                background: #fbfdff;
            }

            .roles-empty.is-visible {
                display: block;
            }

            @media (max-width: 991.98px) {
                .roles-section-head,
                .roles-form,
                .role-row {
                    flex-direction: column;
                    align-items: stretch;
                }

                .roles-form {
                    grid-template-columns: 1fr;
                }
            }

            @media (max-width: 767.98px) {
                .roles-header,
                .roles-form-panel,
                .roles-list-panel {
                    padding: 18px;
                }

                .roles-form__actions,
                .role-row__actions {
                    width: 100%;
                }

                .roles-btn,
                .roles-action-btn {
                    width: 100%;
                }
            }
        </style>

        <div class="page_content roles-page">
            <div class="roles-shell">
                <section class="roles-panel roles-header">
                    <h2 class="roles-title">إدارة الأدوار</h2>
                    <p class="roles-subtitle">
                        صفحة أبسط ومرتبة: إضافة أو تعديل الدور من الأعلى، ثم قائمة واضحة بكل الأدوار في الأسفل.
                    </p>
                </section>

                @include('components.session-messages')

                @if ($canControlRoles)
                    <section id="roleFormSection" class="roles-panel roles-form-panel">
                        <div class="roles-section-head">
                            <div>
                                <h3 class="roles-section-title">{{ $editRole ? 'تعديل الدور' : 'إضافة دور جديد' }}</h3>
                                <p class="roles-section-text">
                                    اكتب اسم الدور فقط، ثم احفظه. بعد ذلك يمكنك إدارة صلاحياته من نفس القائمة.
                                </p>
                            </div>

                            <span class="roles-badge {{ $editRole ? 'is-editing' : '' }}">
                                {{ $editRole ? 'جاري تعديل: ' . $editRole->name : 'نموذج إنشاء جديد' }}
                            </span>
                        </div>

                        <form method="post" action="{{ route('dash.roles.store') }}" enctype="multipart/form-data" class="roles-form">
                            {{ csrf_field() }}

                            <div class="roles-form__field">
                                <label for="roleNameInput">{{ __('admin.roles_name') }}</label>
                                <input
                                    id="roleNameInput"
                                    name="name"
                                    type="text"
                                    value="{{ $editRole->name ?? '' }}"
                                    placeholder="مثال: customer_service_manager"
                                    required>
                                <div class="roles-form__hint">
                                    يتم إنشاء الأدوار وتعديلها داخل لوحة الإدارة على الحارس <code>admin</code>.
                                </div>
                            </div>

                            @if ($editRole)
                                <input type="hidden" name="edit_id" value="{{ $editRole->id }}">
                            @endif

                            <div class="roles-form__actions">
                                <button type="submit" class="roles-btn roles-btn--primary">
                                    <i class="fa fa-check"></i>
                                    <span>{{ __('admin.confirm') }}</span>
                                </button>

                                @if ($editRole)
                                    <a href="{{ route('dash.roles.index') }}" class="roles-btn">
                                        <i class="fa fa-xmark"></i>
                                        <span>{{ __('admin.cancel') }}</span>
                                    </a>
                                @endif
                            </div>
                        </form>
                    </section>
                @endif

                @if ($canViewRoles)
                    <section class="roles-panel roles-list-panel">
                        <div class="roles-section-head">
                            <div>
                                <h3 class="roles-section-title">قائمة الأدوار</h3>
                                <p class="roles-section-text">
                                    عرض مباشر وواضح لكل دور، مع أزرار التعديل والصلاحيات والحذف في نفس السطر.
                                </p>
                            </div>

                            <span class="roles-badge">عدد الأدوار: {{ $roles->count() }}</span>
                        </div>

                        <div class="roles-search-wrap">
                            <input id="rolesSearch" type="search" class="roles-search" placeholder="ابحث باسم الدور">
                        </div>

                        <div class="roles-list" id="rolesList">
                            @forelse ($roles as $singleRole)
                                @php
                                    $linkedAdminsCount = (int) ($singleRole->linked_admins_count ?? 0);
                                    $permissionsCount = (int) ($singleRole->permissions_count ?? 0);
                                    $isProtected = (bool) ($singleRole->is_protected ?? false);
                                    $isEditing = $editRole && (int) $editRole->id === (int) $singleRole->id;
                                @endphp

                                <article class="role-row {{ $isEditing ? 'is-editing' : '' }}" data-role-row data-search="{{ mb_strtolower($singleRole->name) }}">
                                    <div class="role-row__main">
                                        <span class="role-row__index">{{ $loop->iteration }}</span>

                                        <div class="role-row__content">
                                            <h4 class="role-row__name">{{ $singleRole->name }}</h4>

                                            <div class="role-row__meta">
                                                <span class="role-pill">المشرفون: {{ $linkedAdminsCount }}</span>
                                                <span class="role-pill">الصلاحيات: {{ $permissionsCount }}</span>

                                                @if ($isProtected)
                                                    <span class="role-pill is-protected">دور أساسي</span>
                                                @endif

                                                @if ($linkedAdminsCount > 0)
                                                    <span class="role-pill is-linked">مرتبط بمشرفين</span>
                                                @endif

                                                @if ($isEditing)
                                                    <span class="role-pill is-editing">قيد التعديل</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <div class="role-row__actions">
                                        @if ($canManagePermissions)
                                            <a class="roles-action-btn roles-action-btn--primary" href="{{ route('dash.perms.setPerms', ['id' => $singleRole->id]) }}">
                                                <i class="fa fa-key"></i>
                                                <span>الصلاحيات</span>
                                            </a>
                                        @endif

                                        @if ($canControlRoles)
                                            <a class="roles-action-btn" href="{{ route('dash.roles.index', ['id' => $singleRole->id]) }}">
                                                <i class="fa fa-pen"></i>
                                                <span>تعديل</span>
                                            </a>
                                        @endif

                                        @if ($canDeleteRoles)
                                            @if ($isProtected || $linkedAdminsCount > 0)
                                                <span class="roles-action-btn is-disabled">
                                                    <i class="fa fa-ban"></i>
                                                    <span>{{ $isProtected ? 'محمي' : 'غير قابل للحذف' }}</span>
                                                </span>
                                            @else
                                                <form action="{{ route('dash.roles.delete', ['id' => $singleRole->id]) }}" method="post">
                                                    {!! csrf_field() !!}
                                                    <input type="hidden" name="_method" value="delete">
                                                    <a onclick="delete_confirm($(this), event)" class="roles-action-btn roles-action-btn--danger">
                                                        <i class="fa fa-trash"></i>
                                                        <span>حذف</span>
                                                    </a>
                                                </form>
                                            @endif
                                        @endif
                                    </div>
                                </article>
                            @empty
                                <div class="roles-empty is-visible" id="rolesEmptyState">
                                    لا توجد أدوار متاحة حاليًا.
                                </div>
                            @endforelse
                        </div>

                        @if ($roles->isNotEmpty())
                            <div class="roles-empty" id="rolesEmptyState">
                                لا توجد نتائج مطابقة لعبارة البحث.
                            </div>
                        @endif
                    </section>
                @endif
            </div>
        </div>

        <script>
            function delete_confirm(element, event) {
                var form = $(element).closest("form");
                event.preventDefault();
                swal.fire({
                    title: `{!! __('admin.confirm') !!}`,
                    text: `{!! __('admin.confirm_delete') !!}`,
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

            document.addEventListener('DOMContentLoaded', function () {
                const searchInput = document.getElementById('rolesSearch');
                const roleRows = Array.from(document.querySelectorAll('[data-role-row]'));
                const emptyState = document.getElementById('rolesEmptyState');
                const roleNameInput = document.getElementById('roleNameInput');

                function applySearch() {
                    const query = (searchInput?.value || '').toLowerCase().trim();
                    let visibleRows = 0;

                    roleRows.forEach((row) => {
                        const haystack = (row.dataset.search || '').toLowerCase();
                        const shouldShow = query === '' || haystack.includes(query);

                        row.classList.toggle('d-none', !shouldShow);

                        if (shouldShow) {
                            visibleRows++;
                        }
                    });

                    if (emptyState) {
                        emptyState.classList.toggle('is-visible', roleRows.length > 0 && visibleRows === 0);
                    }
                }

                if (searchInput) {
                    searchInput.addEventListener('input', applySearch);
                }

                if (roleNameInput && {{ $editRole ? 'true' : 'false' }}) {
                    roleNameInput.focus();
                    roleNameInput.select();
                }

                applySearch();
            });
        </script>
    @else
        @include('components.no_permissions')
    @endif
@endsection
