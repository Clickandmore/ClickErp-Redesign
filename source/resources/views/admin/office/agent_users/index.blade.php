@extends('admin.layout.indexs.index')

@section('page-title')
    مستخدمي الوكلاء
@endsection

@section('css')
    <style>
        .agent-user-actions {
            display: inline-flex;
            align-items: center;
            justify-content: flex-end;
            gap: .5rem;
            white-space: nowrap;
        }

        .agent-user-actions .btn {
            min-width: 92px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .4rem;
        }

        @media (max-width: 767.98px) {
            .agent-user-actions {
                display: flex;
                flex-direction: column;
                align-items: stretch;
            }

            .agent-user-actions .btn {
                width: 100%;
            }
        }
    </style>
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
                            <th>الجنسية</th>
                            <th>الحالة</th>
                            <th class="text-end text-nowrap">الإجراءات</th>
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
                                    @php
                                        $nationalities = $user->agentOffices
                                            ->map(fn ($office) => $office->nationality?->display_name)
                                            ->filter()
                                            ->unique();
                                    @endphp
                                    @forelse ($nationalities as $nationality)
                                        <span class="badge bg-info-subtle text-info border me-1">{{ $nationality }}</span>
                                    @empty
                                        <span class="text-muted">-</span>
                                    @endforelse
                                </td>
                                <td>
                                    <span class="badge {{ $user->status === 'suspended' ? 'bg-danger' : 'bg-success' }}">
                                        {{ $user->status === 'suspended' ? 'موقوف' : 'نشط' }}
                                    </span>
                                </td>
                                <td class="text-end text-nowrap">
                                    <div class="agent-user-actions" role="group" aria-label="إجراءات {{ $user->name }}">
                                        <button type="button" class="btn btn-sm btn-info text-white" data-bs-toggle="modal"
                                            data-bs-target="#agentUserDetails{{ $user->id }}" title="عرض بيانات المستخدم كاملة">
                                            <i class="fa fa-eye" aria-hidden="true"></i>
                                            <span>التفاصيل</span>
                                        </button>
                                        @if (Route::has('offices.users.edit') && @AdminGuardHasPerm(\App\PermKeys::USERS_EDIT))
                                            <button type="button" class="btn btn-sm btn-primary editButton"
                                                data-bs-toggle="modal" data-bs-target="#createModal"
                                                model-route="{{ route('offices.users.edit', $user->id) }}"
                                                model-title="بيانات دخول {{ $user->name }}" title="تعديل بيانات الدخول">
                                                <i class="fa fa-edit" aria-hidden="true"></i>
                                                <span>تعديل</span>
                                            </button>
                                            @if ($user->status === 'suspended')
                                                <form method="POST"
                                                    action="{{ route('offices.users.activate', [
                                                        'user' => $user->id,
                                                        'search' => request('search'),
                                                        'office_id' => request('office_id'),
                                                        'page' => request('page'),
                                                    ]) }}"
                                                    class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success"
                                                        title="تفعيل الحساب">
                                                        <i class="fa fa-check-circle" aria-hidden="true"></i>
                                                        <span>تفعيل</span>
                                                    </button>
                                                </form>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">لا يوجد مستخدمين وكلاء.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-body">
                {{ $users->links() }}
            </div>
        </div>

        @foreach ($users as $user)
            @php
                $adminTypeLabel = \Illuminate\Support\Arr::get(
                    \App\Helpers\Constant::globalArray['admin_typeis'],
                    $user->admin_type . '.' . app()->getLocale(),
                    '-'
                );
                $officeNames = $user->agentOffices->pluck('title')->filter()->implode('، ');
                $nationalityNames = $user->agentOffices
                    ->map(fn ($office) => $office->nationality?->display_name)
                    ->filter()
                    ->unique()
                    ->implode('، ');
            @endphp
            <div class="modal fade" id="agentUserDetails{{ $user->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">تفاصيل مستخدم الوكيل: {{ $user->name }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-4"><strong>رقم المستخدم</strong><div>{{ $user->id }}</div></div>
                                <div class="col-md-4"><strong>اسم المستخدم</strong><div>{{ $user->username ?: '-' }}</div></div>
                                <div class="col-md-4"><strong>نوع المستخدم</strong><div>{{ $adminTypeLabel }}</div></div>
                                <div class="col-md-4"><strong>البريد الإلكتروني</strong><div>{{ $user->email ?: '-' }}</div></div>
                                <div class="col-md-4"><strong>الجوال</strong><div>{{ $user->contact_phone ?: '-' }}</div></div>
                                <div class="col-md-4"><strong>واتساب</strong><div>{{ $user->whatsapp_phone ?: '-' }}</div></div>
                                <div class="col-md-4"><strong>هاتف التحقق</strong><div>{{ $user->verification_phone ?: '-' }}</div></div>
                                <div class="col-md-4"><strong>الدور</strong><div>{{ $user->role?->name ?: '-' }}</div></div>
                                <div class="col-md-4"><strong>الحالة</strong><div>{{ $user->status === 'suspended' ? 'موقوف' : 'نشط' }}</div></div>
                                <div class="col-md-6"><strong>المكاتب المرتبطة</strong><div>{{ $officeNames ?: '-' }}</div></div>
                                <div class="col-md-6"><strong>الجنسيات</strong><div>{{ $nationalityNames ?: '-' }}</div></div>
                                <div class="col-md-4"><strong>آخر دخول</strong><div>{{ $user->last_login_at?->format('Y-m-d H:i') ?: '-' }}</div></div>
                                <div class="col-md-4"><strong>آخر نشاط</strong><div>{{ $user->last_activity_at?->format('Y-m-d H:i') ?: '-' }}</div></div>
                                <div class="col-12"><strong>ملاحظات</strong><div>{{ $user->note ?: '-' }}</div></div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إغلاق</button>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
