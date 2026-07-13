@extends('admin.layout.indexs.index')

@section('page-title')
    {{ __('buttons.edit') }} {{ $obj->name }}
@endsection

@section('content')
    <div class="container-fluid py-3">
        <div class="card">
            <div class="card-header bg-white">
                <h4 class="mb-0">{{ __('buttons.edit') }} {{ $obj->name }}</h4>
            </div>
            <div class="card-body">
                @include('admin.auth.admin.edit', ['standalone' => true])
            </div>
        </div>
    </div>
@endsection
