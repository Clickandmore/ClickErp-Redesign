@extends('click.layout.indexs.index')
@section("style")
    <link rel="stylesheet" href="{{asset("admin")}}/assets/css/luno-style.css">
    @include('hr::hr.employees.inc._form_styles')
    <style>
        .fs-10 {
            font-size: 9px;
        }
    </style>
@endsection
@section('page-title')
    {{__('hr::hr.employees')}}
@endsection
@section('content')
    <div class="col-12 mt-4">
        <div class="employee-form-page">
            <div class="card employee-form-hero">
                <div class="card-body">
                    <div>
                        <span class="employee-form-eyebrow">
                            <i class="fa fa-user-plus"></i>
                            {{ __("buttons.add button") }} {{ __('hr::hr.employee') }}
                        </span>
                        <h3 class="employee-form-title">{{ __("buttons.add button") }} {{ __('hr::hr.employee') }}</h3>
                        <p class="employee-form-subtitle">{{ __("hr::hr.One-page employee form hint") }}</p>
                    </div>
                    <div class="employee-form-chip-list">
                        <span class="employee-form-chip"><i class="fa fa-id-card"></i> {{ __("hr::hr.basic information") }}</span>
                        <span class="employee-form-chip"><i class="fa fa-briefcase"></i> {{ __("hr::hr.Functional data") }}</span>
                        <span class="employee-form-chip"><i class="fa fa-paperclip"></i> {{ __("hr::hr.Employee papers") }}</span>
                        <span class="employee-form-chip"><i class="fa fa-money"></i> {{ __("hr::hr.Salary details") }}</span>
                    </div>
                </div>
            </div>

            <form class="from-submit-global" method="POST" action="{{route("employees.store")}}" enctype="multipart/form-data">
                @csrf
                <div class="row g-4">
                    <div class="col-xl-6 col-12">
                        <div class="card employee-form-card h-100">
                            <div class="card-header">
                                <h6 class="employee-form-card-title">{{ __("hr::hr.basic information") }}</h6>
                                <p class="employee-form-card-hint">{{ __("hr::hr.Basic data hint") }}</p>
                            </div>
                            <div class="card-body">
                                @include('hr::hr.employees.create.step1')
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-6 col-12">
                        <div class="card employee-form-card h-100">
                            <div class="card-header">
                                <h6 class="employee-form-card-title">{{ __("hr::hr.Functional data") }}</h6>
                                <p class="employee-form-card-hint">{{ __("hr::hr.Functional data hint") }}</p>
                            </div>
                            <div class="card-body">
                                @include('hr::hr.employees.create.step2')
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="card employee-form-card">
                            <div class="card-header">
                                <h6 class="employee-form-card-title">{{ __("hr::hr.Employee papers") }}</h6>
                                <p class="employee-form-card-hint">{{ __("hr::hr.Attachments hint") }}</p>
                            </div>
                            <div class="card-body">
                                @include('hr::hr.employees.create.step3')
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="card employee-form-card">
                            <div class="card-header">
                                <h6 class="employee-form-card-title">{{ __("hr::hr.Salary details") }}</h6>
                                <p class="employee-form-card-hint">{{ __("hr::hr.Compensation hint") }}</p>
                            </div>
                            <div class="card-body">
                                @include('hr::hr.employees.create.step4')
                            </div>
                        </div>
                    </div>
                </div>

                <div class="employee-form-submit mt-4">
                    <div>
                        <strong>{{ __("hr::hr.save") }}</strong>
                        <span>{{ __("hr::hr.One-page save hint") }}</span>
                    </div>
                    <button type="submit" class="btn btn-primary">{{ __("hr::hr.save") }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
@section('js')
    @include('hr::hr.employees.inc._js')
@endsection
