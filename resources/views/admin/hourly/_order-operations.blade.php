@php
/* لكل إجراء: العنوان، الأيقونة، ولون الأيقونة في شبكة الاختيار. */
$operations=[
    'reschedule'=>['إعادة جدولة جماعية','fa-calendar-days','indigo'],
    'start_time'=>['تغيير وقت البدء','fa-person-walking','violet'],
    'duration'=>['تغيير المدة','fa-clock','blue'],
    'price'=>['تغيير السعر','fa-dollar-sign','amber'],
    'assign_workers'=>['اختيار العاملات','fa-user-check','teal'],
    'assign_all'=>['تعيين العاملات تلقائيًا','fa-right-left','teal'],
    'add_worker'=>['إضافة عامل','fa-user-plus','green'],
    'remove_worker'=>['إزالة عامل وتقليل العدد','fa-user-minus','red'],
    'unassign_worker'=>['إلغاء تعيين عامل','fa-user-xmark','red'],
    'assign_driver'=>['تعيين السائق','fa-car','teal'],
    'team_leader'=>['قائد الفريق','fa-users','green'],
    'free_visit'=>['إضافة زيارة مجانية','fa-circle-plus','blue'],
    'unassign_drivers'=>['إلغاء تعيين السائقين','fa-xmark','red'],
    'unassign_workers'=>['إلغاء تعيين العاملات','fa-xmark','red'],
    'payment_method'=>['تغيير نوع الدفع','fa-wallet','amber'],
    'complete'=>['إكمال الزيارة','fa-circle-check','green'],
    'cancel_visit'=>['إلغاء الزيارة','fa-calendar-xmark','red'],
    'cancel_order'=>['إلغاء الطلب','fa-ban','red'],
];
$operations=array_filter($operations,fn($op,$action)=>!in_array($action,['price','duration','free_visit','add_worker','remove_worker']) || AdminGuardHasPerm('hourly_orders_price_override'),ARRAY_FILTER_USE_BOTH);
@endphp
@if(AdminGuardHasPerm('hourly_orders_create') && in_array($order->status,['pending_confirmation','active']))
@include('admin.hourly._modal-theme')
@include('admin.hourly._visit-edit-modal')
@include('admin.hourly._add-worker-modal')
@include('admin.hourly._team-leader-modal')
@include('admin.hourly._schedule-modal')
<div class="d-flex gap-3 flex-wrap align-items-center my-3">
<label><input type="checkbox" id="select-all-visits"> <i class="fa fa-list-check"></i> تحديد كل الزيارات القابلة للتعديل</label>
<button class="btn btn-primary" type="button" id="open-operations-picker"><i class="fa fa-gears ms-1"></i> الإجراءات</button>
<span class="text-muted" id="selected-visits-count" aria-live="polite"></span>
</div>
<div class="modal fade om-modal" id="order-operation-modal" data-op-step="pick" tabindex="-1" aria-labelledby="operation-modal-title" aria-hidden="true" dir="{{ app()->getLocale()==='ar'?'rtl':'ltr' }}"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content">
<div class="modal-header"><span class="om-m-icon"><i class="fa fa-gears" id="operation-modal-icon"></i></span><div><h5 id="operation-modal-title">الإجراءات</h5><p><i class="fa fa-hashtag"></i> {{ $order->order_number }}</p></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button></div>
<form id="order-operation-form" data-workers-url="{{ route('admin.hourly-orders.available-workers',[$order,'VISIT_ID']) }}" data-drivers-url="{{ route('admin.hourly-orders.available-drivers',$order) }}" method="POST" action="{{ route('admin.hourly-orders.operations',$order) }}">@csrf
<div class="modal-body">
<p id="operation-target" class="alert alert-info">يطبق الإجراء على الزيارات المحددة.</p>
<input type="hidden" id="operation-action" name="action" value="{{ array_key_first($operations) }}">
<div class="om-op-grid" data-op-picker>
@foreach($operations as $action=>[$label,$icon,$tone])<button type="button" class="om-op-tile om-op-{{ $tone }}" data-pick-action="{{ $action }}" data-label="{{ $label }}" data-icon="{{ $icon }}"><i class="fa {{ $icon }}"></i><span>{{ $label }}</span></button>
@endforeach
</div>
<div data-op-form>
<div data-op-fields="assign_workers"><p id="operation-workers-context" aria-live="polite"></p><div id="operation-worker-choices" class="d-grid gap-2"></div><small>اختر زيارة واحدة لتحديد عاملاتها. لا يغيّر الاختيار عدد العاملات المحجوز أو سعر الزيارة.</small></div>
<div data-op-fields="cancel_visit cancel_order">
<label>استرداد المبلغ بعد الإلغاء</label><select name="refund_method" class="form-select"><option value="wallet">إلى محفظة العميل — بدون رسوم إلغاء</option><option value="cash">تسجيل استرداد نقدي منفّذ — بدون رسوم إلغاء</option><option value="bank_transfer">تسجيل تحويل بنكي منفّذ — بدون رسوم إلغاء</option><option value="none">بدون استرداد — احتساب قيمة الزيارة كرسوم إلغاء</option></select>
<label class="d-block mt-2"><input type="checkbox" name="external_refund_confirmed" value="1"> للنقد أو التحويل: أؤكد صرف المبلغ المستحق للعميل فعليًا.</label>
<p class="small text-muted">يُرد فقط ما يزيد من المدفوع عن قيمة الزيارات المتبقية والرسوم.</p>
</div>
<div data-op-fields="reschedule"><label><i class="fa fa-calendar-days"></i> إزاحة المواعيد بعدد أيام <small>(سالب للتقديم)</small></label><input class="form-control" type="number" name="shift_days" min="-365" max="365" value="7"></div>
<div data-op-fields="start_time"><label><i class="fa fa-clock"></i> وقت البداية الجديد</label><input class="form-control" type="time" name="start_time"></div>
<div data-op-fields="duration"><label><i class="fa fa-hourglass-half"></i> مدة الزيارة بالساعات</label><input class="form-control" type="number" name="hours" step="0.25" min="0.5" max="24"><small>يتغير سعر الزيارة بنسبة تغير المدة.</small></div>
<div data-op-fields="price"><label><i class="fa fa-tag"></i> السعر الجديد لكل زيارة <small>(شاملة الضريبة — ر.س)</small></label><input class="form-control" type="number" name="price" min="0" max="9999999" step="0.01"></div>
<div data-op-fields="add_worker remove_worker unassign_worker"><label><i class="fa fa-user-nurse"></i> العاملة</label><select class="form-select" name="worker_id"><option value="">اختر</option>@foreach($workers as $worker)<option value="{{ $worker->id }}">#{{ $worker->id }} — {{ $worker->name }}</option>@endforeach</select><small>إضافة عاملة فوق العدد المحجوز تزيد السعر بنسبة زيادة العدد. إلغاء التعيين يحتفظ بمقعد الزيارة.</small></div>
<div data-op-fields="assign_driver"><div id="driver-visit-schedule" class="alert alert-info mb-3" aria-live="polite"></div><label><i class="fa fa-truck"></i> السائق</label><select class="form-select" name="driver_id"><option value="">???? ??? ?????? ???? ???????? ????????</option></select><label><i class="fa fa-route"></i> نوع المهمة</label><select name="driver_role" class="form-select"><option value="delivery">توصيل — Drop Off</option><option value="pickup">استلام — Pick Up</option></select><label id="driver-time-label"><i class="fa fa-clock"></i> وقت التوصيل</label><input type="time" class="form-control" name="driver_time" aria-labelledby="driver-time-label"><small>يمكن تعديل وقت المهمة حسب حركة السائق. لا يغيّر ذلك موعد بداية عمل العاملة.</small></div>
<div data-op-fields="team_leader"><label><i class="fa fa-user-tie"></i> قائد الفريق</label><select name="leader_id" class="form-select"><option value="">اختر</option>@foreach($drivers as $driver)<option value="{{ $driver->id }}">{{ $driver->name }}</option>@endforeach</select><small>يجب أن يكون من قادة فريق الزيارة.</small></div>
<div data-op-fields="free_visit"><label><i class="fa fa-gift"></i> موعد الزيارة المجانية</label><input type="datetime-local" name="starts_at" class="form-control" step="900" min="{{ now()->copy()->addMinutes(15-now()->minute%15)->second(0)->format('Y-m-d\TH:i') }}">
<p class="small text-muted">تُنسخ تعيينات آخر زيارة غير ملغاة، وتُفحص العاملات والسائقون قبل الحفظ. المدة وعدد العاملات حسب الطلب.</p>
<label><input type="checkbox" name="allow_unassigned" value="1"> إضافة بدون تعيين إذا كانت العاملة غير متاحة</label>
@if(AdminGuardHasPerm('hourly_orders_notice_override'))<label><input type="checkbox" name="ignore_notice" value="1"> تجاوز مهلة الحجز (يسجل باسم الموظف)</label>@endif
</div>
<div data-op-fields="payment_method"><label><i class="fa fa-wallet"></i> طريقة الدفع</label><select name="payment_method" class="form-select">@foreach($paymentMethods as $method)<option value="{{ $method }}" {{ $order->payment_method === $method ? 'selected' : '' }}>{{ __('hourly.'.$method) }}</option>@endforeach</select><small>التغيير يخص الدفعات القادمة؛ يحتفظ سجل الدفعات بطرق التحصيل السابقة. عند التغيير من ميسر يُلغى رابط الدفع المعلّق أولًا حتى لا يمكن استخدامه لاحقًا.</small></div>
<label class="mt-3" for="operation-reason"><i class="fa fa-pen"></i> سبب التعديل <small>(اختياري — يُسجَّل في السجلات)</small></label><textarea id="operation-reason" name="reason" class="form-control" maxlength="2000"></textarea>
</div>
<div id="operation-error" role="alert" class="alert alert-danger mt-3" hidden></div>
</div><div class="modal-footer" data-op-form><button class="btn btn-primary" id="operation-save"><i class="fa fa-circle-check"></i> تحقق وتنفيذ</button><button type="button" class="btn btn-light" id="operation-back"><i class="fa fa-arrow-right"></i> كل الإجراءات</button><button type="button" class="btn btn-light" data-bs-dismiss="modal"><i class="fa fa-xmark"></i> إغلاق</button></div>
</form></div></div></div>
@endif
