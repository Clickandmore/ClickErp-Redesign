@extends('admin.layout.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- معلومات الدولة -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-flag"></i>
                        {{ $country->name_ar }}
                        <small class="text-muted">({{ $country->code }})</small>
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('countries.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> العودة للقائمة
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-primary">
                                            <i class="fas fa-globe"></i>
                                        </span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">الاسم بالإنجليزية</span>
                                            <span class="info-box-number">{{ $country->name_en }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-success">
                                            <i class="fas fa-phone"></i>
                                        </span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">رمز الهاتف</span>
                                            <span class="info-box-number">+{{ $country->phone_code }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-warning">
                                            <i class="fas fa-dollar-sign"></i>
                                        </span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">العملة</span>
                                            <span class="info-box-number">{{ $country->currency_symbol }} {{ $country->currency }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-info">
                                            <i class="fas fa-city"></i>
                                        </span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">عدد المدن</span>
                                            <span class="info-box-number">{{ $country->cities->count() }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="text-center">
                                <h4>العاصمة</h4>
                                @php
                                    $capital = $country->cities->where('is_capital', true)->first();
                                @endphp
                                @if($capital)
                                    <div class="alert alert-success">
                                        <i class="fas fa-star"></i>
                                        <strong>{{ $capital->name_ar }}</strong><br>
                                        <small>{{ $capital->name_en }}</small>
                                        @if($capital->latitude && $capital->longitude)
                                            <br><small class="text-muted">
                                                📍 {{ $capital->latitude }}, {{ $capital->longitude }}
                                            </small>
                                        @endif
                                    </div>
                                @else
                                    <div class="alert alert-warning">
                                        لم يتم تحديد العاصمة
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- قائمة المدن -->
            <div class="card mt-4">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="fas fa-city"></i>
                        مدن {{ $country->name_ar }}
                        <span class="badge badge-primary">{{ $country->cities->count() }}</span>
                    </h4>
                </div>

                <div class="card-body">
                    <div class="row">
                        @forelse($country->cities as $city)
                        <div class="col-md-6 mb-3">
                            <div class="card {{ $city->is_capital ? 'border-success' : 'border-light' }}">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="card-title mb-1">
                                                @if($city->is_capital)
                                                    <i class="fas fa-star text-warning"></i>
                                                @endif
                                                {{ $city->name_ar }}
                                            </h6>
                                            <small class="text-muted">{{ $city->name_en }}</small>
                                        </div>

                                        @if($city->is_capital)
                                        <span class="badge badge-success">العاصمة</span>
                                        @endif
                                    </div>

                                    @if($city->latitude && $city->longitude)
                                    <div class="mt-2">
                                        <small class="text-muted">
                                            <i class="fas fa-map-marker-alt"></i>
                                            {{ number_format($city->latitude, 4) }}, {{ number_format($city->longitude, 4) }}
                                        </small>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="col-12">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                لا توجد مدن مسجلة لهذه الدولة
                            </div>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
.info-box {
    box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
    border-radius: .25rem;
    margin-bottom: 1rem;
    background-color: #fff;
    display: flex;
    align-items: center;
    width: 100%;
}

.info-box-icon {
    border-top-left-radius: .25rem;
    border-bottom-left-radius: .25rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 1.875rem;
    width: 70px;
}

.info-box-content {
    padding: 5px 10px;
    margin-left: 0;
    display: flex;
    flex-direction: column;
}

.info-box-text {
    text-transform: uppercase;
    font-weight: 700;
    font-size: .6875rem;
    color: #6c757d;
}

.info-box-number {
    font-size: 1.125rem;
    font-weight: 700;
    color: #495057;
}
</style>
@endsection
