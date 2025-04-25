@if (@AdminGuardHasPerm(\App\PermKeys::STAT_CONTROL))

    <form class="row g-3 from-submit-global" method="post" action="{{route("statistics.update",$obj->id)}}">
        @csrf
        @method('Put')
        <label for="TextInput" class="form-label">
            {{helperTrans("ingaz.counter icon")}}
        </label>
        @include('admin.ingaz.statistics.icons')
        @foreach (config('translatable.locales') as $locale)
            <div class="col-6">
                <label for="TextInput" class="form-label">
                    {{helperTrans("ingaz.title")}}
                    ({{helperTrans('ingaz.'.$locale)}})
                </label>
                <input type="text" name="title:{{ $locale }}" value="{{$obj->translate($locale)->title}}" class="form-control" data-validation="required">
            </div>
        @endforeach
        <div class="col-6">
            <label for="TextInput" class="form-label">
                {{helperTrans("ingaz.counter_num")}}
            </label>
            <input type="number" min="1" name="counter_num" class="form-control" data-validation="required" value="{{$obj->counter_num}}">
        </div>
        <div class="col-6">
            <label for="TextInput" class="form-label">
                {{helperTrans("ingaz.counter_unit")}}
            </label>
            <input type="text" name="counter_unit" class="form-control" value="{{$obj->counter_unit}}">
        </div>
        <div class="col-12 modal-footer">
            <button class="btn btn-primary" type="submit">{{__("buttons.save")}}</button>
            <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">{{__("buttons.close")}}</button>
        </div>
    </form>

@else
    @include('components.no_permissions')
@endif
