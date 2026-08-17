<form class="row g-3 from-submit-global" method="post" action="{{route("crm.questions.update",$obj->id)}}"
      enctype="multipart/form-data">
    @method('Put')
    <div class="col-12">
        <label for="TextInput" class="form-label">{{ __("crm.type") }}</label>
        <select class="form-control show-tick select2" name="type" id="type"  data-validation="required">
            <option value=""> {{ __("crm.Choose") }} </option>
            <option value="choices" {{ $obj->type == 'choices'? "selected" : ''}}> {{ __("crm.choices") }} </option>
            <option value="essay" {{$obj->type == 'essay'? "selected" : ''}}> {{ __("crm.essay") }} </option>
        </select>
    </div>

    <div class="col-12">
        <label for="TextInput" class="form-label">{{__('crm.question')}}</label>
        <input type="text" value="{{$obj->name}}" name="name" class="form-control" data-validation="required">
    </div>

    <input type="hidden" name="poll_id" value="{{$obj->poll_id}}">
    <div class="col-12 "  style=" padding-top: 20px !important; {{$obj->type != 'choices'? "display:none" : ''}}"  id="answers">
        <table class="table  table-bordered  " >
            <thead>
            <tr>
                <th scope="row" >{{__('crm.answer')}}</th>

            </tr>
            </thead>
            <tbody id="json_array" class="content" data-mfield-options='{"section": ".y-group","btnAdd":"#btnAdd-1","btnRemove":".btnRemove"}'>
            @forelse($obj->questionsAnswers as $answer)
                <tr class="y-group">
                    <td>
                        <input type="text" name="answers[{{$loop->iteration}}][value]" value="{{$answer->value}}" class="form-control index-plus" data-validation="required">
                    </td>


                    <td>
                        <i class="fa fa-plus-circle" id="btnAdd-1"></i>
                        <i class="fa fa-trash-o btnRemove"></i>
                    </td>

                </tr>
            @empty
            <tr class="y-group">
                <td>
                    <input type="text" name="answers[0][value]"  class="form-control index-plus" data-validation="required">
                </td>


                <td>
                    <i class="fa fa-plus-circle" id="btnAdd-1"></i>
                    <i class="fa fa-trash btnRemove"></i>
                </td>

            </tr>
            @endforelse
            </tbody>
        </table>
    </div>


    <div class="col-12 modal-footer">
        {!! submitButton(route("crm.questions.update",$obj->id) ) !!}
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
