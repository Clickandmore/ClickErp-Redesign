@extends('admin.layout.indexs.index')
@section("style")
<link rel="stylesheet" href="{{asset(" admin")}}/assets/cssbundle/dataTables.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="{{asset("admin")}}/custom/status-badges.css">
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

    .btn-change-maid {
        background: linear-gradient(135deg, #ffc107, #e0a800);
        color: white;
    }

    /* Modal Styles */
    .modal-content {
        border-radius: 10px;
        border: none;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    }

    .modal-header {
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        border-bottom: 1px solid #dee2e6;
        border-radius: 10px 10px 0 0;
    }

    .modal-title {
        color: #495057;
        font-weight: 600;
    }

    .modal-title i {
        margin-left: 8px;
        color: #007bff;
    }

    .form-check {
        margin-bottom: 10px;
        padding: 8px;
        border-radius: 4px;
        transition: background-color 0.2s ease;
    }

    .form-check:hover {
        background-color: #f8f9fa;
    }

    .form-check-label {
        font-weight: 500;
        color: #495057;
        cursor: pointer;
        display: flex;
        align-items: center;
    }

    .form-check-label i {
        margin-left: 8px;
        font-size: 16px;
    }

    .form-check-input:checked + .form-check-label {
        font-weight: 600;
    }

    .alert-info {
        background-color: #d1ecf1;
        border-color: #bee5eb;
        color: #0c5460;
    }

    /* Filter Styles */
    .form-group .btn-outline-secondary {
        border-color: #6c757d;
        color: #6c757d;
        transition: all 0.3s ease;
    }

    .form-group .btn-outline-secondary:hover {
        background-color: #6c757d;
        color: white;
    }

    .form-control:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    .form-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 0.5rem;
    }

    .form-label i {
        margin-left: 0.5rem;
        color: #007bff;
    }

    /* Search Input Group Styles */
    .input-group .form-control {
        border-right: 0;
    }

    .input-group .btn {
        border-left: 0;
        background-color: #f8f9fa;
    }

    .input-group .btn:hover {
        background-color: #e9ecef;
    }

    .input-group:focus-within .form-control {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    .input-group:focus-within .btn {
        border-color: #007bff;
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
        font-weight: 600;
        text-shadow: 0 1px 2px rgba(0,0,0,0.1);
    }

    /* تحسين ألوان البادجات لتكون أكثر وضوحاً */
    .badge.bg-primary {
        background-color: #0d6efd !important;
        color: white !important;
    }

    .badge.bg-success {
        background-color: #198754 !important;
        color: white !important;
    }

    .badge.bg-warning {
        background-color: #ffc107 !important;
        color: #212529 !important;
    }

    .badge.bg-info {
        background-color: #0dcaf0 !important;
        color: white !important;
    }

    .badge.bg-danger {
        background-color: #dc3545 !important;
        color: white !important;
    }

    .badge.bg-secondary {
        background-color: #6c757d !important;
        color: white !important;
    }

    .badge.bg-dark {
        background-color: #212529 !important;
        color: white !important;
    }

    /* تحسين مظهر بادج حالة العقد */
    .status-badge {
        font-size: 0.9rem;
        padding: 0.5rem 1rem;
        font-weight: 600;
        border-radius: 25px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }

    .status-badge:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
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

    /* Search Results Highlighting */
    .form-control:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    /* Dropdown Enhancement */
    select.form-control option {
        padding: 8px 12px;
        border-bottom: 1px solid #f8f9fa;
    }

    select.form-control option:hover {
        background-color: #f8f9fa;
    }

    select.form-control option[disabled] {
        color: #6c757d;
        font-style: italic;
        background-color: #f8f9fa;
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
                        {{helperTrans('ingaz.contract date')}} : {{@$info->contract_date}}
                    @endif
                    @if(@$info->msaned_number)
                        {{helperTrans('ingaz.msaned number')}} : {{@$info->msaned_number}}
                    @endif
                    @if(@$info->tafwed_number)
                        {{helperTrans('ingaz.tafwed number')}} : {{@$info->tafwed_number}}
                    @endif
                    @if(@$info->visa_number)
                        {{helperTrans('ingaz.visa number')}} :      {{@$info->visa_number}}
                    @endif

                    {{helperTrans('ingaz.contract status')}} :  {{getEnumValue('contract_statuses', $contract->status)}}


                </textarea>
                        <div>
                            <img src="{{showFile($settings->logo_header)}}" alt="logo">
                            {{-- <img src="{{asset('a.png')}}" alt="logo">--}}
                        </div>
                    </div>
                    <div style="clear:both"></div>
<div class="row">


                    <!-- معلومات العميل المحسنة -->
                    <div class="customer col-md-6 mt-4">

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
                    {{-- <div style="clear:both"></div> --}}

                    <!-- معلومات العقد المحسنة -->
                    <div class="col-md-6 mt-4">
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
                                        <span class="badge bg-{{ getContractStatusColor($contract->status) }} status-badge">
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

                    <div class="col-md-6 mt-4">
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

            @if(@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_UPDATE))
            <button type="button" class="btn-icon btn-change-maid" onclick="openChangeMaidModal()" title="تغيير العاملة">
                <i class="fas fa-exchange-alt"></i>
            </button>
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

<!-- دايلوج تغيير العاملة -->
<div class="modal fade" id="changeMaidModal" tabindex="-1" aria-labelledby="changeMaidModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changeMaidModalLabel">
                    <i class="fas fa-exchange-alt"></i>
                    تغيير العاملة في العقد رقم #{{ $contract->id }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- معلومات العاملة الحالية -->
                <div class="alert alert-info">
                    <h6><i class="fas fa-user-tie"></i> العاملة الحالية:</h6>
                    <strong>{{ $contract->maid->name ?? 'غير محدد' }}</strong> -
                    {{ $contract->maid->passport_num ?? 'غير محدد' }}
                    ({{ $contract->maid->nationality->sub_title ?? 'غير محدد' }})
                </div>

                <form id="changeMaidForm">
                    @csrf
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group mb-3">
                                <label for="maid_search" class="form-label">
                                    <i class="fas fa-search"></i>
                                    البحث في العاملات
                                </label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="maid_search"
                                           placeholder="ابحث بالاسم أو رقم الجواز..."
                                           onkeyup="searchMaidsWithDelay(this.value)"
                                           onkeydown="handleSearchKeydown(event)">
                                    <button class="btn btn-outline-secondary" type="button" onclick="clearSearch()">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <small class="form-text text-muted">
                                    اكتب للبحث في العاملات المتاحات - اضغط Enter لفتح القائمة
                                </small>
                            </div>

                            <div class="form-group mb-3">
                                <label for="new_maid_id" class="form-label">
                                    <i class="fas fa-user-plus"></i>
                                    اختيار العاملة الجديدة
                                </label>
                                <select class="form-control" id="new_maid_id" name="new_maid_id" required>
                                    <option value="">اختر العاملة الجديدة...</option>
                                </select>
                                <small class="form-text text-muted">
                                    سيتم تحميل العاملات المتاحات...
                                </small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label class="form-label">
                                    <i class="fas fa-user-minus"></i>
                                    حالة العاملة السابقة
                                </label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="old_maid_status"
                                           id="status_available" value="available" required>
                                    <label class="form-check-label" for="status_available">
                                        <i class="fas fa-check text-success"></i>
                                        متاحة
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="old_maid_status"
                                           id="status_unavailable" value="backout" required>
                                    <label class="form-check-label" for="status_unavailable">
                                        <i class="fas fa-ban text-danger"></i>
                                        باك اوت
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label for="reason" class="form-label">
                            <i class="fas fa-comment"></i>
                            سبب التغيير (اختياري)
                        </label>
                        <textarea class="form-control" id="reason" name="reason" rows="3"
                                  placeholder="اكتب سبب تغيير العاملة..."></textarea>
                    </div>

                    {{-- <!-- زر اختبار تحميل العاملات -->
                    <div class="form-group mb-3">
                        <button type="button" class="btn btn-info btn-sm" onclick="testLoadMaids()">
                            <i class="fas fa-sync"></i>
                            اختبار تحميل العاملات
                        </button>
                    </div> --}}
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i>
                    إلغاء
                </button>
                <button type="button" class="btn btn-primary" onclick="submitChangeMaid()">
                    <i class="fas fa-save"></i>
                    حفظ التغيير
                </button>
            </div>
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

// فتح دايلوج تغيير العاملة
function openChangeMaidModal() {
    console.log('Opening change maid modal...');

    // فتح الدايلوج أولاً
    $('#changeMaidModal').modal('show');

    // تحميل العاملات المتاحات بعد فتح الدايلوج
    setTimeout(function() {
        loadAvailableMaids();
        loadFilterOptions();
    }, 300);
}

// تحميل خيارات الفلترة
function loadFilterOptions() {
    console.log('Loading filter options...');

    const url = '{{ route("RecruitmentContract.filterOptions") }}';

    fetch(url, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        },
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // تحميل الجنسيات
            const nationalitySelect = document.getElementById('nationality_filter');
            nationalitySelect.innerHTML = '<option value="">جميع الجنسيات</option>';

            if (data.nationalities) {
                data.nationalities.forEach(nationality => {
                    const option = document.createElement('option');
                    option.value = nationality.id;
                    option.textContent = nationality.title;
                    nationalitySelect.appendChild(option);
                });
            }

            // تحميل الأديان
            const religionSelect = document.getElementById('religion_filter');
            religionSelect.innerHTML = '<option value="">جميع الأديان</option>';

            if (data.religions) {
                data.religions.forEach(religion => {
                    const option = document.createElement('option');
                    option.value = religion.id;
                    option.textContent = religion.title;
                    religionSelect.appendChild(option);
                });
            }
        }
    })
    .catch(error => {
        console.error('Error loading filter options:', error);
    });
}

