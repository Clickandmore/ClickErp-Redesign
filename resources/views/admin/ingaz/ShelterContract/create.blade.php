@extends('admin.layout.indexs.index')
@section('page-title')
{{helperTrans("ingaz.add Shelter contract")}}
@endsection
@section('content')
@if (@AdminGuardHasPerm(\App\PermKeys::SHELTER_CONTRACT_CONTROL))

    <form class="row g-3 contractForm" method="post" action="{{route("ShelterContract.store")}}">
        @csrf
        <div class="row p-3">

            <div class="col-4">
                <label for="TextInput" class="form-label">{{helperTrans("ingaz.contracts")}}</label>
                <select name="contract_id" class="form-select form-control select2" data-validation="required">
                    <option disabled selected>{{helperTrans('ingaz.choose')}}</option>
                    @foreach(($contracts ?? []) as $contract)
                    <option value="{{$contract->id}}"> {{$contract->desc }} </option>
                    @endforeach
                </select>
            </div>

            <div class="col-4">
                <label for="TextInput" class="form-label">{{helperTrans("ingaz.shelter-places")}}</label>
                <select name="shelter_id" class="form-select form-control select2" data-validation="required">
                    <option disabled selected>{{helperTrans('ingaz.choose')}}</option>
                    @foreach(($shelters ?? []) as $shelter)
                    <option value="{{$shelter->id}}"> {{$shelter->title }} </option>
                    @endforeach
                </select>
            </div>

            <div class="col-4">
                <label for="TextInput" class="form-label">مصدر الاستلام</label>
                <select name="receipt_source" class="form-select form-control select2" data-validation="required">
                    @forelse (__('shelter_worker_statuses.received_by') ?? [] as $k => $name)
                    <option value="{{$k}}" @if (@$data->receipt_source == $k) selected @endif>
                        {{ $name }}
                    </option>
                    @empty
                    @endforelse
                </select>
            </div>

            <div class="col-4 mt-4">
                <label for="TextInput" class="form-label">{{helperTrans("ingaz.maid date back")}}</label>
                <input type="date" name="maid_date_back" class="form-control" data-validation="required">
            </div>

            <div class="col-4 mt-4">
                <label for="TextInput" class="form-label">{{helperTrans("ingaz.salary_final_status")}}</label>
                <select name="salary_final_status" class="form-select form-control select2" data-validation="required">
                    <option disabled selected>{{helperTrans('ingaz.choose')}}</option>
                    <option value="received">تم استلام الراتب</option>
                    <option value="not_received">لم يتم استلام الراتب</option>
                </select>
            </div>

            <div class="col-4 mt-4">
                <label for="TextInput" class="form-label">{{helperTrans("ingaz.duration_from_enter")}}</label>
                <input type="text" name="duration_from_enter" class="form-control" readonly>
            </div>

            <div class="col-12 mt-4">
                <label for="TextInput" class="form-label">{{helperTrans("ingaz.back_reasons")}}</label>
                <textarea name="back_reasons" rows="4" class="form-control" data-validation="required"></textarea>
            </div>

        </div>

        <div class="col-12 modal-footer">
            <button class="btn btn-primary" type="submit">{{__('buttons.save')}}</button>
        </div>
    </form>

@else
    @include('components.no_permissions')
@endif

@endsection
@section('js')
<script>
    $('.select2').select2({
            placeholder: 'Search for an order...',
            allowClear: true
        });
</script>

<script>
    var indexRoute = "{{ route('ShelterContract.index') }}";
</script>

<script>
    // Calculate duration from enter automatically
    $(document).ready(function() {
        $('select[name="contract_id"]').on('change', function() {
            let contractId = $(this).val();
            if (contractId) {
                $.ajax({
                    url: "{{ route('get.contract.details') }}",
                    type: 'GET',
                    data: { contract_id: contractId },
                    success: function(response) {
                        console.log('Contract Details Response:', response);

                        // Get the arrival date from the response
                        let arrivalDate = response.arrival_date;
                        console.log('Arrival Date from Response:', arrivalDate);

                        if (arrivalDate) {
                            // Parse the date string to a Date object
                            let arrivalDateObj = new Date(arrivalDate);
                            let today = new Date();

                            console.log('Parsed Arrival Date:', arrivalDateObj);
                            console.log('Today:', today);

                            // Calculate the difference in days
                            let diffTime = Math.abs(today - arrivalDateObj);
                            let diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                            console.log('Difference in days:', diffDays);

                            // Format the duration text
                            let durationText = diffDays + ' يوم';
                            console.log('Duration Text:', durationText);

                            // Update the input field
                            $('input[name="duration_from_enter"]').val(durationText);
                        } else {
                            console.log('No valid arrival date found in response');
                            console.log('Debug Info:', response.debug_info);
                            $('input[name="duration_from_enter"]').val('');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching contract details:', error);
                        console.log('Response:', xhr.responseText);
                        $('input[name="duration_from_enter"]').val('');
                    }
                });
            } else {
                $('input[name="duration_from_enter"]').val('');
            }
        });
    });
</script>

<script>
    $(document).on('submit', '.contractForm', function (e) {
            e.preventDefault();
            let formData = new FormData(this);
            let url = $(this).attr('action');
            let submitButton = $(e.originalEvent.submitter);
            let buttonText = submitButton.text();
            let modalResult = $("#createModal");
            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                beforeSend: function () {
                    $('#loading-wrapper').fadeIn();
                    submitButton.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ' + buttonText)
                },
                complete: function () {
                    $('#loading-wrapper').fadeOut();
                },
                success: function (result) {
                    console.log(200)
                    window.location.href = indexRoute;
                    submitButton.html(buttonText)
                    if (modalResult.is(":visible")) {
                        modalResult.modal('toggle');
                    }
                    console.log(modalResult.is(':visible'));
                },
                error: function (errorObj, errorText, errorThrown) {
                    console.log(500)
                    console.log(errorObj)
                    handleErrorResponce(errorObj);
                }
            });
        });
</script>


@endsection
