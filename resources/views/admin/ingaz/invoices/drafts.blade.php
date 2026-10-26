@extends('admin.layout.indexs.index')
@section('page-title', 'طلبات اعتماد الفواتير')

@php
    $statusMeta = [
        'pending' => ['label' => 'بانتظار الاعتماد', 'class' => 'warning', 'icon' => 'fa-hourglass-half'],
        'approved' => ['label' => 'معتمدة', 'class' => 'success', 'icon' => 'fa-circle-check'],
        'returned' => ['label' => 'معادة للتصحيح', 'class' => 'danger', 'icon' => 'fa-rotate-left'],
    ];
    $activeStatus = $filters['status'] ?? null;
    $totalCount = $statusCounts->sum();
    $hasFilters = collect($filters)->except('status')->filter(fn ($value) => $value !== null && $value !== '')->isNotEmpty();
    $statusUrl = fn ($status) => route('invoice-drafts.index', array_filter(array_merge(request()->except(['page', 'status']), ['status' => $status])));
    $hiddenKeys = ['contract_id', 'ingaz_delegation_id', 'client_id', 'line_items', 'subtotal', 'discount', 'vat_amount', 'total_due', 'invoice_date'];
    if (!\App\Support\InvoiceAuthorization::canViewSupplierTotalCost()) $hiddenKeys[] = 'supplier_total_cost_usd';
    $formatValue = function ($value) {
        if (is_bool($value)) return $value ? 'نعم' : 'لا';
        if (is_array($value)) return json_encode($value, JSON_UNESCAPED_UNICODE);
        if (is_numeric($value) && str_contains((string) $value, '.')) return number_format((float) $value, 2);
        return $value;
    };
@endphp

