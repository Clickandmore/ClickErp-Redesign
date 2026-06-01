@extends('admin.layout.indexs.index')

@section('page-title')
    مسميات مراحل عقود الاستقدام
@endsection

@section('content')
    @if (@AdminGuardHasPerm(\App\PermKeys::SETTINGS_VIEW))
        <div class="container-fluid">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h3 class="h4 mb-1">
                        <i class="fas fa-tags text-primary me-2"></i>
                        مسميات مراحل عقود الاستقدام
                    </h3>
                    <div class="text-muted">تظهر هذه المسميات في صفحة عقود الاستقدام، الفلاتر، وسجل مراحل العقد.</div>
                </div>
                <a href="{{ route('RecruitmentContract.index', ['status' => 'current']) }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-right me-1"></i>
                    الرجوع للعقود
                </a>
            </div>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    تأكد من المسميات المدخلة ثم حاول مرة أخرى.
                </div>
            @endif

            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">المسميات</h5>
                </div>
                <form method="POST" action="{{ route('recruitment-contract-stage-labels.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 180px;">كود المرحلة</th>
                                        <th>المسمى العربي</th>
                                        <th>المسمى الإنجليزي</th>
                                        <th style="width: 220px;">الافتراضي</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($labels as $status => $locales)
                                        <tr>
                                            <td>
                                                <code>{{ $status }}</code>
                                            </td>
                                            <td>
                                                <input type="text" name="labels[{{ $status }}][ar]"
                                                    class="form-control @error("labels.$status.ar") is-invalid @enderror"
                                                    value="{{ old("labels.$status.ar", $locales['ar'] ?? '') }}"
                                                    maxlength="100">
                                            </td>
                                            <td>
                                                <input type="text" name="labels[{{ $status }}][en]"
                                                    class="form-control @error("labels.$status.en") is-invalid @enderror"
                                                    value="{{ old("labels.$status.en", $locales['en'] ?? '') }}"
                                                    maxlength="100">
                                            </td>
                                            <td class="text-muted small">
                                                {{ $defaults[$status]['ar'] ?? '' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer d-flex flex-wrap justify-content-between gap-2">
                        @if (@AdminGuardHasPerm(\App\PermKeys::SETTINGS_CONTROL))
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>
                                حفظ المسميات
                            </button>
                            <button type="submit" name="reset_defaults" value="1" class="btn btn-outline-danger">
                                <i class="fas fa-rotate-left me-1"></i>
                                استعادة الافتراضي
                            </button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection
