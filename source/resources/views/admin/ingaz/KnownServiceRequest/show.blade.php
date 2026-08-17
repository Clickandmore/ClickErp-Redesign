@extends('admin.layout.indexs.index')

@section('page-title')
    عرض طلب خدمة معروفة
@endsection

@section('content')
    <div class="container-fluid">
        <div class="mb-3 mt-3">
            <a href="{{ route('known-service-requests.index') }}" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> العودة للقائمة
            </a>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0"><i class="fas fa-user"></i> بيانات العميل</h5></div>
                    <div class="card-body">
                        <ul class="mb-0">
                            <li><strong>الاسم:</strong> {{ $knownServiceRequest->client_name }}</li>
                            <li><strong>الهاتف:</strong> {{ $knownServiceRequest->client_phone }}</li>
                            <li><strong>البريد الإلكتروني:</strong> {{ $knownServiceRequest->client_email ?? '-' }}</li>
                            <li><strong>العنوان:</strong> {{ $knownServiceRequest->client_address ?? '-' }}</li>
                            <li><strong>المدينة:</strong> {{ $knownServiceRequest->city ?? '-' }}</li>
                            <li><strong>المستخدم:</strong> {{ $knownServiceRequest->user?->name ?? '-' }}</li>
                            <li><strong>الحالة:</strong>
                                @if ($knownServiceRequest->status === \App\Enums\ingaz\RequestsStatusEnum::Approved->value)
                                    <span class="badge bg-success">معتمد</span>
                                @elseif ($knownServiceRequest->status === \App\Enums\ingaz\RequestsStatusEnum::Rejected->value)
                                    <span class="badge bg-danger">مرفوض</span>
                                @else
                                    <span class="badge bg-warning">معلق</span>
                                @endif
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0"><i class="fas fa-female"></i> بيانات العاملة</h5></div>
                    <div class="card-body">
                        <ul class="mb-0">
                            <li><strong>اسم العاملة:</strong> {{ $knownServiceRequest->worker_name }}</li>
                            <li><strong>الجنسية:</strong> {{ $knownServiceRequest->nationality?->title ?? '-' }}</li>
                            <li><strong>رقم الجواز:</strong> {{ $knownServiceRequest->passport_number }}</li>
                            <li><strong>تاريخ انتهاء الجواز:</strong> {{ $knownServiceRequest->passport_expiration_date ?? '-' }}</li>
                            <li><strong>العمر:</strong> {{ $knownServiceRequest->age ?? '-' }}</li>
                            <li><strong>الحالة الاجتماعية:</strong> {{ $knownServiceRequest->socialStatus?->title ?? '-' }}</li>
                            <li><strong>الديانة:</strong> {{ $knownServiceRequest->religion?->title ?? '-' }}</li>
                            <li><strong>المهارات:</strong> {{ $knownServiceRequest->skills ?? '-' }}</li>
                            <li><strong>الراتب المطلوب:</strong> {{ $knownServiceRequest->required_salary ?? '-' }}</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0"><i class="fas fa-address-card"></i> معلومات إضافية</h5></div>
                    <div class="card-body">
                        <ul class="mb-0">
                            <li><strong>هاتف العاملة:</strong> {{ $knownServiceRequest->worker_phone ?? '-' }}</li>
                            <li><strong>عنوان العاملة في بلدها:</strong> {{ $knownServiceRequest->worker_country_address ?? '-' }}</li>
                            <li><strong>اسم قريب في السعودية:</strong> {{ $knownServiceRequest->relative_in_saudi_name ?? '-' }}</li>
                            <li><strong>هاتف القريب:</strong> {{ $knownServiceRequest->relative_in_saudi_phone ?? '-' }}</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0"><i class="fas fa-paperclip"></i> المرفقات</h5></div>
                    <div class="card-body">
                        @php
                            $attachments = [
                                'worker_passport_copy' => 'صورة جواز العاملة',
                                'worker_personal_photo' => 'الصورة الشخصية للعاملة',
                                'client_identity_copy' => 'هوية العميل',
                            ];
                        @endphp

                        @foreach ($attachments as $field => $label)
                            <div class="mb-3">
                                <strong>{{ $label }}</strong><br>
                                @if ($knownServiceRequest->{$field})
                                    <div class="btn-group" role="group">
                                        <a href="{{ showFile($knownServiceRequest->{$field}) }}" target="_blank" class="btn btn-sm btn-primary">عرض</a>
                                        <a href="{{ showFile($knownServiceRequest->{$field}) }}" download class="btn btn-sm btn-success">تحميل</a>
                                    </div>
                                @else
                                    <span class="text-muted">غير متوفر</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
