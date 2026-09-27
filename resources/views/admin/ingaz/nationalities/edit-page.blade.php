@extends('admin.layout.indexs.index')

@section('page-title')
    تعديل الجنسية: {{ $obj->display_name }}
@endsection

@section('style')
    <style>
        .nationality-edit-page { max-width: 1180px; margin-inline: auto; }
        .nationality-edit-page .card { border-radius: 18px; }
        .nationality-edit-form .dropify-wrapper { height: 145px; }
        .nationality-edit-form .form-label { margin-bottom: .4rem; font-weight: 600; color: #475569; }
        .nationality-form-section {
            grid-column: 1 / -1;
            display: flex;
            align-items: center;
            gap: 9px;
            margin-top: 8px;
            color: #1e3a8a;
            font-weight: 700;
        }
        .nationality-form-section::after { content: ''; height: 1px; background: #e5eaf2; flex: 1; }
        .nationality-edit-page .modal-footer {
            position: sticky;
            bottom: 0;
            z-index: 5;
            margin: 1rem -1.5rem -1.5rem;
            padding: 1rem 1.5rem;
            background: rgba(255, 255, 255, .96);
            border-top: 1px solid #e9ecef;
            backdrop-filter: blur(8px);
        }
    </style>
@endsection

@section('content')
    <div class="page-body">
        <div class="container-fluid nationality-edit-page">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                <div>
                    <h3 class="h3 mb-1 text-gray-800"><i class="fas fa-flag text-primary me-2"></i>تعديل الجنسية</h3>
                    <p class="text-muted mb-0">{{ $obj->display_name }}</p>
                </div>
                <a href="{{ route('nationalities.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-right me-1"></i>{{ helperTrans('ingaz.back') }}
                </a>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    @include('admin.ingaz.nationalities.edit')
                </div>
            </div>
        </div>
    </div>
@endsection
