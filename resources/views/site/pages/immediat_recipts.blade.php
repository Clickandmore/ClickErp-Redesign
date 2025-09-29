@extends('site.layouts.index')
@section('title')
@endsection
@section('content')
    <section class="page_header">
        <h1>{{ $type->title }}</h1>
        <h6><a href="{{ route('site.home.index') }}">{{ helperTrans('web.Home') }}</a> / {{ $type->title }}</h6>
    </section>
    <div class="cvExploresNew">
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
                                            <img
                                                src="{{ $maid->cv_preview ? showFile($maid->cv_preview) : showFile($maid->cv_file) }}"
                                                alt="cv1"
                                                style="border-radius: 50%; object-fit: cover; width: 130px; height: 130px;">
                                        @endif
                                    </a>
                                </div>
                                <div class="info">
                                    <ul>
                                        <li>
                                            <span>{{ helperTrans('web.nationality') }}</span>
                                            <h3>{{ $maid->nationality->title ?? '-' }}</h3>
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
                                            <h3>{{ $maid->religion->title ?? '-'}}</h3>
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
                        <form id="wizard-property-listing-form" class="TypeThreeForm" method="post"
                              action="{{route('site.immediate-recipts.store')}}">
                            @csrf
                            <input type="hidden" name="maid_id" value="{{ $maid->id }}">
                            <input type="hidden" name="payment_method" value="none">
                            <input type="hidden" name="job_type_id" value="{{ $type->id }}">
                            <input type="hidden" name="order_type" value="{{ $type->id }}">
                            <div class="select_cutomer_services">
                                <h3>{{__("ingaz.Selecting a customer service representative")}}</h3>
                                <p>{{__("ingaz.To complete the sponsorship transfer application")}}</p>
                                <div class="agents">
                                    @foreach ($customer_services as $customer_service)
                                        <label for="{{ $customer_service->name }}">
                                            <div class="agent">
                                                @php
                                                    $imagePath =
                                                        $customer_service->sex_type == 0
                                                            ? asset('web/assets/images/man.svg')
                                                            : asset('web/assets/images/woman.svg');
                                                @endphp
                                                <img src="{{ $imagePath }}" style="mix-blend-mode: multiply"
                                                     alt="man">
                                                <h6>أ. {{ $customer_service->name }}</h6>
                                            </div>
                                            <input type="radio" value="{{ $customer_service->id }}"
                                                   name="details_list[admin_id]" id="{{ $customer_service->name }}">
                                        </label>
                                    @endforeach
                                </div>

                                <button type="submit" id="myButtonsave">
                                    @auth
                                        {{__("ingaz.Confirm sponsorship transfer request")}}
                                    @endauth
                                    @guest
                                        تسجيل الدخول لإكمال الطلب
                                    @endguest
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </div>
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel"
         data-bs-backdrop="static"
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
                        <p>{{ helperTrans('web.please_contact_with') }}<span
                                id="customer_name"></span>{{ helperTrans('web.in_24_for_cv') }}
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
@endsection
@section('scripts')
    <script>
        $('#closeMod').click(function () {
            window.location = '/';
        });
        $(document).ready(function () {
            $('#wizard-property-listing-form').on('submit', function (e) {
                e.preventDefault();
                let formData = new FormData(this);
                let url = $(this).attr('action');
                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        Toastify({
                            text: response.message,
                            duration: 4000,
                            gravity: "top",
                            position: "right",
                            backgroundColor: "linear-gradient(to right, #00b09b, #96c93d)",
                            stopOnFocus: true
                        }).showToast();
                        setTimeout(function () {
                            location.href = response.data.redirect;
                        }, 500);

                    },
                    error: function (xhr) {
                        if (xhr.status === 401) {
                            const json = xhr.responseJSON;
                            setTimeout(function () {
                                window.location.href = json.redirect;
                            }, 300);

                            return;
                        }
                        if (xhr.status === 422) {
                            // أخطاء الفالديشن
                            let errors = xhr.responseJSON.errors;
                            let errorMessages = '';
                            for (let key in errors) {
                                errorMessages += `<p style="margin:0 0 5px;color:red;text-align:center;">${errors[key][0]}</p>`;
                            }
                            Swal.fire({
                                icon: 'error',
                                title: '{{ __("ingaz.error") }}',
                                html: `<div>${errorMessages}</div>`,
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: '{{ __("ingaz.error") }}',
                                text: '{{ __("ingaz.error_from_server") }}'
                            });
                        }
                    }


                });
            })

            // استعادة بيانات الطلب المحفوظة
            @if (session('restore_order_data'))
            var restoreData = @json(session('restore_order_data'));
            console.log('Restoring order data:', restoreData);

            // استعادة البيانات في النموذج
            if (restoreData.maid_id) {
                $("input[name='maid_id']").val(restoreData.maid_id);
            }
            if (restoreData.job_type_id) {
                $("input[name='job_type_id']").val(restoreData.job_type_id);
            }
            if (restoreData.order_type) {
                $("input[name='order_type']").val(restoreData.order_type);
            }
            if (restoreData.payment_method) {
                $("input[name='payment_method']").val(restoreData.payment_method);
            }
            if (restoreData.admin_id) {
                $("input[name='details_list[admin_id]'][value='" + restoreData.admin_id + "']").prop('checked',
                    true);
            }
            if (restoreData.address_id) {
                $("input[name='details_list[address_id]']").val(restoreData.address_id);
            }
            if (restoreData.package_id) {
                $("input[name='details_list[package_id]']").val(restoreData.package_id);
            }

            // إظهار رسالة للمستخدم
            Swal.fire({
                title: 'تم استعادة بيانات الطلب',
                text: 'تم استعادة بيانات الطلب المحفوظة مسبقاً',
                icon: 'success',
                timer: 3000,
                showConfirmButton: false
            });
            @endif

            $('.newPayFormClass').off('submit').on('submit', function (event) {
                event.preventDefault();
                var formData = $(this).serialize(); // Serialize the form data
                let submitButton = $(event.originalEvent.submitter);
                let buttonText = submitButton.text();
                submitButton.prop('disabled', true); // Disable the button

                var selectedAgentId = $('.agents input[type="radio"]:checked').val();
                // Update the hidden input value with the selected agent's ID
                $('input[name="details_list[admin_id]"]').val(selectedAgentId);


                // التحقق من تسجيل الدخول قبل إرسال الطلب
                if (!{{ auth()->check() ? 'true' : 'false' }}) {
                    // إذا لم يكن مسجل دخول، اعرض رسالة تطلب تسجيل الدخول
                    Swal.fire({
                        title: 'تسجيل الدخول مطلوب',
                        text: 'يجب تسجيل الدخول لتأكيد طلب التأجير',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'تسجيل الدخول',
                        cancelButtonText: 'إلغاء',
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // توجيه المستخدم لصفحة تسجيل الدخول
                            window.location.href = '{{ route('web.auth') }}';
                        }
                    });
                    // إعادة تفعيل الزر
                    submitButton.prop('disabled', false);
                    submitButton.html(buttonText);
                    return;
                }

                $.ajax({
                    type: 'post',
                    url: '{{ route('web.orders.store') }}',
                    data: formData,
                    beforeSend: function () {
                        submitButton.html(
                            '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ' +
                            buttonText);
                    },
                    complete: function () {
                        submitButton.html("{{ helperTrans('web.confirmation') }}");
                        submitButton.prop('disabled', false); // Re-enable the button
                    },
                    success: function (response) {
                        console.log('Response:', response);
                        var data = response.data;

                        if (response.code == 410) {
                            errorToster('{{ __('auth.an error occurred') }}', response
                                .message);
                            window.location = '/';
                            return;
                        }

                        // إظهار رسالة نجاح
                        Swal.fire({
                            title: 'تم تأكيد الطلب بنجاح!',
                            text: 'سيتم التواصل معك قريباً لتأكيد التفاصيل',
                            icon: 'success',
                            confirmButtonText: 'حسناً',
                            confirmButtonColor: '#28a745'
                        }).then((result) => {
                            // التوجيه لصفحة الطلب أو الصفحة الرئيسية
                            if (data && data.order && data.order.id) {
                                window.location =
                                    "{{ route('web.orders.show', ['order' => ':orderId']) }}"
                                        .replace(':orderId', data.order.id);
                            } else {
                                window.location = "{{ route('web.orders.index') }}";
                            }
                        });
                    },
                    error: function (xhr, status, error) {
                        console.error('Error:', xhr.responseText);

                        // إعادة تفعيل الزر
                        submitButton.prop('disabled', false);
                        submitButton.html(buttonText);

                        // إظهار رسالة خطأ
                        var errorMessage = 'حدث خطأ أثناء تأكيد الطلب';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        } else if (xhr.status === 401) {
                            errorMessage = 'يجب تسجيل الدخول لتأكيد الطلب';
                        } else if (xhr.status === 422) {
                            errorMessage = 'بيانات الطلب غير صحيحة';
                        }

                        Swal.fire({
                            title: 'خطأ!',
                            text: errorMessage,
                            icon: 'error',
                            confirmButtonText: 'حسناً',
                            confirmButtonColor: '#dc3545'
                        });
                    }
                });
            });
        });

        var firstPage = null;
        var secondPage = null;
        var currentStep = 1;

        $(document).ready(function () {
            var stepper = new Stepper($('.bs-stepper')[0]);
            $('.btn-next').on('click', function () {
                if (stepper._currentIndex === 1) {
                    if ((firstPage == null || firstPage === '' || firstPage === undefined) && $(
                        'input[name="address_id"]:checked').val() !== 'skip' && $(
                        'input[name="address_id"]:checked').val() !== 'manual') {
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
            $('.btn-prev').on('click', function () {
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
                $('input[name="details_list[address_id]"]').val(id);

                // إعادة تعيين حدود الخيارات الجديدة
                if (typeof $('#manual-address-option') !== 'undefined') {
                    $('#manual-address-option').css('border', '2px dashed #007bff');
                    $('#skip-address-option').css('border', '2px dashed #6c757d');
                }
            }
        }

        // دالة تخطي العنوان
        function skipAddress() {
            firstPage = 'skip';
            $('input[name="details_list[address_id]"]').val('');
            document.getElementById('llocation-l').innerHTML =
                '<div class="alert alert-info"><i class="fa-light fa-info-circle me-2"></i>تم تخطي اختيار العنوان</div>';

            // إعادة تعيين حدود الخيارات
            if (typeof $('#manual-address-option') !== 'undefined') {
                $('#manual-address-option').css('border', '2px dashed #007bff');
                $('#skip-address-option').css('border', '2px solid #6c757d');
            }
        }

        // دالة إدخال العنوان يدوياً
        function manualAddress() {
            firstPage = 'manual';
            $('input[name="details_list[address_id]"]').val('');
            // سيتم إضافة العنوان اليدوي لاحقاً
        }

        // Get references to the checkbox and button
        var myCheckbox = document.getElementById('acceptCheckbox');
        var myButton = document.getElementById('myButtonsave');

        // Attach event listener to the checkbox
        myCheckbox.addEventListener('change', function () {
            // Enable or disable the button based on the checkbox state
            myButton.disabled = !this.checked;
        });
    </script>
    <script>
        // حل بسيط لحفظ البيانات والعودة لنفس الصفحة
        document.addEventListener('DOMContentLoaded', function () {
            console.log("Type 3 order page loaded (simple solution)");

            // حفظ URL الحالي في localStorage
            const currentUrl = window.location.href;
            localStorage.setItem('returnUrl', currentUrl);
            console.log('Current URL saved:', currentUrl);

            // البحث عن زر تسجيل الدخول
            const saveButton = document.querySelector('.save-order-and-login');
            if (saveButton) {
                console.log('Save button found');

                saveButton.addEventListener('click', function (e) {
                    e.preventDefault();
                    console.log('Save order button clicked');

                    try {
                        // جمع بيانات الطلب
                        const form = document.querySelector('.TypeThreeForm');
                        const formData = new FormData(form);
                        const formDataString = new URLSearchParams(formData).toString();

                        const orderData = {
                            maid_id: document.querySelector('input[name="maid_id"]')?.value || '',
                            job_type_id: document.querySelector('input[name="job_type_id"]')?.value ||
                                '',
                            order_type: document.querySelector('input[name="order_type"]')?.value || '',
                            payment_method: document.querySelector('input[name="payment_method"]')
                                ?.value || '',
                            admin_id: document.querySelector(
                                'input[name="details_list[admin_id]"]:checked')?.value || '',
                            form_data: formDataString,
                            return_url: currentUrl
                        };

                        console.log('Order data to save:', orderData);

                        // حفظ البيانات في localStorage
                        localStorage.setItem('pendingOrder', JSON.stringify(orderData));
                        console.log('Data saved successfully');

                        // التوجيه إلى صفحة تسجيل الدخول
                        window.location.href =
                            "{{ route('site.login.index') }}?redirect=order_confirmation";
                    } catch (error) {
                        console.error('Error saving data:', error);
                        // في حالة الخطأ، التوجيه مباشرة
                        window.location.href =
                            "{{ route('site.login.index') }}?redirect=order_confirmation";
                    }
                });
            } else {
                console.log('Save button not found');
            }

            // معالجة زر التأكيد للمستخدمين المسجلين
            const submitButton = document.getElementById('myButtonsave');
            if (submitButton) {
                submitButton.addEventListener('click', function () {
                    const selectedAgent = document.querySelector('.agents input[type="radio"]:checked');
                    if (selectedAgent) {
                        const adminIdInput = document.querySelector('input[name="details_list[admin_id]"]');
                        if (adminIdInput) {
                            adminIdInput.value = selectedAgent.value;
                        }
                    }
                });
            }

            // معالجة إرسال النموذج
            {{--const form = document.querySelector('.TypeThreeForm');--}}
            {{--if (form) {--}}
            {{--    form.addEventListener('submit', function (event) {--}}
            {{--        event.preventDefault();--}}

            {{--        const formData = new FormData(this);--}}
            {{--        const submitButton = event.submitter;--}}
            {{--        const buttonText = submitButton.textContent;--}}
            {{--        submitButton.disabled = true;--}}

            {{--        const xhr = new XMLHttpRequest();--}}
            {{--        xhr.open('POST', '{{ route('web.orders.store') }}', true);--}}
            {{--        xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]')--}}
            {{--            ?.getAttribute('content') || '');--}}
            {{--        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');--}}

            {{--        xhr.onload = function () {--}}
            {{--            submitButton.disabled = false;--}}
            {{--            submitButton.textContent = "{{ helperTrans('web.confirmation') }}";--}}

            {{--            if (xhr.status === 200) {--}}
            {{--                try {--}}
            {{--                    const response = JSON.parse(xhr.responseText);--}}

            {{--                    if (response.code == 410) {--}}
            {{--                        window.location = '/';--}}
            {{--                        return;--}}
            {{--                    }--}}

            {{--                    alert('تم إرسال طلب نقل الكفالة بنجاح');--}}
            {{--                    window.location.href = '{{ route('site.profile.index') }}';--}}
            {{--                } catch (e) {--}}
            {{--                    console.error('Error parsing response:', e);--}}
            {{--                }--}}
            {{--            }--}}
            {{--        };--}}

            {{--        xhr.onerror = function() {--}}
            {{--            submitButton.disabled = false;--}}
            {{--            submitButton.textContent = buttonText;--}}
            {{--            console.error('Request failed');--}}
            {{--        };--}}

            {{--        const data = new URLSearchParams(formData).toString();--}}
            {{--        xhr.send(data);--}}
            {{--    });--}}
            {{--}--}}
        });
        $('#addLocation').modal('hide');
    </script>
@endsection