// تطبيق الفلاتر
function applyFilters() {
    const searchTerm = document.getElementById('maid_search').value;
    const nationalityId = document.getElementById('nationality_filter').value;
    const religionId = document.getElementById('religion_filter').value;

    console.log('Applying filters:', { searchTerm, nationalityId, religionId });

    // بناء URL مع الفلاتر
    const url = '{{ route("RecruitmentContract.availableMaids") }}';
    const params = new URLSearchParams();

    if (searchTerm) params.append('search', searchTerm);
    if (nationalityId) params.append('nationality_id', nationalityId);
    if (religionId) params.append('religion_id', religionId);

    const fullUrl = params.toString() ? `${url}?${params.toString()}` : url;
    console.log('Fetching from URL:', fullUrl);

    // الحصول على CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    fetch(fullUrl, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        },
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Filtered response data:', data);

        if (data.success) {
            const select = document.getElementById('new_maid_id');
            select.innerHTML = '<option value="">اختر العاملة الجديدة...</option>';

            if (data.maids && data.maids.length > 0) {
                data.maids.forEach(maid => {
                    const option = document.createElement('option');
                    option.value = maid.id;
                    option.textContent = `${maid.name} - ${maid.passport_num} (${maid.nationality})`;
                    // إضافة بيانات للبحث
                    option.setAttribute('data-name', maid.name);
                    option.setAttribute('data-passport', maid.passport_num);
                    option.setAttribute('data-nationality', maid.nationality);
                    select.appendChild(option);
                });
                console.log('Loaded ' + data.maids.length + ' maids');
            } else {
                select.innerHTML = '<option value="">لا توجد عاملات متاحات تطابق الفلترة</option>';
                console.log('No available maids found with current filters');
            }
        } else {
            console.error('Error loading filtered maids:', data.message);
            const select = document.getElementById('new_maid_id');
            select.innerHTML = '<option value="">خطأ في تحميل العاملات</option>';
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        const select = document.getElementById('new_maid_id');
        select.innerHTML = '<option value="">خطأ في الاتصال: ' + error.message + '</option>';
    });
}

