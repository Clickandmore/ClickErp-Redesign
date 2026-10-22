{{-- عودة معلّقة: العاملة ممنوعة من الحجز لأن عودتها من زيارة لم تُسجَّل. يُعرض في رأس ملف العاملة فقط. --}}
@php
    $holdVisit = $returnHold['visit'] ?? null;
    $holdEnded = $returnHold['ended_at'] ? \Carbon\Carbon::parse($returnHold['ended_at']) : null;
    $holdClosed = $holdVisit && in_array($holdVisit->status, \App\Services\Hourly\VisitSearch::CLOSED, true);
@endphp
<style>
    .rh { position: relative; border: 1px solid #f3d29a; border-radius: 18px; background: linear-gradient(180deg, #fffaf0 0%, #fff 70%); box-shadow: 0 10px 30px rgba(180, 83, 9, .08); overflow: hidden; margin-bottom: 1.25rem; }
    .rh::before { content: ''; position: absolute; inset-block: 0; inset-inline-start: 0; width: 5px; background: linear-gradient(180deg, #f59e0b, #d97706); }
    .rh-head { display: flex; align-items: center; gap: .9rem; padding: 1.1rem 1.35rem .9rem; flex-wrap: wrap; }
    .rh-icon { width: 48px; height: 48px; flex: 0 0 48px; border-radius: 14px; display: grid; place-items: center; font-size: 1.25rem; color: #fff; background: linear-gradient(135deg, #f59e0b, #d97706); box-shadow: 0 6px 16px rgba(217, 119, 6, .3); }
    .rh-title { flex: 1; min-width: 220px; }
    .rh-title h5 { margin: 0; font-size: 1.05rem; font-weight: 800; color: #7c4a03; }
    .rh-title p { margin: .2rem 0 0; font-size: .84rem; color: #8a6a3a; }
    .rh-badge { display: inline-flex; align-items: center; gap: .4rem; border-radius: 99px; padding: .3rem .8rem; font-size: .78rem; font-weight: 800; background: #fdeeee; color: #b42a2e; border: 1px solid #f5c2c4; }
    .rh-badge i { font-size: .7rem; }
    .rh-facts { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: .6rem; padding: 0 1.35rem 1rem; }
    .rh-fact { background: #fff; border: 1px solid #f1e3c8; border-radius: 12px; padding: .6rem .8rem; display: flex; gap: .6rem; align-items: center; }
    .rh-fact i { width: 32px; height: 32px; flex: 0 0 32px; border-radius: 9px; display: grid; place-items: center; background: #fff4e0; color: #b45309; font-size: .85rem; }
    .rh-fact span { display: block; font-size: .72rem; font-weight: 700; color: #8a7a62; }
    .rh-fact b { display: block; font-size: .9rem; color: #3b2a12; }
    .rh-fact a { color: #1b6fb8; font-weight: 800; text-decoration: none; }
    .rh-fact a:hover { text-decoration: underline; }
    .rh-body { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1.25fr); gap: 1rem; padding: 1rem 1.35rem 1.25rem; border-top: 1px dashed #f1dcb4; }
    @media (max-width: 991px) { .rh-body { grid-template-columns: 1fr; } }
    .rh-steps { list-style: none; margin: 0; padding: 0; display: grid; gap: .55rem; align-content: start; }
    .rh-steps li { display: flex; gap: .65rem; align-items: flex-start; font-size: .86rem; color: #4b3a20; line-height: 1.7; }
    .rh-steps li b { color: #7c4a03; }
    .rh-num { width: 24px; height: 24px; flex: 0 0 24px; border-radius: 50%; display: grid; place-items: center; background: #fff4e0; color: #b45309; font-size: .76rem; font-weight: 800; border: 1px solid #f3d29a; margin-top: .1rem; }
    .rh-safe { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .3rem; }
    .rh-safe span { display: inline-flex; align-items: center; gap: .35rem; font-size: .74rem; font-weight: 700; color: #0f7a5a; background: #e3f6f1; border-radius: 99px; padding: .2rem .65rem; }
    .rh-form { background: #fff; border: 1px solid #ecdcbf; border-radius: 14px; padding: .95rem 1rem; display: grid; gap: .75rem; }
    .rh-confirm { display: flex; gap: .65rem; align-items: center; margin: 0; padding: .7rem .85rem; border: 1.5px solid #e6ecf3; border-radius: 12px; cursor: pointer; font-weight: 700; font-size: .88rem; color: #16212e; transition: border-color .15s, background .15s; }
    .rh-confirm:hover { border-color: #f3c27a; }
    .rh-confirm:has(input:checked) { border-color: #068a7d; background: #e3f6f1; color: #0f5e55; }
    .rh-confirm input { width: 19px; height: 19px; flex: 0 0 19px; margin: 0; accent-color: #068a7d; cursor: pointer; }
    .rh-label { display: block; font-size: .8rem; font-weight: 800; color: #4b3a20; margin-bottom: .3rem; }
    .rh-input { position: relative; }
    .rh-input i { position: absolute; inset-inline-start: .8rem; top: 50%; transform: translateY(-50%); color: #b8a37f; font-size: .85rem; }
    .rh-input input { width: 100%; height: 44px; border: 1px solid #e3d6bd; border-radius: 11px; padding-inline: 2.2rem .8rem; font-size: .88rem; background: #fffdf9; }
    .rh-input input:focus { outline: 0; border-color: #f59e0b; box-shadow: 0 0 0 4px rgba(245, 158, 11, .15); background: #fff; }
    .rh-submit { height: 46px; border: 0; border-radius: 12px; font-weight: 800; font-size: .92rem; color: #fff; background: linear-gradient(135deg, #068a7d, #00ac9a); display: inline-flex; align-items: center; justify-content: center; gap: .5rem; box-shadow: 0 8px 18px rgba(6, 138, 125, .25); transition: filter .15s, opacity .15s; }
    .rh-submit:hover:not(:disabled) { filter: brightness(1.06); }
    .rh-submit:disabled { opacity: .45; box-shadow: none; cursor: not-allowed; }
    .rh-locked { display: flex; gap: .5rem; align-items: center; font-size: .84rem; color: #6b7a8c; background: #f6f9fc; border-radius: 12px; padding: .8rem 1rem; }
</style>

<section class="rh" aria-labelledby="rh-title">
    <header class="rh-head">
        <span class="rh-icon" aria-hidden="true"><i class="fa fa-house-circle-exclamation"></i></span>
        <div class="rh-title">
            <h5 id="rh-title">عودة العاملة للإيواء لم تُسجَّل</h5>
            <p>{{ $holdClosed ? 'الزيارة مغلقة، لكن عودة العاملة منها لم تُؤكَّد بعد.' : 'انتهى موعد الزيارة ولم تُؤكَّد عودة العاملة منها.' }}</p>
        </div>
        <span class="rh-badge" role="status"><i class="fa fa-circle"></i> غير متاحة للحجز</span>
    </header>

    <div class="rh-facts">
        <div class="rh-fact"><i class="fa fa-calendar-check" aria-hidden="true"></i><div>
            <span>الزيارة</span>
            <b>#{{ $returnHold['visit_id'] }}@if($holdVisit?->order) · <a href="{{ route('admin.hourly-orders.show', $holdVisit->hourly_rental_order_id) }}">طلب {{ $holdVisit->order->order_number }}</a>@endif</b>
        </div></div>
        <div class="rh-fact"><i class="fa fa-clock" aria-hidden="true"></i><div>
            <span>انتهت الزيارة</span>
            <b><bdi dir="ltr">{{ $holdEnded?->format('Y-m-d H:i') ?? '—' }}</bdi></b>
        </div></div>
        <div class="rh-fact"><i class="fa fa-hourglass-half" aria-hidden="true"></i><div>
            <span>مدة التعليق</span>
            <b>{{ $holdEnded ? $holdEnded->locale('ar')->diffForHumans(null, true) : '—' }}</b>
        </div></div>
        <div class="rh-fact"><i class="fa fa-signal" aria-hidden="true"></i><div>
            <span>حالة الزيارة</span>
            <b>{{ $holdVisit ? (\App\Services\Hourly\VisitSearch::STATUSES[$holdVisit->display_status] ?? $holdVisit->status) : 'محذوفة' }}</b>
        </div></div>
    </div>

    <div class="rh-body">
        <div>
            <ol class="rh-steps">
                <li><span class="rh-num">1</span><span><b>تحقّق من وجود العاملة في الإيواء</b> مع المشرف أو من سجل الاستلام.</span></li>
                <li><span class="rh-num">2</span><span><b>أكّد التحقق واكتب مرجعه</b>؛ يُحفظ باسمك في سجل العاملة وسجل الطلب.</span></li>
                <li><span class="rh-num">3</span><span><b>يُرفع منع الحجز</b> وتعود العاملة متاحة للزيارات الجديدة فورًا.</span></li>
            </ol>
            <div class="rh-safe" aria-label="ما لا يتغير">
                <span><i class="fa fa-lock"></i> حالة الزيارة كما هي</span>
                <span><i class="fa fa-lock"></i> المستحقات كما هي</span>
            </div>
        </div>

        @if($canReturn)
            <form method="post" action="{{ route('admin.hourly-maids.return-from-visit', [$maid, $returnHold['visit_id']]) }}" class="rh-form" id="rh-form">
                @csrf
                <label class="rh-confirm"><input type="checkbox" name="confirmed_return" value="1" required id="rh-confirm"> تحققت من وصول العاملة فعليًا إلى الإيواء</label>
                <div>
                    <label class="rh-label" for="rh-reason">سبب / مرجع التحقق</label>
                    <div class="rh-input"><i class="fa fa-pen" aria-hidden="true"></i><input id="rh-reason" name="reason" maxlength="1000" required placeholder="مثال: تأكيد مشرف الإيواء وسجل الاستلام"></div>
                </div>
                <button class="rh-submit" type="submit" id="rh-submit" disabled><i class="fa fa-house-circle-check"></i> تسجيل العودة للإيواء</button>
            </form>
            <script>
                (() => {
                    const check = document.getElementById('rh-confirm'), reason = document.getElementById('rh-reason'), submit = document.getElementById('rh-submit');
                    const sync = () => { submit.disabled = !check.checked || !reason.value.trim(); };
                    check.addEventListener('change', sync); reason.addEventListener('input', sync);
                })();
            </script>
        @else
            <div class="rh-locked"><i class="fa fa-lock" aria-hidden="true"></i> تسجيل العودة يتطلب صلاحية تعديل العاملات.</div>
        @endif
    </div>
</section>
