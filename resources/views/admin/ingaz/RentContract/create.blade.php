@extends('admin.layout.indexs.index')
@section('page-title')
    {{helperTrans("ingaz.add Rent contract")}}
@endsection
@section('content')
@if (@AdminGuardHasPerm(\App\PermKeys::RENT_CONTRACT_CONTROL))

    <form class="row g-3 contractForm" method="post" action="{{route("RentContract.store")}}">
        @csrf
        @include('admin.ingaz.RentContract.client-section')
        @include('admin.ingaz.RentContract.maid-section')

        <div id="step-3" class="step d-none">
            <div class="col-12">
                <label for="TextInput" class="form-label">{{helperTrans("ingaz.Previous sponsor")}}</label>
                <select name="previous_sponsorship" id="previous_sponsorship" class="form-select form-control select2">
                    <option disabled selected>{{helperTrans('ingaz.choose')}}</option>
                    @foreach($offices as $office)
                        <option value="{{$office->id}}"> {{$office->title }} </option>
                    @endforeach
                </select>
            </div>
        </div>

        @include('admin.ingaz.RentContract.contract-section')


        <div class="col-12 modal-footer">
            <button id="prevBtn" type="button" class="btn btn-default d-none"
                    style="margin-left: 3px">{{__('buttons.prev')}}</button>
            <button id="nextBtn" type="button" class="btn btn-primary"
                    style="margin-left: 3px">{{__('buttons.next')}}</button>
            <button id="submitBtnn" class="btn btn-primary d-none"
                    type="submit">{{__('buttons.save')}}</button>
        </div>
    </form>

@else
    @include('components.no_permissions')
@endif

@endsection
@section('js')
    <script>
        var indexRoute = "{{ route('RentContract.index') }}";
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
    @include('admin.ingaz.RentContract.js')

@endsection
