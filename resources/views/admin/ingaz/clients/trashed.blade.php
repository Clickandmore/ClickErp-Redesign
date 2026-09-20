@extends('admin.layout.indexs.index')

@section('page-title')
    العملاء المحذوفون
@endsection

@section('style')
    <style>
        .trashed-card {
            border-radius: 18px;
            border: 1px solid #eef2f6;
            box-shadow: 0 6px 18px rgba(15, 34, 58, 0.05);
        }

        .trashed-hero-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            background: #fdecec;
            color: #dc3545;
        }

        .trashed-table th {
            white-space: nowrap;
            font-size: 0.85rem;
        }

        .trashed-table td {
            vertical-align: middle;
            font-size: 0.9rem;
        }

        .trashed-table .client-name {
            font-weight: 600;
            white-space: nowrap;
        }

        .trashed-table .muted-line {
            font-size: 12px;
            color: #7b8794;
        }

        .trashed-empty {
            padding: 48px 16px;
            text-align: center;
            color: #98a2b3;
        }

        .trashed-empty i {
            font-size: 2.5rem;
            display: block;
            margin-bottom: 12px;
            opacity: .5;
        }

        .badge {
            border-radius: 999px;
        }

        @media (max-width: 575.98px) {
            .trashed-card {
                border-radius: 12px;
            }
        }
    </style>
@endsection

