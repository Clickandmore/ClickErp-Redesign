@extends('admin.layout.indexs.index')

@section('page-title', 'مراحل التفويض')

@section('content')
    @if (@AdminGuardHasPerm(\App\PermKeys::SETTINGS_VIEW))
        <div class="container-fluid">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h3 class="h4 mb-1"><i class="fa fa-list-ol text-primary me-2"></i>مراحل التفويض</h3>
                    <div class="text-muted">رتّب مراحل سير التفويض، وأضف مراحل بين «تحت المعالجة» و«تم تنفيذ التفويض».</div>
                </div>
                <a href="{{ route('ingaz-delegations.index') }}" class="btn btn-outline-secondary">
                    <i class="fa fa-arrow-right me-1"></i>الرجوع للتفاويض
                </a>
            </div>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <div class="card shadow-sm border-0">
                <form method="POST" action="{{ route('ingaz-delegation-stages.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>اسم المرحلة بالعربية</th>
                                        <th>اسم المرحلة بالإنجليزية</th>
                                        <th style="width:130px">الترتيب</th>
                                        <th style="width:100px">مفعّلة</th>
                                        <th style="width:90px">حذف</th>
                                    </tr>
                                </thead>
                                <tbody id="delegation-stages-body">
                                    @foreach (old('stages', $stages->toArray()) as $index => $stage)
                                        <tr>
                                            <td>
                                                <input type="hidden" name="stages[{{ $index }}][id]" value="{{ $stage['id'] ?? '' }}">
                                                <input type="text" class="form-control" name="stages[{{ $index }}][name_ar]" value="{{ $stage['name_ar'] ?? '' }}" maxlength="150" required>
                                            </td>
                                            <td><input type="text" class="form-control" name="stages[{{ $index }}][name_en]" value="{{ $stage['name_en'] ?? '' }}" maxlength="150"></td>
                                            <td><input type="number" class="form-control" name="stages[{{ $index }}][sort_order]" value="{{ $stage['sort_order'] ?? 0 }}" min="0" max="9999" required></td>
                                            <td class="text-center">
                                                <input type="hidden" name="stages[{{ $index }}][is_active]" value="{{ ($stage['is_system'] ?? false) ? 1 : 0 }}">
                                                <input type="checkbox" class="form-check-input" name="stages[{{ $index }}][is_active]" value="1" @checked($stage['is_active'] ?? true) @disabled($stage['is_system'] ?? false)>
                                            </td>
                                            <td class="text-center">
                                                @if (!($stage['is_system'] ?? false))
                                                    <input type="checkbox" class="form-check-input" name="stages[{{ $index }}][_delete]" value="1" title="حذف المرحلة عند الحفظ">
                                                @else
                                                    <span class="badge bg-light text-dark">أساسية</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if (@AdminGuardHasPerm(\App\PermKeys::SETTINGS_CONTROL))
                            <button type="button" id="add-delegation-stage" class="btn btn-outline-primary mt-3">
                                <i class="fa fa-plus me-1"></i>إضافة مرحلة
                            </button>
                        @endif
                    </div>
                    @if (@AdminGuardHasPerm(\App\PermKeys::SETTINGS_CONTROL))
                        <div class="card-footer bg-white">
                            <button type="submit" class="btn btn-primary"><i class="fa fa-save me-1"></i>حفظ المراحل</button>
                        </div>
                    @endif
                </form>
            </div>
        </div>
    @endif
@endsection

@section('js')
    <script>
        let delegationStageIndex = {{ count(old('stages', $stages->toArray())) }};

        document.getElementById('add-delegation-stage')?.addEventListener('click', function () {
            const body = document.getElementById('delegation-stages-body');
            const index = delegationStageIndex++;
            const row = document.createElement('tr');
            row.innerHTML = `
                <td><input type="text" class="form-control" name="stages[${index}][name_ar]" maxlength="150" required></td>
                <td><input type="text" class="form-control" name="stages[${index}][name_en]" maxlength="150"></td>
                <td><input type="number" class="form-control" name="stages[${index}][sort_order]" value="50" min="0" max="9999" required></td>
                <td class="text-center"><input type="hidden" name="stages[${index}][is_active]" value="0"><input type="checkbox" class="form-check-input" name="stages[${index}][is_active]" value="1" checked></td>
                <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-new-stage"><i class="fa fa-trash"></i></button></td>`;
            body.appendChild(row);
        });

        document.addEventListener('click', function (event) {
            if (event.target.closest('.remove-new-stage')) event.target.closest('tr').remove();
        });
    </script>
@endsection
