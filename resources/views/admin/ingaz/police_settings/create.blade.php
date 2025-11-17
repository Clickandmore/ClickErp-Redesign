@if (@AdminGuardHasPerm(\App\PermKeys::ABOUT_US_CONTROL))
    <form class="row g-3 from-submit-global" method="post" action="{{route("about-us.store")}}">
        @csrf

        @foreach (config('translatable.locales') as $locale)
            <div class="col-6">
                <label for="TextInput" class="form-label">
                    {{helperTrans("ingaz.title")}}
                    ({{helperTrans('ingaz.'.$locale)}})
                </label>
                <input type="text" name="title:{{ $locale }}" class="form-control" data-validation="required">
            </div>
        @endforeach
        @foreach (config('translatable.locales') as $locale)
            <div class="col-12">
                <label for="TextInput" class="form-label">
                    {{helperTrans("ingaz.description")}}
                    ({{helperTrans('ingaz.'.$locale)}})
                </label>
                <textarea class="mytextarea" rows="10" data-validation="required"
                        name="description:{{ $locale }}"></textarea>
    {{--            <input type="text" name="description:{{ $locale }}" class="form-control" data-validation="required">--}}
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

