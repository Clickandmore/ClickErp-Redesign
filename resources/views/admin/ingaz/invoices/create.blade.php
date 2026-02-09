@extends('admin.layout.indexs.index')

@section('page-title')
    إصدار فاتورة
@endsection

@section('content')
    @php
        $contractId = $contractId ?? request('contract_id');
        $selectedContractId = old('contract_id', $contractId);
    @endphp

    <form method="post" action="{{ route('invoices.store') }}" class="row g-3" id="invoiceForm">
        @csrf
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row g-3">
                        @if (!$contractId)
                            <div class="col-md-6">
                                <label class="form-label">العقد</label>
                                <select name="contract_id" class="form-select" required>
                                    <option value="" selected disabled>اختر العقد</option>
                                    @foreach ($contracts ?? [] as $contract)
                                        <option value="{{ $contract->id }}">
                                            {{ $contract->desc ?? $contract->id }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            <input type="hidden" name="contract_id" value="{{ $contractId }}">
                        @endif

                        <div class="col-md-6">
                            <label class="form-label">شركة التأمين</label>
                            {{-- <input type="text" name="insurance_company" class="form-control" value="{{ old('insurance_company') }}"> --}}
                            <select name="insurance_company" class="form-select">
                                <option value="" selected disabled>شركة التأمين</option>
                                {{-- @foreach ($contracts ?? [] as $contract)
                                    <option value="{{ $contract->id }}">{{ $contract->desc ?? $contract->id }}</option>
                                @endforeach --}}
                            </select>
                        </div>

                        <div class="my-4 col-12 row">
                            <div class="col-md-6">
                                <label class="form-label" for="direct_recruitment_cost">تكاليف الاستقدام المباشر</label>
                                <input type="number" step="0.01" min="0" name="direct_recruitment_cost"
                                    id="direct_recruitment_cost" class="form-control"
                                    value="{{ old('direct_recruitment_cost', 0) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">تكاليف الاستقدام الخارجية</label>
                                <input type="number" step="0.01" min="0" name="external_recruitment_cost"
                                    id="external_recruitment_cost" class="form-control"
                                    value="{{ old('external_recruitment_cost', 0) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">تكاليف حكومية</label>
                                <input type="number" step="0.01" min="0" name="government_cost"
                                    id="government_cost" class="form-control" value="{{ old('government_cost', 0) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">تكلفة التذكرة الداخلية</label>
                                <input type="number" step="0.01" min="0" name="internal_ticket_cost"
                                    id="internal_ticket_cost" class="form-control"
                                    value="{{ old('internal_ticket_cost', 0) }}">
                            </div>
                        </div>

                        <div class="my-4 col-12 row">
                            <div class="col-md-6">
                                <label class="form-label">عدد المستقدمين في التأشيرة</label>
                                <input type="number" min="1" name="visa_recruited_count" class="form-control"
                                    id="visa_recruited_count" value="{{ old('visa_recruited_count', 1) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">عدد تكاليف الاستقدام المباشر</label>
                                <input type="number" min="1" name="direct_recruitment_count" class="form-control"
                                    id="direct_recruitment_count" value="{{ old('direct_recruitment_count', 1) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">عدد تكاليف الاستقدام الخارجية</label>
                                <input type="number" min="1" name="external_recruitment_count" class="form-control"
                                    id="external_recruitment_count" value="{{ old('external_recruitment_count', 1) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">عدد المؤمن عليهم</label>
                                <input type="number" min="1" name="insured_count" class="form-control"
                                    id="insured_count" value="{{ old('insured_count', 1) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">عدد تكاليف حكومية</label>
                                <input type="number" min="1" name="government_cost_count" class="form-control"
                                    id="government_cost_count" value="{{ old('government_cost_count', 1) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">عدد تكلفة التذكرة الداخلية</label>
                                <input type="number" min="1" name="internal_ticket_count" class="form-control"
                                    id="internal_ticket_count" value="{{ old('internal_ticket_count', 1) }}">
                            </div>
                        </div>

                        <div class="col-md-3 col-sm-6">
                            <label class="form-label text-success">المجموع</label>
                            <input type="number" step="0.01" min="0" name="subtotal" id="subtotal"
                                class="form-control" value="{{ old('subtotal', 0) }}">
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <label class="form-label">خصم</label>
                            <input type="number" step="0.01" min="0" name="discount" class="form-control"
                                id="discount" value="{{ old('discount', 0) }}">
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <label class="form-label text-primary" for="vat_amount">ضريبة القيمة المضافة</label>
                            <input type="number" step="0.01" min="0" name="vat_amount" id="vat_amount"
                                class="form-control" value="{{ old('vat_amount', 0) }}">
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <label class="form-label text-danger">المطلوب من العميل</label>
                            <input type="number" step="0.01" min="0" name="total_due" class="form-control"
                                id="total_due" value="{{ old('total_due', 0) }}">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-primary" id="btnSubmit">حفظ</button>
            <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary">رجوع</a>
        </div>
    </form>
@endsection

@section('js')
    <script>
        $(document).ready(function() {

            let vat_amount = $('#vat_amount');

            $(document).on(
                'input',
                '#direct_recruitment_cost, #external_recruitment_cost, #government_cost, #internal_ticket_cost, #visa_recruited_count, #direct_recruitment_count, #external_recruitment_count, #insured_count, #government_cost_count, #internal_ticket_count, #discount',
                function() {

                    let direct = parseFloat($('#direct_recruitment_cost').val()) || 0;
                    let external = parseFloat($('#external_recruitment_cost').val()) || 0;
                    let government = parseFloat($('#government_cost').val()) || 0;
                    let ticket = parseFloat($('#internal_ticket_cost').val()) || 0;

                    // المجموع
                    let subtotal = (direct * $('#direct_recruitment_count').val()) + (external * $(
                        '#external_recruitment_count').val()) + (government * $('#government_cost_count')
                        .val()) + (ticket * $('#internal_ticket_count').val());
                    $('#subtotal').val(subtotal.toFixed(2));

                    // الضريبة 15% من الاستقدام المباشر فقط
                    let vat = direct * 0.15;
                    vat_amount.val(vat.toFixed(2));

                    // المطلوب من العميل
                    let totalDue = (subtotal + vat) - (parseFloat($('#discount').val()) || 0);

                    $('#total_due').val(totalDue.toFixed(2));
                }
            );


            $("#invoiceForm").on('submit', function(e) {
                e.preventDefault();
                let $form = $(this);
                let url = $form.attr('action');
                let method = $form.attr('method') || 'POST';
                let data = $form.serialize();

                $.ajax({
                    url: url,
                    method: method,
                    data: data,
                    beforeSend: function() {
                        $('#btnSubmit').html(`
    <div class="spinner-border spinner-border-sm" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
`);
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#btnSubmit').html('حفظ');

                            Swal.fire({
                                icon: 'success',
                                title: 'نجح',
                                text: response.message || "تم اضافة الفاتورة بنجاح"
                            }).then(() => {
                                if (response.redirect) {
                                    window.location.href = response.redirect;
                                } else {
                                    window.location.href = "{{ route('RecruitmentContract.index') }}";
                                }
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'خطأ',
                                text: response.message || 'حدث خطأ أثناء اضافة الفاتورة'
                            });
                        }
                    },
                    error: function(xhr) {
                        console.log(xhr);
                         $('#btnSubmit').html('حفظ');
                         
                        if (xhr.status == 422) {
                            Swal.fire({
                                icon: 'error',
                                title: 'خطأ',
                                text: xhr.responseJSON.message ||
                                    'حدث خطأ أثناء الارسال'
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'خطأ',
                                text: 'حدث خطأ أثناء الارسال'
                            });
                        }
                    },
                });
            })
        });
    </script>
@endsection
