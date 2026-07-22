<form class="row g-3 from-submit-global" method="POST" action="{{ route('what-do.update', $obj->id) }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    {{-- 🧩 أيقونة العداد --}}

    <div class="col-6">
        <label for="TextInput" class="form-label">
            {{helperTrans("web.type")}}
        </label>
        <select name="type" class="form-control" data-validation="required">
            <option value="">{{__("ingaz.choose")}}</option>
            <option
                value="estqdam" {{$obj->type == 'estqdam' ? 'selected' : ''}}> {{ __('ingaz.recruitment') }}</option>
            <option value="egar"{{$obj->type == 'egar' ? 'selected' : ''}}> {{ __('ingaz.Labor_rental') }}</option>
            <option
                value="transfer" {{$obj->type == 'transfer' ? 'selected' : ''}}> {{ __('ingaz.transfer_services') }}</option>
        </select>
    </div>
    <div class="col-6">
        <label class="form-label fw-bold">
            {{ helperTrans('ingaz.icon') }}
        </label>
        @include('admin.ingaz.what_do.icons')
    </div>
    <div class="col-12">
        <label class="form-label">
            {{ helperTrans('ingaz.image') }} ({{ helperTrans('ingaz.optional') }})
        </label>
        <input type="file" name="image" class="dropify"
               accept="image/jpeg,image/png,image/webp"
               data-default-file="{{ showFile($obj->image) }}">
    </div>
    @foreach (config('translatable.locales') as $locale)
        <div class="col-md-6">
            <label class="form-label">
                {{ helperTrans('ingaz.title') }} ({{ helperTrans('ingaz.' . $locale) }})
            </label>
            <input
                type="text"
                name="title:{{ $locale }}"
                value="{{ $obj->translate($locale)->title }}"
                class="form-control"
                data-validation="required"
                placeholder="{{ helperTrans('ingaz.title_placeholder') ?? '' }}"
            >
        </div>
    @endforeach
    @foreach (config('translatable.locales') as $locale)
        <div class="col-md-6">
            <label class="form-label">
                {{ helperTrans('ingaz.description') }} ({{ helperTrans('ingaz.' . $locale) }})
            </label>
            <textarea
                name="description:{{ $locale }}"
                class="form-control"
                rows="4"
                data-validation="required"
                placeholder="{{ helperTrans('ingaz.description_placeholder') ?? '' }}"
            >{{ $obj->translate($locale)->description }}</textarea>
        </div>
    @endforeach
    <div class="col-12 mt-4">
        <label class="form-label fw-bold">{{ helperTrans('ingaz.items') }}</label>
        <table class="table table-bordered align-middle card-table my-2">
            <thead class="table-light">
            <tr>
                <th style="width: 50px;">#</th>
                @foreach (config('translatable.locales') as $locale)
                    <th>{{ __("ingaz.title") }} ({{ helperTrans('ingaz.' . $locale) }})</th>
                @endforeach
                <th class="text-center" style="width: 120px;">{{ __("ingaz.actions") }}</th>
            </tr>
            </thead>
            <tbody
                id="json_array"
                class="content"
                data-mfield-options='{"section": ".y-group","btnAdd":"#btnAdd-2","btnRemove":".btnRemove"}'
            >
            @foreach($obj->items as $key => $item)
                <tr class="y-group">
                    <td class="text-center">{{ $loop->iteration }}</td>
                    @foreach (config('translatable.locales') as $locale)
                        <td>
                            <input type="text" name="titles[{{ $key }}][{{ $locale }}][title]" data-validation="required"
                                   value="{{ $item->translate($locale)->title }}" class="form-control index-plus">
                        </td>
                    @endforeach
                    <td class="text-center">
                        <i class="fa fa-plus-circle text-success me-2 cursor-pointer" id="btnAdd-2"
                           title="{{ __('Add') }}"></i>
                        <i class="fa fa-trash-o text-danger btnRemove cursor-pointer" title="{{ __('Remove') }}"></i>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    {{-- 🎯 الأزرار --}}
    <div class="col-12 modal-footer">
        <button type="submit" class="btn btn-primary">
            <i class="fa fa-save me-1"></i> {{ __("buttons.save") }}
        </button>
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
            <i class="fa fa-times me-1"></i> {{ __("buttons.close") }}
        </button>
    </div>
</form>
