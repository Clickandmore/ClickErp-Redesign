@extends('click.layout.indexs.index')

@section('style')
    <link rel="stylesheet" href="{{ asset('admin') }}/assets/cssbundle/dataTables.min.css">
    <link rel="stylesheet" href="{{ asset('admin') }}/assets/cssbundle/dropify.min.css">
    <style>
        .sarf-page-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 16px;
        }
        .sarf-page-toolbar h5 { margin: 0; font-weight: 700; }
        .sarf-page-toolbar p { margin: 3px 0 0; color: #6c757d; font-size: 13px; }
        .sarf-filter-card, .sarf-table-card {
            border: 0;
            border-radius: 14px;
            box-shadow: 0 4px 18px rgba(28, 39, 60, .06);
        }
        .sarf-filter-card .card-header, .sarf-table-card .card-header {
            background: #fff;
            border-bottom: 1px solid #edf0f4;
            padding: 15px 18px;
        }
        .sarf-filter-card .form-label {
            color: #4b5563;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 6px;
        }
        .sarf-filter-card .form-control, .sarf-filter-card .select2-container .select2-selection {
            min-height: 40px;
            border-color: #e1e6ed;
        }
        .sarf-filter-actions { display: flex; align-items: end; gap: 8px; height: 100%; }
        .sarf-summary-card {
            border: 1px solid #edf0f4;
            border-radius: 12px;
            background: #fff;
            padding: 14px 16px;
            height: 100%;
        }
        .sarf-summary-card .summary-label { color: #6c757d; font-size: 12px; }
        .sarf-summary-card .summary-value { color: #172033; font-size: 20px; font-weight: 800; margin-top: 5px; }
        .sarf-summary-card .summary-compare { color: #8a94a3; font-size: 11px; margin-top: 3px; }
        .sarf-summary-card .summary-icon {
            width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center;
            border-radius: 10px; color: #246bfd; background: rgba(36, 107, 253, .09);
        }
        #dataTableObject th, #dataTableObject td { vertical-align: middle; }
        #dataTableObject thead th { background: #f8fafc; color: #4b5563; font-size: 12px; white-space: nowrap; }
        .sarf-voucher-number, .sarf-account { display: flex; flex-direction: column; gap: 3px; }
        .sarf-voucher-number a { font-size: 15px; font-weight: 800; text-decoration: none; }
        .sarf-voucher-number small, .sarf-account small { color: #8a94a3; font-size: 11px; }
        .sarf-account { min-width: 210px; }
        .sarf-account strong { color: #273142; white-space: normal; line-height: 1.5; }
        .sarf-account small { max-width: 270px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .sarf-date { display:flex; flex-direction:column; gap:3px; white-space:nowrap; }
        .sarf-date small { color:#8a94a3; font-size:11px; }
        .sarf-amount { white-space: nowrap; }
        .sarf-amount { color: #111827; font-size: 14px; }
        .sarf-status { display: inline-flex; align-items: center; gap: 5px; padding: 7px 9px; white-space: nowrap; }
        .sarf-constraint-link { white-space: nowrap; }
        .sarf-actions { display: flex; align-items: center; gap: 4px; white-space: nowrap; }
        .sarf-actions .btn { margin: 0 !important; }
        .sarf-table-card .dataTables_wrapper { padding: 0 16px 16px; }
        .sarf-table-card .dataTables_filter { margin-top: 14px; }
        @media (max-width: 767px) {
            .sarf-page-toolbar { align-items: flex-start; flex-direction: column; }
            .sarf-filter-actions { padding-top: 4px; }
        }
    </style>
@endsection

@section('page-title', $bladeTitle)

@section('page-links')
    <li class="breadcrumb-item active" aria-current="page">{{ $bladeTitle }}</li>
@endsection

@section('content')
    <div class="sarf-page-toolbar">
        <div>
            <h5>إدارة سندات الصرف</h5>
            <p>ابحث وراجع السند والقيد المحاسبي المرتبط به من مكان واحد.</p>
        </div>
        {!! addButton($createRoute, $addButtonText) !!}
    </div>

    <div class="row g-3 mb-3">
        @php
            $summaries = [
                ['label' => __('finance::finance.yearly'), 'value' => $results['year']->total_value, 'compare' => __('finance::finance.last_year').' '.number_format($results['last_year']->total_value, 2), 'icon' => 'fa-calendar'],
                ['label' => __('finance::finance.quarterly'), 'value' => $results['quarterYearData']->total_value, 'compare' => __('finance::finance.last_quarterly').' '.number_format($results['last_quarterYearData']->total_value, 2), 'icon' => 'fa-calendar-check'],
                ['label' => __('finance::finance.monthly'), 'value' => $results['month']->total_value, 'compare' => __('finance::finance.last_month').' '.number_format($results['last_month']->total_value, 2), 'icon' => 'fa-calendar-alt'],
                ['label' => __('finance::finance.daily'), 'value' => $results['day']->total_value, 'compare' => __('finance::finance.last_day').' '.number_format($results['last_day']->total_value, 2), 'icon' => 'fa-calendar-day'],
            ];
        @endphp
        @foreach($summaries as $summary)
            <div class="col-xl-3 col-md-6">
                <div class="sarf-summary-card">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <div class="summary-label">{{ $summary['label'] }}</div>
                            <div class="summary-value" dir="ltr">{{ number_format($summary['value'], 2) }}</div>
                        </div>
                        <span class="summary-icon"><i class="fa {{ $summary['icon'] }}"></i></span>
                    </div>
                    <div class="summary-compare">{{ $summary['compare'] }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card sarf-filter-card mb-3">
        <div class="card-header d-flex align-items-center justify-content-between">
            <strong><i class="fa fa-filter text-primary me-1"></i> تصفية السندات</strong>
            <button type="button" class="btn btn-sm btn-light border" data-bs-toggle="collapse" data-bs-target="#sarfFilters" aria-expanded="true">
                <i class="fa fa-chevron-up"></i>
            </button>
        </div>
        <div class="collapse show" id="sarfFilters">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-xl-3 col-md-6">
                        <label class="form-label" for="bond_search">بحث سريع</label>
                        <input class="form-control" id="bond_search" type="search" placeholder="رقم السند، القيد، الدفتر، البيان أو التحويل">
                    </div>
                    <div class="col-xl-2 col-md-6">
                        <label class="form-label" for="from_date">{{ __('finance::finance.from_date') }}</label>
                        <input class="form-control" id="from_date" type="date">
                    </div>
                    <div class="col-xl-2 col-md-6">
                        <label class="form-label" for="to_date">{{ __('finance::finance.to_date') }}</label>
                        <input class="form-control" id="to_date" type="date">
                    </div>
                    <div class="col-xl-2 col-md-6">
                        <label class="form-label" for="is_confirm_filter">حالة السند</label>
                        <select id="is_confirm_filter" class="form-control select2">
                            <option value="">كل الحالات</option>
                            <option value="1">معتمد</option>
                            <option value="0">بانتظار الاعتماد</option>
                        </select>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <label class="form-label" for="payment_type_filter">{{ __('finance::finance.payment_type') }}</label>
                        <select id="payment_type_filter" class="form-control select2">
                            <option value="">كل طرق الدفع</option>
                            @foreach ($payment_types as $payment_type)
                                <option value="{{ $payment_type->value }}">{{ $payment_type->lang() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <label class="form-label" for="debit_account_id">{{ __('finance::finance.Payee') }}</label>
                        <select id="debit_account_id" class="form-control sarf-remote-select" data-option-type="account" data-placeholder="ابحث باسم المستفيد أو رقم الحساب">
                            <option value="">كل المستفيدين</option>
                        </select>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <label class="form-label" for="credit_account_id">{{ __('finance::finance.Cash/Drawer/Bank') }}</label>
                        <select id="credit_account_id" class="form-control select2">
                            <option value="">كل الصناديق والبنوك</option>
                            @foreach ($banks_boxes as $account)
                                <option value="{{ $account->id }}">{{ $account->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <label class="form-label" for="cost_center_id">{{ __('finance::finance.cost_center') }}</label>
                        <select id="cost_center_id" class="form-control sarf-remote-select" data-option-type="cost_center" data-placeholder="ابحث باسم أو رقم مركز التكلفة">
                            <option value="">كل مراكز التكلفة</option>
                        </select>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="sarf-filter-actions">
                            <button type="button" id="applySarfFilters" class="btn btn-primary flex-grow-1"><i class="fa fa-search"></i> تطبيق</button>
                            <button type="button" id="resetSarfFilters" class="btn btn-outline-secondary" title="مسح الفلاتر"><i class="fa fa-undo"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card sarf-table-card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <strong><i class="fa fa-list text-primary me-1"></i> سجل سندات الصرف</strong>
            <small class="text-muted">اضغط رقم السند للطباعة أو رقم القيد لعرض تفاصيله</small>
        </div>
        <div class="table-responsive">
            <table id="dataTableObject" class="table table-hover align-middle mb-0" style="width:100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('finance::finance.Voucher Number') }}</th>
                        <th>{{ __('finance::finance.Voucher Date') }}</th>
                        <th>{{ __('finance::finance.Payee') }} / البيان</th>
                        <th>{{ __('finance::finance.Cash/Drawer/Bank') }}</th>
                        <th>{{ __('finance::finance.Amount') }}</th>
                        <th>الحالة</th>
                        <th>القيد المرتبط</th>
                        <th>{{ __('finance::finance.actions') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{ asset('admin') }}/assets/js/bundle/dataTables.bundle.js"></script>
    <script src="{{ asset('admin') }}/assets/js/bundle/dropify.bundle.js"></script>
    <script>
        $(function () {
            $('.sarf-remote-select').each(function () {
                const select = $(this);
                select.select2({
                    width: '100%',
                    allowClear: true,
                    placeholder: select.data('placeholder'),
                    minimumInputLength: 0,
                    ajax: {
                        url: @json($filterOptionsRoute),
                        dataType: 'json',
                        delay: 300,
                        data: function (params) {
                            return {
                                type: select.data('option-type'),
                                q: params.term || '',
                                page: params.page || 1
                            };
                        },
                        processResults: function (data) { return data; },
                        cache: true
                    }
                });
            });
        });
    </script>
    @php($bondTableType = 'sarf')
    @include('finance::finance.bond._js_bond')
@endsection
