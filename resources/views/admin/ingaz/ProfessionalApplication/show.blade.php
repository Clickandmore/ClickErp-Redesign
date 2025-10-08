@extends('admin.layout.indexs.index')
@section('page-title')
    {{helperTrans("ingaz.professional_application")}}
@endsection
@section('content')
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">      {{__('ingaz.view_title')}}- #{{ $application->id }}</h3>
                <div class="card-tools">
                    <a href="{{ route('professional_application.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> {{__('ingaz.back_to_list')}}
                    </a>
                </div>
            </div>

            <div class="card-body">
                <!-- بيانات المستخدم -->
                <h5><i class="fas fa-user"></i> {{__('ingaz.user_info')}}</h5>
                <ul>
                    <li><strong>{{__('ingaz.user_name')}}:</strong> {{ $application->user->name ?? '-' }}</li>
                    <li><strong>  {{__('ingaz.user_phone')}}:</strong> {{ $application->user->phone ?? '-' }}</li>
                    <li><strong>{{__('ingaz.user_email')}} :</strong> {{ $application->user->email ?? '-' }}</li>
                </ul>

                <!-- بيانات الطلب -->
                <h5 class="mt-4"><i class="fas fa-briefcase"></i> {{__('ingaz.order_details')}}</h5>
                <ul>
                    <li><strong>{{__('ingaz.facility_name')}} :</strong> {{ $application->facility_name }}</li>
                    <li><strong>  {{__('ingaz.application_phone')}}:</strong> {{ $application->phone }}</li>
                    <li><strong>  {{__('ingaz.number_of_workers_required')}}
                            :</strong> {{ $application->number_of_workers_required }}</li>
                    <li><strong> {{__('ingaz.job_title')}} :</strong> {{ $application->jobtitle?->title ?? '-' }}</li>
                    <li><strong>  {{__('ingaz.visa_number')}} :</strong> {{ $application->visa_number }}</li>
                    <li><strong>   {{__('ingaz.other_requirement')}}
                            :</strong> {{ $application->other_requirement ?: 'لا يوجد' }}</li>
                    <li><strong> {{__('ingaz.status')}}:</strong>
                        @if($application->status == \App\Enums\ingaz\RequestsStatusEnum::Approved->value)
                            <span class="badge badge-success bg-success">   {{__('ingaz.approved')}}</span>
                        @elseif($application->status == \App\Enums\ingaz\RequestsStatusEnum::Rejected->value)
                            <span class="badge badge-danger bg-danger">   {{__('ingaz.rejected')}}</span>
                        @elseif($application->status == \App\Enums\ingaz\RequestsStatusEnum::Pending->value)
                            <span class="badge badge-secondary bg-secondary">   {{__('ingaz.pending')}}</span>
                        @endif
                    </li>
                </ul>
            </div>
        </div>
    </div>
@endsection
