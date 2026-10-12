@extends('click.layout.indexs.index')

@section('content')
    @if (@AdminGuardHasAnyPerm([\App\PermKeys::FINANCIAL_REQUESTS_VIEW, \App\PermKeys::FINANCIAL_REQUESTS_CREATE, \App\PermKeys::FINANCIAL_REQUESTS_DELETE, \App\PermKeys::FINANCIAL_REQUESTS_CHANGE_STATUS, \App\PermKeys::FINANCIAL_REQUESTS_APPROVE, \App\PermKeys::FINANCIAL_REQUESTS_REQUEST_CLOSING, \App\PermKeys::FINANCIAL_REQUESTS_CLOSE, \App\PermKeys::FINANCIAL_REQUESTS_CONTROL]))
        <div class="container-fluid">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                    <h3 class="mb-1">{{ __('financial_requests.manage') }}</h3>
                    <div class="text-muted">{{ __('financial_requests.subtitle') }}</div>
                </div>
                <div class="d-flex gap-2">
                    @if (! $showDeleted && @AdminGuardHasAnyPerm([\App\PermKeys::FINANCIAL_REQUESTS_EXPORT, \App\PermKeys::FINANCIAL_REQUESTS_CONTROL]))
                        <a class="btn btn-outline-success" href="{{ route('financial-requests.export', request()->query()) }}">
                            <i class="fa fa-file-excel"></i> Excel
                        </a>
                        <a class="btn btn-outline-dark" target="_blank" href="{{ route('financial-requests.print', request()->query()) }}">
                            <i class="fa fa-print"></i> {{ __('financial_requests.print') }}
                        </a>
                    @endif
                    @if (@AdminGuardHasAnyPerm([\App\PermKeys::FINANCIAL_REQUESTS_CREATE, \App\PermKeys::FINANCIAL_REQUESTS_CONTROL]))
                        <a class="btn btn-primary" href="{{ route('financial-requests.create') }}">
                            <i class="fa fa-plus"></i> {{ __('financial_requests.new') }}
                        </a>
                    @endif
                </div>
            </div>

            @php
                $activeScope = request('scope', 'all');
                $scopeQuery = request()->except(['page', 'scope', 'status']);
            @endphp
            <div class="d-flex flex-wrap gap-2 mb-3">
                <a class="btn {{ $activeScope === 'open' ? 'btn-primary' : 'btn-outline-primary' }}"
                    href="{{ route('financial-requests.index', array_merge($scopeQuery, ['scope' => 'open'])) }}">
                    {{ app()->getLocale() === 'ar' ? 'غير المقفلة' : 'Open requests' }}
                    <span class="badge bg-light text-dark ms-1">{{ $counts['open'] ?? 0 }}</span>
                </a>
                <a class="btn {{ $activeScope === 'all' ? 'btn-dark' : 'btn-outline-dark' }}"
                    href="{{ route('financial-requests.index', array_merge($scopeQuery, ['scope' => 'all'])) }}">
                    {{ $scopes['all'] }}
                    <span class="badge bg-light text-dark ms-1">{{ $counts['all'] ?? 0 }}</span>
                </a>
                <a class="btn {{ $activeScope === 'incomplete' ? 'btn-dark' : 'btn-outline-dark' }}"
                    href="{{ route('financial-requests.index', array_merge($scopeQuery, ['scope' => 'incomplete'])) }}">
                    {{ $scopes['incomplete'] }}
                    <span class="badge bg-light text-dark ms-1">{{ $counts['incomplete'] ?? 0 }}</span>
                </a>
                <a class="btn {{ $activeScope === 'paid' ? 'btn-success' : 'btn-outline-success' }}"
                    href="{{ route('financial-requests.index', array_merge($scopeQuery, ['scope' => 'paid'])) }}">
                    {{ $scopes['paid'] }}
                    <span class="badge bg-light text-dark ms-1">{{ $counts['paid'] ?? 0 }}</span>
                </a>
                @if($canDeleteRequests)
                    <a class="btn {{ $activeScope === 'deleted' ? 'btn-danger' : 'btn-outline-danger' }}"
                        href="{{ route('financial-requests.index', array_merge($scopeQuery, ['scope' => 'deleted'])) }}">
                        {{ $scopes['deleted'] }}
                        <span class="badge bg-light text-dark ms-1">{{ $counts['deleted'] ?? 0 }}</span>
                    </a>
                @endif
            </div>

            <form class="card mb-3" method="GET">
                <div class="card-body">
                    @if (in_array(request('scope'), ['open', 'all', 'incomplete', 'paid', 'deleted'], true))
                        <input type="hidden" name="scope" value="{{ request('scope') }}">
                    @endif
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">{{ __('financial_requests.search') }}</label>
                            <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="{{ __('financial_requests.contract_no') }}, {{ __('financial_requests.identity') }}, {{ __('financial_requests.client') }}, {{ __('financial_requests.request_no') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">{{ __('financial_requests.request_type') }}</label>
                            <select name="type" class="form-select">
                                <option value="">{{ __('financial_requests.all') }}</option>
                                @foreach ($types as $key => $label)
                                    <option value="{{ $key }}" {{ request('type') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">{{ __('financial_requests.status') }}</label>
                            <select name="status" class="form-select">
                                <option value="">{{ __('financial_requests.all') }}</option>
                                @foreach ($statuses as $key => $label)
                                    <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">{{ __('financial_requests.priority') }}</label>
                            <select name="priority" class="form-select">
                                <option value="">{{ __('financial_requests.all') }}</option>
                                @foreach ($priorities as $key => $label)
                                    <option value="{{ $key }}" {{ request('priority') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">{{ __('financial_requests.date') }}</label>
                            <input type="date" name="date" value="{{ request('date') }}" class="form-control">
                        </div>
                        @include('admin.partials.branch-filter', ['id' => 'branchFilter', 'containerClass' => 'col-md-2'])
                        <div class="col-md-1 d-flex align-items-end">
                            <button class="btn btn-dark w-100" type="submit"><i class="fa fa-search"></i></button>
                        </div>
                    </div>
                </div>
            </form>

            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                        <tr>
                            <th>{{ __('financial_requests.request_no') }}</th>
                            <th>{{ __('financial_requests.type') }}</th>
                            <th>{{ __('financial_requests.client') }}</th>
                            <th>{{ __('financial_requests.contract_no') }}</th>
                            <th>{{ __('financial_requests.amount') }}</th>
                            <th>{{ __('financial_requests.status') }}</th>
                            <th>{{ __('financial_requests.priority') }}</th>
                            <th>{{ __('financial_requests.creator') }}</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($requests as $item)
                            <tr>
                                <td><strong>{{ $item->request_no }}</strong></td>
                                <td>{{ $types[$item->type] ?? $item->type }}</td>
                                <td>{{ $item->client_name ?: '-' }}<br><small class="text-muted">{{ $item->client_identity }}</small></td>
                                <td>{{ $item->contract_number ?: '-' }}</td>
                                <td>{{ $item->amount ? number_format((float) $item->amount, 2) : '-' }}</td>
                                <td><span class="badge bg-secondary">{{ $statuses[$item->status] ?? $item->status }}</span></td>
                                <td><span class="badge bg-{{ $item->priority === 'critical' ? 'danger' : ($item->priority === 'important' ? 'warning' : 'light text-dark') }}">{{ $priorities[$item->priority] ?? $item->priority }}</span></td>
                                <td>{{ $item->creator?->name ?: '-' }}</td>
                                <td class="text-end">
                                    @if($showDeleted)
                                        <form method="POST" action="{{ route('financial-requests.restore', $item->id) }}" class="d-inline">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-success" type="submit" title="{{ __('financial_requests.restore') }}">
                                                <i class="fa fa-undo"></i> {{ __('financial_requests.restore') }}
                                            </button>
                                        </form>
                                    @else
                                        <a class="btn btn-sm btn-outline-primary" href="{{ route('financial-requests.show', $item) }}"><i class="fa fa-eye"></i></a>
                                        @if (in_array($item->status, ['preparing', 'for_approval', 'rejected'], true) && (int) $item->created_by_id === (int) auth('admin')->id() && @AdminGuardHasAnyPerm([\App\PermKeys::FINANCIAL_REQUESTS_CREATE, \App\PermKeys::FINANCIAL_REQUESTS_EDIT, \App\PermKeys::FINANCIAL_REQUESTS_CONTROL]))
                                            <a class="btn btn-sm btn-outline-dark" href="{{ route('financial-requests.edit', $item) }}" title="{{ __('financial_requests.edit') }}"><i class="fa fa-edit"></i></a>
                                        @endif
                                        @if ($canDeleteRequests && in_array($item->status, ['preparing', 'for_approval', 'rejected'], true) && ! $item->finance_bond_id)
                                            <form method="POST" action="{{ route('financial-requests.destroy', $item) }}" class="d-inline" onsubmit="return confirm(@js(__('financial_requests.delete_confirm')))" >
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger" type="submit" title="{{ __('financial_requests.delete') }}"><i class="fa fa-trash"></i></button>
                                            </form>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">{{ __('financial_requests.no_requests') }}</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($requests->hasPages())
                    <div class="card-footer d-flex justify-content-center">
                        {{ $requests->onEachSide(1)->links('pagination::bootstrap-4') }}
                    </div>
                @endif
            </div>
        </div>
    @else
        @include('components.no_permissions')
    @endif
@endsection
