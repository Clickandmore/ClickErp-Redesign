@include('admin.hourly._modal-theme')
@php
    /* بطاقات الإجراءات مجمّعة حسب نوعها: [العنوان، الأيقونة، اللون، وصف قصير]. */
    $actionGroups = [
        'التعيين' => ['assign_all' => ['fa-wand-magic-sparkles', 'teal', 'حسب التوفر والفريق'], 'auto_driver' => ['fa-route', 'teal', 'توصيل واستلام'], 'assign_driver' => ['fa-car', 'blue', 'سائق محدد لمهمة'], 'team_leader' => ['fa-user-tie', 'green', 'من قادة الفريق'], 'offer' => ['fa-bullhorn', 'violet', 'إظهار في بوابة العاملات']],
        'المواعيد' => ['reschedule' => ['fa-calendar-days', 'indigo', 'تاريخ جديد أو إزاحة'], 'start_time' => ['fa-clock', 'indigo', 'مع الإبقاء على التاريخ'], 'duration' => ['fa-hourglass-half', 'blue', 'يُعدَّل السعر تبعًا لها']],
        'المال' => ['collect' => ['fa-money-bill-wave', 'green', 'تحصيل موزّع على الزيارات'], 'price' => ['fa-tag', 'amber', 'سعر جديد لكل زيارة']],
        'الإزالة' => ['unassign_all' => ['fa-users-slash', 'red', 'العاملات والسائقون'], 'unassign_drivers' => ['fa-car-burst', 'red', 'السائقون فقط'], 'clear_driver_routes' => ['fa-road-circle-xmark', 'red', 'مسارات السائقين']],
        'الإغلاق والملاحظات' => ['confirm_return' => ['fa-house', 'amber', 'تأكيد الوصول الفعلي مع تسجيل السبب'], 'complete' => ['fa-circle-check', 'green', 'تسجيل التنفيذ'], 'cancel_visit' => ['fa-ban', 'red', 'مع خيارات الاسترداد'], 'note' => ['fa-note-sticky', 'violet', 'تُسجَّل على الزيارات']],
    ];
    $actionGroups['التعيين']['assign_workers']=['fa-user-plus','blue','اختيار العاملات المتاحات ومعاينة التعيين'];
    $actionGroups['الإزالة']['unassign_workers']=['fa-users-slash','red','إلغاء تعيين العاملات فقط'];
    $actionGroups['الإزالة']['unassign_driver']=['fa-car','red','إزالة التوصيل أو الاستلام'];
    $allowed = fn ($action) => ($action === 'collect' && $canCollect) || ($action !== 'collect' && $canEdit && (!in_array($action, ['price', 'duration']) || $canPrice));
    $labels = \App\Services\Hourly\VisitBatchService::ACTIONS;
@endphp
<div class="modal fade om-modal" id="vl-modal" data-op-step="pick" tabindex="-1" aria-labelledby="vl-modal-title" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered"><form class="modal-content" id="vl-batch-form" enctype="multipart/form-data">
@csrf<input type="hidden" name="batch_id">
<div class="modal-header"><span class="om-m-icon"><i class="fa fa-gears" id="vl-modal-icon"></i></span><div><h5 id="vl-modal-title">الإجراءات</h5><p id="vl-modal-sub">اختر الإجراء لتطبيقه على الزيارات المحددة</p></div><button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="إغلاق"></button></div>
<div class="modal-body"><p id="vl-target" class="alert alert-info"></p>
<input type="hidden" id="vl-action" name="action" value="">
<div data-op-picker>
@foreach($actionGroups as $group => $actions)
    @php $visible = array_filter($actions, fn ($a) => isset($labels[$a]) && $allowed($a), ARRAY_FILTER_USE_KEY); @endphp
    @if($visible)
    <section class="om-op-section"><h6>{{ $group }}</h6><div class="om-op-grid">
        @foreach($visible as $action => [$icon, $tone, $hint])<button type="button" class="om-op-tile om-op-{{ $tone }}" data-pick-action="{{ $action }}" data-label="{{ $labels[$action] }}" data-icon="{{ $icon }}"><i class="fa {{ $icon }}"></i><span>{{ $labels[$action] }}<small>{{ $hint }}</small></span></button>@endforeach
    </div></section>
    @endif
@endforeach
</div>
<div data-op-form>
@foreach(['assign_workers', 'assign_all'] as $assignmentAction)
<div data-fields="{{ $assignmentAction }}" class="alert alert-info" role="note">
    <i class="fa fa-circle-info" aria-hidden="true"></i>
    <strong>التعيين متاح قبل بدء موعد الزيارة فقط.</strong>
    لا تُطبّق مهلة حجز جديدة على زيارة محجوزة، ويُتحقق من توفر العاملات وعدم تعارض مواعيدهن.
    إذا بدأ موعد الزيارة أو انتهى، اختر زيارة قادمة؛ ولتصحيح سجل سابق راجع مسؤول التشغيل.
