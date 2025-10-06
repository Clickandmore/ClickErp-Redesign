@extends('admin.layout.indexs.index')

@section('page-title')
    {{ __('ingaz.details') }}
@endsection

@section('content')
    {{-- @dd($client) --}}
    {{-- @dd($client->activities) --}}
    {{-- @dd($client->messages) --}}
    {{-- @dd($client->complaints) --}}
    {{-- @dd($client->orders) --}}
    <div class="container my-5">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h4>بيانات العميل</h4>
            </div>
            <div class="card-body">

                <!-- بيانات العميل الأساسية -->
                <div class="row mb-3">
                    <div class="col-md-4 font-weight-bold">الاسم:</div>
                    <div class="col-md-8">{{ $client->name ?? '-' }}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4 font-weight-bold">البريد الإلكتروني:</div>
                    <div class="col-md-8">{{ $client->email ?? '-' }}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4 font-weight-bold">رقم الهاتف:</div>
                    <div class="col-md-8">{{ $client->phone ?? '-' }}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4 font-weight-bold">العنوان:</div>
                    <div class="col-md-8">{{ $client->address ?? '-' }}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4 font-weight-bold">تاريخ الميلاد:</div>
                    <div class="col-md-8">{{ $client->birthdate ?? '-' }}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4 font-weight-bold">الرقم القومي:</div>
                    <div class="col-md-8">{{ $client->national_num ?? '-' }}</div>
                </div>

                <!-- العناوين المتعددة -->
                <div class="mb-3">
                    <div class="font-weight-bold mb-2">العناوين:</div>
                    @forelse($client->addresses as $address)
                        <div class="card mb-2 p-2 shadow-sm">
                            <div class="row">
                                <div class="col-md-4 font-weight-bold">البلد:</div>
                                <div class="col-md-8">
                                    {{ (app()->getLocale() == 'ar' ? $address->country->name_ar ?? '-' : $address->country->name_en ?? '-') ?? '-' }}
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 font-weight-bold">العنوان:</div>
                                <div class="col-md-8">{{ $address->address ?? '-' }}</div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 font-weight-bold">العنوان المختصر / العنوان الفرعي:</div>
                                <div class="col-md-8">{{ $address->title ?? '-' }}</div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 font-weight-bold">الهاتف:</div>
                                <div class="col-md-8">{{ $address->phone ?? '-' }}</div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 font-weight-bold">اسم المستلم:</div>
                                <div class="col-md-8">{{ $address->name ?? '-' }}</div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 font-weight-bold">ملاحظات:</div>
                                <div class="col-md-8">{{ $address->notes ?? '-' }}</div>
                            </div>
                        </div>
                    @empty
                        <p>- لا توجد عناوين مسجلة -</p>
                    @endforelse
                </div>

                <!-- الجنسية -->
                <div class="row mb-3">
                    <div class="col-md-4 font-weight-bold">الجنسية:</div>
                    <div class="col-md-8">{{ $client->nationality->title ?? '-' }}</div>
                </div>

            </div>
        </div>

        {{-- Mahmoud Amer's Code --}}
        <div class="card mt-4">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">{{ __('طلبات العميل') }}</h4>
            </div>
            <div class="card-body">
                @forelse ($client->orders as $order)
                    @php
                        $maid = $order->maid;
                        $details = $order->details;
                        $package = $details?->package;
                        $address = $details?->address;
                        $admin = $details?->admin;
                        $statusKey = $order->status ?? '';
                        $statusLabel = $statusKey ? getEnumValue('orders_statuses', $statusKey) : __('غير محدد');
                        $statusBadgeMap = [
                            'new' => 'bg-warning text-dark',
                            'completed' => 'bg-success',
                            'done' => 'bg-success',
                            'contracted' => 'bg-success',
                            'refused' => 'bg-danger',
                            'return' => 'bg-danger',
                            'under_process' => 'bg-info text-dark',
                            'contracted_without_contract' => 'bg-primary text-white',
                        ];
                        $badgeClass = $statusBadgeMap[$statusKey] ?? 'bg-secondary text-white';

                        $paymentMethodMap = [
                            'cash' => __('دفع نقدي'),
                            'online' => __('دفع إلكتروني'),
                        ];
                        $paymentTypeMap = [
                            'bank_transfer' => __('تحويل بنكي'),
                            'pay_later' => __('دفع لاحق'),
                            'mada' => __('مدى'),
                            'visa' => 'Visa',
                            'masterCard' => 'MasterCard',
                        ];
                        $paymentMethodLabel = $paymentMethodMap[$order->payment_method] ?? $order->payment_method;
                        $paymentTypeLabel = $order->payment_type
                            ? $paymentTypeMap[$order->payment_type] ?? $order->payment_type
                            : null;
                    @endphp

                    <div class="border rounded-3 mb-4 shadow-sm">
                        <div class="px-3 py-3 border-bottom bg-light d-flex flex-wrap align-items-center gap-2">
                            <div class="fw-semibold">
                                {{ __('رقم الطلب') }}:
                                <span class="text-primary">#{{ $order->order_code ?? $order->id }}</span>
                            </div>
                            <div class="text-muted small">
                                {{ __('تاريخ الإنشاء') }}:
                                {{ optional($order->created_at)->format('Y-m-d H:i') ?? '-' }}
                            </div>
                            @if ($order->status_changed_at)
                                <div class="text-muted small">
                                    {{ __('آخر تحديث') }}:
                                    {{ optional($order->status_changed_at)->format('Y-m-d H:i') ?? '-' }}
                                </div>
                            @endif
                            <span class="badge {{ $badgeClass }} ms-auto">
                                {{ $statusLabel }}
                            </span>
                        </div>

                        <div class="px-3 py-3">
                            <h6 class="fw-bold mb-3">{{ __('تفاصيل الطلب') }}</h6>
                            <div class="row gy-3">
                                <div class="col-md-4 col-sm-6">
                                    <div class="text-muted small">{{ __('نوع الوظيفة') }}</div>
                                    <div class="fw-semibold">{{ $order->jobType?->title ?? '-' }}</div>
                                </div>
                                <div class="col-md-4 col-sm-6">
                                    <div class="text-muted small">{{ __('طريقة الدفع') }}</div>
                                    <div class="fw-semibold">{{ $paymentMethodLabel ?? '-' }}</div>
                                </div>
                                <div class="col-md-4 col-sm-6">
                                    <div class="text-muted small">{{ __('قناة الدفع') }}</div>
                                    <div class="fw-semibold">{{ $paymentTypeLabel ?? '-' }}</div>
                                </div>
                                <div class="col-md-4 col-sm-6">
                                    <div class="text-muted small">{{ __('المهلة النهائية') }}</div>
                                    <div class="fw-semibold">
                                        {{ optional($order->deadline_at)->format('Y-m-d H:i') ?? '-' }}</div>
                                </div>
                                <div class="col-md-4 col-sm-6">
                                    <div class="text-muted small">{{ __('انتهاء الحجز الآلي') }}</div>
                                    <div class="fw-semibold">
                                        {{ optional($order->expiration_time)->format('Y-m-d H:i') ?? '-' }}</div>
                                </div>
                                <div class="col-md-4 col-sm-6">
                                    <div class="text-muted small">{{ __('موظف الخدمة') }}</div>
                                    <div class="fw-semibold">{{ $admin?->name ?? '-' }}</div>
                                </div>
                            </div>

                            @if ($maid)
                                <hr class="my-4">
                                <h6 class="fw-bold mb-3">{{ __('بيانات العاملة') }}</h6>
                                <div class="row gy-3">
                                    <div class="col-md-4 col-sm-6">
                                        <div class="text-muted small">{{ __('اسم العاملة') }}</div>
                                        <div class="fw-semibold">{{ $maid?->name ?? '-' }}</div>
                                    </div>
                                    <div class="col-md-4 col-sm-6">
                                        <div class="text-muted small">{{ __('الجنسية') }}</div>
                                        <div class="fw-semibold">{{ $maid?->nationality?->title ?? '-' }}</div>
                                    </div>
                                    <div class="col-md-4 col-sm-6">
                                        <div class="text-muted small">{{ __('رقم الجواز') }}</div>
                                        <div class="fw-semibold">{{ $maid?->passport_num ?? '-' }}</div>
                                    </div>
                                    <div class="col-md-4 col-sm-6">
                                        <div class="text-muted small">{{ __('الراتب الشهري') }}</div>
                                        <div class="fw-semibold">{{ $maid?->salary ?? '-' }}</div>
                                    </div>
                                    <div class="col-md-4 col-sm-6">
                                        <div class="text-muted small">{{ __('المكتب') }}</div>
                                        <div class="fw-semibold">{{ $maid?->office?->name ?? '-' }}</div>
                                    </div>
                                    <div class="col-md-4 col-sm-6">
                                        <div class="text-muted small">{{ __('العمر') }}</div>
                                        <div class="fw-semibold">{{ $maid?->age ?? '-' }}</div>
                                    </div>
                                    <div class="col-md-4 col-sm-6">
                                        <div class="text-muted small">{{ __('سنوات الخبرة') }}</div>
                                        <div class="fw-semibold">{{ $maid?->experience_years ?? '-' }}</div>
                                    </div>
                                    <div class="col-md-4 col-sm-6">
                                        <div class="text-muted small">{{ __('الديانة') }}</div>
                                        <div class="fw-semibold">{{ $maid?->religion?->name ?? '-' }}</div>
                                    </div>
                                </div>
                            @endif

                            @if ($package)
                                <hr class="my-4">
                                <h6 class="fw-bold mb-3">{{ __('الباقة المختارة') }}</h6>
                                <div class="fw-semibold">{{ $package?->title ?? '-' }}</div>
                            @endif

                            @if ($address)
                                <hr class="my-4">
                                <h6 class="fw-bold mb-3">{{ __('عنوان العميل') }}</h6>
                                <div class="row gy-3">
                                    <div class="col-md-4 col-sm-6">
                                        <div class="text-muted small">{{ __('اسم العنوان') }}</div>
                                        <div class="fw-semibold">{{ $address?->title ?? '-' }}</div>
                                    </div>
                                    <div class="col-md-4 col-sm-6">
                                        <div class="text-muted small">{{ __('الدولة') }}</div>
                                        <div class="fw-semibold">{{ $address?->country?->title ?? '-' }}</div>
                                    </div>
                                    <div class="col-md-4 col-sm-6">
                                        <div class="text-muted small">{{ __('رقم التواصل') }}</div>
                                        <div class="fw-semibold">{{ $address?->phone ?? '-' }}</div>
                                    </div>
                                    <div class="col-12">
                                        <div class="text-muted small">{{ __('الوصف') }}</div>
                                        <div class="fw-semibold">{{ $address?->address ?? '-' }}</div>
                                    </div>
                                    @if ($address?->notes)
                                        <div class="col-12">
                                            <div class="text-muted small">{{ __('ملاحظات') }}</div>
                                            <div class="fw-semibold">{{ $address?->notes }}</div>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-center text-muted mb-0">{{ __('لا توجد طلبات مسجلة لهذا العميل.') }}</p>
                @endforelse
            </div>
        </div>

        <div class="card">
            <h4 class="card-title">{{ __('شكاوى العميل') }}</h4>

            <div class="card-body table-responsive  table-sm ">
                <table class="table  ">
                    <thead class="bg-success text-white">
                        <th>{{ __('موضوع الشكوى') }}</th>
                        <th>{{ __('عنوان الشكوى') }}</th>
                        <th>{{ __('جهة الشكوى') }}</th>
                        <th>{{ __('تاريخ الشكوى') }}</th>
                        <th>{{ __('الحالة') }}</th>
                        <th>{{ __('تاريخ اخر رد') }}</th>
                        <th>{{ __('مسؤل الشكوى') }}</th>
                        <th>{{ __('الاجراءات') }}</th>
                    </thead>
                    <tbody>
                        <!-- row -->
                        @foreach ($client->complaints as $key => $row)
                            <tr class="search-items">
                                <td>
                                    <div class="text-truncate">{{ $row->address }}</div>
                                </td>
                                <td>
                                    <div class="text-truncate">{{ $row->message }}</div>
                                </td>

                                <td>
                                    <?php
                                    
                                    if ($row->complaint_from == 1) {
                                        $complaint_from = 'مكتب العمل بالفلبين';
                                    } elseif ($row->complaint_from == 2) {
                                        $complaint_from = __('السفارة بالخبر');
                                    } elseif ($row->complaint_from == 3) {
                                        $complaint_from = __('مكتب الفلبين');
                                    } elseif ($row->complaint_from == 4) {
                                        $complaint_from = __('الكفيل');
                                    } elseif ($row->complaint_from == 5) {
                                        $complaint_from = __('العاملة');
                                    } else {
                                        $complaint_from = __('الكفيل');
                                    }
                                    ?>
                                    <div class="text-truncate">{{ $complaint_from }}</div>
                                </td>
                                <td>{{ date('d-m-Y', strtotime($row->created_at)) }}</td>
                                <?php
                                if ($row->status == 'pending') {
                                    $status = '<span class="badge bg-orange fw-normal">' . __('معلقة') . '</span>';
                                } elseif ($row->status == 'open') {
                                    $status = '<span class="badge bg-light-info  fw-normal" >' . __('مفتوحة') . '</span>';
                                } else {
                                    $status = '<span   class="badge bg-success  fw-normal" >' . __('تم الحل') . '</span>';
                                }
                                ?>
                                <td>{{ $status }}</td>
                                <?php
                                if (count($row->ticketreplies) > 0) {
                                    $ticketreplies = $row->ticketreplies->last()->created_at;
                                } else {
                                    $ticketreplies = $row->created_at;
                                }
                                ?>
                                <td>{{ $ticketreplies }}</td>
                                <td>{{ $row->admin ? $row->admin->name : 'لم يتم الاسناد' }}</td>
                                <td>
                                    <?php
                                    // $actions = '<a href="' . route('support_ticket.admin_show', encrypt($row->id)) . '"  class="dropdown-item">' . __('فتح الشكوى') . '</a>';
                                    ?>
                                    {!! $actions !!}

                                </td>

                            </tr>
                            <!-- /.row -->
                        @endforeach
                    </tbody>
                </table>
                <!--/span-->
            </div>
            <br />
        </div>

        <div class="card">
            <h4 class="card-title">{{ __('رسايل العميل') }}</h4>

            <div class="card-body table-responsive  table-sm ">
                <table class="table  ">
                    <thead class="bg-success text-white">
                        <th>
                            #
                        </th>
                        <th>{{ __('اسم العميل') }}</th>
                        <th>{{ __('التاريخ') }}</th>
                        <th>{{ __('الرسالة') }}</th>

                    </thead>
                    <tbody>
                        @foreach ($client->messages as $key => $val)
                            <tr class="search-items">
                                <td>{{ $key + 1 }}</td>
                                <td>
                                    <div class="text-truncate">{{ $client->name }}</div>
                                </td>
                                <td>{{ date('d-m-Y', strtotime($val->created_at)) }}</td>
                                <td>{{ $val->message }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <br />

        </div>
    </div>
@endsection
