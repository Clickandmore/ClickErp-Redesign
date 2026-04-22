@extends('admin.layout.indexs.index')

@section('content')
    @php
        $isEditingTrip = isset($travel) && $travel;
        $canManageTrip = $isEditingTrip
            ? @AdminGuardHasPerm(\App\PermKeys::TRAVEL_EDIT)
            : @AdminGuardHasPerm(\App\PermKeys::TRAVEL_CREATE);
    @endphp

    @if ($canManageTrip)
    <div class="container-fluid">
        <div class="page-title-box">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h4 class="page-title">
                        <i class="fa fa-plane"></i> {{ $isEditingTrip ? 'تعديل رحلة ترحيل' : 'جدولة رحلة ترحيل جديدة' }}
                    </h4>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                @if ($contract)
                    <div class="alert alert-info mb-4">
                        <h5><i class="fa fa-info-circle"></i> معلومات العقد</h5>
                        <div class="row mt-3">
                            <div class="col-md-4">
                                <strong>رقم العقد:</strong> {{ $contract->id }}
                            </div>
                            <div class="col-md-4">
                                <strong>العميل:</strong> {{ optional($contract->user)->name ?? 'غير محدد' }}
                            </div>
                            <div class="col-md-4">
                                <strong>العاملة:</strong> {{ optional($contract->maid)->name ?? 'غير محدد' }}
                            </div>
                        </div>
                    </div>
                @endif

                <form id="form-deportation-travel" action="{{ $isEditingTrip ? route('DeportationTravel.update', $travel->id) : route('DeportationTravel.store') }}" method="POST">
                    @csrf

                    @if ($contract)
                        <input type="hidden" name="contract_id" value="{{ $contract->id }}">
                    @else
                        <div class="mb-3">
                            <label class="form-label">رقم العقد <span class="text-danger">*</span></label>
                            <input type="number" name="contract_id" class="form-control" value="{{ old('contract_id', $travel->order_id ?? '') }}" required>
                        </div>
                    @endif

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">المطار <span class="text-danger">*</span></label>
                            <select name="arrival_airport" class="form-control" required>
                                <option value="">اختر المطار</option>
                                <option value="مطار الملك خالد الدولي - الرياض" {{ old('arrival_airport', $travel->arrival_airport ?? '') === 'مطار الملك خالد الدولي - الرياض' ? 'selected' : '' }}>مطار الملك خالد الدولي - الرياض</option>
                                <option value="مطار الملك عبدالعزيز الدولي - جدة" {{ old('arrival_airport', $travel->arrival_airport ?? '') === 'مطار الملك عبدالعزيز الدولي - جدة' ? 'selected' : '' }}>مطار الملك عبدالعزيز الدولي - جدة
                                </option>
                                <option value="مطار الملك فهد الدولي - الدمام" {{ old('arrival_airport', $travel->arrival_airport ?? '') === 'مطار الملك فهد الدولي - الدمام' ? 'selected' : '' }}>مطار الملك فهد الدولي - الدمام</option>
                                <option value="مطار الأمير محمد بن عبدالعزيز - المدينة" {{ old('arrival_airport', $travel->arrival_airport ?? '') === 'مطار الأمير محمد بن عبدالعزيز - المدينة' ? 'selected' : '' }}>مطار الأمير محمد بن عبدالعزيز -
                                    المدينة
                                </option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">وسيلة السفر <span class="text-danger">*</span></label>
                            <select name="travel_with" class="form-control" required>
                                <option value="">اختر وسيلة السفر</option>
                                <option value="مطار" {{ old('travel_with', $travel->travel_with ?? '') === 'مطار' ? 'selected' : '' }}>مطار</option>
                                <option value="نقل جماعي" {{ old('travel_with', $travel->travel_with ?? '') === 'نقل جماعي' ? 'selected' : '' }}>نقل جماعي</option>
                                <option value="قطار" {{ old('travel_with', $travel->travel_with ?? '') === 'قطار' ? 'selected' : '' }}>قطار</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">تاريخ الرحلة <span class="text-danger">*</span></label>
                            <input type="date" name="arrival_date" class="form-control" value="{{ old('arrival_date', isset($travel->arrival_date) ? \Carbon\Carbon::parse($travel->arrival_date)->format('Y-m-d') : '') }}" required>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">وقت الرحلة <span class="text-danger">*</span></label>
                            <input type="time" name="arrival_time" class="form-control" value="{{ old('arrival_time', $travel->arrival_time ?? '') }}" required>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">شركة الطيران</label>
                            <input type="text" name="flight_company" class="form-control" value="{{ old('flight_company', $travel->flight_company ?? '') }}" placeholder="اختياري">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">رقم الرحلة</label>
                            <input type="text" name="flight_number" class="form-control" value="{{ old('flight_number', $travel->flight_number ?? '') }}" placeholder="اختياري">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">ملاحظات</label>
                            <textarea name="notes" class="form-control" rows="3">{{ old('notes', $travel->notes ?? '') }}</textarea>
                        </div>
                    </div>

                    <div class="text-end mt-4">
                        <button type="submit" id="submitTravelBtn" class="btn btn-success">
                            <i class="fa fa-save"></i> {{ $isEditingTrip ? 'تحديث الرحلة' : 'جدولة الرحلة' }}
                        </button>

                        <a href="{{ route('DeportationTravel.index') }}" class="btn btn-secondary">
                            <i class="fa fa-times"></i> إلغاء
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @else
        @include('components.no_permissions')
    @endif
@endsection
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script>
    $(document).ready(function() {
        $('#form-deportation-travel').on('submit', function(e) {
            e.preventDefault();

            let form = $(this);
            let formData = new FormData(this);
            let url = form.attr('action');
            let $btn = $('#submitTravelBtn');
            let originalHtml = $btn.html(); // نحفظ الشكل الأصلي

            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function() {
                    $btn.prop('disabled', true).html(
                        '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> جاري الحفظ...'
                    );
                },
                success: function(response) {
                    handleSuccessResponce(response)
                    @if (!$isEditingTrip)
                    $('#form-deportation-travel')[0].reset();
                    @endif
                },
                error: function(xhr) {
                    let errorMessages = '';
                    
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        for (let key in errors) {
                            errorMessages +=
                                `<p style="margin:0 0 5px; color:red; text-align:center;">${errors[key][0]}</p>`;
                        }
                        Swal.fire({
                            icon: 'error',
                            title: '{{ __('ingaz.error') }}',
                            html: `<div>${errorMessages}</div>`,
                        });
                    } else {
                        let errors = xhr.responseJSON.errors
                        if (errors) {
                            for (let key in errors) {
                                errorMessages +=
                                    `<p style="margin:0 0 5px; color:red; text-align:center;">${errors[key][0]}</p>`;
                            }
                        } else {
                            Swal.fire('{{ __('ingaz.error') }}', xhr.responseJSON.message || '{{ __('ingaz.error_from_server') }}', 'error');
                        }

                    }
                },
                complete: function() {
                    // ⬅️ بعد ما يخلص (سواء نجاح أو خطأ) يرجع الزرار زي ما كان
                    $btn.prop('disabled', false).html(originalHtml);
                }
            });
        });
    });
</script>
