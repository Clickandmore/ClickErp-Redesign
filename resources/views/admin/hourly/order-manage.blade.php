@extends('admin.layout.indexs.index')
@section('page-title', 'إدارة طلب الساعات')
@push('css')
<style>

    /* ——— هوية أسس: الأزرق السماوي #2794eb مع الفيروزي #00AC9A المأخوذان من ثيم لوحة التحكم ——— */
    .om {
        --brand: #2794eb; --brand-ink: #1b6fb8; --brand-deep: #14507f; --teal: #00ac9a; --teal-deep: #068a7d;
        --ink: #16212e; --muted: #6b7a8c; --line: #e6ecf3; --soft: #f6f9fc;
        --amber: #f0a020; --coral: #e5484d; --violet: #6b63d6;
        direction: rtl;
    }
    .om-layout { display: grid; grid-template-columns: 330px minmax(0, 1fr); gap: 1.25rem; align-items: start; }
    @media (max-width: 1199px) { .om-layout { grid-template-columns: 1fr; } .om-side { order: 2; } }

    /* البطاقة: حافة علوية بتدرّج الهوية تميّز كل لوحة */
    .om-card { position: relative; background: #fff; border: 1px solid var(--line); border-radius: 18px; overflow: hidden; margin-bottom: 1.25rem; box-shadow: 0 10px 30px rgba(20, 80, 127, .06); }
    .om-head { position: relative; background: linear-gradient(105deg, var(--brand-deep) 0%, var(--brand-ink) 42%, var(--brand) 78%, var(--teal) 140%); color: #fff; padding: 1.05rem 1.3rem; display: flex; align-items: center; justify-content: space-between; gap: .75rem; flex-wrap: wrap; }
    .om-head::after { content: ''; position: absolute; inset-inline: 0; bottom: 0; height: 3px; background: linear-gradient(90deg, var(--teal), rgba(255, 255, 255, .35)); }
    .om-head h5, .om-head strong { margin: 0; font-size: 1.05rem; font-weight: 700; display: flex; align-items: center; gap: .5rem; }
    .om-head-actions { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
    .om-head .om-btn-light { background: rgba(255, 255, 255, .95); color: var(--brand-deep); border: 0; border-radius: 10px; padding: .45rem .9rem; font-size: .84rem; font-weight: 700; display: inline-flex; align-items: center; gap: .4rem; text-decoration: none; }
    .om-head .om-btn-light:hover { background: #fff; color: var(--brand-ink); }
    .om-head .om-icon { background: rgba(255, 255, 255, .18); color: #fff; border: 0; border-radius: 10px; width: 34px; height: 34px; display: grid; place-items: center; }
    .om-head .om-icon:hover { background: rgba(255, 255, 255, .34); }
    .om-body { padding: 1.35rem; }

    /* بطاقة الطلب الجانبية */
    .om-side section { padding: 1.1rem 1.15rem; border-bottom: 1px solid var(--line); text-align: center; overflow-wrap: anywhere; }
    .om-side section:last-child { border-bottom: 0; }
    .om-side .om-name { font-size: 1.3rem; font-weight: 700; color: var(--ink); margin: 0; line-height: 1.3; }
    .om-side .om-label { font-size: .82rem; color: var(--muted); margin: 0 0 .2rem; }
    .om-side .om-value { font-size: 1.4rem; font-weight: 700; color: var(--teal-deep); margin: 0; }
    .om-icons { display: flex; justify-content: center; gap: .5rem; margin-bottom: .65rem; }
    .om-icons button, .om-icons a { width: 40px; height: 40px; border-radius: 12px; border: 1px solid var(--line); background: var(--soft); color: var(--muted); display: grid; place-items: center; text-decoration: none; font-weight: 700; }
    .om-icons button:hover, .om-icons a:hover { color: var(--brand); border-color: #bfe0f8; background: #eff8ff; }
    .om-tile { display: flex; align-items: center; justify-content: center; gap: .6rem; padding: .95rem; border-radius: 14px; background: linear-gradient(105deg, var(--teal-deep), var(--teal)); color: #fff; font-size: 1.15rem; font-weight: 700; box-shadow: 0 8px 20px rgba(6, 138, 125, .25); }
    .om-tile small { display: block; font-size: .78rem; font-weight: 500; opacity: .92; }
    .om-sec-head { display: flex; align-items: flex-start; justify-content: space-between; gap: .5rem; text-align: start; }
    .om-sec-head h6 { margin: 0 0 .5rem; font-size: .9rem; font-weight: 700; color: var(--ink); }
    .om-lines { text-align: start; font-size: .84rem; color: var(--muted); display: grid; gap: .2rem; }
    .om-lines b { color: var(--ink); font-weight: 600; }
    .om-meta { text-align: start; font-size: .84rem; display: grid; gap: .4rem; }
    .om-meta span { color: var(--ink); }
    .om-meta i { width: 16px; color: var(--brand); }
    .om-ltr { direction: ltr; display: inline-block; }
    .om-num { font-variant-numeric: tabular-nums; }

    /* تبويبات بمؤشّر متدرّج مستدير */
    .om-tabs { display: flex; gap: 1.4rem; flex-wrap: wrap; border-bottom: 1px solid var(--line); margin-bottom: 1.35rem; }
    .om-tabs .nav-link { position: relative; border: 0; background: none; padding: .6rem .1rem .75rem; color: var(--muted); font-weight: 600; font-size: .92rem; display: inline-flex; align-items: center; gap: .4rem; }
    .om-tabs .nav-link::after { content: ''; position: absolute; inset-inline: 0; bottom: -1px; height: 3px; border-radius: 99px; background: transparent; }
    .om-tabs .nav-link:hover { color: var(--ink); }
    .om-tabs .nav-link.active { color: var(--brand-ink); }
    .om-tabs .nav-link.active::after { background: linear-gradient(90deg, var(--brand), var(--teal)); }
    .om-tabs .nav-link .badge { background: #eef3f8; color: var(--muted); font-weight: 600; }
    .om-tabs .nav-link.active .badge { background: #e7f3fd; color: var(--brand-ink); }

    /* بطاقات الإحصاء: بيضاء بشريط جانبي وأيقونة مظلّلة بدل الكتل المصمتة */
    .om-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: .9rem; margin-bottom: 1.5rem; }
    .om-stat { position: relative; background: #fff; border: 1px solid var(--line); border-radius: 14px; padding: .95rem 1.1rem; display: flex; align-items: center; gap: .8rem; overflow: hidden; box-shadow: 0 6px 18px rgba(20, 80, 127, .05); }
    .om-stat::before { content: ''; position: absolute; inset-block: 0; inset-inline-start: 0; width: 4px; background: var(--tone, var(--brand)); }
    .om-stat > i { width: 40px; height: 40px; flex: 0 0 40px; border-radius: 12px; display: grid; place-items: center; font-size: 1.05rem; color: var(--tone, var(--brand)); background: var(--tint, #eff8ff); }
    .om-stat strong { display: block; font-size: 1.35rem; line-height: 1.2; color: var(--ink); font-variant-numeric: tabular-nums; }
    .om-stat span { font-size: .8rem; color: var(--muted); }
    .om-t-orange { --tone: #e08a17; --tint: #fef5e7; }
    .om-t-violet { --tone: #6b63d6; --tint: #f0effc; }
    .om-t-teal   { --tone: #068a7d; --tint: #e6f7f5; }
    .om-t-indigo { --tone: #3f6ad8; --tint: #ecf1fd; }
    .om-t-blue   { --tone: #2794eb; --tint: #eaf5fe; }
    .om-t-red    { --tone: #e5484d; --tint: #fdeeee; }
    .om-t-green  { --tone: #17996f; --tint: #e8f7f1; }

    /* صندوق الملاحظات التشغيلية */
    .om-notes { border: 1px solid var(--line); border-radius: 16px; padding: 1rem 1.15rem; margin-bottom: 1.5rem; background: linear-gradient(180deg, var(--soft), #fff); }
    .om-notes-head { display: flex; align-items: center; justify-content: space-between; gap: .75rem; flex-wrap: wrap; }
    .om-notes-head h6 { margin: 0; font-size: 1rem; font-weight: 700; color: var(--brand-ink); display: flex; align-items: center; gap: .45rem; }
    .om-note { border: 1px solid var(--line); border-radius: 12px; background: #fff; padding: .7rem .85rem; margin-top: .75rem; }
    .om-note p { white-space: pre-wrap; margin: 0 0 .3rem; font-size: .86rem; color: var(--ink); }
    .om-note small { color: var(--muted); font-size: .74rem; }

    /* عنوان القسم وشريط الإجراءات الجماعية */
    .om-section-head { display: flex; align-items: center; justify-content: space-between; gap: .75rem; flex-wrap: wrap; margin-bottom: .85rem; }
    .om-section-head h6 { margin: 0; font-size: 1rem; font-weight: 700; color: var(--brand-ink); }
    .om-ops .d-flex { margin: 0 !important; gap: .6rem !important; }
    .om-ops label { font-size: .84rem; color: var(--muted); display: inline-flex; align-items: center; gap: .4rem; margin: 0; }
    .om-ops .btn-primary { background: var(--brand-ink); border-color: var(--brand-ink); border-radius: 10px; font-weight: 600; }
    .om-ops .btn-primary:hover { background: var(--brand-deep); border-color: var(--brand-deep); }
    .om-ops #selected-visits-count { font-size: .8rem; }

    /* جدول الزيارات: كل صف بطاقة بشريط حالة على الحافة الخارجية */
    .om-table { width: 100%; font-size: .86rem; border-collapse: separate; border-spacing: 0 .55rem; }
    .om-table th { font-size: .82rem; color: var(--muted); font-weight: 700; padding: .5rem .9rem; white-space: nowrap; text-align: start; }
    .om-table td { padding: 1rem .9rem; vertical-align: top; min-width: 140px; background: #fff; border-block: 1px solid var(--line); }
    .om-table td:first-child { border-inline-start: 4px solid var(--row-tone, var(--brand)); border-start-start-radius: 14px; border-end-start-radius: 14px; }
    .om-table td:last-child { border-inline-end: 1px solid var(--line); border-start-end-radius: 14px; border-end-end-radius: 14px; }
    .om-kv { display: flex; gap: .4rem; flex-wrap: wrap; align-items: baseline; margin-bottom: .25rem; }
    .om-kv > span { color: var(--muted); font-size: .8rem; }
    .om-kv > b { color: var(--ink); font-weight: 600; }
    .om-badge { display: inline-flex; align-items: center; gap: .3rem; border-radius: 8px; padding: .18rem .55rem; font-size: .76rem; font-weight: 700; white-space: nowrap; border: 1px solid transparent; }
    .om-b-green { background: #e8f7f1; color: #0f7a5a; border-color: #bfe9d9; }
    .om-b-grey  { background: #eef3f8; color: #5a6b7d; border-color: #dbe4ee; }
    .om-b-red   { background: #fdeeee; color: #b42a2e; border-color: #f7cdcf; }
    .om-b-amber { background: #fef5e7; color: #9a6206; border-color: #f7ddb2; }
    .om-b-blue  { background: #eaf5fe; color: #1b6fb8; border-color: #c4e2f8; }
    .om-b-violet{ background: #f0effc; color: #4f47b8; border-color: #d6d2f5; }

    /* بطاقات الزيارات: ترويسة للموعد والحالة، شريط تنبيهات، ثم ثلاثة أعمدة (الطاقم، النقل، الدفع) وشريط إجراءات */
    .om-vfilter { display: flex; gap: .45rem; flex-wrap: wrap; margin-bottom: 1rem; }
    .om-vfilter button { border: 1px solid var(--line); background: #fff; color: var(--muted); border-radius: 99px; padding: .35rem .85rem; font-size: .8rem; font-weight: 700; display: inline-flex; align-items: center; gap: .4rem; }
    .om-vfilter button b { background: var(--soft); color: var(--ink); border-radius: 99px; padding: 0 .45rem; font-size: .74rem; }
    .om-vfilter button:hover { border-color: #bfe0f8; color: var(--brand-ink); }
    .om-vfilter button.active { background: var(--brand-ink); border-color: var(--brand-ink); color: #fff; }
    .om-vfilter button.active b { background: rgba(255, 255, 255, .22); color: #fff; }
    .om-vfilter button.is-attention:not(.active) { border-color: #f7cdcf; color: #b42a2e; }
    .om-vfilter button.is-attention:not(.active) b { background: #fdeeee; color: #b42a2e; }
    .om-visits { display: grid; gap: .9rem; }
    .om-visit { --tone: var(--brand); background: #fff; border: 1px solid var(--line); border-inline-start: 4px solid var(--tone); border-radius: 14px; box-shadow: 0 4px 14px rgba(20, 80, 127, .05); transition: box-shadow .15s, border-color .15s; }
    .om-visit:has(.om-v-check:checked) { border-color: var(--brand); box-shadow: 0 0 0 3px rgba(39, 148, 235, .15); }
    .om-visit[hidden] { display: none; }
    .om-v-head { display: flex; align-items: center; gap: .9rem 1.1rem; flex-wrap: wrap; padding: .85rem 1.1rem; }
    .om-v-check { width: 1.15rem; height: 1.15rem; margin: 0; flex: 0 0 auto; cursor: pointer; }
    .om-v-no { display: grid; justify-items: center; min-width: 54px; padding: .3rem .5rem; border-radius: 10px; background: var(--tint, var(--soft)); line-height: 1.15; }
    .om-v-no span { font-size: .68rem; color: var(--muted); }
    .om-v-no b { font-size: 1.2rem; color: var(--tone); }
    .om-v-no small { font-size: .66rem; color: var(--muted); }
    .om-v-when { display: grid; gap: .15rem; flex: 1 1 220px; min-width: 0; }
    .om-v-when b { font-size: .95rem; color: var(--ink); display: inline-flex; align-items: center; gap: .4rem; flex-wrap: wrap; }
    .om-v-when > span { font-size: .8rem; color: var(--muted); }
    .om-v-when i { color: #9fb0c2; }
    .om-v-tags { display: flex; gap: .35rem; flex-wrap: wrap; }
    .om-v-price { text-align: end; line-height: 1.1; margin-inline-start: auto; }
    .om-v-price b { display: block; font-size: 1.15rem; color: var(--ink); }
    .om-v-price small { font-size: .7rem; color: var(--muted); }
    .om-v-alerts { display: flex; gap: .4rem; flex-wrap: wrap; padding: .55rem 1.1rem; background: #fffaf3; border-block: 1px solid #f7ead2; }
    .om-v-body { display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); border-top: 1px solid var(--line); }
    .om-v-alerts + .om-v-body { border-top: 0; }
    .om-v-body > section { padding: .85rem 1.1rem; min-width: 0; }
    .om-v-body > section + section { border-inline-start: 1px solid var(--line); }
    .om-v-body h6 { font-size: .78rem; font-weight: 700; color: var(--muted); margin: 0 0 .55rem; display: flex; align-items: center; gap: .4rem; }
    .om-v-body h6 i { color: var(--brand); }
    .om-v-body h6 small { margin-inline-start: auto; background: var(--soft); border-radius: 99px; padding: 0 .45rem; color: var(--ink); }
    .om-v-list { list-style: none; margin: 0 0 .5rem; padding: 0; display: grid; gap: .4rem; }
    .om-v-list li { display: flex; align-items: center; gap: .5rem; min-width: 0; }
    .om-v-avatar { width: 30px; height: 30px; flex: 0 0 30px; border-radius: 50%; display: grid; place-items: center; font-size: .8rem; font-weight: 700; background: #e7f3fd; color: var(--brand-ink); }
    .om-v-list li.is-empty .om-v-avatar { background: #fff; border: 1.5px dashed #c3d2e0; color: #9fb0c2; font-size: .72rem; }
    .om-v-name { flex: 1; min-width: 0; font-size: .85rem; font-weight: 600; color: var(--ink); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .om-v-name small { color: var(--muted); font-weight: 500; }
    .om-v-slot { flex: 1; text-align: start; border: 1.5px dashed #f2c48a; background: #fffaf3; color: #9a6206; border-radius: 8px; padding: .3rem .6rem; font-size: .8rem; font-weight: 700; }
    .om-v-slot:hover { background: #fef5e7; }
    .om-v-icon { width: 28px; height: 28px; flex: 0 0 28px; border-radius: 8px; border: 1px solid var(--line); background: #fff; color: var(--muted); display: grid; place-items: center; font-size: .78rem; }
    .om-v-icon.is-danger:hover { color: var(--coral); border-color: #f7cdcf; background: #fdeeee; }
    .om-v-row { display: flex; align-items: baseline; gap: .5rem; font-size: .82rem; padding: .22rem 0; }
    .om-v-row > span { color: var(--muted); white-space: nowrap; }
    .om-v-row > span i { width: 15px; color: #9fb0c2; }
    .om-v-row > b { color: var(--ink); font-weight: 600; margin-inline-start: auto; text-align: end; min-width: 0; overflow-wrap: anywhere; }
    .om-v-row > b.is-missing { color: #c2410c; }
    .om-v-row > em { font-style: normal; font-size: .76rem; color: var(--muted); background: var(--soft); border-radius: 6px; padding: 0 .4rem; }
    .om-v-bar { height: 6px; border-radius: 99px; background: #eef3f8; overflow: hidden; margin: .45rem 0 .3rem; }
    .om-v-bar span { display: block; height: 100%; border-radius: inherit; background: linear-gradient(90deg, var(--teal-deep), var(--teal)); }
    .om-v-pay .alert { padding: .4rem .6rem; margin: .5rem 0 0 !important; font-size: .76rem; border-radius: 8px; }
    .om-v-pay details summary { font-size: .76rem; color: var(--brand-ink); cursor: pointer; margin-top: .35rem; }
    .om-v-notes { padding: .55rem 1.1rem; border-top: 1px solid var(--line); background: var(--soft); }
    .om-v-notes p { margin: .15rem 0; font-size: .8rem; color: var(--ink); white-space: pre-wrap; }
    .om-v-notes i { color: var(--violet); }
    .om-v-notes small { color: var(--muted); }
    .om-v-actions { display: flex; justify-content: space-between; align-items: center; gap: .5rem; flex-wrap: wrap; padding: .6rem 1.1rem; border-top: 1px solid var(--line); background: #fbfcfe; border-end-start-radius: 14px; border-end-end-radius: 14px; }
    .om-v-actions > div { display: flex; gap: .4rem; flex-wrap: wrap; }
    .om-v-actions > div:empty { display: none; }
    .om-v-actions .om-btn, .om-v-actions .om-note-btn { width: auto; margin: 0; padding: .35rem .7rem; font-size: .77rem; border-radius: 8px; background: #fff; border: 1px solid var(--line); color: #4a5a6b; box-shadow: none; filter: none; }
    .om-v-actions .om-btn i, .om-v-actions .om-note-btn i { color: var(--act, var(--brand)); }
    .om-v-actions .om-btn:hover, .om-v-actions .om-note-btn:hover { border-color: var(--act, var(--brand)); color: var(--act, var(--brand)); background: #fff; }
    .om-v-actions .om-btn-warn { --act: #e08a17; }
    .om-v-actions .om-btn-green { --act: var(--teal-deep); }
    .om-v-actions .om-note-btn { --act: var(--violet); }
    .om-v-actions .om-btn-violet { --act: #fff; background: var(--teal-deep); border-color: var(--teal-deep); color: #fff; }
    .om-v-actions .om-btn-violet:hover { color: #fff; filter: brightness(.95); }
    .om-v-actions .om-btn-danger { --act: var(--coral); color: var(--coral); border-color: #f7cdcf; }
    .om-v-actions .om-btn-danger:hover { background: #fdeeee; }
    .om-visit[data-buckets~="attention"] .om-v-actions [data-order-action="reschedule"] { --act: #fff; background: var(--amber); border-color: var(--amber); color: #fff; }
    @media (max-width: 767px) {
        .om-v-body > section + section { border-inline-start: 0; border-top: 1px solid var(--line); }
        .om-v-price { margin-inline-start: 0; }
    }

    /* أزرار الإجراءات: ملء بلون الهوية للأساسي وحواف خفيفة للثانوي */
    .om-btn { display: inline-flex; align-items: center; justify-content: center; gap: .4rem; width: 100%; border: 0; border-radius: 10px; padding: .55rem .7rem; font-size: .82rem; font-weight: 700; color: #fff; background: var(--brand); margin-top: .45rem; text-decoration: none; transition: filter .15s, box-shadow .15s; }
    .om-btn:hover { color: #fff; filter: brightness(.95); box-shadow: 0 4px 12px rgba(20, 80, 127, .16); }
    .om-btn-warn { background: var(--amber); }
    .om-btn-info { background: var(--brand); }
    .om-btn-violet { background: var(--violet); }
    .om-btn-danger { background: var(--coral); }
    .om-btn-green { background: var(--teal-deep); }
    .om-btn-soft { background: #fff; color: #4a5a6b; border: 1px solid var(--line); }
    .om-btn-soft:hover { border-color: var(--brand); color: var(--brand); background: #f7fbff; }
    .om-order-actions { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .65rem; }
    .om-order-actions .om-btn { margin: 0; min-height: 42px; }
    .om-link-edit { background: none; border: 0; color: var(--brand); font-size: .78rem; display: inline-flex; align-items: center; gap: .25rem; padding: 0; }
    .om-schedule-btn { display: inline-flex; align-self: flex-start; align-items: center; gap: .4rem; margin-top: .4rem; padding: .35rem .8rem; border-radius: 9px; border: 1px solid var(--brand); background: #eef6fe; color: var(--brand-ink); font-size: .8rem; font-weight: 700; white-space: nowrap; transition: background .15s, color .15s; }
    .om-schedule-btn:hover, .om-schedule-btn:focus-visible { background: var(--brand); color: #fff; }

    /* زر فتح مودال الملاحظة التشغيلية: مفرَّغ في ترويسة الصندوق، وممتلئ داخل الجدول */
    .om-note-btn { display: inline-flex; align-items: center; justify-content: center; gap: .4rem; border-radius: 10px; padding: .55rem .9rem; font-size: .82rem; font-weight: 700; background: #fff; color: var(--brand-ink); border: 1px solid var(--brand); }
    .om-note-btn:hover { background: #f7fbff; color: var(--brand-deep); }

    .om-note-form summary { display: inline-flex; align-items: center; justify-content: center; gap: .4rem; border-radius: 10px; padding: .55rem .9rem; font-size: .82rem; font-weight: 700; color: #fff; background: var(--violet); cursor: pointer; list-style: none; }
    .om-note-form summary::-webkit-details-marker { display: none; }
    .om-notes-head .om-note-form { margin: 0; }
    .om-notes-head .om-note-form summary { background: #fff; color: var(--brand-ink); border: 1px solid var(--brand); }
    .om-notes-head .om-note-form summary:hover { background: #f7fbff; }

    /* سجل الحالة: نقطة بأيقونة ولون الحالة الجديدة على خط عمودي، وبجانبها بطاقة التفاصيل */
    .om-timeline { list-style: none; margin: 0; padding: 0; position: relative; }
    .om-timeline li { position: relative; display: grid; grid-template-columns: 42px minmax(0, 1fr); gap: .9rem; padding-bottom: 1rem; }
    .om-timeline li:last-child { padding-bottom: 0; }
    .om-timeline li::before { content: ''; position: absolute; inset-inline-start: 20px; top: 42px; bottom: 0; width: 2px; background: var(--line); }
    .om-timeline li:last-child::before { display: none; }
    .om-tl-dot { width: 42px; height: 42px; border-radius: 50%; display: grid; place-items: center; font-size: 1rem; color: var(--tone); background: var(--tint); border: 2px solid #fff; box-shadow: 0 0 0 1px var(--ring); position: relative; z-index: 1; }
    .om-tl-card { background: #fff; border: 1px solid var(--line); border-inline-start: 3px solid var(--tone); border-radius: 14px; padding: .8rem 1rem; box-shadow: 0 4px 14px rgba(20, 80, 127, .05); }
    .om-tl-current .om-tl-card { background: linear-gradient(180deg, var(--tint), #fff 70%); }
    .om-tl-head { display: flex; align-items: flex-start; justify-content: space-between; gap: .75rem; flex-wrap: wrap; }
    .om-tl-title { margin: 0; font-size: .92rem; font-weight: 700; color: var(--ink); }
    .om-tl-flow { display: flex; align-items: center; gap: .4rem; flex-wrap: wrap; margin-top: .45rem; }
    .om-tl-flow .fa-arrow-left { color: #a6b4c3; font-size: .75rem; }
    .om-tl-when { text-align: end; line-height: 1.35; white-space: nowrap; }
    .om-tl-when b { display: block; font-size: .82rem; color: var(--ink); font-weight: 700; }
    .om-tl-when span { font-size: .74rem; color: var(--muted); }
    .om-tl-actor { display: inline-flex; align-items: center; gap: .4rem; margin-top: .65rem; padding-top: .6rem; border-top: 1px dashed var(--line); width: 100%; font-size: .78rem; color: var(--muted); }
    .om-tl-actor b { color: var(--ink); font-weight: 600; }
    .om-tl-actor i { color: #9fb0c2; }
    .om-tl-now { font-size: .7rem; font-weight: 700; color: var(--tone); background: #fff; border: 1px solid var(--ring); border-radius: 99px; padding: .05rem .5rem; margin-inline-start: .35rem; vertical-align: middle; }
    .om-tl-green  { --tone: #17996f; --tint: #e8f7f1; --ring: #bfe9d9; }
    .om-tl-grey   { --tone: #5a6b7d; --tint: #eef3f8; --ring: #dbe4ee; }
    .om-tl-red    { --tone: #e5484d; --tint: #fdeeee; --ring: #f7cdcf; }
    .om-tl-amber  { --tone: #e08a17; --tint: #fef5e7; --ring: #f7ddb2; }
    .om-tl-blue   { --tone: #2794eb; --tint: #eaf5fe; --ring: #c4e2f8; }
    .om-tl-violet { --tone: #6b63d6; --tint: #f0effc; --ring: #d6d2f5; }

    .om-collect { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: .85rem; align-items: end; padding: 1rem 1.1rem; border: 1px solid var(--line); border-radius: 16px; background: linear-gradient(180deg, var(--soft), #fff); margin-bottom: 1.5rem; }
    .om-collect label { display: block; font-size: .8rem; color: var(--muted); margin-bottom: .35rem; }
    .om-collect .form-control, .om-collect .form-select { border-radius: 10px; border-color: #dfe7ef; }
    .om-collect .form-control:focus, .om-collect .form-select:focus { border-color: #9fd0f4; box-shadow: 0 0 0 3px rgba(39, 148, 235, .15); }

    .om-alert { display: flex; gap: .7rem; align-items: flex-start; border: 1px solid #f7ddb2; background: #fef8ec; color: #8a5806; border-radius: 14px; padding: .85rem 1rem; font-size: .86rem; margin-bottom: 1.25rem; }

    .om-empty { text-align: center; padding: 2.2rem 1rem; color: var(--muted); }
    .om-empty i { font-size: 1.7rem; color: #c3d2e0; display: block; margin-bottom: .5rem; }

    @media print {
        body * { visibility: hidden; }
        .om, .om * { visibility: visible; }
        .om { position: absolute; inset: 0; }
        .om-layout { grid-template-columns: 1fr; }
        .om-card { break-inside: avoid; box-shadow: none; }
        .om-head-actions, .om-note-form, .om-tabs, .om-ops, .om-btn, .om-icons, form, details { display: none !important; }
        .tab-pane { display: block !important; opacity: 1 !important; }
    }
</style>
@endpush
@section('content')
@include('admin.hourly._booking-options')
@php
    $snapshot = $order->package_snapshot ?? [];
    $itemTitle = collect($snapshot['translations'] ?? [])->firstWhere('locale', app()->getLocale())['title'] ?? ($snapshot['title'] ?? $snapshot['code'] ?? '—');
    $notes = app(\App\Services\Hourly\OperationalNotes::class)->forOrder($order);
    $visitCount = $order->visits->count();
    $completed = $order->visits->where('status', 'completed')->count();
    $cancelled = $order->visits->whereIn('status', ['cancelled', 'cancelled_by_client'])->count();
    $lastVisit = $order->visits->sortByDesc('scheduled_end_at')->first();
    $remaining = max(0, (float) $order->total_amount - (float) $order->paid_amount);
    $paidPercent = $order->total_amount > 0 ? min(100, round($order->paid_amount / $order->total_amount * 100)) : 0;
    $daysLeft = $order->expires_at ? (int) now()->startOfDay()->diffInDays($order->expires_at->startOfDay(), false) : null;
    $totalWorkHours = $order->visits->sum(fn ($visit) => (float) $visit->planned_hours * $visit->workers_count);

    $money = fn ($amount) => __('hourly.sar').' '.number_format((float) $amount, 2);
    $hours = fn ($value) => rtrim(rtrim(number_format((float) $value, 2), '0'), '.');
    /* لون وأيقونة موحّدان لكل حالة: الطلب والزيارة والدفعة. */
    $statusTone = fn ($status) => match ($status) {
        'pending_confirmation' => ['om-b-amber', 'fa-hourglass-half'],
        'awaiting_payment' => ['om-b-amber', 'fa-file-invoice-dollar'],
        'active', 'completed', 'paid' => ['om-b-green', 'fa-circle-check'],
        'expired', 'no_show', 'cancelled', 'cancelled_by_client', 'failed', 'unpaid', 'rejected' => ['om-b-red', 'fa-circle-xmark'],
        'missed' => ['om-b-red', 'fa-triangle-exclamation'],
        'scheduled' => ['om-b-blue', 'fa-calendar-check'],
        'en_route', 'delivered', 'picked_up' => ['om-b-violet', 'fa-van-shuttle'],
        'in_progress', 'worker_finished', 'partial', 'pending', 'pending_review' => ['om-b-amber', 'fa-clock'],
        default => ['om-b-grey', 'fa-circle'],
    };
    [$statusClass, $statusIcon] = $statusTone($order->status);
    [$payClass, $payIcon] = $statusTone($order->payment_status);
@endphp

<div class="container-fluid om">
@include('admin.hourly.messages')
@include('admin.hourly._order-awaiting-payment')
@include('admin.hourly._order-refund')
@include('admin.hourly._cash-link')

<a class="btn btn-outline-warning mb-3" href="{{ route('admin.hourly-recovery.index') }}">طلبات معالجة الزيارات غير المنفذة</a>
@foreach($order->visits as $replacementVisit)
    @include('admin.hourly._worker-replacement',['visit'=>$replacementVisit])
    @include('admin.hourly._incident-report',['visit'=>$replacementVisit])
@endforeach
<a class="btn btn-outline-warning mb-3" href="{{ route('admin.hourly-incidents.index') }}">بلاغات تعذر تنفيذ الخدمة والمتابعة</a>
<div class="om-layout">
<aside class="om-side">
    <div class="om-card">
        <header class="om-head"><strong><i class="fa fa-cart-shopping"></i> بطاقة الطلب #{{ str_pad((string) $order->id, 8, '0', STR_PAD_LEFT) }}</strong></header>
        <section>
            <div class="om-icons">
                <button type="button" id="om-copy-card" title="نسخ بيانات الاتصال" aria-label="نسخ بيانات الاتصال"><i class="fa fa-copy"></i></button>
                <a href="#om-notes-box" title="عدد الملاحظات" aria-label="عدد الملاحظات"><span class="om-num">{{ $notes->count() }}</span></a>
                @if($order->user?->email)<a href="mailto:{{ $order->user->email }}" title="مراسلة العميل" aria-label="مراسلة العميل"><i class="fa fa-envelope"></i></a>@endif
            </div>
            <p class="om-name" id="om-customer-name">{{ $order->user?->name ?: 'عميل محذوف' }}</p>
            <p class="om-label mt-2"><i class="fa fa-circle-info"></i> بيانات الاتصال وقت الطلب</p>
            <p class="om-ltr om-num" id="om-customer-phone"><i class="fa fa-phone"></i> {{ $order->contact_phone ?: $order->user?->phone }}</p>
            <div class="om-tile mt-2"><i class="fa fa-wallet"></i> <span>{{ $money($order->user?->hourly_wallet_balance ?? 0) }}<small>رصيد العميل</small></span></div>
        </section>
        <section>
            <div class="om-sec-head">
                <div>
                    <h6>معلومات العنوان</h6>
                    <div class="om-lines">
                        <div>اسم المبنى / الفيلا: <b>{{ $order->building ?: '—' }}</b></div>
                        <div>رقم الوحدة: <b>{{ $order->unit ?: '—' }}</b></div>
                        <div>العنوان: <b>{{ $order->address_text }}</b></div>
                        <div>الحي / المنطقة: <b>{{ $order->district ?: '—' }}</b></div>
                    </div>
                </div>
                @if($order->latitude !== null && $order->longitude !== null)
                    <a class="om-btn om-btn-warn" style="width:auto;margin:0" target="_blank" rel="noopener" title="عرض على الخريطة" aria-label="عرض على الخريطة" href="https://www.google.com/maps?q={{ urlencode($order->latitude.','.$order->longitude) }}"><i class="fa fa-map-location-dot"></i></a>
                @endif
            </div>
        </section>
        <section><p class="om-label">مبلغ الطلب الأولي</p><p class="om-value om-num">{{ $money($order->initial_amount ?? $snapshot['original_total'] ?? $order->total_amount) }}</p></section>
        <section><p class="om-label">مبلغ الطلب الحالي</p><p class="om-value om-num">{{ $money($order->total_amount) }}</p></section>
        <section><p class="om-label">مبلغ الطلب المحصَّل</p><p class="om-value om-num">{{ $money($order->paid_amount) }}</p>
            @if($remaining > 0)<p class="om-label mt-1" style="color:#c2410c">المتبقي {{ $money($remaining) }} — سُدِّد {{ $paidPercent }}%</p>@endif
        </section>
        <section>
            <div class="om-meta">
                <div><i class="fa fa-folder"></i> <b>فئة الخدمة:</b> <span>{{ $snapshot['category'] ?? $order->serviceCategory?->name ?? '—' }}</span></div>
                <div><i class="fa fa-box"></i> <b>الباقة:</b> <span>{{ $itemTitle }}</span></div>
                <div><i class="fa fa-tag"></i> <b>سعر الباقة:</b> <span class="om-num">{{ $money($snapshot['price_per_visit'] ?? $order->price_per_visit) }}</span></div>
                <div><i class="fa fa-droplet"></i> <b>مواد التنظيف:</b> <span>{{ !empty($snapshot['materials_included']) ? 'مشمولة' : 'غير مشمولة' }}</span></div>
            </div>
        </section>
        <section>
            <div class="om-meta">
                <div><i class="fa fa-user-tie"></i> <b>مصدر الطلب:</b> <span>{{ $order->source === 'admin' ? 'موظف الفرع' : 'العميل' }} — {{ $creator?->name ?: '—' }}</span></div>
                <div><i class="fa fa-clock"></i> <b>إنشاء الطلب:</b> <span class="om-ltr om-num">{{ $order->created_at?->format('Y-m-d H:i') }}</span></div>
                <div><i class="fa fa-play"></i> <b>بداية الطلب:</b> <span class="om-ltr om-num">{{ $order->starts_at?->format('Y-m-d H:i') }}</span></div>
                <div><i class="fa fa-flag-checkered"></i> <b>نهاية آخر زيارة:</b> <span class="om-ltr om-num">{{ $lastVisit?->scheduled_end_at?->format('Y-m-d H:i') ?: 'لم يحدد' }}</span></div>
                <div><i class="fa fa-calendar-day"></i> <b>الصلاحية:</b> <span class="om-ltr om-num">{{ $order->expires_at?->format('Y-m-d') }}</span>
                    @if($daysLeft !== null)<span class="om-badge {{ $daysLeft < 0 ? 'om-b-red' : ($daysLeft <= 7 ? 'om-b-amber' : 'om-b-grey') }}">{{ $daysLeft < 0 ? 'منتهية' : $daysLeft.' يوم' }}</span>@endif
                </div>
            </div>
        </section>
        <section>
            <div class="om-meta">
                <div><i class="fa fa-wallet"></i> <b>نوع الدفع:</b> <span>{{ __('hourly.'.$order->payment_method) }}</span></div>
                <div><i class="fa fa-receipt"></i> <b>حالة السداد:</b> <span class="om-badge {{ $payClass }}"><i class="fa {{ $payIcon }}"></i> {{ __('hourly.'.$order->payment_status) }}</span></div>
                <div><i class="fa fa-flag"></i> <b>حالة الطلب:</b> <span class="om-badge {{ $statusClass }}"><i class="fa {{ $statusIcon }}"></i> {{ \App\Services\Hourly\HourlyOrderService::statusLabel($order) }}</span></div>
            </div>
        </section>
        @include('admin.hourly._order-card-actions')
    </div>
</aside>

<main>
    <div class="om-card">
        <header class="om-head">
            <h5><i class="fa fa-cart-shopping"></i> #{{ str_pad((string) $order->id, 8, '0', STR_PAD_LEFT) }} — تفاصيل الطلب</h5>
            <div class="om-head-actions">
                <button class="om-icon" onclick="window.print()" type="button" title="طباعة" aria-label="طباعة"><i class="fa fa-print"></i></button>
                <a class="om-btn-light" target="_blank" href="{{ route('admin.hourly-orders.invoice', $order) }}">الفاتورة <i class="fa fa-file-pdf"></i></a>
                @if(AdminGuardHasPerm('hourly_orders_create'))
                    @php $renewalReason=app(\App\Services\Hourly\RenewalEligibility::class)->reason($order); @endphp
                    @if($renewalReason)<span class="om-btn-light" aria-disabled="true" title="{{ $renewalReason }}">التجديد غير متاح <i class="fa fa-lock"></i><small class="d-block">{{ $renewalReason }}</small></span>@else
                    <a class="om-btn-light" href="{{ route('admin.hourly-orders.create', ['renew' => $order->id]) }}">تجديد <i class="fa fa-rotate"></i></a>
                    @endif
                @endif
                <a class="om-btn-light" href="{{ route('admin.hourly-orders.summary', $order) }}">الملخص <i class="fa fa-file-lines"></i></a>
                <a class="om-btn-light" href="{{ route('admin.hourly-orders.index') }}">الطلبات <i class="fa fa-list"></i></a>
            </div>
        </header>
        <div class="om-body">
            @php
                $tabs = ['details' => ['تفاصيل الطلب', $visitCount], 'status' => ['سجل الحالة', $order->statusLogs->count()], 'reviews' => ['تقييمات العاملات', $reviews->count()], 'audit' => ['السجلات', $audits->count()], 'notes' => ['الملاحظات', $notes->count()], 'payments' => ['سجل الدفعات', $order->payments->count()]];
            @endphp
            <nav class="nav om-tabs" role="tablist">
                @foreach($tabs as $key => $tab)
                    <button class="nav-link {{ $loop->first ? 'active' : '' }}" id="om-tab-{{ $key }}" data-bs-toggle="tab" data-bs-target="#om-{{ $key }}" type="button" role="tab" aria-controls="om-{{ $key }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                        {{ $tab[0] }}@if($tab[1])<span class="badge rounded-pill om-num">{{ $tab[1] }}</span>@endif
                    </button>
                @endforeach
            </nav>

            <div class="tab-content">
            <section id="om-details" class="tab-pane show active" role="tabpanel" aria-labelledby="om-tab-details">
                <div class="om-stats">
                    <div class="om-stat om-t-orange"><i class="fa fa-calendar-days"></i><div><strong>{{ $visitCount }}</strong><span>إجمالي الزيارات</span></div></div>
                    <div class="om-stat om-t-violet"><i class="fa fa-user-group"></i><div><strong>{{ $order->workers_per_visit }}</strong><span>الموظفون لكل زيارة</span></div></div>
                    <div class="om-stat om-t-teal"><i class="fa fa-hourglass-half"></i><div><strong>{{ $hours($totalWorkHours) }} ساعة</strong><span>إجمالي ساعات العمل</span></div></div>
                    <div class="om-stat om-t-indigo"><i class="fa fa-clock"></i><div><strong>{{ $hours($order->hours_per_visit) }} ساعة</strong><span>وقت الزيارة</span></div></div>
                    <div class="om-stat om-t-blue"><i class="fa fa-dollar-sign"></i><div><strong>{{ $money($averageVisitPrice) }}</strong><span>متوسط السعر (دون المجانية والملغاة)</span></div></div>
                    <div class="om-stat om-t-red"><i class="fa fa-circle-xmark"></i><div><strong>{{ $cancelled }} ({{ $visitCount ? round($cancelled / $visitCount * 100) : 0 }}%)</strong><span>ملغاة</span></div></div>
                    <div class="om-stat om-t-green"><i class="fa fa-circle-check"></i><div><strong>{{ $completed }} ({{ $visitCount ? round($completed / $visitCount * 100) : 0 }}%)</strong><span>مكتملة</span></div></div>
                </div>

                <div class="om-notes" id="om-notes-box">
                    <div class="om-notes-head">
                        <h6>الملاحظات التشغيلية</h6>
                        @include('admin.hourly._note-form', ['visitId' => null])
                    </div>
                    @if($order->notes)<div class="om-note"><p>{{ $order->notes }}</p><small>ملاحظة مرفقة بالطلب عند الإنشاء</small></div>@endif
                    @foreach($notes->whereNull('visit_id') as $note)
                        <div class="om-note"><p>{{ $note['text'] }}</p><small><i class="fa fa-user-pen"></i> {{ $note['author'] }} — <span class="om-ltr">{{ $note['created_at'] }}</span></small></div>
                    @endforeach
                </div>

                <div class="om-section-head">
                    <h6><i class="fa fa-calendar-days"></i> الزيارات <span class="om-num">({{ $visitCount }})</span></h6>
                    <div class="om-ops">@include('admin.hourly._order-operations')</div>
                </div>

                @php
                    $visitFinances = app(\App\Services\Hourly\PaymentScheduleService::class)->forOrder($order)['visits'];
                    $closedStatuses = ['completed', 'cancelled', 'expired', 'no_show', 'cancelled_by_client'];
                    $earlyService = app(\App\Services\Hourly\EarlyFinish::class);
                    $earlyFinishes = \App\Services\Hourly\EarlyFinish::ready()
                        ? \Illuminate\Support\Facades\DB::table('hourly_early_finishes')->where('order_id', $order->id)->get()->groupBy('visit_id') : collect();
                    /* ما يحتاج إجراء في الزيارة وتصنيفها لأزرار التصفية؛ تستخدمه البطاقة والعدّادات معًا. */
                    $visitState = function ($visit) use ($visitFinances, $closedStatuses, $money, $earlyFinishes, $earlyService) {
                        $finance = $visitFinances[$visit->id] ?? null;
                        $free = $visit->is_free || (float) $visit->visit_price === 0.0;
                        $emptySlots = max(0, (int) $visit->workers_count - $visit->workers->count());
                        $alerts = [];
                        if (!in_array($visit->status, $closedStatuses) && $emptySlots) $alerts[] = ['om-b-amber', 'fa-user-clock', $emptySlots === 1 ? 'مقعد شاغر بدون عاملة' : $emptySlots.' مقاعد شاغرة بدون عاملات'];
                        if (!$free && ($finance['is_overdue'] ?? false)) $alerts[] = ['om-b-red', 'fa-file-invoice-dollar', 'دفعة متأخرة: '.$money($finance['remaining'])];
                        if ($driverGap = \App\Services\Hourly\DriverGaps::missingRoles($visit)) {
                            $driverGapText = count($driverGap) === 2 ? 'بدون سائق' : 'ينقصها سائق '.\App\Services\Hourly\DriverGaps::roleLabel($driverGap[0]);
                            $alerts[] = $visit->scheduled_start_at->isPast()
                                ? ['om-b-red', 'fa-van-shuttle', 'متأخرة '.\App\Services\Hourly\DriverGaps::startsIn($visit->scheduled_start_at).' '.$driverGapText]
                                : ['om-b-amber', 'fa-van-shuttle', 'زيارة قادمة '.$driverGapText];
                        }
                        // الإنهاء المبكر: يبقى تنبيهًا حتى يؤكده العميل أو تقرر الإدارة.
                        $earlyOpen = false;
                        foreach ($earlyFinishes[$visit->id] ?? [] as $early) {
                            $earlyState = $earlyService->effectiveStatus($early);
                            if (in_array($earlyState, ['confirmed', 'resolved'], true)) continue;
                            $earlyOpen = true;
                            $alerts[] = [$earlyService->needsReview($early) ? 'om-b-red' : 'om-b-amber', 'fa-stopwatch',
                                'إنهاء مبكر: نُفذ '.$earlyService->duration((int) $early->worked_minutes).' من '.$earlyService->duration((int) $early->planned_minutes)
                                .' — '.($earlyService->needsReview($early) ? 'يحتاج قرار الإدارة' : 'بانتظار رد العميل')];
                        }
                        $buckets = [match (true) { $visit->status === 'completed' => 'done', in_array($visit->status, $closedStatuses) => 'cancelled', default => 'upcoming' }];
                        if ($earlyOpen) $buckets[] = 'attention';
                        /* الزيارة الفائتة تظهر في شارة الحالة نفسها، فلا تُكرَّر في شريط التنبيهات. */
                        if ($alerts || $visit->is_missed) $buckets[] = 'attention';
                        return compact('finance', 'free', 'emptySlots', 'alerts', 'buckets');
                    };
                    $visitStates = $order->visits->mapWithKeys(fn ($visit) => [$visit->id => $visitState($visit)]);
                    $bucketCount = fn ($bucket) => $visitStates->filter(fn ($state) => in_array($bucket, $state['buckets']))->count();
                    $filters = ['all' => ['الكل', $visitCount], 'attention' => ['تحتاج إجراء', $bucketCount('attention')], 'upcoming' => ['القادمة والجارية', $bucketCount('upcoming')], 'done' => ['المكتملة', $bucketCount('done')], 'cancelled' => ['الملغاة', $bucketCount('cancelled')]];
                @endphp
                @if($visitCount > 1)
                    <div class="om-vfilter" role="group" aria-label="تصفية الزيارات">
                        @foreach($filters as $key => [$label, $count])
                            @if($key === 'all' || $count)
                                <button type="button" class="{{ $loop->first ? 'active' : '' }} {{ $key === 'attention' ? 'is-attention' : '' }}" data-visit-filter="{{ $key }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}">{{ $label }} <b class="om-num">{{ $count }}</b></button>
                            @endif
                        @endforeach
                    </div>
                @endif
                <div class="om-visits">
                    @forelse($order->visits->sortBy('visit_number') as $visit)
                        @include('admin.hourly._manage-visit-card', $visitStates[$visit->id])
                    @empty
                        <div class="om-empty"><i class="fa fa-calendar-xmark"></i> لا توجد زيارات على هذا الطلب.</div>
                    @endforelse
                </div>
            </section>

            <section id="om-status" class="tab-pane" role="tabpanel" aria-labelledby="om-tab-status">
                @if($order->statusLogs->isEmpty())
                    <div class="om-empty"><i class="fa fa-timeline"></i> لا توجد سجلات حالة بعد.</div>
                @else
                    <ul class="om-timeline">
                        @foreach($order->statusLogs->sortByDesc('id')->values() as $log)
                            @php
                                [$toClass, $toIcon] = $statusTone($log->to_status);
                                $changed = $log->from_status && $log->from_status !== $log->to_status;
                                [$actorIcon, $actorRole, $actorName] = match ($log->actor_type) {
                                    'admin' => ['fa-user-tie', 'موظف', $logAdmins[$log->actor_id] ?? null],
                                    'client' => ['fa-user', 'العميل', $log->actor_id == $order->user_id ? $order->user?->name : null],
                                    'system' => ['fa-gears', 'النظام', null],
                                    default => ['fa-circle-user', $log->actor_type, null],
                                };
                            @endphp
                            <li class="{{ str_replace('om-b-', 'om-tl-', $toClass) }} {{ $loop->first ? 'om-tl-current' : '' }}">
                                <span class="om-tl-dot"><i class="fa {{ $toIcon }}"></i></span>
                                <div class="om-tl-card">
                                    <div class="om-tl-head">
                                        <div>
                                            <p class="om-tl-title">{{ $log->reason ?: __('hourly.'.$log->to_status) }}@if($loop->first)<span class="om-tl-now">الحالية</span>@endif</p>
                                            <div class="om-tl-flow">
                                                @if($changed)<span class="om-badge om-b-grey">{{ __('hourly.'.$log->from_status) }}</span><i class="fa fa-arrow-left"></i>@endif
                                                <span class="om-badge {{ $toClass }}"><i class="fa {{ $toIcon }}"></i> {{ __('hourly.'.$log->to_status) }}</span>
                                            </div>
                                        </div>
                                        <div class="om-tl-when">
                                            <b class="om-ltr om-num">{{ $log->created_at?->format('Y-m-d') }}</b>
                                            <span class="om-ltr om-num">{{ $log->created_at?->format('H:i') }}</span>
                                        </div>
                                    </div>
                                    <div class="om-tl-actor">
                                        <i class="fa {{ $actorIcon }}"></i> {{ $actorRole }}@if($actorName): <b>{{ $actorName }}</b>@elseif($log->actor_id) <span class="om-ltr om-num">#{{ $log->actor_id }}</span>@endif
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            @include('admin.hourly._order-audit-reviews')

            <section id="om-notes" class="tab-pane" role="tabpanel" aria-labelledby="om-tab-notes">
                @if($order->notes)<div class="om-note"><p>{{ $order->notes }}</p><small>ملاحظة مرفقة بالطلب عند الإنشاء</small></div>@endif
                @forelse($notes->reverse() as $note)
                    <div class="om-note">
                        <span class="om-badge {{ $note['visit_id'] ? 'om-b-blue' : 'om-b-grey' }}">{{ $note['visit_id'] ? 'زيارة #'.$note['visit_id'] : 'الطلب بالكامل' }}</span>
                        <p class="mt-2">{{ $note['text'] }}</p>
                        <span class="badge bg-light text-dark">{{ __('operational_notes.'.$note['audience']) }}</span>
                        <small><i class="fa fa-user-pen"></i> {{ $note['author'] }} — <span class="om-ltr">{{ $note['created_at'] }}</span></small>
                    </div>
                @empty
                    @unless($order->notes)<div class="om-empty"><i class="fa fa-note-sticky"></i> لا توجد ملاحظات إضافية.</div>@endunless
                @endforelse
                @include('admin.hourly._note-form', ['visitId' => null])
            </section>

            <section id="om-payments" class="tab-pane" role="tabpanel" aria-labelledby="om-tab-payments">
                @include('admin.hourly._order-collection')
                <div class="table-responsive">
                    <table class="om-table">
                        <thead><tr><th>المبلغ</th><th>الطريقة</th><th>الحالة</th><th>التاريخ</th><th>المستندات</th></tr></thead>
                        <tbody>
                        @forelse($order->payments as $payment)
                            @php [$rowClass, $rowIcon] = $statusTone($payment->settlement_status); @endphp
                            <tr>
                                <td><b class="om-num">{{ $money($payment->amount) }}</b></td>
                                <td>{{ __('hourly.'.$payment->method) }}</td>
                                <td><span class="om-badge {{ $rowClass }}"><i class="fa {{ $rowIcon }}"></i> {{ __('hourly.'.$payment->settlement_status) }}</span></td>
                                <td class="om-ltr om-num">{{ optional($payment->paid_at)->format('Y-m-d H:i') ?: '—' }}</td>
                                <td>
                                    @if($payment->method === 'bank_transfer')
                                        @if(!empty($payment->provider_payload['reference_number']))
                                            <div class="om-kv"><span>رقم المرجع:</span><b class="om-ltr om-num">{{ $payment->provider_payload['reference_number'] }}</b></div>
                                        @endif
                                        @if(!empty($payment->provider_payload['rejection_reason']))
                                            <div class="alert alert-danger py-2 px-3 mb-2">سبب الرفض: {{ $payment->provider_payload['rejection_reason'] }}</div>
                                        @endif
                                    @endif
                                    @if($payment->status === 'paid' && (float) $payment->amount > 0)
                                        <a class="om-btn om-btn-soft" style="width:auto;margin:0 .15rem" target="_blank" href="{{ route('admin.hourly-orders.receipt', [$order, $payment]) }}"><i class="fa fa-receipt"></i> سند قبض</a>
                                    @endif
                                    @if($payment->attachment_path)
                                        <a class="om-btn om-btn-soft" style="width:auto;margin:0 .15rem" target="_blank" href="{{ route('admin.hourly-orders.attachment', [$order, $payment]) }}"><i class="fa fa-paperclip"></i> عرض الإيصال</a>
                                    @elseif($payment->status !== 'paid' || (float) $payment->amount <= 0)
                                        —
                                    @endif
                                    @if($payment->method === 'bank_transfer' && $payment->status === 'pending_review' && AdminGuardHasPerm('hourly_orders_collect'))
                                        <div class="d-flex gap-2 flex-wrap mt-2">
                                            <form method="post" action="{{ route('admin.hourly-orders.bank-transfer-review',[$order,$payment]) }}" data-confirm-title="قبول التحويل" data-confirm="اعتماد التحويل وتسجيل المبلغ مدفوعًا؟" data-confirm-button="نعم، اعتماد" data-confirm-danger="0">
                                                @csrf
                                                <input type="hidden" name="decision" value="accept">
                                                <button class="btn btn-sm btn-success" type="submit"><i class="fa fa-check"></i> قبول التحويل</button>
                                            </form>
                                            <form method="post" action="{{ route('admin.hourly-orders.bank-transfer-review',[$order,$payment]) }}" class="d-flex gap-1">
                                                @csrf
                                                <input type="hidden" name="decision" value="reject">
                                                <input class="form-control form-control-sm" name="rejection_reason" required maxlength="1000" placeholder="سبب الرفض">
                                                <button class="btn btn-sm btn-danger" type="submit"><i class="fa fa-xmark"></i> رفض</button>
                                            </form>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><div class="om-empty"><i class="fa fa-receipt"></i> لا توجد دفعات مسجلة.</div></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
            </div>
        </div>
    </div>
</main>
</div>
</div>
@endsection
@push('js')
@include('admin.hourly._confirm-script')
<script src="{{ asset('admin/assets/js/hourly-order-operations.js') }}?v={{ filemtime(public_path('admin/assets/js/hourly-order-operations.js')) }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tabs = [...document.querySelectorAll('[id^="om-tab-"]')];
    const active = tabs.find(tab => tab.dataset.bsTarget === location.hash);
    if (active && window.bootstrap) bootstrap.Tab.getOrCreateInstance(active).show();
    tabs.forEach(tab => tab.addEventListener('shown.bs.tab', () => history.replaceState(null, '', tab.dataset.bsTarget)));

    const copy = document.getElementById('om-copy-card');
    if (copy) copy.addEventListener('click', async function () {
        const text = ['om-customer-name', 'om-customer-phone']
            .map(id => document.getElementById(id)).filter(Boolean)
            .map(node => node.innerText.trim()).join('\n');
        try { await navigator.clipboard.writeText(text); this.innerHTML = '<i class="fa fa-check"></i>'; }
        catch (_) { this.innerHTML = '<i class="fa fa-xmark"></i>'; }
        setTimeout(() => { this.innerHTML = '<i class="fa fa-copy"></i>'; }, 1500);
    });

    // تصفية بطاقات الزيارات؛ البطاقة المخفية لا تُشمل في «تحديد الكل».
    const filters = [...document.querySelectorAll('[data-visit-filter]')];
    filters.forEach(button => button.addEventListener('click', () => {
        const key = button.dataset.visitFilter;
        filters.forEach(other => { other.classList.toggle('active', other === button); other.setAttribute('aria-pressed', String(other === button)); });
        document.querySelectorAll('[data-visit-card]').forEach(card => { card.hidden = key !== 'all' && !card.dataset.buckets.split(' ').includes(key); });
    }));
});
</script>
@endpush
