@extends('admin.layout.indexs.index')

@section('page-title')
    إدارة أنواع الشكاوى
@endsection

@section('content')
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4>أنواع الشكاوى</h4>
            @if (@AdminGuardHasPerm(\App\PermKeys::COMPLAINT_TYPE_CREATE))
                <a href="{{ route('complaint-types.create') }}" class="btn btn-success">إضافة نوع جديد</a>
            @endif
        </div>
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        <table class="table table-bordered">
            <thead>
            <tr>
                <th>#</th>
                <th>اسم النوع</th>
                <th>العمليات</th>
            </tr>
            </thead>
            <tbody>
            @foreach($types as $type)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $type->name }}</td>
                    <td>
                        @if (@AdminGuardHasPerm(\App\PermKeys::COMPLAINT_TYPE_EDIT))
                            <a href="{{ route('complaint-types.edit', $type->id) }}" class="btn btn-primary btn-sm">تعديل</a>
                        @endif
                        @if (@AdminGuardHasPerm(\App\PermKeys::COMPLAINT_TYPE_DELETE))
                            <button class="btn btn-sm btn-danger delete-complaint-type" data-id="{{$type->id}}"
                                    title="حذف">
                                حذف
                            </button>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script>
    $(document).ready(function () {

        $(document).on('click', '.delete-complaint-type', function () {
            let complaintId = $(this).data('id');
            Swal.fire({
                title: 'هل أنت متأكد؟',
                text: "لن تتمكن من التراجع عن هذا الإجراء!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'نعم، احذف!',
                cancelButtonText: 'إلغاء'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '/admin/complaint-types/' + complaintId,
                        type: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function (response) {
                           handleSuccessResponce(response)
                        },
                        error: function () {
                            Swal.fire({
                                icon: 'error',
                                title: 'خطأ',
                                text: 'حدث خطأ أثناء الحذف'
                            });
                        }
                    });
                }
            });
        });
    });


</script>
