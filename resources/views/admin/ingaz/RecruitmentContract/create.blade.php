@extends('admin.layout.indexs.index')
@section('page-title')
{{helperTrans("ingaz.add Recruitment contract")}}
@endsection
@section('content')

@if (@AdminGuardHasPerm(\App\PermKeys::REC_CONTRACT_CONTROL))

    <form id="maidForm" class="row g-3 contractForm" method="post" action="{{route("RecruitmentContract.store")}}">
        @csrf
        @include('admin.ingaz.contract.client-section')
        @include('admin.ingaz.contract.maid-section')


        <div id="step-3" class="step d-none">
            <div id="contract-section" class="row">

                <div class="col-6">
                    <label class="form-label">{{helperTrans("ingaz.contract date")}}</label>
                    <input type="date" id="contract_date" name="contract_date" class="form-control"
                        data-validation="required">
                </div>

                <div class="col-6">
                    <label class="form-label">{{helperTrans("ingaz.msaned number")}}</label>
                    <input type="text" id="msaned_number" name="msaned_number" class="form-control"
                        data-validation="required">
                </div>

                {{-- <div class="col-6">
                    <label class="form-label">{{helperTrans("ingaz.tafwed number")}}</label>
                    <input type="text" id="tafwed_number" name="tafwed_number" class="form-control"
                        data-validation="required">
                </div> --}}

                <div class="col-6">
                    <label class="form-label">{{helperTrans("ingaz.visa number")}}</label>
                    <input type="text" id="visa_number" name="visa_number" class="form-control" data-validation="required">
                </div>
                {{-- <div class="col-6">
                    <label class="form-label">{{helperTrans("ingaz.visa hijry date")}}</label>
                    <input type="date" id="visa_milad_date" name="visa_milad_date" class="form-control"
                        data-validation="required">
                </div>
                <div class="col-6">
                    <label class="form-label">{{helperTrans("ingaz.visa milad date")}}</label>
                    <input type="date" id="visa_hijry_date" name="visa_hijry_date" class="form-control"
                        data-validation="required">
                </div> --}}


            </div>
        </div>


        <div class="col-12 modal-footer">
            <button id="prevBtn" type="button" class="btn btn-default d-none"
                style="margin-left: 3px">{{__('buttons.prev')}}</button>
            <button id="nextBtn" type="button" class="btn btn-primary"
                style="margin-left: 3px">{{__('buttons.next')}}</button>
            <button id="submitBtnn" class="btn btn-primary d-none" type="submit">{{__('buttons.save')}}</button>
        </div>
    </form>

@else
    @include('components.no_permissions')
@endif

@endsection
@section('js')
<script>
    var indexRoute = "{{ route('RecruitmentContract.index') }}";
</script>

<script>

    $(document).ready(function () {

        const orderId = '{{ $orderId }}';
        if (orderId) {
            var url = '{{ route('orders.getOrderById', ['id' => ':id']) }}'.replace(':id', orderId);
            $.ajax({
                url,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (data) {
                    if (data.code == 200) {
                        setTimeout(function () {
                            $('select#order_id').html('<option value="' + data.data.id + '" selected>' + data.data.user.name + '</option>');
                            $('#order_id').val(orderId).trigger('change').trigger('select2:select');
                        }, 1000);
                    }
                },
                error: function (xhr, status, error) {
                    // Handle AJAX errors
                    alert('An error occurred while completing the order. Please try again.');
                    console.error(xhr.responseText); // Log the error for debugging
                }
            })
        }

        $('#client_nationality_id').change(function () {
            console.log($(this).val());
            let nationalityId = $(this).val();
            var url = "{{ route('getMaidByNationality', ['nationalityId' => ':nationalityId']) }}".replace(':nationalityId', nationalityId);
            $.ajax({
                url: url,
                type: 'GET',
                data: { nationalityId: $(this).val() },
                success: function (data) {
                    $('#maid_id').empty();
                    // loop to the object
                    $('#maid_id').append('<option selected disabled>{{helperTrans('ingaz.choose')}}</option>');
                    data.forEach(function (item) {
                        $('#maid_id').append('<option value="' + item.id + '">' + item.passport_num + '</option>');
                    });
                }
            });
        });
        // submit form
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
                    alert(errorObj.responseJSON.message);
                }
            });
        });
    });



</script>
@include('admin.ingaz.contract.js')
@endsection