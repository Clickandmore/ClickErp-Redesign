@extends('admin.layout.indexs.index')
@section("style")
<link rel="stylesheet" href="{{asset(" admin")}}/assets/cssbundle/dataTables.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
@endsection
@section('page-title')
{{helperTrans("ingaz.contract details")}}
@endsection
<style>
    .print_invoice textarea:focus,
    .print_invoice textarea:hover {
        background: white !important;
        border: 0px !important;
        color: black;
    }

    .print_invoice textarea {
        border: 0px !important;
    }

    .address {
        line-height: 1.6 !important;
        width: 300px !important;
        font-size: 18px !important;
        margin-top: 20px;
    }

    .print_invoice .meta {
        width: 500px !important;
    }

    .print_invoice .meta td.meta-head {
        text-align: right !important;
    }

    .print_invoice .meta td textarea {
        height: auto !important;
    }

    .action-buttons {
        display: flex;
        gap: 10px;
        justify-content: center;
        margin-top: 20px;
    }

    .btn-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
    }

    .btn-icon:hover {
        transform: scale(1.1);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }

    .btn-print {
        background: linear-gradient(135deg, #007bff, #0056b3);
        color: white;
    }

    .btn-edit {
        background: linear-gradient(135deg, #28a745, #1e7e34);
        color: white;
    }

    .btn-back {
        background: linear-gradient(135deg, #6c757d, #545b62);
        color: white;
    }

    .btn-delete {
        background: linear-gradient(135deg, #dc3545, #c82333);
        color: white;
    }

    .info-card {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        border-left: 4px solid #007bff;
    }

    .info-title {
        font-weight: bold;
        color: #495057;
        margin-bottom: 10px;
        font-size: 16px;
    }

    .info-value {
        color: #212529;
        font-size: 14px;
    }

    .print_invoice .meta {
        margin-top: 1rem;
    }

    .print_invoice .meta > div {
        margin-bottom: 0.5rem;
    }

    .print_invoice .meta > div > span {
        font-weight: 600;
        display: inline-block;
        width: 100px;
    }

    .card {
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }

    .card-header {
        border-radius: 10px 10px 0 0 !important;
        border-bottom: none;
    }

    .card-body {
        padding: 1.5rem;
    }

    .form-label {
        color: #495057;
        font-size: 0.9rem;
        margin-bottom: 0.5rem;
    }

    .badge {
        font-size: 0.8rem;
        padding: 0.5rem 0.75rem;
    }

    .alert {
        border-radius: 8px;
        border: none;
    }

    .text-primary {
        color: #007bff !important;
    }

    .text-success {
        color: #28a745 !important;
    }

    .text-danger {
        color: #dc3545 !important;
    }

    .bg-info {
        background-color: #17a2b8 !important;
    }

    .bg-warning {
        background-color: #ffc107 !important;
    }

    .bg-secondary {
        background-color: #6c757d !important;
    }
</style>
@section('content')
@if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_VIEW))

<div class="row g-3">
    <div class="col-12">
        <div class="card print_invoice" id="printDiv">
            <div class="card-header border-bottom fs-4">
                <h5 class="card-title mb-0">{{@$contract->desc}}</h5>
            </div>
            <div class="card-body">
                <div class="card p-3">
                    <div class="border-bottom pb-2">
                        <textarea class="form-control address">
                    @if(@$info->contract_date)
                        {{helperTrans('ingaz.contract date')}} {{@$info->contract_date}}
                    @endif
                    @if(@$info->msaned_number)
                        {{helperTrans('ingaz.msaned number')}} {{@$info->msaned_number}}
                    @endif
                    @if(@$info->tafwed_number)
                        {{helperTrans('ingaz.tafwed number')}} {{@$info->tafwed_number}}
                    @endif
                    @if(@$info->visa_number)
                        {{helperTrans('ingaz.visa number')}} {{@$info->visa_number}}
                    @endif
                </textarea>
                        <div>
                            <img src="{{showFile($settings->logo_header)}}" alt="logo">
                            {{-- <img src="{{asset('a.png')}}" alt="logo">--}}
                        </div>
                    </div>
                    <div style="clear:both"></div>

                    <!-- معلومات العميل المحسنة -->
                    <div class="customer mt-4">
                        <textarea class="form-control customer-title">
                                        {{getEnumValue('contract_statuses', $contract->status)}}
                                    </textarea>
                        <table class="meta">
                            <tbody>
                                <tr>
                                    <td class="meta-head">{{helperTrans('ingaz.client')}}</td>
                                    <td><textarea class="form-control">{{@$contract->user->name}}</textarea></td>
                                </tr>
                                <tr>
                                    <td class="meta-head">{{helperTrans('ingaz.phone')}}</td>
                                    <td><textarea
                                            class="form-control">{{@$contract->user->phone_code.@$contract->user->phone}}</textarea>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="meta-head">تاريخ الميلاد</td>
                                    <td><textarea class="form-control">{{@$contract->user->birthdate ? \Carbon\Carbon::parse($contract->user->birthdate)->format('Y-m-d') : 'غير محدد'}}</textarea></td>
                                </tr>
                                <tr>
                                    <td class="meta-head">{{helperTrans('ingaz.address')}}</td>
                                    <td>
                                        @if(isset($contract->user->addresses[0]))
                                        <textarea
                                            class="form-control">{{@$contract->user->addresses[0]->address.@$contract->user->addresses[0]->title}}</textarea>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="meta-head">الجنسية</td>
                                    <td><textarea class="form-control">{{@$contract->user->nationality->title ?: 'غير محدد'}}</textarea></td>
                                </tr>
                                <tr>
                                    <td class="meta-head">رقم الهوية</td>
                                    <td><textarea class="form-control">{{@$contract->user->national_num ?: 'غير محدد'}}</textarea></td>
                                </tr>
                                <tr>
                                    <td class="meta-head">البريد الإلكتروني</td>
                                    <td><textarea class="form-control">{{@$contract->user->email ?: 'غير محدد'}}</textarea></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div style="clear:both"></div>

                    <!-- معلومات العقد المحسنة -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>معلومات العقد الأساسية</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">رقم العقد:</label>
                                        <p class="text-primary">{{ $contract->id }}</p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">تاريخ العقد:</label>
                                        <p class="text-success">{{ $info->contract_date ? \Carbon\Carbon::parse($info->contract_date)->format('Y-m-d') : 'غير محدد' }}</p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">رقم مساند:</label>
                                        <p>{{ $info->msaned_number ?? 'غير محدد' }}</p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">رقم تفويض:</label>
                                        <p>{{ $info->tafwed_number ?? 'غير محدد' }}</p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">رقم التأشيرة:</label>
                                        <p>{{ $info->visa_number ?? 'غير محدد' }}</p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">تاريخ التأشيرة الميلادي:</label>
                                        <p>{{ $info->visa_milad_date ? \Carbon\Carbon::parse($info->visa_milad_date)->format('Y-m-d') : 'غير محدد' }}</p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">تاريخ التأشيرة الهجري:</label>
                                        <p>{{ $info->visa_hijry_date ? \Carbon\Carbon::parse($info->visa_hijry_date)->format('Y-m-d') : 'غير محدد' }}</p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">مدة العقد:</label>
                                        <p>{{ $info->contract_duration ?? 'غير محدد' }} سنة</p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">تاريخ انتهاء العقد:</label>
                                        @php
                                            $contractDate = $info->contract_date ? \Carbon\Carbon::parse($info->contract_date) : null;
                                            $expiryDate = $contractDate ? $contractDate->addYears($info->contract_duration ?? 2) : null;
                                        @endphp
                                        <p class="text-danger">{{ $expiryDate ? $expiryDate->format('Y-m-d') : 'غير محدد' }}</p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">حالة العقد:</label>
                                        <span class="badge bg-{{ getContractStatusColor($contract->status) }}">
                                            {{ getContractStatus($contract->status) }}
                                        </span>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">تاريخ إنشاء العقد:</label>
                                        <p>{{ $contract->created_at ? \Carbon\Carbon::parse($contract->created_at)->format('Y-m-d H:i') : 'غير محدد' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="mb-0"><i class="fas fa-user-tie me-2"></i>معلومات المسوق</h5>
                            </div>
                            <div class="card-body">
                                @if($marketer)
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold">اسم المسوق:</label>
                                            <p class="text-primary">{{ $marketer->name }}</p>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold">رقم الجوال:</label>
                                            <p>{{ $marketer->phone ?? 'غير محدد' }}</p>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold">البريد الإلكتروني:</label>
                                            <p>{{ $marketer->email ?? 'غير محدد' }}</p>
                                        </div>
                                       
                                    </div>
                                @else
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        لا توجد معلومات عن المسوق لهذا العقد
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <table class="items">
                        <tbody>
                            <tr>
                                <th>تقرير الحالات</th>
                                <th style="width: 140px;">الوقت</th>
                            </tr>
                            @foreach($contract->contract_status_logs as $logs)
                            <tr class="item-row">
                                <td>
                                    {{getEnumValue('contract_statuses',$logs->status)}}
                                </td>
                                <td>{{$logs->date}}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <table class="items">
                        <tbody>
                            <tr>
                                <th>العامل/ة</th>
                                <th style="width: 140px;">البيانات</th>
                            </tr>
                            <tr class="item-row">
                                <td>
                                    رقم الجواز
                                </td>
                                <td>
                                    {{@$contract->maid->passport_num ?: 'غير محدد'}}
                                </td>
                            </tr>
                            <tr class="item-row">
                                <td>
                                    {{helperTrans('ingaz.price')}}
                                </td>
                                <td>
                                    {{@$contract->maid->price}}
                                </td>
                            </tr>
                            <tr class="item-row">
                                <td>
                                    {{helperTrans('ingaz.salary')}}
                                </td>
                                <td>
                                    {{@$contract->maid->salary}}
                                </td>
                            </tr>
                            <tr class="item-row">
                                <td>
                                    الجنسية
                                </td>
                                <td>
                                    {{@$contract->maid->nationality->title ?: 'غير محدد'}}
                                </td>
                            </tr>
                            <tr class="item-row">
                                <td>
                                    الدين
                                </td>
                                <td>
                                    {{@$contract->maid->religion->title ?: 'غير محدد'}}
                                </td>
                            </tr>
                            <tr class="item-row">
                                <td>
                                    المهنة
                                </td>
                                <td>
                                    {{@$contract->maid->jobTitles && $contract->maid->jobTitles->first() ? $contract->maid->jobTitles->first()->title : 'غير محدد'}}
                                </td>
                            </tr>
                            <tr class="item-row">
                                <td>
                                    سنوات الخبرة
                                </td>
                                <td>
                                    {{@$contract->maid->experience_years ?: 'غير محدد'}}
                                </td>
                            </tr>
                            <tr class="item-row">
                                <td>
                                    العمر
                                </td>
                                <td>
                                    {{@$contract->maid->age ?: 'غير محدد'}}
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <table class="items">
                        <tbody>
                            <tr>
                                <th colspan="2">بيانات العقد</th>
                            </tr>
                            <tr class="item-row">
                                <td>
                                    مدة العقد
                                </td>
                                <td>
                                    {{@$info->contract_duration}}
                                </td>
                            </tr>
                            <tr class="item-row">
                                <td>
                                    رقم التأشيرة
                                </td>
                                <td>
                                    {{@$info->visa_number}}
                                </td>
                            </tr>
                            <tr class="item-row">
                                <td>
                                    تاريخ التأشيرة الهجري
                                </td>
                                <td>
                                    {{@$info->visa_hijry_date}}
                                </td>
                            </tr>
                            <tr class="item-row">
                                <td>
                                    تاريخ التأشيرة الميلادي
                                </td>
                                <td>
                                    {{@$info->visa_milad_date}}
                                </td>
                            </tr>
                            <tr class="item-row">
                                <td>
                                    عدد ساعات العمل
                                </td>
                                <td>
                                    {{@$info->working_hours_num}}
                                </td>
                            </tr>
                            <tr class="item-row">
                                <td>
                                    مبلغ نقل الكفالة
                                </td>
                                <td>
                                    {{@$info->sponsorship_price}}
                                </td>
                            </tr>
                            <tr class="item-row">
                                <td>
                                    الراتب بعد الكفالة
                                </td>
                                <td>
                                    {{@$info->salary_after_sponsorship}}
                                </td>
                            </tr>

                            <tr class="item-row">
                                <td>
                                    التكلفة الشهرية
                                </td>
                                <td>
                                    {{@$info->monthly_cost}}
                                </td>
                            </tr>
                            <tr class="item-row">
                                <td>
                                    قيمة العقد
                                </td>
                                <td>
                                    {{@$info->contract_value}}
                                </td>
                            </tr>

                            <tr class="item-row">
                                <td>
                                    الانجاز من
                                </td>
                                <td>
                                    {{@$info->ingaz_from}}
                                </td>
                            </tr>

                            <tr class="item-row">
                                <td>
                                    الانجاز الي
                                </td>
                                <td>
                                    {{@$info->ingaz_to}}
                                </td>
                            </tr>

                            <tr class="item-row">
                                <td>
                                    الكفيل السابق
                                </td>
                                <td>
                                    {{ @$info->prevSponsorship->title }}
                                </td>
                            </tr>

                            <tr class="item-row">
                                <td>
                                    تاريخ ارجاع العامل/ة
                                </td>
                                <td>
                                    {{ @$info->maid_date_back }}
                                </td>
                            </tr>

                            <tr class="item-row">
                                <td>
                                    الحالة النهائية للراتب
                                </td>
                                <td>
                                    {{ @$info->salary_final_status }}
                                </td>
                            </tr>

                            <tr class="item-row">
                                <td>
                                    المدة منذ دخول المملكة
                                </td>
                                <td>
                                    {{ @$info->duration_from_enter }}
                                </td>
                            </tr>

                            <tr class="item-row">
                                <td>
                                    اسباب ارجاع العاملة
                                </td>
                                <td>
                                    {{ @$info->back_reasons }}
                                </td>
                            </tr>

                            <tr class="item-row">
                                <td>
                                    مقر الايواء
                                </td>
                                <td>
                                    {{ @$info->shelter->title }}
                                </td>
                            </tr>


                        </tbody>
                    </table>

                    @if($contract->order)
                    <div class="col-md-12 mt-4">
                        <div class="card">
                            <div class="card-header bg-secondary text-white">
                                <h5 class="mb-0"><i class="fas fa-list-check me-2"></i>معلومات الطلب المسبق</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label fw-bold">رقم الطلب:</label>
                                        <p class="text-primary">{{ $contract->order->order_code }}</p>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label fw-bold">تاريخ الطلب:</label>
                                        <p>{{ $contract->order->created_at ? \Carbon\Carbon::parse($contract->order->created_at)->format('Y-m-d H:i') : 'غير محدد' }}</p>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label fw-bold">حالة الطلب:</label>
                                        <span class="badge bg-{{ $contract->order->status == 'completed' ? 'success' : 'warning' }}">
                                            {{ $contract->order->status == 'completed' ? 'مكتمل' : 'قيد المعالجة' }}
                                        </span>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label fw-bold">طريقة الدفع:</label>
                                        <p>{{ $contract->order->payment_method ?? 'غير محدد' }}</p>
                                    </div>
                                    @if($contract->order->details && $contract->order->details->package)
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label fw-bold">الحزمة المختارة:</label>
                                        <p class="text-primary">{{ $contract->order->details->package->title ?? 'غير محدد' }}</p>
                                    </div>
                                    @endif
                                    @if($contract->order->details && $contract->order->details->address)
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">عنوان التوصيل:</label>
                                        <p>{{ $contract->order->details->address->address ?? 'غير محدد' }}</p>
                                    </div>
                                    @endif
                                    @if($contract->order->details && $contract->order->details->package)
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">سعر الحزمة:</label>
                                        <p class="text-success">{{ $contract->order->details->package->price ? number_format($contract->order->details->package->price, 2) . ' ريال' : 'غير محدد' }}</p>
                                    </div>
                                    @endif
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">نوع العمل:</label>
                                        <p>{{ $contract->order->jobType->title ?? 'غير محدد' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <div style="clear:both"></div>
                    <div class="footer-note mt-5">
                        <h5 style="margin-right: 35px">التوقيـــــــع</h5>
                        (&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- أزرار الإجراءات المحسنة -->
    <div class="col-12">
        <div class="action-buttons">
            <button type="button" class="btn-icon btn-print" onclick="printDiv()" title="طباعة العقد">
                <i class="fas fa-print"></i>
            </button>

            @if(@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_EDIT))
            <a href="{{ route('RecruitmentContract.edit', $contract->id) }}" class="btn-icon btn-edit" title="تعديل العقد">
                <i class="fas fa-edit"></i>
            </a>
            @endif

            <a href="{{ route('RecruitmentContract.index') }}" class="btn-icon btn-back" title="العودة للقائمة">
                <i class="fas fa-arrow-right"></i>
            </a>

            @if(@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_DELETE))
            <button type="button" class="btn-icon btn-delete" onclick="deleteContract({{ $contract->id }})" title="حذف العقد">
                <i class="fas fa-trash"></i>
            </button>
            @endif
        </div>
    </div>
</div>

@else
    @include('components.no_permissions')
@endif


@endsection
@section('js')
<script>
function printDiv() {
    var printContents = document.getElementById('printDiv').innerHTML;
    var originalContents = document.body.innerHTML;
    document.body.innerHTML = printContents;
    window.print();
    document.body.innerHTML = originalContents;
}

function deleteContract(contractId) {
    if (confirm('هل أنت متأكد من حذف هذا العقد؟')) {
        fetch(`/admin/RecruitmentContract/${contractId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('تم حذف العقد بنجاح');
                window.location.href = '{{ route("RecruitmentContract.index") }}';
            } else {
                alert('حدث خطأ أثناء حذف العقد');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('حدث خطأ أثناء حذف العقد');
        });
    }
}
</script>
@endsection
