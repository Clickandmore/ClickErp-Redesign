@extends('web.layout.app')


@push('css')
<link rel="stylesheet" href="{{ asset('web') }}/assets/css/bs-stepper.css">
@endpush


@section('content')

<main>
    <section class="page_header">
        <h1>{{ $type->title }}</h1>
        <h6><a href="{{ route('web.home') }}">{{ helperTrans('web.Home') }}</a> / {{ $type->title }}</h6>
    </section>


    @if ($type->id == 1)
    @include('web.ingaz.maids.sub.type_1_order')
    @else
    @include('web.ingaz.maids.sub.type_2_order')
    @endif
</main>


<!-- *** confirm model *** -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" data-bs-backdrop="static"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" id="closeMod" class="btn-close" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="confirm_model">
                    <img src="{{ asset('web') }}\assets/images/confirm.gif" alt="confirm" loop="false">
                    <p>{{ helperTrans('web.please_contact_with') }}<span id="customer_name"></span>{{
                        helperTrans('web.in_24_for_cv') }}
                    </p>
                    <a href="tel:" class="phone" id="customer_phone" target="_blank">
                        {{ helperTrans('web.contact_with_phone') }}
                        <i class="fa-light fa-phone-volume"></i>
                    </a>
                    <a href="https://wa.me/" class="whatsapp" target="_blank">
                        {{ helperTrans('web.contact_with_watts') }}
                        <i class="fa-brands fa-whatsapp"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@include('web.ingaz.maids.sub.pay_modal')

<!-- Bank Transfer Modal -->
@include('web.ingaz.orders.sub.bank_proof_uplaod_modal')

@include('web.ingaz.maids.sub.maid_modal_view')

@endsection

@push('js')
<script src="{{ asset('web') }}/assets/js/bs-stepper.js"></script>
<script>
    $('#closeMod').click(function() {
            window.location = '/';
        });


        $(document).ready(function() {
            $('.newPayFormClass').off('submit').on('submit', function(event) {
                event.preventDefault();
                var formData = $(this).serialize(); // Serialize the form data
                let submitButton = $(event.originalEvent.submitter);
                let buttonText = submitButton.text();
                submitButton.prop('disabled', true); // Disable the button

                var selectedAgentId = $('.agents input[type="radio"]:checked').val();
                // Update the hidden input value with the selected agent's ID
                $('input[name="details_list[admin_id]"]').val(selectedAgentId);


                $.ajax({
                    type: 'post',
                    url: '{{ route('web.orders.store') }}',
                    data: formData,
                    beforeSend: function () {
                        submitButton.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ' + buttonText);
                    },
                    complete: function () {
                        submitButton.html("{{ helperTrans('web.confirmation') }}");
                        submitButton.prop('disabled', false); // Re-enable the button
                    },
                    success: function (response) {
                        var data = response.data;

                        if (response.code == 410) {
                            errorToster('{{ __('auth.an error occurred') }}', response.message);
                            window.location = '/';
                            return;
                        }

                        if ($('input[name="payment_type"]:checked').val() === 'bank_transfer') {
                            // Set order ID in the bank transfer form
                            $('#order_id').val(data.order.id);
                            // Open the bank transfer modal
                            $('#bankTransferModal').modal('show');

                            // Handle submission of the bank transfer details form
                            $('#bankTransferSubmitBtn').off('click').on('click', function () {
                                var bankTransferFormData = new FormData($('#bankTransferForm')[0]);

                                // Submit bank transfer details first
                                $.ajax({
                                    type: 'post',
                                    url: '{{ route('web.bank_transfer.submit') }}', // Replace with your bank transfer submission route
                                    data: bankTransferFormData,
                                    contentType: false,
                                    processData: false,
                                    success: function (bankTransferResponse) {
                                        // Bank transfer details submitted successfully, redirect
                                        window.location = "{{ route('web.orders.show', ['order' => ':orderId']) }}"
                                            .replace(':orderId', data.order.id);
                                    },
                                    error: function (error) {
                                        console.error(error);
                                    }
                                });
                            });
                        } else {
                            // Redirect for non-bank transfer payments
                            window.location = "{{ route('web.orders.show', ['order' => ':orderId']) }}"
                                .replace(':orderId', data.order.id);
                        }
                    },
                    error: function (error) {
                        console.error(error);
                    }
                });
            });
        });

        var firstPage = null;
        var secondPage = null;
        var currentStep = 1;

        $(document).ready(function() {
            var stepper = new Stepper($('.bs-stepper')[0]);
            $('.btn-next').on('click', function() {
                if (stepper._currentIndex === 1) {
                    if (firstPage == null) {
                        infoToster('{{ helperTrans('auth.an error occurred') }}',
                            "{{ helperTrans('web.address_required') }}");
                        return true;
                    }
                } else if (stepper._currentIndex === 0) {
                    if (secondPage == null) {
                        infoToster('{{ helperTrans('auth.an error occurred') }}',
                            "{{ helperTrans('web.package_required') }}");

                        return true;
                    }
                }
                console.log(stepper._steps);
                stepper.next();
                currentStep++;
                if (stepper._currentIndex === 2 && currentStep == 3) {
                    infoToster('', "{{ helperTrans('web.terms_required') }}");
                }

            });
            $('.btn-prev').on('click', function() {
                currentStep--;
                stepper.previous();
            });


        });

        function render_data(type, id) {
            var elementId = type == 'package' ? 'package-' + id : 'address-' + id;
            var myElementHtml = document.getElementById('' + elementId).innerHTML;
            if (type == 'package') {
                secondPage = id;
                document.getElementById('ppackage-p').innerHTML = myElementHtml;
                document.getElementById('details_list_package_id').value = id;

            } else {
                firstPage = id;
                document.getElementById('llocation-l').innerHTML = myElementHtml;
                document.getElementById('details_list_address_id').value = id;
            }
        }

        // Get references to the checkbox and button
        var myCheckbox = document.getElementById('acceptCheckbox');
        var myButton = document.getElementById('myButtonsave');

        // Attach event listener to the checkbox
        myCheckbox.addEventListener('change', function() {
            // Enable or disable the button based on the checkbox state
            myButton.disabled = !this.checked;
        });
</script>
@endpush
