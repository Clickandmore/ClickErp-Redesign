@extends('admin.layout.indexs.index')

@section('page-title')
    {{ __('admin.no_perm') }}
@endsection

@section('content')
    @include('components.no_permissions')
@endsection
