@extends('admin.layout.indexs.index')

@section('page-title')
    ملفات المكاتب
@endsection

@section('content')
    <div class="container-fluid py-3">
        <div class="card mb-3">
            <div class="card-body d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                <div>
                    <h4 class="mb-1">ملفات المكاتب</h4>
                    <p class="text-muted mb-0">ملف المكتب يستخدم كقالب سريع لتسهيل إضافة السير الذاتية.</p>
                </div>
                <a href="{{ route('offices.index') }}" class="btn btn-outline-primary">
                    <i class="fa fa-building"></i>
                    الوكلاء
                </a>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-12 col-lg-6">
                        <label class="form-label">بحث</label>
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control"
                            placeholder="اسم المكتب، المدير، البريد">
                    </div>
                    <div class="col-12 col-lg-3">
                        <label class="form-label">حالة الملف</label>
                        <select name="file_status" class="form-select">
                            <option value="">الكل</option>
                            <option value="exists" @selected(request('file_status') === 'exists')>لديه ملف</option>
                            <option value="missing" @selected(request('file_status') === 'missing')>بدون ملف</option>
                        </select>
                    </div>
                    <div class="col-12 col-lg-3 d-flex gap-2">
                        <button class="btn btn-primary flex-fill" type="submit">تصفية</button>
                        <a href="{{ route('offices.files.index') }}" class="btn btn-outline-secondary">مسح</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>المكتب</th>
                            <th>الجنسية</th>
                            <th>ملف المكتب</th>
                            <th>السير المرتبطة</th>
                            <th class="text-end">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($offices as $office)
                            @php($officeFile = $office->file)
                            <tr>
                                <td>{{ $office->id }}</td>
                                <td>
                                    <strong>{{ $office->title }}</strong>
                                    <div class="text-muted small">{{ $office->manager_email ?: '-' }}</div>
                                </td>
                                <td>{{ $office->nationality?->title ?: '-' }}</td>
                                <td>
                                    @if ($officeFile)
                                        <span class="badge bg-success">مكتمل</span>
                                        <div class="text-muted small mt-1">
                                            {{ $officeFile->office_file_name ?: 'ملف مكتب' }}
                                            @if ($officeFile->jobTitle)
                                                - {{ $officeFile->jobTitle->title }}
                                            @endif
                                        </div>
                                    @else
                                        <span class="badge bg-warning text-dark">بدون ملف</span>
                                    @endif
                                </td>
                                <td>{{ $office->maids_count }}</td>
                                <td class="text-end">
                                    <a href="{{ route('offices.show', $office->id) }}" class="btn btn-sm btn-outline-secondary">
                                        عرض
                                    </a>
                                    @if ($officeFile)
                                        <a href="{{ route('offices-file.edit', $officeFile->id) }}" class="btn btn-sm btn-primary">
                                            تعديل الملف
                                        </a>
                                    @else
                                        <a href="{{ route('offices-file.create', ['office_id' => $office->id]) }}" class="btn btn-sm btn-success">
                                            إضافة ملف
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">لا توجد مكاتب مطابقة.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-body">
                {{ $offices->links() }}
            </div>
        </div>
    </div>
@endsection
