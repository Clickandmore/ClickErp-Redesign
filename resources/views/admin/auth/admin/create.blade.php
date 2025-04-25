@if(@AdminGuardHasPerm(\App\PermKeys::USERS_CONTROL))

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
    <style>
        .select2-container {
            z-index: 9999;
        }
    </style>
    <form class="row g-3 from-submit-global" method="post" action="{{route("admins.store")}}" enctype="multipart/form-data">
        <div class="col-4">
            <label for="TextInput" class="form-label">الاسم </label>
            <input type="text" name="name" class="form-control" data-validation="required">
        </div>
        <div class="col-4">
            <label for="TextInput" class="form-label">الجوال </label>
            <input type="text" name="phone" class="form-control" data-validation="required">
        </div>

        <div class="col-4">
            <label for="TextInput" class="form-label">واتساب </label>
            <input type="text" name="whatsapp" class="form-control">
        </div>

        <div class="col-4">
            <label for="TextInput" class="form-label">البريد الالكترونى </label>
            <input type="text" name="email" class="form-control">
        </div>
        <div class="col-4">
            <label for="TextInput" class="form-label"> كلمة المرور </label>
            <input type="password" name="password" placeholder="" class="form-control">
        </div>

        <div class="col-4">
            <label for="TextInput" class="form-label"> نوع المستخدم </label>
            <select class="form-control show-tick ms" name="admin_type" id="admin_type" data-validation="required">
                {!! getEnumData($admin_typeis, "admin_typeis") !!}
            </select>
        </div>

        {{-- sort --}}
        <div class="col-4">
            <label for="sort" class="form-label"> {{ __('admin.sort') }} </label>
            <input type="number" name="sort" id="sort" class="form-control">
        </div>

        <div class="col-4">
            <label for="TextInput" class="form-label"> {{ __('admin.role_id') }} </label>
            <select name="role_id" id="role_id" class="form-control show-tick ms">
                <option value="" disabled>{{ __('admin.choose') }}</option>
                @forelse (@$roles ?? [] as $id => $name)
                    <option value="{{ $id }}">
                        {{ $name }}
                    </option>
                @empty
                @endforelse
            </select>
        </div>

        {{-- <div class="row">
            <div class="col-12">
                <label for="TextInput" class="form-label mt-3"> {{ helperTrans('ingaz.roles') }} </label>
                <select name="roles[]" class="form-control js-example-basic-multiple" multiple data-validation="required">
                    <option value="" disabled>{{helperTrans('ingaz.choose')}}</option>
                    @foreach($roles as $role)
                    <option value="{{ $role->id }}">{{ $role->display_name }}</option>
                    @endforeach
                </select>
            </div>
        </div> --}}

        <div class="row">
            <label for="TextInput" class="form-label mt-3"> الجنس </label>
            <div class="sex-option col-6">
                <input type="radio" id="male" name="sex_type" value="0">
                <label for="male"><img src="{{getFile('images/male.png')}}" alt="ذكر"> ذكر </label>
            </div>

            <div class="sex-option col-6">
                <input type="radio" id="female" name="sex_type" value="1">
                <label for="female"><img src="{{getFile('images/female.avif')}}" alt="انثي"> انثي </label>
            </div>
        </div>


        <div class="col-12 modal-footer">
            <button class="btn btn-primary" type="submit">{{__("buttons.save")}}</button>
            <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">{{__("buttons.close")}}</button>
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