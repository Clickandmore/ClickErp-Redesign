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
                                    data-client-nationality="{{$order->user->nationality}}">
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
        // Initialize select2 with search functionality
        $('.select2').select2({
            placeholder: '{{helperTrans("ingaz.search_order")}}',
            allowClear: true,
            language: {
                noResults: function() {
                    return "{{helperTrans('ingaz.no_results')}}";
                }
            }
        });

        // Initialize nationality select2 specifically
        $('#client_nationality_id').select2({
            placeholder: '{{helperTrans("ingaz.choose_nationality")}}',
            allowClear: true,
            language: {
                noResults: function() {
                    return "{{helperTrans('ingaz.no_results')}}";
                }
            }
        });

        // Handle client type selection
        $('input[name="client_type"]').on('change', function() {
            if ($(this).val() === 'new_client') {
                $('#order_selection').hide();
                $('#order_id').prop('required', false);
                // Clear and enable nationality field for new client
                $('#client_nationality_id').val('').trigger('change');
                $('#client_nationality_id').prop('disabled', false);
            } else {
                $('#order_selection').show();
                $('#order_id').prop('required', true);
                // Disable nationality field for existing client
                $('#client_nationality_id').prop('disabled', true);
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

            // Fill in the form fields
            $('#client_name').val(clientName);
            $('#client_phone').val(clientPhone);
            $('#maid_name').val(maidName);
            $('#maid_passport').val(maidPassport);
            if (clientNationality) {
                $('#client_nationality_id').val(clientNationality).trigger('change');
            }
        });

        // Form validation before submission
        $('#maidForm').on('submit', function(e) {
            e.preventDefault();

            // Reset previous error messages
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').remove();

            var isValid = true;
            var firstInvalidField = null;

            // Check required fields
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
                        case 'client_nationality_id':
                            errorMessage = 'يرجى اختيار الجنسية';
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

            // If all validations pass, submit the form
            this.submit();
        });
    });
</script>

@include('admin.ingaz.contract.js')
@endsection
