@php
    $statement = trim((string) $obj->statement);
    $requestNumber = '-';
    foreach ([
        '/رقم\s+الطلب\s*[:#-]?\s*(\d+)/u',
        '/رقم\s+العقد\s*[:#-]?\s*(\d+)/u',
        '/العقد\s*#\s*(\d+)/u',
        '/العقد\s+(\d+)/u',
        '/الطلب\s*#\s*(\d+)/u',
        '/الطلب\s+(\d+)/u',
    ] as $pattern) {
        if (preg_match($pattern, $statement, $matches)) {
            $requestNumber = $matches[1];
            break;
        }
    }

    $accountLabel = trim(implode(' :: ', array_filter([
        $obj->creditAccount->code ?? null,
        $obj->creditAccount->name ?? '-',
    ])));

    $paymentMethod = getEnumValue('bond_payment_type', $obj->payment_type) ?: '-';
    $bankName = $obj->debitAccount->name ?? '-';
    $amount = rtrim(rtrim(number_format((float) $obj->value, 2, '.', ','), '0'), '.') . ' ر.س';
    $movementAmount = rtrim(rtrim(number_format((float) $obj->value, 2, '.', ','), '0'), '.') . ' ر.س';
    $accountant = optional($obj->createdBy)->name ?: '-';
    $printRoute = route('finance.bonds-print.show', $obj->id) . '?type=qabd';
@endphp

<style>
    .receipt-preview {
        display: flex;
        flex-direction: column;
        gap: 18px;
    }

    .receipt-preview__header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        padding: 18px 20px;
        border-radius: 16px;
        background: linear-gradient(135deg, #f4fbfd 0%, #eef5f7 100%);
        border: 1px solid #d9e7ec;
    }

    .receipt-preview__header h4 {
        margin: 0 0 6px;
        font-size: 22px;
        font-weight: 700;
        color: #16414b;
    }

    .receipt-preview__header p {
        margin: 0;
        color: #55717a;
        line-height: 1.8;
    }

    .receipt-preview__grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 12px;
    }

    .receipt-preview__item {
        padding: 14px 16px;
        border: 1px solid #e8edf0;
        border-radius: 14px;
        background: #fff;
    }

    .receipt-preview__item span {
        display: block;
        margin-bottom: 6px;
        color: #71848b;
        font-size: 12px;
    }

    .receipt-preview__item strong {
        display: block;
        color: #1d2e33;
        line-height: 1.8;
        word-break: break-word;
    }

    .receipt-preview__statement,
    .receipt-preview__files {
        padding: 18px 20px;
        border: 1px solid #e8edf0;
        border-radius: 16px;
        background: #fff;
    }

    .receipt-preview__statement h5,
    .receipt-preview__files h5 {
        margin: 0 0 12px;
        font-size: 15px;
        font-weight: 700;
        color: #16414b;
    }

    .receipt-preview__statement p {
        margin: 0;
        color: #394b50;
        line-height: 2;
    }

    .receipt-preview__files-list {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .receipt-preview__files-list .btn {
        white-space: nowrap;
    }
</style>

<div class="receipt-preview">
    <div class="receipt-preview__header">
        <div>
            <h4>سند قبض #{{ $obj->id }}</h4>
            <p>{{ $accountLabel ?: '-' }}</p>
        </div>
        <a href="{{ $printRoute }}" target="_blank" class="btn btn-success">
            <i class="fa fa-print"></i>
            طباعة السند
        </a>
    </div>

    <div class="receipt-preview__grid">
        <div class="receipt-preview__item">
            <span>رقم الطلب</span>
            <strong>{{ $requestNumber }}</strong>
        </div>
        <div class="receipt-preview__item">
            <span>التاريخ</span>
            <strong dir="ltr">{{ $obj->date }}</strong>
        </div>
        <div class="receipt-preview__item">
            <span>المبلغ المطلوب</span>
            <strong>{{ $amount }}</strong>
        </div>
        <div class="receipt-preview__item">
            <span>المبلغ المقبوض</span>
            <strong>{{ $amount }}</strong>
        </div>
        <div class="receipt-preview__item">
            <span>الحركة المالية</span>
            <strong>{{ $movementAmount }}</strong>
        </div>
        <div class="receipt-preview__item">
            <span>بواسطة</span>
            <strong>{{ $accountant }}</strong>
        </div>
        <div class="receipt-preview__item">
            <span>طريقة السداد</span>
            <strong>{{ $paymentMethod }}</strong>
        </div>
        <div class="receipt-preview__item">
            <span>حساب التحصيل / البنك</span>
            <strong>{{ $bankName }}</strong>
        </div>
        <div class="receipt-preview__item">
            <span>مركز التكلفة</span>
            <strong>{{ $obj->costCenter->title ?? '-' }}</strong>
        </div>
        @if ($obj->payment_type != Nami\Finance\Enums\BondPaymentTypes::Cash->value)
            <div class="receipt-preview__item">
                <span>رقم التحويل / الشيك</span>
                <strong>{{ $obj->transfer_number ?: ($obj->check->check_num ?? '-') }}</strong>
            </div>
        @endif
        @if ($obj->is_tax == 1)
            <div class="receipt-preview__item">
                <span>صافي السند</span>
                <strong>{{ rtrim(rtrim(number_format((float) $obj->net, 2, '.', ','), '0'), '.') }} ر.س</strong>
            </div>
        @endif
    </div>

    <div class="receipt-preview__statement">
        <h5>البيان</h5>
        <p>{{ $statement ?: '-' }}</p>
    </div>

    @if ($obj->file || $obj->transfer_file)
        <div class="receipt-preview__files">
            <h5>المرفقات</h5>
            <div class="receipt-preview__files-list">
                @if ($obj->file)
                    <a href="{{ showFile($obj->file) }}" download class="btn btn-outline-primary">
                        <i class="fa fa-download"></i>
                        مستند السند
                    </a>
                @endif
                @if ($obj->transfer_file)
                    <a href="{{ showFile($obj->transfer_file) }}" download class="btn btn-outline-primary">
                        <i class="fa fa-download"></i>
                        مستند التحويل
                    </a>
                @endif
            </div>
        </div>
    @endif
</div>
