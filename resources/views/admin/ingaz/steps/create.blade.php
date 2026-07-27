@if (@AdminGuardHasPerm(\App\PermKeys::STEPS_CONTROL))

    <form class="row g-3 from-submit-global" method="post" action="{{route("steps.store")}}">
        @csrf
        <label for="TextInput" class="form-label">
            {{helperTrans("ingaz.icon")}}
        </label>
        @include('admin.ingaz.steps.icons')
        <div class="col-12">
            <label class="form-label">{{ __('steps.sort_order') }}</label>
            <input type="number" name="sort_order" class="form-control" min="1" max="9999"
                value="{{ old('sort_order', $nextSortOrder) }}" data-validation="required">
        </div>
        @foreach (config('translatable.locales') as $locale)
            <div class="col-12">
                <label for="TextInput" class="form-label">
                    {{helperTrans("ingaz.title")}}
                    ({{helperTrans('ingaz.'.$locale)}})
                </label>
                <input type="text" name="title:{{ $locale }}" class="form-control" data-validation="required">
            </div>
        @endforeach
        @foreach (config('translatable.locales') as $locale)
            <div class="col-12">
                <label class="form-label">
                    {{ helperTrans('ingaz.description') }}
                    ({{ helperTrans('ingaz.' . $locale) }})
                </label>
                <textarea name="description:{{ $locale }}" class="form-control" rows="3"
                    maxlength="2000">{{ old('description:' . $locale) }}</textarea>
            </div>
        @endforeach

        <div class="col-12 modal-footer">
            <button class="btn btn-primary" type="submit">{{__("buttons.save")}}</button>
            <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">{{__("buttons.close")}}</button>
        </div>
    </form>

@else
    @include('components.no_permissions')
@endif

