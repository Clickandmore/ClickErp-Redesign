<div class="container">
    <div class="row">
        @if(isset($settings))
            <div class="col-lg-4 col-md-6 mb-4">
                <img src="{{showFile($settings->logo_footer)}}" alt="Logo" class="footer-logo"/>
                <p class="footer-desc">
                    {{showFile($settings->footer_text)}}
                </p>
                <ul class="footer-social">
                    <li><a href="{{$settings->facebook}}"><i class="fab fa-facebook-f"></i></a></li>
                    <li><a href="{{$settings->twitter}}"><i class="fab fa-twitter"></i></a></li>
                    <li>
                        <a href="https://wa.me/{{ ltrim($settings->whatsapp, '+') }}" target="_blank" rel="noopener">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                    </li>

                    <li>
                        <a href="mailto:{{ $settings->email }}">
                            <i class="fas fa-envelope"></i>
                        </a>
                    </li>

                </ul>
            </div>
        @else
            <!-- الشعار والوصف -->
            <div class="col-lg-4 col-md-6 mb-4">
                <img src="{{asset('site')}}/assets/images/logo.svg" alt="Logo" class="footer-logo"/>
                <p class="footer-desc">
                    مكتب ترف يقدم خدمات استقدام العمالة المنزلية باحترافية عالية وجودة مميزة.
                </p>
                <ul class="footer-social">
                    <li><a href="#"><i class="fab fa-facebook-f"></i></a></li>
                    <li><a href="#"><i class="fab fa-twitter"></i></a></li>
                    <li><a href="#"><i class="fab fa-whatsapp"></i></a></li>
                    <li><a href="#"><i class="fas fa-envelope"></i></a></li>
                </ul>
            </div>
        @endif
        <!-- خدماتنا -->
        <div class="col-lg-2 col-md-6 mb-4">
            <h6 class="footer-title">{{__("ingaz.our_services")}}</h6>
            <ul class="footer-links">
                <li><a href="{{route('site.all-workers.index')}}?type=estqdam">{{__("ingaz.recruitment_request")}}</a></li>
                <li><a href="{{route('site.all-workers.index')}}?type=egar">{{__("ingaz.labor_rental_request")}}</a>
                </li>
                <li><a href="{{route('site.daily-lebor-demand.index')}}">{{__("ingaz.request_professional_labor")}}</a>
                </li>
                <li><a href="{{route('site.renew-contract.index')}}">{{__("ingaz.renewing_employment_contract")}}</a>
                </li>
                <li><a href="{{route('site.support.index')}}">{{__("ingaz.faq")}}</a></li>
            </ul>
        </div>

        <!-- تواصل معنا -->
        <div class="col-lg-3 col-md-6 mb-4">
            <h6 class="footer-title">{{__("ingaz.contact_us")}}</h6>
            <ul class="footer-contact">
                @if(isset($settings))
                    <li><i class="fa-solid fa-map-marker-alt"></i> {{$settings->location}}</li>
                    <li><i class="fas fa-envelope"></i> {{$settings->email}}</li>
                    <li><i class="fa-solid fa-phone"></i> {{$settings->phone}}</li>
                    <li><i class="fa-solid fa-headset"></i> {{$settings->other_phone}}</li>
                @else
                    <li><i class="fa-solid fa-map-marker-alt"></i> الدمام 32413</li>
                    <li><i class="fas fa-envelope"></i> info@platinumeastern.com</li>
                    <li><i class="fa-solid fa-phone"></i> 920003174</li>
                    <li><i class="fa-solid fa-headset"></i> 0552333182</li>
                @endif
            </ul>
        </div>

        <!-- سياسات الاستقدام -->
        <div class="col-lg-3 col-md-6 mb-4">
            <h6 class="footer-title">{{__("ingaz.recruitment_policies")}}</h6>
            <ul class="footer-links">
                <li><a href="{{route('site.visa.index')}}">{{__("ingaz.visa_issuance")}}</a></li>
                <li><a href="{{route('site.select-worker.index')}}l"> {{__("ingaz.selection_of_workers")}}</a></li>
                <li><a href="{{route('site.all-workers.index')}}">  {{__("ingaz.recruitment_contract")}}</a></li>
                <li><a href="{{route('site.arrival-worker.index')}}">   {{__("ingaz.arrival_worker")}}</a></li>
            </ul>
        </div>

    </div>
</div>
<div class="footer-bottom">
    <p>
        {{__("ingaz.all_rights_reserved")}} © -
        <script>document.write(new Date().getFullYear());</script>
        <a target="_blank" href="http://nami-tec.com">NAMI</a>
    </p>
</div>
