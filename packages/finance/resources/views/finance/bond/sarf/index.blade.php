@extends('click.layout.indexs.index')
@section('style')
    <link rel="stylesheet" href="{{ asset('admin') }}/assets/cssbundle/dataTables.min.css">
    <link rel="stylesheet" href="{{ asset('admin') }}/assets/cssbundle/dropify.min.css">

    <style>
        .custom-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }

        .reports {
            .col-lg-6, .col-xl-3 {
                &:nth-child(1) {
                    h4, h5, .icon {
                        color: #2d91ea;
                    }
                }

                &:nth-child(2) {
                    h4, h5, .icon {
                        color: #3ebfbe;
                    }
                }

                &:nth-child(3) {
                    h4, h5, .icon {
                        color: #ffb553;
                    }
                }

                &:nth-child(4) {
                    h4, h5, .icon {
                        color: #f33e6f;
                    }
                }
            }

            .card {
                padding: 1.5rem;
                border-radius: 16px;
                background-color: #fff;
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
                transition: 0.3s ease;

                &:hover {
                    transform: translateY(-3px);
                }

                h4 {
                    font-size: 1.8rem;
                    font-weight: 700;
                    margin-bottom: 0.5rem;
                    text-transform: capitalize;
                }

                h5 {
                    font-size: 1.2rem;
                    font-weight: 600;
                    margin: 0;
                }

                .details {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }

                .icon {
                    font-size: 1.3rem;
                    color: var(--subText);
                }

                small {
                    color: var(--subText);

                    i {
                        color: #d52121;
                    }
                }

                p {
                    margin-top: 0.4rem;
                    color: var(--subText);
                    font-weight: 500;
                    font-size: 0.9rem;
                }
            }
        }

        .custom-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
        }

        input:checked + .slider {
            background-color: #2196F3;
        }

        input:focus + .slider {
            box-shadow: 0 0 1px #2196F3;
        }

        input:checked + .slider:before {
            transform: translateX(26px);
        }

        /* Rounded sliders */
        .slider.round {
            border-radius: 34px;
        }

        .slider.round:before {
            border-radius: 50%;
        }
    </style>
@endsection
@section('page-title')
    {{ $bladeTitle }}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{ $bladeTitle }}</li>
@endsection
@section('page-static')

@endsection
@section('up-card')

