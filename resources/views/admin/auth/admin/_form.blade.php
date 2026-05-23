@php
    $isEdit = filled($obj ?? null);
    $selectedStatus = old('status', $obj->status ?? 'active');
    $selectedSex = old('sex_type', $obj->sex_type ?? 0);
@endphp

<style>
    .admin-user-form {
        --form-border: #e5e7eb;
        --form-soft: #f8fafc;
        --form-text: #111827;
        --form-muted: #6b7280;
    }

    .admin-user-form .form-section {
        border: 1px solid var(--form-border);
        border-radius: 8px;
        padding: 1rem;
        background: #fff;
        margin-bottom: 1rem;
    }

    .admin-user-form .section-title {
        display: flex;
        align-items: center;
        gap: 0.45rem;
        color: var(--form-text);
        font-weight: 800;
        margin-bottom: 0.9rem;
        padding-bottom: 0.65rem;
        border-bottom: 1px solid var(--form-border);
    }

    .admin-user-form .form-label {
        color: var(--form-text);
        font-weight: 700;
        margin-bottom: 0.35rem;
    }

    .admin-user-form .form-control,
    .admin-user-form .form-select {
        min-height: 42px;
        border-radius: 6px;
        border-color: var(--form-border);
    }

    .admin-user-form .field-hint {
        color: var(--form-muted);
        font-size: 0.78rem;
        margin-top: 0.25rem;
    }

    .admin-user-form .sex-group {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.75rem;
    }

    .admin-user-form .sex-option input {
        display: none;
    }

    .admin-user-form .sex-option label {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.55rem;
        min-height: 42px;
        border: 1px solid var(--form-border);
        border-radius: 6px;
        background: var(--form-soft);
        color: var(--form-text);
        font-weight: 800;
        cursor: pointer;
        margin: 0;
    }

    .admin-user-form .sex-option input:checked + label {
        border-color: #2794eb;
        background: #eef7ff;
        color: #1769aa;
    }

    .admin-user-form .sex-option img {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: #fff;
    }

    .admin-user-form .modal-footer {
        padding: 0.75rem 0 0;
        border-top: 1px solid var(--form-border);
    }
</style>

