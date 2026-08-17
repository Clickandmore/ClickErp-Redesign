@extends('admin.layout.indexs.index')

@section('page-title')
    {{ helperTrans('ingaz.view_details') }} #{{ $contact->id }}
@endsection

@section('style')
    <style>
        .contact-show-shell {
            display: grid;
            gap: 1.5rem;
        }

        .contact-show-card {
            border: 0;
            border-radius: 22px;
            box-shadow: 0 18px 50px rgba(15, 23, 42, 0.08);
        }

        .contact-show-header {
            background:
                radial-gradient(circle at top right, rgba(13, 110, 253, 0.16), transparent 34%),
                linear-gradient(135deg, #ffffff 0%, #f7fbff 100%);
        }

        .contact-show-title {
            font-size: 1.75rem;
            font-weight: 800;
            color: #152033;
        }

        .contact-avatar-lg {
            width: 64px;
            height: 64px;
            border-radius: 18px;
            background: linear-gradient(135deg, #dbeafe 0%, #eff6ff 100%);
            color: #1d4ed8;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            font-weight: 800;
            flex-shrink: 0;
        }

        .contact-detail-row {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid #eef2f7;
        }

        .contact-detail-row:last-child {
            border-bottom: 0;
        }

        .contact-detail-label {
            color: #667085;
            font-weight: 700;
            min-width: 140px;
        }

        .contact-detail-value {
            color: #101828;
            font-weight: 600;
            text-align: end;
            word-break: break-word;
        }

        .contact-message-box {
            color: #344054;
            line-height: 1.9;
            white-space: pre-wrap;
        }

        .status-badge {
            font-size: 0.85rem;
            font-weight: 700;
            padding: 0.65rem 0.9rem;
        }

        .status-contacted {
            background: #ecfdf3;
            color: #027a48;
        }

        .status-pending {
            background: #fff7ed;
            color: #b45309;
        }

        @media (max-width: 767.98px) {
            .contact-detail-row {
                display: block;
            }

            .contact-detail-value {
                text-align: start;
                margin-top: 0.35rem;
            }
        }
    </style>
@endsection

@section('content')
    @if (@AdminGuardHasPerm(\App\PermKeys::CONTACT_VIEW))
        @php
            $initial = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($contact->name, 0, 1));
            $createdAt = optional($contact->created_at);
            $updatedAt = optional($contact->updated_at);
        @endphp

        <div class="page-body">
            <div class="container-fluid">
                <div class="contact-show-shell">
                    <div>
                        <a href="{{ route('contact.index') }}" class="btn btn-outline-secondary">
                            <i class="fa fa-arrow-left ms-1"></i>
                            {{ helperTrans('ingaz.back_to_list') }}
                        </a>
                    </div>

                    <div class="card contact-show-card contact-show-header">
                        <div class="card-body p-4 p-lg-5">
                            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="contact-avatar-lg">{{ $initial }}</div>
                                    <div>
                                        <div class="contact-show-title">{{ $contact->name ?: '-' }}</div>
                                        <div class="text-muted">#{{ $contact->id }}</div>
                                    </div>
                                </div>
                                <div>
                                    @if ((int) $contact->is_contacted === 1)
                                        <span class="badge rounded-pill status-badge status-contacted">
                                            <i class="fa fa-check-circle ms-1"></i>
                                            {{ helperTrans('ingaz.contacted') }}
                                        </span>
                                    @else
                                        <span class="badge rounded-pill status-badge status-pending">
                                            <i class="fa fa-clock ms-1"></i>
                                            {{ helperTrans('ingaz.pending_contact') }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4">
                        <div class="col-lg-5">
                            <div class="card contact-show-card h-100">
                                <div class="card-body p-4">
                                    <h5 class="mb-3">{{ helperTrans('ingaz.sender') }}</h5>

                                    <div class="contact-detail-row">
                                        <div class="contact-detail-label">{{ helperTrans('ingaz.name') }}</div>
                                        <div class="contact-detail-value">{{ $contact->name ?: '-' }}</div>
                                    </div>
                                    <div class="contact-detail-row">
                                        <div class="contact-detail-label">{{ helperTrans('ingaz.phone') }}</div>
                                        <div class="contact-detail-value">
                                            {!! $contact->phone ? getPhoneWithTag($contact->phone) : '<span class="text-muted">-</span>' !!}
                                        </div>
                                    </div>
                                    <div class="contact-detail-row">
                                        <div class="contact-detail-label">{{ helperTrans('ingaz.email') }}</div>
                                        <div class="contact-detail-value">
                                            {!! $contact->email ? getEmailWithTag($contact->email) : '<span class="text-muted">-</span>' !!}
                                        </div>
                                    </div>
                                    <div class="contact-detail-row">
                                        <div class="contact-detail-label">{{ helperTrans('ingaz.sent_at') }}</div>
                                        <div class="contact-detail-value">
                                            @if ($contact->created_at)
                                                {{ $createdAt->format('Y-m-d h:i A') }}
                                            @else
                                                -
                                            @endif
                                        </div>
                                    </div>
                                    <div class="contact-detail-row">
                                        <div class="contact-detail-label">{{ helperTrans('ingaz.updated_at') }}</div>
                                        <div class="contact-detail-value">
                                            @if ($contact->updated_at)
                                                {{ $updatedAt->format('Y-m-d h:i A') }}
                                            @else
                                                -
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-7">
                            <div class="card contact-show-card h-100">
                                <div class="card-body p-4">
                                    <h5 class="mb-3">{{ helperTrans('ingaz.request_type') }}</h5>

                                    <div class="contact-detail-row">
                                        <div class="contact-detail-label">{{ helperTrans('ingaz.request_type') }}</div>
                                        <div class="contact-detail-value">
                                            <span class="badge rounded-pill bg-light text-dark px-3 py-2 border">
                                                {{ helperTrans('ingaz.contact_us') }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="contact-detail-row">
                                        <div class="contact-detail-label">{{ helperTrans('ingaz.subject') }}</div>
                                        <div class="contact-detail-value">{{ $contact->subject ?: helperTrans('ingaz.no_subject') }}</div>
                                    </div>
                                    <div class="pt-3">
                                        <div class="contact-detail-label mb-2">{{ helperTrans('ingaz.message') }}</div>
                                        <div class="contact-message-box">
                                            {{ $contact->message ?: '-' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        @include('components.no_permissions')
    @endif
@endsection
