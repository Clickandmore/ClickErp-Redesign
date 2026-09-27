@if (@AdminGuardHasPerm(\App\PermKeys::NATIONALITIES_CONTROL))
    <form class="row g-3 from-submit-global nationality-edit-form" method="post"
          action="{{ route('nationalities.update', $obj->id) }}"
          @unless(request()->ajax()) data-success-url="{{ route('nationalities.index') }}" @endunless>
        @csrf
        @method('PUT')
        @include('admin.ingaz.nationalities._form-fields', ['obj' => $obj])

        <div class="col-12 modal-footer nationality-form-actions">
            <button class="btn btn-primary" type="submit"><i class="fas fa-save me-1"></i>{{ __('buttons.save') }}</button>
            @if(request()->ajax())
                <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">{{ __('buttons.close') }}</button>
            @else
                <a class="btn btn-outline-secondary" href="{{ route('nationalities.index') }}">{{ helperTrans('ingaz.back') }}</a>
            @endif
        </div>
    </form>
@else
    @include('components.no_permissions')
@endif
