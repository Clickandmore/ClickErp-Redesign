@extends('click.layout.indexs.index')
@php
@endphp
@section('page-title')
    {{ __('finance::finance.home') }}
@endsection
@section('content')
    <style>
        /*@media print {*/
        /*  */
        /*    body * {*/
        /*        visibility: hidden;*/
        /*    }*/
        /*    #printableArea, #printableArea * {*/
        /*        visibility: visible;*/
        /*    }*/
        /*    #printableArea {*/
        /*        position: absolute;*/
        /*        left: 0;*/
        /*        top: 0;*/
        /*        width: 100%;*/
        /*    }*/
        /*}*/
        html[data-theme="dark"] {
            --bg: #1d1f20;
            --btnBg: linear-gradient(135deg, #1e824c, #0a6b89);
            --text: #dadada;
            --btnHoverBg: linear-gradient(135deg, #1a6f42, #095a74);
            --Hover: 0 6px 20px rgba(0, 0, 0, 0.6);

            --td-bg-1: #2e3b2e;
            /* غامق لأخضر فاتح */
            --td-bg-2: #4e3b28;
            /* غامق لكريمي */
            --td-bg-3: #4a2e2e;
            /* غامق لوردي */
            --td-bg-4: #3d2b40;
            /* غامق لبنفسجي */
            --td-bg-5: #3b3532;
            /* غامق لرمادي وردي */
            --td-bg-6: #3b3532;
            /* نفس 5 */
            --td-bg-7: #3a3f2e;
            /* غامق لأصفر فاتح */
            --td-bg-8: #3d2b40;
            /* نفس 4 */
            --td-bg-9: #3b3532;
            /* نفس 5 */
            --td-bg-10: #322c45;
            /* غامق لبنفسجي مزرق */
            --td-bg-11: #4e3b28;
            /* نفس 2 */
            --td-bg-12: #442c38;
            /* غامق لوردي سكري */
            --td-bg-13: #2e3b2e;
            /* نفس 1 */
            --td-bg-14: #4e3b28;
            /* نفس 2 */
        }

        html[data-theme="light"] {
            --w: #ffff;
            --border: #eaeaea;
            --text: #191919;
            --subText: #40485b;
            --ts: .3s ease-in-out;
        }


        .gridSystem {
            display: grid;
            gap: 16px;

            &.col_2 {
                grid-template-columns: repeat(2, 1fr);

                @media(max-width:991px) {
                    grid-template-columns: 1fr;
                }
            }
        }

        ul li {
            list-style-type: none;
        }

        ul {
            margin-block: 0 !important;
            margin-inline-start: 0;
            padding-left: 0;

        }

        .border-b {
            border-bottom: 1px solid var(--border);
        }

        .AE_btn,
        .btn {
            background: transparent;
            border: none;
            outline: none;
            color: var(--text);

            &.AE_btnDown {
                font-size: 1.02rem;

                &:hover,
                &:focus,
                &.show {
                    background: transparent;
                    color: var(--text);
                    outline: none;
                    border: none;
                    box-shadow: none
                }
            }
        }

        .box {
            background: var(--w);
            border: 1px solid var(--border);
            padding: 1rem 1rem 2rem;

            h4 {
                color: var(--text);
                font-size: 1.03rem;
                font-weight: 600;
                padding-bottom: 6px;
                margin-block: 0 !important;
                margin-bottom: 0;

                i {
                    font-size: 1rem;
                }

            }

            i {
                color: var(--text);
            }
        }

        /* body{
                                overflow-x: hidden !important;
                            } */
        .chartGroup {

            perspective: 1000px;

            .chartFlip {
                width: 100%;
                height: 100%;
                position: relative;
                transform-style: preserve-3d;
                transition: transform 0.4s ease-in-out;

                &.flip {
                    transform: rotateY(-180deg);
                }

                .front,
                .back {
                    position: absolute;
                    width: 100%;
                    height: 100%;
                    -webkit-backface-visibility: hidden;
                    backface-visibility: hidden;


                }

                .back {
                    transform: rotateY(-180deg);
                }
            }

        }

        .charts {
            &.cricleChart {
                display: flex;
                place-items: center;
                flex-direction: column;

                .chartHead {
                    width: 100%;
                    /* padding-block-end: 1rem; */
                }
            }

            .chart {
                width: 100%;
                height: 100%;

                &.smallSize {
                    width: 350px;
                    height: 350px;
                }
            }
        }

        .fastLinks {
            .list {
                display: grid;
                grid-template-columns: 1fr 200px;

                /* gap: 16px; */
                a {
                    color: var(--subText);
                    transition: var(--ts);

                    i {
                        color: var(--subText);
                        transition: var(--ts);
                    }

                    &:hover {
                        color: var(--w);

                        i {
                            color: var(--w);

                        }
                    }
                }

                .IconList {
                    display: grid;
                    align-items: start;
                    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));

                    li {

                        border-inline-start: 1px solid var(--border);

                        a {
                            display: flex;
                            flex-direction: column;
                            align-items: center;
                            justify-content: center;
                            gap: 16px;
                            padding: 20px 16px;
                            border-bottom: 1px solid var(--border);

                            i {
                                font-size: 2rem;

                            }

                            &:hover {
                                background: var(--subText);

                            }
                        }
                    }
                }

                .textList {
                    display: flex;
                    flex-direction: column;
                    gap: 4px;
                    padding-top: 12px;
                    border-inline-start: 1px solid var(--border);
                    transition: var(--ts);

                    &:hover {
                        background: var(--subText);

                        .AE_title {
                            color: var(--w);
                        }

                        a {
                            color: var(--w);

                            i {
                                color: var(--w);

                            }
                        }
                    }

                    li {
                        padding-inline-start: 1rem;

                        &.AE_title {
                            padding-top: .5rem;
                            font-weight: 600;
                            color: var(--text);
                            text-align: center;
                            font-size: 1.01rem;
                        }

                        a {
                            font-size: .89rem;

                            &:hover {
                                text-decoration: underline;
                            }
                        }
                    }

                }

            }

        }

        .reports {
            .col-lg-3 {
                &:first-child {

                    h4,
                    h5 {
                        color: #2d91ea;
                    }
                }

                &:nth-child(2) {

                    h4,
                    h5 {
                        color: #3ebfbe;
                    }
                }

                &:nth-child(3) {

                    h4,
                    h5 {
                        color: #ffb553;
                    }
                }

                &:nth-child(4) {

                    h4,
                    h5 {
                        color: #f33e6f;
                    }
                }
            }

            .cardReport {

                padding-block: 1rem;
                padding-inline: 1.8rem;
                border-radius: 16px;

                h4 {
                    font-size: 1.8rem;
                    font-weight: 700;
                    text-transform: capitalize;

                }

                h5 {
                    font-size: 1.3rem;
                }

                small {
                    color: var(--subText);

                    i {
                        color: #d52121;
                    }
                }

                p {
                    margin-top: .4rem;
                    color: var(--subText);
                    font-weight: 500;
                    font-size: .9rem;
                }

                .details {
                    display: flex;
                    align-items: center;
                    gap: 4px;
                }
            }
        }
        .tables{
            table{
                margin-block:1rem;
            }
            thead{
                th{
                    font-size: 1rem;
                font-weight: 600;
                }
            }
        }
    </style>
    <div class="container-fluid py-3">
        {{-- quicky likns --}}
        <div class="gridSystem col_2">
            <div class="fastLinks box">
                <h4 class="border-b">
                    <i class="fa-solid fa-rocket-launch"></i>
                    إجراءات عاجلة
                </h4>
                <div class="list">
                    <ul class="IconList">
                        <li>
                            <a href="#!">
                                <i class="fa-solid fa-user-plus"></i>
                                اضف مستخدم
                            </a>
                        </li>
                        <li>
                            <a href="#!">
                                <i class="fa-solid fa-memo"></i>
                                انشاء عرض
                            </a>
                        </li>
                        <li>
                            <a href="#!">
                                <i class="fa-solid fa-file-lines"></i>
                                إنشاء فاتورة بيع
                            </a>
                        </li>
                        <li>
                            <a href="#!">
                                <i class="fa-solid fa-user-tie"></i>
                                إضافة مورد
                            </a>
                        </li>

                        <li>
                            <a href="#!">
                                <i class="fa-solid fa-file-lines"></i>
                                إنشاء فاتورة شراء
                            </a>
                        </li>
                        <li>
                            <a href="#!">
                                <i class="fa-solid fa-box"></i>
                                إدخال منتج
                            </a>
                        </li>
                        <li>
                            <a href="#!">
                                <i class="fa-solid fa-credit-card-front"></i>
                                دفعات البيع
                            </a>
                        </li>
                        <li>
                            <a href="#!">
                                <i class="fa-solid fa-circle-dollar"></i>
                                أدخل مصروف
                            </a>
                        </li>
                        <li>
                            <a href="#!">
                                <i class="fa-solid fa-cart-shopping"></i>
                                دفعات الشراء
                            </a>
                        </li>

                    </ul>
                    <ul class="textList">

                        <li>
                            <a href="#!">
                                <i class="fa-solid fa-file-chart-column"></i>
                                تقادم فواتير العملاء
                            </a>
                        </li>
                        <li>
                            <a href="#!">
                                <i class="fa-solid fa-file-chart-column"></i>
                                تقادم فواتير الشراء
                            </a>
                        </li>
                        <li>
                            <a href="#!">
                                <i class="fa-solid fa-file-chart-column"></i>
                                ﻻئحة الدفعات
                            </a>
                        </li>
                        <li>
                            <a href="#!">
                                <i class="fa-solid fa-file-chart-column"></i>
                                سجل دفعات الشراء
                            </a>
                        </li>
                        <li>
                            <a href="#!">
                                <i class="fa-solid fa-file-chart-column"></i>
                                الرصيد المتبقي للعملاء
                            </a>
                        </li>
                        <li>
                            <a href="#!">
                                <i class="fa-solid fa-file-chart-column"></i>
                                رصيد الموردين
                            </a>
                        </li>
                        <li>
                            <a href="#!">
                                <i class="fa-solid fa-file-chart-column"></i>
                                المبيعات حسب الزبون
                            </a>
                        </li>
                        <li>
                            <a href="#!">
                                <i class="fa-solid fa-file-chart-column"></i>
                                مبيعات كل مندوب
                            </a>
                        </li>
                        <li>
                            <a href="#!">
                                <i class="fa-solid fa-file-chart-column"></i>
                                قائمة اسعار المنتجات
                            </a>
                        </li>
                        <li>
                            <a href="#!">
                                <i class="fa-solid fa-file-chart-column"></i>
                                لائحة المصروفات
                            </a>
                        </li>
                        <li class="AE_title">
                            التقارير
                        </li>
                    </ul>

                </div>
            </div>
            <div class="chartGroup">
                <div class="chartFlip">
                    <div class="charts box front">
                        <div class="chartHead border-b d-flex align-items-center justify-content-between">
                            <h4>
                                <i class="fa-solid fa-chart-mixed"></i>
                                الإجمالي للفواتير مقابل الإجمالي للدفع
                            </h4>
                            <div class="actions d-flex align-items-center">
                                <button class="btn flipBtn">
                                    <i class="fa-solid fa-arrow-right-arrow-left"></i>
                                </button>
                                <button class='btn '>
                                    <i class="fa-solid fa-arrows-rotate"></i>
                                </button>
                                <div class="dropdown">
                                    <button class="btn btn-secondary dropdown-toggle AE_btnDown AE_btn" type="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        هذا العام
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="#">هذا العام</a></li>
                                        <li><a class="dropdown-item" href="#"> العام الماضي</a></li>
                                        <li><a class="dropdown-item" href="#"> N-2 سنة </a></li>
                                        <li><a class="dropdown-item" href="#"> N-3 سنة </a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="chart">
                            <canvas id="myChart" width="100%" height="50%"></canvas>
                        </div>
                    </div>
                    <div class="charts box back">
                        <div class="chartHead border-b d-flex align-items-center justify-content-between">
                            <h4>
                                <i class="fa-solid fa-chart-mixed"></i>
                                إجمالي المبيعات مقابل المصروفات
                            </h4>
                            <div class="actions d-flex align-items-center">
                                <button class="btn flipBtn">
                                    <i class="fa-solid fa-arrow-right-arrow-left"></i>
                                </button>
                                <button class='btn '>
                                    <i class="fa-solid fa-arrows-rotate"></i>
                                </button>
                                <div class="dropdown">
                                    <button class="btn btn-secondary dropdown-toggle AE_btnDown AE_btn" type="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        هذا العام
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="#">هذا العام</a></li>
                                        <li><a class="dropdown-item" href="#"> العام الماضي</a></li>
                                        <li><a class="dropdown-item" href="#"> N-2 سنة </a></li>
                                        <li><a class="dropdown-item" href="#"> N-3 سنة </a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="chart">
                            <canvas id="myChart2" width="100%" height="50%"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            {{-- --}}
        </div>
        {{-- reports --}}
        <div class="row reports">
            <div class="col-lg-3 col-md-6 col-sm-6 p-2 p-lg-3">
                <div class="cardReport box">
                    <h4>0 SAR</h4>
                    <div class="details">
                        <h5>الأرباح</h5>
                        <small>
                            <i class="fa-solid fa-circle-arrow-down"></i>
                            100%
                        </small>
                    </div>
                    <p>السابق 0 SAR | الشهر الماضي 0 SAR</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6 p-2 p-lg-3">
                <div class="cardReport box">
                    <h4>0 SAR</h4>
                    <div class="details">
                        <h5>المبيعات</h5>
                        <small>
                            <i class="fa-solid fa-circle-arrow-down"></i>
                            100%
                        </small>
                    </div>
                    <p>السابق 0 SAR | الشهر الماضي 0 SAR</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6 p-2 p-lg-3">
                <div class="cardReport box">
                    <h4>0 SAR</h4>
                    <div class="details">
                        <h5>نقد</h5>
                        <small>
                            <i class="fa-solid fa-circle-arrow-down"></i>
                            100%
                        </small>
                    </div>
                    <p>السابق 0 SAR | الشهر الماضي 0 SAR</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6 p-2 p-lg-3">
                <div class="cardReport box">
                    <h4>0 SAR</h4>
                    <div class="details">
                        <h5>الرصيد</h5>
                        <small>
                            <i class="fa-solid fa-circle-arrow-down"></i>
                            100%
                        </small>
                    </div>
                    <p>السابق 0 SAR | الشهر الماضي 0 SAR</p>
                </div>
            </div>

        </div>

        {{-- charts 3_4 --}}
        <div class="gridSystem col_2">
            <div class="chartGroup ">
                <div class="chartFlip">
                    <div class="charts box cricleChart">
                        <div class="chartHead border-b d-flex align-items-center justify-content-between">
                            <h4>
                                <i class="fa-solid fa-chart-mixed"></i>
                                أعلى المصاريف حسب التصنيف
                            </h4>
                            <div class="actions d-flex align-items-center">

                                <button class='btn '>
                                    <i class="fa-solid fa-arrows-rotate"></i>
                                </button>
                                <div class="dropdown">
                                    <button class="btn btn-secondary dropdown-toggle AE_btnDown AE_btn" type="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        هذا العام
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="#">هذا العام</a></li>
                                        <li><a class="dropdown-item" href="#"> العام الماضي</a></li>
                                        <li><a class="dropdown-item" href="#"> N-2 سنة </a></li>
                                        <li><a class="dropdown-item" href="#"> N-3 سنة </a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="chart smallSize">
                            <canvas id="myChart3" width="50%" height="50%"></canvas>
                        </div>
                    </div>

                </div>
            </div>
            {{-- --}}
            <div class="chartGroup">
                <div class="chartFlip">
                    <div class="charts box front cricleChart">
                        <div class="chartHead border-b d-flex align-items-center justify-content-between">
                            <h4>
                                <i class="fa-solid fa-chart-mixed"></i>
                                العملاء الأكثر نشاطا
                            </h4>
                            <div class="actions d-flex align-items-center">
                                <button class="btn flipBtn">
                                    <i class="fa-solid fa-arrow-right-arrow-left"></i>
                                </button>
                                <button class='btn '>
                                    <i class="fa-solid fa-arrows-rotate"></i>
                                </button>
                                <div class="dropdown">
                                    <button class="btn btn-secondary dropdown-toggle AE_btnDown AE_btn" type="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        هذا العام
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="#">هذا العام</a></li>
                                        <li><a class="dropdown-item" href="#"> العام الماضي</a></li>
                                        <li><a class="dropdown-item" href="#"> N-2 سنة </a></li>
                                        <li><a class="dropdown-item" href="#"> N-3 سنة </a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="chart smallSize">
                            <canvas id="myChart4" width="100%" height="50%"></canvas>
                        </div>
                    </div>
                    <div class="charts box back cricleChart">
                        <div class="chartHead border-b d-flex align-items-center justify-content-between">
                            <h4>
                                <i class="fa-solid fa-chart-mixed"></i>
                                المنتجات الأكثر مبيعا
                            </h4>
                            <div class="actions d-flex align-items-center">
                                <button class="btn flipBtn">
                                    <i class="fa-solid fa-arrow-right-arrow-left"></i>
                                </button>
                                <button class='btn '>
                                    <i class="fa-solid fa-arrows-rotate"></i>
                                </button>
                                <div class="dropdown">
                                    <button class="btn btn-secondary dropdown-toggle AE_btnDown AE_btn" type="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        هذا العام
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="#">هذا العام</a></li>
                                        <li><a class="dropdown-item" href="#"> العام الماضي</a></li>
                                        <li><a class="dropdown-item" href="#"> N-2 سنة </a></li>
                                        <li><a class="dropdown-item" href="#"> N-3 سنة </a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="chart smallSize">
                            <canvas id="myChart5" width="100%" height="50%"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            {{-- --}}
        </div>
        {{-- tabels --}}
        <div class="gridSystem col_2 py-3">

            {{--  --}}
            <div class="box tables">
                <h4 class="border-b">
                    <i class="fa-solid fa-box"></i>
                    تنبيهات المخزن
                </h4>
                <table class="table table-striped no-margin">
                    <thead>
                        <tr>
                            <th style="width: 15%;">الوضع</th>
                            <th style="width: 20%;">المنتج</th>
                            <th style="width: 10%;">الكمية</th>
                            <th style="width: 10%;">أقل</th>
                            <th style="width: 30%;">تنبيه</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                جيد
                            </td>
                            <td> منتج ١ </td>
                            <td> 1000 </td>
                            <td> 50 </td>
                            <td>
                                لقد اوشك المنتج علي الانتهاء
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            {{--  --}}
            <div class="box tables">
                <h4 class="border-b">
                    <i class="fa-solid fa-file-lines"></i>
                    الفواتير الأخيرة
                </h4>
                <table class="table table-striped no-margin">
                    <thead>
                        <tr>
                            <th style="width: 15%;">الوضع</th>
                            <th style="width: 20%;">تاريخ الاستحقاق</th>
                            <th style="width: 10%;">الفاتورة</th>
                            <th style="width: 35%;">العميل</th>
                            <th style="text-align: right; width: 15%;white-space:nowrap;">الرصيد</th>
                            <th style="text-align: center; width: 5%;">PDF</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                جيد
                            </td>
                            <td>
                                4/42025
                            </td>
                            <td>
                                <button type="button" class="btn btn-primary">
                                    <a href="£!" class='text-white'>عرض</a>
                                </button>
                            </td>
                            <td> احمد علي السيد </td>
                            <td> 5000 </td>
                            <td>
                                <button type="button" class="btn btn-danger">
                                    <a href="£!" class='text-danger'>تحميل</a>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            {{--  --}}
        </div>








        {{-- stop links --}}

    </div>
@endsection
@section('js')
    {{--
    <script src="{{ asset('admin/assets/js/Chart.bundle.min.js')}}"></script> --}}
    {{--
    <script src="{{ asset('admin') }}/assets/js/theme.js"></script> --}}
    <script src="{{ asset('admin') }}/assets/js/bundle/apexcharts.bundle.js"></script>
    {{--
    <script src="{{ asset('admin/assets/js/canvasjs.min.js')}}"></script> --}}
    <script>
        // Sales Statistics
        var options = {
            series: [{
                name: 'إيرادات',
                data: @json($revenues_value_month ?? [])
            }, {
                name: 'مصروفات',
                data: @json($expenses_value_month ?? [])
            }],
            chart: {
                type: 'bar',
                height: 190,
                stacked: true,
                //stackType: '100%',
                toolbar: {
                    show: false,
                },
            },
            colors: ['var(--chart-color1)', 'var(--chart-color2)'],
            responsive: [{
                breakpoint: 480,
                options: {
                    legend: {
                        position: 'bottom',
                        offsetX: -10,
                        offsetY: 0
                    }
                }
            }],
            xaxis: {
                categories: ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15',],
            },
            fill: {
                opacity: 1
            },
            dataLabels: {
                enabled: false,
            },
            legend: {
                position: 'bottom',
            },
            tooltip: {
                y: [{
                    title: {
                        formatter: function (val) {
                            return val + " (K)"
                        }
                    }
                }, {
                    title: {
                        formatter: function (val) {
                            return val + " (K)"
                        }
                    }
                }]
            },
        };
        var chart = new ApexCharts(document.querySelector("#apex-SalesStatistics"), options);
        chart.render();
    </script>

    <script>
        // const cards = document.querySelectorAll(".chartFlip");
        const flipBtn = document.querySelectorAll(".flipBtn");
        flipBtn.forEach((btn) => {
            btn.addEventListener("click", (e) => {
                const card = e.target.closest(".chartFlip");
                if (card) {
                    card.classList.toggle("flip");
                }
            });
        });
    </script>
    {{-- charts --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        // id , labels , labelName_1 , labelName_2,data_1,data_2,type,textY,textX
        function createCharts(id, label, data, type, textY, textX, dir = 'bottom', aligns = 'end') {
            const ctx = document.getElementById(id).getContext('2d');
            const config = {
                type: type,
                data: {
                    labels: label,
                    datasets: data
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: false,
                            text: 'chart'
                        },
                        legend: {
                            position: dir,
                            align: aligns,
                            labels: {
                                font: {
                                    weight: 'bold',
                                    family: 'Nunito, sans-serif'
                                },
                                boxWidth: 15,
                                boxHeight: 15
                            }
                        },
                    },

                    scales: type === 'bar' ? {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: textY
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: textX
                            }
                        }
                    } : {}


                }
            };
            new Chart(ctx, config);
        };


        //chart 1
        const MonLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const dataMonBar = [
            {
                label: 'تم تحرير الفواتير',
                data: [1200, 1900, 3000, 2500, 2700, 3500, 4000, 3200, 2800, 3100, 2900, 3300],
                backgroundColor: 'rgba(63, 192, 190, 0.6)',
                borderColor: 'rgba(63, 192, 190, 1)',
                borderWidth: 1,
            },
            {
                label: 'دفعت',
                data: [1000, 1500, 2800, 2200, 2600, 3300, 3700, 3000, 2700, 2900, 2600, 3100],
                backgroundColor: 'rgba(45, 145, 234, 0.6)',
                borderColor: 'rgba(45, 145, 234, 1)',
                borderWidth: 1
            }
        ];

        //chart2
        const dataMonBar2 = [
            {
                label: '  نفقات',
                data: [1200, 1900, 3000, 2500, 2700, 3500, 4000, 3200, 2800, 3100, 2900, 3300],
                backgroundColor: 'rgba(243, 62, 111, 0.6)',
                borderColor: 'rgba(243, 62, 111, 1)',
                borderWidth: 1,
            },
            {
                label: 'دفعت',
                data: [1000, 1500, 2800, 2200, 2600, 3300, 3700, 3000, 2700, 2900, 2600, 3100],
                backgroundColor: 'rgba(45, 145, 234, 0.6)',
                borderColor: 'rgba(45, 145, 234, 1)',
                borderWidth: 1
            }
        ];
        // chart3
        const labels3 = ['المصروف', 'المصروف', 'المصروف', 'المصروف'];
        const dataMonBar3 = [
            {
                label: '  نفقات',
                data: [50, 25, 5, 20],
                backgroundColor: ['#4bc0c0', '#ff6384', '#ffce56', '#37a2eb']
            },
        ];
        // chart4
        const labels4 = ['عميل نشط', 'عميل نشط', 'عميل نشط', 'عميل نشط'];
        const dataMonBar4 = [
            {
                label: '  نفقات',
                data: [50, 25, 5, 20],
                backgroundColor: ['#4bc0c0', '#ff6384', '#ffce56', '#37a2eb']
            },
        ];
        // chart5
        const labels5 = ['المخزن', 'المخزن', 'المخزن', 'المخزن'];
        const dataMonBar5 = [
            {
                label: '  نفقات',
                data: [50, 25, 5, 20],
                backgroundColor: ['#4bc0c0', '#ff6384', '#ffce56', '#37a2eb']
            },
        ];
        createCharts('myChart', MonLabels, dataMonBar, 'bar', 'العملة (ريال سعودي)', 'شهور');
        createCharts('myChart2', MonLabels, dataMonBar2, 'bar', 'العملة (ريال سعودي)', 'شهور');
        createCharts('myChart3', labels3, dataMonBar3, 'pie', '', '', 'left', 'center');
        createCharts('myChart4', labels4, dataMonBar4, 'pie', '', '', 'left', 'center');
        createCharts('myChart5', labels5, dataMonBar5, 'doughnut', '', '', 'left', 'center');

    </script>

@endsection
