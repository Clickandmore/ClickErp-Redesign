@extends('click.layout.indexs.index')
@section('style')
@endsection
@section('page-title')
    {{ $bladeTitle }}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{ $bladeTitle }}</li>
@endsection
@section('content')
    @include('finance::finance.constraint.edit')
@endsection
@section('js')
    @include('finance::finance.constraint.partials.edit-scripts')
@endsection
