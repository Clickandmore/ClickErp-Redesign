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

        .constraint-create-page .page-hero p,
        .constraint-create-page .section-title p,
        .constraint-create-page .submit-note {
            margin-bottom: 0;
            color: #6c757d;
        }

        .constraint-create-page .section-card {
            padding: 1.25rem;
            margin-bottom: 1rem;
        }

        .constraint-create-page .section-title,
        .constraint-create-page .constraint-toolbar {
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

        .constraint-create-page .summary-box,
        .constraint-create-page .total-chip {
            border-radius: 14px;
            border: 1px solid #eef1f4;
            background: #f8f9fa;
            padding: .85rem 1rem;
        }

        .constraint-create-page .summary-label,
        .constraint-create-page .total-chip span {
            display: block;
            margin-bottom: .25rem;
            color: #6c757d;
            font-size: .82rem;
        }

        .constraint-create-page .summary-value,
        .constraint-create-page .total-chip strong {
            font-size: 1.05rem;
            font-weight: 700;
            color: #212529;
        }

        .constraint-create-page .totals-group {
            display: flex;
            flex-wrap: wrap;
            gap: .75rem;
        }

        .constraint-create-page .total-chip {
            min-width: 180px;
            background: #fff;
        }

        .constraint-create-page .total-chip.debtor-total {
            border-color: rgba(25, 135, 84, .18);
            background: rgba(25, 135, 84, .06);
        }

        .constraint-create-page .total-chip.creditor-total {
            border-color: rgba(220, 53, 69, .18);
            background: rgba(220, 53, 69, .06);
        }

        .constraint-create-page .constraint-table {
            min-width: 1120px;
            margin-bottom: 0;
            table-layout: fixed;
            vertical-align: middle;
        }

        .constraint-create-page .constraint-table thead th {
            background: #f8f9fb;
            color: #495057;
            font-weight: 700;
            border-bottom-width: 1px;
            white-space: nowrap;
        }

        .constraint-create-page .constraint-table th,
        .constraint-create-page .constraint-table td {
            vertical-align: top;
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
            cursor: pointer;
        }

        .constraint-create-page .delete-row-btn:hover {
            background: rgba(220, 53, 69, .15);
        }

        .constraint-create-page .select2-container {
            width: 100% !important;
        }

        .constraint-create-page .select2-selection--single {
            min-height: 42px;
        }

        .constraint-create-page .select2-selection__rendered {
            white-space: normal !important;
            line-height: 1.5 !important;
            padding-top: .35rem;
            padding-bottom: .35rem;
        }

        .constraint-create-page .statement-account-field {
            min-height: 72px;
            resize: vertical;
            line-height: 1.7;
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

        .constraint-create-page .balance-alert {
            display: none;
            margin-top: 1rem;
        }

        .constraint-create-page .constraint-attachment-grid {
            grid-template-columns: repeat(auto-fill, minmax(112px, 112px));
            align-items: start;
            justify-content: flex-start;
        }

        .constraint-create-page .constraint-attachment-grid .img_upload,
        .constraint-create-page .constraint-attachment-grid .uploadedImage {
            width: 112px;
            height: 112px;
            border-radius: 12px;
        }

        .constraint-create-page .constraint-attachment-grid .img_upload {
            border: 1px dashed #cbd5e1;
            background: #f8fafc;
            margin: 0;
        }

        .constraint-create-page .constraint-attachment-grid .img_upload .img {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .constraint-create-page .constraint-attachment-grid .img_upload img {
            width: 28px !important;
            height: 28px !important;
        }

        .constraint-create-page .constraint-attachment-grid .uploadedImage {
            border: 1px solid #e5e7eb;
            background: #fff;
        }

        .constraint-create-page .constraint-attachment-grid .uploadedImage > img,
        .constraint-create-page .constraint-attachment-grid .uploadedImage > video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .constraint-create-page .constraint-file-preview {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: .35rem;
            color: #334155;
            text-decoration: none;
            font-size: .75rem;
            text-align: center;
            padding: .5rem;
        }

        .constraint-create-page .constraint-file-preview i {
            font-size: 1.8rem;
            color: #0f766e;
        }

        .constraint-create-page .constraint-attachment-grid .uploadedImage .delete {
            top: 6px;
            right: 6px;
            width: 28px;
            height: 28px;
            padding: 0;
            border: 0 !important;
            border-radius: 50%;
            background: #fff !important;
            box-shadow: 0 6px 16px rgba(15, 23, 42, .16);
        }

        .constraint-create-page .constraint-attachment-grid .uploadedImage .delete img {
            width: 14px;
            height: 14px;
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
    <li class="breadcrumb-item">
        <a href="{{ $showRoute }}">{{ __('finance::finance.constraint_details_page') }}</a>
    </li>
    <li class="breadcrumb-item active" aria-current="page"> {{ $bladeTitle }}</li>
@endsection
@section('content')
    <div class="constraint-create-page">
        <div class="page-card page-hero">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div>
                    <h3>{{ $bladeTitle }}</h3>
                    <p>{{ __('finance::finance.constraint_create_helper_text') }}</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ $showRoute }}" class="btn btn-outline-secondary">
                        <i class="fa fa-eye"></i>
                        {{ __('finance::finance.constraint_details_page') }}
                    </a>
                    <a href="{{ $indexRoute }}" class="btn btn-outline-primary">
                        <i class="fa fa-list-ul"></i>
                        {{ __('finance::finance.view_all_constraints') }}
                    </a>
                </div>
            </div>
        </div>

        @include('finance::finance.constraint.edit')
    </div>
@endsection
@section('js')
    @include('finance::finance.constraint.partials.edit-scripts')
    <script>
        $(document).on('submit', '.constraint-edit-page-form', function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            updateConstraintTotals();

            if (!$('.constraint-total-value').val()) {
                warningToster('{{ __('auth.an error occurred') }}',
                    '{{ __('finance::finance.debit_credit_totals_must_match') }}');
                return;
            }

            if (!checkInternetConnection()) {
                validToster("{{ __('auth.no_internet') }}");
                return;
            }

            let form = this;
            let formData = new FormData(form);
            let submitButton = $(e.originalEvent.submitter);
            let buttonText = submitButton.text();

            $.ajax({
                url: $(form).attr('action'),
                type: 'POST',
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                beforeSend: function() {
                    if (window.cmShowLoader) {
                        window.cmShowLoader();
                    } else {
                        $('#loading-wrapper').show();
                    }
                    submitButton.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ' + buttonText);
                },
                complete: function() {
                    if (window.cmHideLoader) {
                        window.cmHideLoader();
                    } else {
                        $('#loading-wrapper').hide();
                    }
                    submitButton.html(buttonText);
                },
                success: function(result) {
                    handleSuccessResponce(result);
                    if (result.data && result.data.show_url) {
                        window.location.href = result.data.show_url;
                    }
                },
                error: function(errorObj) {
                    handleErrorResponce(errorObj);
                }
            });
        });
    </script>
@endsection
