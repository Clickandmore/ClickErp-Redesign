@extends('click.layout.indexs.index')
@section('style')
    <style>
        :root {
            --profit-primary: #315a63;
            --profit-accent: #f7941d;
            --profit-surface: #ffffff;
            --profit-soft: #f7fafb;
            --profit-border: #dce7ea;
            --profit-text: #1f2937;
            --profit-shadow: 0 12px 28px rgba(49, 90, 99, 0.08);
        }

        .loader {
            border: 10px solid #edf2f4;
            border-top: 10px solid var(--profit-accent);
            border-radius: 50%;
            width: 84px;
            height: 84px;
            animation: spin 0.9s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .profit-page {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            color: var(--profit-text);
        }

        .profit-panel,
        .profit-result {
            background: var(--profit-surface);
            border: 1px solid var(--profit-border);
            border-radius: 8px;
            box-shadow: var(--profit-shadow);
        }

        .profit-panel {
            padding: 1rem;
        }

        .profit-panel__head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            padding-bottom: 0.85rem;
            margin-bottom: 1rem;
            border-bottom: 1px solid var(--profit-border);
        }

        .profit-panel__title {
            margin: 0;
            color: var(--profit-primary);
            font-size: 1.05rem;
            font-weight: 800;
        }

        .profit-year {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            min-height: 34px;
            padding: 0.25rem 0.7rem;
            border-radius: 6px;
            background: var(--profit-soft);
            color: var(--profit-primary);
            font-size: 0.9rem;
            font-weight: 700;
            border: 1px solid var(--profit-border);
        }

        .profit-filter-grid {
            display: grid;
            grid-template-columns: repeat(12, minmax(0, 1fr));
            gap: 0.9rem;
            align-items: end;
        }

        .profit-field {
            grid-column: span 3;
        }

        .profit-field--action {
            grid-column: span 2;
        }

        .profit-field label {
            display: block;
            margin-bottom: 0.4rem;
            color: var(--profit-primary);
            font-size: 0.9rem;
            font-weight: 700;
        }

        .profit-field .form-control {
            min-height: 42px;
            border-color: var(--profit-border);
            border-radius: 6px;
            box-shadow: none;
        }

        .profit-search-btn {
            width: 100%;
            min-height: 42px;
            border-radius: 6px;
            font-weight: 700;
            white-space: nowrap;
        }

        .profit-result {
            min-height: 260px;
            overflow: hidden;
        }

        .profit-loading,
        .profit-result__empty {
            min-height: 260px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--profit-soft);
        }

        @media (max-width: 991.98px) {
            .profit-field,
            .profit-field--action {
                grid-column: span 6;
            }
        }

        @media (max-width: 575.98px) {
            .profit-panel__head {
                align-items: stretch;
                flex-direction: column;
            }

            .profit-field,
            .profit-field--action {
                grid-column: span 12;
            }
        }

        @media print {
            .to_hide {
                display: none !important;
            }
        }
    </style>
@endsection
@section('page-title')
    {{ $bladeTitle }}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{ $bladeTitle }}</li>
@endsection
@section('content')
    <div class="profit-page">
        @if ($active_year)
            <div class="profit-panel to_hide">
                <div class="profit-panel__head">
                    <h3 class="profit-panel__title">{{ $bladeTitle }}</h3>
                    <span class="profit-year">
                        <i class="fa fa-calendar"></i>
                        {{ $active_year['from_date'] }} / {{ $active_year['to_date'] }}
                    </span>
                </div>

                <div class="profit-filter-grid">
                    <div class="profit-field">
                        <label for="from_date">{{ __('finance::finance.From Date') }}</label>
                        <input type="date" name="from_date" value="{{ $active_year['from_date'] }}" class="form-control search_keys"
                            id="from_date">
                    </div>

                    <div class="profit-field">
                        <label for="to_date">{{ __('finance::finance.To Date') }}</label>
                        <input type="date" name="to_date" value="{{ $active_year['to_date'] }}" class="form-control search_keys"
                            id="to_date">
                    </div>

                    <div class="profit-field">
                        <label for="levelSelect">{{ __('finance::finance.select the level') }}</label>
                        <select name="level" id="levelSelect" class="form-control search_keys">
                            <option value="all" selected>
                                {{ __('finance::finance.All levels') }}
                            </option>
                            @foreach ($levels as $level)
                                <option value="{{ $level }}">
                                    {{ helperTrans('finance::finance.level') }}
                                    {{ $level }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="profit-field profit-field--action">
                        <button type="button" id="search_button" class="btn btn-success profit-search-btn">
                            <i class="fa fa-search"></i> {{ __('finance::finance.Search') }}
                        </button>
                    </div>
                </div>
            </div>
        @else
            <div class="alert alert-info mb-0" style="color:red;">{{ trans('finance::finance.the year is not active') }}</div>
        @endif

        <div class="profit-result" id="account_list">
            <div class="profit-result__empty">{{ __('finance::finance.no_data_found') }}</div>
        </div>
    </div>
@endsection
@section('js')
    <script>
        function loadProfitLoss() {
            var emptyFields = $(".search_keys").filter(function() {
                return $(this).val() === "";
            });

            if (emptyFields.length > 0) {
                alert("يرجي ادخال كل البيانات اولا");
            } else {
                var data = $(".search_keys").serialize();
                $.ajax({
                    url: '{{ $dataTableRoute }}',
                    type: 'GET',
                    data: data,
                    beforeSend: function() {
                        $("#account_list").html('<div class="profit-loading"><div class="loader"></div></div>');
                    },
                    success: function(result) {
                        $("#account_list").html(result.data.html);
                    },
                    error: function(errorObj, errorText, errorThrown) {
                        handleErrorResponce(errorObj);
                    }
                });
            }
        }

        $("#search_button").on("click", function(e) {
            e.preventDefault();
            loadProfitLoss();
        });

        @if ($active_year)
            loadProfitLoss();
        @endif
    </script>
@endsection
