@extends('site.layouts.index')
@section('title')
@endsection
@section('content')
<!-- Main Banner  -->
<section class="mainBanner">
    <button onclick="goBack()" class="Back">
        <i class="fas fa-angle-left"></i>
    </button>
    <ul>
        <li>
            <a href="/"> {{__('ingaz.home')}} </a>
        </li>
        <li>
            <a href="{{route('site.visa.index')}}">{{__('ingaz.Recruitment journey')}}</a>
        </li>
        <li>
            <a href="#!" class="active">{{__('ingaz.Visa issuance')}}</a>
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
            data-parent="#SVGwave1BottomSMShape">
            <g>
                <path
                    class="wave-bottom-1-sm-0"
                    fill="#fdfdfd"
                    opacity=".8"
                    d="M10.9,63.9c0,0,42.9-34.5,87.5-14.2c77.3,35.1,113.3-2,146.6-4.7C293.7,41,315,61.2,315,61.2v54.4H10.9V63.9z"></path>
                <path
                    class="wave-bottom-1-sm-0"
                    fill="#fdfdfd"
                    opacity=".6"
                    d="M-55.7,64.6c0,0,42.9-34.5,87.5-14.2c77.3,35.1,113.3-2,146.6-4.7c48.7-4.1,69.9,16.2,69.9,16.2v54.4H-55.7     V64.6z"></path>
                <path
                    class="wave-bottom-1-sm-0"
                    fill="#fdfdfd"
                    opacity=".4"
                    fill-opacity="0"
                    d="M23.4,118.3c0,0,48.3-68.9,109.1-68.9c65.9,0,98,67.9,98,67.9v3.7H22.4L23.4,118.3z"></path>
                <path
                    class="wave-bottom-1-sm-0"
                    fill="#fdfdfd"
                    d="M-54.7,83c0,0,56-45.7,120.3-27.8c81.8,22.7,111.4,6.2,146.6-4.7c53.1-16.4,104,36.9,104,36.9l1.3,36.7l-372-3     L-54.7,83z"></path>
            </g>
        </svg>
    </figure>
