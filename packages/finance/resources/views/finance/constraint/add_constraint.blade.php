@extends('click.layout.indexs.index')

@section('style')
    <style>
        .constraint-create-page .page-card {
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 20px;
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.06);
        }

        .constraint-create-page .page-hero {
            padding: 1.5rem;
            margin-bottom: 1rem;
            background:
                radial-gradient(circle at top right, rgba(13, 110, 253, 0.08), transparent 30%),
                linear-gradient(135deg, #ffffff 0%, #f8fbff 100%);
        }

        .constraint-create-page .page-hero h3 {
            margin-bottom: .4rem;
            font-size: 1.4rem;
            font-weight: 700;
        }

        .constraint-create-page .page-hero p {
            margin-bottom: 0;
            color: #6c757d;
        }

        .constraint-create-page .section-card {
            padding: 1.25rem;
            margin-bottom: 1rem;
        }

        .constraint-create-page .section-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .constraint-create-page .section-title h5 {
            margin-bottom: .25rem;
            font-size: 1rem;
            font-weight: 700;
        }

        .constraint-create-page .section-title p {
            margin-bottom: 0;
            color: #6c757d;
            font-size: .9rem;
        }

        .constraint-create-page .summary-box {
            border-radius: 16px;
            padding: .9rem 1rem;
            background: #f8f9fa;
            border: 1px solid #eef1f4;
        }

        .constraint-create-page .summary-label {
            display: block;
            margin-bottom: .35rem;
            color: #6c757d;
            font-size: .82rem;
        }

        .constraint-create-page .summary-value {
            font-size: 1.05rem;
            font-weight: 700;
            color: #212529;
        }

        .constraint-create-page .constraint-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: .75rem;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }

        .constraint-create-page .totals-group {
            display: flex;
            flex-wrap: wrap;
            gap: .75rem;
        }

        .constraint-create-page .total-chip {
            min-width: 180px;
            padding: .8rem 1rem;
            border-radius: 14px;
            border: 1px solid #edf0f3;
            background: #fff;
        }

        .constraint-create-page .total-chip span {
            display: block;
            color: #6c757d;
            font-size: .82rem;
            margin-bottom: .15rem;
        }

        .constraint-create-page .total-chip strong {
            font-size: 1rem;
        }

        .constraint-create-page .total-chip.debtor {
            border-color: rgba(25, 135, 84, .18);
            background: rgba(25, 135, 84, .06);
        }

        .constraint-create-page .total-chip.creditor {
            border-color: rgba(220, 53, 69, .18);
            background: rgba(220, 53, 69, .06);
        }

        .constraint-create-page .constraint-table {
            margin-bottom: 0;
            vertical-align: middle;
        }

        .constraint-create-page .constraint-table thead th {
            background: #f8f9fb;
            color: #495057;
            font-weight: 700;
            border-bottom-width: 1px;
        }

        .constraint-create-page .constraint-table tfoot td {
            background: #fcfcfd;
            font-weight: 700;
        }

        .constraint-create-page .amount-input {
            text-align: center;
            font-weight: 600;
        }

        .constraint-create-page .delete-row-btn {
            border: 0;
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: rgba(220, 53, 69, .1);
            color: #dc3545;
        }

        .constraint-create-page .delete-row-btn:hover {
            background: rgba(220, 53, 69, .15);
        }

        .constraint-create-page .submit-bar {
            display: flex;
            flex-wrap: wrap;
            gap: .75rem;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.25rem;
            position: sticky;
            bottom: 1rem;
            z-index: 5;
        }

        .constraint-create-page .submit-note {
            color: #6c757d;
            font-size: .9rem;
            margin-bottom: 0;
        }

        .constraint-create-page .balance-alert {
            display: none;
            margin-top: 1rem;
        }

        @media (max-width: 767.98px) {

            .constraint-create-page .page-hero,
            .constraint-create-page .section-card {
                padding: 1rem;
            }

            .constraint-create-page .submit-bar {
                position: static;
            }

            .constraint-create-page .submit-actions {
                width: 100%;
            }

            .constraint-create-page .submit-actions .btn {
                width: 100%;
            }
        }
    </style>
@endsection

@section('page-title')
    {{ $bladeTitle }}
@endsection

@section('page-links')
    <li class="breadcrumb-item">
        <a href="{{ $indexRoute }}">{{ __('finance::finance.constraints') }}</a>
    </li>
    <li class="breadcrumb-item active" aria-current="page">{{ $bladeTitle }}</li>
@endsection

