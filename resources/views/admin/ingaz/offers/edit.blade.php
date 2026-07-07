@if (@AdminGuardHasAnyPerm([\App\PermKeys::OFFERS_EDIT, \App\PermKeys::OFFERS_CONTROL]))
    <form class="row g-3 from-submit-global" method="post" action="{{ route('offers.update', $obj->id) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('admin.ingaz.offers._form')
    </form>
@else
    @include('components.no_permissions')
@endif