</section>
<!-- ================ recruitment Visa ================= -->
<section class="recruitmentVisa">
    <div class="container">
        <div class="row">
            <!-- tabs -->
            <div class="col-md-3 col-lg-2 p-2 nav" role="tablist">
                <!-- button -->
                <button
                    class="nav-link active"
                    id="newAccount-tab"
                    data-bs-toggle="pill"
                    data-bs-target="#newAccount">
                    <i class="fa-solid fa-user-plus"></i>
                    <p> {{__('ingaz.new_user_tab')}}</p>
                </button>
                <!-- button -->
                <button
                    class="nav-link"
                    id="haveAccount-tab"
                    data-bs-toggle="pill"
                    data-bs-target="#haveAccount">
                    <i class="fa-solid fa-user-check"></i>
                    <p> {{__('ingaz.existing_user_tab')}}</p>
                </button>
            </div>
            <!-- content -->
            <div class="col-md-9 col-lg-10 p-2 p-md-4 tab-content">
                <!-- new account -->
                <div class="tab-pane fade show active" id="newAccount">
                    <h4 class="secondaryTitle">
                        {{__('ingaz.visa_steps_title')}}
                    </h4>
                    <h6 class="thirdTitle">
                        {{__('ingaz.visit_musaned')}}
                        <a href="https://musaned.com.sa" target="_Blank">
                            musaned.com.sa
                        </a>
                    </h6>
                    <!-- step -->
                    <div class="step">
                        <div class="head">
                            <h5> {{__('ingaz.step_create_account')}}</h5>
                        </div>
                        <div class="info">
                            <p>
                                {{__('ingaz.step_verification_note')}}
                            </p>
                        </div>
                        <!-- single step -->
                        <div class="secondaryTitle">
                            {{__('ingaz.step_basic_info_form')}}
                        </div>
                        <ul>
                            <li>{{__('ingaz.basic_info_fields.id_number')}}</li>
                            <li>{{__('ingaz.basic_info_fields.first_last_name')}}</li>
                            <li>{{__('ingaz.basic_info_fields.birth_date')}}</li>
                            <li>{{__('ingaz.basic_info_fields.mobile_number')}}</li>
                            <li>{{__('ingaz.basic_info_fields.city')}}</li>

                        </ul>
                        <div class="info">
                            <p>
                                <li>{{__('ingaz.activation_code_note')}}</li>
                            </p>
                        </div>

                        <!-- single step -->
                        <div class="secondaryTitle">
                            {{__('ingaz.step_password_info')}}
                        </div>
                        <ul>
                            <li>{{__('ingaz.step_password_mobile_email.password_confirmation')}}</li>
                            <li>{{__('ingaz.step_password_mobile_email.mobile_number_registered')}}</li>
                            <li>{{__('ingaz.step_password_mobile_email.email_address')}}</li>

                        </ul>
                        <div class="info">
                            <p>
                                <li>{{__('ingaz.password_hint')}}</li>
                            </p>
                        </div>
                    </div>

                    <!-- requirement -->
                    <div class="requirement">
                        <h4 class="secondaryTitle">
                            <li>{{__('ingaz.requirements_title')}}</li>

                        </h4>
                        <ul>
                            <li>{{__('ingaz.requirements_list.social_insurance')}}</li>
                            <li>{{__('ingaz.requirements_list.employment_status')}}</li>
                            <li>{{__('ingaz.requirements_list.financial_proof')}}</li>

                        </ul>
                    </div>

                    <!-- step -->
                    <div class="step">
                        <div class="head">
                            <h5> {{__('ingaz.payment_step_title')}}</h5>
                        </div>

                        <ul>
                            <li>{{__('ingaz.payment_steps.login_to_bank')}}</li>
                            <li>{{__('ingaz.payment_steps.choose_payments')}}</li>
                            <li>{{__('ingaz.payment_steps.gov_payments')}}</li>
                            <li>{{__('ingaz.payment_steps.service_type')}}</li>
                            <li>{{__('ingaz.payment_steps.request_type')}}</li>
                            <li>{{__('ingaz.payment_steps.operation_type')}}</li>
                            <li>{{__('ingaz.payment_steps.enter_id')}}</li>
                            <li>{{__('ingaz.payment_steps.select_visa_count')}}</li>
                            <li>{{__('ingaz.payment_steps.pay_fees')}}</li>


                        </ul>
                        <div class="info">
                            <p>
                                {{__('ingaz.payment_note')}}

                            </p>
                        </div>
                    </div>
                    <!-- step -->
                    <div class="step">
                        <div class="head">
                            <h5>
                                {{__('ingaz.visa_request_title')}}
                            </h5>
                        </div>
                        <!-- single step -->
                        <div class="secondaryTitle">{{__('ingaz.visa_info_step')}}</div>
                        <ul>
                            <li>{{__('ingaz.visa_info_fields.job_title')}}</li>
                            <li>{{__('ingaz.visa_info_fields.nationality')}}</li>
                            <li>{{__('ingaz.visa_info_fields.arrival_country')}}</li>


                        </ul>
                        <!-- single step -->
                        <div class="secondaryTitle">
                            {{__('ingaz.financial_proof_step')}}
                        </div>
                        <ul>
                            <li>{{__('ingaz.financial_proof_fields.salary_statement')}}</li>
                            <li>{{__('ingaz.financial_proof_fields.bank_statement')}}</li>


                        </ul>
                        <!-- single step -->
                        <div class="secondaryTitle">
                            {{__('ingaz.upload_documents')}}
                        </div>
                        <ul>
                            <li>{{__('ingaz.documents_list.financial_proof_doc')}}</li>
                            <li>{{__('ingaz.documents_list.id_or_family_card')}}</li>

                        </ul>
                        <div class="info">
                            <p>
                                {{__('ingaz.agreement_note')}}
                            </p>
                        </div>
                    </div>

                    <h4 class="secondaryTitle">
                        {{__('ingaz.application_status_note')}}

                    </h4>
                </div>
                <!-- have account -->
                <div class="tab-pane fade" id="haveAccount">
                    <h4 class="secondaryTitle">
                        {{__('ingaz.visa_steps_title')}}
                    </h4>
                    <h6 class="thirdTitle">
                        {{__('ingaz.visit_musaned')}}
                        <a href="https://musaned.com.sa" target="_Blank">
                            musaned.com.sa
                        </a>
                    </h6>

                    <!-- requirement -->
                    <div class="requirement">
                        <h4 class="secondaryTitle">
                            <li>{{__('ingaz.requirements_title')}}</li>
                        </h4>
                        <ul>
                            <li>{{__('ingaz.requirements_list.social_insurance')}}</li>
                            <li>{{__('ingaz.requirements_list.employment_status')}}</li>
                            <li>{{__('ingaz.requirements_list.financial_proof')}}</li>

                        </ul>
                    </div>

                    <!-- step -->
                    <div class="step">
                        <div class="head">
                            <h5> {{__('ingaz.payment_step_title')}}</h5>
                        </div>

                        <ul>
                            <li>{{__('ingaz.payment_steps.login_to_bank')}}</li>
                            <li>{{__('ingaz.payment_steps.choose_payments')}}</li>
                            <li>{{__('ingaz.payment_steps.gov_payments')}}</li>
                            <li>{{__('ingaz.payment_steps.service_type')}}</li>
                            <li>{{__('ingaz.payment_steps.request_type')}}</li>
                            <li>{{__('ingaz.payment_steps.operation_type')}}</li>
                            <li>{{__('ingaz.payment_steps.enter_id')}}</li>
                            <li>{{__('ingaz.payment_steps.select_visa_count')}}</li>
                            <li>{{__('ingaz.payment_steps.pay_fees')}}</li>


                        </ul>
                        <div class="info">
                            <p>
                                {{__('ingaz.payment_note')}}

                            </p>
                        </div>
                    </div>
                    <!-- step -->
                    <div class="step">
                        <div class="head">
                            <h5>
                                {{__('ingaz.visa_request_title')}}
                            </h5>
                        </div>
                        <!-- single step -->
                        <div class="secondaryTitle">{{__('ingaz.visa_info_step')}}</div>
                                    <ul>
                            <li>{{__('ingaz.visa_info_fields.job_title')}}</li>
                            <li>{{__('ingaz.visa_info_fields.nationality')}}</li>
                            <li>{{__('ingaz.visa_info_fields.arrival_country')}}</li>


                        </ul>
                        <!-- single step -->
                           <div class="secondaryTitle">
                            {{__('ingaz.financial_proof_step')}}
                        </div>
                        <ul>
                            <li>{{__('ingaz.financial_proof_fields.salary_statement')}}</li>
                            <li>{{__('ingaz.financial_proof_fields.bank_statement')}}</li>


                        </ul>
                      <div class="secondaryTitle">
                            {{__('ingaz.upload_documents')}}
                        </div>
                        <ul>
                            <li>{{__('ingaz.documents_list.financial_proof_doc')}}</li>
                            <li>{{__('ingaz.documents_list.id_or_family_card')}}</li>

                        </ul>
                        <div class="info">
                            <p>
                                {{__('ingaz.agreement_note')}}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- ================ /recruitment Visa ================= -->
@endsection
@section('js')
@endsection