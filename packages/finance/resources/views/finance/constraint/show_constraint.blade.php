@extends('click.layout.indexs.index')

@section('style')
    <style>
        .constraint-show-page {
            max-width: 1160px;
            margin: 0 auto;
        }

        .constraint-show-page .show-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 20px;
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.06);
        }

        .constraint-show-page .hero-card {
            padding: 1.5rem;
            margin-bottom: 1rem;
            background:
                radial-gradient(circle at top left, rgba(15, 122, 135, 0.08), transparent 30%),
                linear-gradient(135deg, #ffffff 0%, #f8fcfd 100%);
        }

        .constraint-show-page .document-brand-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1rem 1.1rem;
            border: 1px solid #d1d5db;
            border-radius: 18px;
            margin-bottom: 1rem;
            background: #fff;
        }

        .constraint-show-page .brand-copy {
            display: flex;
            align-items: center;
            gap: 1rem;
            min-width: 0;
        }

        .constraint-show-page .brand-copy img {
            width: 72px;
            height: 72px;
            object-fit: contain;
            flex-shrink: 0;
        }

        .constraint-show-page .brand-copy h4,
        .constraint-show-page .brand-copy h5 {
            margin: 0;
            font-weight: 700;
            color: #0f172a;
        }

        .constraint-show-page .brand-copy h5 {
            margin-top: .25rem;
            font-size: .95rem;
        }

        .constraint-show-page .brand-copy p {
            margin: .45rem 0 0;
            color: #6b7280;
            font-size: .88rem;
        }

        .constraint-show-page .document-badge {
            min-width: 170px;
            text-align: center;
            padding: .95rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 18px;
            background: linear-gradient(135deg, #ecfeff 0%, #f8fafc 100%);
        }

        .constraint-show-page .document-badge span {
            display: block;
            color: #6b7280;
            font-size: .8rem;
        }

        .constraint-show-page .document-badge strong {
            display: block;
            margin-top: .3rem;
            color: #0f7a87;
            font-size: 1.25rem;
        }

        .constraint-show-page .hero-main {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .constraint-show-page .hero-main h3 {
            margin-bottom: .35rem;
            font-size: 1.55rem;
            font-weight: 700;
            color: #0f172a;
        }

        .constraint-show-page .hero-main p {
            margin-bottom: 0;
            color: #6b7280;
        }

        .constraint-show-page .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 1rem;
        }

        .constraint-show-page .summary-item {
            padding: 1rem;
            border-radius: 16px;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-top: 3px solid #0f7a87;
        }

        .constraint-show-page .summary-item span {
            display: block;
            margin-bottom: .35rem;
            color: #6b7280;
            font-size: .82rem;
        }

        .constraint-show-page .summary-item strong {
            display: block;
            font-size: 1rem;
            color: #111827;
            line-height: 1.8;
            word-break: break-word;
        }

        .constraint-show-page .section-card {
            padding: 1.25rem;
            margin-bottom: 1rem;
        }

        .constraint-show-page .section-head {
            margin-bottom: 1rem;
        }

        .constraint-show-page .section-head h5 {
            margin-bottom: .25rem;
            font-weight: 700;
            color: #0f172a;
        }

        .constraint-show-page .section-head p {
            margin-bottom: 0;
            color: #6b7280;
        }

        .constraint-show-page .constraint-table {
            margin-bottom: 0;
        }

        .constraint-show-page .constraint-table thead th {
            background: #f3f4f6;
            color: #1f2937;
            font-weight: 700;
            text-align: center;
            white-space: nowrap;
        }

        .constraint-show-page .constraint-table td {
            vertical-align: middle;
        }

        .constraint-show-page .constraint-account-link {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            color: #0f7a87;
            font-weight: 700;
            text-decoration: none;
        }

        .constraint-show-page .constraint-account-link:hover {
            text-decoration: underline;
        }

        .constraint-show-page .constraint-table tfoot td {
            background: #ecfeff;
            font-weight: 700;
        }

        .constraint-show-page .constraint-table .amount {
            font-weight: 700;
            text-align: center;
            direction: ltr;
        }

        .constraint-show-page .attachment-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .constraint-show-page .attachment-grid img {
            width: 100%;
            height: 190px;
            object-fit: cover;
            border-radius: 16px;
            border: 1px solid #e5e7eb;
        }

        .constraint-show-page .activity-list {
            display: flex;
            flex-direction: column;
            gap: .75rem;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .constraint-show-page .activity-item {
            display: grid;
            grid-template-columns: 140px 1fr 180px;
            gap: 1rem;
            align-items: start;
            padding: .85rem 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .constraint-show-page .activity-item:last-child {
            border-bottom: 0;
        }

        .constraint-show-page .activity-item strong {
            color: #0f172a;
        }

        .constraint-show-page .activity-item span,
        .constraint-show-page .activity-item small {
            color: #6b7280;
        }

        @media (max-width: 767.98px) {
            .constraint-show-page .activity-item {
                grid-template-columns: 1fr;
                gap: .35rem;
            }
        }

        @media print {
            @page {
                size: A4 portrait;
                margin: 10mm;
            }

            body {
                background: #fff !important;
            }

            .no-print,
            .sidebar,
            .header,
            .right_chat,
            .page-header,
            .breadcrumb {
                display: none !important;
            }

            .main {
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .constraint-show-page {
                max-width: none;
                padding: 0;
            }

            .constraint-show-page .show-card {
                box-shadow: none;
                border-color: #d1d5db;
                break-inside: avoid;
            }

            .constraint-show-page .section-card {
                margin-bottom: .75rem;
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
    @php
        $companyNameAr = documentSetting(
            'document_company_name',
            documentSetting('website_name', optional($setting_web ?? null)->website_name ?: config('app.name', 'Platinum'), 'ar'),
            'ar',
        );
        $companyNameEn = documentSetting(
            'document_company_name',
            documentSetting('website_name', optional($setting_web ?? null)->website_name ?: config('app.name', 'Platinum'), 'en'),
            'en',
        );
        $logoPath = documentSetting('document_logo') ?: optional($setting_web ?? null)->logo_header;
        $logoUrl = $logoPath ? showFile($logoPath) : null;
        $taxNumber = isset($setting_web) && !empty($setting_web->tax_number) ? $setting_web->tax_number : '-';
        $entryCode = $obj->code ?: $obj->id;
        $printedAt = now(config('app.timezone', 'Asia/Riyadh'))->format('Y-m-d H:i:s');
        $statementPermissions = [
            'finance.account-statement-read',
            'finance.account-statement.index-read',
            'finance.account-statement-print-read',
            'finance::finance.account-statement-print-read',
        ];
        $canOpenAccountStatement =
            auth('admin')->check() &&
            (auth('admin')->user()->admin_type == \App\Enums\AdminTypeisEnum::Developer->value ||
                count(array_intersect($statementPermissions, getUserPermissions())) > 0);
        $buildAccountStatementUrl = function ($accountId) {
            return route('finance.account-statement.index', [
                'account_id' => $accountId,
                'to_date' => now(config('app.timezone', 'Asia/Riyadh'))->format('Y-m-d'),
                'type' => 'both',
                'other_account' => 'yes',
            ]);
        };
        $buildCostCenterStatementUrl = function ($costCenterId) {
            return route('finance.account-cost-centers.index', [
                'cost_center_id' => $costCenterId,
                'to_date' => now(config('app.timezone', 'Asia/Riyadh'))->format('Y-m-d'),
                'type' => 'both',
                'auto_search' => 1,
            ]);
        };
    @endphp

    <div class="constraint-show-page">
        @if (request()->boolean('saved'))
            <div class="alert alert-success no-print">
                {{ __('finance::finance.constraint_saved_actions_hint', ['code' => $obj->code]) }}
            </div>
        @endif

        <div class="show-card hero-card">
            <div class="document-brand-bar">
                <div class="brand-copy">
                    @if ($logoUrl)
                        <img src="{{ $logoUrl }}" alt="company logo">
                    @endif

                    <div>
                        <h4>{{ $companyNameAr }}</h4>
                        @if ($companyNameEn && $companyNameEn !== $companyNameAr)
                            <h5>{{ $companyNameEn }}</h5>
                        @endif
                        <p>الرقم الضريبي / VAT: {{ $taxNumber }}</p>
                    </div>
                </div>

                <div class="document-badge">
                    <span>رقم القيد</span>
                    <strong>{{ $entryCode }}</strong>
                    <span>Journal Entry</span>
                </div>
            </div>

            <div class="hero-main">
                <div>
                    <h3>{{ __('finance::finance.constraint_details_page') }}</h3>
                    <p>{{ __('finance::finance.constraint_show_helper_text') }}</p>
                </div>

                <div class="d-flex flex-wrap gap-2 no-print">
                    <a href="{{ $editRoute }}" class="btn btn-success">
                        <i class="fa fa-edit"></i>
                        {{ __('buttons.edit') }}
                    </a>
                    <a href="{{ $createRoute }}" class="btn btn-primary">
                        <i class="fa fa-plus-circle"></i>
                        {{ __('finance::finance.create_new_constraint') }}
                    </a>
                    <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
                        <i class="fa fa-print"></i>
                        {{ __('finance::finance.print') }}
                    </button>
                    <a href="{{ $indexRoute }}" class="btn btn-outline-dark">
                        <i class="fa fa-list-ul"></i>
                        {{ __('finance::finance.view_all_constraints') }}
                    </a>
                </div>
            </div>

            <div class="summary-grid">
                <div class="summary-item">
                    <span>{{ __('finance::finance.Journal Entry Number') }}</span>
                    <strong>{{ $entryCode }}</strong>
                </div>
                <div class="summary-item">
                    <span>{{ __('finance::finance.date') }}</span>
                    <strong dir="ltr">{{ $obj->date }}</strong>
                </div>
                <div class="summary-item">
                    <span>{{ __('finance::finance.value') }}</span>
                    <strong>{{ number_format((float) $obj->value, 2, '.', ',') }} ريال</strong>
                </div>
                <div class="summary-item">
                    <span>{{ __('finance::finance.constraint_lines') }}</span>
                    <strong>{{ $obj->constraintDetails->count() }}</strong>
                </div>
                <div class="summary-item">
                    <span>{{ __('finance::finance.statement') }}</span>
                    <strong>{{ $obj->statement ?: '-' }}</strong>
                </div>
                <div class="summary-item">
                    <span>وقت الطباعة</span>
                    <strong dir="ltr">{{ $printedAt }}</strong>
                </div>
            </div>
        </div>

        @if ($inlineConstraintEditOpen)
        <div class="no-print" id="constraintInlineEdit">
            <div class="show-card section-card">
                <div class="section-head">
                    <h5>{{ __('buttons.edit') }} {{ __('finance::finance.constraint_details_page') }}</h5>
                    <p class="mb-0">يتم التعديل من نفس صفحة العرض، والحسابات الحالية في القيد تظل ظاهرة حتى لو تغير شجر الحسابات لاحقاً.</p>
                </div>

                @include('finance::finance.constraint.edit')
            </div>
        </div>
        @endif

        <div class="show-card section-card">
            <div class="section-head">
                <h5>{{ __('finance::finance.constraint_lines') }}</h5>
                <p>{{ __('finance::finance.constraint_lines_review_hint') }}</p>
                <p class="mb-0 mt-1 text-muted">يمكنك الضغط على اسم أي حساب أو مركز تكلفة للانتقال مباشرة إلى كشفه.</p>
            </div>

            <div class="table-responsive">
                <table class="table constraint-table align-middle">
                    <thead>
                        <tr>
                            <th>{{ __('finance::finance.type') }}</th>
                            <th>{{ __('finance::finance.Account Name') }}</th>
                            <th>{{ __('finance::finance.debtor') }}</th>
                            <th>{{ __('finance::finance.creditor') }}</th>
                            <th>{{ __('finance::finance.fn-cost-centers') }}</th>
                            <th>{{ __('finance::finance.Account Statement') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($obj->constraintDetails as $detail)
                            <tr>
                                <td>{{ $detail->type === 'debtor' ? __('finance::finance.debtor') : __('finance::finance.creditor') }}
                                </td>
                                <td>
                                    @if ($canOpenAccountStatement && optional($detail->account)->id)
                                        <a href="{{ $buildAccountStatementUrl($detail->account->id) }}" target="_blank"
                                            rel="noopener" class="constraint-account-link">
                                            {{ $detail->account->name }}
                                            <i class="fa fa-external-link"></i>
                                        </a>
                                    @else
                                        {{ optional($detail->account)->name }}
                                    @endif
                                </td>
                                <td class="amount">{{ number_format((float) $detail->debtor_value, 2, '.', ',') }}</td>
                                <td class="amount">{{ number_format((float) $detail->creditor_value, 2, '.', ',') }}</td>
                                <td>
                                    @if (optional($detail->costCenter)->id)
                                        <a href="{{ $buildCostCenterStatementUrl($detail->costCenter->id) }}"
                                            target="_blank" rel="noopener" class="constraint-account-link">
                                            {{ $detail->costCenter->title }}
                                            <i class="fa fa-external-link"></i>
                                        </a>
                                    @else
                                        {{ __('finance::finance.without') }}
                                    @endif
                                </td>
                                <td>{{ $detail->statement ?: '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2">{{ __('finance::finance.Total') }}</td>
                            <td class="amount">{{ number_format((float) $obj->value, 2, '.', ',') }}</td>
                            <td class="amount">{{ number_format((float) $obj->value, 2, '.', ',') }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="show-card section-card">
            <div class="section-head">
                <h5>{{ __('finance::finance.attachments') }}</h5>
                <p>{{ __('finance::finance.constraint_attachments_hint') }}</p>
            </div>

            @if ($obj->files->count() > 0)
                <div class="attachment-grid">
                    @foreach ($obj->files as $file)
                        <div>
                            <img src="{{ showFile($file->file) }}" alt="attachment">
                        </div>
                    @endforeach
                </div>
            @else
                <div class="alert alert-light border mb-0">
                    {{ __('finance::finance.There are no attachments.') }}
                </div>
            @endif
        </div>

        <div class="show-card section-card">
            <div class="section-head">
                <h5>{{ __('finance::finance.constraint_activity_log') }}</h5>
                <p>{{ __('finance::finance.constraint_activity_log_hint') }}</p>
            </div>

            @if ($obj->logs->count() > 0)
                <ul class="activity-list">
                    @foreach ($obj->logs as $log)
                        <li class="activity-item">
                            <strong>{{ $log->action_label }}</strong>
                            <div>
                                <span>{{ $log->description ?: '-' }}</span>
                                <small class="d-block mt-1">
                                    {{ __('finance::finance.created_by') }}:
                                    {{ optional($log->admin)->name ?: '-' }}
                                </small>
                            </div>
                            <small dir="ltr">{{ optional($log->created_at)->format('Y-m-d H:i:s') }}</small>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="alert alert-light border mb-0">
                    {{ __('finance::finance.constraint_activity_log_empty') }}
                </div>
            @endif
        </div>
    </div>
@endsection

@section('js')
    @if ($inlineConstraintEditOpen)
        @include('finance::finance.constraint.partials.edit-scripts')
        <script>
            $(document).on('submit', '.constraint-inline-edit-form', function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();

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
                        } else {
                            window.location.reload();
                        }
                    },
                    error: function(errorObj) {
                        handleErrorResponce(errorObj);
                    }
                });
            });
        </script>
    @endif
@endsection
