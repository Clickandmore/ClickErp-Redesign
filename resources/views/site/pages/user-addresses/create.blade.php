<form class="form-wrap from-submit-global" action="{{route('site.UserAddresss.store')}}" method="post">
    @csrf
    <div class="row">
        <div class="col-lg-6 col-12 p-2">
            <div class="form-group">
                <label for="locationName">
                    <i class="fa-light fa-map-pin"></i>
                   {{helperTrans('web.address_name')}}
                </label>
                <input id="locationName" name="title" type="text" placeholder="{{helperTrans('web.house_example')}}">
            </div>
        </div>
        <div class="col-lg-6 col-12 p-2">
            <div class="form-group">
                <label for="reciever">
                    <i class="fa-light fa-user"></i>
                    {{helperTrans('web.recipient_name')}}
                </label>
                <input id="reciever" name="name" type="text" placeholder="{{helperTrans('web.write_full_name')}}">
            </div>
        </div>
        <div class="col-lg-6 col-12 p-2">
            <div class="form-group">
                <label for="phone">
                    <i class="fa-light fa-phone"></i>
                    {{helperTrans('web.phone')}}
                </label>
                <input id="phone" name="phone" type="tel" placeholder="+996 0XXXXXXXX">
            </div>
        </div>
        <div class="col-lg-6 col-12 p-2">
            <div class="form-group">
                <label for="address">
                    <i class="fa-light fa-location-dot"></i>

                    {{helperTrans('web.address')}}
                </label>
                <input id="address" name="address" type="text"
                       placeholder="{{helperTrans('web.example_city_name')}}">
            </div>
        </div>
        <div class="col-lg-12 p-2">
            <div class="form-group">
                <label for="details">
                    <i class="fa-regular fa-comment-dots"></i>
                    {{helperTrans('web.additional_details')}}
                </label>
                <textarea name="notes" id="details"
                          placeholder="{{helperTrans('web.write_additional_details')}}"></textarea>
            </div>
        </div>
        <div class="col-lg-12 p-2">
            <div class="form-group mb-0">
                <button type="submit">
                    {{helperTrans('web.save')}}
                </button>
            </div>
        </div>
    </div>
</form>
