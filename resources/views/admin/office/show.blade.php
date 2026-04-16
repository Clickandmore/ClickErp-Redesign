@extends('admin.layout.indexs.index')
@section("style")
    <style>
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.25rem;
        }

        .detail-item {
            min-height: 72px;
        }

        .detail-label {
            font-size: .85rem;
            color: #6c757d;
        }

        .detail-value {
            font-weight: 600;
            font-size: 1rem;
            color: #212529;
        }

        .attachment-list li {
            margin-bottom: .5rem;
        }

        .attachment-list i {
            color: #066f63;
        }

        /* .card-title-head{
            color: #4dca88;
            font-weight: bold;
        } */

        .btn-head-primary,.btn-head-success,.btn-head-secondary{
            color: white;
            border-color: white;
            transition: 0.3s ease-in-out;
        }

        .btn-head-primary:hover{
            background-color: #1e76bf;
            border-color: #1e76bf;
        }
        .btn-head-success:hover{
            background-color: #4dca88;
            border-color: #4dca88;
        }
        .btn-head-secondary:hover{
            background-color: #6c757d;
            border-color: #6c757d;
        }

        .timeline-table th {
            background-color: #f8f9fa;
        }

        .timeline-table th,
        .timeline-table td {
            vertical-align: middle;
        }
    </style>
@endsection

@section('page-title')
    {{ helperTrans("ingaz.show") }}
@endsection

