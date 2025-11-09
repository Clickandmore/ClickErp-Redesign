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
    </style>
@endsection

@section('page-title')
    {{ helperTrans('ingaz.show_maid') }}
@endsection

@section('content')
    @if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_VIEW))
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
            $imagePath = $maid->image ?: ($maid->cv_preview ?: $maid->cv_file);
            $imageUrl = $imagePath ? showFile($imagePath) : asset('images/no-image.png');
        @endphp

        <div class="container-fluid py-3 py-lg-4">
            <div class="card maid-hero mb-4">
                <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <img src="{{ $imageUrl }}" alt="maid-avatar" class="rounded-circle border"
                            style="width:88px;height:88px;object-fit:cover;">
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
                                <span><i
                                        class="fas fa-flag me-1"></i>{{ $maid->nationality?->sub_title ?? helperTrans('ingaz.not_specified') }}</span>
                                <span><i
                                        class="fas fa-building me-1"></i>{{ $maid->office?->title ?? helperTrans('ingaz.not_specified') }}</span>
                                <span><i
                                        class="fas fa-calendar-plus me-1"></i>{{ $availability['created_at']?->format('Y-m-d') ?? '-' }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('maids.index') }}" class="btn btn-outline-secondary">
                            <i
                                class="fas fa-arrow-right {{ app()->getLocale() === 'ar' ? '' : 'me-1' }}"></i>{{ helperTrans('ingaz.back_to_list') }}
                        </a>
                        @if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_EDIT))
                            <a href="{{ route('maids.edit', $maid->id) }}" class="btn btn-primary">
                                <i class="fas fa-edit me-1"></i>{{ helperTrans('ingaz.edit_maid') }}
                            </a>
                        @endif
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
                                <div class="text-muted small fw-semibold">{{ helperTrans('ingaz.rental_contracts_count') }}
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
                                        {{ $maid->office?->title ?? helperTrans('ingaz.not_specified') }}</div>
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
                            @if (!empty($skills))
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach ($skills as $skill)
                                        <span class="badge bg-light text-muted border">{{ $skill->name }}</span>
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
                            <ul class="timeline">
                                @forelse($activities as $activity)
                                    <li class="timeline-item">
                                        <div class="fw-semibold">
                                            {{ $activity->description ?? helperTrans('ingaz.not_specified') }}</div>
                                        <div class="text-muted small">{{ $activity->created_at?->diffForHumans() }}</div>
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
