@extends('admin.layout.indexs.index')

@section('content')
<div class="container-fluid">
    <div class="page-title-box">
        <div class="row align-items-center">
            <div class="col-sm-6">
                <h4 class="page-title">
                    <i class="fa fa-plane"></i> جدولة رحلة ترحيل جديدة
                </h4>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            @if($contract)
            <div class="alert alert-info mb-4">
                <h5><i class="fa fa-info-circle"></i> معلومات العقد</h5>
                <div class="row mt-3">
                    <div class="col-md-4">
                        <strong>رقم العقد:</strong> {{ $contract->id }}
                    </div>
                    <div class="col-md-4">
                        <strong>العميل:</strong> {{ $contract->user->name }}
                    </div>
                    <div class="col-md-4">
                        <strong>العاملة:</strong> {{ $contract->maid->name }}
                    </div>
                </div>
            </div>
            @endif

            <form action="{{ route('DeportationTravel.store') }}" method="POST">
                @csrf

                @if($contract)
                <input type="hidden" name="contract_id" value="{{ $contract->id }}">
                @else
                <div class="mb-3">
                    <label class="form-label">رقم العقد <span class="text-danger">*</span></label>
                    <input type="number" name="contract_id" class="form-control" required>
                </div>
                @endif

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">المطار <span class="text-danger">*</span></label>
                        <select name="arrival_airport" class="form-control" required>
                            <option value="">اختر المطار</option>
                            <option value="مطار الملك خالد الدولي - الرياض">مطار الملك خالد الدولي - الرياض</option>
                            <option value="مطار الملك عبدالعزيز الدولي - جدة">مطار الملك عبدالعزيز الدولي - جدة</option>
                            <option value="مطار الملك فهد الدولي - الدمام">مطار الملك فهد الدولي - الدمام</option>
                            <option value="مطار الأمير محمد بن عبدالعزيز - المدينة">مطار الأمير محمد بن عبدالعزيز - المدينة</option>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">وسيلة السفر <span class="text-danger">*</span></label>
                        <select name="travel_with" class="form-control" required>
                            <option value="">اختر وسيلة السفر</option>
                            <option value="مطار">مطار</option>
                            <option value="نقل جماعي">نقل جماعي</option>
                            <option value="قطار">قطار</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">تاريخ الرحلة <span class="text-danger">*</span></label>
                        <input type="date" name="arrival_date" class="form-control" required>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">وقت الرحلة <span class="text-danger">*</span></label>
                        <input type="time" name="arrival_time" class="form-control" required>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">شركة الطيران</label>
                        <input type="text" name="flight_company" class="form-control" placeholder="اختياري">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">رقم الرحلة</label>
                        <input type="text" name="flight_number" class="form-control" placeholder="اختياري">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>

                <div class="text-end mt-4">
                    <button type="submit" class="btn btn-success">
                        <i class="fa fa-save"></i> جدولة الرحلة
                    </button>
                    <a href="{{ route('DeportationTravel.index') }}" class="btn btn-secondary">
                        <i class="fa fa-times"></i> إلغاء
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
