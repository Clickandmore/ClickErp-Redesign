<form class="row g-3 from-submit-global"
      method="post" action="{{route("crm.questions.store")}}" enctype="multipart/form-data">

    <div class="col-12">
        <label for="type" class="form-label">{{ __("crm::crm.type") }}</label>
        <select class="form-control show-tick select2" name="type" id="type" data-validation="required">
            <option value=""> {{ __("crm::crm.Choose") }} </option>
            <option value="choices"> {{ __("crm::crm.choices") }} </option>
            <option value="essay"> {{ __("crm::crm.essay") }} </option>
        </select>
    </div>

    <div class="col-12">
        <label for="TextInput" class="form-label">{{__('crm::crm.question')}}</label>
        <input type="text" name="name" class="form-control" data-validation="required">
    </div>


    <input type="hidden" name="poll_id" value="{{request('poll_id')}}">
    <div class="col-12" style="padding-top: 20px !important;" id="answers">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th scope="col">{{ __('crm::crm.answer') }}</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody id="json_array" class="content" data-mfield-options='{"section": ".y-group","btnAdd":"#btnAdd-1","btnRemove":".btnRemove"}'>
                <tr class="y-group">
                    <td>
                        <input type="text" name="answers[0][value]" class="form-control index-plus" data-validation="required">
                    </td>
                    <td>
                        <i class="fa fa-plus-circle" id="btnAdd-1"></i>
                        <i class="fa fa-trash btnRemove"></i>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="col-12 modal-footer">
        {!! submitButton(route("crm.questions.store")) !!}
        {!! closeButton() !!}
    </div>

</form>

<script>
    $('#type').change(function() {
        var type = $(this).val();
        if (type == 'choices' ) {
            $('#answers').show();
        } else {
            $('#answers').hide();
        }
    });
</script>
