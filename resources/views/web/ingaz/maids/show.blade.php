@extends('web.layout.app')


@push('css')
<link rel="stylesheet" href="{{ asset('web') }}/assets/css/bs-stepper.css">
@endpush

@push('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endpush


@section('content')

<main>
    <section class="page_header">
        <h1>{{ $type->title }}</h1>
        <h6><a href="{{ route('web.home') }}">{{ helperTrans('web.Home') }}</a> / {{ $type->title }}</h6>
    </section>


    @if ($type->id == 1)
    @include('web.ingaz.maids.sub.type_1_order')
    @elseif ($type->id == 2)
    @include('web.ingaz.maids.sub.type_2_order')
    @elseif ($type->id == 3)
    @include('web.ingaz.maids.sub.type_3_order')
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
            // استعادة بيانات الطلب المحفوظة
            @if(session('restore_order_data'))
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
                    $("input[name='details_list[admin_id]'][value='" + restoreData.admin_id + "']").prop('checked', true);
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

            $('.newPayFormClass').off('submit').on('submit', function(event) {
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
                            window.location.href = '{{ route("web.auth") }}';
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
                        submitButton.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ' + buttonText);
                    },
                    complete: function () {
                        submitButton.html("{{ helperTrans('web.confirmation') }}");
                        submitButton.prop('disabled', false); // Re-enable the button
                    },
                    success: function (response) {
                        console.log('Response:', response);
                        var data = response.data;

                        if (response.code == 410) {
                            errorToster('{{ __('auth.an error occurred') }}', response.message);
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
                                window.location = "{{ route('web.orders.show', ['order' => ':orderId']) }}"
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

        $(document).ready(function() {
            var stepper = new Stepper($('.bs-stepper')[0]);
            $('.btn-next').on('click', function() {
                if (stepper._currentIndex === 1) {
                    if ((firstPage == null || firstPage === '' || firstPage === undefined) && $('input[name="address_id"]:checked').val() !== 'skip' && $('input[name="address_id"]:checked').val() !== 'manual') {
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
            document.getElementById('llocation-l').innerHTML = '<div class="alert alert-info"><i class="fa-light fa-info-circle me-2"></i>تم تخطي اختيار العنوان</div>';

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
        myCheckbox.addEventListener('change', function() {
            // Enable or disable the button based on the checkbox state
            myButton.disabled = !this.checked;
        });
</script>
@endpush
