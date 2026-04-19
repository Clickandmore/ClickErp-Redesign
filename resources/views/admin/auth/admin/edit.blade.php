@if(@AdminGuardHasPerm(\App\PermKeys::USERS_CONTROL))


    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
    <style>
        .select2-container {
            z-index: 9999;
        }
    </style>
    <form class="row g-3 from-submit-global" method="post" action="{{ route('admins.update', $obj->id) }}"
        enctype="multipart/form-data">
        @method('put')
        <div class="col-4">
            <label for="TextInput" class="form-label">الاسم </label>
            <input type="text" name="name" value="{{ $obj->name }}" class="form-control" data-validation="required">
        </div>
        <div class="col-4">
            <label for="TextInput" class="form-label">رقم الاتصال </label>
            <input type="text" name="phone" value="{{ $obj->phone }}" class="form-control" data-validation="required">
        </div>


        <div class="col-4">
            <label for="TextInput" class="form-label">واتساب </label>
            <input type="text" name="whatsapp" class="form-control" value="{{ $obj->whatsapp_phone }}">
        </div>

        <div class="col-4">
            <label for="TextInput" class="form-label">رقم التحقق ورسائل الطلب </label>
            <input type="text" name="verification_phone" class="form-control" value="{{ $obj->verification_phone }}">
        </div>

        <div class="col-4">
            <label for="TextInput" class="form-label">البريد الالكترونى </label>
            <input type="text" name="email" value="{{ $obj->email }}" class="form-control">
        </div>

        <div class="col-4">
            <label for="status" class="form-label"> {{ __('admin.status') }} </label>
            <select name="status" id="status" class="form-control">
                <option value="active" @selected($obj->status === 'active')>{{ __('admin.active') }}</option>
                <option value="suspended" @selected($obj->status === 'suspended')>{{ __('admin.suspended') }}</option>
            </select>
        </div>

        {{-- sort --}}
        <div class="col-4">
            <label for="sort" class="form-label"> {{ __('admin.sort') }} </label>
            <input type="number" name="sort" id="sort" class="form-control" value="{{$obj->sort ?? 999}}">
        </div>


        <div class="col-4">
            <label for="TextInput" class="form-label"> نوع المستخدم </label>
            <select class="form-control show-tick ms" name="admin_type" id="admin_type" data-validation="required">
                {!! getEnumData($admin_typeis, "admin_typeis", $obj->admin_type) !!}
            </select>
        </div>


        <div class="col-4">
            <label for="TextInput" class="form-label"> {{ __('admin.role_id') }} </label>
            <select name="role_id" id="role_id" class="form-control show-tick ms">
                <option value="" disabled @selected($hasInvalidCurrentRole ?? false)>{{ __('admin.choose') }}</option>
                @forelse (@$roles ?? [] as $id => $name)
                    <option value="{{ $id }}" @if (@$obj->role_id == $id) selected @endif>
                        {{ $name }}
                    </option>
                @empty
                @endforelse
            </select>
        </div>

        @if($hasInvalidCurrentRole ?? false)
            <div class="col-12">
                <div class="alert alert-warning mb-0">
                    هذا الموظف مرتبط حالياً بالدور
                    <strong>{{ $obj->role?->name }}</strong>
                    على الحارس
                    <strong>{{ $obj->role?->guard_name }}</strong>
                    وهو غير صالح لمشرفي لوحة التحكم. اختر دوراً من أدوار الإدارة ثم احفظ التعديل.
                </div>
            </div>
        @endif


        {{-- <div class="row">
            <div class="col-12">
                <label for="roles" class="form-label mt-3">{{ helperTrans('ingaz.roles') }}</label>
                <select name="roles[]" id="roles" class="form-control js-example-basic-multiple" multiple
                    data-validation="required">
                    <option value="" disabled>{{ helperTrans('ingaz.choose') }}</option>
                    @foreach($roles as $role)
                    <option value="{{ $role->id }}" {{ in_array($role->id, $adminRole) ? 'selected' : '' }}>{{
                        $role->display_name }}</option>
                    @endforeach
                </select>
            </div>
        </div> --}}


        <div class="row">
            <label for="TextInput" class="form-label mt-3"> الجنس </label>
            <div class="sex-option col-6">
                <input type="radio" id="male" name="sex_type" value="0" {{($obj->sex_type == 0) ? 'checked' : ''}}>
                <label for="male"><img src="{{ asset('site/assets/images/user1.webp') }}" alt="ذكر"> ذكر </label>
            </div>

            <div class="sex-option col-6">
                <input type="radio" id="female" name="sex_type" value="1" {{($obj->sex_type == 1) ? 'checked' : ''}}>
                <label for="female"><img src="{{ asset('site/assets/images/user2.webp') }}" alt="انثى"> انثى </label>
            </div>
        </div>

        <div class="col-12">
            <label for="note" class="form-label">{{ __('admin.note') }}</label>
            <textarea name="note" id="note" class="form-control" rows="3" placeholder="{{ __('admin.note') }}">{{ $obj->note }}</textarea>
        </div>


        <div class="col-12 modal-footer">
            <button class="btn btn-primary" type="submit">{{ __('buttons.save') }}</button>
            <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">{{ __('buttons.close')
                        }}</button>
        </div>

    </form>
    <style>
        .sex-option {
            display: inline-flex;
            margin-top: 20px;
            align-items: center;
            margin-bottom: 10px;
        }

        .sex-option input[type="radio"] {
            display: none;
        }

        .sex-option label {
            display: flex;
            align-items: center;
            cursor: pointer;
            margin-left: 10px;
            border-radius: 30px;
            padding: 0 30px;
        }

        .sex-option label:hover {
            color: #2076B8;
            /* Change color on hover if desired */
        }

        .sex-option input[type="radio"]:checked+label {
            color: #2076B8;
            /* Change label color when the radio button is checked */
        }

        .sex-option input[type="radio"]:checked+label {
            background-color: lightblue;
            /* Change background color when the radio button is checked */
        }

        .sex-option img {
            margin-left: 5px;
            border-radius: 50%;
            max-width: 60px;
            max-height: 60px;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function () {
            $('.js-example-basic-multiple').select2();
        });

    </script>

@else
    @include('components.no_permissions')
@endif
