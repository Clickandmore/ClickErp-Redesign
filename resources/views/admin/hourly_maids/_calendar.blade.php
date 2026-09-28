{{-- تقويم العاملة (شهر/أسبوع): الزيارات ملوّنة حسب حالتها، مع الإجازات وأيام الراحة، ولكل يوم في عرض الأسبوع الحد الأقصى لساعات العمل والمستهلك منها. --}}
<div class="mp-cal" id="mp-cal" data-feed="{{ route('admin.hourly-maids.calendar', $maid) }}">
    <div class="mp-cal-bar">
        <div class="mp-cal-nav">
            <button type="button" class="mp-cal-btn" data-cal="prev" aria-label="السابق"><i class="fa fa-chevron-right"></i></button>
            <button type="button" class="mp-cal-btn mp-cal-today" data-cal="today">اليوم</button>
            <button type="button" class="mp-cal-btn" data-cal="next" aria-label="التالي"><i class="fa fa-chevron-left"></i></button>
            <h5 class="mp-cal-title" id="mp-cal-title" aria-live="polite">…</h5>
        </div>
        <div class="mp-cal-views" role="group" aria-label="طريقة العرض">
            <button type="button" class="mp-cal-view is-active" data-view="dayGridMonth" aria-pressed="true">شهر</button>
            <button type="button" class="mp-cal-view" data-view="timeGridWeek" aria-pressed="false">أسبوع</button>
        </div>
    </div>
    <ul class="mp-cal-legend">
        @foreach(['assigned' => 'مُسندة', 'partial' => 'إسناد جزئي', 'progress' => 'قيد التنفيذ', 'completed' => 'مكتملة', 'cancelled' => 'ملغاة', 'rest' => 'الفاصل', 'leave' => 'إجازة', 'off' => 'يوم راحة'] as $key => $label)
            <li><span class="mp-sw mp-sw-{{ $key }}"></span>{{ $label }}</li>
        @endforeach
    </ul>
    <div class="mp-cal-wrap">
        <div id="mp-cal-el"></div>
        <div class="mp-cal-busy" id="mp-cal-busy" hidden><span class="mp-spinner"></span></div>
    </div>
    <p class="mp-cal-error" id="mp-cal-error" role="alert" hidden>تعذّر تحميل مواعيد العاملة.</p>