@section('style')
    <style>
        .drafts-page .drafts-title { margin: 0; font-size: 22px; font-weight: 900; }
        .drafts-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 12px; margin-bottom: 16px; }
        .drafts-stat { display: flex; align-items: center; gap: 12px; padding: 14px 16px; border-radius: 12px; border: 1px solid var(--bs-border-color, #e5e7eb); background: var(--bs-body-bg, #fff); color: inherit; text-decoration: none; transition: box-shadow .15s, border-color .15s; }
        .drafts-stat:hover { box-shadow: 0 4px 14px rgba(0, 0, 0, .06); color: inherit; }
        .drafts-stat.active { border-color: var(--bs-primary, #0d6efd); box-shadow: 0 0 0 2px rgba(13, 110, 253, .15); }
        .drafts-stat .stat-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; }
        .drafts-stat .stat-count { font-size: 22px; font-weight: 900; line-height: 1; }
        .drafts-stat .stat-label { font-size: 13px; color: var(--bs-secondary-color, #6c757d); }
        .draft-card { border-radius: 12px; border-inline-start: 4px solid transparent; }
        .draft-card.status-pending { border-inline-start-color: var(--bs-warning, #ffc107); }
        .draft-card.status-approved { border-inline-start-color: var(--bs-success, #198754); }
        .draft-card.status-returned { border-inline-start-color: var(--bs-danger, #dc3545); }
        .draft-meta { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 10px 16px; }
        .draft-meta .meta-label { font-size: 12px; color: var(--bs-secondary-color, #6c757d); }
        .draft-meta .meta-value { font-weight: 700; word-break: break-word; }
        .draft-total { font-size: 20px; font-weight: 900; }
        .draft-details th { width: 35%; font-size: 13px; white-space: nowrap; }
        .draft-details td { font-size: 13px; }
        .draft-actions { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 8px; }
        .draft-return-form { display: flex; gap: 8px; flex: 1 1 320px; }
    </style>
@endsection

@section('content')
<div class="container-fluid py-3 py-lg-4 drafts-page" dir="rtl">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h4 class="drafts-title">طلبات اعتماد الفواتير</h4>
            <small class="text-muted">مراجعة مسودات الفواتير واعتمادها أو إرجاعها للتصحيح.</small>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if(\App\Support\InvoiceAuthorization::canView())
                <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary"><i class="fas fa-file-invoice me-1"></i> الفواتير</a>
            @endif
            @if(\App\Support\InvoiceAuthorization::canCreate())
                <a href="{{ route('invoices.create') }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i> طلب جديد</a>
            @endif
        </div>
    </div>

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if($errors->any()) <div class="alert alert-danger">{{ $errors->first() }}</div> @endif

    <div class="drafts-stats">
        <a href="{{ $statusUrl(null) }}" class="drafts-stat {{ $activeStatus ? '' : 'active' }}">
            <span class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="fas fa-layer-group"></i></span>
            <span><span class="stat-count d-block">{{ $totalCount }}</span><span class="stat-label">كل الطلبات</span></span>
        </a>
        @foreach($statusMeta as $status => $meta)
            <a href="{{ $statusUrl($status) }}" class="drafts-stat {{ $activeStatus === $status ? 'active' : '' }}">
                <span class="stat-icon bg-{{ $meta['class'] }} bg-opacity-10 text-{{ $meta['class'] }}"><i class="fas {{ $meta['icon'] }}"></i></span>
                <span><span class="stat-count d-block">{{ $statusCounts[$status] ?? 0 }}</span><span class="stat-label">{{ $meta['label'] }}</span></span>
            </a>
        @endforeach
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('invoice-drafts.index') }}" class="row g-3 align-items-end">
                @if($activeStatus)<input type="hidden" name="status" value="{{ $activeStatus }}">@endif
                <div class="col-md-6 col-lg-3">
                    <label for="draftSearch" class="form-label fw-bold">بحث</label>
                    <input type="text" id="draftSearch" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="رقم الطلب / العقد / اسم العميل أو جواله">
                </div>
                @if($creators->isNotEmpty())
                    <div class="col-md-6 col-lg-2">
                        <label for="draftCreator" class="form-label">أنشأه</label>
                        <select id="draftCreator" name="created_by" class="form-select">
                            <option value="">الكل</option>
                            @foreach($creators as $creator)
                                <option value="{{ $creator->id }}" {{ (string) ($filters['created_by'] ?? '') === (string) $creator->id ? 'selected' : '' }}>{{ $creator->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                @if($branchesEnabled && $branches->isNotEmpty())
                    <div class="col-md-6 col-lg-2">
                        <label for="draftBranch" class="form-label">{{ __('admin.branches') }}</label>
                        <select id="draftBranch" name="branch_id" class="form-select">
                            <option value="">الكل</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ (string) ($filters['branch_id'] ?? '') === (string) $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-3 col-lg-2">
                    <label for="draftDateFrom" class="form-label">من تاريخ</label>
                    <input type="date" id="draftDateFrom" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-control">
                </div>
                <div class="col-md-3 col-lg-2">
                    <label for="draftDateTo" class="form-label">إلى تاريخ</label>
                    <input type="date" id="draftDateTo" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-control">
                </div>
                <div class="col-auto d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-filter me-1"></i> تصفية</button>
                    @if($hasFilters || $activeStatus)
                        <a href="{{ route('invoice-drafts.index') }}" class="btn btn-outline-secondary"><i class="fas fa-rotate-left me-1"></i> مسح</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    @forelse($drafts as $draft)
        @php
            $meta = $statusMeta[$draft->status] ?? ['label' => $draft->status, 'class' => 'secondary', 'icon' => 'fa-circle'];
            $payload = $draft->payload ?? [];
            $clientName = $draft->contract?->user?->name ?? $draft->delegation?->user?->name ?? $draft->client?->name ?? '—';
            $details = collect($payload)->except($hiddenKeys)->reject(fn ($value) => $value === null || $value === '' || $value === []);
            $lineItems = is_array($payload['line_items'] ?? null) ? $payload['line_items'] : [];
        @endphp
        <div class="card mb-3 draft-card status-{{ $draft->status }}">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
                    <div>
                        <h5 class="mb-1 fw-bold">طلب #{{ $draft->id }}</h5>
                        <span class="badge bg-{{ $meta['class'] }} {{ $meta['class'] === 'warning' ? 'text-dark' : '' }}"><i class="fas {{ $meta['icon'] }} me-1"></i>{{ $meta['label'] }}</span>
                    </div>
                    @isset($payload['total_due'])
                        <div class="text-end">
                            <div class="meta-label text-muted small">الإجمالي المستحق</div>
                            <div class="draft-total">{{ number_format((float) $payload['total_due'], 2) }} <small class="fs-6 text-muted">ر.س</small></div>
                        </div>
                    @endisset
                </div>

                <div class="draft-meta mb-3">
                    <div><div class="meta-label">العميل</div><div class="meta-value">{{ $clientName }}</div></div>
                    @if(!empty($payload['contract_id']))<div><div class="meta-label">رقم العقد</div><div class="meta-value">{{ $payload['contract_id'] }}</div></div>@endif
                    @if(!empty($payload['ingaz_delegation_id']))<div><div class="meta-label">رقم التفويض</div><div class="meta-value">{{ $payload['ingaz_delegation_id'] }}</div></div>@endif
                    @if(!empty($payload['invoice_date']))<div><div class="meta-label">تاريخ الفاتورة</div><div class="meta-value">{{ $payload['invoice_date'] }}</div></div>@endif
                    <div><div class="meta-label">أنشأه</div><div class="meta-value">{{ $draft->creator?->name ?? $draft->created_by }}</div></div>
                    <div><div class="meta-label">تاريخ الطلب</div><div class="meta-value">{{ $draft->created_at?->format('Y-m-d H:i') }}</div></div>
                    @if($branchesEnabled)<div><div class="meta-label">الفرع</div><div class="meta-value">{{ $draft->branch?->name ?? '—' }}</div></div>@endif
                    @if(isset($payload['subtotal']))<div><div class="meta-label">قبل الضريبة والخصم</div><div class="meta-value">{{ number_format((float) $payload['subtotal'], 2) }}</div></div>@endif
                    @if(!empty($payload['discount']))<div><div class="meta-label">الخصم</div><div class="meta-value">{{ number_format((float) $payload['discount'], 2) }}</div></div>@endif
                    @if(isset($payload['vat_amount']))<div><div class="meta-label">الضريبة</div><div class="meta-value">{{ number_format((float) $payload['vat_amount'], 2) }}</div></div>@endif
                </div>

                @if($draft->review_note)
                    <div class="alert alert-danger py-2 mb-3"><strong>سبب الإرجاع:</strong> {{ $draft->review_note }}</div>
                @endif
                @if($draft->reviewed_at)
                    <p class="small text-muted mb-3"><i class="fas fa-user-check me-1"></i> راجعه: {{ $draft->reviewer?->name ?? $draft->reviewed_by }} — {{ $draft->reviewed_at->format('Y-m-d H:i') }}</p>
                @endif

                @if($details->isNotEmpty() || $lineItems)
                    <button class="btn btn-sm btn-light border mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#draftDetails{{ $draft->id }}" aria-expanded="false">
                        <i class="fas fa-list me-1"></i> تفاصيل الطلب
                    </button>
                    <div class="collapse" id="draftDetails{{ $draft->id }}">
                        @if($lineItems)
                            <div class="table-responsive mb-3">
                                <table class="table table-sm table-bordered draft-details mb-0">
                                    <thead class="table-light"><tr><th>{{ __('invoice_drafts.fields.line_items') }}</th><th>الكمية</th><th>سعر الوحدة</th><th>خاضع للضريبة</th></tr></thead>
                                    <tbody>
                                    @foreach($lineItems as $item)
                                        <tr>
                                            <td>{{ $item['label'] ?? $item['key'] ?? '—' }}</td>
                                            <td>{{ $item['quantity'] ?? '—' }}</td>
                                            <td>{{ isset($item['unit_price']) ? number_format((float) $item['unit_price'], 2) : '—' }}</td>
                                            <td>{{ !empty($item['taxable']) ? 'نعم' : 'لا' }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                        @if($details->isNotEmpty())
                            <div class="table-responsive mb-3">
                                <table class="table table-sm table-bordered draft-details mb-0"><tbody>
                                @foreach($details as $key => $value)
                                    <tr><th class="table-light">{{ __('invoice_drafts.fields.'.$key) }}</th><td>{{ $formatValue($value) }}</td></tr>
                                @endforeach
                                </tbody></table>
                            </div>
                        @endif
                    </div>
                @endif

                <div class="draft-actions">
                    @if($draft->status === 'returned' && \App\Support\InvoiceAuthorization::canCreate())
                        <a class="btn btn-primary" href="{{ route('invoices.create', array_intersect_key($payload, array_flip(['contract_id', 'ingaz_delegation_id', 'client_id']))) }}"><i class="fas fa-pen me-1"></i> إنشاء طلب مصحح</a>
                    @endif
                    @if($draft->status === 'approved' && $draft->invoice_id && \App\Support\InvoiceAuthorization::canView())
                        <a class="btn btn-outline-primary" href="{{ route('invoices.show', $draft->invoice_id) }}"><i class="fas fa-eye me-1"></i> عرض الفاتورة</a>
                    @endif
                    @if($draft->status === 'pending' && \App\Support\InvoiceAuthorization::canApprove())
                        <form method="POST" action="{{ route('invoice-drafts.approve', $draft->id) }}" class="js-draft-approve" data-draft-id="{{ $draft->id }}" data-total="{{ isset($payload['total_due']) ? number_format((float) $payload['total_due'], 2) : '' }}">
                            @csrf
                            <button class="btn btn-success"><i class="fas fa-check me-1"></i> اعتماد وإصدار الفاتورة</button>
                        </form>
                        <form method="POST" action="{{ route('invoice-drafts.return', $draft->id) }}" class="draft-return-form js-draft-return" data-draft-id="{{ $draft->id }}">
                            @csrf
                            <label for="note-{{ $draft->id }}" class="visually-hidden">سبب الإرجاع</label>
                            <input id="note-{{ $draft->id }}" name="review_note" required maxlength="2000" class="form-control" placeholder="سبب الإرجاع">
                            <button class="btn btn-outline-warning text-nowrap"><i class="fas fa-rotate-left me-1"></i> إرجاع للتصحيح</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="card"><div class="card-body text-center text-muted py-5">
            <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
            {{ ($hasFilters || $activeStatus) ? 'لا توجد طلبات مطابقة للفلاتر.' : 'لا توجد طلبات.' }}
        </div></div>
    @endforelse

    <div class="mt-3">{{ $drafts->links() }}</div>
</div>
@endsection

@section('js')
<script>
    (function () {
        if (typeof Swal === 'undefined') return;

        const confirmSubmit = (form, options) => {
            form.addEventListener('submit', function (event) {
                if (form.dataset.confirmed === '1') return;
                event.preventDefault();
                Swal.fire(Object.assign({
                    icon: 'question',
                    showCancelButton: true,
                    cancelButtonText: 'إلغاء',
                    reverseButtons: true,
                    focusCancel: true,
                }, options(form))).then(function (result) {
                    if (!result.isConfirmed) return;
                    form.dataset.confirmed = '1';
                    form.querySelectorAll('button').forEach(button => button.disabled = true);
                    form.submit();
                });
            });
        };

        document.querySelectorAll('.js-draft-approve').forEach(form => confirmSubmit(form, form => ({
            title: 'اعتماد الطلب #' + form.dataset.draftId + '؟',
            html: 'سيتم إصدار الفاتورة وترحيل قيودها المحاسبية.' + (form.dataset.total ? '<br><strong>الإجمالي المستحق: ' + form.dataset.total + ' ر.س</strong>' : ''),
            confirmButtonText: 'نعم، اعتماد وإصدار',
            confirmButtonColor: '#198754',
        })));

        document.querySelectorAll('.js-draft-return').forEach(form => confirmSubmit(form, form => ({
            icon: 'warning',
            title: 'إرجاع الطلب #' + form.dataset.draftId + ' للتصحيح؟',
            text: 'سبب الإرجاع: ' + form.querySelector('[name="review_note"]').value,
            confirmButtonText: 'نعم، إرجاع',
            confirmButtonColor: '#f0ad4e',
        })));
    })();
</script>
@endsection
