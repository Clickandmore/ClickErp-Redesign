@extends('admin.layout.indexs.index')
@section('style')
    <style>
        .recruitment-request-show .summary-card {
            border: 1px solid #e8ecf4;
            border-radius: 16px;
            background: #fff;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.05);
        }

        .recruitment-request-show .section-card {
            border: 1px solid #e8ecf4;
            border-radius: 16px;
            background: #fff;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
        }

        .recruitment-request-show .section-title {
            font-size: 1rem;
            font-weight: 700;
            color: #1f2b46;
            margin-bottom: 14px;
        }

        .recruitment-request-show .summary-item {
            border: 1px solid #edf1f7;
            background: #f8fafd;
            border-radius: 12px;
            padding: 10px 12px;
            height: 100%;
        }

        .recruitment-request-show .summary-item .label {
            display: block;
            font-size: 0.82rem;
            color: #6b778f;
            margin-bottom: 4px;
        }

        .recruitment-request-show .summary-item .value {
            font-size: 1rem;
            font-weight: 700;
            color: #1f2b46;
            word-break: break-word;
        }

        .recruitment-request-show .info-list {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .recruitment-request-show .info-item {
            border: 1px solid #edf1f7;
            border-radius: 12px;
            padding: 10px 12px;
            background: #fff;
        }

        .recruitment-request-show .info-item .label {
            display: block;
            font-size: 0.82rem;
            color: #6b778f;
            margin-bottom: 4px;
        }

        .recruitment-request-show .info-item .value {
            font-size: 0.95rem;
            font-weight: 600;
            color: #1f2b46;
            word-break: break-word;
        }

        .recruitment-request-show .notes-box {
            border: 1px solid #edf1f7;
            background: #f8fafd;
            border-radius: 12px;
            padding: 12px;
            color: #1f2b46;
            min-height: 84px;
        }

        @media (max-width: 991.98px) {
            .recruitment-request-show .info-list {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection
@section('page-title')
    {{ __('ingaz.recruitment_request_maid') }} - #{{ $recruitmentRequest->id }}
@endsection

@section('content')
    @php
        $statusText = \App\Enums\ingaz\RequestsStatusEnum::tryFrom($recruitmentRequest->status)?->lang() ?? ($recruitmentRequest->status ?? '-');
        $statusClass = match ($recruitmentRequest->status) {
            \App\Enums\ingaz\RequestsStatusEnum::Approved->value => 'bg-success',
            \App\Enums\ingaz\RequestsStatusEnum::Rejected->value => 'bg-danger',
            default => 'bg-warning text-dark',
        };
        $socialStatus = \App\Enums\Office\MaritalStatusEnum::tryFrom($recruitmentRequest->social_status)?->lang() ?? ($recruitmentRequest->social_status ?? '-');
        $workerStatus = $recruitmentRequest->worker_status == 'new_come' ? __('ingaz.new_come') : __('ingaz.have_experince');
    @endphp

    <div class="page-body recruitment-request-show">
        <div class="container-fluid">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                <h4 class="mb-0">{{ __('ingaz.view_title') }} - #{{ $recruitmentRequest->id }}</h4>
                <a href="{{ route('recruitment_requests.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> {{ __('ingaz.back_to_list') }}
                </a>
            </div>

            <div class="card summary-card mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3 col-6">
                            <div class="summary-item">
                                <span class="label">رقم الطلب</span>
                                <span class="value">#{{ $recruitmentRequest->id }}</span>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="summary-item">
                                <span class="label">{{ __('ingaz.status') }}</span>
                                <span class="value">
                                    <span class="badge {{ $statusClass }}">{{ $statusText }}</span>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="summary-item">
                                <span class="label">تاريخ الطلب</span>
                                <span class="value">{{ $recruitmentRequest->created_at?->format('Y-m-d') ?? '-' }}</span>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="summary-item">
                                <span class="label">وقت الطلب</span>
                                <span class="value">{{ $recruitmentRequest->created_at?->format('h:i A') ?? '-' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="card section-card h-100">
                        <div class="card-body">
                            <h5 class="section-title"><i class="fas fa-user me-1"></i> {{ __('ingaz.client_info') }}</h5>
                            <div class="info-list">
                                <div class="info-item">
                                    <span class="label">{{ __('ingaz.client_name') }}</span>
                                    <span class="value">{{ $recruitmentRequest->client->name ?? '-' }}</span>
                                </div>
                                <div class="info-item">
                                    <span class="label">{{ __('ingaz.client_phone') }}</span>
                                    <span class="value">{{ $recruitmentRequest->client->phone ?? '-' }}</span>
                                </div>
                                <div class="info-item">
                                    <span class="label">{{ __('ingaz.client_email') }}</span>
                                    <span class="value">{{ $recruitmentRequest->client->email ?? '-' }}</span>
                                </div>
                                <div class="info-item">
                                    <span class="label">منذ</span>
                                    <span class="value">{{ $recruitmentRequest->created_at?->diffForHumans() ?? '-' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card section-card h-100">
                        <div class="card-body">
                            <h5 class="section-title"><i class="fas fa-briefcase me-1"></i> {{ __('ingaz.recruitment_request_details') }}</h5>
                            <div class="info-list mb-3">
                                <div class="info-item">
                                    <span class="label">{{ __('ingaz.job_title') }}</span>
                                    <span class="value">{{ $recruitmentRequest->job_title ?? '-' }}</span>
                                </div>
                                <div class="info-item">
                                    <span class="label">{{ __('ingaz.nationality') }}</span>
                                    <span class="value">{{ $recruitmentRequest->nationality ?? '-' }}</span>
                                </div>
                                <div class="info-item">
                                    <span class="label">{{ __('ingaz.experience_years') }}</span>
                                    <span class="value">{{ $recruitmentRequest->experience_years ?? '-' }}</span>
                                </div>
                                <div class="info-item">
                                    <span class="label">{{ __('ingaz.salary') }}</span>
                                    <span class="value">{{ $recruitmentRequest->salary ?? '-' }}</span>
                                </div>
                                <div class="info-item">
                                    <span class="label">{{ __('ingaz.visa_number') }}</span>
                                    <span class="value">{{ $recruitmentRequest->visa_number ?? '-' }}</span>
                                </div>
                                <div class="info-item">
                                    <span class="label">{{ __('ingaz.social_status') }}</span>
                                    <span class="value">{{ $socialStatus }}</span>
                                </div>
                                <div class="info-item">
                                    <span class="label">{{ __('ingaz.age') }}</span>
                                    <span class="value">{{ $recruitmentRequest->age ?? '-' }}</span>
                                </div>
                                <div class="info-item">
                                    <span class="label">{{ __('ingaz.religion') }}</span>
                                    <span class="value">{{ $recruitmentRequest->religion->title ?? '-' }}</span>
                                </div>
                                <div class="info-item">
                                    <span class="label">{{ __('ingaz.language') }}</span>
                                    <span class="value">{{ $recruitmentRequest->language ?? '-' }}</span>
                                </div>
                                <div class="info-item">
                                    <span class="label">{{ __('ingaz.worker_status') }}</span>
                                    <span class="value">{{ $workerStatus }}</span>
                                </div>
                            </div>

                            <div>
                                <span class="label d-block mb-1">{{ __('ingaz.notes') }}</span>
                                <div class="notes-box">{{ $recruitmentRequest->notes ?? '-' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
