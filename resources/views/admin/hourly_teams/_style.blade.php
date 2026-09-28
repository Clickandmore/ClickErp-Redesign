<style>
    .ht-page { --ht-ink: #0f172a; --ht-muted: #64748b; --ht-line: #e2e8f0; --ht-soft: #f8fafc; }
    .ht-card { border: 0; border-radius: 18px; box-shadow: 0 14px 40px rgba(15, 23, 42, 0.08); overflow: hidden; margin-bottom: 1.25rem; background: #fff; }
    .ht-card > .ht-head { background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%); border-bottom: 1px solid #eef2f7; padding: 1rem 1.35rem; display: flex; align-items: center; gap: 0.85rem; flex-wrap: wrap; }
    .ht-head .ht-head-icon { width: 42px; height: 42px; flex: 0 0 42px; border-radius: 13px; display: grid; place-items: center; background: #ecfeff; color: #0e7490; font-size: 1.05rem; }
    .ht-head h5 { margin: 0; font-weight: 700; color: var(--ht-ink); font-size: 1.03rem; }
    .ht-head p  { margin: 0.15rem 0 0; font-size: 0.82rem; color: var(--ht-muted); }
    .ht-head .ht-actions { margin-inline-start: auto; display: flex; gap: 0.5rem; flex-wrap: wrap; }
    .ht-body { padding: 1.25rem 1.35rem; }
    .ht-foot { padding: 1rem 1.35rem; background: var(--ht-soft); border-top: 1px solid #eef2f7; display: flex; gap: .5rem; flex-wrap: wrap; align-items: center; }
    .ht-dot { display: inline-block; width: 14px; height: 14px; border-radius: 50%; border: 2px solid #fff; box-shadow: 0 0 0 1px rgba(15,23,42,.15); vertical-align: middle; }
    .ht-chip { display: inline-flex; align-items: center; gap: .35rem; padding: .2rem .6rem; border-radius: 999px; font-size: .78rem; font-weight: 600; border: 1px solid transparent; white-space: nowrap; }
    .ht-chip-on  { background: #dcfce7; color: #166534; border-color: #bbf7d0; }
    .ht-chip-off { background: #f1f5f9; color: #475569; border-color: #e2e8f0; }
    .ht-table { width: 100%; font-size: .9rem; }
    .ht-table th { font-size: .78rem; color: var(--ht-muted); font-weight: 700; border-bottom: 1px solid var(--ht-line); padding: .6rem .75rem; white-space: nowrap; }
    .ht-table td { padding: .7rem .75rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    .ht-table tr:last-child td { border-bottom: 0; }
    .ht-num { font-variant-numeric: tabular-nums; }
    .ht-map { width: 100%; height: 520px; border-radius: 14px; border: 1px solid var(--ht-line); overflow: hidden; }
    .ht-map-tall { height: calc(100vh - 260px); min-height: 520px; }
    .ht-areas { list-style: none; padding: 0; margin: 0; display: grid; gap: .5rem; }
    .ht-areas li { display: flex; gap: .5rem; align-items: center; padding: .5rem .7rem; border: 1px solid var(--ht-line); border-radius: 12px; background: var(--ht-soft); }
    .ht-areas li .idx { font-family: ui-monospace, Menlo, monospace; font-size: .75rem; color: var(--ht-muted); flex: 0 0 auto; }
    .ht-areas li input { flex: 1 1 auto; }
    .ht-areas li .pts { font-size: .72rem; color: var(--ht-muted); white-space: nowrap; }
    .ht-note { border-radius: 12px; padding: .75rem 1rem; font-size: .82rem; border: 1px solid; display: flex; gap: .6rem; align-items: flex-start; }
    .ht-note-info { background: #f0f9ff; border-color: #bae6fd; color: #075985; }
    .ht-note-warn { background: #fffbeb; border-color: #fde68a; color: #92400e; }
    .ht-legend { list-style: none; padding: 0; margin: 0; display: grid; gap: .35rem; max-height: 420px; overflow: auto; }
    .ht-legend li { display: flex; align-items: center; gap: .5rem; padding: .45rem .6rem; border-radius: 10px; cursor: pointer; font-size: .86rem; }
    .ht-legend li:hover { background: var(--ht-soft); }
    .ht-legend li .m { margin-inline-start: auto; font-size: .74rem; color: var(--ht-muted); }
    .leaflet-container { font-family: inherit; }
    .ht-switch { display: flex; align-items: flex-start; gap: .75rem; padding: .75rem .9rem; border: 1px solid var(--ht-line); border-radius: 12px; background: var(--ht-soft); }
    .ht-switch .form-check-input { width: 2.4em; height: 1.3em; margin-top: .15em; cursor: pointer; }
    .ht-switch label { font-weight: 700; margin: 0; cursor: pointer; }
    .ht-switch small { display: block; color: var(--ht-muted); font-size: .78rem; }
    .ht-switch.form-switch { padding: .75rem .9rem; }
    .ht-switch.form-switch .form-check-input { float: none; flex: 0 0 auto; margin: .15em 0 0; }
    .ht-switch > div { flex: 1 1 auto; min-width: 0; }

    /* بطاقات الملخص */
    .hd-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 1rem; margin-bottom: 1.25rem; }
    .hd-stat { background: #fff; border-radius: 16px; box-shadow: 0 10px 30px rgba(15, 23, 42, .06); padding: 1rem 1.1rem; display: flex; align-items: center; gap: .85rem; }
    .hd-stat span { display: block; font-size: .8rem; color: var(--ht-muted); }
    .hd-stat strong { font-size: 1.5rem; color: var(--ht-ink); line-height: 1.2; }
    .hd-stat-icon { width: 44px; height: 44px; border-radius: 13px; display: grid; place-items: center; background: #eef2ff; color: #4338ca; font-size: 1.1rem; flex: 0 0 44px; }
    .hd-stat-on .hd-stat-icon { background: #dcfce7; color: #166534; }
    .hd-stat-off .hd-stat-icon { background: #f1f5f9; color: #475569; }
    .hd-stat-info .hd-stat-icon { background: #ecfeff; color: #0e7490; }
    .hd-stat-warn .hd-stat-icon { background: #fffbeb; color: #b45309; }

    /* شارات */
    .hd-chip-warn { background: #fffbeb; color: #92400e; border-color: #fde68a; }
    .hd-chip-danger { background: #fef2f2; color: #991b1b; border-color: #fecaca; }
    .hd-chip-info { background: #ecfeff; color: #0e7490; border-color: #a5f3fc; }
    .hd-chip-primary { background: #eef2ff; color: #4338ca; border-color: #c7d2fe; }

    .hd-section-title { font-size: .78rem; font-weight: 700; color: var(--ht-muted); margin: 0 0 .75rem; display: flex; align-items: center; gap: .5rem; }
    .hd-section-title::after { content: ''; flex: 1; height: 1px; background: var(--ht-line); }

    /* بطاقة الفلتر */
    .hd-filter { border: 1px solid var(--ht-line); border-radius: 16px; background: #fff; overflow: hidden; }
    .hd-filter-head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; padding: .9rem 1.1rem; border-bottom: 1px solid var(--ht-line); }
    .hd-filter-title { font-weight: 700; color: var(--ht-ink); display: flex; align-items: center; gap: .5rem; }
    .hd-filter-title > i { color: #6861ee; }
    .hd-filter-count { font-size: .72rem; font-weight: 600; background: #eef2ff; color: #4338ca; border-radius: 999px; padding: .15rem .55rem; }
    .hd-filter-search { position: relative; flex: 0 1 360px; min-width: 220px; }
    .hd-filter-search i { position: absolute; top: 50%; inset-inline-start: .85rem; transform: translateY(-50%); color: #94a3b8; pointer-events: none; }
    .hd-filter-search .form-control { padding-inline-start: 2.3rem; border-radius: 10px; background: var(--ht-soft); }
    .hd-filter-body { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 1rem 1.1rem; padding: 1.1rem; }
    .hd-filter-body-compact { grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); }
    .hd-filter-field label { display: flex; align-items: center; gap: .4rem; font-size: .8rem; color: var(--ht-muted); margin-bottom: .4rem; }
    .hd-filter-field label i { font-size: .75rem; color: #94a3b8; }
    .hd-filter-field .form-select { height: 46px; border-radius: 8px; border-color: #e5e7eb; color: #64748b; box-shadow: 0 1px 2px rgba(15, 23, 42, .04); }
    .hd-filter-field .form-select:focus { border-color: #a5b4fc; box-shadow: 0 0 0 3px rgba(104, 97, 238, .15); }
    .hd-filter-field .form-select.is-set { color: #16a34a; font-weight: 700; border-color: #bbf7d0; background-color: #f0fdf4; }
    .hd-filter-foot { display: flex; align-items: center; justify-content: space-between; gap: .75rem; flex-wrap: wrap; padding: .75rem 1.1rem; background: var(--ht-soft); border-top: 1px solid var(--ht-line); }

    .hd-sticky { position: sticky; top: 90px; }
    .hd-dim { opacity: .55; }
    .hd-save { position: sticky; bottom: 0; z-index: 5; }

    /* مفتاح حالة يُحفظ فورًا */
    .ht-toggle { display: inline-flex; align-items: center; width: 56px; height: 30px; padding: 3px; border: 1px solid #e5e7f0; border-radius: 24px; background: #fff; box-shadow: 0 4px 14px rgba(98, 95, 238, .12); direction: ltr; cursor: pointer; }
    .ht-toggle span { display: flex; align-items: center; justify-content: center; width: 22px; height: 22px; border-radius: 50%; background: #cbd5e1; color: #fff; font-size: .7rem; transform: translateX(26px); transition: transform .2s, background .2s; }
    .ht-toggle[aria-checked="true"] span { background: #6861ee; transform: translateX(0); }
    .ht-toggle:focus-visible { outline: 3px solid #b7b3ff; outline-offset: 3px; }
    .ht-toggle:disabled { cursor: not-allowed; opacity: .55; }

    /* رؤوس جداول قابلة للترتيب */
    .ht-sort { color: inherit; text-decoration: none; display: inline-flex; align-items: center; gap: .3rem; }
    .ht-sort:hover { color: #4338ca; }
    .ht-sort i { font-size: .7rem; opacity: .45; }
    .ht-sort.is-sorted { color: #4338ca; }
    .ht-sort.is-sorted i { opacity: 1; }

    /* شريط نسبة */
    .ht-progress { display: flex; align-items: center; gap: .5rem; min-width: 140px; }
    .ht-progress-bar { flex: 1; height: 7px; border-radius: 99px; background: #f1f5f9; overflow: hidden; }
    .ht-progress-bar > span { display: block; height: 100%; border-radius: 99px; background: #22c55e; }
    .ht-progress-bar.is-low > span { background: #f59e0b; }
    .ht-progress-bar.is-zero > span { background: #ef4444; }
    .ht-progress small { font-size: .75rem; color: var(--ht-muted); white-space: nowrap; }

    /* تبويبات */
    .ht-tabs { display: flex; gap: .4rem; flex-wrap: wrap; border: 0; padding: .35rem; background: var(--ht-soft); border-radius: 14px; }
    .ht-tabs .nav-link { border: 0; border-radius: 10px; color: var(--ht-muted); font-weight: 600; display: flex; align-items: center; gap: .45rem; padding: .55rem 1rem; }
    .ht-tabs .nav-link.active { background: #fff; color: #4338ca; box-shadow: 0 4px 14px rgba(15, 23, 42, .08); }
    .ht-tabs .nav-link .badge { background: #e2e8f0; color: #475569; font-weight: 600; }
    .ht-tabs .nav-link.active .badge { background: #eef2ff; color: #4338ca; }

    .ht-empty { text-align: center; padding: 2.5rem 1rem; color: var(--ht-muted); }
    .ht-empty i { font-size: 1.8rem; color: #cbd5e1; display: block; margin-bottom: .6rem; }
    .ht-icon-btn { width: 32px; height: 32px; padding: 0; display: inline-grid; place-items: center; border-radius: 9px; }

    /* رأس المودالات: العنوان في البداية وزر الإغلاق في الطرف المقابل مهما كان اتجاه الصفحة */
    .ht-page .modal-header { display: flex; align-items: center; gap: .85rem; }
    .ht-page .modal-header > .btn-close { margin: 0 !important; margin-inline-start: auto !important; flex: 0 0 auto; }
</style>
@include('admin.hourly_teams._multiselect')
