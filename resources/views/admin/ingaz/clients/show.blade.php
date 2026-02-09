@extends('admin.layout.indexs.index')

@section('style')
<style>
    .client-hero {
        border-radius: 18px;
        border: 1px solid #eef2f6;
        box-shadow: 0 6px 16px rgba(15, 34, 58, 0.05);
    }

    .client-status-badge {
        border-radius: 12px;
        padding: 0.35rem 0.9rem;
        font-weight: 600;
        font-size: 0.85rem;
    }

    .client-card {
        border-radius: 18px;
        border: 1px solid #eef2f6;
        box-shadow: 0 10px 20px rgba(15, 34, 58, 0.05);
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 1.5rem;
    }

    .info-item .label {
        font-size: 0.8rem;
        font-weight: 600;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    .info-item .value {
        font-size: 0.95rem;
        font-weight: 600;
        color: #1e293b;
    }

    .stats-pill {
        border-radius: 15px;
        padding: 1.5rem;
        border: 1px solid rgba(148, 163, 184, 0.18);
        background: linear-gradient(135deg, rgba(15, 118, 110, 0.08), #fff);
        height: 100%;
    }

    .stats-icon {
        width: 44px;
        height: 44px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
    }

    .timeline {
        position: relative;
        padding-inline-start: 0;
        margin: 0;
        list-style: none;
    }

    .timeline::before {
        content: '';
        position: absolute;
        inset-inline-start: 15px;
        top: 6px;
        bottom: 6px;
        width: 2px;
        background: linear-gradient(180deg, rgba(30, 64, 175, 0.25), rgba(14, 165, 233, 0.25));
    }

    .timeline-item {
        position: relative;
        padding-inline-start: 2.5rem;
        margin-bottom: 1.75rem;
    }

    .timeline-item:last-child {
        margin-bottom: 0;
    }

    .timeline-item::before {
        content: '';
        position: absolute;
        inset-inline-start: 7px;
        top: 4px;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background: #fff;
        border: 3px solid #2563eb;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
    }

    .data-badge {
        border-radius: 999px;
        background: rgba(15, 23, 42, 0.05);
        padding: 0.45rem 0.85rem;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .table-clean th {
        background: #f8fafc;
        border: 0;
        text-transform: uppercase;
        font-size: 0.75rem;
        color: #475569;
        letter-spacing: 0.08em;
    }

    .table-clean td {
        vertical-align: middle;
        border-top-color: rgba(148, 163, 184, 0.15);
    }

    .card-section-title {
        font-size: 1.05rem;
        font-weight: 700;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    @media (max-width: 767.98px) {
        .timeline::before {
            inset-inline-start: 12px;
        }

        .timeline-item {
            padding-inline-start: 2.35rem;
        }
    }
</style>
@endsection

@section('page-title')
    {{ helperTrans('ingaz.show_client') }}
@endsection

@section('content')
@if (@AdminGuardHasPerm(\App\PermKeys::CLIENTS_SHOW))
    <div class="container-fluid py-3 py-lg-4">
        <div class="card client-hero mb-4">
            <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex flex-column gap-2">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <h2 class="mb-0 fw-bold">{{ $client->name ?? helperTrans('ingaz.not_specified') }}</h2>
                        <span class="client-status-badge {{ $client->is_blocked ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' }}">
                            <i class="fas {{ $client->is_blocked ? 'fa-user-slash' : 'fa-user-check' }} me-1"></i>
                            {{ $client->is_blocked ? helperTrans('ingaz.blocked') : helperTrans('ingaz.active') }}
                        </span>
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-3 text-muted small">
                        <span><i class="fas fa-id-card me-1"></i>{{ $client->national_num ?? helperTrans('ingaz.not_specified') }}</span>
                        <span><i class="fas fa-flag me-1"></i>{{ $client->nationality->title ?? helperTrans('ingaz.not_specified') }}</span>
                        <span><i class="fas fa-calendar-plus me-1"></i>{{ $client->created_at?->format('Y-m-d') ?? '-' }}</span>
                        @if($lastInteractionAt)
                            <span><i class="fas fa-history me-1"></i>{{ helperTrans('ingaz.last_interaction') }}: {{ $lastInteractionAt->diffForHumans() }}</span>
                        @endif
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('clients.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-right {{ app()->getLocale() === 'ar' ? '' : 'me-1' }} {{ app()->getLocale() === 'ar' ? 'ms-1' : '' }}"></i>
                        {{ helperTrans('ingaz.clients_list') }}
                    </a>
                    @if (@AdminGuardHasPerm(\App\PermKeys::CLIENTS_EDIT))
                        <a href="{{ route('clients.edit', $client->id) }}" class="btn btn-primary">
                            <i class="fas fa-edit me-1"></i>{{ helperTrans('ingaz.edit_client') }}
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <div class="row g-3 g-xl-4 mb-4">
            <div class="col-xxl-2 col-lg-4 col-md-6">
                <div class="stats-pill h-100">
                    <div class="d-flex align-items-center gap-3">
                        <span class="stats-icon bg-primary-subtle text-primary">
                            <i class="fas fa-clipboard-list"></i>
                        </span>
                        <div>
                            <div class="text-muted small fw-semibold">{{ helperTrans('ingaz.total_orders_count') }}</div>
                            <div class="fs-4 fw-bold">{{ number_format($stats['total_orders']) }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xxl-2 col-lg-4 col-md-6">
                <div class="stats-pill h-100">
                    <div class="d-flex align-items-center gap-3">
                        <span class="stats-icon bg-warning-subtle text-warning">
                            <i class="fas fa-tasks"></i>
                        </span>
                        <div>
                            <div class="text-muted small fw-semibold">{{ helperTrans('ingaz.active_orders_count') }}</div>
                            <div class="fs-4 fw-bold">{{ number_format($stats['active_orders']) }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xxl-2 col-lg-4 col-md-6">
                <div class="stats-pill h-100">
                    <div class="d-flex align-items-center gap-3">
                        <span class="stats-icon bg-success-subtle text-success">
                            <i class="fas fa-check-circle"></i>
                        </span>
                        <div>
                            <div class="text-muted small fw-semibold">{{ helperTrans('ingaz.completed_orders_count') }}</div>
                            <div class="fs-4 fw-bold">{{ number_format($stats['completed_orders']) }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xxl-2 col-lg-4 col-md-6">
                <div class="stats-pill h-100">
                    <div class="d-flex align-items-center gap-3">
                        <span class="stats-icon bg-info-subtle text-info">
                            <i class="fas fa-file-contract"></i>
                        </span>
                        <div>
                            <div class="text-muted small fw-semibold">{{ helperTrans('ingaz.rental_contracts_count') }}</div>
                            <div class="fs-4 fw-bold">{{ number_format($stats['rental_contracts']) }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xxl-2 col-lg-4 col-md-6">
                <div class="stats-pill h-100">
                    <div class="d-flex align-items-center gap-3">
                        <span class="stats-icon bg-secondary-subtle text-secondary">
                            <i class="fas fa-exchange-alt"></i>
                        </span>
                        <div>
                            <div class="text-muted small fw-semibold">{{ helperTrans('ingaz.transfer_requests_count') }}</div>
                            <div class="fs-4 fw-bold">{{ number_format($stats['transfer_requests']) }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xxl-2 col-lg-4 col-md-6">
                <div class="stats-pill h-100">
                    <div class="d-flex align-items-center gap-3">
                        <span class="stats-icon bg-danger-subtle text-danger">
                            <i class="fas fa-bell"></i>
                        </span>
                        <div>
                            <div class="text-muted small fw-semibold">{{ helperTrans('ingaz.complaints_count') }}</div>
                            <div class="fs-4 fw-bold">{{ number_format($stats['complaints']) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 g-xl-4">
            <div class="col-xl-8 d-flex flex-column gap-3 gap-xl-4">
                <div class="card client-card">
                    <div class="card-body">
                        <div class="card-section-title mb-3">
                            <i class="fas fa-user-circle text-primary"></i>
                            {{ helperTrans('ingaz.client_information') }}
                        </div>
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="label">{{ helperTrans('ingaz.client_name') }}</div>
                                <div class="value">{{ $client->name ?? helperTrans('ingaz.not_specified') }}</div>
                            </div>
                            <div class="info-item">
                                <div class="label">{{ helperTrans('ingaz.client_phone') }}</div>
                                <div class="value">
                                    @if($client->phone)
                                        <a href="tel:{{ $client->phone_code . $client->phone }}" class="text-decoration-none">
                                            <i class="fas fa-phone-alt me-1 text-success"></i>{{ $client->phone_code . $client->phone }}
                                        </a>
                                    @else
                                        {{ helperTrans('ingaz.not_specified') }}
                                    @endif
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="label">{{ helperTrans('ingaz.email') }}</div>
                                <div class="value">
                                    @if($client->email)
                                        <a href="mailto:{{ $client->email }}" class="text-decoration-none">
                                            <i class="fas fa-envelope me-1 text-primary"></i>{{ $client->email }}
                                        </a>
                                    @else
                                        {{ helperTrans('ingaz.not_specified') }}
                                    @endif
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="label">{{ helperTrans('ingaz.phone') }} 2</div>
                                <div class="value">{{ $client->phone2 ?? helperTrans('ingaz.not_specified') }}</div>
                            </div>
                            <div class="info-item">
                                <div class="label">{{ helperTrans('ingaz.nationality_id') }}</div>
                                <div class="value">{{ $client->nationality->title ?? helperTrans('ingaz.not_specified') }}</div>
                            </div>
                            <div class="info-item">
                                <div class="label">{{ helperTrans('ingaz.city') }}</div>
                                <div class="value">{{ $client->city ?? helperTrans('ingaz.not_specified') }}</div>
                            </div>
                            <div class="info-item">
                                <div class="label">{{ helperTrans('ingaz.birth_date') }}</div>
                                <div class="value">{{ $client->birthdate ? \Carbon\Carbon::parse($client->birthdate)->format('Y-m-d') : helperTrans('ingaz.not_specified') }}</div>
                            </div>
                            <div class="info-item">
                                <div class="label">{{ helperTrans('ingaz.family_num') }}</div>
                                <div class="value">{{ $client->family_num ?? helperTrans('ingaz.not_specified') }}</div>
                            </div>
                            <div class="info-item">
                                <div class="label">{{ helperTrans('ingaz.rooms_num') }}</div>
                                <div class="value">{{ $client->rooms_num ?? helperTrans('ingaz.not_specified') }}</div>
                            </div>
                            <div class="info-item">
                                <div class="label">{{ helperTrans('ingaz.children') }}</div>
                                <div class="value">{{ $client->children ?? helperTrans('ingaz.not_specified') }}</div>
                            </div>
                            <div class="info-item">
                                <div class="label">{{ helperTrans('ingaz.job') }}</div>
                                <div class="value">{{ $client->job ?? helperTrans('ingaz.not_specified') }}</div>
                            </div>
                            <div class="info-item">
                                <div class="label">{{ helperTrans('ingaz.address') }}</div>
                                <div class="value">{{ $client->address ?? helperTrans('ingaz.not_specified') }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card client-card">
                    <div class="card-body">
                        <div class="card-section-title mb-4">
                            <i class="fas fa-stream text-primary"></i>
                            {{ helperTrans('ingaz.orders_history') }}
                        </div>
                        @forelse($client->orders as $order)
                            <div class="timeline-item">
                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        <span class="fw-semibold text-primary">#{{ $order->order_code ?? $order->id }}</span>
                                        <span class="data-badge bg-primary-subtle text-primary">{{ ucfirst(str_replace('_', ' ', $order->status ?? '---')) }}</span>
                                        @if($order->jobType)
                                            <span class="data-badge bg-secondary-subtle text-secondary">{{ $order->jobType->title ?? '' }}</span>
                                        @endif
                                    </div>
                                    <div class="text-muted small">{{ $order->created_at?->format('Y-m-d H:i') }}</div>
                                </div>
                                <div class="text-muted small mb-2">
                                    <i class="fas fa-user-friends me-1"></i>{{ $order->maid?->name ?? helperTrans('ingaz.not_specified') }}
                                    <span class="mx-2 text-secondary">•</span>
                                    <i class="fas fa-box me-1"></i>{{ $order->package?->title ?? helperTrans('ingaz.not_specified') }}
                                </div>
                                @if($order->address)
                                    <div class="small">
                                        <i class="fas fa-map-marker-alt me-1 text-danger"></i>
                                        {{ $order->address->title ?? helperTrans('ingaz.not_specified') }} -
                                        {{ $order->address->address ?? helperTrans('ingaz.not_specified') }}
                                    </div>
                                @endif
                                <div class="mt-2 d-flex gap-2">
                                    <span class="badge bg-light text-muted px-3">
                                        <i class="fas fa-user-tie me-1"></i>{{ $order->details?->admin?->name ?? helperTrans('ingaz.not_specified') }}
                                    </span>
                                    @if(@AdminGuardHasPerm(\App\PermKeys::ORDERS_VIEW))
                                        <a href="{{ route('orders.show', $order->id) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye me-1"></i>{{ helperTrans('ingaz.show') }}
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-center text-muted mb-0">{{ helperTrans('ingaz.no_records') }}</p>
                        @endforelse
                    </div>
                </div>

                <div class="card client-card">
                    <div class="card-body">
                        <div class="card-section-title mb-3">
                            <i class="fas fa-file-invoice-dollar text-success"></i>
                            الفواتير
                        </div>
                        <div class="table-responsive">
                            <table class="table table-clean">
                                <thead>
                                    <tr>
                                        <th>رقم الفاتورة</th>
                                        <th>رقم العقد</th>
                                        <th>الإجمالي</th>
                                        <th>الضريبة</th>
                                        <th>الخصم</th>
                                        <th>المطلوب</th>
                                        <th>تاريخ الإنشاء</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                        <tr>
                                            <td>#{{ $invoice->id }}</td>
                                            <td>#{{ $invoice->contract_id }}</td>
                                            <td>{{ number_format($invoice->subtotal, 2) }}</td>
                                            <td>{{ number_format($invoice->vat_amount, 2) }}</td>
                                            <td>{{ number_format($invoice->discount, 2) }}</td>
                                            <td>{{ number_format($invoice->total_due, 2) }}</td>
                                            <td>{{ $invoice->created_at?->format('Y-m-d H:i') ?? '-' }}</td>
                                            <td class="text-end d-flex justify-content-end gap-2">
                                                <a href="{{ route('invoices.show', $invoice->id) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('invoice.download', $invoice->id) }}" class="btn btn-sm btn-outline-secondary" target="_blank">
                                                    <i class="fas fa-file-pdf"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center text-muted">{{ helperTrans('ingaz.no_records') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card client-card">
                    <div class="card-body">
                        <div class="card-section-title mb-3">
                            <i class="fas fa-file-contract text-info"></i>
                            {{ helperTrans('ingaz.rental_contracts') }}
                        </div>
                        <div class="table-responsive">
                            <table class="table table-clean">
                                <thead>
                                    <tr>
                                        <th>{{ helperTrans('ingaz.contract_number') }}</th>
                                        <th>{{ helperTrans('ingaz.maid_name') }}</th>
                                        <th>{{ helperTrans('ingaz.contract_status') }}</th>
                                        <th>{{ helperTrans('ingaz.contract_date') }}</th>
                                        <th>{{ helperTrans('ingaz.contract_value') }}</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($client->rentalContracts as $contract)
                                        <tr>
                                            <td>#{{ $contract->contract_number ?? $contract->id }}</td>
                                            <td>{{ $contract->maid?->name ?? helperTrans('ingaz.not_specified') }}</td>
                                            <td><span class="badge bg-info-subtle text-info">{{ $contract->status_text ?? helperTrans('ingaz.not_specified') }}</span></td>
                                            <td>{{ $contract->contract_date?->format('Y-m-d') ?? '-' }}</td>
                                            <td>{{ number_format($contract->calculateContractValue(), 2) }}</td>
                                            <td class="text-end">
                                                @if(@AdminGuardHasPerm(\App\PermKeys::RENT_CONTRACT_VIEW) || @AdminGuardHasPerm(\App\PermKeys::RENT_CONTRACT_CONTROL))
                                                    <a href="{{ route('RentContract.show', $contract->id) }}" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">{{ helperTrans('ingaz.no_records') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card client-card">
                    <div class="card-body">
                        <div class="card-section-title mb-3">
                            <i class="fas fa-exchange-alt text-secondary"></i>
                            {{ helperTrans('ingaz.transfer_history') }}
                        </div>
                        <div class="table-responsive">
                            <table class="table table-clean">
                                <thead>
                                    <tr>
                                        <th>{{ helperTrans('ingaz.contract_number') }}</th>
                                        <th>{{ helperTrans('ingaz.status') }}</th>
                                        <th>{{ helperTrans('ingaz.created_at') }}</th>
                                        <th>{{ helperTrans('ingaz.contract_value') }}</th>
                                        <th>{{ helperTrans('ingaz.old_sponsor') }}</th>
                                        <th>{{ helperTrans('ingaz.new_sponsor') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($sponsorshipTransfers as $transfer)
                                        <tr>
                                            <td>#{{ $transfer->contract?->id ?? '--' }}</td>
                                            <td><span class="badge bg-secondary-subtle text-secondary">{{ $transfer->status_text ?? helperTrans('ingaz.not_specified') }}</span></td>
                                            <td>{{ $transfer->created_at?->format('Y-m-d') ?? '-' }}</td>
                                            <td>{{ number_format($transfer->contract_value ?? 0, 2) }}</td>
                                            <td>{{ $transfer->oldSponsor?->name ?? helperTrans('ingaz.not_specified') }}</td>
                                            <td>{{ $transfer->newSponsor?->name ?? helperTrans('ingaz.not_specified') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">{{ helperTrans('ingaz.no_records') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card client-card">
                    <div class="card-body">
                        <div class="card-section-title mb-3">
                            <i class="fas fa-exclamation-circle text-danger"></i>
                            {{ helperTrans('ingaz.complaints_history') }}
                        </div>
                        <div class="table-responsive">
                            <table class="table table-clean">
                                <thead>
                                    <tr>
                                        <th>{{ helperTrans('ingaz.title') }}</th>
                                        <th>{{ helperTrans('ingaz.status') }}</th>
                                        <th>{{ helperTrans('ingaz.created_at') }}</th>
                                        <th>{{ helperTrans('ingaz.assigned_to') }}</th>
                                        <th>{{ helperTrans('ingaz.last_update') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($client->complaints as $complaint)
                                        <tr>
                                            <td>{{ $complaint->title ?? helperTrans('ingaz.not_specified') }}</td>
                                            <td><span class="badge bg-warning-subtle text-warning">{{ ucfirst($complaint->status ?? '') }}</span></td>
                                            <td>{{ $complaint->created_at?->format('Y-m-d H:i') ?? '-' }}</td>
                                            <td>{{ $complaint->assignedTo?->name ?? helperTrans('ingaz.not_specified') }}</td>
                                            <td>{{ $complaint->updated_at?->format('Y-m-d H:i') ?? '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">{{ helperTrans('ingaz.no_records') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 d-flex flex-column gap-3 gap-xl-4">
                <div class="card client-card">
                    <div class="card-body">
                        <div class="card-section-title mb-3">
                            <i class="fas fa-map-marker-alt text-danger"></i>
                            {{ helperTrans('ingaz.addresses') }}
                        </div>
                        <div class="d-flex flex-column gap-3">
                            @forelse($client->addresses as $address)
                                <div class="border rounded-3 p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div class="fw-semibold">{{ $address->title ?? helperTrans('ingaz.not_specified') }}</div>
                                        <span class="badge bg-light text-muted">{{ $address->country?->title ?? helperTrans('ingaz.not_specified') }}</span>
                                    </div>
                                    <div class="text-muted small d-flex flex-column gap-1">
                                        <span><i class="fas fa-map-pin me-1"></i>{{ $address->address ?? helperTrans('ingaz.not_specified') }}</span>
                                        <span><i class="fas fa-phone me-1"></i>{{ $address->phone ?? helperTrans('ingaz.not_specified') }}</span>
                                        <span><i class="fas fa-user me-1"></i>{{ $address->name ?? helperTrans('ingaz.not_specified') }}</span>
                                        @if($address->notes)
                                            <span><i class="fas fa-sticky-note me-1"></i>{{ $address->notes }}</span>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <p class="text-muted mb-0">{{ helperTrans('ingaz.no_addresses') }}</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="card client-card">
                    <div class="card-body">
                        <div class="card-section-title mb-3">
                            <i class="fas fa-comments text-success"></i>
                            {{ helperTrans('ingaz.messages_history') }}
                        </div>
                        <div class="d-flex flex-column gap-3">
                            @forelse($client->messages as $message)
                                <div class="border rounded-3 p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="fw-semibold">{{ $client->name }}</span>
                                        <span class="badge bg-light text-muted">{{ $message->created_at?->format('Y-m-d H:i') }}</span>
                                    </div>
                                    <div class="text-muted small">{{ $message->message }}</div>
                                </div>
                            @empty
                                <p class="text-muted mb-0">{{ helperTrans('ingaz.no_records') }}</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="card client-card">
                    <div class="card-body">
                        <div class="card-section-title mb-3">
                            <i class="fas fa-bolt text-warning"></i>
                            {{ helperTrans('ingaz.activity_timeline') }}
                        </div>
                        <ul class="timeline">
                            @forelse($activities as $activity)
                                <li class="timeline-item">
                                    <div class="fw-semibold">{{ $activity->description ?? helperTrans('ingaz.not_specified') }}</div>
                                    <div class="text-muted small">{{ $activity->created_at?->diffForHumans() }}</div>
                                    @if(!empty($activity->causer_name))
                                        <div class="text-muted small"><i class="fas fa-user me-1"></i>{{ $activity->causer_name }}</div>
                                    @endif
                                </li>
                            @empty
                                <li class="timeline-item">
                                    <div class="text-muted small">{{ helperTrans('ingaz.no_records') }}</div>
                                </li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
@else
    @include('components.no_permissions')
@endif
@endsection
