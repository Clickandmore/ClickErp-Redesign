@extends('admin.layout.indexs.index')

@section('style')
    <style>
        .maid-hero {
            border-radius: 18px;
            border: 1px solid #eef2f6;
            box-shadow: 0 8px 24px rgba(15, 34, 58, 0.08);
        }

        .maid-card {
            border-radius: 18px;
            border: 1px solid #eef2f6;
            box-shadow: 0 10px 28px rgba(15, 34, 58, 0.06);
        }

        .stats-card {
            border-radius: 16px;
            border: 1px solid #eef2f6;
            box-shadow: 0 6px 18px rgba(15, 34, 58, 0.05);
        }

        .stats-card .stats-icon {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }

        .timeline {
            list-style: none;
            padding-inline-start: 0;
            margin: 0;
            position: relative;
        }

        .timeline::before {
            content: '';
            position: absolute;
            inset-inline-start: 15px;
            top: 8px;
            bottom: 8px;
            width: 2px;
            background: linear-gradient(180deg, rgba(37, 99, 235, 0.25), rgba(6, 182, 212, 0.25));
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

        .maid-skill-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
            gap: .5rem;
        }

        .maid-skill-item {
            display: flex;
            align-items: center;
            gap: .45rem;
            min-height: 38px;
            padding: .55rem .7rem;
            border: 1px solid #e8eef5;
            border-radius: 10px;
            background: #f8fafc;
            color: #243447;
            font-size: .86rem;
            font-weight: 600;
            line-height: 1.35;
            overflow-wrap: anywhere;
        }

        .maid-skill-item i {
            color: #f59e0b;
            flex: 0 0 auto;
        }

        .maid-photo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: .75rem;
        }

        .maid-photo-card {
            display: block;
            border: 1px solid #e8eef5;
            border-radius: 12px;
            overflow: hidden;
            background: #fff;
            text-decoration: none;
            color: inherit;
        }

        .maid-photo-card img {
            width: 100%;
            aspect-ratio: 4 / 3;
            object-fit: cover;
            display: block;
            background: #f8fafc;
        }

        .maid-photo-card span {
            display: block;
            padding: .55rem .65rem;
            font-size: .82rem;
            font-weight: 700;
            color: #243447;
        }
    </style>
@endsection

@section('page-title')
    {{ helperTrans('ingaz.show_maid') }}
@endsection

