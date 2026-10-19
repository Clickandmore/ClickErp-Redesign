@extends('admin.layout.indexs.index')

@section('page-title', 'تحويلات مساند')

@push('css')
<style>
    .mt-page { --mt-primary: #4f46e5; --mt-soft: #eef2ff; --mt-border: #e5e7eb; --mt-muted: #6b7280; }
    .mt-hero { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 1rem; padding: 1.25rem 1.5rem; margin-bottom: 1rem; border-radius: 16px; background: linear-gradient(135deg, #312e81, #4f46e5 60%, #0d9488); color: #fff; }
    .mt-hero h4 { margin: 0 0 .25rem; font-weight: 700; color: #fff; }
    .mt-hero p { margin: 0; opacity: .85; font-size: .9rem; }
    .mt-hero .btn-light { color: #312e81; font-weight: 600; }
    .mt-hero .btn-outline-light:hover { color: #312e81; }

    .mt-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: .75rem; margin-bottom: 1rem; }
    .mt-stat { display: flex; align-items: center; gap: .75rem; padding: .9rem 1rem; border: 1px solid var(--mt-border); border-radius: 14px; background: #fff; }
    .mt-stat-icon { width: 42px; height: 42px; flex: 0 0 42px; border-radius: 12px; display: grid; place-items: center; font-size: 1rem; }
    .mt-stat small { display: block; color: var(--mt-muted); font-size: .78rem; }
    .mt-stat b { display: block; font-size: 1.15rem; color: #111827; font-variant-numeric: tabular-nums; }
    .mt-stat b span { font-size: .72rem; font-weight: 500; color: var(--mt-muted); }

    .mt-box { border: 1px solid var(--mt-border); border-radius: 14px; background: #fff; }
    .mt-filter { padding: 1rem 1.1rem; margin-bottom: 1rem; }
    .mt-filter-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem; margin-bottom: .9rem; }
    .mt-filter-head h6 { margin: 0; font-weight: 700; color: #1f2937; }
    .mt-filter-count { margin-inline-start: .4rem; padding: .15rem .55rem; border-radius: 99px; background: var(--mt-soft); color: var(--mt-primary); font-size: .72rem; font-weight: 600; }
    .mt-search { position: relative; flex: 1 1 320px; max-width: 460px; }
    .mt-search i { position: absolute; inset-inline-start: .8rem; top: 50%; transform: translateY(-50%); color: #9ca3af; }
    .mt-search .form-control { padding-inline-start: 2.2rem; border-radius: 10px; }
    .mt-filter .form-label { font-size: .78rem; font-weight: 600; color: #4b5563; margin-bottom: .3rem; }
    .mt-filter .form-control, .mt-filter .form-select { border-radius: 10px; font-size: .88rem; }
    .mt-filter .is-set { border-color: var(--mt-primary); background-color: #fafaff; }
    .mt-presets { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .85rem; padding-top: .85rem; border-top: 1px dashed var(--mt-border); }
    .mt-presets .btn { border-radius: 99px; font-size: .78rem; padding: .25rem .8rem; }

    .mt-table thead th { background: #f9fafb; color: #4b5563; font-size: .78rem; font-weight: 600; white-space: nowrap; border-bottom: 1px solid var(--mt-border); padding: .75rem; }
    .mt-table td { padding: .75rem; font-size: .88rem; border-color: #f3f4f6; }
    .mt-table .num { font-variant-numeric: tabular-nums; white-space: nowrap; }
    .mt-id { display: inline-block; min-width: 2.4rem; padding: .15rem .45rem; border-radius: 8px; background: var(--mt-soft); color: var(--mt-primary); font-weight: 700; text-align: center; text-decoration: none; }
    .mt-ref { font-weight: 600; color: #111827; }
    .mt-sub { display: block; color: var(--mt-muted); font-size: .75rem; }
    .mt-badge { display: inline-flex; align-items: center; gap: .25rem; padding: .15rem .55rem; border-radius: 99px; font-size: .72rem; font-weight: 600; white-space: nowrap; }
    .mt-badge.single { background: #ecfdf5; color: #047857; }
    .mt-badge.bulk { background: #fff7ed; color: #c2410c; }
    .mt-badge.src { background: #f3f4f6; color: #374151; }
    .mt-badge.branch { background: #f5f3ff; color: #6d28d9; border: 1px solid #ede9fe; }
    .mt-commission { color: #b91c1c; }
    .mt-transferred { color: #047857; font-weight: 700; }
    .mt-empty { padding: 3rem 1rem; text-align: center; color: var(--mt-muted); }
    .mt-empty i { font-size: 2.2rem; color: #c7d2fe; margin-bottom: .75rem; display: block; }

    .mt-foot { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem; padding: .85rem 1rem; border-top: 1px solid var(--mt-border); background: #fcfcfd; border-radius: 0 0 14px 14px; }
    .mt-foot-info { color: var(--mt-muted); font-size: .82rem; }
    .mt-foot-info b { color: #111827; }
    .mt-pagination { margin: 0; gap: .3rem; flex-wrap: wrap; }
    .mt-pagination .page-link { min-width: 36px; height: 36px; display: grid; place-items: center; padding: 0 .6rem; border-radius: 10px !important; border: 1px solid var(--mt-border); color: #374151; font-size: .85rem; font-weight: 600; }
    .mt-pagination .page-link:hover { background: var(--mt-soft); border-color: #c7d2fe; color: var(--mt-primary); }
    .mt-pagination .page-item.active .page-link { background: var(--mt-primary); border-color: var(--mt-primary); color: #fff; box-shadow: 0 4px 12px rgba(79, 70, 229, .3); }
    .mt-pagination .page-item.disabled .page-link { background: #f9fafb; color: #d1d5db; }
</style>
@endpush

@section('content')
@php
    $set = fn (string $key) => filled($filters[$key] ?? null) ? 'is-set' : '';
    $money = fn ($value) => number_format((float) $value, 2);
    $activeFilters = collect(['q', 'date_from', 'date_to', 'bank_account_id', 'created_by_id', 'kind', 'source', 'amount_min', 'amount_max'])
        ->filter(fn ($key) => filled($filters[$key]))
        ->count() + (request()->filled('branch_id') ? 1 : 0);
    $indexUrl = route('musaned-transfers.index');
    $presetUrl = fn (string $from, string $to) => $indexUrl . '?' . http_build_query(array_filter(
        array_merge(request()->except(['page', 'date_from', 'date_to']), ['date_from' => $from, 'date_to' => $to]),
        fn ($value) => filled($value)
    ));
    $today = today();
    $datePresets = [
        'اليوم' => [$today->toDateString(), $today->toDateString()],
        'آخر 7 أيام' => [$today->copy()->subDays(6)->toDateString(), $today->toDateString()],
        'هذا الشهر' => [$today->copy()->startOfMonth()->toDateString(), $today->copy()->endOfMonth()->toDateString()],
        'الشهر الماضي' => [$today->copy()->subMonthNoOverflow()->startOfMonth()->toDateString(), $today->copy()->subMonthNoOverflow()->endOfMonth()->toDateString()],
        'هذه السنة' => [$today->copy()->startOfYear()->toDateString(), $today->copy()->endOfYear()->toDateString()],
    ];
@endphp
<div class="container-fluid mt-page" dir="rtl">
    <header class="mt-hero">
        <div>
            <h4><i class="fas fa-money-bill-transfer me-2"></i>تحويلات مساند</h4>
            <p>متابعة الحوالات المفردة والمجمعة الواردة من مساند، وربطها بالعقود وقيودها المحاسبية.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('musaned-transfers.invoices-report') }}" class="btn btn-outline-light">
                <i class="fas fa-file-invoice-dollar me-1"></i>فواتير المبيعات بالصافي
            </a>
            @if (AdminGuardHasAnyPerm([\App\PermKeys::MUSANED_TRANSFERS_CREATE, \App\PermKeys::MUSANED_TRANSFERS_CONTROL, \App\PermKeys::REC_CONTRACTS_CREATE, \App\PermKeys::REC_CONTRACTS_MANAGE]))
                <a href="{{ route('musaned-transfers.create') }}" class="btn btn-light">
                    <i class="fas fa-plus me-1"></i>إضافة تحويل مساند
                </a>
            @endif
        </div>
    </header>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @include('admin.hourly._filter-loader', ['id' => 'mt-filter-loader', 'title' => 'جارٍ تحديث التحويلات'])
    <p id="mt-filter-status" class="alert" role="alert" hidden></p>

    <div id="mt-results" class="hx-results" data-ajax-filter data-ajax-loader="mt-filter-loader" data-ajax-status="mt-filter-status">
        <div class="mt-stats">
            <div class="mt-stat">
                <span class="mt-stat-icon" style="background:#eef2ff;color:#4f46e5"><i class="fas fa-receipt"></i></span>
                <div><small>عدد التحويلات</small><b>{{ number_format((int) $summary->transfers_count) }} <span>· {{ number_format((int) $summary->items_count) }} بند</span></b></div>
            </div>
            <div class="mt-stat">
                <span class="mt-stat-icon" style="background:#f0f9ff;color:#0369a1"><i class="fas fa-user-tag"></i></span>
                <div><small>إجمالي ما دفعه العملاء</small><b>{{ $money($summary->customer_paid_total) }} <span>ر.س</span></b></div>
            </div>
            <div class="mt-stat">
                <span class="mt-stat-icon" style="background:#ecfdf5;color:#047857"><i class="fas fa-building-columns"></i></span>
                <div><small>المحوّل من مساند</small><b>{{ $money($summary->transferred_total) }} <span>ر.س</span></b></div>
            </div>
            <div class="mt-stat">
                <span class="mt-stat-icon" style="background:#fef2f2;color:#b91c1c"><i class="fas fa-percent"></i></span>
                <div><small>إجمالي العمولات</small><b>{{ $money($summary->commission_total) }} <span>ر.س</span></b></div>
            </div>
        </div>

        <form method="GET" action="{{ $indexUrl }}" class="mt-box mt-filter" role="search" aria-label="فلترة تحويلات مساند" data-ajax-form>
            <div class="mt-filter-head">
                <h6>
                    <i class="fas fa-sliders me-1"></i>فلترة التحويلات
                    @if ($activeFilters)<span class="mt-filter-count">{{ $activeFilters }} فلتر مفعّل</span>@endif
                </h6>
                <div class="mt-search">
                    <i class="fas fa-magnifying-glass"></i>
                    <input type="search" class="form-control {{ $set('q') }}" name="q" maxlength="100" value="{{ $filters['q'] }}"
                        placeholder="رقم التحويل، المرجع، العميل، الجوال، رقم العقد أو إنجاز ثم Enter" aria-label="بحث">
                </div>
            </div>

            <div class="row g-3 align-items-end">
                @include('admin.partials.branch-filter', ['id' => 'branchFilter', 'containerClass' => 'col-6 col-md-4 col-xl-2'])
                <div class="col-6 col-md-4 col-xl-2">
                    <label class="form-label" for="mt-date-from">من تاريخ</label>
                    <input type="date" class="form-control {{ $set('date_from') }}" id="mt-date-from" name="date_from" value="{{ $filters['date_from'] }}" data-auto-apply>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <label class="form-label" for="mt-date-to">إلى تاريخ</label>
                    <input type="date" class="form-control {{ $set('date_to') }}" id="mt-date-to" name="date_to" value="{{ $filters['date_to'] }}" data-auto-apply>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <label class="form-label" for="mt-bank">البنك</label>
                    <select class="form-select {{ $set('bank_account_id') }}" id="mt-bank" name="bank_account_id" data-auto-apply>
                        <option value="">كل البنوك</option>
                        @foreach ($bankAccounts as $account)
                            <option value="{{ $account->id }}" {{ ($filters['bank_account_id'] === $account->id) ? "selected" : "" }}>{{ $account->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <label class="form-label" for="mt-kind">نوع الحوالة</label>
                    <select class="form-select {{ $set('kind') }}" id="mt-kind" name="kind" data-auto-apply>
                        <option value="">الكل</option>
                        <option value="single" {{ ($filters['kind'] === 'single') ? "selected" : "" }}>مفردة (بند واحد)</option>
                        <option value="bulk" {{ ($filters['kind'] === 'bulk') ? "selected" : "" }}>مجمعة (أكثر من بند)</option>
                    </select>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <label class="form-label" for="mt-source">مصدر البنود</label>
                    <select class="form-select {{ $set('source') }}" id="mt-source" name="source" data-auto-apply>
                        <option value="">الكل</option>
                        <option value="contract" {{ ($filters['source'] === 'contract') ? "selected" : "" }}>عقود استقدام</option>
                        <option value="delegation" {{ ($filters['source'] === 'delegation') ? "selected" : "" }}>تفويضات إنجاز</option>
                    </select>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <label class="form-label" for="mt-creator">أنشأها</label>
                    <select class="form-select {{ $set('created_by_id') }}" id="mt-creator" name="created_by_id" data-auto-apply>
                        <option value="">كل الموظفين</option>
                        @foreach ($creators as $creator)
                            <option value="{{ $creator->id }}" {{ ($filters['created_by_id'] === $creator->id) ? "selected" : "" }}>{{ $creator->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <label class="form-label" for="mt-amount-min">المحوّل من (ر.س)</label>
                    <input type="number" step="0.01" min="0" class="form-control {{ $set('amount_min') }}" id="mt-amount-min" name="amount_min" value="{{ $filters['amount_min'] }}" placeholder="0.00">
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <label class="form-label" for="mt-amount-max">المحوّل إلى (ر.س)</label>
                    <input type="number" step="0.01" min="0" class="form-control {{ $set('amount_max') }}" id="mt-amount-max" name="amount_max" value="{{ $filters['amount_max'] }}" placeholder="0.00">
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <label class="form-label" for="mt-sort">الترتيب</label>
                    <select class="form-select" id="mt-sort" name="sort" data-auto-apply>
                        <option value="latest" {{ ($filters['sort'] === 'latest') ? "selected" : "" }}>الأحدث أولاً</option>
                        <option value="oldest" {{ ($filters['sort'] === 'oldest') ? "selected" : "" }}>الأقدم أولاً</option>
                        <option value="amount_desc" {{ ($filters['sort'] === 'amount_desc') ? "selected" : "" }}>الأعلى مبلغاً</option>
                        <option value="amount_asc" {{ ($filters['sort'] === 'amount_asc') ? "selected" : "" }}>الأقل مبلغاً</option>
                        <option value="items_desc" {{ ($filters['sort'] === 'items_desc') ? "selected" : "" }}>الأكثر بنوداً</option>
                    </select>
                </div>
                <div class="col-12 col-md-4 col-xl-2 d-flex gap-2">
                    <button class="btn btn-primary flex-fill" type="submit"><i class="fas fa-filter me-1"></i>تطبيق</button>
                    @if ($activeFilters)
                        <a href="{{ $indexUrl }}" class="btn btn-outline-secondary" data-ajax-link title="مسح الفلاتر"><i class="fas fa-rotate-left"></i></a>
                    @endif
                </div>
            </div>
            <input type="hidden" name="per_page" value="{{ $filters['per_page'] }}">

            <div class="mt-presets">
                <span class="text-muted small align-self-center me-1"><i class="far fa-calendar me-1"></i>فترة سريعة:</span>
                @foreach ($datePresets as $label => [$from, $to])
                    @php $active = $filters['date_from'] === $from && $filters['date_to'] === $to; @endphp
                    <a href="{{ $presetUrl($from, $to) }}" class="btn {{ $active ? 'btn-primary' : 'btn-outline-primary' }}" data-ajax-link>{{ $label }}</a>
                @endforeach
            </div>
        </form>

        <div class="mt-box">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 mt-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>التاريخ</th>
                            <th>المرجع / العملاء</th>
                            <th>النوع</th>
                            <th>إجمالي العميل</th>
                            <th>المحوّل من مساند</th>
                            <th>العمولة</th>
                            <th>البنك</th>
                            <th>أنشأها</th>
                            <th class="text-end">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($transfers as $transfer)
                            @php
                                $customers = $transfer->items
                                    ->map(fn ($item) => optional(optional($item->contract)->user)->name ?? optional(optional($item->ingazDelegation)->user)->name)
                                    ->filter()->unique()->values();
                                $hasContracts = $transfer->items->contains(fn ($item) => $item->contract_id);
                                $hasDelegations = $transfer->items->contains(fn ($item) => $item->ingaz_delegation_id);
                                $isBulk = (int) $transfer->contracts_count > 1;
                            @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('musaned-transfers.show', $transfer->id) }}" class="mt-id">{{ $transfer->id }}</a>
                                    @if ($branchesEnabled)
                                        <span class="d-block mt-1">
                                            @if ($transfer->branch)
                                                <span class="mt-badge branch" title="{{ $transfer->branch->code ? 'رمز الفرع: ' . $transfer->branch->code : 'الفرع' }}"><i class="fas fa-code-branch"></i>{{ $transfer->branch->name }}</span>
                                            @else
                                                <span class="mt-badge src" title="غير مرتبط بفرع، ويظهر لكل الفروع"><i class="fas fa-globe"></i>عام</span>
                                            @endif
                                        </span>
                                    @endif
                                </td>
                                <td class="num">
                                    {{ optional($transfer->transfer_date)->format('Y-m-d') }}
                                    <span class="mt-sub">{{ optional($transfer->transfer_date)->translatedFormat('l') }}</span>
                                </td>
                                <td style="min-width: 200px">
                                    <span class="mt-ref">{{ $transfer->reference_number ?: 'بدون مرجع' }}</span>
                                    <span class="mt-sub">
                                        @if ($customers->isNotEmpty())
                                            <i class="far fa-user me-1"></i>{{ $customers->first() }}@if ($customers->count() > 1) <span class="text-primary">+{{ $customers->count() - 1 }}</span>@endif
                                        @else
                                            —
                                        @endif
                                    </span>
                                </td>
                                <td>
                                    <span class="mt-badge {{ $isBulk ? 'bulk' : 'single' }}">
                                        <i class="fas {{ $isBulk ? 'fa-layer-group' : 'fa-file' }}"></i>{{ $isBulk ? 'مجمعة' : 'مفردة' }} · {{ $transfer->contracts_count }}
                                    </span>
                                    <span class="d-block mt-1">
                                        @if ($hasContracts)<span class="mt-badge src">عقود</span>@endif
                                        @if ($hasDelegations)<span class="mt-badge src">إنجاز</span>@endif
                                    </span>
                                </td>
                                <td class="num">{{ $money($transfer->customer_paid_total) }}</td>
                                <td class="num mt-transferred">{{ $money($transfer->transferred_total) }}</td>
                                <td class="num mt-commission">{{ $money($transfer->commission_total) }}</td>
                                <td>{{ $transfer->bankAccount->name ?? '-' }}</td>
                                <td>
                                    {{ $transfer->createdBy->name ?? '-' }}
                                    <span class="mt-sub">{{ optional($transfer->created_at)->format('Y-m-d H:i') }}</span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('musaned-transfers.show', $transfer->id) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye me-1"></i>عرض
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="mt-empty">
                                    <i class="fas fa-inbox"></i>
                                    @if ($activeFilters)
                                        لا توجد تحويلات مطابقة للفلاتر المختارة.
                                        <div class="mt-2"><a href="{{ $indexUrl }}" class="btn btn-sm btn-outline-secondary" data-ajax-link>مسح الفلاتر</a></div>
                                    @else
                                        لا توجد تحويلات مساند مسجلة حالياً.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($transfers->total() > 0)
                <div class="mt-foot">
                    <div class="mt-foot-info">
                        عرض <b>{{ $transfers->firstItem() }}</b>–<b>{{ $transfers->lastItem() }}</b> من <b>{{ number_format($transfers->total()) }}</b> تحويل
                        <span class="ms-2">· لكل صفحة:</span>
                        @foreach ([20, 50, 100] as $size)
                            <a href="{{ $indexUrl . '?' . http_build_query(array_merge(request()->except(['page', 'per_page']), ['per_page' => $size])) }}" data-ajax-link
                               class="badge rounded-pill text-decoration-none {{ $filters['per_page'] === $size ? 'bg-primary' : 'bg-light text-dark border' }}">{{ $size }}</a>
                        @endforeach
                    </div>
                    {{ $transfers->onEachSide(1)->links('admin.ingaz.musaned_transfers._pagination') }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('js')
<script src="{{ asset('admin/assets/js/hourly-ajax-filter.js') }}?v={{ filemtime(public_path('admin/assets/js/hourly-ajax-filter.js')) }}"></script>
@endpush
