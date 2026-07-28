@extends('admin.layout.indexs.index')

@section('page-title', __('find_us.edit'))

@section('style')
    @include('admin.ingaz.find_us.partials.styles')
@endsection

@section('content')
    <div class="mb-4">
        <h4 class="mb-1">{{ __('find_us.edit') }}</h4>
        <p class="text-muted mb-0">{{ $location->branch_name }}</p>
    </div>
    <form method="POST" action="{{ route('find-us.update', $location) }}">
        @include('admin.ingaz.find_us._form')
    </form>
@endsection

@section('js')
    @stack('scripts')
@endsection
