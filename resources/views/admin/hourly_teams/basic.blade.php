@extends('admin.layout.indexs.index')

@section('page-title', $team->exists ? 'تعديل الفريق — '.$team->name : 'إضافة فريق جديد')

@section('style')
    @include('admin.hourly_teams._style')
@endsection

@section('content')
    <div class="container-fluid ht-page" dir="rtl">
        @if (session('success'))
            <div class="alert alert-success border-0 rounded-3"><i class="fa fa-circle-check ms-1"></i> {{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger border-0 rounded-3"><i class="fa fa-circle-xmark ms-1"></i> {{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ $team->exists ? route('admin.hourly-teams.update', $team) : route('admin.hourly-teams.store') }}" class="ht-card mx-auto" style="max-width: 760px">
            @csrf
            @if ($team->exists) @method('PUT') @endif
            <div class="ht-head">
                <div class="ht-head-icon"><i class="fa {{ $team->exists ? 'fa-pen-to-square' : 'fa-people-group' }}"></i></div>
                <div>
                    <h5>{{ $team->exists ? $team->name : 'إضافة فريق جديد' }}</h5>
                    <p>{{ $team->exists ? 'تعديل البيانات الأساسية للفريق' : 'الخطوة الأولى: البيانات الأساسية، ثم تُكمل الإعداد من صفحة إدارة الفريق' }}</p>
                </div>
                <div class="ht-actions">
                    @if ($team->exists)
                        <a class="btn btn-outline-primary rounded-3" href="{{ route('admin.hourly-teams.manage', $team) }}"><i class="fa fa-sliders ms-1"></i> إدارة الفريق</a>
                    @endif
                    <a class="btn btn-outline-secondary rounded-3" href="{{ route('admin.hourly-teams.index') }}"><i class="fa fa-arrow-right ms-1"></i> قائمة الفرق</a>
                </div>
            </div>
            <div class="ht-body">
                @include('admin.hourly_teams._team-fields')
                @unless ($team->exists)
                    @include('admin.hourly_teams._team-next-steps')
                @endunless
            </div>
            <div class="ht-foot">
                <button class="btn btn-primary rounded-3 px-4" type="submit"><i class="fa fa-floppy-disk ms-1"></i> {{ $team->exists ? 'حفظ التعديلات' : 'إنشاء الفريق والمتابعة' }}</button>
                <a class="btn btn-outline-secondary rounded-3" href="{{ route('admin.hourly-teams.index') }}">إلغاء</a>
            </div>
        </form>
    </div>
@endsection
