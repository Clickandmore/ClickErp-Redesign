@if (@AdminGuardHasAnyPerm([\App\PermKeys::OFFERS_CREATE, \App\PermKeys::OFFERS_CONTROL]))
    <form class="row g-3 from-submit-global" method="post" action="{{ route('offers.store') }}" enctype="multipart/form-data">
        @csrf
        @include('admin.ingaz.offers._form')
    </form>
@else
    @include('components.no_permissions')
@endif
