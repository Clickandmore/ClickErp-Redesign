{{-- هوية «عملاء الساعات»: نفس ألوان وحدة الساعات (الأزرق مع الفيروزي). --}}
@include('admin.hourly._modal-theme')
<style>
    .hc { --brand: #2794eb; --brand-ink: #1b6fb8; --brand-deep: #14507f; --teal: #00ac9a; --ink: #16212e; --muted: #6b7a8c; --line: #e6ecf3; --soft: #f6f9fc; --pos: #0f7a5a; --neg: #c0262d; color: var(--ink); }
    .hc [hidden] { display: none !important; }
    .hc-box { background: #fff; border: 1px solid var(--line); border-radius: 18px; box-shadow: 0 10px 30px rgba(20, 80, 127, .06); margin-bottom: 1.1rem; }
    .hc-head { display: flex; align-items: center; justify-content: space-between; gap: .75rem; flex-wrap: wrap; padding: .9rem 1.2rem; border-bottom: 1px solid var(--line); }
    .hc-head h3 { margin: 0; font-size: 1.05rem; font-weight: 800; color: var(--brand-deep); display: flex; align-items: center; gap: .5rem; }
    .hc-body { padding: 1.1rem 1.2rem; }
    .hc-btn { height: 40px; padding: 0 .95rem; border-radius: 10px; border: 1px solid var(--line); background: #fff; color: var(--ink); display: inline-flex; align-items: center; gap: .45rem; font-weight: 700; font-size: .84rem; text-decoration: none; white-space: nowrap; cursor: pointer; }
    .hc-btn:hover { border-color: var(--brand); color: var(--brand-ink); }
    .hc-btn.is-primary { background: var(--brand-ink); border-color: var(--brand-ink); color: #fff; }
    .hc-btn.is-primary:hover { background: var(--brand-deep); color: #fff; }
    .hc-btn.is-green { background: #17996f; border-color: #17996f; color: #fff; }
    .hc-btn.is-amber { background: #f59e0b; border-color: #f59e0b; color: #fff; }
    .hc-btn.is-sm { height: 32px; padding: 0 .7rem; font-size: .78rem; }
    .hc-badge { display: inline-flex; align-items: center; gap: .3rem; border-radius: 99px; padding: .15rem .6rem; font-size: .74rem; font-weight: 800; background: #eef3f8; color: #445466; white-space: nowrap; }
    .hc-badge.is-count { background: #fde68a; color: #7a4a00; }
    .hc-badge.is-source { background: #7a1d1d; color: #fff; }
    .hc-badge.is-blocked { background: #fee2e2; color: #991b1b; }
    .hc-money { font-weight: 800; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .hc-money.is-pos { color: var(--pos); }
    .hc-money.is-neg { color: var(--neg); }
    .hc-muted { color: var(--muted); font-size: .78rem; }
    .hc-empty { padding: 2.5rem 1rem; text-align: center; color: var(--muted); }

    /* الفلاتر */
    .hc-filters { display: grid; gap: 1rem; }
    .hc-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 210px), 1fr)); gap: .9rem 1rem; align-items: end; }
    .hc-field > label, .hc-field > span.hc-label { display: flex; align-items: center; gap: .35rem; font-size: .78rem; font-weight: 700; color: var(--muted); margin-bottom: .35rem; }
    .hc-field .form-control, .hc-field .form-select { height: 40px; border-radius: 10px; border-color: #dfe7ef; font-size: .85rem; }
    .hc-field .is-set { border-color: #9fd0f4; background-color: #f2f9ff; font-weight: 700; color: var(--brand-deep); }
    .hc-seg { display: flex; flex-wrap: wrap; border: 1px solid #dfe7ef; border-radius: 10px; overflow: hidden; }
    .hc-seg label { flex: 1 1 auto; margin: 0; position: relative; }
    .hc-seg input { position: absolute; opacity: 0; }
    .hc-seg span { display: flex; align-items: center; justify-content: center; gap: .3rem; height: 38px; padding: 0 .7rem; font-size: .8rem; font-weight: 700; color: var(--muted); cursor: pointer; white-space: nowrap; border-inline-end: 1px solid #eef2f6; }
    .hc-seg label:last-child span { border-inline-end: 0; }
    .hc-seg input:checked + span { background: var(--brand-ink); color: #fff; }
    .hc-seg input:focus-visible + span { box-shadow: inset 0 0 0 2px #9fd0f4; }
    .hc-seg .is-pos input:checked + span { background: var(--pos); }
    .hc-seg .is-neg input:checked + span { background: var(--neg); }
    .hc-actions { display: flex; gap: .5rem; flex-wrap: wrap; align-items: center; padding-top: .9rem; border-top: 1px solid var(--line); }
    .hc-collapse { border: 0; background: none; color: var(--muted); font-size: 1rem; }

    /* الجدول */
    .hc-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: .84rem; }
    .hc-table th { background: var(--soft); color: var(--muted); font-weight: 700; font-size: .76rem; padding: .65rem .6rem; border-bottom: 1px solid var(--line); white-space: nowrap; }
    .hc-table td { padding: .7rem .6rem; border-bottom: 1px solid var(--line); vertical-align: middle; }
    .hc-table tr:hover td { background: #fbfdff; }
    .hc-table a.hc-name { font-weight: 800; color: var(--brand-ink); text-decoration: none; }
    .hc-table a.hc-name:hover { text-decoration: underline; }
    .hc-summary { display: grid; gap: .15rem; font-size: .84rem; font-weight: 700; }
    .hc-summary b { font-variant-numeric: tabular-nums; }
    .hc-switch { width: 2.4em; height: 1.3em; cursor: pointer; }

    /* الملف */
    .hc-layout { display: grid; grid-template-columns: minmax(260px, 320px) 1fr; gap: 1.1rem; align-items: start; }
    @media (max-width: 991px) { .hc-layout { grid-template-columns: 1fr; } }
    .hc-side { padding: 1.1rem; display: grid; gap: .9rem; }
    .hc-who h2 { margin: 0; font-size: 1.15rem; font-weight: 800; display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
    .hc-copy { display: flex; align-items: center; justify-content: space-between; gap: .5rem; font-size: .85rem; color: #374151; }
    .hc-copy button { width: 30px; height: 30px; border-radius: 8px; border: 1px solid var(--line); background: #fff; color: var(--muted); }
    .hc-stat { display: flex; align-items: flex-start; justify-content: space-between; gap: .75rem; padding: .6rem 0; border-bottom: 1px dashed var(--line); font-size: .84rem; }
    .hc-stat:last-child { border-bottom: 0; }
    .hc-stat span { color: var(--muted); font-weight: 700; display: flex; align-items: center; gap: .35rem; }
    .hc-stat b { text-align: end; font-variant-numeric: tabular-nums; }
    .hc-stat small { display: block; color: var(--muted); font-weight: 500; }
    .hc-balance { border-radius: 14px; padding: .9rem 1rem; background: linear-gradient(135deg, #eff8ff, #ecfdf5); display: flex; align-items: center; justify-content: space-between; gap: .5rem; }
    .hc-balance b { font-size: 1.35rem; }
    .hc-tabs { display: flex; gap: .25rem; flex-wrap: wrap; padding: .6rem .8rem 0; border-bottom: 1px solid var(--line); }
    .hc-tabs a { padding: .65rem .95rem; border-radius: 10px 10px 0 0; font-weight: 700; font-size: .84rem; color: var(--muted); text-decoration: none; display: inline-flex; align-items: center; gap: .4rem; }
    .hc-tabs a:hover { color: var(--brand-ink); background: var(--soft); }
    .hc-tabs a.active { color: var(--brand-ink); background: #eff8ff; box-shadow: inset 0 -3px 0 var(--brand); }
    .hc-form { display: grid; gap: 1.2rem; }
    .hc-form fieldset { border: 1px solid var(--line); border-radius: 14px; padding: .9rem 1rem 1rem; margin: 0; }
    .hc-form legend { float: none; width: auto; padding: 0 .4rem; margin: 0; font-size: .86rem; font-weight: 800; color: var(--brand-ink); }
    .hc-form .form-text { font-size: .74rem; color: var(--muted); }
    .hc-note { display: flex; gap: .5rem; align-items: flex-start; border-radius: 12px; padding: .7rem .9rem; font-size: .8rem; background: #f0f9ff; border: 1px solid #bae6fd; color: #075985; }
    .hc-note.is-warn { background: #fffbeb; border-color: #fde68a; color: #92400e; }
    .hc-age { display: inline-flex; align-items: center; padding: 0 .75rem; border: 1px solid #dfe7ef; border-inline-start: 0; border-radius: 0 10px 10px 0; background: var(--soft); font-size: .8rem; font-weight: 700; color: var(--muted); white-space: nowrap; }
    [dir=rtl] .hc-age { border-radius: 10px 0 0 10px; }
    .hc-inline { display: flex; }
    .hc-inline .form-control { border-start-end-radius: 0; border-end-end-radius: 0; }
    .hc-address { border: 1px solid var(--line); border-radius: 14px; padding: .8rem 1rem; display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
    .hc-address b { display: block; }
    #hc-address-map { height: 300px; border-radius: 12px; margin-top: .35rem; }
    .hc-changes { margin: 0; padding: 0; list-style: none; display: grid; gap: .2rem; font-size: .78rem; }
    .hc-changes code { color: var(--brand-deep); }
    .hc-modes { display: grid; gap: .45rem; }
    .hc-modes label { display: flex !important; align-items: flex-start; gap: .55rem; border: 1px solid var(--om-line, #e6ecf3); border-radius: 12px; padding: .6rem .75rem; margin: 0 !important; cursor: pointer; font-weight: 600 !important; }
    .hc-modes label:has(input:checked) { border-color: var(--om-brand, #2794eb); background: #eff8ff; }
    .hc-modes small { display: block; color: var(--om-muted, #6b7a8c); font-weight: 500; }

    /* القائمة: الترتيب، المحذوف، المجموعة، الطلبات المكتملة */
    .hc-check { display: inline-flex; align-items: center; gap: .4rem; margin: 0; font-size: .82rem; font-weight: 700; color: var(--muted); cursor: pointer; }
    .hc-check input { width: 16px; height: 16px; accent-color: var(--brand-ink); }
    .hc-sort { color: inherit; text-decoration: none; display: inline-flex; align-items: center; gap: .3rem; white-space: nowrap; }
    .hc-sort i { opacity: .45; font-size: .72rem; }
    .hc-sort.is-active { color: var(--brand-ink); }
    .hc-sort.is-active i { opacity: 1; }
    .hc-done { display: inline-flex; align-items: center; gap: .2rem; margin-inline-start: .35rem; font-size: .72rem; font-weight: 800; color: #0f7a5a; background: #dcfce7; border-radius: 99px; padding: 0 .45rem; }
    .hc-done.is-zero { color: #94a3b8; background: #f1f5f9; }
    .hc-badge.is-group { background: #ede9fe; color: #5b21b6; }
    .hc-table tr.is-deleted td { background: #fff7f7; color: #9ca3af; }
    .hc-table tr.is-deleted a.hc-name { color: #b42318; text-decoration: line-through; }

    /* الملف: البطاقات والأزرار */
    .hc-buckets { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .75rem; margin-bottom: 1.1rem; }
    @media (max-width: 767px) { .hc-buckets { grid-template-columns: 1fr; } }
    .hc-bucket { border-radius: 16px; padding: .85rem 1rem; border: 1px solid var(--line); background: #fff; display: grid; gap: .45rem; box-shadow: 0 6px 18px rgba(20, 80, 127, .05); }
    .hc-bucket h4 { margin: 0; font-size: .9rem; font-weight: 800; display: flex; align-items: center; gap: .45rem; }
    .hc-bucket.is-completed h4 { color: #0f7a5a; } .hc-bucket.is-pending h4 { color: #b45309; } .hc-bucket.is-cancelled h4 { color: #b42318; }
    .hc-bucket-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: .4rem; }
    .hc-bucket-stats div { background: var(--soft); border-radius: 10px; padding: .35rem .5rem; text-align: center; }
    .hc-bucket-stats b { display: block; font-size: 1.1rem; font-variant-numeric: tabular-nums; }
    .hc-bucket-stats span { font-size: .72rem; color: var(--muted); font-weight: 700; }
    .hc-toolbar { display: flex; gap: .5rem; flex-wrap: wrap; }
    .hc-btn.is-danger { background: #fff; border-color: #fca5a5; color: #b42318; }
    .hc-btn.is-danger:hover { background: #fee2e2; }
    .hc-banner { display: flex; gap: .6rem; align-items: center; justify-content: space-between; flex-wrap: wrap; border-radius: 14px; padding: .75rem 1rem; margin-bottom: 1rem; background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; font-weight: 700; }

    /* التقويم */
    .hc-cal { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); border: 1px solid var(--line); border-radius: 12px; overflow: hidden; }
    .hc-cal-head { background: var(--soft); font-size: .74rem; font-weight: 800; color: var(--muted); text-align: center; padding: .45rem .2rem; border-bottom: 1px solid var(--line); }
    .hc-cal-day { min-height: 96px; border-inline-end: 1px solid var(--line); border-bottom: 1px solid var(--line); padding: .3rem; display: grid; align-content: start; gap: .25rem; }
    .hc-cal-day.is-out { background: #fafbfc; color: #b6c2cf; }
    .hc-cal-day.is-today { box-shadow: inset 0 0 0 2px var(--brand); }
    .hc-cal-num { font-size: .74rem; font-weight: 800; }
    .hc-cal-visit { display: block; border-radius: 7px; padding: .2rem .35rem; font-size: .7rem; line-height: 1.35; text-decoration: none; background: #eff8ff; color: var(--brand-deep); border-inline-start: 3px solid var(--brand); }
    .hc-cal-visit.is-completed { background: #ecfdf5; border-color: #10b981; color: #065f46; }
    .hc-cal-visit.is-cancelled { background: #fef2f2; border-color: #ef4444; color: #991b1b; text-decoration: line-through; }
    .hc-cal.is-week .hc-cal-day { min-height: 220px; }
    @media (max-width: 767px) { .hc-cal { grid-template-columns: 1fr; } .hc-cal-head { display: none; } .hc-cal-day.is-out { display: none; } }

    /* بطاقات الهوية والتقييمات والمفضلة */
    .hc-docs { display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 320px), 1fr)); gap: .8rem; }
    .hc-doc { border: 1px solid var(--line); border-radius: 14px; padding: .85rem; display: grid; gap: .5rem; }
    .hc-doc-images { display: grid; grid-template-columns: 1fr 1fr; gap: .4rem; }
    .hc-doc-images a { display: grid; place-items: center; height: 110px; border-radius: 10px; background: var(--soft); border: 1px dashed var(--line); overflow: hidden; text-decoration: none; color: var(--muted); font-size: .76rem; }
    .hc-doc-images img { width: 100%; height: 100%; object-fit: cover; }
    .hc-status.is-pending { background: #fef3c7; color: #92400e; } .hc-status.is-verified { background: #dcfce7; color: #166534; } .hc-status.is-rejected { background: #fee2e2; color: #991b1b; }
    .hc-stars { color: #f59e0b; letter-spacing: .05em; white-space: nowrap; }
    .hc-stars .is-off { color: #e5e7eb; }
    .hc-chips { display: flex; flex-wrap: wrap; gap: .4rem; }
    .hc-chip { display: inline-flex; align-items: center; gap: .4rem; border-radius: 99px; padding: .3rem .4rem .3rem .75rem; background: #eff8ff; border: 1px solid #c4e2f8; font-size: .82rem; font-weight: 700; color: var(--brand-deep); }
    .hc-chip button { border: 0; width: 22px; height: 22px; border-radius: 50%; background: rgba(27, 111, 184, .12); color: inherit; display: grid; place-items: center; font-size: .7rem; }
    .hc-group-card { border: 1px solid var(--line); border-radius: 14px; padding: .85rem 1rem; display: grid; gap: .6rem; }
    .hc-member { display: grid; grid-template-columns: auto 1fr auto; gap: .6rem; align-items: center; padding: .45rem .6rem; border-radius: 10px; background: var(--soft); font-size: .83rem; }

    /* الفلاتر: شبكة ثابتة الأعمدة؛ البحث والرصيد والنشاط بعرض عمودين، وأزرار الاختيار في سطر واحد */
    .hc-filters .hc-row { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    .hc-filters .hc-field.is-wide { grid-column: span 2; }
    @media (max-width: 1199px) { .hc-filters .hc-row { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 575px) { .hc-filters .hc-row { grid-template-columns: 1fr; } .hc-filters .hc-field.is-wide { grid-column: auto; } }
    .hc-filters .hc-seg { flex-wrap: nowrap; overflow-x: auto; scrollbar-width: none; }
    .hc-filters .hc-seg label { flex: 1 1 0; min-width: max-content; }
    .hc-filters .hc-seg span { height: 38px; }

    /* الجدول: خلايا لا تتكسر، والاسم والتواصل بعرض مناسب */
    .hc-table th.is-num, .hc-table td.is-num { text-align: center; }
    .hc-th-stack { justify-items: center !important; }
    .hc-table th.is-actions, .hc-table td.is-actions { text-align: center; white-space: nowrap; width: 1%; }
    .hc-table td.is-actions form { display: inline; }
    .hc-icon-btn { width: 34px; height: 34px; border-radius: 10px; border: 1px solid var(--line); background: #fff; color: var(--brand-ink); display: inline-grid; place-items: center; text-decoration: none; font-size: .85rem; cursor: pointer; transition: all .15s; }
    .hc-icon-btn:hover { border-color: var(--brand); background: #eff8ff; }
    .hc-icon-btn.is-primary { background: var(--brand-ink); border-color: var(--brand-ink); color: #fff; }
    .hc-icon-btn.is-primary:hover { background: var(--brand-deep); }
    .hc-icon-btn.is-green { background: #17996f; border-color: #17996f; color: #fff; }
    .hc-icon-btn + .hc-icon-btn, form + .hc-icon-btn { margin-inline-start: .25rem; }
    .hc-table tbody tr { transition: background .15s; }
    .hc-id { font-size: .76rem; font-weight: 800; color: var(--muted); font-variant-numeric: tabular-nums; white-space: nowrap; }
    .hc-cell-name { min-width: 180px; }
    .hc-th-stack { display: grid; gap: .15rem; justify-items: end; }
    .hc-name-line { display: flex; align-items: center; gap: .35rem; flex-wrap: wrap; }
    .hc-name-line .hc-done { margin: 0; }
    .hc-tags { display: flex; align-items: center; gap: .3rem .5rem; flex-wrap: wrap; margin-top: .25rem; }
    .hc-tags:empty { display: none; }
    .hc-badge.is-verified { background: #dcfce7; color: #166534; }
    .hc-badge.is-source { background: #fdf2f2; color: #9b1c1c; border: 1px solid #fbd5d5; }
    .hc-cell-contact { min-width: 150px; max-width: 175px; }
    .hc-phone, .hc-email { display: flex; align-items: center; gap: .35rem; white-space: nowrap; direction: ltr; justify-content: flex-end; }
    [dir=ltr] .hc-phone, [dir=ltr] .hc-email { justify-content: flex-start; }
    .hc-phone { font-weight: 700; font-variant-numeric: tabular-nums; }
    .hc-email { font-size: .76rem; color: var(--muted); margin-top: .15rem; max-width: 160px; margin-inline-start: auto; }
    [dir=ltr] .hc-email { margin-inline-start: 0; }
    .hc-email span { overflow: hidden; text-overflow: ellipsis; min-width: 0; }
    .hc-phone i, .hc-email i { font-size: .7rem; color: #9fb0c2; }
    .hc-date { font-weight: 700; font-variant-numeric: tabular-nums; white-space: nowrap; margin-bottom: .2rem; text-align: start; }
    .hc-date small { color: var(--muted); font-weight: 600; }
    .hc-money.is-zero, .hc-count.is-zero { color: #a3b1c0; font-weight: 700; }
    .hc-count { display: inline-grid; place-items: center; min-width: 30px; height: 26px; padding: 0 .45rem; border-radius: 8px; background: #eff8ff; color: var(--brand-ink); font-weight: 800; font-variant-numeric: tabular-nums; }
    .hc-count.is-zero { background: #f4f6f9; }
    .hc-list-foot { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: .75rem; padding: .9rem 1.2rem; border-top: 1px solid var(--line); background: #fcfdfe; border-radius: 0 0 18px 18px; }

    .hc-pill { display: inline-flex; align-items: center; gap: .4rem; height: 40px; padding: 0 .85rem; border-radius: 10px; font-size: .8rem; font-weight: 700; white-space: nowrap; }
    .hc-pill.is-pos { background: #ecfdf5; color: var(--pos); border: 1px solid #bbf7d0; }
    .hc-pill.is-neg { background: #fef2f2; color: var(--neg); border: 1px solid #fecaca; }
    .hc-pill b { font-size: .92rem; }

    /* ترقيم الصفحات */
    .hc-pager { display: flex; align-items: center; gap: .75rem 1rem; flex-wrap: wrap; justify-content: space-between; flex: 1 1 auto; }
    .hc-pager-info { font-size: .8rem; font-weight: 700; color: var(--muted); font-variant-numeric: tabular-nums; }
    .hc-pager ul { display: flex; gap: .3rem; list-style: none; margin: 0; padding: 0; flex-wrap: wrap; }
    .hc-pager a, .hc-pager span.is-active, .hc-pager span.is-disabled, .hc-pager span.is-gap { min-width: 36px; height: 36px; padding: 0 .55rem; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center;
        font-size: .82rem; font-weight: 700; font-variant-numeric: tabular-nums; text-decoration: none; border: 1px solid var(--line); background: #fff; color: var(--ink); }
    .hc-pager a:hover { border-color: var(--brand); color: var(--brand-ink); background: #f2f9ff; }
    .hc-pager span.is-active { background: var(--brand-ink); border-color: var(--brand-ink); color: #fff; box-shadow: 0 4px 12px rgba(27, 111, 184, .25); }
    .hc-pager span.is-disabled { color: #c3cdd8; background: var(--soft); cursor: not-allowed; }
    .hc-pager span.is-gap { border-color: transparent; background: none; color: var(--muted); min-width: 20px; padding: 0; }
    .hc-body > .hc-pager, .mt-2 > .hc-pager { margin-top: .5rem; }

    /* نموذج الإنشاء والتعديل: أقسام كبطاقات، وحقول تبدأ من الأعلى، وشريط حفظ ثابت */
    .hc-form .hc-row { align-items: start; }
    .hc-form fieldset { background: #fff; padding: 1.1rem 1.2rem 1.2rem; }
    .hc-form legend { display: inline-flex; align-items: center; gap: .5rem; font-size: .92rem; }
    .hc-form legend i { width: 30px; height: 30px; border-radius: 9px; display: inline-grid; place-items: center; background: #eff8ff; color: var(--brand-ink); font-size: .8rem; }
    .hc-form .form-text { line-height: 1.5; margin-top: .3rem; }
    .hc-form .input-group-text { border-radius: 10px 0 0 10px; background: var(--soft); border-color: #dfe7ef; font-weight: 700; font-size: .85rem; color: var(--muted); }
    .hc-form .input-group .form-control { height: 40px; border-color: #dfe7ef; font-size: .85rem; }
    .hc-form .form-control:focus, .hc-form .form-select:focus { border-color: #9fd0f4; box-shadow: 0 0 0 .2rem rgba(39, 148, 235, .12); }
    .hc-form textarea.form-control { border-radius: 10px; border-color: #dfe7ef; font-size: .85rem; min-height: 90px; }
    .hc-form .hc-actions { position: sticky; bottom: 0; z-index: 5; margin: 0 -1.2rem -1.1rem; padding: .85rem 1.2rem; background: rgba(255, 255, 255, .96); backdrop-filter: blur(6px); border-radius: 0 0 18px 18px; box-shadow: 0 -8px 20px rgba(20, 80, 127, .06); }
    .hc-page-head { display: flex; align-items: center; gap: .85rem; }
    .hc-page-head .hc-icon { width: 46px; height: 46px; border-radius: 14px; display: grid; place-items: center; background: linear-gradient(135deg, #2794eb, #00ac9a); color: #fff; font-size: 1.1rem; box-shadow: 0 8px 18px rgba(39, 148, 235, .25); }
    .hc-page-head h3 { margin: 0; }
    .hc-page-head p { margin: .15rem 0 0; font-size: .8rem; color: var(--muted); max-width: 720px; }
</style>
