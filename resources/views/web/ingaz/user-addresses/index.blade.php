@foreach($addresses as $address)
    <div class="col-lg-6 col-12 p-2" >
        <label for="l1{{$address->id}}"  onclick="render_data('address',{{$address->id}})">
            <div class="location" id="address-{{$address->id}}">
                <h6>{{$address->title}}</h6>
                <ul>
                    <li>
                        <i class="fa-light fa-user"></i>{{$address->name}}
                    </li>
                    <li>
                        <i class="fa-light fa-phone"></i> {{$address->phone}}
                    </li>
                    <li>
                        <i class="fa-light fa-location-dot"></i>{{$address->address}}
                    </li>
                </ul>

            </div>
            <input  type="radio" name="address_id" id="l1{{$address->id}}">
        </label>
    </div>
@endforeach
