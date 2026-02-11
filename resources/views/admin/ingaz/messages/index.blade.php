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
                            جميع الرسائل المرسلة للعملاء
                        </h4>
                        <form class="d-flex" method="get" action="{{ route('messages.index') }}">
                            <input type="text" name="q" class="form-control me-2"
                                placeholder="بحث بالاسم أو الهاتف أو نص الرسالة"
                                value="{{ request('q') }}">
                            <button class="btn btn-primary" type="submit">بحث</button>
                        </form>
                    </div>
                </div>
                <div class="card-body table-responsive">
                    <ul class="nav nav-tabs mb-3" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="sms-tab" data-bs-toggle="tab" data-bs-target="#sms-tab-pane"
                                type="button" role="tab" aria-controls="sms-tab-pane" aria-selected="true">
                                الرسائل النصية
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="notifications-tab" data-bs-toggle="tab"
                                data-bs-target="#notifications-tab-pane" type="button" role="tab"
                                aria-controls="notifications-tab-pane" aria-selected="false">
                                الإشعارات
                            </button>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="sms-tab-pane" role="tabpanel" aria-labelledby="sms-tab">
                            <table class="table table-striped align-middle table-style">
                                <thead class="table-dark">
                                    <tr>
                                        <th>#</th>
                                        <th>العميل</th>
                                        <th>الهاتف</th>
                                        <th>التاريخ</th>
                                        <th>الرسالة</th>
                                        <th>الاجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($messages as $index => $message)
                                        <tr>
                                            <td>{{ $messages->firstItem() + $index }}</td>
                                            <td>{{ $message->user->name ?? 'غير معروف' }}</td>
                                            <td>{{ $message->user->phone ?? '-' }}</td>
                                            <td>{{ $message->created_at?->format('d-m-Y H:i') }}</td>
                                            <td>{{ $message->message }}</td>
                                            <td>
                                                @if ($message->user_id)
                                                    <a href="{{ route('clients.show', $message->user_id) }}"
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
                                            <td colspan="6" class="text-center text-muted">لا توجد رسائل نصية مرسلة.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                            <div class="mt-3">
                                {{ $messages->links() }}
                            </div>
                        </div>
                        <div class="tab-pane fade" id="notifications-tab-pane" role="tabpanel" aria-labelledby="notifications-tab">
                            <table class="table table-striped align-middle">
                                <thead class="table-dark">
                                    <tr>
                                        <th>#</th>
                                        <th>العميل</th>
                                        <th>الهاتف</th>
                                        <th>التاريخ</th>
                                        <th>العنوان</th>
                                        <th>الرسالة</th>
                                        <th>الاجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($notifications as $index => $notification)
                                        @php
                                            $notificationTitle = is_array($notification->title)
                                                ? ($notification->title[app()->getLocale()] ?? reset($notification->title))
                                                : $notification->title;
                                            $notificationDescription = is_array($notification->description)
                                                ? ($notification->description[app()->getLocale()] ?? reset($notification->description))
                                                : $notification->description;
                                        @endphp
                                        <tr>
                                            <td>{{ $notifications->firstItem() + $index }}</td>
                                            <td>{{ $notification->user->name ?? 'الكل' }}</td>
                                            <td>{{ $notification->user->phone ?? '-' }}</td>
                                            <td>{{ $notification->created_at?->format('d-m-Y H:i') }}</td>
                                            <td>{{ $notificationTitle }}</td>
                                            <td>{{ $notificationDescription }}</td>
                                            <td>
                                                @if ($notification->user_id)
                                                    <a href="{{ route('clients.show', $notification->user_id) }}"
                                                        class="btn btn-sm btn-outline-primary">
                                                        عرض العميل
                                                    </a>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">لا توجد إشعارات مرسلة.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                            <div class="mt-3">
                                {{ $notifications->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        @include('components.no_permissions')
    @endif
@endsection
