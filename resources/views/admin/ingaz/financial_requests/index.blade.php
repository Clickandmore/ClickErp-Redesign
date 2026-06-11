@extends('click.layout.indexs.index')

@section('content')
    @if (@AdminGuardHasAnyPerm([\App\PermKeys::FINANCIAL_REQUESTS_VIEW, \App\PermKeys::FINANCIAL_REQUESTS_CREATE, \App\PermKeys::FINANCIAL_REQUESTS_CONTROL]))
        <div class="container-fluid">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                    <h3 class="mb-1">إدارة الطلبات المالية</h3>
                    <div class="text-muted">طلبات مالية تشغيلية مرتبطة بالعملاء والعاملات والعقود.</div>
                </div>
                <div class="d-flex gap-2">
                    @if (@AdminGuardHasAnyPerm([\App\PermKeys::FINANCIAL_REQUESTS_EXPORT, \App\PermKeys::FINANCIAL_REQUESTS_CONTROL]))
                        <a class="btn btn-outline-success" href="{{ route('financial-requests.export', request()->query()) }}">
                            <i class="fa fa-file-excel"></i> Excel
                        </a>
                        <a class="btn btn-outline-dark" target="_blank" href="{{ route('financial-requests.print', request()->query()) }}">
                            <i class="fa fa-print"></i> طباعة
                        </a>
                    @endif
                    @if (@AdminGuardHasAnyPerm([\App\PermKeys::FINANCIAL_REQUESTS_CREATE, \App\PermKeys::FINANCIAL_REQUESTS_CONTROL]))
                        <a class="btn btn-primary" href="{{ route('financial-requests.create') }}">
                            <i class="fa fa-plus"></i> طلب جديد
                        </a>
                    @endif
                </div>
            </div>

            @php
                $activeScope = request('scope', 'all');
                $scopeQuery = request()->except(['page', 'scope', 'status']);
            @endphp
            <div class="d-flex flex-wrap gap-2 mb-3">
                <a class="btn {{ $activeScope === 'all' ? 'btn-dark' : 'btn-outline-dark' }}"
                    href="{{ route('financial-requests.index', $scopeQuery) }}">
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
            </div>

            <form class="card mb-3" method="GET">
                <div class="card-body">
                    @if (in_array(request('scope'), ['incomplete', 'paid'], true))
                        <input type="hidden" name="scope" value="{{ request('scope') }}">
                    @endif
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">بحث</label>
                            <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="رقم عقد، هوية، عميل، رقم طلب">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">نوع الطلب</label>
                            <select name="type" class="form-select">
                                <option value="">الكل</option>
                                @foreach ($types as $key => $label)
                                    <option value="{{ $key }}" @selected(request('type') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">الحالة</label>
                            <select name="status" class="form-select">
                                <option value="">الكل</option>
                                @foreach ($statuses as $key => $label)
                                    <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">التصعيد</label>
                            <select name="priority" class="form-select">
                                <option value="">الكل</option>
                                @foreach ($priorities as $key => $label)
                                    <option value="{{ $key }}" @selected(request('priority') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">التاريخ</label>
                            <input type="date" name="date" value="{{ request('date') }}" class="form-control">
                        </div>
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
                            <th>رقم الطلب</th>
                            <th>النوع</th>
                            <th>العميل</th>
                            <th>رقم العقد</th>
                            <th>المبلغ</th>
                            <th>الحالة</th>
                            <th>التصعيد</th>
                            <th>مقدم الطلب</th>
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
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('financial-requests.show', $item) }}"><i class="fa fa-eye"></i></a>
                                    @if ($item->status !== 'paid' && @AdminGuardHasAnyPerm([\App\PermKeys::FINANCIAL_REQUESTS_EDIT, \App\PermKeys::FINANCIAL_REQUESTS_CONTROL]))
                                        <a class="btn btn-sm btn-outline-dark" href="{{ route('financial-requests.edit', $item) }}"><i class="fa fa-edit"></i></a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">لا توجد طلبات مالية.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer">{{ $requests->links() }}</div>
            </div>
        </div>
    @else
        @include('components.no_permissions')
    @endif
@endsection
