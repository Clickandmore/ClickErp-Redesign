<form class="row g-3 from-submit-global" method="post" action="{{ route('crm.user-polls.store') }}"
    enctype="multipart/form-data">


    <input type="hidden" value="{{ $poll_id }}" id="userIdInput" name="poll_id">

    <div class="col-12">
        <label>{{ __("crm::crm.users") }}</label>
        <select name="admin_id[]" class="form-control select2" multiple id="userSelect">
            <option value="all">{{ __("crm::crm.All") }}</option> <!-- Added All option -->
            @foreach($users as $user)
                <option value="{{$user->id}}">{{$user->name}}</option>
            @endforeach
        </select>
    </div>

    <div class="col-12 modal-footer">
        {!! submitButton( route('crm.user-polls.store') ) !!}
        {!! closeButton() !!}
    </div>

</form>

<script>
    $(document).ready(function() {
        $('#userSelect').on('change', function() {
            var selectedOptions = $(this).val();

            if (selectedOptions.includes('all')) {
                $(this).val(['all']);
            } else {
                $(this).find('option[value="all"]').prop('selected', false);
            }
            $(this).trigger('change.select2');
        });
    });
</script>