@section('content')
    <div class="constraint-create-page">
        <div class="page-card page-hero">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div>
                    <h3>{{ __('finance::finance.Add daily constraint') }}</h3>
                    <p>{{ __('finance::finance.constraint_create_helper_text') }}</p>
                </div>
                <a href="{{ $indexRoute }}" class="btn btn-outline-primary">
                    <i class="fa fa-list-ul"></i>
                    {{ __('finance::finance.view_all_constraints') }}
                </a>
            </div>
        </div>

        <form id="constraint-create-form" class="row g-3" method="post" action="{{ $storeRoute }}"
            enctype="multipart/form-data">
            @csrf
            @include('finance::finance.constraint.inc.__create')

            <div class="col-12">
                <div class="page-card submit-bar">
                    <p class="submit-note">{{ __('finance::finance.constraint_submit_note') }}</p>
                    <div class="submit-actions d-flex gap-2">
                        <a href="{{ $indexRoute }}" class="btn btn-light border">
                            {{ __('finance::finance.view_all_constraints') }}
                        </a>
                        <button class="btn btn-primary mySubmitButton" type="submit">
                            {{ __('buttons.save') }}
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@section('js')
    <script>
        (function() {
            const form = document.getElementById('constraint-create-form');

            if (!form) {
                return;
            }

            const tableBody = form.querySelector('.table-body');
            const balanceInput = form.querySelector('#constraint_total_value');
            const debtorFooter = form.querySelector('#debtor_footer_value');
            const creditorFooter = form.querySelector('#creditor_footer_value');
            const debtorBadge = form.querySelector('#debtor_total_badge');
            const creditorBadge = form.querySelector('#creditor_total_badge');
            const balanceAlert = form.querySelector('#balance_alert');

            $.validate({
                ignore: 'input[type=hidden]',
                modules: 'date, security',
                lang: '{{ \App::currentLocale() }}',
                validateOnEvent: true
            });

            $('.select2').select2();

            function normalizeDigits(value) {
                if (value === null || value === undefined) {
                    return '';
                }

                return String(value)
                    .replace(/[٠-٩]/g, function(digit) {
                        return '٠١٢٣٤٥٦٧٨٩'.indexOf(digit);
                    })
                    .replace(/٫|،/g, '.')
                    .replace(/٬/g, '')
                    .replace(/\s+/g, '');
            }

            function sanitizeDecimal(value) {
                let normalized = normalizeDigits(value).replace(/[^0-9.]/g, '');
                const parts = normalized.split('.');

                if (parts.length > 2) {
                    normalized = parts.shift() + '.' + parts.join('');
                }

                return normalized;
            }

            function parseAmount(value) {
                const sanitized = sanitizeDecimal(value);
                return sanitized === '' ? 0 : parseFloat(sanitized) || 0;
            }

            function formatAmount(value) {
                return Number(value || 0).toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            function disableSelectedAccounts() {
                $('.select-unique-option option').prop('disabled', false);
            }

            function updateTotals() {
                let debtorTotal = 0;
                let creditorTotal = 0;

                form.querySelectorAll('.debtor').forEach((input) => {
                    debtorTotal += parseAmount(input.value);
                });

                form.querySelectorAll('.creditor').forEach((input) => {
                    creditorTotal += parseAmount(input.value);
                });

                debtorFooter.value = formatAmount(debtorTotal);
                creditorFooter.value = formatAmount(creditorTotal);
                debtorBadge.textContent = formatAmount(debtorTotal);
                creditorBadge.textContent = formatAmount(creditorTotal);

                const hasValues = debtorTotal > 0 || creditorTotal > 0;
                const balanced = debtorTotal > 0 && debtorTotal.toFixed(2) === creditorTotal.toFixed(2);

                form.querySelectorAll('.debtor, .creditor').forEach((input) => {
                    input.classList.toggle('input-error', hasValues && !balanced);
                });

                if (balanced) {
                    balanceInput.value = debtorTotal.toFixed(2);
                    balanceAlert.style.display = 'none';
                } else {
                    balanceInput.value = '';
                    balanceAlert.style.display = hasValues ? 'block' : 'none';
                }
            }

            function createRow(type) {
                const template = document.getElementById('constraint-row-template-' + type);
                const row = template.content.firstElementChild.cloneNode(true);
                return row;
            }

            function addRow(type) {
                const row = createRow(type);
                tableBody.appendChild(row);
                $(row).find('.select2').select2();
                disableSelectedAccounts();
                updateTotals();
            }

            form.addEventListener('input', function(event) {
                if (!event.target.classList.contains('js-amount-input')) {
                    return;
                }

                event.target.value = sanitizeDecimal(event.target.value);
                updateTotals();
            });

            form.addEventListener('blur', function(event) {
                if (!event.target.classList.contains('js-amount-input')) {
                    return;
                }

                const parsedValue = parseAmount(event.target.value);
                event.target.value = parsedValue > 0 ? parsedValue.toFixed(2) : '';
                updateTotals();
            }, true);

            $(document).on('change', '.select-unique-option', function() {
                disableSelectedAccounts();
            });

            $(document).on('click', '.add_debtor', function(event) {
                event.preventDefault();
                addRow('debtor');
            });

            $(document).on('click', '.add_creditor', function(event) {
                event.preventDefault();
                addRow('creditor');
            });

            $(document).on('click', '.delete-row-btn', function(event) {
                event.preventDefault();

                const row = event.currentTarget.closest('tr');
                row.remove();
                disableSelectedAccounts();
                updateTotals();
            });

            $(document).on('submit', '#constraint-create-form', function(event) {
                event.preventDefault();

                updateTotals();

                if (!balanceInput.value) {
                    warningToster('{{ __('auth.an error occurred') }}',
                        '{{ __('finance::finance.debit_credit_totals_must_match') }}');
                    return;
                }

                if (!checkInternetConnection()) {
                    validToster("{{ __('auth.no_internet') }}");
                    return;
                }

                const submitter = event.originalEvent && event.originalEvent.submitter ?
                    event.originalEvent.submitter :
                    this.querySelector('.mySubmitButton');
                const submitButton = $(submitter);
                const buttonText = submitButton.text().trim();
                const formData = new FormData(this);

                $.ajax({
                    url: this.action,
                    type: 'POST',
                    data: formData,
                    cache: false,
                    contentType: false,
                    processData: false,
                    beforeSend: function() {
                        submitButton.html(
                            '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ' +
                            buttonText);
                    },
                    success: function(result) {
                        if (result.data && result.data.show_url) {
                            window.location.href = result.data.show_url;
                            return;
                        }

                        handleSuccessResponce(result);
                        submitButton.html(buttonText);
                    },
                    error: function(errorObj) {
                        handleErrorResponce(errorObj);
                        submitButton.html(buttonText);
                    }
                });
            });

            updateTotals();
            disableSelectedAccounts();
        })();
    </script>
@endsection
