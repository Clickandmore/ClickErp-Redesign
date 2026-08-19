@if (@AdminGuardHasAnyPerm([\App\PermKeys::BLOGS_CREATE, \App\PermKeys::BLOGS_CONTROL]))
    <form class="row g-3 from-submit-global" method="post" action="{{ route('blogs.store') }}" enctype="multipart/form-data">
        @csrf
        @include('admin.ingaz.blogs._form')
    </form>
@else
    @include('components.no_permissions')
@endif