</div>
@endforeach
<div data-fields="assign_workers" class="vl-modal-group"><p id="vl-worker-context"></p><div id="vl-worker-choices"></div><p class="small text-muted">الاختيار يمثل التعيين النهائي للزيارة. لا يغير عدد العاملات المطلوب أو السعر.</p></div>
<div data-fields="unassign_driver" class="vl-modal-group"><label>المهمة المطلوب إزالتها</label><select name="remove_driver_role" class="form-control"><option value="delivery">التوصيل</option><option value="pickup">الاستلام</option></select></div>
<div data-fields="reschedule" class="vl-modal-group"><label>التاريخ الجديد لجميع الزيارات المحددة</label><input class="form-control" type="date" name="new_date"><label>أو تغيير التاريخ بعدد الأيام (+ للتأخير / − للتقديم)</label><input class="form-control" type="number" name="shift_days" min="-365" max="365"><small>تحتفظ كل زيارة بوقت بدايتها. أدخل تاريخًا جديدًا أو عدد الأيام.</small></div>
<div data-fields="start_time" class="vl-modal-group"><label>وقت البدء الجديد</label><input type="time" name="start_time" class="form-control"></div>
<div data-fields="duration" class="vl-modal-group"><label>المدة الجديدة بالساعات</label><input class="form-control" name="hours" type="number" min="0.5" max="24" step="0.25"><label><input type="checkbox" name="ignore_break" value="1"> تجاهل الفاصل بين الزيارات</label><small>يُعدَّل السعر بنسبة المدة الجديدة.</small></div>
<div data-fields="price" class="vl-modal-group"><label>السعر الجديد لكل زيارة شامل الضريبة</label><input class="form-control" type="number" name="price" min="0" step="0.01"></div>
<div data-fields="auto_driver" class="vl-modal-group"><label>اتجاه تعيين السائق</label><select name="driver_direction" class="form-control"><option value="together">التوصيل والاستلام معًا</option><option value="delivery">التوصيل فقط</option><option value="pickup">الاستلام فقط</option></select></div>
<div data-fields="assign_driver" class="vl-modal-group"><label>السائق</label><select name="driver_id" class="form-control"><option value="">اختر</option>@foreach($drivers as $driver)<option value="{{ $driver->id }}">{{ $driver->name }}</option>@endforeach</select><label>المهمة</label><select name="driver_role" class="form-control"><option value="delivery">توصيل</option><option value="pickup">استلام</option></select><label>وقت المهمة</label><input name="driver_time" type="time" class="form-control"></div>
<div data-fields="team_leader" class="vl-modal-group"><label>قائد الفريق</label><select name="leader_id" class="form-control"><option value="">اختر</option>@foreach($drivers as $driver)<option value="{{ $driver->id }}">{{ $driver->name }}</option>@endforeach</select><small>يجب أن يكون قائدًا مفعّلًا في فريق كل زيارة محددة.</small></div>
<div data-fields="offer" class="vl-modal-group"><label>عرض الزيارة للعاملات المؤهلات</label><select name="offered" class="form-control"><option value="1">إظهار</option><option value="0">إخفاء</option></select></div>
<div data-fields="cancel_visit" class="vl-modal-group"><label><input type="checkbox" name="external_refund_confirmed" value="1"> للنقد أو التحويل: أؤكد صرف المبلغ المستحق للعميل فعليًا.</label><label>طريقة الاسترداد</label><select name="refund_method" class="form-control">@if($canCollect)<option value="wallet">إلى رصيد العميل</option><option value="cash">نقدًا</option><option value="bank_transfer">تحويل بنكي</option>@endif<option value="none">بدون استرداد (تُحتسب قيمة الزيارة كرسوم إلغاء)</option></select><p class="small text-muted">يُرد فقط ما يزيد من السداد على قيمة الطلب بعد الإلغاء. النقد والتحويل يسجّلان استردادًا منفذًا خارج النظام؛ لا يرسلان تحويلًا بنكيًا.</p><label><input type="checkbox" name="notify_app" value="1"> تبليغ بالتطبيق</label><label><input type="checkbox" name="notify_email" value="1"> تبليغ بالإيميل</label></div>
<div data-fields="collect" class="vl-modal-group"><label>نوع الدفع</label><select name="method" class="form-control"><option value="cash">نقدًا</option><option value="card_on_delivery">بطاقة</option><option value="bank_transfer">تحويل بنكي</option><option value="cheque">شيك</option></select><label>إجمالي المبلغ المستلم لجميع الزيارات المحددة</label><input type="number" name="amount" min="0.01" step="0.01" class="form-control"><label>موعد الدفع</label><input type="datetime-local" name="paid_at" value="{{ now()->format('Y-m-d\TH:i') }}" class="form-control"><label>إثبات التحويل البنكي</label><input type="file" name="attachment" class="form-control" accept=".jpg,.jpeg,.png,.pdf"><label><input type="checkbox" name="send_receipt" value="1"> إرسال إيصال موحد لكل عميل بالبريد</label></div>
<label class="mt-3" for="vl-reason"><i class="fa fa-pen"></i> السبب / وصف الدفعة / نص المذكرة</label><textarea id="vl-reason" class="form-control" name="reason" maxlength="2000" required rows="3"></textarea><div id="vl-error" class="alert alert-danger mt-3" role="alert" hidden></div>
<p class="small text-muted mt-3">يُحفظ الإجراء كاملًا أو يُرفض كاملًا عند وجود تعارض. إرسال الإشعارات يتم عبر مجدول النظام بعد الحفظ.</p>
</div></div><div class="modal-footer" data-op-form><button class="btn btn-primary" id="vl-submit"><i class="fa fa-circle-check"></i> تحقق وتنفيذ</button><button type="button" class="btn btn-light" id="vl-back"><i class="fa fa-arrow-right"></i> كل الإجراءات</button><button type="button" class="btn btn-light" data-bs-dismiss="modal"><i class="fa fa-xmark"></i> إغلاق</button></div>
</form></div></div>
