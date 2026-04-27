<form class="row g-3 " id="sendPollForm" method="post" action="{{ url('crm/sendPoll/store') }}"
    enctype="multipart/form-data">
    @csrf
    <div class="modal-body">
        <input type="hidden" value="{{ $poll_id }}" id="userIdInput" name="poll_id">
        <label>{{ __("crm::crm.clients") }}</label>
        <select name="client_id" class="form-control">
            @foreach($clients as $client)
                <option value="{{$client->id}}">{{$client->name}}</option>
            @endforeach
        </select>
    </div>

    <div class="col-12 modal-footer">

        {!! submitButton(url('crm/sendPoll/store'), __('crm::crm.share').' <i
            class="fab fa-whatsapp"></i>','success') !!}
        {!! closeButton() !!}

    </div>

</form>
