@extends('admin.layout.indexs.index')

@section('page-title', 'الملفات المالية للعقود')

@section('content')
    <div class="container-fluid">
        <h4 class="mb-1">الملفات المالية للعقود</h4>
        <div class="text-muted mb-3">حالة كل عقد: الفاتورة، تحصيل مساند، وسداد الوكيل الخارجي.</div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <form method="GET" class="mb-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label" for="search">بحث برقم العقد أو اسم العميل أو العاملة</label>
                    <input type="text" class="form-control" id="search" name="search" value="{{ $search }}">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-dark w-100" type="submit"><i class="fas fa-filter me-1"></i>تصفية</button>
                </div>
            </div>
        </form>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-striped mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>العقد</th>
                            <th>العميل</th>
                            <th>العاملة</th>
                            <th>الدولة</th>
                            <th>الفاتورة</th>
                            <th>تحصيل مساند</th>
                            <th>الوكيل الخارجي</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td>#{{ $row['contract_id'] }}</td>
                                <td>{{ $row['client'] }}</td>
                                <td>{{ $row['worker'] }}</td>
                                <td>{{ $row['nationality'] !== '' ? $row['nationality'] : '-' }}</td>
                                <td>
                                    @if ($row['invoice']['exists'])
                                        <span class="badge bg-success">{{ number_format($row['invoice']['total'], 2) }}</span>
                                    @else
                                        <span class="badge bg-secondary">لم تصدر</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($row['musaned']['settled'])
                                        <span class="badge bg-success">{{ number_format($row['musaned']['transferred'], 2) }}</span>
                                    @else
                                        <span class="badge bg-secondary">لم يحصَّل</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($row['agent']['invoiced'] <= 0)
                                        <span class="badge bg-secondary">لم تُثبت</span>
                                    @elseif ($row['agent']['remaining'] <= 0)
                                        <span class="badge bg-success">مسدَّد</span>
                                    @else
                                        <span class="badge bg-warning">متبقٍ {{ number_format($row['agent']['remaining'], 2) }}</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-dark"
                                       href="{{ route('recruitment-contract-finance.show', $row['contract_id']) }}">فتح الملف</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">لا توجد عقود مطابقة.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3">{{ $contracts->links() }}</div>
    </div>
@endsection
