@php
$locale = \App::currentLocale();
$scaleDirection = ($locale == "ar")? "scale-right":"scale-left";
$user = getAuthUser();
@endphp
<header class="page-header sticky-top px-xl-4 px-sm-2 px-0 py-lg-2 py-1">
    <div class="container-fluid">
        <nav class="navbar">

            <div class="d-flex">
                <button type="button" class="btn btn-link d-none d-xl-block sidebar-mini-btn p-0 text-primary">
                    <span class="hamburger-icon">
                        <span class="line"></span>
                        <span class="line"></span>
                        <span class="line"></span>
                    </span>
                </button>
                <button type="button" class="btn btn-link d-block d-xl-none menu-toggle p-0 text-primary">
                    <span class="hamburger-icon">
                        <span class="line"></span>
                        <span class="line"></span>
                        <span class="line"></span>
                    </span>
                </button>
                <a href="{{route('dashboard.index')}}" class="brand-icon d-flex align-items-center mx-2 mx-sm-3 text-primary">
                    <img src="@if(isset($settings) && isset($settings->logo_header))
                    {{ showFile($settings->logo_header) }}
                    @else
                    {{url('/')}}/web/img/logo.svg
                @endif" style="height: 30px;filter: none;" alt="">
                    {{-- <svg height="22" viewBox="0 0 67 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path class="fill-muted"
                            d="M0.863281 18.9997V1.14968H3.11328V16.9997H10.9133V18.9997H0.863281Z" />
                        <path class="fill-muted"
                            d="M27.3045 12.6997C27.3045 13.933 27.0545 15.0497 26.5545 16.0497C26.0545 17.033 25.2878 17.8163 24.2545 18.3997C23.2378 18.9663 21.9378 19.2497 20.3545 19.2497C18.1378 19.2497 16.4462 18.6497 15.2795 17.4497C14.1295 16.233 13.5545 14.633 13.5545 12.6497V1.14968H15.8045V12.7247C15.8045 14.1747 16.1878 15.2997 16.9545 16.0997C17.7378 16.8997 18.9128 17.2997 20.4795 17.2997C21.5628 17.2997 22.4378 17.108 23.1045 16.7247C23.7878 16.3247 24.2878 15.783 24.6045 15.0997C24.9212 14.3997 25.0795 13.5997 25.0795 12.6997V1.14968H27.3045V12.6997Z" />
                        <path class="fill-secondary"
                            d="M46.5286 0.765681C46.6246 0.82568 46.6726 0.92168 46.6726 1.05368L46.5466 18.6037C46.5466 18.8677 46.3906 18.9937 46.0786 18.9817H44.4586L33.1546 3.62768L33.1006 13.1677L37.5646 13.1857C37.6726 13.1857 37.7626 13.2217 37.8346 13.2937C37.9186 13.3657 37.9606 13.4617 37.9606 13.5817L37.9426 14.8957C37.9426 15.0157 37.9066 15.1237 37.8346 15.2197C37.7626 15.3037 37.6666 15.3457 37.5466 15.3457L31.3726 15.2917H31.3546C31.1026 15.2917 30.9706 15.1837 30.9586 14.9677L31.0666 0.98168C31.0666 0.849679 31.1026 0.74768 31.1746 0.675681C31.2586 0.59168 31.3666 0.555679 31.4986 0.56768H33.1726C33.3406 0.56768 33.4726 0.63368 33.5686 0.765681L44.4406 15.4177L44.5486 0.94568C44.5966 0.741679 44.7286 0.639679 44.9446 0.639679L46.2046 0.65768C46.3126 0.65768 46.4206 0.69368 46.5286 0.765681ZM39.7786 16.7857C39.8986 16.7977 39.9946 16.8397 40.0666 16.9117C40.1386 16.9717 40.1746 17.0677 40.1746 17.1997L40.1566 18.4957C40.1566 18.6157 40.1206 18.7237 40.0486 18.8197C39.9886 18.9037 39.8926 18.9457 39.7606 18.9457H31.3546C31.2346 18.9457 31.1386 18.9097 31.0666 18.8377C30.9946 18.7657 30.9586 18.6697 30.9586 18.5497V17.2357C30.9586 17.1157 30.9946 17.0137 31.0666 16.9297C31.1386 16.8337 31.2406 16.7857 31.3726 16.7857H35.5666C38.0266 16.7857 39.4306 16.7857 39.7786 16.7857Z" />
                        <path class="fill-muted"
                            d="M66.0301 10.0497C66.0301 11.433 65.8551 12.6913 65.5051 13.8247C65.1551 14.9413 64.6301 15.908 63.9301 16.7247C63.2467 17.5413 62.3884 18.1663 61.3551 18.5997C60.3384 19.033 59.1551 19.2497 57.8051 19.2497C56.4051 19.2497 55.1884 19.033 54.1551 18.5997C53.1217 18.1497 52.2634 17.5247 51.5801 16.7247C50.8967 15.908 50.3884 14.933 50.0551 13.7997C49.7217 12.6663 49.5551 11.408 49.5551 10.0247C49.5551 8.19135 49.8551 6.59135 50.4551 5.22468C51.0551 3.85801 51.9634 2.79135 53.1801 2.02468C54.4134 1.25801 55.9634 0.87468 57.8301 0.87468C59.6134 0.87468 61.1134 1.25801 62.3301 2.02468C63.5467 2.77468 64.4634 3.84135 65.0801 5.22468C65.7134 6.59135 66.0301 8.19968 66.0301 10.0497ZM51.9301 10.0497C51.9301 11.5497 52.1384 12.8413 52.5551 13.9247C52.9717 15.008 53.6134 15.8413 54.4801 16.4247C55.3634 17.008 56.4717 17.2997 57.8051 17.2997C59.1551 17.2997 60.2551 17.008 61.1051 16.4247C61.9717 15.8413 62.6134 15.008 63.0301 13.9247C63.4467 12.8413 63.6551 11.5497 63.6551 10.0497C63.6551 7.79968 63.1884 6.04135 62.2551 4.77468C61.3217 3.49135 59.8467 2.84968 57.8301 2.84968C56.4801 2.84968 55.3634 3.14135 54.4801 3.72468C53.6134 4.29135 52.9717 5.11635 52.5551 6.19968C52.1384 7.26635 51.9301 8.54968 51.9301 10.0497Z" />
                    </svg> --}}
                </a>
            </div>

            <div class="header-left flex-grow-1 d-none d-md-block">
                <div class="main-search px-3 flex-fill">
                    <input class="form-control" type="text" placeholder="">
                    <div class="card shadow rounded-4 search-result slidedown">
                       <div class="card-body">
                            <small class="text-uppercase text-muted">روابط سريعة </small>
                            <div class="d-flex flex-wrap align-items-start mt-2 mb-4">


                            </div>
                            {{-- <small class="text-uppercase text-muted">Suggestions</small>
                            <div class="card list-group list-group-flush list-group-custom mt-2">
                                <a class="list-group-item list-group-item-action text-truncate" href="#">
                                    <div class="fw-bold">Helper Class</div>
                                    <small class="text-muted">Lorem Ipsum is simply dummy text of the printing and
                                        typesetting industry.</small>
                                </a>
                                <a class="list-group-item list-group-item-action text-truncate" href="#">
                                    <div class="fw-bold">Date Range Picker</div>
                                    <small class="text-muted">There are many variations of passages of Lorem Ipsum
                                        available</small>
                                </a>
                                <a class="list-group-item list-group-item-action text-truncate" href="#">
                                    <div class="fw-bold">Image Input</div>
                                    <small class="text-muted">It is a long established fact that a reader will be
                                        distracted</small>
                                </a>
                                <a class="list-group-item list-group-item-action text-truncate" href="#">
                                    <div class="fw-bold">DataTables for jQuery</div>
                                    <small class="text-muted">Lorem Ipsum is simply dummy text of the printing and
                                        typesetting industry.</small>
                                </a>
                                <a class="list-group-item list-group-item-action text-truncate" href="#">
                                    <div class="fw-bold">Development Setup</div>
                                    <small class="text-muted">Contrary to popular belief, Lorem Ipsum is not simply
                                        random text.</small>
                                </a>
                            </div> --}}
                        </div>
                    </div>
                </div>
            </div>

            <ul class="header-right justify-content-end d-flex align-items-center mb-0">
                <li>
                    <div class="dropdown morphing {{$scaleDirection}} notifications">
                        <a class="nav-link dropdown-toggle after-none" href="#" role="button" data-bs-toggle="dropdown">
                            <span class="d-none d-xl-block me-2">الإشعارات</span>
                            <i class="fa fa-bell"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notification-badge" style="display: none;">
                                0
                            </span>
                        </a>
                        <div class="dropdown-menu shadow rounded-4 border-0 p-0 m-0" style="width: 300px; max-height: 400px; overflow-y: auto;">
                            <div class="card">
                                <div class="card-header p-3 d-flex justify-content-between align-items-center">
                                    <h6 class="card-title mb-0">الإشعارات</h6>
                                    <button class="btn btn-sm btn-link mark-all-read">تعيين الكل كمقروء</button>
                                </div>
                                <div class="card-body p-0">
                                    <div class="notification-list">
                                        <!-- Notifications will be loaded here -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </li>

                <li class="d-none d-xl-inline-block">
                    <a class="nav-link fullscreen" href="#{{--javascript:void(0);--}}"
                        onclick="toggleFullScreen(documentElement)">
                        <svg viewBox="0 0 16 16" width="18px" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd"
                                d="M5.8279 10.172C5.73414 10.0783 5.60698 10.0256 5.4744 10.0256C5.34182 10.0256 5.21467 10.0783 5.1209 10.172L1.0249 14.268V11.5C1.0249 11.3674 0.972224 11.2402 0.878456 11.1464C0.784688 11.0527 0.657511 11 0.524902 11C0.392294 11 0.265117 11.0527 0.171349 11.1464C0.0775808 11.2402 0.0249023 11.3674 0.0249023 11.5V15.475C0.0249023 15.6076 0.0775808 15.7348 0.171349 15.8285C0.265117 15.9223 0.392294 15.975 0.524902 15.975H4.4999C4.63251 15.975 4.75969 15.9223 4.85346 15.8285C4.94722 15.7348 4.9999 15.6076 4.9999 15.475C4.9999 15.3424 4.94722 15.2152 4.85346 15.1214C4.75969 15.0277 4.63251 14.975 4.4999 14.975H1.7319L5.8279 10.879C5.92164 10.7852 5.9743 10.6581 5.9743 10.5255C5.9743 10.3929 5.92164 10.2658 5.8279 10.172ZM10.1719 10.172C10.2657 10.0783 10.3928 10.0256 10.5254 10.0256C10.658 10.0256 10.7851 10.0783 10.8789 10.172L14.9749 14.268V11.5C14.9749 11.3674 15.0276 11.2402 15.1213 11.1464C15.2151 11.0527 15.3423 11 15.4749 11C15.6075 11 15.7347 11.0527 15.8285 11.1464C15.9222 11.2402 15.9749 11.3674 15.9749 11.5V15.475C15.9749 15.6076 15.9222 15.7348 15.8285 15.8285C15.7347 15.9223 15.6075 15.975 15.4749 15.975H11.4999C11.3673 15.975 11.2401 15.9223 11.1463 15.8285C11.0526 15.7348 10.9999 15.6076 10.9999 15.475C10.9999 15.3424 11.0526 15.2152 11.1463 15.1214C11.2401 15.0277 11.3673 14.975 11.4999 14.975H14.2679L10.1719 10.879C10.0782 10.7852 10.0255 10.6581 10.0255 10.5255C10.0255 10.3929 10.0782 10.2658 10.1719 10.172ZM5.8279 5.82799C5.73414 5.92173 5.60698 5.97439 5.4744 5.97439C5.34182 5.97439 5.21467 5.92173 5.1209 5.82799L1.0249 1.73199V4.49999C1.0249 4.6326 0.972224 4.75978 0.878456 4.85355C0.784688 4.94732 0.657511 4.99999 0.524902 4.99999C0.392294 4.99999 0.265117 4.94732 0.171349 4.85355C0.0775808 4.75978 0.0249023 4.6326 0.0249023 4.49999V0.524994C0.0249023 0.392386 0.0775808 0.265209 0.171349 0.17144C0.265117 0.0776723 0.392294 0.0249939 0.524902 0.0249939H4.4999C4.63251 0.0249939 4.75969 0.0776723 4.85346 0.17144C4.94722 0.265209 4.9999 0.392386 4.9999 0.524994C4.9999 0.657602 4.94722 0.784779 4.85346 0.878547C4.75969 0.972315 4.63251 1.02499 4.4999 1.02499H1.7319L5.8279 5.12099C5.92164 5.21476 5.9743 5.34191 5.9743 5.47449C5.9743 5.60708 5.92164 5.73423 5.8279 5.82799Z" />
                            <path class="fill-secondary"
                                d="M10.5253 5.97439C10.3927 5.97439 10.2655 5.92173 10.1718 5.82799C10.078 5.73423 10.0254 5.60708 10.0254 5.47449C10.0254 5.34191 10.078 5.21476 10.1718 5.12099L14.2678 1.02499H11.4998C11.3672 1.02499 11.24 0.972315 11.1462 0.878547C11.0525 0.784779 10.9998 0.657602 10.9998 0.524994C10.9998 0.392386 11.0525 0.265209 11.1462 0.17144C11.24 0.0776723 11.3672 0.0249939 11.4998 0.0249939H15.4748C15.6074 0.0249939 15.7346 0.0776723 15.8283 0.17144C15.9221 0.265209 15.9748 0.392386 15.9748 0.524994V4.49999C15.9748 4.6326 15.9221 4.75978 15.8283 4.85355C15.7346 4.94732 15.6074 4.99999 15.4748 4.99999C15.3422 4.99999 15.215 4.94732 15.1212 4.85355C15.0275 4.75978 14.9748 4.6326 14.9748 4.49999V1.73199L10.8788 5.82799C10.785 5.92173 10.6579 5.97439 10.5253 5.97439Z" />
                        </svg>
                    </a>
                </li>

                <li class="d-none d-xl-inline-block">
                    <div class="dropdown morphing {{$scaleDirection}} Language mx-sm-2">
                        <a class="nav-link dropdown-toggle after-none" href="#" role="button" data-bs-toggle="dropdown">
                            <svg viewBox="0 0 16 16" width="18px" fill="currentColor"
                                xmlns="http://www.w3.org/2000/svg">
                                <path class="fill-secondary"
                                    d="M4.545 6.714 4.11 8H3l1.862-5h1.284L8 8H6.833l-.435-1.286H4.545zm1.634-.736L5.5 3.956h-.049l-.679 2.022H6.18z" />
                                <path
                                    d="M0 2a2 2 0 0 1 2-2h7a2 2 0 0 1 2 2v3h3a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-3H2a2 2 0 0 1-2-2V2zm2-1a1 1 0 0 0-1 1v7a1 1 0 0 0 1 1h7a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H2zm7.138 9.995c.193.301.402.583.63.846-.748.575-1.673 1.001-2.768 1.292.178.217.451.635.555.867 1.125-.359 2.08-.844 2.886-1.494.777.665 1.739 1.165 2.93 1.472.133-.254.414-.673.629-.89-1.125-.253-2.057-.694-2.82-1.284.681-.747 1.222-1.651 1.621-2.757H14V8h-3v1.047h.765c-.318.844-.74 1.546-1.272 2.13a6.066 6.066 0 0 1-.415-.492 1.988 1.988 0 0 1-.94.31z" />
                            </svg>
                        </a>
                        <div class="dropdown-menu rounded-4 shadow border-0 p-0" data-bs-popper="none">
                          <div class="card">
                              {{--    <ul style="list-style: none;: 0;padding: 0;">
                                    @foreach(LaravelLocalization::getSupportedLocales() as $localeCode => $properties)
                                        <li style="display: inline-block;-right: 10px;">
                                            <a style="color: #333333;font-size: 16px;text-decoration: none;padding: 5px 10px;border-radius: 5px;" rel="alternate" hreflang="{{ $localeCode }}" href="{{ LaravelLocalization::getLocalizedURL($localeCode, null, [], true) }}">
                                                {{ $properties['native'] }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>--}}
                                 <div class="list-group list-group-custom" style="width: 200px;">

                                     <a href="{{ LaravelLocalization::getLocalizedURL("en", null, [], true) }}" class="list-group-item"><span
                                            class="flag-icon flag-icon-us me-2"></span>US English</a>

                                    <a href="{{ LaravelLocalization::getLocalizedURL("ar", null, [], true) }}" class="list-group-item"><span
                                            class="flag-icon flag-icon-sa me-2"></span>Saudi Arabia</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </li>

                <li class="d-none d-sm-inline-block d-xl-none">
                    <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#MynotesModal">
                        <svg viewBox="0 0 16 16" width="18px" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                            <path class="fill-secondary"
                                d="M1.5 0A1.5 1.5 0 0 0 0 1.5V13a1 1 0 0 0 1 1V1.5a.5.5 0 0 1 .5-.5H14a1 1 0 0 0-1-1H1.5z" />
                            <path
                                d="M3.5 2A1.5 1.5 0 0 0 2 3.5v11A1.5 1.5 0 0 0 3.5 16h6.086a1.5 1.5 0 0 0 1.06-.44l4.915-4.914A1.5 1.5 0 0 0 16 9.586V3.5A1.5 1.5 0 0 0 14.5 2h-11zM3 3.5a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 .5.5V9h-4.5A1.5 1.5 0 0 0 9 10.5V15H3.5a.5.5 0 0 1-.5-.5v-11zm7 11.293V10.5a.5.5 0 0 1 .5-.5h4.293L10 14.793z" />
                        </svg>
                    </a>
                </li>
                <li class="d-none d-xl-inline-block">
                    <a class="nav-link" href="{{url('/')}}" >
                        <svg class="avatar xs" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M15.7469 6C15.9125 6.64062 16 7.30937 16 8C16 8.69063 15.9125 9.35938 15.7469 10H11.9C11.9656 9.35625 12 8.65938 12 8C12 7.3125 11.9656 6.64375 11.9 6H15.7469ZM15.4187 5H11.7719C11.4594 3.00437 10.8406 1.33188 10.0437 0.263813C12.4937 0.909063 14.4812 2.68563 15.4187 5ZM10.7594 5H5.24062C5.43125 3.8625 5.725 2.85562 6.08437 2.04219C6.4125 1.30437 6.77812 0.769063 7.13125 0.431563C7.48125 0.0993125 7.77187 0 8 0C8.22812 0 8.51875 0.0993125 8.86875 0.431563C9.22187 0.769063 9.5875 1.30437 9.91562 2.04219C10.275 2.85562 10.5687 3.8625 10.7594 5ZM0.581563 5C1.51844 2.68563 3.50625 0.909063 5.95625 0.263813C5.15938 1.33188 4.54063 3.00437 4.22813 5H0.581563ZM4.1 6C4.03437 6.64375 3.97187 7.3125 3.97187 8C3.97187 8.65938 4.03437 9.35625 4.1 10H0.252031C0.0875 9.35938 0 8.69063 0 8C0 7.30937 0.0875 6.64062 0.252031 6H4.1ZM6.08437 13.9563C5.725 13.1438 5.43125 12.1375 5.24062 11H10.7594C10.5687 12.1375 10.275 13.1438 9.91562 13.9563C9.5875 14.6969 9.22187 15.2313 8.86875 15.5688C8.51875 15.9 8.22813 16 7.97188 16C7.77188 16 7.48125 15.9 7.13125 15.5688C6.77812 15.2313 6.4125 14.6969 6.08437 13.9563ZM5.95625 15.7375C3.50625 15.0906 1.51844 13.3156 0.581563 11H4.22813C4.54063 12.9969 5.15938 14.6687 5.95625 15.7375ZM10.0437 15.7375C10.8406 14.6687 11.4594 12.9969 11.7719 11H15.4187C14.4812 13.3156 12.4937 15.0906 10.0437 15.7375Z"
                                fill="black"/>
                            <path class="fill-secondary"
                                  d="M10.8969 10C10.9625 9.3625 11 8.69375 11 8C11 7.30625 10.9625 6.6375 10.8969 6H5.10317C5.03755 6.6375 4.97192 7.30625 4.97192 8C4.97192 8.69375 5.03755 9.3625 5.10317 10H10.8969Z"
                                  fill="black"/>
                        </svg>
                </li>

                <li class="d-none d-sm-inline-block d-xl-none">
                    <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#RecentChat">
                        <svg viewBox="0 0 16 16" width="18px" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M14 1a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H4.414A2 2 0 0 0 3 11.586l-2 2V2a1 1 0 0 1 1-1h12zM2 0a2 2 0 0 0-2 2v12.793a.5.5 0 0 0 .854.353l2.853-2.853A1 1 0 0 1 4.414 12H14a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2z" />
                            <path class="fill-secondary"
                                d="M3 3.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5zM3 6a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9A.5.5 0 0 1 3 6zm0 2.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5z" />
                        </svg>
                    </a>
                </li>

                <li>
                    <div class="dropdown morphing {{$scaleDirection}} user-profile mx-lg-3 mx-2">
                        <a class="nav-link dropdown-toggle rounded-circle after-none p-0" href="#" role="button"
                            data-bs-toggle="dropdown">
                            <img class="avatar img-thumbnail rounded-circle shadow"
                                src="{{ showFile($user->image==null ? '../admin/assets/img/profile_av.png':$user->image )}}"
                                alt="">
                        </a>
                        <div class="dropdown-menu border-0 rounded-4 shadow p-0">
                            <div class="card border-0 w240">
                                <div class="card-body border-bottom d-flex">
                                    <img class="avatar rounded-circle"
                                        src="{{showFile($user->image==null ? '../admin/assets/img/profile_av.png':$user->image )}}"
                                        alt="">
                                    <div class="flex-fill ms-3">
                                        <h6 class="card-title mb-0">{{$user->name}}</h6>
                                        <span class="text-muted">
                                            {{--<a href="#" class="__cf_email__"
                                                data-cfemail="a8c9c4c4c1cdcfdac9dccddae8c4ddc6c786cbc7c5">--}}
                                                {{$user->phone}}{{--</a>--}}
                                        </span>
                                    </div>
                                </div>
                                <div class="list-group m-2 mb-3">
                                    <a class="list-group-item list-group-item-action border-0"
                                        href="{{route('profile.index')}}"><i class="w30 fa fa-user"></i>ملفي الشخصي</a>
                                    {{-- @if (Auth::guard('admin')->user()->hasPermission('settings-read')) --}}
                                    <a class="list-group-item list-group-item-action border-0" href="{{route('settings.index')}}"><i
                                            class="w30 fa fa-gear"></i>{{ __('skaeeb.settings') }}</a>
                                    {{-- @endif --}}
                                    {{-- @if(Auth::guard('admin')->user()->hasPermission('users-read')) --}}
                                    <a class="list-group-item list-group-item-action border-0" href="{{route('admins.index')}}"><i
                                            class="w30 fa fa-users"></i>{{ __('auth.users') }}</a>
                                    {{-- @endif --}}
                                    {{--<a class="list-group-item list-group-item-action border-0" href="#"><i
                                            class="w30 fa fa-tag"></i>Support Ticket</a>--}}
                                </div>
                                <a href="{{route('admin.logout')}}"
                                    class="btn bg-secondary text-light text-uppercase rounded-0">تسجيل الخروج</a>
                            </div>
                        </div>
                    </div>
                </li>

                {{-- <li>
                    <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#SettingsModal"
                        title="Settings">
                        <svg viewBox="0 0 16 16" width="18px" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                            <path class="fill-secondary"
                                d="M8 4.754a3.246 3.246 0 1 0 0 6.492 3.246 3.246 0 0 0 0-6.492zM5.754 8a2.246 2.246 0 1 1 4.492 0 2.246 2.246 0 0 1-4.492 0z">
                            </path>
                            <path
                                d="M9.796 1.343c-.527-1.79-3.065-1.79-3.592 0l-.094.319a.873.873 0 0 1-1.255.52l-.292-.16c-1.64-.892-3.433.902-2.54 2.541l.159.292a.873.873 0 0 1-.52 1.255l-.319.094c-1.79.527-1.79 3.065 0 3.592l.319.094a.873.873 0 0 1 .52 1.255l-.16.292c-.892 1.64.901 3.434 2.541 2.54l.292-.159a.873.873 0 0 1 1.255.52l.094.319c.527 1.79 3.065 1.79 3.592 0l.094-.319a.873.873 0 0 1 1.255-.52l.292.16c1.64.893 3.434-.902 2.54-2.541l-.159-.292a.873.873 0 0 1 .52-1.255l.319-.094c1.79-.527 1.79-3.065 0-3.592l-.319-.094a.873.873 0 0 1-.52-1.255l.16-.292c.893-1.64-.902-3.433-2.541-2.54l-.292.159a.873.873 0 0 1-1.255-.52l-.094-.319zm-2.633.283c.246-.835 1.428-.835 1.674 0l.094.319a1.873 1.873 0 0 0 2.693 1.115l.291-.16c.764-.415 1.6.42 1.184 1.185l-.159.292a1.873 1.873 0 0 0 1.116 2.692l.318.094c.835.246.835 1.428 0 1.674l-.319.094a1.873 1.873 0 0 0-1.115 2.693l.16.291c.415.764-.42 1.6-1.185 1.184l-.291-.159a1.873 1.873 0 0 0-2.693 1.116l-.094.318c-.246.835-1.428.835-1.674 0l-.094-.319a1.873 1.873 0 0 0-2.692-1.115l-.292.16c-.764.415-1.6-.42-1.184-1.185l.159-.291A1.873 1.873 0 0 0 1.945 8.93l-.319-.094c-.835-.246-.835-1.428 0-1.674l.319-.094A1.873 1.873 0 0 0 3.06 4.377l-.16-.292c-.415-.764.42-1.6 1.185-1.184l.292.159a1.873 1.873 0 0 0 2.692-1.115l.094-.319z">
                            </path>
                        </svg>
                    </a>
                </li> --}}
            </ul>
        </nav>
    </div>
</header>
