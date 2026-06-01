@if (@AdminGuardHasPerm(\App\PermKeys::RETURN_REQUEST_EDIT) || @AdminGuardHasPerm(\App\PermKeys::RETURN_REQUEST_CONTROL))

<form class="row g-3 from-submit-global" method="post" action="{{route("return_requests.update",$obj->id)}}">
    @csrf
    @method('Put')
    @if($obj->maid && $obj->maid->available_at && now() < $obj->maid->available_at)
    <span class="text-info mb-1">يرجي العلم ان تلك العاملة متاحة من يوم {{$obj->maid->available_at}}</span>
    @endif
    <div class="col-4">
        <label for="TextInput" class="form-label">
            {{helperTrans('ingaz.order_code')}}
        </label>
        <input type="text" disabled class="form-control"
               value="{{ $obj->order_code}}">
    </div>
    <div class="col-4">
        <label for="TextInput" class="form-label">
            {{helperTrans('ingaz.order job type')}}
        </label>
        <input type="text" disabled class="form-control"
               value="{{ $obj->jobType->title ?? '-' }}">
    </div>
    <div class="col-4">
        <label for="TextInput" class="form-label">
            {{helperTrans('ingaz.client')}}
        </label>
        <input type="text" disabled class="form-control"
               value="{{ $obj->user->name ?? '-' }}">
    </div>
    <div class="col-4">
        <label for="TextInput" class="form-label">
            {{helperTrans('ingaz.status')}}
        </label>
        <input type="text" disabled class="form-control"
               value="{{getEnumValue('orders_statuses', $obj->status)}}">
    </div>
    <div class="col-4">
        <label for="TextInput" class="form-label">
            {{helperTrans('ingaz.order created at')}} :
        </label>
        <input type="text" disabled class="form-control"
               value="{{ optional($obj->created_at)->format('Y-m-d h:i A') }}">
    </div>
    <div class="col-4">
        <label for="TextInput" class="form-label">
            {{helperTrans('ingaz.phone')}} :
        </label>
        <br>
        {!!  ($obj->user) ? getPhoneWithTag($obj->user->phone_code.$obj->user->phone) :'' !!}


    </div>
    {{--    ## لو الطلب له باقة نحسب امتي تنهي - ولو الطلب نوعه يتم العمل والعاملة هناك نجيب تاريخ نهاية الطلب من ميعاد انتهاء العاملة ##--}}
    @if($obj->package)
        @if($obj->status == 'started')
            <div class="col-6">
                <label for="TextInput" class="form-label">
                    {{helperTrans('ingaz.package')}} :
                </label>
                <input type="text" disabled class="form-control"
                       value="{{$obj->package_title ." : ".
                           helperTrans('ingaz.ends at')." : ".
                           optional($obj->maid)->available_at}}">
            </div>
        @else
            <div class="col-6">
                <label for="TextInput" class="form-label">
                    {{helperTrans('ingaz.package')}} :
                </label>
                <input type="text" disabled class="form-control"
                       value="{{$obj->package_title ." : ".
                           helperTrans('ingaz.ends at')." : ".
                           now()->addDays($obj->package->days_count)->format('Y-m-d')}}">
            </div>
        @endif
    @endif
    @if($obj->address)
        <div class="col-6">
            <label for="TextInput" class="form-label">
                {{helperTrans('ingaz.order address')}} :
            </label>
            <input type="text" disabled class="form-control"
                   value="{{$obj->address->title}}">
        </div>
    @endif
    @if(!$obj->deleted_at)
        <div class="col-12">
            <label for="TextInput" class="form-label">
                {{helperTrans('ingaz.update order status')}} :
            </label>
            @php
                $isCancelledStatus = in_array($obj->status, ['refused', 'return'], true);
                $maidCanBeReactivated = $obj->maid
                    && (bool) $obj->maid->available
                    && in_array($obj->maid->status, ['available', 'completed'], true)
                    && (!$obj->maid->available_at || now()->gte($obj->maid->available_at));
                $availableStatuses = $isCancelledStatus
                    ? ($maidCanBeReactivated ? ['new', 'under_process'] : [])
                    : collect(\App\Enums\ingaz\OrderEnum::cases())
                        ->map(fn ($case) => $case->value)
                        ->reject(fn ($status) => in_array($status, [$obj->status, 'canceled_by_system'], true))
                        ->values()
                        ->all();
            @endphp

            @if($isCancelledStatus && !$maidCanBeReactivated)
                <div class="alert alert-warning mb-2">
                    لا يمكن تنشيط الطلب حالياً لأن العاملة لم تعد متاحة.
                </div>
            @endif

            @foreach($availableStatuses as $status)
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="status" value="{{ $status }}" id="status_{{ $status }}" @checked($loop->first)>
                    <label class="form-check-label" for="status_{{ $status }}">
                        {{ $isCancelledStatus && $status === 'new' ? 'تنشيط الطلب مرة أخرى' : getEnumValue('orders_statuses', $status) }}
                    </label>
                </div>
            @endforeach
        </div>

        <div class="col-12 d-none" data-refuse-reason>
            <label class="form-label" for="refuse_reason">{{ helperTrans('ingaz.reason') }}</label>
            <textarea name="refuse_reason" id="refuse_reason" rows="3" class="form-control"
                      placeholder="{{ helperTrans('ingaz.enter_reason') }}">{{ old('refuse_reason', $obj->refuse_reason) }}</textarea>
            <small class="text-muted">{{ helperTrans('ingaz.refuse_reason_optional') }}</small>
        </div>

    @endif


    <div class="col-12 modal-footer">
        @if(!$obj->deleted_at && count($availableStatuses))
        <button class="btn btn-primary" type="submit">{{__("buttons.save")}}</button>
        @endif
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">{{__("buttons.close")}}</button>
    </div>
</form>

@else
    @include('components.no_permissions')
@endif
