@extends('admin.layout.indexs.index')

@section('page-title')
جميع الرسائل المرسلة
@endsection

@section('content')
    @if (@AdminGuardHasPerm(\App\PermKeys::NOTIFICATIONS_CONTROL))
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <h4 class="mb-0">
                            <i class="fa fa-inbox me-2"></i>
                            سجل الرسائل المرسلة (SMS / WhatsApp)
                        </h4>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('notifications.settings') }}" class="btn btn-outline-dark">
                                <i class="fa fa-plug me-1"></i>
                                إعدادات الربط
                            </a>
                            <form class="d-flex flex-wrap gap-2" method="get" action="{{ route('messages.index') }}">
                                <select name="type" class="form-control">
                                    <option value="all" {{ ($type ?? 'all') === 'all' ? 'selected' : '' }}>كل القنوات</option>
                                    <option value="sms" {{ ($type ?? 'all') === 'sms' ? 'selected' : '' }}>SMS</option>
                                    <option value="whatsapp" {{ ($type ?? 'all') === 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
                                </select>
                                <input type="text" name="q" class="form-control"
                                    placeholder="بحث بالاسم أو الهاتف أو نص الرسالة"
                                    value="{{ request('q') }}">
                                <button class="btn btn-primary" type="submit">بحث</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-striped align-middle table-style">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>القناة</th>
                                <th>العميل</th>
                                <th>الهاتف</th>
                                <th>التاريخ</th>
                                <th>العنوان</th>
                                <th>الرسالة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($smsLogs as $index => $smsLog)
                                @php
                                    $smsTitle = is_array($smsLog->title)
                                        ? ($smsLog->title[app()->getLocale()] ?? reset($smsLog->title))
                                        : $smsLog->title;
                                    $smsDescription = is_array($smsLog->description)
                                        ? ($smsLog->description[app()->getLocale()] ?? reset($smsLog->description))
                                        : $smsLog->description;
                                    $channel = strtolower((string) $smsLog->type) === 'whatsapp' ? 'whatsapp' : 'sms';
                                @endphp
                                <tr>
                                    <td>{{ $smsLogs->firstItem() + $index }}</td>
                                    <td>
                                        @if ($channel === 'whatsapp')
                                            <span class="badge bg-success">WhatsApp</span>
                                        @else
                                            <span class="badge bg-primary">SMS</span>
                                        @endif
                                    </td>
                                    <td>{{ $smsLog->user->name ?? 'غير معروف' }}</td>
                                    <td>{{ $smsLog->user->phone ?? '-' }}</td>
                                    <td>{{ $smsLog->created_at?->format('d-m-Y H:i') }}</td>
                                    <td>{{ $smsTitle ?: '-' }}</td>
                                    <td>{{ $smsDescription ?: '-' }}</td>
                                    <td>
                                        @if ($smsLog->user_id)
                                            <a href="{{ route('clients.show', $smsLog->user_id) }}"
                                                class="btn btn-sm btn-outline-primary text-nowrap">
                                                عرض العميل
                                            </a>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted">لا توجد رسائل مرسلة بهذا الفلتر.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="mt-3">
                        {{ $smsLogs->links() }}
                    </div>
                </div>
            </div>
        </div>
    @else
        @include('components.no_permissions')
    @endif
@endsection
