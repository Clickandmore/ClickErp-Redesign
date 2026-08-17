@extends('admin.layout.indexs.index')

@section('page-title')
    {{ helperTrans('ingaz.add_nationality') }}
@endsection

@section('content')
    <div class="page-body">
        <div class="container-fluid">
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <div>
                    <h3 class="h3 mb-1 text-gray-800">
                        <i class="fas fa-plus text-primary me-2"></i>{{ helperTrans('ingaz.add_nationality') }}
                    </h3>
                </div>
                <a href="{{ route('nationalities.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-right me-1"></i>{{ helperTrans('ingaz.back') }}
                </a>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body">
                    @include('admin.ingaz.nationalities.create')
                </div>
            </div>
        </div>
    </div>
@endsection
