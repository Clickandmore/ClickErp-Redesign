@extends('site.layouts.index')
@section('title')

@endsection
@section('content')
    <section class="profile">
        <div class="container ">
            <!-- user Header  -->
            @include('site.pages.profile.includes.user_header')
            <!-- /user Header  -->
            <div class="row">
                <!-- links -->
                @include('site.pages.profile.includes.sidebar')
                <!-- content -->
                <div class=" col-md-8 col-lg-9 p-2 profileContent">
                    <!-- chat -->
                    <section id="chat">
                        <div class="container p-0">
                            <div class="row">
                                <div class="col-lg-4 p-1">
                                    <button class="defaultBtn UsersControl d-lg-none">
                                        <i class="fas fa-comments-alt mx-1"></i> المحادثات <span></span>
                                    </button>

                                    <!-- chat users -->
                                    <div class=" Users ">
                                        <div class="UsersHeder">
                                            <h5> المحادثات </h5>
                                            <span class="closeUsers"> <i class="fal fa-times"></i> </span>
                                        </div>
                                        <ul class="nav">
                                            <li>
                                                <a class="active" id="chat1-tab" data-bs-toggle="tab" href="#chat1"
                                                   aria-controls="chat1"
                                                   aria-selected="true">
                                                    <div class="userInfo">
                                                        <div class="userDiv">
                                <span class="nameDate">
                                  <h6 class="name"> مصطفي ابراهيم </h6>
                                  <span class=" date"> 28 / 4 / 2022 </span>
                                </span>
                                                            <p> اريد طلب استقدام</p>
                                                        </div>
                                                    </div>
                                                </a>
                                            </li>
                                            <li>
                                                <a id="profile-tab" data-bs-toggle="tab" href="#chat2"
                                                   aria-controls="chat2"
                                                   aria-selected="false">
                                                    <div class="userInfo">
                                                        <div class="userDiv">
                                <span class="nameDate">
                                  <h6 class="name"> احمد حلمي </h6>
                                  <span class=" date"> 28 / 4 / 2022 </span>
                                </span>
                                                            <p> السلام عليكم ورحمة الله </p>
                                                        </div>
                                                    </div>
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-lg-8 p-1">
                                    <div class="tab-content p-0">
                                        <div class="tab-pane fade show active" id="chat1" aria-labelledby="chat1-tab">
                                            <div class=" chatTop">
                                                <h6> مصطفي ابراهيم </h6>
                                            </div>
                                            <div class=" chatBox ">
                                                <div class=" chatBody">
                                                    <div class="userSend">
                                                        <div class="sendMassage">
                                                            <p> اريد طلب استقدام </p>
                                                            <div class=" time">
                                                                <span> 29 / 4 / 2022 </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="userReceive">
                                                        <div class="sendMassage">
                                                            <p> سوف ارسل لك الطلب الخاصة بك </p>
                                                            <div class=" time">
                                                                <span> 29 / 4 / 2022 </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="chat-input">
                                                    <form>
                                                        <input type="text" id="chat-input"
                                                               placeholder="ارسل رسالة ..."/>
                                                        <button type="submit" class="chat-submit" id="chat-submit"><i
                                                                class="fas fa-paper-plane"></i></button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="tab-pane fade" id="chat2" aria-labelledby="chat2-tab">
                                            <div class=" chatTop">
                                                <h6> احمد حلمي</h6>
                                            </div>
                                            <div class=" chatBox ">
                                                <div class=" chatBody">
                                                    <div class="userSend">
                                                        <div class="sendMassage">
                                                            <p> اريد طلب استقدام </p>
                                                            <div class=" time">
                                                                <span> 29 / 4 / 2022 </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="userReceive">
                                                        <div class="sendMassage">
                                                            <p> سوف ارسل لك الطلب الخاصة بك </p>
                                                            <div class=" time">
                                                                <span> 29 / 4 / 2022 </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="chat-input">
                                                    <form>
                                                        <input type="text" id="chat-input"
                                                               placeholder="ارسل رسالة ..."/>
                                                        <button type="submit" class="chat-submit" id="chat-submit"><i
                                                                class="fas fa-paper-plane"></i></button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </section>
@endsection
@section('js')
@endsection
