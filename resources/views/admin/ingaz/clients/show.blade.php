@extends("admin.layout.indexs.index")

@section('page-title')
    {{ __("ingaz.details") }}
@endsection

@section('content')
    <div class="container my-5">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h4>بيانات العميل</h4>
            </div>
            <div class="card-body">

                <!-- بيانات العميل الأساسية -->
                <div class="row mb-3">
                    <div class="col-md-4 font-weight-bold">الاسم:</div>
                    <div class="col-md-8">{{ $client->name ?? '-' }}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4 font-weight-bold">البريد الإلكتروني:</div>
                    <div class="col-md-8">{{ $client->email ?? '-' }}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4 font-weight-bold">رقم الهاتف:</div>
                    <div class="col-md-8">{{ $client->phone ?? '-' }}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4 font-weight-bold">العنوان:</div>
                    <div class="col-md-8">{{ $client->address ?? '-' }}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4 font-weight-bold">تاريخ الميلاد:</div>
                    <div class="col-md-8">{{ $client->birthdate ?? '-' }}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4 font-weight-bold">الرقم القومي:</div>
                    <div class="col-md-8">{{ $client->national_num ?? '-' }}</div>
                </div>

                <!-- العناوين المتعددة -->
                <div class="mb-3">
                    <div class="font-weight-bold mb-2">العناوين:</div>
                    @forelse($client->addresses as $address)
                        <div class="card mb-2 p-2 shadow-sm">
                            <div class="row">
                                <div class="col-md-4 font-weight-bold">البلد:</div>
                                <div class="col-md-8">{{ (app()->getLocale() == 'ar' ? $address->country->name_ar ?? '-' : $address->country->name_en ?? '-') ?? '-' }}</div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 font-weight-bold">العنوان:</div>
                                <div class="col-md-8">{{ $address->address ?? '-' }}</div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 font-weight-bold">العنوان المختصر / العنوان الفرعي:</div>
                                <div class="col-md-8">{{ $address->title ?? '-' }}</div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 font-weight-bold">الهاتف:</div>
                                <div class="col-md-8">{{ $address->phone ?? '-' }}</div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 font-weight-bold">اسم المستلم:</div>
                                <div class="col-md-8">{{ $address->name ?? '-' }}</div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 font-weight-bold">ملاحظات:</div>
                                <div class="col-md-8">{{ $address->notes ?? '-' }}</div>
                            </div>
                        </div>
                    @empty
                        <p>- لا توجد عناوين مسجلة -</p>
                    @endforelse
                </div>

                <!-- الجنسية -->
                <div class="row mb-3">
                    <div class="col-md-4 font-weight-bold">الجنسية:</div>
                    <div class="col-md-8">{{ $client->nationality->title ?? '-' }}</div>
                </div>

            </div>
        </div>
    </div>
@endsection
