@extends('admin.layout.indexs.index')
@section("style")
<link rel="stylesheet" href="{{asset(" admin")}}/assets/cssbundle/dataTables.min.css">
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
                                    <td class="meta-head">{{helperTrans('ingaz.address')}}</td>
                                    <td>
                                        @if(isset($contract->user->addresses[0]))
                                        <textarea
                                            class="form-control">{{@$contract->user->addresses[0]->address.@$contract->user->addresses[0]->title}}</textarea>
                                        @endif
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div style="clear:both"></div>
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
                                    {{@$contract->maid->passport_num}}
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
                                    العمر
                                </td>
                                <td>
                                    {{@$contract->maid->age}}
                                </td>
                            </tr>
                            <tr class="item-row">
                                <td>
                                    سنوات الخبرة
                                </td>
                                <td>
                                    {{@$contract->maid->experience_years}}
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


                    <div style="clear:both"></div>
                    <div class="footer-note mt-5">
                        <h5 style="margin-right: 35px">التوقيـــــــع</h5>
                        (&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 text-center text-md-end">
        {{-- <button type="button" class="btn btn-lg btn-primary" onclick="printDiv()">
            <i--}} {{-- class="fa fa-print me-2"></i>طباعة العقد--}}
                {{--
        </button>--}}
    </div>
</div>

@else
    @include('components.no_permissions')
@endif


@endsection
@section('js')

@endsection
