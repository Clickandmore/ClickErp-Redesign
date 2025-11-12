@extends('admin.layout.indexs.index')

@section('page-title')
    {{ helperTrans('ingaz.professional_application') }}
@endsection

@section("style")
    <style>
        .order-info-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .order-info-header {
            background: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid #dee2e6;
            border-radius: 8px 8px 0 0;
        }
        .order-info-body {
            padding: 20px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f1f1f1;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #495057;
            min-width: 150px;
        }
        .info-value {
            color: #212529;
            text-align: right;
        }
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-new { background: #fff3cd; color: #856404; }
        .status-paid { background: #d1ecf1; color: #0c5460; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-started { background: #cce5ff; color: #004085; }
        .status-done { background: #d1e7dd; color: #0f5132; }
        .status-refused { background: #f8d7da; color: #721c24; }
        .status-under_process { background: #e2e3e5; color: #383d41; }
        .status-contracted { background: #d1e7dd; color: #0f5132; }
        .status-contracted_without_contract { background: #fff3cd; color: #856404; }

        .maid-photo {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .file-item {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        .progress-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
@endsection

@section('content')

    <!-- Back Button -->
    <div class="mb-3 mt-3">
        <a href="{{ route('professional_application.index') }}" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> {{ __('ingaz.back_to_list') }}
        </a>
    </div>

    <div class="row">
        {{-- User Information --}}
        <div class="col-md-6">
            <div class="order-info-card">
                <div class="order-info-header">
                    <h5><i class="fas fa-user"></i> {{ helperTrans('ingaz.user_info') }}</h5>
                </div>
                <div class="order-info-body">
                    <div class="info-row">
                        <span class="info-label">{{ __('ingaz.user_name') }}:</span>
                        <span class="info-value">{{ $application->user->name ?? '-' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"> {{ __('ingaz.user_phone') }}:</span>
                        <span class="info-value">{{ $application->user->phone ?? '-' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">{{ __('ingaz.user_email') }} :</span>
                        <span class="info-value">{{ $application->user->email ?? '-' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Information -->
        <div class="col-md-6">
            <div class="order-info-card">
                <div class="order-info-header">
                    <h5><i class="fas fa-briefcase"></i> {{ __('ingaz.order_details') }}</h5>
                </div>
                <div class="order-info-body">
                    <div class="info-row">
                        <span class="info-label">{{ __('ingaz.facility_name') }} :</span>
                        <span class="info-value">{{ $application->facility_name }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">{{ __('ingaz.application_phone') }}:</span>
                        <span class="info-value">{{ $application->phone }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">{{ __('ingaz.number_of_workers_required') }} :</span>
                        <span class="info-value">{{ $application->number_of_workers_required }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">{{ __('ingaz.job_title') }} :</span>
                        <span class="info-value">{{ $application->jobtitle?->title ?? '-' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"> {{ __('ingaz.visa_number') }} :</span>
                        <span class="info-value"> {{ $application->visa_number }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">{{ __('ingaz.other_requirement') }} :</span>
                        <span class="info-value">{{ $application->other_requirement ?: 'لا يوجد' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">{{ __('ingaz.status') }}:</span>
                        @if ($application->status == \App\Enums\ingaz\RequestsStatusEnum::Approved->value)
                            <span class="badge badge-success bg-success"> {{ __('ingaz.approved') }}</span>
                        @elseif($application->status == \App\Enums\ingaz\RequestsStatusEnum::Rejected->value)
                            <span class="badge badge-danger bg-danger"> {{ __('ingaz.rejected') }}</span>
                        @elseif($application->status == \App\Enums\ingaz\RequestsStatusEnum::Pending->value)
                            <span class="badge badge-secondary bg-secondary"> {{ __('ingaz.pending') }}</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
