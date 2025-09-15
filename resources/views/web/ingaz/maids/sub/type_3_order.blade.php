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
                <form id="wizard-property-listing-form" class="TypeThreeForm" onSubmit="return false">
                    @csrf
                    <input type="hidden" name="maid_id" value="{{ $maid->id }}">
                    <input type="hidden" name="payment_method" value="none">
                    <input type="hidden" name="job_type_id" value="{{ $type->id }}">
                    <input type="hidden" name="order_type" value="{{ $type->id }}">
                    <div class="select_cutomer_services">
                        <h3>اختيار ممثل خدمة العملاء</h3>
                        <p>لإكمال طلب نقل الكفالة</p>
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
                                                            <input type="radio" value="{{ $customer_service->id }}" 
                                                                name="details_list[admin_id]" id="{{ $customer_service->name }}">
                                                        </label>
                            @endforeach
                        </div>
                        @auth
                            <button type="submit" id="myButtonsave">
                                تأكيد طلب نقل الكفالة
                            </button>
                        @endauth
                        @guest
                            <a href="#" class="btns save-order-and-login" style="
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
                                تسجيل الدخول لإكمال الطلب

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
        // حل بسيط لحفظ البيانات والعودة لنفس الصفحة
        document.addEventListener('DOMContentLoaded', function() {
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
                            job_type_id: document.querySelector('input[name="job_type_id"]')?.value || '',
                            order_type: document.querySelector('input[name="order_type"]')?.value || '',
                            payment_method: document.querySelector('input[name="payment_method"]')?.value || '',
                            admin_id: document.querySelector('input[name="details_list[admin_id]"]:checked')?.value || '',
                            form_data: formDataString,
                            return_url: currentUrl
                        };

                        console.log('Order data to save:', orderData);

                        // حفظ البيانات في localStorage
                        localStorage.setItem('pendingOrder', JSON.stringify(orderData));
                        console.log('Data saved successfully');

                        // التوجيه إلى صفحة تسجيل الدخول
                        window.location.href = "{{ route('web.auth') }}?redirect=order_confirmation";
                    } catch (error) {
                        console.error('Error saving data:', error);
                        // في حالة الخطأ، التوجيه مباشرة
                        window.location.href = "{{ route('web.auth') }}?redirect=order_confirmation";
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
            const form = document.querySelector('.TypeThreeForm');
            if (form) {
                form.addEventListener('submit', function (event) {
                    event.preventDefault();

                    const formData = new FormData(this);
                    const submitButton = event.submitter;
                    const buttonText = submitButton.textContent;
                    submitButton.disabled = true;

                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', '{{ route("web.orders.store") }}', true);
                    xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '');
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

                    xhr.onload = function() {
                        submitButton.disabled = false;
                        submitButton.textContent = "{{ helperTrans('web.confirmation') }}";

                        if (xhr.status === 200) {
                            try {
                                const response = JSON.parse(xhr.responseText);

                                if (response.code == 410) {
                                    window.location = '/';
                                    return;
                                }

                                alert('تم إرسال طلب نقل الكفالة بنجاح');
                                window.location.href = '{{ route("web.profile") }}';
                            } catch (e) {
                                console.error('Error parsing response:', e);
                            }
                        }
                    };

                    xhr.onerror = function() {
                        submitButton.disabled = false;
                        submitButton.textContent = buttonText;
                        console.error('Request failed');
                    };

                    const data = new URLSearchParams(formData).toString();
                    xhr.send(data);
                });
            }
        });
    </script>
@endpush