<form class="admin-user-form from-submit-global" method="post" action="{{ $action }}" enctype="multipart/form-data">
    @if($isEdit)
        @method('put')
    @endif

    <div class="form-section">
        <div class="section-title">
            <i class="fa fa-user"></i>
            <span>{{ __('admin.basic_data') }}</span>
        </div>

        <div class="row g-3">
            <div class="col-md-4">
                <label for="name" class="form-label">{{ __('auth.name') }}</label>
                <input type="text" id="name" name="name" value="{{ old('name', $obj->name ?? '') }}" class="form-control" data-validation="required">
            </div>

            <div class="col-md-4">
                <label for="username" class="form-label">{{ __('admin.username') }}</label>
                <input type="text" id="username" name="username" value="{{ old('username', $obj->username ?? '') }}" class="form-control" placeholder="username">
            </div>

            <div class="col-md-4">
                <label for="email" class="form-label">{{ __('auth.email') }}</label>
                <input type="email" id="email" name="email" value="{{ old('email', $obj->email ?? '') }}" class="form-control" data-validation="required">
            </div>

            <div class="col-md-4">
                <label for="phone" class="form-label">{{ __('admin.contact_phone') }}</label>
                <input type="text" id="phone" name="phone" value="{{ old('phone', $obj->phone ?? '') }}" class="form-control" data-validation="required">
            </div>

            <div class="col-md-4">
                <label for="whatsapp" class="form-label">{{ __('admin.whatsapp_phone') }}</label>
                <input type="text" id="whatsapp" name="whatsapp" value="{{ old('whatsapp', $obj->whatsapp ?? '') }}" class="form-control">
                <div class="field-hint">{{ __('admin.leave_empty_to_use_contact_phone') }}</div>
            </div>

            <div class="col-md-4">
                <label for="verification_phone" class="form-label">{{ __('admin.verification_phone') }}</label>
                <input type="text" id="verification_phone" name="verification_phone" value="{{ old('verification_phone', $obj->verification_phone ?? '') }}" class="form-control">
                <div class="field-hint">{{ __('admin.leave_empty_to_use_contact_phone') }}</div>
            </div>

            <div class="col-md-4">
                <label for="identity_number" class="form-label">{{ __('admin.identity_number') }}</label>
                <input type="text" id="identity_number" name="identity_number" value="{{ old('identity_number', $obj->identity_number ?? '') }}" class="form-control">
            </div>

            <div class="col-md-4">
                <label for="identity_expiry_date" class="form-label">{{ __('admin.identity_expiry_date') }}</label>
                <input type="date" id="identity_expiry_date" name="identity_expiry_date" value="{{ old('identity_expiry_date', $obj->identity_expiry_date ?? '') }}" class="form-control">
            </div>

            <div class="col-md-4">
                <label class="form-label">{{ helperTrans('ingaz.sex_type') }}</label>
                <div class="sex-group">
                    <div class="sex-option">
                        <input type="radio" id="male" name="sex_type" value="0" @checked((string) $selectedSex === '0')>
                        <label for="male"><img src="{{ asset('site/assets/images/user1.webp') }}" alt="{{ __('admin.male') }}"> {{ __('admin.male') }}</label>
                    </div>
                    <div class="sex-option">
                        <input type="radio" id="female" name="sex_type" value="1" @checked((string) $selectedSex === '1')>
                        <label for="female"><img src="{{ asset('site/assets/images/user2.webp') }}" alt="{{ __('admin.female') }}"> {{ __('admin.female') }}</label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="form-section">
        <div class="section-title">
            <i class="fa fa-shield"></i>
            <span>{{ __('admin.account_settings') }}</span>
        </div>

        <div class="row g-3">
            <div class="col-md-4">
                <label for="password" class="form-label">{{ $isEdit ? __('admin.new_password') : __('auth.password') }}</label>
                <input type="password" id="password" name="password" class="form-control" @if(! $isEdit) data-validation="required" @endif placeholder="{{ $isEdit ? __('admin.leave_password_empty') : '' }}">
            </div>

            <div class="col-md-4">
                <label for="admin_type" class="form-label">{{ __('admin.user_type') }}</label>
                <select class="form-select show-tick ms" name="admin_type" id="admin_type" data-validation="required">
                    {!! getEnumData($admin_typeis, old('admin_type', $obj->admin_type ?? '')) !!}
                </select>
            </div>

            <div class="col-md-4">
                <label for="role_id" class="form-label">{{ __('admin.role_id') }}</label>
                <select name="role_id" id="role_id" class="form-select show-tick ms" data-validation="required">
                    <option value="" disabled @selected(! old('role_id', $obj->role_id ?? null))>{{ __('admin.choose') }}</option>
                    @forelse ($roles ?? [] as $id => $name)
                        <option value="{{ $id }}" @selected((string) old('role_id', $obj->role_id ?? '') === (string) $id)>{{ $name }}</option>
                    @empty
                    @endforelse
                </select>
            </div>

            <div class="col-md-4">
                <label for="status" class="form-label">{{ __('admin.status') }}</label>
                <select name="status" id="status" class="form-select">
                    <option value="active" @selected($selectedStatus === 'active')>{{ __('admin.active') }}</option>
                    <option value="suspended" @selected($selectedStatus === 'suspended')>{{ __('admin.suspended') }}</option>
                </select>
            </div>

            <div class="col-md-4">
                <label for="sort" class="form-label">{{ __('admin.sort') }}</label>
                <input type="number" name="sort" id="sort" class="form-control" value="{{ old('sort', $obj->sort ?? 999) }}">
            </div>
        </div>
    </div>

    @if($hasInvalidCurrentRole ?? false)
        <div class="alert alert-warning">
            هذا الموظف مرتبط حالياً بالدور
            <strong>{{ $obj->role?->name }}</strong>
            على الحارس
            <strong>{{ $obj->role?->guard_name }}</strong>
            وهو غير صالح لمشرفي لوحة التحكم. اختر دوراً من أدوار الإدارة ثم احفظ التعديل.
        </div>
    @endif

    <div class="form-section">
        <div class="section-title">
            <i class="fa fa-sticky-note"></i>
            <span>{{ __('admin.note') }}</span>
        </div>
        <textarea name="note" id="note" class="form-control" rows="3" placeholder="{{ __('admin.note') }}">{{ old('note', $obj->note ?? '') }}</textarea>
    </div>

    <div class="col-12 modal-footer">
        <button class="btn btn-primary" type="submit">
            <i class="fa fa-save"></i> {{ __('buttons.save') }}
        </button>
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">{{ __('buttons.close') }}</button>
    </div>
</form>
