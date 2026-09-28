@extends('admin.layout.indexs.index')
@section('page-title', ['all'=>'قائمة الزيارات','unassigned'=>'الزيارات غير المخصصة','past'=>'زيارات ماضية غير منتهية','latest'=>'آخر الزيارات للطلب'][$mode])
@section('style')
@include('admin.hourly_teams._style')
<style>
    /* ——— صفحة تشغيل الزيارات بهوية وحدة الساعات: الأزرق #2794eb مع الفيروزي #00ac9a ——— */
    .vl { --brand: #2794eb; --brand-ink: #1b6fb8; --brand-deep: #14507f; --teal: #00ac9a; --teal-deep: #068a7d; --ink: #16212e; --muted: #6b7a8c; --line: #e6ecf3; --soft: #f6f9fc; color: var(--ink); }
    .vl [hidden] { display: none !important; }
    .vl a:focus-visible, .vl button:focus-visible, .vl input:focus-visible, .vl select:focus-visible { outline: 3px solid #9fd0f4; outline-offset: 2px; }
    .vl-card-box { background: #fff; border: 1px solid var(--line); border-radius: 18px; box-shadow: 0 10px 30px rgba(20, 80, 127, .06); margin-bottom: 1.25rem; }

    /* الترويسة وتبويبات الأوضاع */
    .vl-hero { position: relative; border-radius: 18px; overflow: hidden; background: linear-gradient(105deg, var(--brand-deep) 0%, var(--brand-ink) 42%, var(--brand) 78%, var(--teal) 140%); color: #fff; padding: 1.3rem 1.5rem 0; margin-bottom: 1.25rem; box-shadow: 0 10px 30px rgba(20, 80, 127, .12); }
    .vl-hero-top { display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
    .vl-hero h3 { margin: 0; font-size: 1.35rem; font-weight: 700; color: #fff; display: flex; align-items: center; gap: .55rem; }
    .vl-hero p { margin: .3rem 0 0; font-size: .85rem; opacity: .85; }
    .vl-hero-btn { background: rgba(255, 255, 255, .95); color: var(--brand-deep); border-radius: 10px; padding: .5rem .95rem; font-size: .84rem; font-weight: 700; display: inline-flex; align-items: center; gap: .4rem; text-decoration: none; }
    .vl-hero-btn:hover { background: #fff; color: var(--brand-ink); }
    .vl-modes { display: flex; gap: .35rem; margin-top: 1.1rem; overflow-x: auto; }
    .vl-modes a { padding: .7rem 1.1rem; border-radius: 12px 12px 0 0; color: rgba(255, 255, 255, .85); text-decoration: none; font-weight: 600; font-size: .88rem; white-space: nowrap; display: inline-flex; align-items: center; gap: .45rem; }
    .vl-modes a:hover { background: rgba(255, 255, 255, .12); color: #fff; }
    .vl-modes a.active { background: #f4f7fb; color: var(--brand-ink); }

    .vl-notice { display: flex; gap: .6rem; align-items: flex-start; background: #fef8ec; border: 1px solid #f7ddb2; color: #8a5806; padding: .85rem 1rem; border-radius: 14px; font-size: .86rem; margin-bottom: 1.25rem; }

    /* ——— الفلتر ——— */
    .vf { padding: 1.1rem 1.2rem; }
    .vf-main { display: grid; grid-template-columns: minmax(240px, 1.4fr) auto minmax(290px, 1fr) auto; gap: .75rem; align-items: center; }
    @media (max-width: 1399px) { .vf-main { grid-template-columns: 1fr 1fr; } }
    @media (max-width: 767px) { .vf-main { grid-template-columns: 1fr; } }
    .vf-search { position: relative; }
    .vf-search i { position: absolute; top: 50%; inset-inline-start: .95rem; transform: translateY(-50%); color: #94a3b8; pointer-events: none; }
    .vf-search .form-control { height: 46px; padding-inline-start: 2.5rem; border-radius: 12px; background: var(--soft); border-color: var(--line); }
    .vf-presets { display: inline-flex; padding: .25rem; background: var(--soft); border: 1px solid var(--line); border-radius: 12px; gap: .15rem; flex-wrap: wrap; }
    .vf-presets a { padding: .45rem .8rem; border-radius: 9px; font-size: .82rem; font-weight: 600; color: var(--muted); text-decoration: none; white-space: nowrap; }
    .vf-presets a:hover { color: var(--brand-ink); background: #fff; }
    .vf-presets a.active { background: var(--brand-ink); color: #fff; box-shadow: 0 4px 12px rgba(27, 111, 184, .25); }
    .vf-range { display: flex; align-items: center; gap: .4rem; border: 1px solid var(--line); border-radius: 12px; padding: .2rem .45rem; background: #fff; }
    .vf-range i { color: var(--brand); }
    .vf-range input { border: 0; height: 38px; min-width: 0; flex: 1; font-size: .85rem; color: var(--ink); background: transparent; }
    .vf-range input:focus { outline: 0; }
    .vf-range span { color: var(--muted); font-size: .8rem; }
    .vf-actions { display: flex; gap: .5rem; }
    .vf-btn { height: 46px; border-radius: 12px; padding: 0 1rem; font-weight: 700; font-size: .86rem; display: inline-flex; align-items: center; gap: .45rem; border: 1px solid var(--line); background: #fff; color: var(--ink); white-space: nowrap; }
    .vf-btn:hover { border-color: var(--brand); color: var(--brand-ink); }
    .vf-btn[aria-expanded="true"] { background: #eff8ff; border-color: #bfe0f8; color: var(--brand-ink); }
    .vf-btn-primary { background: var(--brand-ink); border-color: var(--brand-ink); color: #fff; }
    .vf-btn-primary:hover { background: var(--brand-deep); color: #fff; }
    .vf-count { min-width: 22px; height: 22px; padding: 0 .4rem; border-radius: 99px; background: var(--brand); color: #fff; font-size: .72rem; display: inline-grid; place-items: center; }

    .vf-chips { display: flex; flex-wrap: wrap; align-items: center; gap: .45rem; margin-top: .9rem; padding-top: .9rem; border-top: 1px dashed var(--line); }
    .vf-chips > span { font-size: .78rem; color: var(--muted); margin-inline-end: .2rem; }
    .vf-chip { display: inline-flex; align-items: center; gap: .4rem; background: #eff8ff; border: 1px solid #c4e2f8; color: var(--brand-deep); border-radius: 99px; padding: .25rem .35rem .25rem .7rem; font-size: .8rem; text-decoration: none; }
    .vf-chip b { font-weight: 700; }
    .vf-chip i { width: 18px; height: 18px; border-radius: 50%; display: grid; place-items: center; background: rgba(27, 111, 184, .12); font-size: .65rem; }
    .vf-chip:hover { background: #fdeeee; border-color: #f7cdcf; color: #b42a2e; }
    .vf-chip:hover i { background: rgba(229, 72, 77, .15); }
    .vf-clear { font-size: .8rem; color: #b42a2e; text-decoration: none; font-weight: 600; margin-inline-start: .3rem; }

    /* مجموعات الفلاتر المتقدمة: بطاقات عريضة (اثنتان أو ثلاث في الصف)، وقائمة الاختيار المتعدد تأخذ عرض البطاقة كاملًا
       حتى لا يتزاحم شريط البحث والعدّاد و«تحديد الكل» داخل نصف عمود. */
    .vf-advanced { margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--line); display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 400px), 1fr)); gap: 1rem; align-items: start; }
    .vf-group { border: 1px solid var(--line); border-radius: 14px; padding: .9rem 1rem 1rem; background: linear-gradient(180deg, var(--soft), #fff 60%); min-width: 0; }
    .vf-group summary { margin: 0 0 .75rem; font-size: .85rem; font-weight: 700; color: var(--brand-ink); cursor: pointer; display: flex; align-items: center; gap: .45rem; list-style: none; }
    .vf-group summary::-webkit-details-marker { display: none; }
    .vf-group summary::after { content: '\f107'; font-family: 'Font Awesome 6 Free', 'Font Awesome 5 Free', FontAwesome; font-weight: 900; margin-inline-start: auto; color: var(--muted); transition: transform .15s; }
    .vf-group:not([open]) summary { margin-bottom: 0; }
    .vf-group:not([open]) summary::after { transform: rotate(90deg); }
    tr.is-selected { background: #eaf5fe; }
    .vf-fields { display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 170px), 1fr)); gap: .75rem; }
    .vf-field { min-width: 0; }
    .vf-field.is-wide, .vf-field.is-multi { grid-column: 1 / -1; }
    .vf-note { display: block; margin-top: .4rem; font-size: .74rem; color: var(--muted); }
    .vf-check { display: flex !important; align-items: center; gap: .45rem; margin: .35rem 0 0 !important; font-size: .78rem !important; color: var(--ink) !important; font-weight: 600; cursor: pointer; }
    .vf-check input { width: 16px; height: 16px; margin: 0; accent-color: var(--brand-ink); }
    /* قائمة الاختيار المتعدد داخل الفلاتر: أقصر وأكثف، وشريطها يلتف بدل أن يتداخل */
    .vf .hms { border-color: #dfe7ef; border-radius: 10px; }
    .vf .hms.has-value, .vf .hms:has(.hms-tag) { border-color: #9fd0f4; }
    .vf .hms-bar { flex-wrap: wrap; gap: .4rem .5rem; padding: .45rem .55rem; }
    .vf .hms-search { flex: 1 1 150px; min-width: 0; }
    .vf .hms-search input { height: 32px; }
    .vf .hms-tags { padding: .45rem .55rem 0; }
    .vf .hms-list { max-height: 168px; display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 150px), 1fr)); gap: 0 .25rem; align-content: start; }
    .vf .hms-option { padding: .35rem .5rem; font-size: .83rem; line-height: 1.35; align-items: flex-start; }
    .vf .hms-option input { margin-top: .15rem; }
    .vf .hms-empty { grid-column: 1 / -1; }
    .vf-field > label:not(.vf-check) { display: block; font-size: .76rem; color: var(--muted); margin-bottom: .3rem; font-weight: 600; }
    .vf-field .form-control, .vf-field .form-select { height: 40px; border-radius: 10px; border-color: #dfe7ef; font-size: .84rem; color: var(--ink); }
    .vf-field .form-control:focus, .vf-field .form-select:focus { border-color: #9fd0f4; box-shadow: 0 0 0 3px rgba(39, 148, 235, .15); }
    .vf-field .is-set { border-color: #9fd0f4; background-color: #f2f9ff; font-weight: 700; color: var(--brand-deep); }
    .vf-advanced-foot { grid-column: 1 / -1; display: flex; justify-content: flex-end; gap: .5rem; }

    /* ——— النتائج ——— */
    .vl-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: .9rem; margin-bottom: 1.25rem; }
    .vl-stat { position: relative; background: #fff; border: 1px solid var(--line); border-radius: 14px; padding: .95rem 1.1rem; display: flex; align-items: center; gap: .8rem; overflow: hidden; box-shadow: 0 6px 18px rgba(20, 80, 127, .05); }
    .vl-stat::before { content: ''; position: absolute; inset-block: 0; inset-inline-start: 0; width: 4px; background: var(--tone); }
    .vl-stat > i { width: 40px; height: 40px; flex: 0 0 40px; border-radius: 12px; display: grid; place-items: center; color: var(--tone); background: var(--tint); }
    .vl-stat strong { display: block; font-size: 1.35rem; line-height: 1.2; font-variant-numeric: tabular-nums; }
    .vl-stat span { font-size: .8rem; color: var(--muted); }
    .vl-t-blue { --tone: #2794eb; --tint: #eaf5fe; } .vl-t-green { --tone: #17996f; --tint: #e8f7f1; } .vl-t-amber { --tone: #e08a17; --tint: #fef5e7; } .vl-t-violet { --tone: #6b63d6; --tint: #f0effc; }

    .vl-results { padding: 1.1rem 1.2rem; }
    .vl-results-head { display: flex; align-items: center; justify-content: space-between; gap: .75rem; flex-wrap: wrap; }
    .vl-results-head h5 { margin: 0; font-size: 1rem; font-weight: 700; color: var(--brand-ink); }
    .vl-day { display: inline-flex; align-items: center; gap: .35rem; }
    .vl-day a { width: 36px; height: 36px; border-radius: 10px; border: 1px solid var(--line); display: grid; place-items: center; color: var(--ink); text-decoration: none; }
    .vl-day a:hover { border-color: var(--brand); color: var(--brand-ink); }
    .vl-day strong { padding: 0 .5rem; font-variant-numeric: tabular-nums; }
    .vl-toolbar { display: flex; align-items: center; justify-content: space-between; gap: .75rem; flex-wrap: wrap; background: var(--soft); border: 1px solid var(--line); border-radius: 14px; padding: .7rem .85rem; margin: 1rem 0; }
    .vl-toolbar label { margin: 0; font-size: .85rem; font-weight: 600; display: inline-flex; align-items: center; gap: .45rem; }
    .vl-toolbar .form-select { height: 38px; border-radius: 10px; font-size: .82rem; width: auto; border-color: #dfe7ef; }
    .vl-toolbar .btn { border-radius: 10px; font-weight: 700; font-size: .84rem; }
    .vl-toolbar .btn-primary { background: var(--brand-ink); border-color: var(--brand-ink); }
    .vl-count { min-width: 26px; height: 22px; border-radius: 99px; background: var(--brand-ink); color: #fff; font-size: .75rem; display: inline-grid; place-items: center; padding: 0 .45rem; }
    .vl-sort { display: flex; gap: .4rem; flex-wrap: wrap; align-items: center; }
    .vl-columns-toggle summary { cursor: pointer; font-size: .82rem; font-weight: 600; color: var(--muted); list-style: none; display: inline-flex; align-items: center; gap: .35rem; }
    .vl-columns-toggle summary::-webkit-details-marker { display: none; }
    .vl-columns { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .6rem; }
    .vl-columns label { font-size: .8rem; border: 1px solid var(--line); border-radius: 99px; padding: .2rem .7rem; display: inline-flex; gap: .35rem; align-items: center; background: #fff; cursor: pointer; }
    .vl-hint { font-size: .78rem; color: var(--muted); margin: .6rem 0 0; }

    /* بطاقة الزيارة: رأس بالشارات، ثم خلايا كبلاطات في 4 أعمدة (العاملات بعرض عمودين) حتى تكتمل الصفوف */
    /* بلا overflow:hidden حتى لا تُقصّ قائمة الدفع؛ الزوايا الدائرية تأتي من الرأس والشريط الجانبي. */
    .vl-card { position: relative; border: 1px solid var(--line); border-radius: 16px; margin: 1rem 0; background: #fff; transition: box-shadow .15s, border-color .15s; }
    .vl-card-head { border-start-start-radius: 15px; border-start-end-radius: 15px; }
    .vl-card::before { content: ''; position: absolute; inset-block: -1px; inset-inline-start: -1px; width: 5px; background: var(--row-tone, var(--brand)); border-start-start-radius: 16px; border-end-start-radius: 16px; z-index: 1; }
    .vl-card-foot .dropdown-menu { z-index: 1060; border-radius: 12px; border-color: var(--line); box-shadow: 0 12px 30px rgba(20, 80, 127, .16); padding: .35rem; }
    .vl-card-foot .dropdown-item { border-radius: 8px; padding: .5rem .75rem; }
    .vl-card:hover { box-shadow: 0 10px 26px rgba(20, 80, 127, .08); }
    .vl-card.is-selected { border-color: var(--brand); box-shadow: 0 0 0 3px rgba(39, 148, 235, .18); }
    .vl-card.is-selected .vl-card-head { background: #eef7ff; }
    .vl-card-head { display: flex; align-items: center; justify-content: space-between; gap: .6rem; flex-wrap: wrap; padding: .8rem 1.15rem; background: var(--soft); border-bottom: 1px solid var(--line); }
    .vl-card-title { display: flex; align-items: center; gap: .6rem; flex-wrap: wrap; }
    .vl-card-title input { width: 18px; height: 18px; accent-color: var(--brand-ink); cursor: pointer; }
    .vl-card-id { font-weight: 800; font-size: 1rem; color: var(--brand-ink); text-decoration: none; }
    .vl-card-id:hover { text-decoration: underline; }
    .vl-card-service { font-size: .8rem; color: var(--muted); display: inline-flex; align-items: center; gap: .3rem; }
    .vl-card-badges { display: flex; gap: .35rem; flex-wrap: wrap; }
    .vl-card-badges .fa-circle { font-size: .45rem; }

    .vl-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .7rem; padding: .9rem 1.15rem; }
    .vl-cell { background: var(--soft); border: 1px solid #edf1f6; border-radius: 12px; padding: .75rem .9rem; min-width: 0; overflow-wrap: anywhere; font-size: .85rem; display: flex; flex-direction: column; gap: .3rem; }
    .vl-cell-wide { grid-column: span 2; }
    .vl-cell h6 { font-size: .72rem; color: var(--muted); margin: 0 0 .2rem; font-weight: 700; display: flex; align-items: center; gap: .4rem; }
    .vl-cell h6 i { width: 20px; height: 20px; border-radius: 6px; display: inline-grid; place-items: center; background: #eaf5fe; color: var(--brand-ink); font-size: .65rem; }
    .vl-cell p { margin: 0; }
    .vl-cell a { color: var(--brand-ink); font-weight: 600; text-decoration: none; }
    .vl-cell a:hover { text-decoration: underline; }
    .vl-ellipsis { display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; text-align: end; font-variant-numeric: tabular-nums; font-size: .82rem; }
    .vl-strong { font-weight: 700 !important; color: var(--ink) !important; }
    .vl-phone { font-variant-numeric: tabular-nums; font-size: .82rem; }
    .vl-phone i { font-size: .72rem; opacity: .7; }
    .vl-kv { display: flex; justify-content: space-between; gap: .5rem; font-size: .8rem; }
    .vl-kv span { color: var(--muted); }
    .vl-kv b { color: var(--ink); font-weight: 700; }
    .vl-due { color: #b42a2e !important; }
    .vl-address { line-height: 1.55; color: var(--ink); }
    .vl-when b { font-weight: 700; }
    .vl-when span { color: var(--muted); font-size: .8rem; }
    .vl-time { font-size: 1.15rem; font-weight: 800; color: var(--brand-ink); }
    .vl-big { font-size: 1.2rem; font-weight: 800; color: var(--ink); line-height: 1.2; }
    .vl-big small { font-size: .75rem; color: var(--muted); font-weight: 600; }
    .vl-progress { height: 6px; border-radius: 99px; background: #e3eaf2; overflow: hidden; margin-top: .25rem; }
    .vl-progress span { display: block; height: 100%; border-radius: 99px; background: linear-gradient(90deg, var(--teal), #17996f); }

    /* أزرار صغيرة موحّدة داخل الخلايا */
    .vl-acts { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: auto; padding-top: .4rem; }
    .vl-act { display: inline-flex; align-items: center; gap: .3rem; border: 1px solid #dbe6f1; background: #fff; color: var(--brand-ink); border-radius: 8px; padding: .22rem .55rem; font-size: .74rem; font-weight: 700; text-decoration: none !important; white-space: nowrap; transition: background .15s, border-color .15s; }
    .vl-act i { font-size: .7rem; }
    .vl-act:hover { background: #eff8ff; border-color: #9fd0f4; color: var(--brand-deep); }
    .vl-act.is-primary { background: var(--brand-ink); border-color: var(--brand-ink); color: #fff; }
    .vl-act.is-primary:hover { background: var(--brand-deep); color: #fff; }
    .vl-act.is-warn { color: #9a6206; border-color: #f7ddb2; }
    .vl-act.is-warn:hover { background: #fef5e7; }
    .vl-act.is-danger { color: #b42a2e; border-color: #f7cdcf; }
    .vl-act.is-danger:hover { background: #fdeeee; }

    .vl-people { display: flex; flex-wrap: wrap; gap: .4rem; }
    .vl-person { display: inline-flex; align-items: center; gap: .35rem; background: #fff; border: 1px solid #dbe6f1; border-radius: 99px; padding: .2rem .35rem .2rem .7rem; }
    .vl-person button { border: 0; background: none; padding: 0; font-weight: 700; color: var(--ink); font-size: .82rem; }
    .vl-person button:hover { color: var(--brand-ink); text-decoration: underline; }
    .vl-person a { width: 22px; height: 22px; border-radius: 50%; display: grid; place-items: center; background: #eaf5fe; font-size: .68rem; }
    .vl-driver { display: flex; align-items: center; justify-content: space-between; gap: .4rem; padding: .25rem 0; border-bottom: 1px dashed #e3eaf2; }
    .vl-driver:last-child { border-bottom: 0; }
    .vl-driver-role { font-size: .76rem; color: var(--muted); display: inline-flex; align-items: center; gap: .3rem; }
    .vl-driver-name { display: inline-flex; align-items: center; gap: .3rem; }
    .vl-driver-name > button:not(.vl-remove) { border: 0; background: none; padding: 0; font-weight: 700; color: var(--ink); font-size: .82rem; }
    .vl-driver-name > button.is-empty { color: var(--brand-ink); text-decoration: underline dotted; }
    .vl-driver-name a { font-size: .75rem; }
    .vl-remove { width: 20px; height: 20px; border-radius: 50%; border: 0; display: grid; place-items: center; background: #fdeeee; color: #b42a2e; font-size: .62rem; padding: 0; }
    .vl-remove:hover { background: #f7cdcf; }

    .vl-muted { color: var(--muted); }
    .vl-warn { color: #b45309; font-weight: 700; display: inline-flex; align-items: center; gap: .3rem; }

    /* تذييل البطاقة: الإجراءات الأساسية في جهة والفواتير كروابط هادئة في الجهة الأخرى */
    .vl-card-foot { padding: .75rem 1.15rem; display: flex; align-items: center; justify-content: space-between; gap: .6rem; flex-wrap: wrap; border-top: 1px solid var(--line); }
    .vl-foot-main { display: flex; gap: .45rem; flex-wrap: wrap; align-items: center; }
    .vl-btn { display: inline-flex; align-items: center; gap: .4rem; border-radius: 10px; padding: .45rem .95rem; font-size: .82rem; font-weight: 700; border: 1px solid transparent; }
    .vl-btn.is-success { background: #17996f; color: #fff; }
    .vl-btn.is-success:hover { background: #0f7a5a; }
    .vl-btn.is-danger { background: #fff; color: #b42a2e; border-color: #f0b6b8; }
    .vl-btn.is-danger:hover { background: #fdeeee; }
    .vl-btn.is-primary { background: var(--brand-ink); color: #fff; }
    .vl-btn.is-primary:hover { background: var(--brand-deep); }
    .vl-card-foot .dropdown-item { font-size: .84rem; display: flex; align-items: center; gap: .45rem; }
    .vl-foot-links { display: flex; gap: 1rem; flex-wrap: wrap; }
    .vl-foot-links a { font-size: .8rem; font-weight: 600; color: var(--muted); text-decoration: none; display: inline-flex; align-items: center; gap: .35rem; }
    .vl-foot-links a:hover { color: var(--brand-ink); }
    .vl-num { font-variant-numeric: tabular-nums; }
    .vl-badge { display: inline-flex; align-items: center; gap: .3rem; border-radius: 8px; padding: .2rem .6rem; font-size: .76rem; font-weight: 700; border: 1px solid transparent; white-space: nowrap; }
    .vl-b-green { background: #e8f7f1; color: #0f7a5a; border-color: #bfe9d9; }
    .vl-b-grey { background: #eef3f8; color: #5a6b7d; border-color: #dbe4ee; }
    .vl-b-red { background: #fdeeee; color: #b42a2e; border-color: #f7cdcf; }
    .vl-b-amber { background: #fef5e7; color: #9a6206; border-color: #f7ddb2; }
    .vl-b-blue { background: #eaf5fe; color: #1b6fb8; border-color: #c4e2f8; }
    .vl-b-violet { background: #f0effc; color: #4f47b8; border-color: #d6d2f5; }
    .vl-empty { text-align: center; padding: 3rem 1rem; color: var(--muted); }
    .vl-empty i { font-size: 1.9rem; color: #c3d2e0; display: block; margin-bottom: .6rem; }
    @media (max-width: 1300px) { .vl-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 650px) { .vl-grid { grid-template-columns: 1fr; } .vl-cell-wide { grid-column: auto; } .vf, .vl-results { padding: .9rem; } .vl-hero { padding: 1rem 1rem 0; } }

    /* مودال الإجراءات الجماعية */
    .vl-modal-group { padding: .6rem 0; }
    .vl-modal-group label { display: block; margin: .5rem 0 .3rem; font-size: .84rem; font-weight: 600; }
</style>
@endsection
@section('content')
@php
    $titles = ['all' => 'قائمة الزيارات', 'unassigned' => 'الزيارات غير المخصصة', 'past' => 'زيارات ماضية غير منتهية','latest'=>'آخر الزيارات للطلب'];
    $modeIcons = ['all' => 'fa-list-check', 'unassigned' => 'fa-user-slash', 'past' => 'fa-clock-rotate-left','latest'=>'fa-calendar-day'];
    $routes = ['all' => 'admin.hourly-visits.index', 'unassigned' => 'admin.hourly-visits.unassigned', 'past' => 'admin.hourly-visits.past','latest'=>'admin.hourly-visits.latest'];
    $route = $routes[$mode];
    $columns = ['info' => 'معلومات الزيارة', 'customer' => 'العميل', 'address' => 'العنوان والهاتف', 'date' => 'موعد الزيارة', 'duration' => 'المدة', 'price' => 'السعر', 'workers' => 'العاملات', 'leader' => 'قائد الفريق', 'drivers' => 'السائقون', 'status' => 'حالة العملية', 'payment' => 'السداد'];
    $day = \Carbon\Carbon::parse($filters['from'] ?? today()->toDateString());
    $options = [
        'status' => ['حالة الزيارة', \App\Services\Hourly\VisitSearch::STATUSES],
        'start_time'=>['وقت البدء',array_combine($startTimes,$startTimes)],
        'customer_source'=>['مصدر العميل',$customerSources],
        'payment_status' => ['حالة سداد الطلب', ['paid' => 'مدفوع', 'unpaid' => 'غير مدفوع', 'partial' => 'جزئي']],
        'payment_method' => ['طريقة الدفع', ['cash' => 'نقدًا', 'card_on_delivery' => 'بطاقة', 'cash_and_card' => 'نقدي وبطاقة', 'bank_transfer' => 'تحويل بنكي', 'cheque' => 'شيك', 'customer_balance' => 'رصيد العميل', 'moyasar' => 'ميسر']],
        'installment' => ['الأقساط', ['has' => 'له أقساط', 'none' => 'بدون أقساط', 'pending' => 'أقساط معلقة', 'overdue' => 'أقساط متأخرة']],
        'admin_id' => ['موظف الفرع', $admins->pluck('name', 'id')->all()], 'team_id' => ['الفريق', $teams->pluck('name', 'id')->all()],
        'leader_id' => ['قائد الفريق', $drivers->pluck('name', 'id')->all()], 'worker_id' => ['العاملة', $workers->pluck('name', 'id')->all()], 'driver_id' => ['السائق', $drivers->pluck('name', 'id')->all()],
        'city_id' => ['المدينة', $cities->mapWithKeys(fn ($c) => [$c->id => $c->name])->all()], 'service_id' => ['الخدمة', $services->pluck('name_ar', 'id')->all()], 'attribute_id' => ['السمة', $attributes->pluck('name', 'id')->all()],
        'day_part' => ['فترة اليوم', ['am' => 'قبل الظهر', 'pm' => 'بعد الظهر']],
    ];
    $multi=\App\Services\Hourly\VisitSearch::MULTI;
    foreach(array_diff($multi,['status','start_time']) as $key)$options[$key][1]=['none'=>'غير مخصص']+$options[$key][1];
    $groups = [
        ['الوقت', 'fa-clock', ['day_part', 'start_time']],
        ['الحالة والدفع', 'fa-receipt', ['status', 'payment_status', 'payment_method', 'installment', 'tax_rate']],
        ['التعيين', 'fa-people-group', ['team_id', 'worker_id', 'leader_id', 'driver_id', 'admin_id']],
        ['الموقع والخدمة', 'fa-location-dot', ['city_id', 'service_id', 'attribute_id', 'customer_source']],
    ];
    $inputs = ['start_time' => ['وقت بدء الزيارة', 'time'], 'tax_rate' => ['نسبة الضريبة %', 'number'], 'customer_source' => ['مصدر العميل', 'text']];
    $advancedKeys = collect($groups)->pluck(2)->flatten()->all();
    $activeAdvanced = count(array_intersect($advancedKeys, array_keys($filters)));

    // اختصارات التاريخ: كل اختصار يحدد من/إلى؛ «كل التواريخ» يرسلهما فارغين حتى لا يُفرض اليوم.
    $today = today();
    $presets = [
        'today' => ['اليوم', $today->toDateString(), $today->toDateString()],
        'tomorrow' => ['غدًا', $today->copy()->addDay()->toDateString(), $today->copy()->addDay()->toDateString()],
        'week' => ['هذا الأسبوع', $today->copy()->startOfWeek(\Carbon\Carbon::SATURDAY)->toDateString(), $today->copy()->endOfWeek(\Carbon\Carbon::FRIDAY)->toDateString()],
        'month' => ['هذا الشهر', $today->copy()->startOfMonth()->toDateString(), $today->copy()->endOfMonth()->toDateString()],
        'all' => ['كل التواريخ', '', ''],
    ];
    $keep = \Illuminate\Support\Arr::except($filters, ['from', 'to', 'page']);
    $activePreset = collect($presets)->search(fn ($p) => $p[1] === ($filters['from'] ?? '') && $p[2] === ($filters['to'] ?? ''));

    // وسوم الفلاتر المفعّلة؛ الحذف يضع قيمة فارغة حتى يبقى «من/إلى» محذوفين فعلًا في وضع كل الزيارات.
    $chips = [];
    foreach ($filters as $key => $value) {
        if (in_array($key, ['sort', 'direction', 'per_page', 'page'], true)) continue;
        $label = match ($key) {
            'search' => 'بحث', 'from' => 'من', 'to' => 'إلى',
            default => $options[$key][0] ?? $inputs[$key][0] ?? $key,
        };
        $display = is_array($value)?implode('، ',array_map(fn($v)=>$options[$key][1][$v]??$v,$value)):($options[$key][1][$value] ?? $value);
        if($key==='show_cancelled'){$label='إظهار الملغاة';$display=$value?'نعم':'لا';}
        $chips[] = [$label, $display, route($route, array_merge($filters, [$key => '', 'page' => 1]))];
    }
    $statusTone = fn ($status) => match ($status) {
        'completed' => ['vl-b-green', '#17996f'],
        'cancelled', 'cancelled_by_client', 'expired', 'no_show' => ['vl-b-red', '#e5484d'],
        'scheduled' => ['vl-b-blue', '#2794eb'],
        'en_route', 'delivered', 'picked_up' => ['vl-b-violet', '#6b63d6'],
        'in_progress', 'worker_finished', 'unscheduled' => ['vl-b-amber', '#e08a17'],
        default => ['vl-b-grey', '#9fb0c2'],
    };
@endphp
<div class="container-fluid ht-page vl" dir="rtl" id="visit-operations" data-visit-base="{{ url('admin/hourly/visits') }}" data-endpoint="{{ route('admin.hourly-visits.batch') }}" data-receipt-base="{{ url('admin/hourly/visits/receipts') }}">

    <header class="vl-hero">
        <div class="vl-hero-top">
            <div>
                <h3><i class="fa {{ $modeIcons[$mode] }}"></i> {{ $titles[$mode] }}</h3>
                <p>التشغيل اليومي · متابعة الزيارات والتعيين والتحصيل</p>
            </div>
            <a class="vl-hero-btn" href="{{ route('admin.hourly-orders.index') }}"><i class="fa fa-cart-shopping"></i> الطلبات</a>
        </div>
        <nav class="vl-modes" aria-label="أنواع قوائم الزيارات">
            @foreach($titles as $key => $title)
                <a class="{{ $mode === $key ? 'active' : '' }}" @if($mode === $key) aria-current="page" @endif href="{{ route($routes[$key]) }}"><i class="fa {{ $modeIcons[$key] }}"></i> {{ $title }}</a>
            @endforeach
        </nav>
    </header>

    @if($mode === 'past')<p class="vl-notice"><i class="fa fa-triangle-exclamation mt-1"></i> هذه الزيارات تجاوزت موعد نهايتها ولم تُغلق. راجع التنفيذ ثم أكملها أو ألغها.</p>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    {{-- الفلتر --}}
    <form method="GET" action="{{ route($route) }}" class="vl-card-box vf" id="vl-filter-form" role="search" aria-label="مرشحات الزيارات">
        <div class="vf-main">
            <div class="vf-search"><i class="fa fa-magnifying-glass"></i><input id="vl-search" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="رقم الزيارة أو الطلب، اسم العميل، الهاتف أو العنوان" aria-label="بحث عام"></div>
            <nav class="vf-presets" aria-label="اختصارات التاريخ">
                @foreach($presets as $key => [$label, $from, $to])
                    <a class="{{ $activePreset === $key ? 'active' : '' }}" href="{{ route($route, array_merge($keep, ['from' => $from, 'to' => $to])) }}">{{ $label }}</a>
                @endforeach
            </nav>
            <div class="vf-range"><i class="fa fa-calendar-days"></i>
                <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" aria-label="من تاريخ"><span>إلى</span>
                <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" aria-label="إلى تاريخ">
            </div>
            <div class="vf-actions">
                <button type="button" class="vf-btn" id="vf-toggle" aria-controls="vf-advanced" aria-expanded="{{ $activeAdvanced ? 'true' : 'false' }}"><i class="fa fa-sliders"></i> فلاتر متقدمة @if($activeAdvanced)<span class="vf-count">{{ $activeAdvanced }}</span>@endif</button>
                <button class="vf-btn vf-btn-primary"><i class="fa fa-filter"></i> تطبيق</button>
            </div>
        </div>

        <div class="vf-advanced" id="vf-advanced" @unless($activeAdvanced) hidden @endunless>
            @foreach($groups as [$group, $icon, $keys])
                <details class="vf-group" open>
                    <summary><i class="fa {{ $icon }}"></i> {{ $group }}</summary>
                    <div class="vf-fields">
                        @foreach($keys as $key)
                            @php $multiple=in_array($key,$multi); $value=$filters[$key]??($multiple?[]:''); @endphp
                            <div class="vf-field {{ $multiple ? 'is-multi' : '' }} {{ in_array($key, ['status', 'customer_source'], true) ? 'is-wide' : '' }}">
                                <label for="vf-{{ $key }}">{{ $options[$key][0] ?? $inputs[$key][0] }}</label>
                                @if(isset($options[$key]))
                                    <select class="form-select {{ !empty($value) ? 'is-set' : '' }}" id="vf-{{ $key }}" name="{{ $key }}{{ $multiple?'[]':'' }}" @if($multiple) multiple size="4" @endif>
                                        @if(!$multiple)<option value="">الكل</option>@endif
                                        @foreach($options[$key][1] as $optionValue => $optionLabel)<option value="{{ $optionValue }}" {{ in_array((string)$optionValue,array_map('strval',(array)$value),true) ? 'selected' : '' }}>{{ $optionLabel }}</option>@endforeach
                                    </select>
                                    {{-- القائمة تتحول لمربعات اختيار بالبحث (hourly_teams._multiselect) فلا حاجة لتلميح Ctrl --}}
                                    @if($key==='status')<small class="vf-note">بدون اختيار: كل الحالات عدا الملغاة.</small><label class="vf-check"><input type="checkbox" name="show_cancelled" value="1" {{ !empty($filters['show_cancelled'])?'checked':'' }}> إظهار الملغاة أيضًا عند عدم تحديد حالات</label>@endif
                                @else
                                    <input class="form-control {{ $value !== '' ? 'is-set' : '' }}" id="vf-{{ $key }}" name="{{ $key }}" type="{{ $inputs[$key][1] }}" value="{{ $value }}" @if($key === 'tax_rate') min="0" max="100" step="0.01" @endif>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </details>
            @endforeach
            <div class="vf-advanced-foot">
                <a class="vf-btn" href="{{ route($route, ['from' => '', 'to' => '']) }}"><i class="fa fa-rotate-left"></i> مسح الفلاتر</a>
                <button class="vf-btn vf-btn-primary"><i class="fa fa-filter"></i> تطبيق الفلاتر</button>
            </div>
        </div>

        @foreach(['sort', 'direction', 'per_page'] as $key)@isset($filters[$key])<input type="hidden" name="{{ $key }}" value="{{ $filters[$key] }}">@endisset @endforeach

        @if($chips)
            <div class="vf-chips" aria-label="الفلاتر المفعّلة"><span>الفلاتر المفعّلة:</span>
                @foreach($chips as [$label, $display, $url])<a class="vf-chip" href="{{ $url }}" title="إزالة الفلتر">{{ $label }}: <b>{{ $display }}</b> <i class="fa fa-xmark" aria-hidden="true"></i><span class="visually-hidden">إزالة</span></a>@endforeach
                <a class="vf-clear" href="{{ route($route, ['from' => '', 'to' => '']) }}">مسح الكل</a>
            </div>
        @endif
    </form>

    <div class="vl-stats">
        <div class="vl-stat vl-t-blue"><i class="fa fa-calendar-days"></i><div><strong>{{ $total }}</strong><span>زيارة مطابقة</span></div></div>
        <div class="vl-stat vl-t-green"><i class="fa fa-user-check"></i><div><strong>{{ $assigned }}</strong><span>تعيين مكتمل</span></div></div>
        <div class="vl-stat vl-t-amber"><i class="fa fa-user-clock"></i><div><strong>{{ max(0, $total - $assigned) }}</strong><span>بحاجة إلى تعيين</span></div></div>
        <div class="vl-stat vl-t-violet"><i class="fa fa-percent"></i><div><strong>{{ $total ? round($assigned / $total * 100) : 0 }}%</strong><span>نسبة اكتمال التعيين</span></div></div>
    </div>

    <section class="vl-card-box vl-results">
        <div class="vl-results-head">
            <h5><i class="fa fa-list"></i> الزيارات <span class="vl-muted vl-num">({{ $visits->total() }})</span></h5>
            <div class="vl-day" aria-label="التنقل باليوم">
                <a aria-label="اليوم السابق" href="{{ route($route, array_merge($filters, ['from' => $day->copy()->subDay()->toDateString(), 'to' => $day->copy()->subDay()->toDateString(), 'page' => 1])) }}"><i class="fa fa-chevron-right"></i></a>
                <strong>{{ $day->translatedFormat('l d M Y') }}</strong>
                <a aria-label="اليوم التالي" href="{{ route($route, array_merge($filters, ['from' => $day->copy()->addDay()->toDateString(), 'to' => $day->copy()->addDay()->toDateString(), 'page' => 1])) }}"><i class="fa fa-chevron-left"></i></a>
            </div>
        </div>

        <div class="vl-toolbar">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <label><input type="checkbox" id="vl-select-all"> تحديد الصفحة <span id="vl-selected" class="vl-count">0</span></label>
                <button type="button" class="btn btn-primary" id="vl-open-batch" @if(!$canEdit && !$canCollect) disabled @endif><i class="fa fa-gears"></i> الإجراءات الجماعية</button>
            </div>
            <form method="GET" class="vl-sort" aria-label="الترتيب">
                @foreach(\Illuminate\Support\Arr::except($filters, ['sort', 'direction', 'per_page', 'page']) as $key => $value)@foreach((array)$value as $part)<input type="hidden" name="{{ $key }}{{ is_array($value)?'[]':'' }}" value="{{ $part }}">@endforeach @endforeach
                @if(!isset($filters['from']) && !isset($filters['to']))<input type="hidden" name="from" value=""><input type="hidden" name="to" value="">@endif
                <i class="fa fa-arrow-down-wide-short vl-muted"></i>
                <select class="form-select" name="sort" aria-label="الترتيب حسب">@foreach(\App\Services\Hourly\VisitSearch::SORTS as $key => $label)<option value="{{ $key }}" {{ ($filters['sort'] ?? 'date') === $key ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select>
                <select class="form-select" name="direction" aria-label="اتجاه الترتيب"><option value="asc">تصاعدي</option><option value="desc" {{ ($filters['direction'] ?? '') === 'desc' ? 'selected' : '' }}>تنازلي</option></select>
                <select class="form-select" name="per_page" aria-label="عدد الزيارات في الصفحة">@foreach([10, 25, 50, 100] as $size)<option value="{{ $size }}" {{ (int) ($filters['per_page'] ?? 25) === $size ? 'selected' : '' }}>{{ $size }} / صفحة</option>@endforeach</select>
            </form>
        </div>

        <details class="vl-columns-toggle"><summary><i class="fa fa-table-columns"></i> تخصيص الأعمدة</summary><div class="vl-columns">@foreach($columns as $key => $label)<label><input type="checkbox" data-column-toggle="{{ $key }}" checked> {{ $label }}</label>@endforeach</div></details>
        <p class="vl-hint"><i class="fa fa-circle-info"></i> حالة السداد والمتبقي تخص الطلب بالكامل. يُوزَّع مبلغ التحصيل الجماعي على المستحق للزيارات المحددة حسب ترتيب الطلبات والزيارات.</p>
        <div id="vl-result" class="alert alert-success mt-3" role="status" hidden></div>

        @if(in_array($mode,['unassigned','past']))
            @include('admin.hourly_visits._compact')
        @else
        @forelse($visits as $visit)
            @php
                $order = $visit->order;
                [$toneClass, $toneHex] = $statusTone($visit->status);
                $assignedCount = $visit->workers->count();
            @endphp
            @include('admin.hourly_visits._card')
        @empty
            <div class="vl-empty"><i class="fa fa-calendar-check"></i><p class="mb-2">لا توجد زيارات مطابقة للفلاتر.</p>@if($chips)<a class="vf-clear" href="{{ route($route, ['from' => '', 'to' => '']) }}">مسح الفلاتر</a>@endif</div>
        @endforelse
        @endif
        <div class="mt-3">{{ $visits->links() }}</div>
    </section>
    @include('admin.hourly_visits._batch')
    <div class="modal fade" id="vl-payments-modal" tabindex="-1" aria-labelledby="vl-payments-title"><div class="modal-dialog modal-xl"><div class="modal-content"><div class="modal-header"><h5 id="vl-payments-title">سجل الدفعات</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button></div><div class="modal-body" id="vl-payments-body"></div></div></div></div>
</div>
@endsection
@push('js')
<script src="{{ asset('admin/assets/js/hourly-visit-operations.js') }}?v={{ filemtime(public_path('admin/assets/js/hourly-visit-operations.js')) }}"></script>
<script>
(function () {
    const form = document.getElementById('vl-filter-form');
    if (!form) return;
    const toggle = document.getElementById('vf-toggle'), panel = document.getElementById('vf-advanced');
    toggle.addEventListener('click', () => { const open = panel.hidden; panel.hidden = !open; toggle.setAttribute('aria-expanded', open ? 'true' : 'false'); });
    // تمييز الحقول المعبّأة فورًا.
    panel.querySelectorAll('select, input').forEach(el => el.addEventListener('change', () => el.classList.toggle('is-set', el.value !== '')));
    // الترتيب يُطبَّق مباشرة عند تغييره.
    document.querySelectorAll('.vl-sort select').forEach(el => el.addEventListener('change', () => el.form.submit()));
    // قائمة الدفع: admin-tables.css يفرض position:fixed على كل قائمة مفتوحة، وPopper لا يحسبها صحيحًا مع تمرير
    // هذا التخطيط فتُرسم خارج الشاشة. لذا نعطّل Popper ونضعها بأنفسنا تحت الزر (أو فوقه عند ضيق المساحة).
    if (window.bootstrap?.Dropdown) {
        const toggles = [...document.querySelectorAll('.vl-card-foot [data-bs-toggle="dropdown"]')];
        const place = btn => {
            const menu = btn.parentElement.querySelector('.dropdown-menu'), r = btn.getBoundingClientRect();
            const w = Math.max(menu.offsetWidth, r.width), h = menu.offsetHeight, gap = 6;
            const top = innerHeight - r.bottom < h + gap && r.top > h + gap ? r.top - h - gap : r.bottom + gap;
            const left = Math.min(Math.max(8, document.dir === 'rtl' || document.body.classList.contains('rtl_mode') ? r.right - w : r.left), innerWidth - w - 8);
            Object.entries({ position: 'fixed', top: top + 'px', left: left + 'px', right: 'auto', bottom: 'auto', transform: 'none', margin: '0' }).forEach(([k, v]) => menu.style.setProperty(k, v, 'important'));
        };
        toggles.forEach(btn => {
            bootstrap.Dropdown.getOrCreateInstance(btn, { display: 'static' });
            btn.addEventListener('shown.bs.dropdown', () => place(btn));
        });
        // الموضع ثابت على الشاشة، فنغلق القائمة عند التمرير أو تغيير الحجم بدل أن تنفصل عن زرها.
        const closeAll = () => toggles.forEach(btn => btn.classList.contains('show') && bootstrap.Dropdown.getInstance(btn)?.hide());
        addEventListener('scroll', closeAll, true);
        addEventListener('resize', closeAll);
    }
    // لا نرسل الحقول الفارغة حتى يبقى الرابط نظيفًا، مع إبقاء «من/إلى» الفارغين لمعنى «كل التواريخ».
    const from=form.elements.from,to=form.elements.to;
    function validateRange(){let message='';if(!!from.value!==!!to.value)message='حدد بداية ونهاية الفترة أو اختر كل التواريخ.';else if(from.value&&to.value){const days=(Date.parse(to.value)-Date.parse(from.value))/86400000;if(days<0||days>30)message='الفترة يجب ألا تزيد عن 31 يومًا شاملًا البداية والنهاية.';}to.setCustomValidity(message);}
    [from,to].forEach(el=>el.addEventListener('change',validateRange));
    form.addEventListener('submit', event => {validateRange();if(!form.reportValidity()){event.preventDefault();return;}form.querySelectorAll('[name]').forEach(el => { if (el.value === '' && !['from', 'to'].includes(el.name)) el.disabled = true; });});
})();
</script>
@endpush
