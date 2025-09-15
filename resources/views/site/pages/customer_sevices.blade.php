@extends('site.layouts.index')
@section('title')
@endsection
@section('content')
    <!-- select Customer Service -->
    <section class="selectCustomerService">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 p-2">
                    <div class="headTitle">
                        <h1> اختر احد مندوبي خدمة العملاء </h1>
                        <p>
                            يرجى اختيار احد ممثلي خدمة العملاء لمواصلة إتمام التعاقد واكمال الإستقدام
                        </p>
                    </div>
                    <form action="selected-customer-service.html">
                        <div class="choose">
                            <div class="customerOption " data-aos=" fade-up">
                                <input type="radio" class="btn-check" name="customer" id="option1">
                                <label class="btn btn-outline" for="option1">
                                    <img src="assets/images/avater2.webp" alt="">
                                    <span> أ.شوقي </span>
                                </label>
                            </div>
                            <div class="customerOption " data-aos=" fade-up">
                                <input type="radio" class="btn-check" name="customer" id="option2">
                                <label class="btn btn-outline" for="option2">
                                    <img src="assets/images/avater2.webp" alt="">
                                    <span> أ.مدحت </span>
                                </label>
                            </div>
                            <div class="customerOption " data-aos=" fade-up">
                                <input type="radio" class="btn-check" name="customer" id="option3">
                                <label class="btn btn-outline" for="option3">
                                    <img src="assets/images/avater2.webp" alt="">
                                    <span> أ.سعاد </span>
                                </label>
                            </div>
                        </div>
                        <div class=" pt-4 p-2 text-center">
                            <button type="submit" class="btn px-5 m-auto">
                                تأكيد
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
    <!-- themeColor -->
    <div class="themeColor">
        <input type="color" id="colorInput" class="color-input">
    </div>
@endsection
@section('js')
@endsection
