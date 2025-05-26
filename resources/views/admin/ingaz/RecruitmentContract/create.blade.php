@extends('admin.layout.indexs.index')
@section('page-title')
{{helperTrans("ingaz.add Recruitment contract")}}
@endsection

@section('style')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
@endsection

@section('content')

@if (@AdminGuardHasPerm(\App\PermKeys::REC_CONTRACT_CONTROL))

    <form id="maidForm" class="row g-3 contractForm" method="post" action="{{route("RecruitmentContract.store")}}">
        @csrf

        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">معلومات العميل</h4>
                </div>
                <div class="card-body">
                    <div id="order_selection" class="col-12 mb-4">
                        <label class="form-label">{{helperTrans("ingaz.select_order")}}</label>
                        <select name="order_id" id="order_id" class="form-select form-control select2" data-validation="required">
                            <option disabled selected>{{helperTrans('ingaz.choose')}}</option>
                            @foreach($orders as $order)
                                <option value="{{$order->id}}"
                                    data-client-name="{{$order->user->name}}"
                                    data-client-phone="{{$order->user->phone}}"
                                    data-maid-name="{{$order->maid->name}}"
                                    data-maid-passport="{{$order->maid->passport_num}}"
                                    data-client-nationality="{{$order->user->nationality}}"
                                    data-maid-id="{{$order->maid->id}}">
                                    {{$order->user->name}} - {{$order->user->phone}} - {{$order->maid->name}} ({{$order->maid->passport_num}})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 mb-4">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="client_type" id="ex_client" value="ex_client" checked>
                            <label class="form-check-label" for="ex_client">{{helperTrans("ingaz.ex_client")}}</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="client_type" id="new_client" value="new_client">
                            <label class="form-check-label" for="new_client">{{helperTrans("ingaz.new_client")}}</label>
                        </div>
                    </div>

                    @include('admin.ingaz.contract.client-section')
                </div>
            </div>
        </div>

        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">معلومات الخادمة</h4>
                </div>
                <div class="card-body">
                    @include('admin.ingaz.contract.maid-section')
                </div>
            </div>
        </div>

        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h4 class="mb-0">معلومات العقد</h4>
                </div>
                <div class="card-body">
                    @include('admin.ingaz.contract.contract-section')
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-body text-center">
                    <button class="btn btn-primary btn-lg px-5" type="submit">
                        <i class="fa-solid fa-floppy-disk me-2"></i>
                        {{__('buttons.save')}}
                    </button>
                </div>
            </div>
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
    $(document).ready(function() {
        $('.select2').select2({
            placeholder: 'Search for an order...',
            allowClear: true
        });

        $('#client_type').change(function () {
            if ($(this).val() === 'new_client') {
                $('#new-client-section').show();
                $('#order-selection').hide();
                $('#maid-selection').show();
                $('#maid_id').prop('required', true);
            } else {
                $('#new-client-section').hide();
                $('#order-selection').show();
                $('#maid-selection').hide();
                $('#maid_id').prop('required', false);
            }
        });

        $('#maid_id').change(function () {
            var maidId = $(this).val();
            if (maidId) {
                $.ajax({
                    url: '/admin/maids/' + maidId + '/details',
                    type: 'GET',
                    success: function (data) {
                        $('#maid_passport').val(data.passport_num);
                        $('#maid_religion').val(data.religion.title);
                        $('#maid_job').val(data.job_type);
                        $('#maid_nationality').val(data.nationality.title);
                        $('#maid_salary').val(data.salary);
                        $('#experience_years').val(data.experience_years);
                        $('#age').val(data.age);
                    }
                });
            }
        });

        // Handle order selection
        $('#order_id').on('change', function() {
            var selectedOption = $(this).find('option:selected');
            var clientName = selectedOption.data('client-name');
            var clientPhone = selectedOption.data('client-phone');
            var maidName = selectedOption.data('maid-name');
            var maidPassport = selectedOption.data('maid-passport');
            var clientNationality = selectedOption.data('client-nationality');
            var maidId = selectedOption.data('maid-id');

            // Fill in the form fields
            $('#client_name').val(clientName);
            $('#client_phone').val(clientPhone);
            $('#maid_name').val(maidName);
            $('#maid_passport').val(maidPassport);
            if (clientNationality) {
                $('#client_nationality_id').val(clientNationality).trigger('change');
            }
            if (maidId) {
                $('#maid_id').val(maidId).trigger('change');
            }
        });

        // Form submission handling
        $('#maidForm').on('submit', function(e) {
            e.preventDefault();

            // Reset previous error messages
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').remove();

            var isValid = true;
            var firstInvalidField = null;
            var clientType = $('input[name="client_type"]:checked').val();

            // Check required fields based on client type
            if (clientType === 'ex_client') {
                if (!$('#order_id').val()) {
                    isValid = false;
                    $('#order_id').addClass('is-invalid');
                    $('#order_id').after('<div class="invalid-feedback">يرجى اختيار الطلب</div>');
                    firstInvalidField = $('#order_id');
                }
            } else {
                // Check new client required fields
                if (!$('#client_name').val()) {
                    isValid = false;
                    $('#client_name').addClass('is-invalid');
                    $('#client_name').after('<div class="invalid-feedback">يرجى ادخال اسم العميل</div>');
                    firstInvalidField = $('#client_name');
                }
                if (!$('#client_phone').val()) {
                    isValid = false;
                    $('#client_phone').addClass('is-invalid');
                    $('#client_phone').after('<div class="invalid-feedback">يرجى ادخال رقم هاتف العميل</div>');
                    if (!firstInvalidField) firstInvalidField = $('#client_phone');
                }
                if (!$('#client_nationality_id').val()) {
                    isValid = false;
                    $('#client_nationality_id').addClass('is-invalid');
                    $('#client_nationality_id').after('<div class="invalid-feedback">يرجى اختيار جنسية العميل</div>');
                    if (!firstInvalidField) firstInvalidField = $('#client_nationality_id');
                }
            }

            // Check other required fields
            $('[data-validation="required"]').each(function() {
                if (!$(this).val()) {
                    isValid = false;
                    $(this).addClass('is-invalid');

                    // Add error message
                    var fieldName = $(this).attr('name');
                    var errorMessage = '';

                    switch(fieldName) {
                        case 'contract_date':
                            errorMessage = 'يرجى ادخال تاريخ العقد';
                            break;
                        case 'msaned_number':
                            errorMessage = 'يرجى ادخال رقم مساند';
                            break;
                        case 'visa_number':
                            errorMessage = 'يرجى ادخال رقم التأشيرة';
                            break;
                        case 'contract_duration':
                            errorMessage = 'يرجى ادخال مدة العقد';
                            break;
                        default:
                            errorMessage = 'هذا الحقل مطلوب';
                    }

                    $(this).after('<div class="invalid-feedback">' + errorMessage + '</div>');

                    if (!firstInvalidField) {
                        firstInvalidField = $(this);
                    }
                }
            });

            if (!isValid) {
                // Scroll to first invalid field
                if (firstInvalidField) {
                    $('html, body').animate({
                        scrollTop: firstInvalidField.offset().top - 100
                    }, 500);
                }
                return false;
            }

            // If all validations pass, submit the form via AJAX
            let formData = new FormData(this);
            let url = $(this).attr('action');
            let submitButton = $(e.originalEvent.submitter);
            let buttonText = submitButton.text();

            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                beforeSend: function () {
                    $('#loading-wrapper').fadeIn();
                    submitButton.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ' + buttonText);
                },
                complete: function () {
                    $('#loading-wrapper').fadeOut();
                    submitButton.html(buttonText);
                },
                success: function (response) {
                    if (response.code === 200) {
                        // Show success message
                        Swal.fire({
                            title: 'نجاح',
                            text: response.data.message,
                            icon: 'success',
                            confirmButtonText: 'حسناً'
                        }).then((result) => {
                            // Redirect to index page
                            window.location.href = indexRoute;
                        });
                    }
                },
                error: function (errorObj) {
                    handleErrorResponce(errorObj);
                }
            });
        });
    });
</script>

@include('admin.ingaz.contract.js')
@endsection