// تحميل العاملات المتاحات
function loadAvailableMaids(searchTerm = '') {
    console.log('Loading available maids...', { searchTerm });

    const url = '{{ route("RecruitmentContract.availableMaids") }}';
    const fullUrl = searchTerm ? `${url}?search=${encodeURIComponent(searchTerm)}` : url;
    console.log('Fetching from URL:', fullUrl);

    // الحصول على CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    console.log('CSRF Token:', csrfToken);

    fetch(fullUrl, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        },
        credentials: 'same-origin'
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);

        if (data.success) {
            const select = document.getElementById('new_maid_id');
            select.innerHTML = '<option value="">اختر العاملة الجديدة...</option>';

            if (data.maids && data.maids.length > 0) {
                data.maids.forEach(maid => {
                    const option = document.createElement('option');
                    option.value = maid.id;
                    option.textContent = `${maid.name} - ${maid.passport_num} (${maid.nationality})`;
                    // إضافة بيانات للبحث
                    option.setAttribute('data-name', maid.name);
                    option.setAttribute('data-passport', maid.passport_num);
                    option.setAttribute('data-nationality', maid.nationality);
                    select.appendChild(option);
                });
                console.log('Loaded ' + data.maids.length + ' maids');
            } else {
                select.innerHTML = '<option value="">لا توجد عاملات متاحات</option>';
                console.log('No available maids found');
            }
        } else {
            console.error('Error loading maids:', data.message);
            const select = document.getElementById('new_maid_id');
            select.innerHTML = '<option value="">خطأ في تحميل العاملات</option>';
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        const select = document.getElementById('new_maid_id');
        select.innerHTML = '<option value="">خطأ في الاتصال: ' + error.message + '</option>';
    });
}

