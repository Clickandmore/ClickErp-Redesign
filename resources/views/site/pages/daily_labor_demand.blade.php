@extends('site.layouts.index')
@section('title')
    {{__("ingaz.request_for_professional_labor")}}
@endsection
@section('content')
    <!-- Main Banner  -->
    <section class="mainBanner">
        <button onclick="goBack()" class="Back">
            <i class="fas fa-angle-left"></i>
        </button>
        <ul>
            <li>
                <a href="{{route('site.home.index')}}"> {{__("ingaz.home")}} </a>
            </li>
            <li>
                <a href="{{route('site.home.index')}}#services">{{__("ingaz.services")}}  </a>
            </li>
            <li>
                <a href="#!" class="active">{{__("ingaz.request_for_professional_labor")}} </a>
            </li>
        </ul>
        <figure>
            <svg
                xmlns="http://www.w3.org/2000/svg"
                preserveAspectRatio="none"
                x="0px"
                y="0px"
                viewBox="0 0 300 100"
                style="margin-bottom: -35px; enable-background: new 0 0 300 100"
                class="injected-svg js-svg-injector"
                data-parent="#SVGwave1BottomSMShape"
            >
                <g>
                    <path
                        class="wave-bottom-1-sm-0"
                        fill="#fdfdfd"
                        opacity=".8"
                        d="M10.9,63.9c0,0,42.9-34.5,87.5-14.2c77.3,35.1,113.3-2,146.6-4.7C293.7,41,315,61.2,315,61.2v54.4H10.9V63.9z"
                    ></path>
                    <path
                        class="wave-bottom-1-sm-0"
                        fill="#fdfdfd"
                        opacity=".6"
                        d="M-55.7,64.6c0,0,42.9-34.5,87.5-14.2c77.3,35.1,113.3-2,146.6-4.7c48.7-4.1,69.9,16.2,69.9,16.2v54.4H-55.7     V64.6z"
                    ></path>
                    <path
                        class="wave-bottom-1-sm-0"
                        fill="#fdfdfd"
                        opacity=".4"
                        fill-opacity="0"
                        d="M23.4,118.3c0,0,48.3-68.9,109.1-68.9c65.9,0,98,67.9,98,67.9v3.7H22.4L23.4,118.3z"
                    ></path>
                    <path
                        class="wave-bottom-1-sm-0"
                        fill="#fdfdfd"
                        d="M-54.7,83c0,0,56-45.7,120.3-27.8c81.8,22.7,111.4,6.2,146.6-4.7c53.1-16.4,104,36.9,104,36.9l1.3,36.7l-372-3     L-54.7,83z"
                    ></path>
                </g>
            </svg>
        </figure>
    </section>

    <section class="why-choose-us">
        <div class="container">
            <div class="headTitle text-center" data-aos="fade-up" data-aos-delay="100">
                <h4>{{__("ingaz.why_choose_our_services")}}</h4>
            </div>
            <div class="cards-wrapper">
                @forelse($whyUs as $items)
                    <div class="feature-card" data-aos="fade-up">
                        <div class="feature-text">
                            <h3>{{$items->title}}</h3>
                            <p>{{$items->descripton}}</p>
                        </div>
                        <div class="feature-icon"><i class="{{$items->icon}}"></i></div>
                    </div>

                @empty
                    <!-- Card 1 -->
                    <div class="feature-card" data-aos="fade-up">
                        <div class="feature-text">
                            <h3>عمالة مؤهلة</h3>
                            <p>نوفر عمالة مهنية مؤهلة ومدربة بأعلى المعايير لضمان جودة الأداء والكفاءة في العمل.</p>
                        </div>
                        <div class="feature-icon"><i class="fas fa-user-check"></i></div>
                    </div>

                    <!-- Card 2 -->
                    <div class="feature-card" data-aos="fade-up">
                        <div class="feature-text">
                            <h3>توفير الوقت</h3>
                            <p>إجراءات سريعة ومبسطة لتوفير الوقت والجهد في عملية استقدام العمالة المهنية.</p>
                        </div>
                        <div class="feature-icon"><i class="fas fa-clock"></i></div>
                    </div>

                    <!-- Card 3 -->
                    <div class="feature-card" data-aos="fade-up">
                        <div class="feature-text">
                            <h3>دعم متواصل</h3>
                            <p>نقدم الدعم والاستشارات المتواصلة قبل وبعد عملية الاستقدام لضمان رضاكم الكامل.</p>
                        </div>
                        <div class="feature-icon"><i class="fas fa-headset"></i></div>
                    </div>
                @endforelse
            </div>
        </div>
    </section>
    <!-- Form  Section -->
    <section class="form-steps-section" id="requestForm">
        <div class="container">
            <div class="row">
                <div class="col-lg-6 col-12">
                    <div class="steps-container">
                        <h2 class="section-title" data-aos="fade-up">{{__("ingaz.request_for_professional_labor")}}</h2>
                        <div class="step-card mt-5" data-aos="fade-up">
                            <div class="step-icon"><i class="fas fa-clipboard-list"></i></div>
                            <div class="step-content">
                                <h3>{{__("ingaz.fill_out_the_form")}}</h3>
                                <p>{{__("ingaz.text_1")}}.</p>
                            </div>
                        </div>

                        <div class="step-card" data-aos="fade-up">
                            <div class="step-icon"><i class="fas fa-user-check"></i></div>
                            <div class="step-content">
                                <h3>{{__("ingaz.review_the_application")}}</h3>
                                <p>{{__("ingaz.text_2")}}</p>
                            </div>
                        </div>

                        <div class="step-card" data-aos="fade-up">
                            <div class="step-icon"><i class="fas fa-phone-alt"></i></div>
                            <div class="step-content">
                                <h3>{{__("ingaz.contact_you")}}</h3>
                                <p>{{__("ingaz.text_3")}}</p>
                            </div>
                        </div>

                        <div class="step-card" data-aos="fade-up">
                            <div class="step-icon"><i class="fas fa-handshake"></i></div>
                            <div class="step-content">
                                <h3>{{__("ingaz.receiving_workers")}}</h3>
                                <p>{{__("ingaz.text_4")}}</p>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="col-lg-6 col-12">
                    <div class="form-container">
                        <form id="form-daily-lebor-demand" method="post"
                              action="{{route('site.daily-lebor-demand.store')}}" class="row dailyForm"
                              data-aos="fade-up">
                            @csrf
                            <div class="col-12 p-2">
                                <label class="form-label">
                                    <i class="fas fa-building me-2"></i>{{__("ingaz.facility_name")}} *
                                </label>
                                <input type="text" class="form-control" name="facility_name"/>
                                <div class="invalid-feedback">{{__("ingaz.this_field_is_required")}}</div>
                            </div>

                            <div class="col-md-6 p-2">
                                <label class="form-label">
                                    <i class="fas fa-phone me-2"></i>{{__("ingaz.contact_number")}} *
                                </label>
                                <input type="tel" class="form-control" name="phone"/>
                                <div class="invalid-feedback">{{__("ingaz.this_field_is_required")}}</div>
                            </div>

                            <div class="col-md-6 p-2">
                                <label class="form-label">
                                    <i class="fas fa-users me-2"></i> {{__("ingaz.number_of_workers_required")}} *
                                </label>
                                <input type="number" min="1" name="number_of_workers_required" class="form-control"/>
                                <div class="invalid-feedback">{{__("ingaz.this_field_is_required")}}</div>
                            </div>

                            <div class="col-md-6 p-2">
                                <label class="form-label">
                                    <i class="fas fa-briefcase me-2"></i> {{__("ingaz.type_of_labor_required")}} *
                                </label>
                                <select class="form-control" name="job_title_id">
                                    <option value="" disabled selected>{{__("ingaz.type_of_labor_required")}}</option>
                                    @foreach($jobTitles as  $jobTitle)
                                        <option value="{{$jobTitle->id}}">{{$jobTitle->title}}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback">{{__("ingaz.this_field_is_required")}}</div>
                            </div>
                            <div class="col-md-6 p-2">
                                <label class="form-label">
                                    <i class="fas fa-passport me-2"></i> {{__("ingaz.visa_number")}} *
                                </label>
                                <input type="text" class="form-control" name="visa_number"/>
                                <div class="invalid-feedback">{{__("ingaz.this_field_is_required")}}</div>
                            </div>
                            <div class="col-12 p-2">
                                <label class="form-label">
                                    <i class="fas fa-feather-alt me-2"></i> {{__("ingaz.other_requirement")}}
                                </label>
                                <textarea class="form-control" name="other_requirement" rows="4"></textarea>
                            </div>
                            <div class="col-12 text-center p-2 mt-3">
                                <button type="submit" class="defaultBtn">
                                    {{__("ingaz.send_request")}} <i class="fas fa-paper-plane ms-2"></i>
                                    <span></span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Testimonials Section -->
    <section class="testimonials-section">
        <div class="container">
            <div class="headTitle text-center" data-aos="fade-up" data-aos-delay="100">
                <h4>{{__("ingaz.our_customers_opinions")}}</h4>
            </div>
            <div class="row">
                @forelse($testimonials as $testimonial)
                    <div class="col-md-6 col-12">
                        <div class="testimonial-card" data-aos="fade-up">
                            <div class="testimonial-text">
                                <p>{{$testimonial->message}}</p>
                            </div>
                            <div class="client-info">
                                <div class="client-image">
                                    <img src="{{showFile($testimonial->image)}}" alt="عميل">
                                </div>
                                <div>
                                    <h5 class="mb-0">{{$testimonial->name}}</h5>
                                    <p class="text-muted mb-0">{{$testimonial->job}}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                @empty
                    <div class="col-md-6 col-12">
                        <div class="testimonial-card" data-aos="fade-up">
                            <div class="testimonial-text">
                                <p>تعاملت مع الشركة لاستقدام عمالة فنية لمشروعي وكانت تجربة رائعة، العمالة كانت مؤهلة
                                    والخدمة سريعة ومميزة.</p>
                            </div>
                            <div class="client-info">
                                <div class="client-image">
                                    <img src="{{asset('site')}}/assets/images/avater2.webp" alt="عميل">
                                </div>
                                <div>
                                    <h5 class="mb-0">أحمد السعدي</h5>
                                    <p class="text-muted mb-0">مدير مشروع</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-12">
                        <div class="testimonial-card" data-aos="fade-up">
                            <div class="testimonial-text">
                                <p>قدمت طلب استقدام طبيب وممرضين وكانت الخدمة ممتازة، الإجراءات سريعة والموظفين محترفين
                                    ومتعاونين.</p>
                            </div>
                            <div class="client-info">
                                <div class="client-image">
                                    <img src="{{asset('site')}}/assets/images/avatar.webp" alt="عميل">
                                </div>
                                <div>
                                    <h5 class="mb-0">فاطمة العبدالله</h5>
                                    <p class="text-muted mb-0">مالكة مركز طبي</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

@endsection
@section('js')
    <script>
        $(document).ready(function () {
            $('#form-daily-lebor-demand').on('submit', function (e) {
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
                        $('#form-daily-lebor-demand')[0].reset();
                    },
                    error: function (xhr) {
                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;
                            let errorMessages = '';
                            for (let key in errors) {
                                errorMessages += `<p style="margin: 0 0 5px; color: red; text-align: center;">${errors[key][0]}</p>`;
                            }
                            Swal.fire({
                                icon: 'error',
                                title: '{{ __("ingaz.error") }}',
                                html: `<div>${errorMessages}</div>`,
                            });
                        } else {
                            Swal.fire('{{ __("ingaz.error") }}', '{{ __("ingaz.error_from_server") }}', 'error');
                        }
                    }

                });
            })
        })
    </script>
@endsection
