
<div class="col-lg-4 col-12 mb-lg-0 mb-4">
    <div class="profile_side_bar">
        <div class="info">
            <h4>{{getGuardedUser('name')}}</h4>
            <span>{{getGuardedUser('phone_code').getGuardedUser('phone')}}</span>
        </div>
        <ul class="links">
            <li class="{{ (Route::currentRouteName() == 'web.profile') ? 'active' : '' }}">
                <a href="{{route('web.profile')}}"><i class="fa-light fa-user-pen"></i> {{ helperTrans('web.edit_profile') }}</a>
            </li>
            <li class="{{ (Route::currentRouteName() == 'web.notifications.index') ? 'active' : '' }}">
                <a href="{{route('web.notifications.index')}}"><i class="fa-light fa-bell"></i></i>{{ helperTrans('web.notifications') }}</a>
            </li>
            <li class="{{ (Route::currentRouteName() == 'web.orders.index') ? 'active' : '' }}">
                <a href="{{route('web.orders.index')}}"><i class="fa-regular fa-list-check"></i> {{ helperTrans('web.my_orders') }}</a>
            </li>
            <li class="{{ (Route::currentRouteName() == 'web.contracts.index') ? 'active' : '' }}">
                <a href="{{route('web.contracts.index')}}"><i class="fa-regular fa-list-check"></i> {{ helperTrans('web.my_contracts') }}</a>
            </li>
            <li class="{{ (Route::currentRouteName() == 'web.complaints.index') ? 'active' : '' }}">
                <a href="{{route('web.complaints.index')}}"><i class="fa-light fa-triangle-exclamation"></i> {{ helperTrans('web.my_complaints') }}</a>
            </li>
           {{-- <li class="">
                <button class="complain" data-bs-toggle="modal" data-bs-target="#complain">
                    <i class="fa-light fa-triangle-exclamation"></i>{{helperTrans('web.complaint_or_inquiry')}}
                </button>
            </li> --}}

            <li>
                <a href="{{route('web.logout')}}">
                    <i class="fa-sharp fa-regular fa-arrow-right-from-bracket"></i>
                    {{ helperTrans('web.logout') }}
                </a>
            </li>



            <li>
                <a href=" {{route('web.deleteAccount')}}"><i class="fa-regular fa-user-xmark"></i>  {{ helperTrans('web.deleteAccount') }}</a>
            </li>
        </ul>
    </div>
</div>

<div class="modal fade add_location" id="complain" tabindex="-1" aria-labelledby="complainLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @include('web.ingaz.complaints._form')
            </div>
        </div>
    </div>
</div>

