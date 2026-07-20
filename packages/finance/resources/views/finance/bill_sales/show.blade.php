@extends('click.layout.indexs.index')

@section('style')
    @include('finance::finance.bill_sales._document-styles')
    <style>
        .bill-sale-screen {
            direction: rtl;
            padding: 20px;
            background: #f4f6f9;
            border-radius: 14px;
        }

        .bill-sale-screen__toolbar {
            max-width: 1180px;
            margin: 0 auto 14px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .bill-sale-screen__toolbar .btn {
            border-radius: 9px;
            font-weight: 700;
        }

        .invoice-validation {
            max-width: 1180px;
            margin: 0 auto 14px;
            border-radius: 10px;
        }

        .invoice-attachments-panel {
            max-width: 1180px;
            margin: 18px auto 0;
            background: #fff;
            border: 1px solid #dde3ea;
            border-radius: 12px;
            padding: 18px;
        }

        .invoice-attachments-panel__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
        }

        .invoice-attachments-panel__grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 12px;
        }

        .invoice-attachment-card {
            display: block;
            padding: 8px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            background: #f8fafc;
            color: #334155;
            text-decoration: none;
            text-align: center;
        }

        .invoice-attachment-card img {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: 7px;
        }

        @media print {
            body * {
                visibility: hidden;
            }

            .invoice-document,
            .invoice-document * {
                visibility: visible;
            }

            .invoice-document {
                position: absolute;
                inset: 0;
                width: 100%;
            }

            .bill-sale-screen {
                padding: 0;
                background: #fff;
            }

            .bill-sale-screen__toolbar,
            .invoice-validation,
            .invoice-attachments-panel,
            .sidebar {
                display: none !important;
            }
        }
    </style>
@endsection

@section('page-title')
    {{ $bladeTitle }}
@endsection

@section('page-links')
    <li class="breadcrumb-item">
        <a href="{{ route('finance.bill-sales.index') }}">{{ $bladeTitle }}</a>
    </li>
    <li class="breadcrumb-item active">#{{ $obj->code }}</li>
@endsection

@section('content')
    <div class="bill-sale-screen">
        <div class="bill-sale-screen__toolbar">
            <a href="{{ route('finance.bill-sales.index', request()->only('shared')) }}"
                class="btn btn-outline-secondary">
                <i class="fa fa-arrow-right me-1"></i>
                العودة إلى الفواتير
            </a>

            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-outline-warning editButton"
                    data-bs-toggle="modal" data-bs-target="#createModal"
                    model-route="{{ route('finance.bill-sales-attachments.edit', $obj->id) }}"
                    model-title="مرفقات الفاتورة رقم {{ $obj->code }}">
                    <i class="fa fa-paperclip me-1"></i>
                    المرفقات ({{ $obj->saleAttachments->count() }})
                </button>
                <a href="{{ route('finance.bill-sales-print.show', $obj->id) }}" target="_blank"
                    class="btn btn-primary">
                    <i class="fa fa-print me-1"></i>
                    معاينة وطباعة
                </a>
            </div>
        </div>

        @if (count($invoiceWarnings))
            <div class="alert alert-warning invoice-validation">
                <h6 class="fw-bold mb-2">
                    <i class="fa fa-exclamation-triangle me-1"></i>
                    تنبيه مراجعة بيانات الفاتورة
                </h6>
                <ul class="mb-0">
                    @foreach ($invoiceWarnings as $warning)
                        <li>{{ $warning }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @include('finance::finance.bill_sales._document')

        <section class="invoice-attachments-panel">
            <div class="invoice-attachments-panel__header">
                <div>
                    <h5 class="fw-bold mb-1">مرفقات الفاتورة</h5>
                    <small class="text-muted">يمكن فتح المرفق بالحجم الكامل في نافذة جديدة.</small>
                </div>
                <button type="button" class="btn btn-sm btn-outline-warning editButton"
                    data-bs-toggle="modal" data-bs-target="#createModal"
                    model-route="{{ route('finance.bill-sales-attachments.edit', $obj->id) }}"
                    model-title="مرفقات الفاتورة رقم {{ $obj->code }}">
                    <i class="fa fa-plus me-1"></i>
                    إدارة المرفقات
                </button>
            </div>

            @if ($obj->saleAttachments->isEmpty())
                <div class="text-center text-muted py-4">
                    <i class="fa fa-paperclip fa-2x mb-2 d-block"></i>
                    لا توجد مرفقات لهذه الفاتورة.
                </div>
            @else
                <div class="invoice-attachments-panel__grid">
                    @foreach ($obj->saleAttachments as $attachment)
                        @php
                            $attachmentUrl = showFile($attachment->file);
                            $isImage = in_array(
                                strtolower(pathinfo((string) $attachment->file, PATHINFO_EXTENSION)),
                                ['jpg', 'jpeg', 'png', 'gif', 'webp'],
                                true,
                            );
                        @endphp
                        <a href="{{ $attachmentUrl }}" target="_blank" class="invoice-attachment-card">
                            @if ($isImage)
                                <img src="{{ $attachmentUrl }}" alt="مرفق الفاتورة">
                            @else
                                <i class="fa fa-file-pdf fa-3x text-danger my-4"></i>
                            @endif
                            <span class="d-block mt-2">فتح المرفق</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
@endsection
