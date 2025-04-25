<section class="exploreCVs">
    <div class="container">
        <div class="row">
            <div class="col-lg-6 col-12 mb-lg-0 mb-3">
                <div class="cv">

                    <div class="cv_details">

                        <div class="p-5 justify-content-center align-content-center">
                            <a href="#" class="maidViewData justify-content-center align-content-center"
                                data-id="{{ @$maid->id }}">
                                @if ($maid->image != null)
                                    <img src="{{ showFile($maid->image) }}" alt="cv1"
                                        style="border-radius: 50%; object-fit: cover; width: 130px; height: 130px;">
                                @else
                                    <img src="{{ $maid->cv_preview ? showFile($maid->cv_preview) : showFile($maid->cv_file) }}"
                                        alt="cv1"
                                        style="border-radius: 50%; object-fit: cover; width: 130px; height: 130px;">
                                @endif
                            </a>
                        </div>


                        <div class="info">
                            <ul>
                                <li>
                                    <span>{{ helperTrans('web.nationality') }}</span>
                                    <h3>{{ $maid->nationality->title }}</h3>
                                </li>
                                <li>
                                    <span>{{ helperTrans('web.salary') }}</span>
                                    <h3>{{ $maid->salary }} ر.س</h3>
                                </li>
                                <li>
                                    <span>{{ helperTrans('web.experience_years') }}</span>
                                    <h3>{{ $maid->experience_years }}</h3>
                                </li>
                                <li>
                                    <span>{{ helperTrans('web.passport_number') }}</span>
                                    <h3>{{ $maid->passport_num }}</h3>
                                </li>
                                <li>
                                    <span>{{ helperTrans('web.religion') }} </span>
                                    <h3>{{ $maid->religion->title }}</h3>
                                </li>
                                <li>
                                    <span>{{ helperTrans('web.recruitment_price') }}</span>
                                    <h3>{{ $maid->price }} ر.س</h3>
                                </li>
                                <li>
                                    <span>{{ helperTrans('web.occupation') }}</span>
                                    <h3>{{ $type->title }}</h3>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

            </div>
            <div class="col-lg-6 col-12">
                <form id="wizard-property-listing-form" class="TypeOneForm" onSubmit="return false">
                    @csrf
                    <input type="hidden" name="maid_id" value="{{ $maid->id }}">
                    <input type="hidden" name="payment_method" value="none">
                    <input type="hidden" name="job_type_id" value="{{ $type->id }}">
                    <input type="hidden" name="order_type" value="{{ $type->id }}">
                    <div class="select_cutomer_services">
                        <h3>{{ helperTrans('web.select_customer_service_representative') }}</h3>
                        <p>{{ helperTrans('web.continue_contract_completion') }}</p>
                        <div class="agents">
                            @foreach ($customer_services as $customer_service)
                                                        <label for="{{ $customer_service->name }}">
                                                            <div class="agent">
                                                                @php
                                                                    $imagePath =
                                                                        $customer_service->sex_type == 0
                                                                        ? asset('web/assets/images/man.svg')
                                                                        : asset('web/assets/images/woman.svg')
                                                                @endphp
                                                                <img src="{{ $imagePath }}" style="mix-blend-mode: multiply" alt="man">
                                                                <h6>أ. {{ $customer_service->name }}</h6>
                                                            </div>
                                                            <input type="radio" value="{{ $customer_service->id }}" checked
                                                                name="details_list[admin_id]" id="{{ $customer_service->name }}">
                                                        </label>
                            @endforeach
                        </div>
                        @auth
                            <button type="submit" id="myButtonsave" {{-- data-bs-toggle="modal" data-bs-target="#payModal"
                                --}}>
                                {{ helperTrans('web.confirmation') }}
                            </button>
                        @endauth
                        @guest
                            <a href="https://ussusalenjaz.sa/ar/auth" class="btns" style="
                                                                                            display: block;
                                                                                            text-align: center;
                                                                                            width: 100%;
                                                                                            border: none;
                                                                                            background: none;
                                                                                            outline: none;
                                                                                            background: #1d70b7;
                                                                                            color: #fff;
                                                                                            padding: 8px;
                                                                                            border-radius: 4px;
                                                                                            transition: all 0.4s ease-in-out;
                                                                                        ">
                                تأكيد

                            </a>
                        @endguest
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

@push('js')
    <script>
        $(document).ready(function () {
            // When the button is clicked
            $('#myButtonsave').on('click', function () {
                // Find the radio button that is checked
                var selectedAgentId = $('.agents input[type="radio"]:checked').val();
                // Update the hidden input value with the selected agent's ID
                $('input[name="details_list[admin_id]"]').val(selectedAgentId);
            });


            $('.TypeOneForm').off('submit').on('submit', function (event) {
                event.preventDefault();
                var formData = $(this).serialize(); // Serialize the form data
                let submitButton = $(event.originalEvent.submitter);
                let buttonText = submitButton.text();
                submitButton.prop('disabled', true); // Disable the button

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

                        // Redirect for non-bank transfer payments
                        window.location = "{{ route('web.orders.show', ['order' => ':orderId']) }}"
                            .replace(':orderId', data.order.id);

                    },
                    error: function (error) {
                        console.error(error);
                    }
                });
            });


        });



    </script>
@endpush