@section('content')
    <div class="page-body">
        <div class="container-fluid">

            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <div class="d-flex align-items-center gap-3">
                    <span class="trashed-hero-icon"><i class="fas fa-trash-restore" aria-hidden="true"></i></span>
                    <div>
                        <h3 class="h3 mb-0 text-gray-800">العملاء المحذوفون</h3>
                        <div class="text-muted small">إجمالي المحذوفين: {{ number_format($clients->total()) }}</div>
                    </div>
                </div>
                <div class="btn-toolbar gap-2 mt-3 mt-sm-0">
                    <a href="{{ route('clients.index') }}" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-users me-1" aria-hidden="true"></i>العودة إلى العملاء
                    </a>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-1" aria-hidden="true"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-1" aria-hidden="true"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-1" aria-hidden="true"></i>
                    <strong>تعذّر تنفيذ العملية:</strong>
                    <ul class="mb-0 mt-2 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
                </div>
            @endif

            <div class="alert alert-info" role="note">
                <i class="fas fa-info-circle me-1" aria-hidden="true"></i>
                يمكن استعادة العميل في أي وقت. الحذف النهائي متاح فقط عند عدم وجود طلبات أو عقود أو سجلات تعاملات مرتبطة
                بالعميل، حفاظًا على بيانات التعاملات.
            </div>

            <div class="card trashed-card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-2 align-items-end">
                        <div class="col-lg-6 col-md-8">
                            <label class="form-label fw-semibold small mb-1" for="trashedSearch">بحث</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="fas fa-search" aria-hidden="true"></i></span>
                                <input type="text" class="form-control" id="trashedSearch" name="search"
                                    value="{{ request('search') }}"
                                    placeholder="الاسم أو الجوال أو الهوية أو البريد أو رقم العميل">
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1" aria-hidden="true"></i>بحث
                            </button>
                            @if (request()->filled('search'))
                                <a href="{{ route('clients.trashed') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-1" aria-hidden="true"></i>إلغاء البحث
                                </a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

            <div class="card trashed-card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-style trashed-table mb-0">
                            <thead>
                                <tr>
                                    <th>رقم العميل</th>
                                    <th>الاسم</th>
                                    <th>الجوال</th>
                                    <th>رقم الهوية</th>
                                    <th>الجنسية</th>
                                    <th>تاريخ الحذف</th>
                                    <th class="text-center">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($clients as $client)
                                    @php
                                        $clientName = trim((string) $client->name) !== '' ? $client->name : 'بدون اسم';
                                        $clientPhone = $client->primaryPhoneValue();
                                        $clientNationality = optional($client->nationality)->name_ar
                                            ?: optional($client->nationality)->name_en;
                                    @endphp
                                    <tr>
                                        <td><span class="badge bg-light text-dark">#{{ $client->id }}</span></td>
                                        <td>
                                            <div class="client-name">{{ $clientName }}</div>
                                            @if (trim((string) $client->email) !== '')
                                                <div class="muted-line">{{ $client->email }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($clientPhone !== '')
                                                <span dir="ltr">{{ $clientPhone }}</span>
                                            @else
                                                <span class="muted-line">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if (trim((string) $client->national_num) !== '')
                                                <span dir="ltr">{{ $client->national_num }}</span>
                                            @else
                                                <span class="muted-line">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($clientNationality)
                                                {{ $clientNationality }}
                                            @else
                                                <span class="muted-line">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($client->deleted_at)
                                                <div>{{ $client->deleted_at->format('Y-m-d H:i') }}</div>
                                                <div class="muted-line">{{ $client->deleted_at->diffForHumans() }}</div>
                                            @else
                                                <span class="muted-line">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2 justify-content-center">
                                                <form method="POST" action="{{ route('clients.restore', $client->id) }}"
                                                    data-confirm
                                                    data-confirm-icon="question"
                                                    data-confirm-title="استعادة العميل"
                                                    data-confirm-text="سيتم استعادة العميل ({{ $clientName }}) رقم #{{ $client->id }} إلى قائمة العملاء."
                                                    data-confirm-button="نعم، استعادة"
                                                    data-confirm-color="#198754">
                                                    @csrf
                                                    <button type="submit" class="btn btn-success btn-sm">
                                                        <i class="fas fa-undo me-1" aria-hidden="true"></i>استعادة
                                                    </button>
                                                </form>
                                                <form method="POST" action="{{ route('clients.forceDelete', $client->id) }}"
                                                    data-confirm
                                                    data-confirm-icon="warning"
                                                    data-confirm-title="حذف نهائي"
                                                    data-confirm-text="سيتم حذف العميل ({{ $clientName }}) رقم #{{ $client->id }} نهائيًا ولن يمكن استعادته."
                                                    data-confirm-button="نعم، حذف نهائي"
                                                    data-confirm-color="#dc3545">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm">
                                                        <i class="fas fa-trash me-1" aria-hidden="true"></i>حذف نهائي
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7">
                                            <div class="trashed-empty">
                                                <i class="fas fa-trash-alt" aria-hidden="true"></i>
                                                @if (request()->filled('search'))
                                                    لا توجد نتائج مطابقة لبحثك
                                                @else
                                                    لا يوجد عملاء محذوفون
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($clients->hasPages())
                        <div class="mt-3 d-flex justify-content-center">
                            {{ $clients->withQueryString()->links() }}
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
@endsection

@section('js')
    <script>
        (function () {
            document.addEventListener('submit', function (event) {
                var form = event.target;
                if (!form || !form.matches('form[data-confirm]') || form.dataset.confirmed === '1') {
                    return;
                }

                event.preventDefault();

                var title = form.dataset.confirmTitle || 'تأكيد العملية';
                var text = form.dataset.confirmText || 'هل تريد المتابعة؟';
                var confirmText = form.dataset.confirmButton || 'تأكيد';
                var icon = form.dataset.confirmIcon || 'warning';
                var color = form.dataset.confirmColor || '#dc3545';

                var submit = function () {
                    form.dataset.confirmed = '1';
                    form.submit();
                };

                if (typeof window.Swal === 'undefined') {
                    if (window.confirm(text + ' هل تريد المتابعة؟')) {
                        submit();
                    }
                    return;
                }

                window.Swal.fire({
                    title: title,
                    text: text,
                    icon: icon,
                    showCancelButton: true,
                    confirmButtonText: confirmText,
                    cancelButtonText: 'إلغاء',
                    confirmButtonColor: color,
                    cancelButtonColor: '#6c757d',
                    reverseButtons: true,
                    focusCancel: true
                }).then(function (result) {
                    if (result.isConfirmed) {
                        submit();
                    }
                });
            });
        })();
    </script>
@endsection