@section('content')
    @if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_VIEW) || @AdminGuardHasPerm(\App\PermKeys::MAIDS_SHOW) || ($isRentalMaid && @AdminGuardHasPerm(\App\PermKeys::MAIDS_RENTAL_VIEW)))
        @php
            $statusLabels = [
                'available' => [
                    'class' => 'bg-success-subtle text-success',
                    'icon' => 'fas fa-check-circle',
                    'text' => helperTrans('ingaz.maid_status_available'),
                ],
                'reserved' => [
                    'class' => 'bg-warning-subtle text-warning',
                    'icon' => 'fas fa-hand-paper',
                    'text' => helperTrans('ingaz.maid_status_reserved'),
                ],
                'contracted' => [
                    'class' => 'bg-primary-subtle text-primary',
                    'icon' => 'fas fa-file-signature',
                    'text' => helperTrans('ingaz.maid_status_contracted'),
                ],
                'in_accommodation' => [
                    'class' => 'bg-info-subtle text-info',
                    'icon' => 'fas fa-warehouse',
                    'text' => helperTrans('ingaz.maid_status_in_accommodation'),
                ],
                'backout' => [
                    'class' => 'bg-danger-subtle text-danger',
                    'icon' => 'fas fa-undo',
                    'text' => helperTrans('ingaz.maid_status_backout'),
                ],
            ];
            $statusMeta = $statusLabels[$availability['status']] ?? [
                'class' => 'bg-secondary-subtle text-secondary',
                'icon' => 'fas fa-question-circle',
                'text' => helperTrans('ingaz.maid_status_unknown'),
            ];
            $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'];
            $imagePath = collect([$maid->image, $maid->cv_preview])
                ->first(fn ($path) => $path && in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), $imageExtensions, true) && showFile($path));
            $imageUrl = $imagePath ? showFile($imagePath) : null;
            $cvFileUrl = $maid->cv_file ? showFile($maid->cv_file) : null;
        @endphp

        <div class="container-fluid py-3 py-lg-4">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="card maid-hero mb-4">
                <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        @if ($imageUrl)
                            <img src="{{ $imageUrl }}" alt="maid-avatar" class="rounded-circle border"
                                style="width:88px;height:88px;object-fit:cover;">
                        @elseif ($cvFileUrl)
                            <button type="button" data-cv-preview-url="{{ $cvFileUrl }}"
                                class="rounded-circle border bg-light d-inline-flex flex-column align-items-center justify-content-center text-decoration-none flex-shrink-0"
                                style="width:88px;height:88px" title="فتح ملف السيرة PDF">
                                <i class="fas fa-file-pdf text-danger fs-2"></i>
                                <span class="small">PDF</span>
                            </button>
                        @else
                            <img src="{{ asset('images/no-image.png') }}" alt="maid-avatar" class="rounded-circle border"
                                style="width:88px;height:88px;object-fit:cover;">
                        @endif
                        <div class="d-flex flex-column gap-2">
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <h2 class="mb-0 fw-bold">{{ $maid->name ?? helperTrans('ingaz.not_specified') }}</h2>
                                <span class="badge {{ $statusMeta['class'] }} d-inline-flex align-items-center gap-1">
                                    <i class="{{ $statusMeta['icon'] }}"></i>{{ $statusMeta['text'] }}
                                </span>
                                @if ($maid->is_pinned)
                                    <span class="badge bg-warning-subtle text-warning"><i class="fas fa-thumbtack"></i>
                                        {{ helperTrans('ingaz.pinned') }}</span>
                                @endif
                            </div>
                            <div class="text-muted small d-flex flex-wrap gap-3">
                                <span><i
                                        class="fas fa-passport me-1"></i>{{ $maid->passport_num ?? helperTrans('ingaz.not_specified') }}</span>
                                @if ($maid->arabia_worker_number)
                                    <span><i class="fas fa-hashtag me-1"></i>رقم عامل أربيا:
                                        {{ $maid->arabia_worker_number }}</span>
                                @endif
                                <span><i
                                        class="fas fa-flag me-1"></i>{{ $maid->nationality?->sub_title ?? helperTrans('ingaz.not_specified') }}</span>
                                <span><i
                                        class="fas fa-building me-1"></i>{{ $externalOffice?->title ?? helperTrans('ingaz.not_specified') }}</span>
                                <span><i
                                        class="fas fa-diagram-project me-1"></i>{{ helperTrans('ingaz.request_type') }}:
                                    {{ $maid->request_type_label }}</span>
                                <span><i
                                        class="fas fa-user-tie me-1"></i>{{ helperTrans('ingaz.added_by') }}:
                                    {{ $maid->addedBy?->name ?? helperTrans('ingaz.not_specified') }}</span>
                                <span><i
                                        class="fas fa-calendar-plus me-1"></i>{{ $availability['created_at']?->format('Y-m-d') ?? '-' }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ $backToListRoute ?? route('maids.index') }}" class="btn btn-outline-secondary">
                            <i
                                class="fas fa-arrow-right {{ app()->getLocale() === 'ar' ? '' : 'me-1' }}"></i>{{ helperTrans('ingaz.back_to_list') }}
                        </a>
                        @if ($isRentalMaid && Route::has('hr.maids.employ') && (!$hrEmployeeRecord || $hrEmployeeRecord->employee_category !== \Nami\Hr\Models\Employee::CATEGORY_EMPLOYEE))
                            <form method="POST" action="{{ route('hr.maids.employ', $maid->id) }}">
                                @csrf
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-user-plus me-1"></i>{{ helperTrans('ingaz.employ_maid') }}
                                </button>
                            </form>
                        @endif
                        @if ($hrEmployeeRecord && $hrEmployeeRecord->employee_category === \Nami\Hr\Models\Employee::CATEGORY_EMPLOYEE && Route::has('employees.edit'))
                            <a href="{{ route('employees.edit', $hrEmployeeRecord->id) }}" class="btn btn-outline-success">
                                <i class="fas fa-id-badge me-1"></i>{{ helperTrans('ingaz.employee_file') }}
                            </a>
                        @endif
                        @if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_EDIT))
                            <a href="{{ route('maids.edit', $maid->id) }}" class="btn btn-primary">
                                <i class="fas fa-edit me-1"></i>{{ helperTrans('ingaz.edit_maid') }}
                            </a>
                        @endif
                        <a href="#maid-attachments" class="btn btn-outline-info">
                            <i class="fas fa-paperclip me-1"></i>{{ helperTrans('ingaz.attachments') }}
                        </a>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-xl-2 col-md-4 col-sm-6">
                    <div class="card stats-card h-100">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-muted small fw-semibold">{{ helperTrans('ingaz.total_orders_count') }}
                                </div>
                                <div class="fs-4 fw-bold">{{ number_format($stats['orders_total']) }}</div>
                            </div>
                            <span class="stats-icon bg-primary-subtle text-primary"><i
                                    class="fas fa-clipboard-list"></i></span>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-sm-6">
                    <div class="card stats-card h-100">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-muted small fw-semibold">{{ helperTrans('ingaz.active_orders_count') }}
                                </div>
                                <div class="fs-4 fw-bold text-warning">{{ number_format($stats['orders_active']) }}</div>
                            </div>
                            <span class="stats-icon bg-warning-subtle text-warning"><i class="fas fa-tasks"></i></span>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-sm-6">
                    <div class="card stats-card h-100">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-muted small fw-semibold">{{ helperTrans('ingaz.recruitment_contracts_count') }}
                                </div>
                                <div class="fs-4 fw-bold text-info">{{ number_format($stats['contracts_total']) }}</div>
                            </div>
                            <span class="stats-icon bg-info-subtle text-info"><i class="fas fa-file-contract"></i></span>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-sm-6">
                    <div class="card stats-card h-100">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-muted small fw-semibold">{{ helperTrans('ingaz.active_contracts_count') }}
                                </div>
                                <div class="fs-4 fw-bold text-secondary">{{ number_format($stats['contracts_active']) }}
                                </div>
                            </div>
                            <span class="stats-icon bg-secondary-subtle text-secondary"><i
                                    class="fas fa-clipboard-check"></i></span>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-sm-6">
                    <div class="card stats-card h-100">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-muted small fw-semibold">{{ helperTrans('ingaz.experience_years') }}</div>
                                <div class="fs-4 fw-bold text-success">
                                    {{ number_format((float) $stats['experience_years'], 1) }}</div>
                            </div>
                            <span class="stats-icon bg-success-subtle text-success"><i
                                    class="fas fa-user-graduate"></i></span>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-sm-6">
                    <div class="card stats-card h-100">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-muted small fw-semibold">{{ helperTrans('ingaz.available_from') }}</div>
                                <div class="fs-5 fw-bold">
                                    {{ $availability['available_at'] ? \Carbon\Carbon::parse($availability['available_at'])->format('Y-m-d') : '-' }}
                                </div>
                            </div>
                            <span class="stats-icon bg-light text-muted"><i class="fas fa-calendar-check"></i></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-xl-8 d-flex flex-column gap-3">
                    <div class="card maid-card">
                        <div class="card-body">
                            <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
                                <i class="fas fa-user-circle text-primary"></i>{{ helperTrans('ingaz.basic_information') }}
                            </h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="text-muted small">{{ helperTrans('ingaz.name') }}</div>
                                    <div class="fw-semibold">{{ $maid->name ?? helperTrans('ingaz.not_specified') }}</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted small">{{ helperTrans('ingaz.age') }}</div>
                                    <div class="fw-semibold">{{ $maid->age ?? '-' }}</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted small">{{ helperTrans('web.nationality') }}</div>
                                    <div class="fw-semibold">
                                        {{ $maid->nationality?->sub_title ?? helperTrans('ingaz.not_specified') }}</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted small">{{ helperTrans('web.religion') }}</div>
                                    <div class="fw-semibold">
                                        {{ $maid->religion?->title ?? helperTrans('ingaz.not_specified') }}</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted small">{{ helperTrans('web.passport_number') }}</div>
                                    <div class="fw-semibold">{{ $maid->passport_num ?? '-' }}</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted small">{{ helperTrans('ingaz.height') }}</div>
                                    <div class="fw-semibold">{{ $maid->height ?? '-' }}</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted small">{{ helperTrans('ingaz.weight') }}</div>
                                    <div class="fw-semibold">{{ $maid->weight ?? '-' }}</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted small">{{ helperTrans('ingaz.children') }}</div>
                                    <div class="fw-semibold">{{ $maid->childrens_count ?? '0' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card maid-card">
                        <div class="card-body">
                            <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
                                <i class="fas fa-briefcase text-secondary"></i>{{ helperTrans('ingaz.job_information') }}
                            </h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="text-muted small">{{ helperTrans('ingaz.job_types') }}</div>
                                    <div class="fw-semibold">
                                        {{ $maid->jobTypes->pluck('title')->implode(', ') ?: helperTrans('ingaz.not_specified') }}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted small">{{ helperTrans('ingaz.office') }}</div>
                                    <div class="fw-semibold">
                                        {{ $externalOffice?->title ?? helperTrans('ingaz.not_specified') }}</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted small">{{ helperTrans('ingaz.request_type') }}</div>
                                    <div class="fw-semibold">{{ $maid->request_type_label }}</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted small">{{ helperTrans('web.salary') }}</div>
                                    <div class="fw-semibold">
                                        {{ $maid->resolved_salary !== null ? number_format($maid->resolved_salary, 2) : helperTrans('ingaz.not_specified') }}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted small">{{ helperTrans('ingaz.price') }}</div>
                                    <div class="fw-semibold">
                                        {{ $maid->resolved_price !== null ? number_format($maid->resolved_price, 2) : helperTrans('ingaz.not_specified') }}
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="text-muted small">{{ helperTrans('ingaz.additional_notes') }}</div>
                                    <div class="fw-semibold">
                                        {{ $maid->additional_notes ?? helperTrans('ingaz.not_specified') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card maid-card">
                        <div class="card-body">
                            <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
                                <i class="fas fa-building text-primary"></i>بيانات الوكيل الخارجي
                            </h5>
                            @if ($externalOffice)
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="text-muted small">{{ helperTrans('ingaz.recruitment_office') }}</div>
                                        <div class="fw-semibold">{{ $externalOffice->title ?? helperTrans('ingaz.not_specified') }}</div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="text-muted small">{{ helperTrans('ingaz.service') }}</div>
                                        <div class="fw-semibold">{{ $maid->request_type_label }}</div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="text-muted small">{{ helperTrans('ingaz.nationality') }}</div>
                                        <div class="fw-semibold">{{ $maid->nationality?->sub_title ?? helperTrans('ingaz.not_specified') }}</div>
                                    </div>
                                </div>
                            @else
                                <p class="text-muted mb-0">{{ helperTrans('ingaz.not_specified') }}</p>
                            @endif
                        </div>
                    </div>

                    @if ($isRentalMaid)
                        <div class="card maid-card">
                            <div class="card-body">
                                <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
                                    <i class="fas fa-wallet text-success"></i>{{ helperTrans('ingaz.financial_items') }}
                                </h5>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-4">
                                        <div class="border rounded-3 p-3 h-100">
                                            <div class="text-muted small">{{ helperTrans('ingaz.total_income') }}</div>
                                            <div class="fw-bold text-success">{{ number_format($rentalFinanceSummary['income'] ?? 0, 2) }}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="border rounded-3 p-3 h-100">
                                            <div class="text-muted small">{{ helperTrans('ingaz.total_expenses') }}</div>
                                            <div class="fw-bold text-danger">{{ number_format($rentalFinanceSummary['expense'] ?? 0, 2) }}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="border rounded-3 p-3 h-100">
                                            <div class="text-muted small">{{ helperTrans('ingaz.net') }}</div>
                                            <div class="fw-bold {{ ($rentalFinanceSummary['net'] ?? 0) >= 0 ? 'text-primary' : 'text-danger' }}">
                                                {{ number_format($rentalFinanceSummary['net'] ?? 0, 2) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th style="width: 18%;">{{ helperTrans('ingaz.type') }}</th>
                                                <th>{{ helperTrans('ingaz.item') }}</th>
                                                <th style="width: 20%;">{{ helperTrans('ingaz.amount') }}</th>
                                                <th style="width: 22%;">{{ helperTrans('ingaz.status') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($rentalFinanceItems as $item)
                                                <tr>
                                                    <td>
                                                        <span class="badge {{ $item->type === 'income' ? 'bg-success' : 'bg-danger' }}">
                                                            {{ $item->type === 'income' ? helperTrans('ingaz.income') : helperTrans('ingaz.expense') }}
                                                        </span>
                                                    </td>
                                                    <td>{{ $item->label ?? '-' }}</td>
                                                    <td>{{ number_format((float) $item->amount, 2) }}</td>
                                                    <td>
                                                        @if ($item->settled_at)
                                                            <span class="badge bg-info text-dark">{{ __('ingaz.settled_at', ['date' => $item->settled_at->format('Y-m-d H:i')]) }}</span>
                                                        @else
                                                            <span class="badge bg-secondary">{{ helperTrans('ingaz.unsettled') }}</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted">{{ helperTrans('ingaz.no_financial_items_recorded') }}</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="card maid-card">
                        <div class="card-body">
                            <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
                                <i class="fas fa-history text-info"></i>{{ helperTrans('ingaz.orders_history') }}
                            </h5>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>{{ helperTrans('ingaz.status') }}</th>
                                            <th>{{ helperTrans('ingaz.job_type') }}</th>
                                            <th>{{ helperTrans('ingaz.client') }}</th>
                                            <th>{{ helperTrans('ingaz.created_at') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($orders as $order)
                                            <tr>
                                                <td>{{ $order->order_code ?? $order->id }}</td>
                                                <td><span
                                                        class="badge bg-light text-muted border">{{ ucfirst($order->status ?? '-') }}</span>
                                                </td>
                                                <td>{{ $order->jobType?->title ?? '-' }}</td>
                                                <td>{{ $order->user?->name ?? '-' }}</td>
                                                <td>{{ $order->created_at?->format('Y-m-d H:i') ?? '-' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center text-muted">
                                                    {{ helperTrans('ingaz.no_records') }}</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="card maid-card">
                        <div class="card-body">
                            <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
                                <i
                                    class="fas fa-file-contract text-primary"></i>{{ helperTrans('ingaz.contracts_history') }}
                            </h5>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>{{ helperTrans('ingaz.status') }}</th>
                                            <th>{{ helperTrans('ingaz.client') }}</th>
                                            <th>{{ helperTrans('ingaz.contract_date') }}</th>
                                            <th>{{ helperTrans('ingaz.contract_value') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($contracts as $contract)
                                            <tr>
                                                <td>{{ $contract->contract_number ?? $contract->id }}</td>
                                                <td><span
                                                        class="badge bg-light text-muted border">{{ ucfirst($contract->status ?? '-') }}</span>
                                                </td>
                                                <td>{{ $contract->user?->name ?? '-' }}</td>
                                                @php
                                                    $contractDate = $contract->contract_date ?? null;
                                                    if ($contractDate && ! $contractDate instanceof \Carbon\Carbon) {
                                                        try {
                                                            $contractDate = \Carbon\Carbon::parse($contractDate);
                                                        } catch (\Exception $e) {
                                                            $contractDate = null;
                                                        }
                                                    }
                                                @endphp
                                                <td>{{ $contractDate?->format('Y-m-d') ?? '-' }}</td>
                                                <td>{{ number_format((float) $contract->calculateContractValue(), 2) }}
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center text-muted">
                                                    {{ helperTrans('ingaz.no_records') }}</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 d-flex flex-column gap-3">
                    <div class="card maid-card" id="maid-attachments">
                        <div class="card-body">
                            <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
                                <i class="fas fa-paperclip text-info"></i>{{ helperTrans('ingaz.maid_attachments') }}
                            </h5>

                            @if ($cvFileUrl)
                                <div class="border rounded-3 p-3 mb-3 bg-light">
                                    <div class="d-flex align-items-center gap-3">
                                        <i class="fas fa-file-pdf text-danger fs-2"></i>
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold">ملف السيرة الذاتية الأساسي</div>
                                            <div class="text-muted small">الملف المستورد والمرتبط بهذه العاملة</div>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2 mt-3">
                                        <button type="button" data-cv-preview-url="{{ $cvFileUrl }}"
                                            class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-eye me-1"></i>عرض داخل الموقع
                                        </button>
                                        <a href="{{ $cvFileUrl }}" download class="btn btn-light btn-sm border">
                                            <i class="fas fa-download me-1"></i>تنزيل
                                        </a>
                                    </div>
                                </div>
                            @endif

                            @if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_EDIT))
                                <form method="POST" action="{{ route('maids.attachments.store', $maid->id) }}"
                                    enctype="multipart/form-data" class="mb-3">
                                    @csrf
                                    <div class="mb-2">
                                        <label class="form-label">{{ helperTrans('ingaz.attachment_optional_description') }}</label>
                                        <input type="text" name="title" class="form-control"
                                            value="{{ old('title') }}" placeholder="{{ helperTrans('ingaz.attachment_placeholder_passport') }}">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">{{ helperTrans('ingaz.select_attachments') }}</label>
                                        <input type="file" name="attachments[]" class="form-control" multiple required>
                                        <div class="form-text">{{ helperTrans('ingaz.attachments_upload_hint') }}</div>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="fas fa-upload me-1"></i>{{ helperTrans('ingaz.upload_attachments') }}
                                    </button>
                                </form>
                            @endif

                            @if ($maid->attachments->isNotEmpty())
                                <form id="maidAttachmentsDownloadForm" method="GET"
                                    action="{{ route('maids.attachments.download', $maid->id) }}"></form>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <strong class="small text-muted">{{ helperTrans('ingaz.current_attachments') }}</strong>
                                    <button type="submit" class="btn btn-outline-success btn-sm"
                                        form="maidAttachmentsDownloadForm">
                                        <i class="fas fa-file-archive me-1"></i>{{ helperTrans('ingaz.download_selected') }}
                                    </button>
                                </div>
                                <div class="d-flex flex-column gap-2">
                                    @foreach ($maid->attachments as $attachment)
                                        <div class="border rounded-3 p-2">
                                            <div class="d-flex align-items-start gap-2">
                                                <input class="form-check-input mt-1" type="checkbox"
                                                    name="attachments[]" value="{{ $attachment->id }}"
                                                    id="maid_attachment_{{ $attachment->id }}" checked
                                                    form="maidAttachmentsDownloadForm">
                                                <label class="flex-grow-1" for="maid_attachment_{{ $attachment->id }}">
                                                    <span class="d-block fw-semibold text-break">
                                                        {{ $attachment->title ?: $attachment->original_name }}
                                                    </span>
                                                    <span class="d-block text-muted small text-break">
                                                        {{ $attachment->original_name }} - {{ $attachment->formatted_size }}
                                                    </span>
                                                    <span class="d-block text-muted small">
                                                        {{ $attachment->created_at?->format('Y-m-d H:i') ?? '-' }}
                                                        @if ($attachment->uploader)
                                                            - {{ $attachment->uploader->name }}
                                                        @endif
                                                    </span>
                                                </label>
                                            </div>
                                            <div class="d-flex flex-wrap gap-2 mt-2">
                                                <a href="{{ route('maids.attachments.view', [$maid->id, $attachment->id]) }}"
                                                    class="btn btn-outline-primary btn-sm" target="_blank">
                                                    <i class="fas fa-eye me-1"></i>{{ helperTrans('ingaz.view') }}
                                                </a>
                                                <a href="{{ route('maids.attachments.download.single', [$maid->id, $attachment->id]) }}"
                                                    class="btn btn-light btn-sm border">
                                                    <i class="fas fa-download me-1"></i>{{ helperTrans('ingaz.download') }}
                                                </a>
                                                @if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_EDIT))
                                                    <form method="POST"
                                                        action="{{ route('maids.attachments.delete', [$maid->id, $attachment->id]) }}"
                                                        onsubmit="return confirm('{{ helperTrans('ingaz.confirm_delete_this_attachment') }}');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-outline-danger btn-sm">
                                                            <i class="fas fa-trash me-1"></i>{{ helperTrans('ingaz.delete') }}
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-muted mb-0">{{ helperTrans('ingaz.no_maid_attachments') }}</p>
                            @endif

                            <div class="mt-4 pt-3 border-top">
                                <h6 class="fw-bold mb-3 d-flex align-items-center gap-2">
                                    <i class="fas fa-images text-primary"></i>صور العاملة
                                </h6>

                                @if (($maidImages ?? collect())->isNotEmpty())
                                    <div class="maid-photo-grid">
                                        @foreach ($maidImages as $image)
                                            <a href="{{ $image['url'] }}" class="maid-photo-card" target="_blank"
                                                rel="noopener">
                                                <img src="{{ $image['url'] }}" alt="{{ $image['label'] }}">
                                                <span>{{ $image['label'] }}</span>
                                            </a>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-muted mb-0">لا توجد صور مسجلة للعاملة.</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="card maid-card">
                        <div class="card-body">
                            <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
                                <i class="fas fa-id-badge text-primary"></i>{{ helperTrans('ingaz.hr_record') }}
                            </h5>
                            @if ($hrEmployeeRecord)
                                <div class="d-flex flex-column gap-2">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">{{ helperTrans('ingaz.current_status') }}</span>
                                        <span class="fw-semibold">
                                            {{ $hrEmployeeRecord->employee_category === \Nami\Hr\Models\Employee::CATEGORY_EMPLOYEE ? helperTrans('ingaz.company_employee') : helperTrans('ingaz.rental_worker_in_hr') }}
                                        </span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">{{ helperTrans('ingaz.code') }}</span>
                                        <span class="fw-semibold">{{ $hrEmployeeRecord->code ?? '-' }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">{{ helperTrans('ingaz.department') }}</span>
                                        <span class="fw-semibold">{{ $hrEmployeeRecord->departments?->title ?? '-' }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">{{ helperTrans('ingaz.job') }}</span>
                                        <span class="fw-semibold">{{ $hrEmployeeRecord->jobs?->title ?? '-' }}</span>
                                    </div>
                                </div>
                                <div class="d-flex flex-wrap gap-2 mt-3">
                                    @if ($hrEmployeeRecord->employee_category !== \Nami\Hr\Models\Employee::CATEGORY_EMPLOYEE && Route::has('hr.maids.employ'))
                                        <form method="POST" action="{{ route('hr.maids.employ', $maid->id) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-success btn-sm">
                                                <i class="fas fa-user-plus me-1"></i>{{ helperTrans('ingaz.employ_maid') }}
                                            </button>
                                        </form>
                                    @endif
                                    @if ($hrEmployeeRecord->employee_category === \Nami\Hr\Models\Employee::CATEGORY_EMPLOYEE && Route::has('employees.edit'))
                                        <a href="{{ route('employees.edit', $hrEmployeeRecord->id) }}" class="btn btn-outline-success btn-sm">
                                            <i class="fas fa-pen me-1"></i>{{ helperTrans('ingaz.edit_employee_data') }}
                                        </a>
                                    @endif
                                </div>
                            @else
                                <p class="text-muted mb-3">{{ helperTrans('ingaz.no_hr_record_for_maid') }}</p>
                                @if ($isRentalMaid && Route::has('hr.maids.employ'))
                                    <form method="POST" action="{{ route('hr.maids.employ', $maid->id) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-sm">
                                            <i class="fas fa-user-plus me-1"></i>{{ helperTrans('ingaz.create_hr_record_and_employ_maid') }}
                                        </button>
                                    </form>
                                @endif
                            @endif
                        </div>
                    </div>

                    <div class="card maid-card">
                        <div class="card-body">
                            <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
                                <i
                                    class="fas fa-user-shield text-success"></i>{{ helperTrans('ingaz.availability_information') }}
                            </h5>
                            <ul class="list-unstyled mb-0 d-flex flex-column gap-2">
                                <li class="d-flex justify-content-between">
                                    <span class="text-muted">{{ helperTrans('ingaz.visibility') }}</span>
                                    <span
                                        class="fw-semibold">{{ $availability['is_show'] ? helperTrans('ingaz.maid_visibility_visible') : helperTrans('ingaz.maid_visibility_hidden') }}</span>
                                </li>
                                <li class="d-flex justify-content-between">
                                    <span class="text-muted">{{ helperTrans('ingaz.availability_status') }}</span>
                                    <span
                                        class="fw-semibold">{{ $availability['available'] ? helperTrans('ingaz.maid_available_now') : helperTrans('ingaz.maid_not_available_now') }}</span>
                                </li>
                                <li class="d-flex justify-content-between">
                                    <span class="text-muted">{{ helperTrans('ingaz.available_from') }}</span>
                                    <span
                                        class="fw-semibold">{{ $availability['available_at'] ? \Carbon\Carbon::parse($availability['available_at'])->format('Y-m-d') : '-' }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="card maid-card">
                        <div class="card-body">
                            <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
                                <i class="fas fa-globe text-info"></i>{{ helperTrans('ingaz.languages') }}
                            </h5>
                            @if (!empty($languages))
                                <ul class="list-unstyled mb-0 d-flex flex-column gap-2">
                                    @foreach ($languages as $language)
                                        @php $language = trim($language); @endphp
                                        <li class="d-flex justify-content-between">
                                            <span class="fw-semibold">{{ $language }}</span>
                                            <span class="text-muted">{{ $languagePercentages[$language] ?? '-' }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="text-muted mb-0">{{ helperTrans('ingaz.not_specified') }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="card maid-card">
                        <div class="card-body">
                            <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
                                <i class="fas fa-star text-warning"></i>{{ helperTrans('ingaz.skills') }}
                            </h5>
                            @if (!empty($maidSkills))
                                <div class="maid-skill-grid">
                                    @foreach ($maidSkills as $skill)
                                        @php
                                            $skillName = is_string($skill)
                                                ? trim($skill)
                                                : trim((string) ($skill->name ?? $skill->title ?? ''));
                                        @endphp
                                        @if ($skillName !== '')
                                            <span class="maid-skill-item">
                                                <i class="fas fa-check-circle"></i>
                                                <span>{{ $skillName }}</span>
                                            </span>
                                        @endif
                                    @endforeach
                                </div>
                            @else
                                <p class="text-muted mb-0">{{ helperTrans('ingaz.not_specified') }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="card maid-card">
                        <div class="card-body">
                            <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
                                <i
                                    class="fas fa-briefcase text-secondary"></i>{{ helperTrans('ingaz.experience_information') }}
                            </h5>
                            @if ($maid->expertises->isNotEmpty())
                                <div class="d-flex flex-column gap-2">
                                    @foreach ($maid->expertises as $expertise)
                                        <div class="border rounded-3 p-3">
                                            <div class="fw-semibold">
                                                {{ $expertise->jobTitle?->title ?? helperTrans('ingaz.not_specified') }}
                                            </div>
                                            <div class="text-muted small">
                                                <i
                                                    class="fas fa-globe me-1"></i>{{ $expertise->country?->title ?? helperTrans('ingaz.not_specified') }}
                                                <span class="mx-1 text-secondary">•</span>
                                                <i
                                                    class="fas fa-hourglass-half me-1"></i>{{ $expertise->number_of_years ?? '0' }}
                                                {{ helperTrans('ingaz.years') }}
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-muted mb-0">{{ helperTrans('ingaz.not_specified') }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="card maid-card">
                        <div class="card-body">
                            <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
                                <i class="fas fa-history text-muted"></i>{{ helperTrans('ingaz.activity_timeline') }}
                            </h5>
                            @include('admin.partials.activity-feed', ['activities' => $activities])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        @include('components.no_permissions')
    @endif

    <div class="modal fade" id="maidCvPreviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content" style="height:92vh">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-file-pdf text-danger me-2"></i>معاينة ملف السيرة الذاتية</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <div class="modal-body p-0 bg-secondary" style="min-height:0">
                    <iframe id="maidCvPreviewFrame" title="معاينة ملف السيرة" class="w-100 h-100 border-0 bg-white"></iframe>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('click', function (event) {
            const trigger = event.target.closest('[data-cv-preview-url]');
            if (!trigger) return;

            const frame = document.getElementById('maidCvPreviewFrame');
            const modalElement = document.getElementById('maidCvPreviewModal');
            frame.src = trigger.dataset.cvPreviewUrl + '#toolbar=1&navpanes=0&view=FitH';
            bootstrap.Modal.getOrCreateInstance(modalElement).show();
        });

        document.getElementById('maidCvPreviewModal')?.addEventListener('hidden.bs.modal', function () {
            document.getElementById('maidCvPreviewFrame').src = 'about:blank';
        });
    </script>
@endsection
