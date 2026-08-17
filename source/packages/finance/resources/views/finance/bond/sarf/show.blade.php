@php
    $constraintUrl = $obj->constraint_id
        ? route('finance.add-constraint.show', ['add_constraint' => $obj->constraint_id])
        : null;
    $printUrl = route('finance.bonds-print.show', [
        'bonds_print' => $obj->id,
        'type' => 'sarf',
    ]);
    $debitAccount = trim(implode(' - ', array_filter([
        optional($obj->debitAccount)->code,
        optional($obj->debitAccount)->name,
    ]))) ?: '-';
    $creditAccount = trim(implode(' - ', array_filter([
        optional($obj->creditAccount)->code,
        optional($obj->creditAccount)->name,
    ]))) ?: '-';
@endphp

<style>
    .sarf-details { color: #172033; }
    .sarf-details__head {
        padding: 16px;
        color: #fff;
        background: linear-gradient(135deg, #0f766e, #115e59);
        border-radius: 12px;
    }
    .sarf-details__amount { font-size: 1.75rem; font-weight: 800; direction: ltr; }
    .sarf-details__steps {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 8px;
        margin: 14px 0;
    }
    .sarf-details__step {
        padding: 10px;
        border: 1px solid #e2e8f0;
        border-radius: 9px;
        background: #f8fafc;
        text-align: center;
    }
    .sarf-details__step.done { color: #166534; border-color: #bbf7d0; background: #f0fdf4; }
    .sarf-details__step.waiting { color: #92400e; border-color: #fde68a; background: #fffbeb; }
    .sarf-details__step i { display: block; margin-bottom: 4px; }
    .sarf-details__card {
        height: 100%;
        padding: 13px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #fff;
    }
    .sarf-details__label { display: block; margin-bottom: 5px; color: #64748b; font-size: .78rem; }
    .sarf-details__statement {
        padding: 13px;
        border: 1px solid #dbeafe;
        border-radius: 10px;
        background: #f8fafc;
        line-height: 1.8;
        word-break: break-word;
    }
    @media(max-width: 700px) {
        .sarf-details__steps { grid-template-columns: 1fr; }
    }
</style>

<div class="sarf-details">
    <div class="sarf-details__head d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <div class="small opacity-75">سند صرف مالي</div>
            <h4 class="mb-1 fw-bold">سند رقم #{{ $obj->code ?: $obj->id }}</h4>
            <div class="small opacity-75">
                رقم النظام #{{ $obj->id }}
                @if(filled(trim((string) $obj->bond_book_num)))
                    — رقم الدفتر {{ $obj->bond_book_num }}
                @endif
            </div>
        </div>
        <div class="text-md-end">
            <div class="small opacity-75">إجمالي السند</div>
            <div class="sarf-details__amount">{{ number_format((float) $obj->value, 2) }} ر.س</div>
            <div dir="ltr">{{ $obj->date }}</div>
        </div>
    </div>

    <div class="sarf-details__steps">
        <div class="sarf-details__step done">
            <i class="fa fa-file-invoice-dollar"></i>
            <strong>1. إصدار السند</strong>
            <div class="small">تم الحفظ</div>
        </div>
        <div class="sarf-details__step {{ $obj->is_confirm ? 'done' : 'waiting' }}">
            <i class="fa {{ $obj->is_confirm ? 'fa-check-circle' : 'fa-clock' }}"></i>
            <strong>2. الاعتماد المالي</strong>
            <div class="small">{{ $obj->is_confirm ? 'تم الاعتماد' : 'بانتظار الاعتماد' }}</div>
        </div>
        <div class="sarf-details__step {{ $obj->constraint_id ? 'done' : 'waiting' }}">
            <i class="fa fa-book"></i>
            <strong>3. القيد المحاسبي</strong>
            <div class="small">{{ $obj->constraint_id ? '#'.$obj->constraint_id : 'ينشأ بعد الاعتماد' }}</div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="sarf-details__card">
                <span class="sarf-details__label">الحساب المدين / المستفيد</span>
                <strong>{{ $debitAccount }}</strong>
            </div>
        </div>
        <div class="col-md-6">
            <div class="sarf-details__card">
                <span class="sarf-details__label">الحساب الدائن / الصندوق أو البنك</span>
                <strong>{{ $creditAccount }}</strong>
            </div>
        </div>
        <div class="col-md-4">
            <div class="sarf-details__card">
                <span class="sarf-details__label">طريقة الدفع</span>
                <strong>{{ getEnumValue('bond_payment_type', $obj->payment_type) ?: '-' }}</strong>
            </div>
        </div>
        <div class="col-md-4">
            <div class="sarf-details__card">
                <span class="sarf-details__label">مرجع التحويل / الشيك</span>
                <strong>{{ $obj->transfer_number ?: (optional($obj->check)->check_num ?: '-') }}</strong>
                @if(optional($obj->check)->check_date)
                    <div class="small text-muted mt-1" dir="ltr">{{ $obj->check->check_date }}</div>
                @endif
            </div>
        </div>
        <div class="col-md-4">
            <div class="sarf-details__card">
                <span class="sarf-details__label">مركز التكلفة</span>
                <strong>{{ optional($obj->costCenter)->title ?: '-' }}</strong>
            </div>
        </div>
    </div>

    <div class="mb-3">
        <span class="sarf-details__label">بيان السند</span>
        <div class="sarf-details__statement">{{ $obj->statement ?: '-' }}</div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="sarf-details__card">
                <span class="sarf-details__label">أنشئ بواسطة</span>
                <strong>{{ optional($obj->createdBy)->name ?: '-' }}</strong>
            </div>
        </div>
        <div class="col-md-4">
            <div class="sarf-details__card">
                <span class="sarf-details__label">الضريبة</span>
                @if($obj->is_tax)
                    <strong>{{ number_format((float) optional($obj->bondTax)->tax_amount, 2) }} ر.س</strong>
                    <div class="small text-muted">الصافي: {{ number_format((float) $obj->net, 2) }} ر.س</div>
                @else
                    <strong>بدون ضريبة</strong>
                @endif
            </div>
        </div>
        <div class="col-md-4">
            <div class="sarf-details__card">
                <span class="sarf-details__label">حالة السند</span>
                <span class="badge {{ $obj->is_confirm ? 'bg-success' : 'bg-warning text-dark' }}">
                    {{ $obj->is_confirm ? 'معتمد ومرحّل' : 'بانتظار الاعتماد' }}
                </span>
            </div>
        </div>
    </div>

    <div class="d-flex flex-wrap justify-content-between gap-2 pt-3 border-top">
        <div class="d-flex flex-wrap gap-2">
            @if($obj->file)
                <a href="{{ showFile($obj->file) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">
                    <i class="fa fa-paperclip"></i> مستند السند
                </a>
            @endif
            @if($obj->transfer_file)
                <a href="{{ showFile($obj->transfer_file) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">
                    <i class="fa fa-paperclip"></i> مستند التحويل
                </a>
            @endif
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if($constraintUrl)
                <a href="{{ $constraintUrl }}" target="_blank" rel="noopener" class="btn btn-primary">
                    <i class="fa fa-book"></i> فتح القيد #{{ $obj->constraint_id }}
                </a>
            @endif
            <a href="{{ $printUrl }}" target="_blank" rel="noopener" class="btn btn-success">
                <i class="fa fa-print"></i> معاينة وطباعة السند
            </a>
        </div>
    </div>
</div>
