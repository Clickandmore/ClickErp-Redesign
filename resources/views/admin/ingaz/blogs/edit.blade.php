@if (@AdminGuardHasAnyPerm([\App\PermKeys::BLOGS_EDIT, \App\PermKeys::BLOGS_CONTROL]))
    <form class="row g-3 from-submit-global" method="post" action="{{ route('blogs.update', $obj->id) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('admin.ingaz.blogs._form')
    </form>
@else
    @include('components.no_permissions')
@endif