@endsection
@section('content')
    <div class="form-check form-switch">
        <label class="form-check-label" id="switchFilterLabel"
               for="switchFilter">{{__('finance::finance.advanced_search')}}</label>
        <input class="form-check-input" type="checkbox" role="switch" id="switchFilter">
    </div>
    <div class="row mb-3 filter_data" style="display: none">
        <div class="col-md-4 mt-2">
            <div class="form-group">
                <label>{{__("finance::finance.from_date")}}</label>
                <input class="form-control" name="from_date" id="from_date" type="date">
            </div>
        </div>
        <div class="col-md-4 mt-2">
            <div class="form-group">
                <label>{{__("finance::finance.to_date")}}</label>
                <input class="form-control" name="to_date" id="to_date" type="date">
            </div>
        </div>
        <div class="col-md-4 mt-2">
            <div class="form-group">
                <label>{{__("finance::finance.cost_center")}}</label>
                <select name="cost_center_id" id="cost_center_id" class="form-control select2">
                    <option value="">{{__("finance::finance.choose")}}</option>
                    @foreach($cost_centers as $cost_center)
                        <option value="{{ $cost_center->id }}">{{ $cost_center->title }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-4 mt-2">
            <div class="form-group">
                <label>{{__("finance::finance.Payee")}}</label>
                <select name="debit_account_id" id="debit_account_id" class="form-control select2">
                    <option value="">{{__("finance::finance.choose")}}</option>
                    @foreach($accounts as $account)
                        <option value="{{ $account->id }}">{{ $account->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-4 mt-2">
            <div class="form-group">
                <label>{{__("finance::finance.Cash/Drawer/Bank")}}</label>
                <select name="credit_account_id" id="credit_account_id" class="form-control select2">
                    <option value="">{{__("finance::finance.choose")}}</option>
                    @foreach($banks_boxes as $banks_box)
                        <option value="{{ $banks_box->id }}">{{ $banks_box->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-4 mt-2">
            <div class="form-group">
                <label>{{__("finance::finance.payment_type")}}</label>
                <select name="payment_type" id="payment_type_filter" class="form-control select2">
                    <option value="">{{__("finance::finance.choose")}}</option>
                    @foreach($payment_types as $payment_type)
                        <option
                                value="{{ $payment_type->value }}">{{ \Nami\Finance\Enums\BondPaymentTypes::tryFrom($payment_type->value)->lang() }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="row m-2 row-deck reports">
            <div class="col-xl-3 col-lg-6 col-md-3 col-sm-6 ">
                <div class="card p-0 overflow-hidden shadow-sm border-0">
                    <div class="card-body">
                        <h4 class="fw-bold text-primary mb-3">{{ number_format($results["year"]->total_value) }}</h4>
                        <div class="details d-flex align-items-center mb-2">
                            <i class="fa-solid fa-calendar icon me-2 text-secondary"></i>
                            <h5 class="mb-0 text-muted">{{ __("finance::finance.yearly") }}</h5>
                        </div>
                        <p class="text-muted mb-0">
                            {{ __("finance::finance.last_year") }} {{ number_format($results["last_year"]->total_value) }}
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-3 col-sm-6">
                <div class="card p-0 overflow-hidden shadow-sm border-0">
                    <div class="card-body">
                        <h4>{{ number_format($results["quarterYearData"]->total_value) }}</h4>
                        <div class="details">
                            <i class="fa-solid fa-calendar icon"></i>
                            <h5>{{ __("finance::finance.quarterly") }}</h5>
                        </div>
                        <p class="text-muted mb-0">
                            {{ __("finance::finance.last_quarterly") }} {{ number_format($results["last_quarterYearData"]->total_value) }}
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-3 col-sm-6">
                <div class="card p-0 overflow-hidden shadow-sm border-0">
                    <div class="card-body">
                        <h4>{{ number_format($results["month"]->total_value) }}</h4>
                        <div class="details">
                            <i class="fa-solid fa-calendar-alt icon"></i>
                            <h5>{{ __("finance::finance.monthly") }}</h5>
                        </div>
                        <p class="text-muted mb-0">
                            {{ __("finance::finance.last_month") }} {{ number_format($results["last_month"]->total_value) }}
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-3 col-sm-6">
                <div class="card p-0 overflow-hidden shadow-sm border-0">
                    <div class="card-body">
                        <h4>{{ number_format($results["day"]->total_value) }}</h4>
                        <div class="details">
                            <i class="fa-solid fa-calendar-day icon"></i>
                            <h5>{{ __("finance::finance.daily") }}</h5>
                        </div>
                        <p class="text-muted mb-0">
                            {{ __("finance::finance.last_day") }} {{ number_format($results["last_day"]->total_value) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    {!! addButton($createRoute, $addButtonText) !!}
    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead>
        <tr>
            <th>{{ __('finance::finance.SL') }}</th>
            <th>{{ __("finance::finance.Voucher Number") }}</th>
            <th>{{ __("finance::finance.Voucher Date") }}</th>
            <th>{{ __("finance::finance.Payee") }}</th>
            <th>{{ __("finance::finance.Cash/Drawer/Bank") }}</th>
            <th>{{ __("finance::finance.Amount") }}</th>
            <th>{{ __("finance::finance.Payment Method") }}</th>
            <th>{{ __('finance::finance.actions') }}</th>
        </tr>
        </thead>
        <tbody>

        </tbody>
    </table>
@endsection
@section('js')
    <script src="{{ asset('admin') }}/assets/js/bundle/dataTables.bundle.js"></script>
    <script src="{{ asset('admin') }}/assets/js/bundle/dropify.bundle.js"></script>

    <script>
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })
    </script>
    @include("finance::finance.bond._js_bond")

@endsection
