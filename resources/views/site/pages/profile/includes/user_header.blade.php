<div class=" userHeader">
    <div class="userInfo">
        @if(\Illuminate\Support\Facades\Auth::guard('web')->check())
            <div class="d-flex flex-wrap align-items-center">
                <img src="{{asseasset('site')}}/ts/images/avatar.webp" alt="" class="me-3">
                <div class="userName">
                    <h3>{{Auth::guard('web')->user()->name}}</h3>
                    <p> {{Auth::guard('web')->user()->phone}} </p>
                </div>
            </div>
        @endif
        <div class="control">
            <a href="{{route('site.logout.index')}}" data-bs-toggle="tooltip" title=" تسجيل الخروج "><i
                    class="fas fa-power-off"></i></a>
        </div>
    </div>
</div>
