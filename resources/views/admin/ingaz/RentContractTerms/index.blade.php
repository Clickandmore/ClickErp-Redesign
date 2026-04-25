@extends('admin.layout.indexs.index')

@section('title', 'بنود عقد الإيجار')
@section('content')
    @php
        $canCreateTerm = @AdminGuardHasAnyPerm([
            \App\PermKeys::RENT_CONTRACT_TERMS_CREATE,
            \App\PermKeys::RENT_CONTRACT_CREATE,
            \App\PermKeys::CONTRACT_SETTINGS,
        ]);
        $canEditTerm = @AdminGuardHasAnyPerm([
            \App\PermKeys::RENT_CONTRACT_TERMS_EDIT,
            \App\PermKeys::RENT_CONTRACT_EDIT,
            \App\PermKeys::CONTRACT_SETTINGS,
        ]);
        $canDeleteTerm = @AdminGuardHasAnyPerm([
            \App\PermKeys::RENT_CONTRACT_TERMS_DELETE,
            \App\PermKeys::RENT_CONTRACT_DELETE,
            \App\PermKeys::CONTRACT_SETTINGS,
        ]);
    @endphp
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title">بنود عقد الإيجار</h3>
                        <div class="d-flex gap-2">
                            @if ($canEditTerm)
                                <button type="button" class="btn btn-outline-warning" id="importDefaultTerms">
                                    <i class="fas fa-file-import"></i> استيراد البنود الافتراضية
                                </button>
                            @endif
                            @if ($canCreateTerm)
                                <a href="{{ route('rent-contract-terms.create') }}" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> إضافة بند جديد
                                </a>
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
                        <table class="table table-hover table-style">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>عنوان القسم</th>
                                    <th>رقم القسم</th>
                                    <th>رقم البند </th>
                                    <th>محتوي البند</th>
                                    <th>ترتيب البند</th>
                                    <th>رقم الصفحة</th>
                                    <th>الحالة</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($terms as $key => $term)
                                    <tr>
                                        <td>{{ ++$key }}</td>
                                        <td>{{ $term->section_title }}</td>
                                        <td>{{ $term->section_order }}</td>
                                        <td>{{ $term->term_number }}</td>
                                        <td>{{ \Illuminate\Support\Str::limit($term->term_content, 20, '...') }}</td>
                                        <td>{{ $term->term_order }}</td>
                                        <td>{{ $term->page_number }}</td>
                                        <td>{{ $term->is_active == 1 ? 'نشط' : 'غير نشط' }}</td>
                                        <td>
                                            @if ($canEditTerm)
                                                <a href="{{ route('rent-contract-terms.edit', $term->id) }}" title="تعديل"
                                                    class="btn btn-sm btn-primary">
                                                    <i class="fa fa-pencil"></i>
                                                </a>
                                            @endif

                                            @if ($canDeleteTerm)
                                                <button type="button" class="btn btn-sm btn-danger btn-delete deleteButton" data-id="{{ $term->id }}"
                                                    title="حذف">
                                                    <i class="fa fa-trash-o"></i>
                                                </button>
                                            @endif

                                            @if (!$canEditTerm && !$canDeleteTerm)
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#importDefaultTerms').on('click', function() {
                Swal.fire({
                    title: 'استيراد البنود الافتراضية؟',
                    text: 'سيتم حذف البنود الحالية واستبدالها ببنود العقد الافتراضية.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'نعم، استورد',
                    cancelButtonText: 'إلغاء'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = "{{ route('rent-contract-terms.import-default') }}";
                    }
                });
            });

            $(document).on('click', '.btn-delete', function() {
                var id = $(this).data('id');
                Swal.fire({
                    title: 'هل أنت متأكد؟',
                    text: 'سيتم حذف البند نهائياً!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'نعم، احذف',
                    cancelButtonText: 'إلغاء'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('rent-contract-terms.destroy', ':id') }}".replace(':id', id),
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire('تم الحذف!', response.message, 'success');
                                    window.location.reload();
                                } else {
                                    Swal.fire('خطأ!', response.message, 'error');
                                }
                            },
                            error: function() {
                                Swal.fire('خطأ!', 'حدث خطأ أثناء الحذف', 'error');
                            }
                        });
                    }
                });
            });
        });
    </script>
@endpush
