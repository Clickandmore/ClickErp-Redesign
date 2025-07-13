@extends('admin.layout.indexs.index')
@section('page-title')
{{helperTrans("ingaz.add Recruitment contract")}}
@endsection

@section('style')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
@endsection

@section('content')

@if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_CREATE))

    <form id="maidForm" class="row g-3 contractForm" method="post" action="{{route("RecruitmentContract.store")}}">
        @csrf
        <input type="hidden" name="client_type" value="ex_client" id="client_type">
        <input type="hidden" name="maid_id" id="maid_id">

        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">معلومات العميل</h4>
                </div>
                <div class="card-body">
                    {{-- <div class="col-12 mb-4">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="client_type" id="ex_client" value="ex_client" checked>
                            <label class="form-check-label" for="ex_client">{{helperTrans("ingaz.ex_client")}}</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="client_type" id="new_client" value="new_client">
                            <label class="form-check-label" for="new_client">{{helperTrans("ingaz.new_client")}}</label>
                        </div>
                    </div> --}}

                    <div id="order-selection" class="col-12 mb-4">
                        <label class="form-label">{{helperTrans("ingaz.select_order")}}</label>
                        <select name="order_id" id="order_id" class="form-select form-control select2" data-validation="required">
                            <option disabled selected>{{helperTrans('ingaz.choose')}}</option>
                            @foreach($orders as $order)
                                <option value="{{$order->id}}"
                                    data-client-name="{{$order->user->name ?? ''}}"
                                    data-client-phone="{{$order->user->phone ?? ''}}"
                                    data-maid-name="{{$order->maid->name ?? ''}}"
                                    data-maid-passport="{{$order->maid->passport_num ?? ''}}"
                                    data-client-nationality="{{$order->user->nationality_id ?? ''}}"
                                    data-maid-id="{{$order->maid->id ?? ''}}"
                                    data-maid-nationality="{{$order->maid->nationality_id ?? ''}}"
                                    data-maid-religion="{{$order->maid->religion_id ?? ''}}"
                                    data-maid-job="{{$order->maid->job_types && $order->maid->job_types->first() && $order->maid->job_types->first()->translations && $order->maid->job_types->first()->translations->first() ? $order->maid->job_types->first()->translations->first()->title : ''}}"
                                    data-maid-salary="{{$order->maid->salary ?? ''}}"
                                    data-maid-experience="{{$order->maid->experience_years ?? ''}}"
                                    data-maid-age="{{$order->maid->age ?? ''}}"
                                    @if(isset($orderId) && $orderId == $order->id) selected @endif>
                                    {{$order->user->name ?? ''}} - {{$order->user->phone ?? ''}} - {{$order->maid->name ?? ''}} ({{$order->maid->passport_num ?? ''}}) - {{$order->jobType->title ?? ''}}
                                </option>
                            @endforeach
                        </select>
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
    var selectedOrder = @json($selectedOrder);
    var hasOrderId = {{ isset($orderId) ? 'true' : 'false' }};
</script>

