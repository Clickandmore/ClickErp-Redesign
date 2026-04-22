<form class="row g-3 from-submit-global" method="post" action="{{ $updateRoute }}"
      enctype="multipart/form-data">
    @method('put')
    <div class="col-4">
        <label for="TextInput" class="form-label">الاسم </label>
        <input type="text" name="name" value="{{ $obj->name }}" class="form-control" data-validation="required">
    </div>


    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">{{ __("auth.permissions") }}</h6>
            </div>
            <div class="card-body">
                <div class="accordion" id="permissionsAccordion">
                    <div class="row">

                        @foreach ($permissions as $group => $permissions)
                            @if(in_array($group, $maps))
                                @continue
                            @endif
                            <div class="col-md-4">
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="heading-{{ $group }}">
                                        <button class="accordion-button " type="button"
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
                                                        <input type="checkbox"
                                                               class="check-all-table"> @lang('charities.all')
                                                    </th>
                                                    <th>@lang('charities.all')</th>
                                                    <th>@lang('charities.create')</th>
                                                    <th>@lang('charities.read')</th>
                                                    <th>@lang('charities.update')</th>
                                                    <th>@lang('charities.delete')</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                @foreach ($permissions as $group => $permission)
                                                    @if(in_array($group,$maps))
                                                        @continue
                                                    @endif
                                                    <tr>
                                                        <td>{{ __("charities.$group") }}</td>

                                                        <td>
                                                            <input type="checkbox" class="check-all-row" data-group="{{ $group }}"
                                                                {{ in_array("$group-create", getByRolePermissions($obj)) &&
                                                                   in_array("$group-read", getByRolePermissions($obj)) &&
                                                                   in_array("$group-update", getByRolePermissions($obj)) &&
                                                                   in_array("$group-delete", getByRolePermissions($obj))
                                                                ? 'checked' : '' }}>
                                                        </td>

                                                        @foreach (['create', 'read', 'update', 'delete'] as $action)
                                                            <td>
                                                                <input type="checkbox" name="permissions[]" value="{{ $group . '-' . $action }}"
                                                                    {{ in_array("$group-$action", getByRolePermissions($obj)) ? 'checked' : '' }}>
                                                            </td>
                                                        @endforeach
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
        <button class="btn btn-primary" type="submit">{{ __('buttons.save') }}</button>
        <button class="btn btn-outline-secondary" type="button"
                data-bs-dismiss="modal">{{ __('buttons.close') }}</button>
    </div>

</form>


<script>
    $('.check-all-row').on('change', function () {
        var isChecked = $(this).is(':checked');
        var $row = $(this).closest('tr');

        $row.find('input[type="checkbox"]').not(this).prop('checked', isChecked);
    });

    $('input[type="checkbox"]').not('.check-all-row').on('change', function () {
        var $row = $(this).closest('tr');
        var allChecked = $row.find('input[type="checkbox"]').not('.check-all-row').length ===
            $row.find('input[type="checkbox"]:checked').not('.check-all-row').length;

        $row.find('.check-all-row').prop('checked', allChecked);
    });
</script>
