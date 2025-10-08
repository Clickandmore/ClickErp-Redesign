@extends('admin.layout.indexs.index')
@section('page-title')
    {{ __('ingaz.recruitment_request_maid') }} - #{{ $recruitmentRequest->id }}
@endsection

@section('content')
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ __('ingaz.view_title') }} - #{{ $recruitmentRequest->id }}</h3>
                <div class="card-tools">
                    <a href="{{ route('recruitment_requests.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> {{ __('ingaz.back_to_list') }}
                    </a>
                </div>
            </div>

            <div class="card-body">
                <!-- بيانات العميل (Client) -->
                <h5><i class="fas fa-user"></i> {{ __('ingaz.client_info') }}</h5>
                <ul>
                    <li><strong>{{ __('ingaz.client_name') }}:</strong> {{ $recruitmentRequest->client->name ?? '-' }}
                    </li>
                    <li><strong>{{ __('ingaz.client_phone') }}:</strong> {{ $recruitmentRequest->client->phone ?? '-' }}
                    </li>
                    <li><strong>{{ __('ingaz.client_email') }}:</strong> {{ $recruitmentRequest->client->email ?? '-' }}
                    </li>
                </ul>

                {{--            <!-- بيانات المساعدة (Maid) -->--}}
                {{--            <h5 class="mt-4"><i class="fas fa-user-tie"></i> {{ __('ingaz.maid_info') }}</h5>--}}
                {{--            <ul>--}}
                {{--                <li><strong>{{ __('ingaz.maid_name') }}:</strong> {{ $recruitmentRequest->maid->name ?? '-' }}</li>--}}
                {{--                <li><strong>{{ __('ingaz.maid_nationality') }}:</strong> {{ $recruitmentRequest->maid->nationality->name ?? '-' }}</li>--}}
                {{--                <li><strong>{{ __('ingaz.maid_status') }}:</strong> {{ $recruitmentRequest->maid->status ?? '-' }}</li>--}}
                {{--            </ul>--}}

                <!-- بيانات الطلب (Recruitment Request) -->
                <h5 class="mt-4"><i class="fas fa-briefcase"></i> {{ __('ingaz.recruitment_request_details') }}</h5>
                <ul>
                    <li><strong>{{ __('ingaz.nationality') }}:</strong> {{ $recruitmentRequest->nationality }}</li>
                    <li><strong>{{ __('ingaz.experience_years') }}:</strong> {{ $recruitmentRequest->experience_years }}
                    </li>
                    <li><strong>{{ __('ingaz.salary') }}:</strong> {{ $recruitmentRequest->salary }}</li>
                    <li><strong>{{ __('ingaz.notes') }}:</strong> {{ $recruitmentRequest->notes ?? '-' }}</li>
                    <li><strong>{{ __('ingaz.status') }}:</strong>
                        @if($recruitmentRequest->status == 'approved')
                            <span class="badge badge-success bg-success">{{ __('ingaz.status_approved') }}</span>
                        @else
                            <span class="badge badge-secondary bg-secondary">{{ __('ingaz.status_pending') }}</span>
                        @endif
                    </li>
                    {{--                <li><strong>{{ __('ingaz.request_type') }}:</strong> {{ $recruitmentRequest->request_type }}</li>--}}
                    <li><strong>{{ __('ingaz.visa_number') }}:</strong> {{ $recruitmentRequest->visa_number }}</li>
                    <li>
                        <strong>
                            {{ __('ingaz.social_status') }}:
                        </strong> {{\App\Enums\Office\MaritalStatusEnum::tryFrom($recruitmentRequest->social_status) ? \App\Enums\Office\MaritalStatusEnum::tryFrom($recruitmentRequest->social_status)->lang() :  $recruitmentRequest->social_status }}
                    </li>
                    <li><strong>{{ __('ingaz.age') }}:</strong> {{ $recruitmentRequest->age }}</li>
                    <li><strong>{{ __('ingaz.religion') }}:</strong> {{ $recruitmentRequest->religion->title ?? '-' }}
                    </li>
                    <li><strong>{{ __('ingaz.language') }}:</strong> {{ $recruitmentRequest->language }}</li>
                    <li><strong>{{ __('ingaz.worker_status') }}
                            :</strong> {{ $recruitmentRequest->worker_status == 'new_come' ?__("ingaz.new_come") : __("ingaz.have_experince") }}</li>
            </ul>
        </div>
    </div>
</div>
@endsection