@section('content')

    @if (@AdminGuardHasAnyPerm([\App\PermKeys::REC_OFFICES_VIEW, \App\PermKeys::OFFICE_VIEW, \App\PermKeys::JOB_TITLES_VIEW]))
        @php
            $locales = config('translatable.locales', []);
            $jobTypeNames = $office->jobTypes->map(function ($jobType) {
                return $jobType->title;
            })->filter()->implode('، ');
            $statusEnum = \App\Enums\Office\OfficesStatusEnum::tryFrom($office->status);
            $managerAccount = optional($office->officeManager)->admin;
            $attachment = $office->attachment;
            $officeFile = $office->file;
            $attachmentDate = $attachment && $attachment->created_at ? $attachment->created_at->format('Y-m-d') : null;
            $officeFileUpdatedAt = $officeFile && $officeFile->updated_at ? $officeFile->updated_at->format('Y-m-d H:i') : null;
            $eventLabels = [
                'created' => helperTrans('ingaz.created'),
                'updated' => helperTrans('ingaz.updated'),
                'deleted' => helperTrans('ingaz.deleted'),
            ];
        @endphp

        <div class="container-fluid">
            <div class="row">
                <div class="col-12">

                    {{-- تفاصيل المكتب --}}
                    <div class="card shadow-sm mb-4">
                        <div class="card-header d-flex bg-primary text-white justify-content-between align-items-center flex-wrap gap-2">
                            <h5 class="mb-0 card-title-head">{{ helperTrans('ingaz.office_details') }}</h5>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="{{ route('offices.edit', $office->id) }}" class="btn btn-sm btn-outline btn-head-primary">
                                    <i class="fa fa-edit"></i> {{ helperTrans('ingaz.edit_office') }}
                                </a>
                                <a href="{{ route('offices-file.create', ['office_id' => $office->id]) }}" class="btn btn-sm btn-outline btn-head-success">
                                    <i class="fa fa-plus"></i> {{ helperTrans('ingaz.add_new_file') }}
                                </a>
                                @if($officeFile)
                                    <a href="{{ route('offices-file.edit', $officeFile->id) }}" class="btn btn-sm btn-outline btn-head-secondary">
                                        <i class="fa fa-eye"></i> {{ helperTrans('ingaz.view_current_file') }}
                                    </a>
                                @endif
                            </div>
                        </div>

                        <div class="card-body">
                            <div class="detail-grid">
                                @foreach($locales as $locale)
                                    <div class="detail-item">
                                        <span class="detail-label">{{ helperTrans('ingaz.title') }} ({{ helperTrans('ingaz.'.$locale) }})</span>
                                        <span class="detail-value">{{ optional($office->translate($locale))->title ?? '-' }}</span>
                                    </div>
                                @endforeach
                                <div class="detail-item">
                                    <span class="detail-label">{{ helperTrans('ingaz.status') }}</span>
                                    <span class="detail-value">{{ $statusEnum?->lang() ?? '-' }}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">{{ helperTrans('ingaz.nationality') }}</span>
                                    <span class="detail-value">{{ optional($office->nationality)->title ?? '-' }}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">{{ helperTrans('ingaz.phone_office') }}</span>
                                    <span class="detail-value">{{ $office->phone_office ?? '-' }}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">{{ helperTrans('ingaz.license_number') }}</span>
                                    <span class="detail-value">{{ $office->license_number ?? '-' }}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">{{ helperTrans('ingaz.street') }}</span>
                                    <span class="detail-value">{{ $office->street ?? '-' }}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">{{ helperTrans('ingaz.office_type') }}</span>
                                    <span class="detail-value">{{ $jobTypeNames ?: '-' }}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">{{ helperTrans('ingaz.linked_cvs') }}</span>
                                    <span class="detail-value">{{ $office->maids_count ?? 0 }}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">{{ helperTrans('ingaz.bank_address') }}</span>
                                    <span class="detail-value">{{ $office->bank_address ?? '-' }}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">{{ helperTrans('ingaz.bank_name') }}</span>
                                    <span class="detail-value">{{ $office->bank_name ?? '-' }}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">{{ helperTrans('ingaz.bank_account_name') }}</span>
                                    <span class="detail-value">{{ $office->bank_account_name ?? '-' }}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">{{ helperTrans('ingaz.bank_account') }}</span>
                                    <span class="detail-value">{{ $office->bank_account ?? '-' }}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">{{ helperTrans('ingaz.bank_swift_code') }}</span>
                                    <span class="detail-value">{{ $office->bank_swift_code ?? '-' }}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">{{ helperTrans('ingaz.bank_iban') }}</span>
                                    <span class="detail-value">{{ $office->bank_iban ?? '-' }}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">{{ helperTrans('ingaz.cost_of_dollar') }}</span>
                                    <span class="detail-value">{{ $office->cost_of_dollar ?? '-' }}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">{{ helperTrans('ingaz.cost_of_other') }}</span>
                                    <span class="detail-value">{{ $office->cost_of_other ?? '-' }}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">{{ helperTrans('ingaz.created_at') }}</span>
                                    <span class="detail-value">{{ optional($office->created_at)->format('Y-m-d H:i') ?? '-' }}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">{{ helperTrans('ingaz.updated_at') }}</span>
                                    <span class="detail-value">{{ optional($office->updated_at)->format('Y-m-d H:i') ?? '-' }}</span>
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="mb-3">
                                <h6 class="fw-bold mb-3"><i class="fa fa-paperclip"></i> {{ helperTrans('ingaz.attachments') }}</h6>
                                <ul class="list-unstyled attachment-list mb-0">
                                    @if($attachment)
                                        <li>
                                            <i class="fa fa-file"></i>
                                            <a href="{{ asset('storage/' . $attachment->file) }}" target="_blank">{{ $attachment->file_name }}</a>
                                            @if($attachmentDate)
                                                <span class="text-muted">({{ $attachmentDate }})</span>
                                            @endif
                                        </li>
                                    @else
                                        <li class="text-muted">{{ helperTrans('ingaz.no_attachments') }}</li>
                                    @endif
                                </ul>
                            </div>

                            @if($officeFile)
                                <div class="border rounded p-3 bg-light">
                                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                        <div>
                                            <span class="detail-label d-block">{{ helperTrans('ingaz.current_file') }}</span>
                                            <span class="detail-value d-block">{{ $officeFile->office_file_name ?? '-' }}</span>
                                            <span class="text-muted small">{{ helperTrans('ingaz.job_title_for_file') }}: {{ optional($officeFile->jobTitle)->title ?? '-' }}</span>
                                        </div>
                                        @if($officeFileUpdatedAt)
                                            <span class="text-muted small">{{ helperTrans('ingaz.updated_at') }}: {{ $officeFileUpdatedAt }}</span>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- بيانات المدير --}}
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0 card-title-head">{{ helperTrans('ingaz.manager_info') }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <span class="detail-label">{{ helperTrans('ingaz.manager_name') }}</span>
                                    <span class="detail-value">{{ $office->manager_name ?? '-' }}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">{{ helperTrans('ingaz.manager_phone') }}</span>
                                    <span class="detail-value">{{ $office->manager_phone ?? '-' }}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">{{ helperTrans('ingaz.manager_email') }}</span>
                                    <span class="detail-value">{{ $office->manager_email ?? '-' }}</span>
                                </div>
                            </div>

                            <hr class="my-4">

                            <h6 class="fw-bold mb-3">{{ helperTrans('ingaz.manager_account_info') }}</h6>
                            @if($managerAccount)
                                <div class="detail-grid">
                                    <div class="detail-item">
                                        <span class="detail-label">{{ helperTrans('ingaz.name') }}</span>
                                        <span class="detail-value">{{ $managerAccount->name ?? '-' }}</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">{{ helperTrans('ingaz.email') }}</span>
                                        <span class="detail-value">{{ $managerAccount->email ?? '-' }}</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">{{ helperTrans('ingaz.phone') }}</span>
                                        <span class="detail-value">{{ $managerAccount->phone ?? '-' }}</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">{{ helperTrans('ingaz.language') }}</span>
                                        <span class="detail-value">{{ $managerAccount->lang ?? '-' }}</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">{{ helperTrans('ingaz.created_in') }}</span>
                                        <span class="detail-value">{{ optional($managerAccount->created_at)->format('Y-m-d H:i') ?? '-' }}</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">{{ helperTrans('ingaz.updated_at') }}</span>
                                        <span class="detail-value">{{ optional($managerAccount->updated_at)->format('Y-m-d H:i') ?? '-' }}</span>
                                    </div>
                                </div>
                            @else
                                <p class="text-muted mb-0">{{ helperTrans('ingaz.no_manager_account') }}</p>
                            @endif
                        </div>
                    </div>

                    {{-- سجل النشاطات --}}
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0 card-title-head">{{ helperTrans('ingaz.activity_log') }}</h5>
                        </div>
                        <div class="card-body">
                            @if($activities->isEmpty())
                                <p class="text-muted mb-0">{{ helperTrans('ingaz.no_activities') }}</p>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover timeline-table">
                                        <thead>
                                        <tr>
                                            <th>{{ helperTrans('ingaz.event') }}</th>
                                            <th>{{ helperTrans('ingaz.description') }}</th>
                                            <th>{{ helperTrans('ingaz.date') }}</th>
                                            <th>{{ helperTrans('ingaz.time') }}</th>
                                            <th>{{ helperTrans('ingaz.by') }}</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($activities as $activity)
                                            <tr>
                                                <td>{{ $eventLabels[$activity->event] ?? ($activity->event ? ucfirst($activity->event) : '-') }}</td>
                                                <td>{{ $activity->description ?? '-' }}</td>
                                                <td>{{ optional($activity->created_at)->format('Y-m-d') ?? '-' }}</td>
                                                <td>{{ optional($activity->created_at)->format('H:i') ?? '-' }}</td>
                                                <td>{{ optional($activity->causer)->name ?? optional($activity->causer)->email ?? '-' }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>

                </div>
            </div>
        </div>

    @else
        @include('components.no_permissions')
    @endif

@endsection

@section('js')
@endsection
