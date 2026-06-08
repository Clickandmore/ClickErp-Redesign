@extends('admin.layout.indexs.index')

@section('page-title')
    مستخدمي الوكلاء
@endsection

@section('content')
    <div class="container-fluid py-3">
        <div class="card mb-3">
            <div class="card-body d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                <div>
                    <h4 class="mb-1">مستخدمي الوكلاء</h4>
                    <p class="text-muted mb-0">الحسابات المرتبطة بمكاتب الوكلاء الخارجيين.</p>
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
                    <div class="col-12 col-lg-5">
                        <label class="form-label">بحث</label>
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control"
                            placeholder="الاسم، البريد، الجوال">
                    </div>
                    <div class="col-12 col-lg-4">
                        <label class="form-label">المكتب</label>
                        <select name="office_id" class="form-select">
                            <option value="">كل المكاتب</option>
                            @foreach ($offices as $office)
                                <option value="{{ $office->id }}" @selected((string) request('office_id') === (string) $office->id)>
                                    {{ $office->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-lg-3 d-flex gap-2">
                        <button class="btn btn-primary flex-fill" type="submit">تصفية</button>
                        <a href="{{ route('offices.users.index') }}" class="btn btn-outline-secondary">مسح</a>
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
                            <th>المستخدم</th>
                            <th>بيانات التواصل</th>
                            <th>المكاتب المرتبطة</th>
                            <th>الحالة</th>
                            <th class="text-end">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr>
                                <td>{{ $user->id }}</td>
                                <td>
                                    <strong>{{ $user->name }}</strong>
                                    <div class="text-muted small">{{ $user->username ?: '-' }}</div>
                                </td>
                                <td>
                                    <div>{{ $user->email ?: '-' }}</div>
                                    <div class="text-muted small">{{ $user->contact_phone ?: '-' }}</div>
                                </td>
                                <td>
                                    @forelse ($user->agentOffices as $office)
                                        <a href="{{ route('offices.show', $office->id) }}" class="badge bg-light text-dark border text-decoration-none">
                                            {{ $office->title }}
                                        </a>
                                    @empty
                                        <span class="text-muted">-</span>
                                    @endforelse
                                </td>
                                <td>
                                    <span class="badge {{ $user->status === 'suspended' ? 'bg-danger' : 'bg-success' }}">
                                        {{ $user->status === 'suspended' ? 'موقوف' : 'نشط' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    @if (Route::has('admins.edit'))
                                        <a href="{{ route('admins.edit', $user->id) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="fa fa-edit"></i>
                                            تعديل
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">لا يوجد مستخدمين وكلاء.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-body">
                {{ $users->links() }}
            </div>
        </div>
    </div>
@endsection
