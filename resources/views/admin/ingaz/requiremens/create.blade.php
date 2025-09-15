     <form class="row g-3 from-submit-global" method="post" action="{{route("requiremens.store")}}">
        @csrf
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
                <input type="text" name="title:{{ $locale }}" class="form-control" data-validation="required">
            </div>
        
@endforeach
 
    <div class="col-12">
        <label for="requirement_type" class="form-label">
            {{ helperTrans("ingaz.requirement_type") }}
        </label>
        <select name="type" id="requirement_type" class="form-control" data-validation="required">
            @foreach ($enums as $enum)
                <option value="{{ $enum->value }}">
                    {{ helperTrans('ingaz.' . $enum->name) }}
                </option>
            @endforeach
        </select>
    </div>

        <div class="col-12 modal-footer">
            <button class="btn btn-primary" type="submit">{{__("buttons.save")}}</button>
            <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">{{__("buttons.close")}}</button>
        </div>
    </form>

 

