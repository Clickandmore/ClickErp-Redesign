<form class="row g-3 from-submit-global" method="post" action="{{ route('why-us.store') }}">
    @csrf

    @include('admin.ingaz.why_us._form')

    <div class="col-12 modal-footer">
        <button class="btn btn-primary" type="submit">{{ __('buttons.save') }}</button>
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">{{ __('buttons.close') }}</button>
    </div>
</form>