// البحث مع تأخير لتجنب طلبات كثيرة
function searchMaidsWithDelay(searchTerm) {
    clearTimeout(window.searchTimeout);
    window.searchTimeout = setTimeout(() => {
        filterMaidOptions(searchTerm);
    }, 200); // تأخير 200 مللي ثانية
}

// إرسال نموذج تغيير العاملة
function submitChangeMaid() {
    const form = document.getElementById('changeMaidForm');
    const formData = new FormData(form);

    // التحقق من البيانات
    if (!formData.get('new_maid_id')) {
        alert('يرجى اختيار العاملة الجديدة');
        return;
    }

    if (!formData.get('old_maid_status')) {
        alert('يرجى اختيار حالة العاملة السابقة');
        return;
    }

    // تأكيد العملية
    if (!confirm('هل أنت متأكد من تغيير العاملة في هذا العقد؟')) {
        return;
    }

    console.log('Submitting change maid form:', {
        new_maid_id: formData.get('new_maid_id'),
        old_maid_status: formData.get('old_maid_status'),
        reason: formData.get('reason')
    });

    // إرسال البيانات
    fetch('{{ route("RecruitmentContract.changeMaid", $contract->id) }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        },
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);

        if (data.success) {
            alert('تم تغيير العاملة بنجاح');
            console.log('Change maid details:', data.data);
            location.reload(); // إعادة تحميل الصفحة لتحديث البيانات
        } else {
            alert('خطأ: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('حدث خطأ أثناء تغيير العاملة');
    });
}

function testLoadMaids() {
    alert('Testing loadAvailableMaids function...');
    loadAvailableMaids();
}

// مسح الفلاتر
function clearFilters() {
    document.getElementById('maid_search').value = '';
    document.getElementById('nationality_filter').value = '';
    document.getElementById('religion_filter').value = '';
    applyFilters(); // إعادة تطبيق الفلاتر لعرض جميع العاملات
}

// فلترة خيارات العاملات في القائمة المنسدلة
function filterMaidOptions(searchTerm) {
    console.log('Filtering maids with term:', searchTerm);

    const select = document.getElementById('new_maid_id');
    const options = Array.from(select.options);

    // إزالة رسالة "لا توجد نتائج" السابقة
    const noResultsOption = select.querySelector('option[disabled]');
    if (noResultsOption) {
        noResultsOption.remove();
    }

    // إذا كان البحث فارغاً، إظهار جميع الخيارات
    if (!searchTerm.trim()) {
        options.forEach(option => {
            option.style.display = '';
        });
        return;
    }

    // إظهار/إخفاء الخيارات بناءً على البحث
    let visibleCount = 0;
    const search = searchTerm.toLowerCase().trim();

    options.forEach(option => {
        if (option.value === '') {
            // إبقاء الخيار الافتراضي دائماً
            option.style.display = '';
        } else {
            const name = option.getAttribute('data-name')?.toLowerCase() || '';
            const passport = option.getAttribute('data-passport')?.toLowerCase() || '';
            const nationality = option.getAttribute('data-nationality')?.toLowerCase() || '';
            const fullText = option.textContent.toLowerCase();

            // البحث في جميع الحقول
            const matches = name.includes(search) ||
                           passport.includes(search) ||
                           nationality.includes(search) ||
                           fullText.includes(search);

            if (matches) {
                option.style.display = '';
                visibleCount++;
            } else {
                option.style.display = 'none';
            }
        }
    });

    // إذا لم يتم العثور على نتائج، إظهار رسالة
    if (visibleCount === 0 && searchTerm.trim()) {
        const noResultsOption = document.createElement('option');
        noResultsOption.value = '';
        noResultsOption.textContent = 'لا توجد نتائج تطابق البحث';
        noResultsOption.disabled = true;
        select.appendChild(noResultsOption);
    }

    console.log('Visible options:', visibleCount);
}

// معالجة المفاتيح في حقل البحث
function handleSearchKeydown(event) {
    if (event.key === 'Enter') {
        event.preventDefault();
        const select = document.getElementById('new_maid_id');
        select.focus();
        select.click(); // فتح القائمة المنسدلة
    }
}

// مسح البحث
function clearSearch() {
    document.getElementById('maid_search').value = '';
    filterMaidOptions(''); // إظهار جميع الخيارات
}
</script>
@endsection
