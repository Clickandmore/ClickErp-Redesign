<form class="row g-3 from-submit-global" method="post" action="{{ route('crm.tickets.update', $obj->id) }}"
    enctype="multipart/form-data">
    @method('PUT')

    <input type="hidden" name="client_id" value="{{$obj->client_id}}">
    <input type="hidden" name="user_id" value="{{$obj->user_id}}">
    <input type="hidden" name="date" value="{{$obj->date}}">
    <input type="hidden" name="notes" value="{{$obj->notes}}">
    <div class="col-4">
        <label for="TextInput" class="form-label">{{ __("crm.status") }}</label>
        <select class="form-control show-tick ms select2" id="status" name="status" data-validation="required">
            {!! getEnumData(\App\Enums\crm\TicketStatus::cases(),"ticket_statuses" ) !!}
        </select>
    </div>
    <div class="col-4" id="end_date" style="display: none">
        <label for="TextInput" class="form-label">{{ __("crm.end_date") }}</label>
        <input type="date"  name="end_date" class="form-control" required>
    </div>


    <div class="col-12 modal-footer">
        <button class="btn btn-primary" type="submit">{{ __('buttons.save') }}</button>
        <button class="btn btn-outline-secondary" type="button"
            data-dismiss="modal">{{ __('buttons.close') }}</button>
    </div>

</form>


<script>
        $('#status').change(function() {
            var status = $(this).val();

            if (status == 'ended') {
                $('#end_date').show();
            } else  {
                $('#end_date').hide();
            }
        });
</script>