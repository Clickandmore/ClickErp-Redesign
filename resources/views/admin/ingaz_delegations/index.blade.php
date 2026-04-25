@extends('admin.layout.indexs.index')

@section('page-title')
    إدارة تفاويض الإنجاز
@endsection

@section('content')
    <div class="page-body">
        <div class="container-fluid">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h3 class="mb-1">إدارة تفاويض الإنجاز</h3>
                    <p class="text-muted mb-0">متابعة تفاويض الإنجاز وعرض بيانات العميل وتحويل مساند والجنسية والمهنة.</p>
                </div>
                @if ($canCreateDelegation)
                    <a href="{{ route('ingaz-delegations.create') }}" class="btn btn-primary">
                        <i class="fa fa-plus me-1"></i>
                        إضافة تفويض جديد
                    </a>
                @endif
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <form method="GET" id="delegations-filter-form" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">بحث</label>
                            <input type="text" name="search" value="{{ request('search') }}" class="form-control"
                                   placeholder="إنجاز، مساند، التأشيرة، العميل، الجواز">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">الجنسية</label>
                            <select name="nationality_id" class="form-select">
                                <option value="">الكل</option>
                                @foreach ($nationalities as $nationality)
                                    <option value="{{ $nationality->id }}" {{ request('nationality_id') == $nationality->id ? 'selected' : '' }}>
                                        {{ $nationality->sub_title ?? $nationality->title ?? ('#' . $nationality->id) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">نوع العميل</label>
                            <select name="client_source_type" class="form-select">
                                <option value="">الكل</option>
                                <option value="existing_in_program" {{ request('client_source_type') === 'existing_in_program' ? 'selected' : '' }}>موجود في البرنامج</option>
                                <option value="separate_client" {{ request('client_source_type') === 'separate_client' ? 'selected' : '' }}>عميل منفصل</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">من تاريخ</label>
                            <input type="date" name="from_date" value="{{ request('from_date') }}" class="form-control">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">إلى تاريخ</label>
                            <input type="date" name="to_date" value="{{ request('to_date') }}" class="form-control">
                        </div>
                        <div class="col-12 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">تصفية</button>
                            <a href="{{ route('ingaz-delegations.index') }}" class="btn btn-light">إعادة تعيين</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="ingaz-delegations-table" class="table table-hover align-middle mb-0" style="width:100%">
                            <thead class="table-light">
                                <tr>
                                    <th>رقم تعريف</th>
                                    <th>عميل</th>
                                    <th>تحويل مساند</th>
                                    <th>الجنسية</th>
                                    <th>المهنة</th>
                                    <th>فاتورة</th>
                                    <th>الحالة</th>
                                    <th>طباعة</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        $(document).ready(function () {
            const table = $('#ingaz-delegations-table').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                pageLength: 25,
                searching: false,
                order: [[0, 'desc']],
                ajax: {
                    url: "{{ route('ingaz-delegations.index') }}",
                    type: 'GET',
                    data: function (d) {
                        d.global_search = $('input[name="search"]').val();
                        d.nationality_id = $('select[name="nationality_id"]').val();
                        d.client_source_type = $('select[name="client_source_type"]').val();
                        d.from_date = $('input[name="from_date"]').val();
                        d.to_date = $('input[name="to_date"]').val();
                    }
                },
                columns: [
                    { data: 'id', name: 'id' },
                    { data: 'client', name: 'client', orderable: false, searchable: false },
                    { data: 'msaned_transfer', name: 'msaned_contract_number' },
                    { data: 'nationality_name', name: 'nationality_name', orderable: false, searchable: false },
                    { data: 'profession_name', name: 'profession_name', orderable: false, searchable: false },
                    { data: 'invoice', name: 'invoice', orderable: false, searchable: false },
                    { data: 'status_control', name: 'status', orderable: false, searchable: false },
                    { data: 'print_action', name: 'print_action', orderable: false, searchable: false },
                    { data: 'actions', name: 'actions', orderable: false, searchable: false }
                ],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/ar.json'
                }
            });

            $('#delegations-filter-form').on('submit', function (event) {
                event.preventDefault();
                table.ajax.reload();
            });

            $(document).on('focus', '.delegation-status-select', function () {
                $(this).data('previous', $(this).val());
            });

            $(document).on('change', '.delegation-status-select', function () {
                const $select = $(this);
                const previousValue = $select.data('previous') ?? $select.val();

                $.ajax({
                    url: $select.data('url'),
                    type: 'PATCH',
                    data: {
                        _token: "{{ csrf_token() }}",
                        status: $select.val()
                    },
                    success: function (response) {
                        $select.data('previous', $select.val());

                        if (typeof successToster === 'function') {
                            successToster('تم بنجاح', response.message || 'تم تحديث الحالة بنجاح');
                        }

                        table.ajax.reload(null, false);
                    },
                    error: function (xhr) {
                        $select.val(previousValue);

                        if (typeof errorToster === 'function') {
                            errorToster('حدث خطأ', xhr.responseJSON?.message || 'تعذر تحديث الحالة');
                        }
                    }
                });
            });
        });
    </script>
@endsection
