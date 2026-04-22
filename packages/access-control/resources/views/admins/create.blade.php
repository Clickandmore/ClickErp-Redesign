<form class="row g-3 from-submit-global" method="post" action="{{ $storeRoute }}" enctype="multipart/form-data">
    <div class="col-6">
        <label for="TextInput" class="form-label">الاسم </label>
        <input type="text" name="name" class="form-control" data-validation="required">
    </div>
    <div class="col-6">
        <label for="TextInput" class="form-label">الجوال </label>
        <input type="text" name="phone" class="form-control" data-validation="required">
    </div>

    <div class="col-6">
        <label for="TextInput" class="form-label">البريد الالكترونى </label>
        <input type="text" name="email" class="form-control">
    </div>
    <div class="col-6">
        <label for="TextInput" class="form-label"> كلمة المرور </label>
        <input type="password" name="password" class="form-control">
    </div>

    <input type="hidden" name="module" value="all">

    <div class="col-6">
        <label for="TextInput" class="form-label"> @lang('auth.permissions') </label>
        <select class="form-control show-tick ms" name="role_id" id="role_id" data-validation="required">
            <option value="">@lang('auth.select')</option>
            <option value="new">@lang('auth.new')</option>
            @foreach($roles as $role)
                <option value="{{ $role->id }}">{{ $role->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-12 permissions-container" style="display: none">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">{{ __('auth.permissions') }}</h6>
            </div>
            <div class="card-body">
                <div class="accordion" id="permissionsAccordion">
                    <div class="row">
                        @foreach ($models as $group => $permissions)
                            <div class="col-md-4">
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="heading-{{ $group }}">
                                        <button class="accordion-button" type="button"
                                                data-bs-toggle="collapse" data-bs-target="#collapse-{{ $group }}"
                                                aria-expanded="true" aria-controls="collapse-{{ $group }}">
                                            @lang("auth.$group")
                                        </button>
                                    </h2>
                                    <div id="collapse-{{ $group }}" class="accordion-collapse collapse show"
                                         aria-labelledby="heading-{{ $group }}" data-bs-parent="#permissionsAccordion">
                                        <div class="accordion-body">
                                            <table class="table table-bordered permissions-table">
                                                <thead>
                                                <tr>
                                                    <th>
                                                        <input type="checkbox" class="check-all-table"> @lang('charities.all')
                                                    </th>
                                                    <th>@lang('charities.all')</th>
                                                    <th>@lang('charities.create')</th>
                                                    <th>@lang('charities.read')</th>
                                                    <th>@lang('charities.update')</th>
                                                    <th>@lang('charities.delete')</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                @foreach ($permissions as $model => $permission)
                                                    <tr>
                                                        <td>{{ __("charities.$model") }}</td>
                                                        <td><input type="checkbox" class="all" data-model="{{ $model }}"></td>
                                                        <td><input type="checkbox" name="permissions[]" value="{{ $model . '-create' }}"></td>
                                                        <td><input type="checkbox" name="permissions[]" value="{{ $model . '-read' }}"></td>
                                                        <td><input type="checkbox" name="permissions[]" value="{{ $model . '-update' }}"></td>
                                                        <td><input type="checkbox" name="permissions[]" value="{{ $model . '-delete' }}"></td>
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 modal-footer">
        {!! submitButton($storeRoute) !!}
        {!! closeButton() !!}
    </div>
</form>

<script>
    $('#role_id').change(function() {
        $('.permissions-container').toggle($(this).val() === 'new');
    });
</script>