<script>
    $(document).ready(function() {
        // Hide both client type options if we have an orderId
        if (hasOrderId) {
            $('#new_client').parent().hide();
            $('#ex_client').parent().hide();
            $('#client_type').val('ex_client'); // Set client type to ex_client when orderId is present

            // Set maid_id from selected order
            if (selectedOrder && selectedOrder.maid) {
                $('#maid_id').val(selectedOrder.maid.id);
            }

            // Lock all maid data fields and make them read-only
            $('#maid_name, #maid_passport, #maid_religion, #maid_job, #maid_nationality, #maid_salary, #experience_years, #age').prop('disabled', true).css('background-color', '#e9ecef');

            // Prevent any changes to maid data
            $('#maid_name, #maid_passport, #maid_religion, #maid_job, #maid_nationality, #maid_salary, #experience_years, #age').on('change keyup', function(e) {
                e.preventDefault();
                return false;
            });
        }

        // Initialize Select2
        $('.select2').select2({
            placeholder: 'Search for an order...',
            allowClear: true
        });

        // If we have a selected order, populate the form fields
        if (selectedOrder) {
            console.log('Selected order data:', selectedOrder);

            // Set the order in the dropdown
            $('#order_id').val(selectedOrder.id).trigger('change');

            // Fill in client data
            if (selectedOrder.user) {
                $('#client_name').val(selectedOrder.user.name);
                $('#client_phone').val(selectedOrder.user.phone);
                if (selectedOrder.user.nationality_id) {
                    $('#client_nationality_id').val(selectedOrder.user.nationality_id).trigger('change');
                }
            }

            // Set default contract duration to 24 months
            $('#contract_duration').val('2');

            // Fill in maid data
            if (selectedOrder.maid) {
                // Set maid name directly from the order data
                $('#maid_name').val(selectedOrder.maid.name);
                $('#maid_passport').val(selectedOrder.maid.passport_num);

                // Fetch maid details to get proper names/values
                if (selectedOrder.maid.id) {
                    $.ajax({
                        url: '/admin/maids/' + selectedOrder.maid.id + '/details',
                        type: 'GET',
                        success: function(data) {
                            console.log('Maid details response:', data);

                            // Set religion
                            if (data.religion_title) {
                                $('#maid_religion').val(data.religion_title);
                            }

                            // Handle job type - use the actual job type from maid data
                            if (data.job_types && data.job_types.length > 0) {
                                $('#maid_job').val(data.job_types[0].title);
                            }

                            // Handle nationality
                            if (data.nationality && data.nationality.title) {
                                $('#maid_nationality').val(data.nationality.title);
                            }

                            // Set other fields
                            $('#maid_salary').val(data.salary || '');
                            $('#experience_years').val(data.experience_years || '');
                            $('#age').val(data.age || '');

                            // Force update of Select2 if it exists
                            if ($.fn.select2) {
                                $('#maid_nationality').select2('destroy').select2();
                                $('#maid_religion').select2('destroy').select2();
                                $('#maid_job').select2('destroy').select2();
                            }
                        },
                        error: function(error) {
                            console.error('Error fetching maid details:', error);
                        }
                    });
                }
            }

            // Set client type to existing client
            $('#ex_client').prop('checked', true).trigger('change');
        }

        $('#client_type').change(function () {
            if ($(this).val() === 'new_client') {
                $('#new-client-section').show();
                $('#order-selection').hide();
                $('#maid-selection').show();
                $('#maid_id').prop('required', true);
                $('#order_id').prop('required', false);
                $('#order_id').val('').trigger('change');
            } else {
                $('#new-client-section').hide();
                $('#order-selection').show();
                $('#maid-selection').hide();
                $('#maid_id').prop('required', false);
                $('#order_id').prop('required', true);
            }
        });

        $('#maid_id').change(function () {
            var maidId = $(this).val();
            if (maidId) {
                var selectedOption = $(this).find('option:selected');
                console.log('Selected maid data:', {
                    name: selectedOption.data('name'),
                    passport: selectedOption.data('passport'),
                    religion: selectedOption.data('religion'),
                    job: selectedOption.data('job'),
                    nationality: selectedOption.data('nationality'),
                    salary: selectedOption.data('salary'),
                    experience: selectedOption.data('experience'),
                    age: selectedOption.data('age')
                });

                // Enable fields temporarily
                $('#maid_name, #maid_passport, #maid_religion, #maid_job, #maid_nationality, #maid_salary, #experience_years, #age').prop('disabled', false);

                // Set basic information from data attributes
                $('#maid_name').val(selectedOption.data('name'));
                $('#maid_passport').val(selectedOption.data('passport'));
                $('#maid_religion').val(selectedOption.data('religion'));
                $('#maid_job').val(selectedOption.data('job'));
                $('#maid_nationality').val(selectedOption.data('nationality'));
                $('#maid_salary').val(selectedOption.data('salary'));
                $('#experience_years').val(selectedOption.data('experience'));
                $('#age').val(selectedOption.data('age'));

                // Force update of Select2 if it exists
                if ($.fn.select2) {
                    $('#maid_nationality').select2('destroy').select2();
                    $('#maid_religion').select2('destroy').select2();
                    $('#maid_job').select2('destroy').select2();
                }

                // Disable fields again after setting values
                setTimeout(function() {
                    $('#maid_name, #maid_passport, #maid_religion, #maid_job, #maid_nationality, #maid_salary, #experience_years, #age').prop('disabled', true);
                }, 100);
            }
        });

        // Handle order selection change
        $('#order_id').on('change', function() {
            var selectedOption = $(this).find('option:selected');
            var maidId = selectedOption.data('maid-id');

            // Set client type to ex_client when an order is selected
            $('#client_type').val('ex_client');

            // Set maid_id from selected order
            $('#maid_id').val(maidId);

            // Clear all fields first
            $('#client_name, #client_phone, #client_nationality_id, #maid_name, #maid_passport, #maid_religion, #maid_job, #maid_nationality, #maid_salary, #experience_years, #age').val('');

            if (maidId) {
                // Fill in the client form fields
                $('#client_name').val(selectedOption.data('client-name'));
                $('#client_phone').val(selectedOption.data('client-phone'));
                if (selectedOption.data('client-nationality')) {
                    $('#client_nationality_id').val(selectedOption.data('client-nationality')).trigger('change');
                }

                // Fill in the maid form fields
                $('#maid_name').val(selectedOption.data('maid-name'));
                $('#maid_passport').val(selectedOption.data('maid-passport'));

                // Fetch maid details to get proper names/values
                $.ajax({
                    url: '/admin/maids/' + maidId + '/details',
                    type: 'GET',
                    success: function(data) {
                        console.log('Maid details response:', data);

                        // Enable fields temporarily
                        $('#maid_religion, #maid_job, #maid_nationality, #maid_salary, #experience_years, #age').prop('disabled', false);

                        // Set religion
                        console.log('Setting religion:', data.religion_title);
                        $('#maid_religion').val(data.religion_title || '');

                        // Handle job type
                        var jobTypeTitle = '';
                        if (data.job_types && data.job_types.length > 0) {
                            jobTypeTitle = data.job_types[0].title || '';
                            console.log('Setting job type:', jobTypeTitle);
                        }
                        $('#maid_job').val(jobTypeTitle);

                        // Handle nationality
                        var nationalityTitle = '';
                        if (data.nationality) {
                            nationalityTitle = data.nationality.title || '';
                            console.log('Setting nationality:', nationalityTitle);
                        }
                        $('#maid_nationality').val(nationalityTitle);

                        // Set other fields
                        console.log('Setting salary:', data.salary);
                        $('#maid_salary').val(data.salary || '');

                        console.log('Setting experience years:', data.experience_years);
                        $('#experience_years').val(data.experience_years || '');

                        console.log('Setting age:', data.age);
                        $('#age').val(data.age || '');

                        // Force update of Select2 if it exists
                        if ($.fn.select2) {
                            $('#maid_nationality').select2('destroy').select2();
                            $('#maid_religion').select2('destroy').select2();
                            $('#maid_job').select2('destroy').select2();
                        }

                        // Disable fields again after setting values
                        setTimeout(function() {
                            $('#maid_religion, #maid_job, #maid_nationality, #maid_salary, #experience_years, #age').prop('disabled', true);
                        }, 100);
                    },
                    error: function(error) {
                        console.error('Error fetching maid details:', error);
                    }
                });
            }
        });

        // // Initialize Select2 for maid_id with custom template
        // if ($.fn.select2) {
        //     $('#maid_id').select2({
        //         placeholder: 'اختر العاملة',
        //         allowClear: true,
        //         templateResult: formatMaidOption,
        //         templateSelection: formatMaidOption
        //     });
        // }

        function formatMaidOption(maid) {
            if (!maid.id) return maid.text;
            var $maid = $(maid.element);
            var $container = $(
                '<div class="select2-result-maid">' +
                    '<div class="select2-result-maid__name">' + $maid.data('name') + '</div>' +
                    '<div class="select2-result-maid__details">' +
                        'رقم الجواز: ' + $maid.data('passport') + ' | ' +
                        'الجنسية: ' + $maid.data('nationality') +
                    '</div>' +
                '</div>'
            );
            return $container;
        }

        // Form submission handling
        $('#maidForm').on('submit', function(e) {
            e.preventDefault();

            // Clear previous validation errors
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').remove();

            let isValid = true;
            let firstInvalidField = null;

            // Validate required fields
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
                        case 'order_id':
                            errorMessage = 'يرجى اختيار الطلب';
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

            // Additional validation for maid_id
            if (!$('#maid_id').val()) {
                isValid = false;
                Swal.fire({
                    icon: 'error',
                    title: 'خطأ',
                    text: 'يرجى اختيار العاملة',
                    confirmButtonText: 'حسناً'
                });
                return false;
            }

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

            // Disable submit button and show loading state
            submitButton.prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-2"></i>جاري الحفظ...');

            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    console.log('Server response:', response);
                    if (response.success) {
                        // Show success message
                        Swal.fire({
                            icon: 'success',
                            title: 'تم بنجاح',
                            text: response.message || 'تم إنشاء العقد بنجاح',
                            confirmButtonText: 'حسناً'
                        }).then((result) => {
                            window.location.href = indexRoute;
                        });
                    } else {
                        // Show error message
                        Swal.fire({
                            icon: 'error',
                            title: 'خطأ',
                            text: response.message || 'حدث خطأ أثناء إنشاء العقد',
                            confirmButtonText: 'حسناً'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', {
                        status: status,
                        error: error,
                        response: xhr.responseText
                    });

                    let errorMessage = 'حدث خطأ أثناء إنشاء العقد';
                    if (xhr.responseJSON) {
                        if (xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        } else if (xhr.responseJSON.errors) {
                            // Handle validation errors
                            const errors = xhr.responseJSON.errors;
                            errorMessage = Object.values(errors).flat().join('\n');
                        }
                    }

                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ',
                        text: errorMessage,
                        confirmButtonText: 'حسناً'
                    });
                },
                complete: function() {
                    // Re-enable submit button
                    submitButton.prop('disabled', false).html('<i class="fa-solid fa-floppy-disk me-2"></i>حفظ');
                }
            });
        });
    });
</script>

@include('admin.ingaz.contract.js')
@endsection
