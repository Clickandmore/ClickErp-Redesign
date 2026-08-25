@extends('click.layout.indexs.index')

@section('page-title', $bladeTitle)

@section('page-links')
    <li class="breadcrumb-item">
        <a href="{{ $indexRoute }}">سندات القبض</a>
    </li>
    <li class="breadcrumb-item active" aria-current="page">{{ $bladeTitle }}</li>
@endsection

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-body p-3 p-lg-4">
            @include('finance::finance.bond.qabd.show')
        </div>
    </div>
@endsection
