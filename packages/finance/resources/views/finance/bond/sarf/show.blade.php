@php
    $constraintUrl = $obj->constraint_id
        ? route('finance.add-constraint.show', ['add_constraint' => $obj->constraint_id])
        : null;
    $printUrl = route('finance.bonds-print.show', ['bonds_print' => $obj->id, 'type' => 'sarf']);
@endphp

<div class="p-1">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h5 class="mb-1 fw-bold">سند صرف #{{ $obj->code }}</h5>
            <span class="text-muted" dir="ltr">{{ $obj->date }}</span>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ $printUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">
                <i class="fa fa-print"></i> طباعة السند
            </a>
            @if($constraintUrl)
                <a href="{{ $constraintUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-primary">
                    <i class="fa fa-book"></i> فتح القيد #{{ $obj->constraint_id }}
                </a>
            @endif
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="border rounded-3 p-3 h-100 bg-light">
                <small class="text-muted d-block mb-1">المبلغ</small>
                <strong class="fs-5" dir="ltr">{{ number_format((float) $obj->value, 2) }}</strong>
            </div>
        </div>
        <div class="col-md-4">
            <div class="border rounded-3 p-3 h-100 bg-light">
                <small class="text-muted d-block mb-1">الحالة</small>
                @if($obj->is_confirm)
                    <span class="badge bg-success"><i class="fa fa-check-circle"></i> معتمد</span>
                @else
                    <span class="badge bg-warning text-dark"><i class="fa fa-clock"></i> بانتظار الاعتماد</span>
                @endif
            </div>
        </div>
        <div class="col-md-4">
            <div class="border rounded-3 p-3 h-100 bg-light">
                <small class="text-muted d-block mb-1">طريقة الدفع</small>
                <strong>{{ getEnumValue('bond_payment_type', $obj->payment_type) }}</strong>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered align-middle mb-3">
            <tbody>
                <tr>
                    <th class="bg-light" style="width: 28%">المستفيد / الحساب المدين</th>
                    <td>{{ optional($obj->debitAccount)->name ?: '-' }}</td>
                </tr>
                <tr>
                    <th class="bg-light">الصندوق / البنك</th>
                    <td>{{ optional($obj->creditAccount)->name ?: '-' }}</td>
                </tr>
                <tr>
                    <th class="bg-light">البيان</th>
                    <td class="text-wrap">{{ $obj->statement ?: '-' }}</td>
                </tr>
                <tr>
                    <th class="bg-light">رقم الدفتر</th>
                    <td>{{ filled(trim((string) $obj->bond_book_num)) ? $obj->bond_book_num : '-' }}</td>
                </tr>
                <tr>
                    <th class="bg-light">مركز التكلفة</th>
                    <td>{{ optional($obj->costCenter)->title ?: '-' }}</td>
                </tr>
                <tr>
                    <th class="bg-light">أنشئ بواسطة</th>
                    <td>{{ optional($obj->createdBy)->name ?: '-' }}</td>
                </tr>
                @if($obj->payment_type != \Nami\Finance\Enums\BondPaymentTypes::Cash->value)
                    <tr>
                        <th class="bg-light">رقم التحويل</th>
                        <td>{{ $obj->transfer_number ?: '-' }}</td>
                    </tr>
                @endif
                <tr>
                    <th class="bg-light">الضريبة</th>
                    <td>
                        @if($obj->is_tax)
                            النسبة {{ number_format((float) optional($obj->bondTax)->tax_rate, 2) }}% —
                            القيمة {{ number_format((float) optional($obj->bondTax)->tax_amount, 2) }} —
                            الصافي {{ number_format((float) $obj->net, 2) }}
                        @else
                            بدون ضريبة
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    @if($obj->file || $obj->transfer_file)
        <div class="d-flex flex-wrap gap-2">
            @if($obj->file)
                <a href="{{ showFile($obj->file) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">
                    <i class="fa fa-paperclip"></i> مستند السند
                </a>
            @endif
            @if($obj->transfer_file)
                <a href="{{ showFile($obj->transfer_file) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">
                    <i class="fa fa-paperclip"></i> مستند التحويل
                </a>
            @endif
        </div>
    @endif
</div>
