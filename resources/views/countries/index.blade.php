@extends('admin.layout.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-globe"></i>
                        قائمة الدول والمدن
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-success btn-sm" onclick="refreshData()">
                            <i class="fas fa-sync"></i> تحديث البيانات
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="info-box bg-light">
                                <div class="info-box-content">
                                    <span class="info-box-text">إجمالي الدول</span>
                                    <span class="info-box-number">{{ $countries->count() }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-primary">
                                <div class="info-box-content">
                                    <span class="info-box-text">الدول العربية</span>
                                    <span class="info-box-number">{{ $countries->whereIn('code', ['SA', 'EG', 'AE', 'KW', 'QA', 'BH', 'OM', 'JO', 'MA', 'TN', 'DZ', 'LY', 'IQ', 'SY', 'LB', 'PS', 'YE', 'SD', 'SO', 'DJ', 'MR'])->count() }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-success">
                                <div class="info-box-content">
                                    <span class="info-box-text">الدول الأوروبية</span>
                                    <span class="info-box-number">{{ $countries->whereIn('code', ['DE', 'FR', 'GB', 'IT', 'ES', 'NL', 'BE', 'SE', 'NO', 'DK', 'FI', 'PT', 'GR', 'AT', 'CH', 'PL', 'RU'])->count() }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-warning">
                                <div class="info-box-content">
                                    <span class="info-box-text">الدول الآسيوية</span>
                                    <span class="info-box-number">{{ $countries->whereIn('code', ['CN', 'JP', 'KR', 'IN', 'ID', 'MY', 'SG', 'TH', 'PH', 'VN', 'PK', 'BD'])->count() }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        @foreach($countries as $country)
                        <div class="col-md-4 mb-4">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-flag"></i>
                                        {{ $country->name_ar }}
                                        <small class="text-muted">({{ $country->code }})</small>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-6">
                                            <small class="text-muted">العملة</small><br>
                                            <strong>{{ $country->currency_symbol }} {{ $country->currency }}</strong>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted">رمز الهاتف</small><br>
                                            <strong>+{{ $country->phone_code }}</strong>
                                        </div>
                                    </div>

                                    <hr>

                                    <div class="mb-3">
                                        <small class="text-muted">المدن الرئيسية:</small>
                                        <div class="mt-2">
                                            @php
                                                $cities = $country->cities()->take(3)->get();
                                            @endphp
                                            @foreach($cities as $city)
                                                <span class="badge badge-secondary mr-1 mb-1">
                                                    @if($city->is_capital)
                                                        <i class="fas fa-star"></i>
                                                    @endif
                                                    {{ $city->name_ar }}
                                                </span>
                                            @endforeach
                                            @if($country->cities()->count() > 3)
                                                <span class="badge badge-light">+{{ $country->cities()->count() - 3 }} أخرى</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer text-center">
                                    <a href="{{ route('countries.show', $country->id) }}" class="btn btn-primary btn-sm">
                                        <i class="fas fa-eye"></i> عرض التفاصيل
                                    </a>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function refreshData() {
    location.reload();
}

$(document).ready(function() {
    // إضافة تأثيرات بصرية
    $('.card').hover(
        function() { $(this).addClass('shadow-lg'); },
        function() { $(this).removeClass('shadow-lg'); }
    );
});
</script>
@endsection
