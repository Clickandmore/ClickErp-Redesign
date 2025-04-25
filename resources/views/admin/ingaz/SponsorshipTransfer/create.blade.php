@extends('admin.layout.indexs.index')
@section('page-title')
{{helperTrans("ingaz.SponsorshipTransfer")}}
@endsection
@section('content')
@if (@AdminGuardHasPerm(\App\PermKeys::SPONSOR_TRANSFER_CONTROL))

    <form class="row g-3 contractForm" method="post" action="{{route("SponsorshipTransfer.store")}}">
        @csrf
        <div class="row p-3">

            <div class="col-12">
                <label for="TextInput" class="form-label">{{helperTrans("ingaz.contracts")}}</label>
                <select name="contract_id" id="contract_id" class="form-select form-control select2"
                    data-validation="required">
                    <option disabled selected>{{helperTrans('ingaz.choose')}}</option>
                    @foreach($contracts as $contract)
                    <option value="{{$contract->id}}"> {{$contract->desc }} </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div id="new-client-section" class="row">
            <div class="col-6">
                <label for="client_name" class="form-label">{{helperTrans("ingaz.client_name")}}</label>
                <input type="text" id="client_name" disabled name="client_name" class="form-control"
                    data-validation="required">
            </div>

            <div class="col-6">
                <label for="client_phone" class="form-label">{{helperTrans("ingaz.client_phone")}}</label>
                <input type="tel" id="client_phone" disabled name="client_phone" class="form-control"
                    data-validation="required">
            </div>

            {{-- <div class="col-6">--}}
                {{-- <label for="client_nationality_id" --}} {{--
                    class="form-label">{{helperTrans("ingaz.nationality_id")}}</label>--}}
                {{-- <select id="client_nationality_id" name="client_nationality_id" --}} {{--
                    class="form-select form-control select2">--}}
                    {{-- <option disabled selected>{{helperTrans('ingaz.choose')}}</option>--}}
                    {{-- @foreach($countries as $country)--}}
                    {{-- <option value="{{$country->id}}">{{$country->title}}</option>--}}
                    {{-- @endforeach--}}
                    {{-- </select>--}}
                {{-- </div>--}}

            <div class="col-6">
                <label for="client_email" class="form-label">{{helperTrans("ingaz.email")}}</label>
                <input type="email" id="client_email" disabled name="client_email" class="form-control">
            </div>

            <div class="col-6">
                <label for="client_national_num" class="form-label">{{helperTrans("ingaz.national_num")}}</label>
                <input type="text" id="client_national_num" disabled name="client_national_num" class="form-control">
            </div>

            <div class="col-6">
                <label for="client_birthdate" class="form-label">{{helperTrans("ingaz.birthdate")}}</label>
                <input type="date" id="client_birthdate" disabled name="client_birthdate" class="form-control">
            </div>

            <div class="col-6">
                <label for="phone2" class="form-label">{{helperTrans("ingaz.phone2")}}</label>
                <input type="tel" id="phone2" name="phone2" disabled class="form-control">
            </div>

            <div class="col-6">
                <label for="address" class="form-label">{{helperTrans("ingaz.address")}}</label>
                <input type="text" id="address" name="address" disabled class="form-control">
            </div>

            <div class="col-6">
                <label for="city" class="form-label">{{helperTrans("ingaz.city of receive")}}</label>
                <input type="text" id="city" name="city" disabled class="form-control">
            </div>

            <div class="col-6">
                <label for="beds_num" class="form-label">{{helperTrans("ingaz.beds number")}}</label>
                <input type="text" id="beds_num" name="beds_num" disabled class="form-control">
            </div>

            <div class="col-6">
                <label for="family_num" class="form-label">{{helperTrans("ingaz.family_number")}}</label>
                <input type="text" id="family_num" name="family_num" disabled class="form-control">
            </div>

            <div class="col-6">
                <label for="children" class="form-label">{{helperTrans("ingaz.children")}}</label>
                <input type="number" id="children" max="50" disabled name="children" class="form-control">
            </div>

            <div class="col-6">
                <label for="job" class="form-label">{{helperTrans("ingaz.job")}}</label>
                <input type="text" id="job" name="job" disabled class="form-control">
            </div>
        </div>


        <div class="col-12">
            <label for="client_nationality_id" class="form-label">{{helperTrans("ingaz.new client")}}</label>
            <select id="client_nationality_id" name="new_client_id" class="form-select form-control select2">
                <option disabled selected>{{helperTrans('ingaz.choose')}}</option>
                @foreach($clients as $user)
                <option value="{{$user->id}}">{{$user->name}}</option>
                @endforeach
            </select>
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
    var indexRoute = "{{ route('RecruitmentContract.index') }}";
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

<script>
    $('#contract_id').change(function () {
            var contractId = $(this).val();
            $.ajax({
                url: '{{route('showUserFromContract')}}',
                method: 'GET',
                data: {contractId: contractId},
                beforeSend: function () {
                    $('#loading-wrapper').fadeIn();
                },
                complete: function () {
                    $('#loading-wrapper').fadeOut();
                },
                success: function (response) {
                    showUserData(response.data.data.user)
                },
                error: function (xhr, status, error) {
                    console.error(xhr.responseText);
                }
            });
        });

        function showUserData(response) {
            const userData = response;
            //
            $('#client_name').val(userData.name);
            $('#client_phone').val(userData.phone);
            $('#client_nationality_id').val(userData.nationality_id);
            $('#client_email').val(userData.email);
            $('#client_national_num').val(userData.national_num);
            $('#client_birthdate').val(userData.birthdate);
            $('#phone2').val(userData.phone2);
            $('#address').val(userData.address);
            $('#city').val(userData.city);
            $('#beds_num').val(userData.rooms_num);
            $('#family_num').val(userData.family_num);
            $('#children').val(userData.children);
            $('#job').val(userData.job);
        }

</script>


@endsection
