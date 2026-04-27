@extends('admin.layout.indexs.index')

@section('style')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .outside-shelter-page .stat-card {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            background: #fff;
            padding: 1rem;
            min-height: 86px;
        }

        .outside-shelter-page .stat-card strong {
            display: block;
            font-size: 1.45rem;
            line-height: 1;
            margin-top: .35rem;
        }

        .outside-shelter-page .table td {
            vertical-align: middle;
            white-space: normal;
        }
    </style>
@endsection

@section('page-title')
    خارج الإيواء
@endsection

@section('content')
    <div class="page-body p-4 text-dark admin-tables outside-shelter-page">
        @if (@AdminGuardHasAnyPerm([
            \App\PermKeys::SHELTER_CONTRACT_INSIDE_VIEW,
            \App\PermKeys::SHELTER_CONTRACT_VIEW,
            \App\PermKeys::SHELTER_CONTRACT_CONTROL,
        ]))
            <div class="container-fluid">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
                    <h3 class="h3 mb-0 text-gray-800">
                        <i class="fas fa-sign-out-alt text-warning me-2"></i>
                        خارج الإيواء
                    </h3>
                    <a href="{{ route('ShelterContract.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-home me-1"></i> داخل الإيواء
                    </a>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <span class="text-muted">الإجمالي</span>
                            <strong>{{ $statistics['total'] ?? 0 }}</strong>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <span class="text-muted">تسليم إيجار</span>
                            <strong>{{ $statistics['rental'] ?? 0 }}</strong>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <span class="text-muted">تسليم نقل كفالة</span>
                            <strong>{{ $statistics['transfer'] ?? 0 }}</strong>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <span class="text-muted">أخرى</span>
                            <strong>{{ $statistics['other'] ?? 0 }}</strong>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <strong><i class="fas fa-filter me-1"></i> الفلاتر</strong>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="filterContractNumber" class="form-label">رقم عقد الإيواء</label>
                                <input type="text" id="filterContractNumber" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label for="filterOutsideReason" class="form-label">سبب الخروج</label>
                                <select id="filterOutsideReason" class="form-select">
                                    <option value="">الكل</option>
                                    <option value="rental_delivery">تسليم عقد إيجار</option>
                                    <option value="transfer_delivery">تسليم نقل كفالة</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="filterNationality" class="form-label">الجنسية</label>
                                <select id="filterNationality" class="form-select">
                                    <option value="">الكل</option>
                                    @foreach ($nationalities as $nationality)
                                        @php
                                            $translation = $nationality->translations->where('locale', app()->getLocale())->first();
                                            $name = $translation ? $translation->sub_title : $nationality->title ?? 'غير محدد';
                                        @endphp
                                        <option value="{{ $nationality->id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="filterShelter" class="form-label">مكان الإيواء السابق</label>
                                <select id="filterShelter" class="form-select">
                                    <option value="">الكل</option>
                                    @foreach ($shelterPlaces as $shelter)
                                        @php
                                            $translation = $shelter->translations->where('locale', app()->getLocale())->first();
                                            $name = $translation ? $translation->title : $shelter->title ?? 'غير محدد';
                                        @endphp
                                        <option value="{{ $shelter->id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap justify-content-end gap-2 mt-3">
                            <button type="button" id="clearFilters" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i> مسح
                            </button>
                            <button type="button" id="applyFilters" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i> بحث
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card shadow">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="outsideShelterTable" class="table table-hover align-middle mb-0 table-style" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>رقم عقد الإيواء</th>
                                        <th>بيانات العاملة</th>
                                        <th>سبب الخروج</th>
                                        <th>تاريخ الخروج</th>
                                        <th>المرجع</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @else
            @include('components.no_permissions')
        @endif
    </div>
@endsection

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        window.addEventListener('load', function() {
            if (typeof $.fn.DataTable === 'undefined') {
                return;
            }

            const table = $('#outsideShelterTable').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                searching: false,
                ajax: {
                    url: '{{ $dataTableRoute }}',
                    type: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    data: function(d) {
                        d.contract_number = $('#filterContractNumber').val();
                        d.outside_reason = $('#filterOutsideReason').val();
                        d.nationality = $('#filterNationality').val();
                        d.shelter = $('#filterShelter').val();
                    },
                    error: function(xhr) {
                        let message = 'حدث خطأ في تحميل البيانات';
                        if (xhr.responseJSON && xhr.responseJSON.error) {
                            message = xhr.responseJSON.error;
                        }
                        Swal.fire('خطأ!', message, 'error');
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'contract_number', name: 'id' },
                    { data: 'maid_data', name: 'maid_data', orderable: false },
                    { data: 'outside_reason', name: 'outside_reason', orderable: false },
                    { data: 'outside_at', name: 'outside_at' },
                    { data: 'reference_link', name: 'reference_link', orderable: false, searchable: false },
                    { data: 'actions', name: 'actions', orderable: false, searchable: false }
                ],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/ar.json'
                }
            });

            $('#applyFilters').on('click', function() {
                table.ajax.reload();
            });

            $('#clearFilters').on('click', function() {
                $('#filterContractNumber, #filterOutsideReason, #filterNationality, #filterShelter').val('');
                table.ajax.reload();
            });
        });
    </script>
@endsection