</div>
<style>
    .mp-cal-bar { display: flex; align-items: center; justify-content: space-between; gap: .75rem; flex-wrap: wrap; margin-bottom: .9rem; }
    .mp-cal-nav { display: flex; align-items: center; gap: .4rem; flex-wrap: wrap; }
    .mp-cal-btn { height: 40px; min-width: 40px; padding: 0 .8rem; border: 1px solid var(--line); border-radius: 11px; background: #fff; color: var(--ink); font-weight: 600; font-size: .85rem; }
    .mp-cal-btn:hover { border-color: var(--brand); color: var(--brand-ink); background: #f7fbff; }
    .mp-cal-title { margin: 0 .5rem; font-size: 1.1rem; font-weight: 700; color: var(--ink); }
    .mp-cal-views { display: inline-flex; padding: .25rem; background: var(--soft); border: 1px solid var(--line); border-radius: 12px; }
    .mp-cal-view { border: 0; background: none; border-radius: 9px; padding: .45rem 1.1rem; font-weight: 700; font-size: .85rem; color: var(--muted); }
    .mp-cal-view.is-active { background: var(--brand-ink); color: #fff; box-shadow: 0 4px 12px rgba(27, 111, 184, .25); }
    .mp-cal-legend { list-style: none; margin: 0 0 1rem; padding: 0; display: flex; flex-wrap: wrap; gap: .45rem 1.1rem; font-size: .8rem; color: var(--muted); }
    .mp-cal-legend li { display: inline-flex; align-items: center; gap: .4rem; }
    .mp-sw { width: 14px; height: 14px; border-radius: 4px; border: 1px solid rgba(0, 0, 0, .08); }
    .mp-sw-assigned, .mp-ev-assigned { --ev-bg: #fef6d8; --ev-bd: #e9b949; --ev-fg: #7a5a06; }
    .mp-sw-partial, .mp-ev-partial { --ev-bg: #fde6c8; --ev-bd: #e08a17; --ev-fg: #8a4b06; }
    .mp-sw-progress, .mp-ev-progress { --ev-bg: #e1f0fd; --ev-bd: #2794eb; --ev-fg: #14507f; }
    .mp-sw-completed, .mp-ev-completed { --ev-bg: #dcf5e9; --ev-bd: #17996f; --ev-fg: #0f6b4e; }
    .mp-sw-cancelled, .mp-ev-cancelled { --ev-bg: #eef1f5; --ev-bd: #9fb0c2; --ev-fg: #5a6b7d; }
    .mp-sw { background: var(--ev-bg); border-color: var(--ev-bd); }
    .mp-sw-rest { background: #8b96a3; }
    .mp-sw-leave { background: #d946a8; }
    .mp-sw-off { background: #dc2626; }

    .mp-cal-wrap { position: relative; }
    .mp-cal-busy { position: absolute; inset: 0; display: grid; place-items: center; background: rgba(255, 255, 255, .6); z-index: 5; border-radius: 14px; }
    .mp-cal-error { margin: .75rem 0 0; color: #b42a2e; font-size: .85rem; }
    #mp-cal-el { --fc-border-color: var(--line); --fc-today-bg-color: #f2f8fe; --fc-neutral-bg-color: var(--soft); --fc-page-bg-color: #fff; font-size: .84rem; }
    /* جداول FullCalendar الداخلية ليست جداول بيانات لوحة التحكم. */
    #mp-cal-el table { margin: 0; box-shadow: none; background: transparent; overflow: visible; border-collapse: collapse; border-spacing: 0; }
    #mp-cal-el thead { background: transparent; box-shadow: none; }
    #mp-cal-el th, #mp-cal-el td { padding: 0 !important; vertical-align: top; }
    #mp-cal-el tbody tr, #mp-cal-el tbody tr:hover { transform: none; transition: none; background: transparent; }
    #mp-cal-el .fc-timegrid-slot-label { vertical-align: middle; }
    #mp-cal-el .fc-scrollgrid { border-radius: 14px; overflow: hidden; }
    #mp-cal-el .fc-col-header-cell { background: var(--soft); font-weight: 600; color: var(--muted); }
    #mp-cal-el .fc-col-header-cell-cushion { padding: .55rem .2rem; }
    #mp-cal-el .fc-daygrid-day-number { color: var(--ink); font-weight: 600; padding: .45rem .6rem; text-decoration: none; }
    #mp-cal-el .fc-day-other .fc-daygrid-day-number { opacity: .4; }
    #mp-cal-el .fc-day-other { background: #fafbfd; }
    #mp-cal-el .fc-timegrid-slot { height: 2.4em; }
    #mp-cal-el .fc-timegrid-slot-label { color: var(--muted); font-variant-numeric: tabular-nums; }
    #mp-cal-el a { text-decoration: none; }

    /* بطاقة الزيارة: خلفية فاتحة وشريط جانبي بلون الحالة */
    #mp-cal-el .mp-ev { background: var(--ev-bg); border: 0; border-inline-start: 3px solid var(--ev-bd); border-radius: 8px; color: var(--ev-fg); padding: .25rem .4rem; margin: 1px 3px; box-shadow: 0 2px 6px rgba(20, 80, 127, .08); }
    #mp-cal-el .mp-ev .fc-event-main { color: var(--ev-fg); }
    #mp-cal-el .mp-ev-cancelled .mp-ev-name { text-decoration: line-through; }
    #mp-cal-el .mp-ev:hover { filter: brightness(.97); }
    .mp-ev-name { display: block; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .mp-ev-time { display: block; font-size: .74rem; font-variant-numeric: tabular-nums; direction: ltr; text-align: end; opacity: .9; }
    #mp-cal-el .mp-bg-rest { background: repeating-linear-gradient(45deg, rgba(139, 150, 163, .28) 0 6px, rgba(139, 150, 163, .12) 6px 12px); opacity: 1; }
    #mp-cal-el .mp-bg-leave { background: rgba(217, 70, 168, .16); opacity: 1; }
    #mp-cal-el .mp-bg-off { background: rgba(220, 38, 38, .08); opacity: 1; }

    /* رأس اليوم في عرض الأسبوع: التاريخ ثم الحد الأقصى والمستهلك من ساعات العمل */
    .mp-dh { display: grid; gap: .2rem; padding: .2rem .3rem; line-height: 1.35; }
    .mp-dh-day { font-size: .78rem; color: var(--muted); font-weight: 600; }
    .mp-dh-date { font-size: 1rem; font-weight: 700; color: var(--ink); }
    .mp-dh-stats { display: grid; gap: .1rem; font-size: .72rem; color: var(--muted); font-weight: 500; }
    .mp-dh-stats b { color: var(--ink); font-variant-numeric: tabular-nums; }
    .mp-dh-stats.is-full b { color: #b42a2e; }
    .mp-dh-off { color: #b42a2e; font-size: .72rem; font-weight: 700; }
</style>
<script>
(function () {
    const root = document.getElementById('mp-cal');
    if (!root || root.dataset.ready) return;
    root.dataset.ready = '1';
    const locale = 'ar-SA-u-ca-gregory-nu-latn';
    const fmt = options => new Intl.DateTimeFormat(locale, options);
    const pad = n => String(n).padStart(2, '0');
    const key = d => d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
    const hours = n => (Math.round(n * 100) / 100).toString();
    let days = {};
    let feedRequest = 0;

    // يُحمَّل FullCalendar عند فتح التبويب فقط.
    function withCalendar(callback) {
        let css = document.querySelector('link[data-fc]');
        if (!css) {
            css = document.createElement('link');
            css.rel = 'stylesheet'; css.href = @json(asset('admin/assets/cssbundle/fullcalendar.min.css')); css.dataset.fc = '1';
            document.head.appendChild(css);
        }
        const stylesReady = css.sheet ? Promise.resolve() : new Promise((resolve, reject) => {
            css.addEventListener('load', resolve, { once: true });
            css.addEventListener('error', reject, { once: true });
        });
        const scriptReady = window.FullCalendar ? Promise.resolve() : new Promise((resolve, reject) => {
            let script = document.querySelector('script[data-fc]');
            const isNew = !script;
            if (isNew) {
                script = document.createElement('script');
                script.src = @json(asset('admin/assets/js/bundle/fullcalendar.bundle.js')); script.dataset.fc = '1';
            }
            script.addEventListener('load', resolve, { once: true });
            script.addEventListener('error', reject, { once: true });
            if (isNew) document.head.appendChild(script);
        });
        // لا تُقاس الجداول قبل وصول أنماط الشهر والأسبوع.
        Promise.all([stylesReady, scriptReady]).then(callback).catch(() => {
            document.getElementById('mp-cal-error').hidden = false;
        });
    }

    function headerHtml(date, view) {
        if (view.type !== 'timeGridWeek') return fmt({ weekday: 'long' }).format(date);
        const day = days[key(date)];
        let stats = '<span class="mp-dh-stats" data-dh="' + key(date) + '"></span>';
        if (day) {
            stats = day.off
                ? '<span class="mp-dh-off"><i class="fa fa-bed"></i> يوم راحة</span>'
                : '<span class="mp-dh-stats' + (day.used >= day.max ? ' is-full' : '') + '"><span>الحد الأقصى لساعات العمل: <b>' + hours(day.max) + '</b></span><span>ساعات العمل المستهلكة: <b>' + hours(day.used) + '</b></span></span>';
        }
        return '<div class="mp-dh"><span class="mp-dh-day">' + fmt({ weekday: 'long' }).format(date) + '</span><span class="mp-dh-date">' + fmt({ day: 'numeric', month: 'long' }).format(date) + '</span>' + stats + '</div>';
    }

    function title(view) {
        if (view.type === 'dayGridMonth') return fmt({ month: 'long', year: 'numeric' }).format(view.currentStart);
        const end = new Date(view.currentEnd.getTime() - 86400000);
        return fmt({ day: 'numeric' }).format(view.currentStart) + ' – ' + fmt({ day: 'numeric', month: 'long', year: 'numeric' }).format(end);
    }

    withCalendar(function () {
        const busy = document.getElementById('mp-cal-busy'), error = document.getElementById('mp-cal-error');
        const calendar = new FullCalendar.Calendar(document.getElementById('mp-cal-el'), {
            initialView: 'dayGridMonth', direction: 'rtl', headerToolbar: false, firstDay: 6, height: 'auto',
            dayMaxEvents: 3, moreLinkContent: arg => '+' + arg.num + ' أخرى', allDayText: 'اليوم كاملًا', noEventsContent: 'لا مواعيد',
            slotMinTime: '06:00:00', slotMaxTime: '24:00:00', scrollTime: '07:00:00', expandRows: true, nowIndicator: true,
            slotLabelFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
            eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
            dayHeaderContent: arg => ({ html: headerHtml(arg.date, arg.view) }),
            events: function (info, success, failure) {
                const requestId = ++feedRequest;
                busy.hidden = false; error.hidden = true;
                const url = root.dataset.feed + '?start=' + key(info.start) + '&end=' + key(info.end);
                fetch(url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(r => { if (!r.ok) throw new Error(r.status); return r.json(); })
                    .then(data => {
                        if (requestId !== feedRequest) { success([]); return; }
                        days = data.days || {};
                        success(data.events || []);
                        // ترويسات الأسبوع تُرسم قبل وصول البيانات، فنعيد رسمها بالقيم.
                        if (calendar.view.type === 'timeGridWeek') calendar.setOption('dayHeaderContent', arg => ({ html: headerHtml(arg.date, arg.view) }));
                    })
                    .catch(e => { if (requestId === feedRequest) error.hidden = false; failure(e); })
                    .finally(() => { if (requestId === feedRequest) busy.hidden = true; });
            },
            eventContent: function (arg) {
                if (arg.event.display === 'background') return null;
                const time = arg.event.start && arg.event.end
                    ? fmt({ hour: '2-digit', minute: '2-digit', hour12: false }).format(arg.event.start) + ' – ' + fmt({ hour: '2-digit', minute: '2-digit', hour12: false }).format(arg.event.end)
                    : '';
                const wrap = document.createElement('div');
                const name = document.createElement('span'); name.className = 'mp-ev-name'; name.textContent = arg.event.title;
                const when = document.createElement('span'); when.className = 'mp-ev-time'; when.textContent = time;
                wrap.append(name, when);
                return { domNodes: [wrap] };
            },
            eventDidMount: function (arg) {
                const p = arg.event.extendedProps;
                if (p.status) arg.el.title = arg.event.title + ' — ' + p.status + (p.order ? ' — طلب #' + p.order : '') + (p.hours ? ' — ' + p.hours + ' ساعة' : '');
            },
            datesSet: function (arg) {
                document.getElementById('mp-cal-title').textContent = title(arg.view);
                root.querySelectorAll('[data-view]').forEach(b => { const on = b.dataset.view === arg.view.type; b.classList.toggle('is-active', on); b.setAttribute('aria-pressed', on ? 'true' : 'false'); });
            },
        });
        calendar.render();
        root.querySelectorAll('[data-cal]').forEach(b => b.addEventListener('click', () => calendar[b.dataset.cal]()));
        root.querySelectorAll('[data-view]').forEach(b => b.addEventListener('click', () => {
            if (calendar.view.type === b.dataset.view) return;
            calendar.changeView(b.dataset.view);
            requestAnimationFrame(() => calendar.updateSize());
        }));
        // عند العودة لتبويب التقويم بعد إخفائه يُعاد حساب المقاسات.
        document.addEventListener('mp:tab-shown', e => { if (e.detail === 'calendar') calendar.updateSize(); });
    });
})();
</script>
