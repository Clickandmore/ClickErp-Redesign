@extends('admin.layout.indexs.index')

@section('page-title', 'اللغات')

@section('content')
    <div class="page-body">
        <div class="container-fluid">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                <div>
                    <h3 class="h4 mb-2"><i class="fas fa-language text-primary me-2"></i>اللغات</h3>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge bg-light text-dark border">الإجمالي: {{ $stats['total'] }}</span>
                        <span class="badge bg-success-subtle text-success border">الظاهرة: {{ $stats['active'] }}</span>
                        <span class="badge bg-secondary-subtle text-secondary border">المخفية: {{ $stats['hidden'] }}</span>
                    </div>
                </div>
                @if (@AdminGuardHasPerm(\App\PermKeys::MAID_LANGUAGES_CREATE))
                    <a href="{{ route('languages.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>إضافة لغة
                    </a>
                @endif
            </div>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="alert alert-info">
                رمز التوافق هو القيمة المحفوظة في السير والمرسلة عبر الـAPI. لا يتغير الرمز عند تعديل العناوين.
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>العنوان بالعربية</th>
                                    <th>العنوان بالإنجليزية</th>
                                    <th>رمز التوافق</th>
                                    <th>الحالة</th>
                                    <th class="text-end">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($languages as $language)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $language->title_ar }}</td>
                                        <td dir="ltr">{{ $language->title_en }}</td>
                                        <td><code>{{ $language->code }}</code></td>
                                        <td>
                                            <span class="badge {{ $language->is_active ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $language->is_active ? 'ظاهرة' : 'مخفية' }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-wrap justify-content-end gap-2">
                                                @if (@AdminGuardHasPerm(\App\PermKeys::MAID_LANGUAGES_TOGGLE_VISIBILITY))
                                                    <form method="post" action="{{ route('languages.toggle-visibility', $language) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button class="btn btn-sm {{ $language->is_active ? 'btn-outline-secondary' : 'btn-outline-success' }}" type="submit">
                                                            <i class="fas {{ $language->is_active ? 'fa-eye-slash' : 'fa-eye' }} me-1"></i>
                                                            {{ $language->is_active ? 'إخفاء' : 'إظهار' }}
                                                        </button>
                                                    </form>
                                                @endif
                                                @if (@AdminGuardHasPerm(\App\PermKeys::MAID_LANGUAGES_EDIT))
                                                    <a href="{{ route('languages.edit', $language) }}" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-edit me-1"></i>تعديل
                                                    </a>
                                                @endif
                                                @if (@AdminGuardHasPerm(\App\PermKeys::MAID_LANGUAGES_DELETE))
                                                    <form method="post" action="{{ route('languages.destroy', $language) }}"
                                                        onsubmit="return confirm('هل تريد حذف اللغة؟ ستبقى قيمتها القديمة محفوظة في السير والـAPI.');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="btn btn-sm btn-outline-danger" type="submit">
                                                            <i class="fas fa-trash me-1"></i>حذف
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted py-5">لا توجد لغات.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
