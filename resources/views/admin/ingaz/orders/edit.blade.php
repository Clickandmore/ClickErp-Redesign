@if (@AdminGuardHasPerm(\App\PermKeys::ORDERS_CONTROL))

    <form class="row g-3 from-submit-global" method="post" action="{{route("orders.update", $obj->id)}}">
        @csrf
        @method('Put')
        @if(now() < $obj->maid->available_at)
            <span class="text-info mb-1">يرجي العلم ان تلك العاملة متاحة من يوم {{$obj->maid->available_at}}</span>
        @endif
        <div class="col-4">
            <label for="TextInput" class="form-label">
                {{helperTrans('ingaz.order_code')}}
            </label>
            <input type="text" disabled class="form-control" value="{{ $obj->order_code}}">
        </div>
        <div class="col-4">
            <label for="TextInput" class="form-label">
                {{helperTrans('ingaz.order job type')}}
            </label>
            <input type="text" disabled class="form-control" value="{{ $obj->jobType->title}}">
        </div>
        <div class="col-4">
            <label for="TextInput" class="form-label">
                {{helperTrans('ingaz.client')}}
            </label>
            <input type="text" disabled class="form-control" value="{{ $obj->user->name}}">
        </div>
        <div class="col-4">
            <label for="TextInput" class="form-label">
                {{helperTrans('ingaz.status')}}
            </label>
            <input type="text" disabled class="form-control" value="{{getEnumValue('orders_statuses', $obj->status)}}">
        </div>
        <div class="col-4">
            <label for="TextInput" class="form-label">
                {{helperTrans('ingaz.order created at')}} :
            </label>
            <input type="text" disabled class="form-control" value="{{$obj->created_at->format('Y-m-d h:i A')}}">
        </div>
        <div class="col-4">
            <label for="TextInput" class="form-label">
                {{helperTrans('ingaz.phone')}} :
            </label>
            <br>
            {!!  ($obj->user) ? getPhoneWithTag($obj->user->phone_code . $obj->user->phone) : '' !!}


        </div>
        {{-- ## لو الطلب له باقة نحسب امتي تنهي - ولو الطلب نوعه يتم العمل والعاملة هناك نجيب تاريخ نهاية الطلب من ميعاد
        انتهاء العاملة ##--}}
        @if($obj->package)
            @if($obj->status == 'started')
                <div class="col-6">
                    <label for="TextInput" class="form-label">
                        {{helperTrans('ingaz.package')}} :
                    </label>
                    <input type="text" disabled class="form-control" value="{{$obj->package_title . " : " .
                            helperTrans('ingaz.ends at') . " : " .
                            $obj->maid->available_at}}">
                </div>
            @else
                <div class="col-6">
                    <label for="TextInput" class="form-label">
                        {{helperTrans('ingaz.package')}} :
                    </label>
                    <input type="text" disabled class="form-control" value="{{$obj->package_title . " : " .
                            helperTrans('ingaz.ends at') . " : " .
                            now()->addDays($obj->package->days_count)->format('Y-m-d')}}">
                </div>
            @endif
        @endif
        @if($obj->address)
            <div class="col-6">
                <label for="TextInput" class="form-label">
                    {{helperTrans('ingaz.order address')}} :
                </label>
                <input type="text" disabled class="form-control" value="{{$obj->address->title}}">
            </div>
        @endif
        {{-- @if($obj->status != 'refused' && $obj->status != 'done')--}}
        {{-- <div class="col-12">--}}
            {{-- <label for="TextInput" class="form-label">--}}
                {{-- {{helperTrans('ingaz.update order status')}} :--}}
                {{-- </label>--}}
            {{-- @if($obj->status == 'new')--}}
            {{-- <div class="form-check">--}}
                {{-- <input class="form-check-input" type="radio" name="status" value="paid">--}}
                {{-- <label class="form-check-label" for="flexRadioDefault2">{{ getEnumValue('orders_statuses', 'paid')
                    }}</label>--}}
                {{-- </div>--}}
            {{-- @endif--}}

            {{-- @if(in_array($obj->status, ['paid', 'new']))--}}
            {{-- <div class="form-check">--}}
                {{-- <input class="form-check-input" type="radio" name="status" value="completed">--}}
                {{-- <label class="form-check-label" for="flexRadioDefault2">{{ getEnumValue('orders_statuses', 'completed')
                    }}</label>--}}
                {{-- </div>--}}
            {{-- @endif--}}

            {{-- @if(!in_array($obj->status, ['new', 'started']))--}}
            {{-- <div class="form-check">--}}
                {{-- <input class="form-check-input" type="radio" name="status" --}} {{-- {{(now() <
                    $obj->maid->available_at) ? 'disabled' : ''}} value="started">--}}
                {{-- <label class="form-check-label" for="flexRadioDefault2">{{ getEnumValue('orders_statuses', 'started')
                    }}</label>--}}
                {{-- @if(now() < $obj->maid->available_at)--}}
                    {{-- <span class="text-danger"> لا يمكن البدء الان لأن العاملة متاحة من يوم {{ $obj->maid->available_at
                        }} </span>--}}
                    {{-- @endif--}}
                    {{-- </div>--}}
            {{-- @endif--}}

            {{-- @if($obj->status != 'started')--}}
            {{-- <div class="form-check">--}}
                {{-- <input class="form-check-input" type="radio" name="status" value="refused">--}}
                {{-- <label class="form-check-label" for="flexRadioDefault2">{{ getEnumValue('orders_statuses', 'refused')
                    }}</label>--}}
                {{-- </div>--}}
            {{-- @endif--}}

            {{-- @if($obj->status != 'done')--}}
            {{-- <div class="form-check">--}}
                {{-- <input class="form-check-input" type="radio" name="status" value="done">--}}
                {{-- <label class="form-check-label" for="flexRadioDefault2">{{ getEnumValue('orders_statuses', 'done')
                    }}</label>--}}
                {{-- </div>--}}
            {{-- @endif--}}
            {{-- </div>--}}

        {{-- @endif--}}
        <div class="row pt-2">
            @if($obj->status == 'new')
                <div class="col-4 p-1">
                    <input class="form-check-input" type="radio" name="status" value="under_process">
                    <label class="form-check-label">{{ getEnumValue('orders_statuses', 'under_process') }}</label>
                </div>
                <div class="col-4 p-1">
                    <input class="form-check-input" type="radio" name="status" value="refused">
                    <label class="form-check-label">{{ getEnumValue('orders_statuses', 'refused') }}</label>
                </div>
            @elseif($obj->status == 'under_process')
                <div class="col-4 p-1">
                    <input class="form-check-input" type="radio" name="status" value="contracted">
                    <label class="form-check-label">{{ getEnumValue('orders_statuses', 'contracted') }}</label>
                </div>
                <div class="col-4 p-1">
                    <input class="form-check-input" type="radio" name="status" value="contracted_without_contract">
                    <label class="form-check-label">{{ getEnumValue('orders_statuses', 'contracted_without_contract') }}</label>
                </div>
                <div class="col-4 p-1">
                    <input class="form-check-input" type="radio" name="status" value="refused">
                    <label class="form-check-label">{{ getEnumValue('orders_statuses', 'refused') }}</label>
                </div>
            @else
                @foreach(\App\Enums\ingaz\OrderEnum::cases() as $value)
                    <div class="col-4 p-1">
                        <input class="form-check-input" type="radio" name="status" value="{{ $value->value }}">
                        <label class="form-check-label">{{ getEnumValue('orders_statuses', $value->value) }}</label>
                    </div>
                @endforeach
            @endif
        </div>

        <div class="col-12 modal-footer">
            @if($obj->status != 'refused' && $obj->status != 'done')
                <button class="btn btn-primary" type="submit">{{__("buttons.save")}}</button>
            @endif
            @if($obj->status == 'under_process')
                <a class="btn btn-danger addNewContract" href="#" data-id="{{ $obj->id }}">{{__("admin.add_new_contract")}}</a>
            @endif
            <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">{{__("buttons.close")}}</button>
        </div>
    </form>

@else
    @include('components.no_permissions')
@endif
