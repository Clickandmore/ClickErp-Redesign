@extends('admin.layout.indexs.index')
@section('page-title')
{{helperTrans("ingaz.renew_contract")}}
@endsection
@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{__('ingaz.view_title_contract')}}- #{{ $renew->id }}</h3>
            <div class="card-tools">
                <a href="{{ route('renew-contracts.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i>   {{__('ingaz.back_to_list')}}
                </a>
            </div>
        </div>

        <div class="card-body">
            <!-- بيانات العميل -->
            <h5><i class="fas fa-user"></i>   {{__('ingaz.client_info')}}</h5>
            <ul>
                <li><strong>{{__('ingaz.name')}}:</strong> {{ $renew->client_name }}</li>
                <li><strong>{{__('ingaz.phone')}}:</strong> {{ $renew->client_phone }}</li>
                <li><strong>{{__('ingaz.address')}}:</strong> {{ $renew->client_address }}</li>
                <li><strong>{{__('ingaz.city')}}:</strong> {{ $renew->city }}</li>
                <li><strong>  {{__('ingaz.customer_national_address')}}:</strong> {{ $renew->customer_national_address }}</li>
                @if($renew->user)
                    <li><strong>{{__('ingaz.user')}}  :</strong> {{ $renew->user->name ?? '-' }}</li>
                @endif
            </ul>

            <!-- بيانات العاملة -->
            <h5 class="mt-4"><i class="fas fa-female"></i>   {{__('ingaz.maid_info')}}</h5>
            <ul>
                <li><strong>{{__('ingaz.worker_name')}}:</strong> {{ $renew->worker_name }}</li>
                <li><strong>{{__('ingaz.worker_phone')}}:</strong> {{ $renew->worker_phone }}</li>
                <li><strong>{{__('ingaz.worker_address')}}:</strong> {{ $renew->worker_address }}</li>
                <li><strong>{{__('ingaz.nationality')}}:</strong> {{ $renew->nationality?->title ?? '-' }}</li>
                <li><strong> {{__('ingaz.worker_marital_status')}}:</strong> {{ $renew->worker_marital_status }}</li>
                <li><strong>  {{__('ingaz.worker_brought_from')}}:</strong> {{ $renew->worker_brought_from }}</li>
                <li><strong>  {{__('ingaz.worker_residence')}}:</strong> {{ $renew->worker_residence }}</li>
                <li><strong>  {{__('ingaz.worker_old_salary')}}:</strong> {{ $renew->worker_old_salary }}</li>
                <li><strong>   {{ __('ingaz.worker_new_salary') }}:</strong> {{ $renew->worker_new_salary }}</li>
                <li><strong>   {{ __('ingaz.passport_number') }}:</strong> {{ $renew->passport_number }}</li>
            </ul>

            <!-- بيانات القريب -->
            <h5 class="mt-4"><i class="fas fa-users"></i>     {{__('ingaz.relative_info')}}</h5>
            <ul>
                <li><strong>{{__('ingaz.name_of_relative')}}:</strong> {{ $renew->name_of_relative }}</li>
                <li><strong>{{__('ingaz.address_of_relative')}}:</strong> {{ $renew->address_of_relative }}</li>
                <li><strong>{{__('ingaz.phone_of_relative')}}:</strong> {{ $renew->phone_of_relative }}</li>
            </ul>

            <!-- الملفات المرفقة -->
            <h5 class="mt-4"><i class="fas fa-paperclip"></i> {{__('ingaz.attachments')}}  </h5>
            <div class="row">
                @php
                    $attachments = [
                        'sponsor_card' => __('ingaz.sponsor_card') ,
                        'first_page_of_the_worker_passport' => __('ingaz.first_page_of_the_worker_passport'),
                        'travel_tickets' =>__('ingaz.travel_tickets'),
                        'visa_printed_in_the_worker_passport' => __('ingaz.visa_printed_in_the_worker_passport'),
                        'exit_and_re_entry_visa' => __('ingaz.exit_and_re_entry_visa'),
                        'worker_information' => __('ingaz.worker_information'),
                        'image_of_bank_transfer' => __('ingaz.image_of_bank_transfer'),
                    ];
                @endphp

                @foreach ($attachments as $key => $label)
                    <div class="col-md-4 mb-3">
                        <strong>{{ $label }}</strong><br>
                        @if ($renew->$key)
                            <a href="{{showFile($renew->$key)}}" target="_blank" class="btn btn-sm btn-primary">
                                {{__('ingaz.view')}} / {{__('ingaz.download')}}
                            </a>
                        @else
                            <span class="text-muted">   {{__('ingaz.not_avalible')}}</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
