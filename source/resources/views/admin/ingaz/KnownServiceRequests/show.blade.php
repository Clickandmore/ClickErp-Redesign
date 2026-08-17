@extends('admin.layout.indexs.index')

@section('page-title')
    تفاصيل طلب الخدمة المعروفة
@endsection

@section('content')
    <div class="container-fluid">
        <div class="mb-3 mt-3"><a href="{{ route('known-service-requests.index') }}" class="btn btn-secondary"><i
                    class="fa fa-arrow-left"></i> {{ __('ingaz.back_to_list') }}</a></div>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">طلب خدمة معروفة #{{ $knownServiceRequest->id }}</h3>
            </div>
            <div class="card-body">
                <h5><i class="fas fa-user"></i> بيانات المستخدم</h5>
                <ul>
                    <li><strong>الاسم:</strong> {{ $knownServiceRequest->user?->name ?? '-' }}</li>
                    <li><strong>الهاتف:</strong> {{ $knownServiceRequest->user?->phone ?? '-' }}</li>
                    <li><strong>البريد:</strong> {{ $knownServiceRequest->user?->email ?? '-' }}</li>
                </ul>
                <h5 class="mt-4"><i class="fas fa-address-card"></i> بيانات العميل</h5>
                <ul>
                    <li><strong>اسم العميل:</strong> {{ $knownServiceRequest->client_name }}</li>
                    <li><strong>الهاتف:</strong> {{ $knownServiceRequest->client_phone }}</li>
                    <li><strong>البريد الإلكتروني:</strong> {{ $knownServiceRequest->client_email }}</li>
                    <li><strong>العنوان:</strong> {{ $knownServiceRequest->client_address }}</li>
                    <li><strong>المدينة:</strong> {{ $knownServiceRequest->city }}</li>
                    <li><strong>الحالة:</strong>
                        @if ($knownServiceRequest->status === \App\Enums\ingaz\RequestsStatusEnum::Approved->value)
                            <span class="badge bg-success">{{ __('ingaz.approved') }}</span>
                        @elseif($knownServiceRequest->status === \App\Enums\ingaz\RequestsStatusEnum::Rejected->value)
                        <span class="badge bg-danger">{{ __('ingaz.rejected') }}</span>@else<span
                                class="badge bg-warning">{{ __('ingaz.pending') }}</span>
                        @endif
                    </li>
                </ul>
                <h5 class="mt-4"><i class="fas fa-female"></i> بيانات العاملة</h5>
                <ul>
                    <li><strong>اسم العاملة:</strong> {{ $knownServiceRequest->worker_name }}</li>
                    <li><strong>الجنسية:</strong> {{ $knownServiceRequest->nationality?->title ?? '-' }}</li>
                    <li><strong>رقم الجواز:</strong> {{ $knownServiceRequest->passport_number }}</li>
                    <li><strong>تاريخ انتهاء الجواز:</strong> {{ $knownServiceRequest->passport_expiration_date ?? '-' }}
                    </li>
                    <li><strong>العمر:</strong> {{ $knownServiceRequest->age ?? '-' }}</li>
                    <li><strong>الحالة الاجتماعية:</strong> {{ $knownServiceRequest->socialStatus?->title ?? '-' }}</li>
                    <li><strong>الديانة:</strong> {{ $knownServiceRequest->religion?->title ?? '-' }}</li>
                    <li><strong>المهارات:</strong> {{ $knownServiceRequest->skills ?? '-' }}</li>
                    <li><strong>الراتب المطلوب:</strong> {{ $knownServiceRequest->required_salary ?? '-' }}</li>
                </ul>
                <h5 class="mt-4"><i class="fas fa-phone"></i> معلومات التواصل الإضافية</h5>
                <ul>
                    <li><strong>هاتف العاملة:</strong> {{ $knownServiceRequest->worker_phone ?? '-' }}</li>
                    <li><strong>عنوان العاملة في بلدها:</strong> {{ $knownServiceRequest->worker_country_address ?? '-' }}
                    </li>
                    <li><strong>اسم قريبها في السعودية:</strong> {{ $knownServiceRequest->relative_in_saudi_name ?? '-' }}
                    </li>
                    <li><strong>هاتف القريب:</strong> {{ $knownServiceRequest->relative_in_saudi_phone ?? '-' }}</li>
                </ul>
                <h5 class="mt-4"><i class="fas fa-paperclip"></i> المرفقات</h5>
                <div class="row">
                    @php $attachments = ['worker_passport_copy' => 'صورة جواز العاملة', 'worker_personal_photo' => 'الصورة الشخصية للعاملة', 'client_identity_copy' => 'هوية العميل']; @endphp
                    @foreach ($attachments as $key => $label)
                        <div class="col-md-4 mb-3"><strong>{{ $label }}</strong><br>
                            @if ($knownServiceRequest->$key)
                                <div class="btn-group" role="group"><a href="{{ showFile($knownServiceRequest->$key) }}"
                                        target="_blank" class="btn btn-sm btn-primary"><i class="bi bi-eye"></i>
                                        {{ __('ingaz.view') }}</a><a href="{{ showFile($knownServiceRequest->$key) }}"
                                        download class="btn btn-sm btn-success"><i class="bi bi-download"></i>
                                    {{ __('ingaz.download') }}</a></div>@else<span
                                    class="text-muted">{{ __('ingaz.not_avalible') }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection
