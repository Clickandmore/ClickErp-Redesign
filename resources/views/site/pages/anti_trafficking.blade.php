@extends('site.layouts.index')
@section('title')
    {{__("ingaz.Avoid_human")}}
@endsection
@section('content')
    <section class="anti-trafficking">
        <div class="container">
            <h2 class="section-title">{{ __('ingaz.anti_trafficking') }}</h2>

            <div class="anti-wrapper">
                <!-- سكشن 1: تعريف -->
                <div class="anti-card definition">
                    <h3><i class="fa-solid fa-circle-info"></i> {{ __('ingaz.definition_title') }}</h3>
                    <p>{{ __('ingaz.definition_desc') }}</p>
                </div>

                <!-- سكشن 2: العقوبات -->
                <div class="anti-card punishment">
                    <h3><i class="fa-solid fa-scale-balanced"></i> {{ __('ingaz.punishment_title') }}</h3>
                    <p>{{ __('ingaz.punishment_desc') }}</p>
                </div>

                <!-- سكشن 3: الجهات المسؤولة -->
                <div class="anti-card authority">
                    <h3><i class="fa-solid fa-building"></i> {{ __('ingaz.authority_title') }}</h3>
                    <p>{{ __('ingaz.authority_desc') }}</p>
                </div>

                <!-- سكشن 4: التحديثات -->
                <div class="anti-card updates">
                    <h3><i class="fa-solid fa-rotate"></i> {{ __('ingaz.updates_title') }}</h3>
                    <p>{{ __('ingaz.updates_desc') }}</p>
                </div>
            </div>
        </div>
    </section>

    <!-- awareness-section  -->
    <section class="awareness-section">
        <div class="container">
            <div class="awareness-wrapper">
                <div class="awareness-image">
                    <img src="{{ asset('site/assets/images/awarness.jpg') }}" alt="{{ __('ingaz.awareness_title') }}" />
                </div>
                <div class="awareness-content">
                    <h2>{{ __('ingaz.awareness_title') }}</h2>
                    <p>{{ __('ingaz.awareness_desc') }}</p>
                    <ul>
                        <li>{{ __('ingaz.awareness_list_1') }}</li>
                        <li>{{ __('ingaz.awareness_list_2') }}</li>
                        <li>{{ __('ingaz.awareness_list_3') }}</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- policies-accordion  -->
    <section class="policies-accordion">
        <div class="container">
            <h2>{{ __('ingaz.policies_title') }}</h2>

            <div class="accordion">
                <div class="accordion-item">
                    <button class="accordion-header">{{ __('ingaz.legal_framework') }}</button>
                    <div class="accordion-body">
                        <p>{{ __('ingaz.legal_framework_desc') }}</p>
                    </div>
                </div>

                <div class="accordion-item">
                    <button class="accordion-header">{{ __('ingaz.general_policies') }}</button>
                    <div class="accordion-body">
                        <p>{{ __('ingaz.general_policies_desc') }}</p>
                    </div>
                </div>

                <div class="accordion-item">
                    <button class="accordion-header">{{ __('ingaz.execution_procedures') }}</button>
                    <div class="accordion-body">
                        <p>{{ __('ingaz.execution_procedures_desc') }}</p>
                    </div>
                </div>

                <div class="accordion-item">
                    <button class="accordion-header">{{ __('ingaz.monitoring_mechanisms') }}</button>
                    <div class="accordion-body">
                        <p>{{ __('ingaz.monitoring_mechanisms_desc') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="report-section">
        <div class="container">
            <h2 class="section-title">{{ __('ingaz.reporting_title') }}</h2>

            <div class="report-cards">
                <div class="report-card">
                    <h3 class="card-title">{{ __('ingaz.unified_number_title') }}</h3>
                    <p class="card-desc">{{ __('ingaz.unified_number_desc') }}</p>
                    <a href="tel:19911" class="card-link">
                        <span>19911</span>
                        <i class="fa-solid fa-phone"></i>
                    </a>
                </div>

                <div class="report-card">
                    <h3 class="card-title">{{ __('ingaz.electronic_app_title') }}</h3>
                    <p class="card-desc">{{ __('ingaz.electronic_app_desc') }}</p>
                    <a href="#" class="card-link">
                        <span>{{ __('ingaz.electronic_app_link') }}</span>
                    </a>
                </div>

                <div class="report-card">
                    <h3 class="card-title">{{ __('ingaz.email_title') }}</h3>
                    <p class="card-desc">{{ __('ingaz.email_desc') }}</p>
                    <a href="mailto:info@ncct.gov.sa" class="card-link">
                        <span>info@ncct.gov.sa</span>
                        <i class="fa-solid fa-envelope"></i>
                    </a>
                </div>
            </div>

            <div class="report-note">
                <p>{{ __('ingaz.report_confidential') }}</p>
            </div>
        </div>
    </section>

@endsection
@section('js')
@endsection
