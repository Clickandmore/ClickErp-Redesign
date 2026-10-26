<style>
    .pg-page { --pg-ink: #0f172a; --pg-muted: #64748b; --pg-line: #e6eaf1; --pg-soft: #f6f8fb; --pg-card: #fff;
        --pg-ok: #15803d; --pg-ok-soft: #dcfce7; --pg-warn: #b45309; --pg-warn-soft: #fef3c7; --pg-bad: #b91c1c; --pg-bad-soft: #fee2e2;
        --pg-info: #1d4ed8; --pg-info-soft: #dbeafe; --pg-mute-soft: #eef2f7;
        color: var(--pg-ink); padding-bottom: 2rem; }
    .pg-hero { position: relative; overflow: hidden; border-radius: 22px; padding: 28px 30px; color: #fff; margin-bottom: 22px;
        background: var(--pg-gradient); box-shadow: 0 18px 40px -22px rgba(15, 23, 42, .55); }
    .pg-hero::after { content: ''; position: absolute; inset-inline-end: -60px; top: -80px; width: 260px; height: 260px; border-radius: 50%;
        background: rgba(255, 255, 255, .08); }
    .pg-hero-row { position: relative; z-index: 1; display: flex; gap: 18px; align-items: center; flex-wrap: wrap; }
    .pg-hero-icon { width: 58px; height: 58px; border-radius: 16px; background: rgba(255, 255, 255, .14); display: grid; place-items: center; font-size: 1.6rem; flex: none; }
    .pg-hero h1 { font-size: 1.55rem; font-weight: 800; margin: 0 0 4px; color: #fff; }
    .pg-hero p { margin: 0; color: rgba(255, 255, 255, .82); max-width: 760px; line-height: 1.8; }
    .pg-hero-period { margin-inline-start: auto; background: rgba(255, 255, 255, .14); border: 1px solid rgba(255, 255, 255, .25);
        border-radius: 999px; padding: 8px 16px; font-weight: 700; white-space: nowrap; }

    .pg-hero-side { margin-inline-start: auto; display: flex; flex-direction: column; align-items: flex-end; gap: 10px; }
    .pg-hero-side .pg-hero-period { margin-inline-start: 0; }
    .pg-export { display: flex; gap: 8px; flex-wrap: wrap; }
    .pg-export-btn { display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; border-radius: 11px; font-weight: 700; font-size: .85rem;
        background: #fff; color: var(--pg-brand); text-decoration: none; box-shadow: 0 6px 16px -8px rgba(0, 0, 0, .45); }
    .pg-export-btn:hover { color: var(--pg-brand); filter: brightness(.96); }
    @media (max-width: 767px) { .pg-hero-side { margin-inline-start: 0; align-items: flex-start; } }
    .pg-card { background: var(--pg-card); border: 1px solid var(--pg-line); border-radius: 18px; box-shadow: 0 1px 2px rgba(15, 23, 42, .04); }
    .pg-card-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 16px 20px 0; flex-wrap: wrap; }
    .pg-card-head h3 { font-size: 1rem; font-weight: 800; margin: 0; display: flex; align-items: center; gap: 8px; }
    .pg-card-head h3 i { color: var(--pg-brand); }
    .pg-card-head small { color: var(--pg-muted); }
    .pg-card-body { padding: 14px 20px 18px; }

    .pg-filters { padding: 16px 20px; margin-bottom: 22px; }
    .pg-filters form { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; align-items: end; }
    .pg-filters label { font-size: .78rem; font-weight: 700; color: var(--pg-muted); margin-bottom: 4px; display: block; }
    .pg-filters .form-control, .pg-filters .form-select { border-radius: 11px; border-color: var(--pg-line); min-height: 42px; }
    .pg-filters .pg-wide { grid-column: span 2; }
    .pg-filters .pg-actions { display: flex; gap: 8px; }
    .pg-btn { border-radius: 11px; min-height: 42px; font-weight: 700; padding: 0 16px; display: inline-flex; align-items: center; justify-content: center; gap: 6px; }
    .pg-btn-brand { background: var(--pg-brand); color: #fff; border: 0; }
    .pg-btn-brand:hover { color: #fff; filter: brightness(1.08); }
    .pg-btn-ghost { background: var(--pg-soft); color: var(--pg-ink); border: 1px solid var(--pg-line); }
    .pg-quick { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 10px; }
    .pg-quick a { font-size: .8rem; font-weight: 700; padding: 4px 12px; border-radius: 999px; background: var(--pg-soft); color: var(--pg-muted);
        border: 1px solid var(--pg-line); text-decoration: none; }
    .pg-quick a.active, .pg-quick a:hover { background: var(--pg-brand-soft); color: var(--pg-brand); border-color: transparent; }

    .pg-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 16px; margin-bottom: 22px; }
    .pg-kpi { padding: 18px 20px; display: flex; gap: 14px; align-items: flex-start; }
    .pg-kpi-icon { width: 46px; height: 46px; border-radius: 13px; display: grid; place-items: center; font-size: 1.15rem; flex: none;
        background: var(--pg-tone-soft, var(--pg-brand-soft)); color: var(--pg-tone, var(--pg-brand)); }
    .pg-kpi small { color: var(--pg-muted); font-weight: 700; font-size: .8rem; display: block; }
    .pg-kpi strong { white-space: nowrap; font-size: 1.4rem; font-weight: 800; display: block; line-height: 1.35; letter-spacing: -.01em; }
    .pg-kpi strong span { font-size: .78rem; color: var(--pg-muted); font-weight: 700; }
    .pg-kpi em { font-style: normal; color: var(--pg-muted); font-size: .78rem; }
    .pg-tone-ok { --pg-tone: var(--pg-ok); --pg-tone-soft: var(--pg-ok-soft); }
    .pg-tone-warn { --pg-tone: var(--pg-warn); --pg-tone-soft: var(--pg-warn-soft); }
    .pg-tone-bad { --pg-tone: var(--pg-bad); --pg-tone-soft: var(--pg-bad-soft); }
    .pg-tone-info { --pg-tone: var(--pg-info); --pg-tone-soft: var(--pg-info-soft); }

    .pg-grid { display: grid; gap: 16px; margin-bottom: 22px; }
    .pg-grid-2-1 { grid-template-columns: 2fr 1fr; }
    .pg-grid-1-1 { grid-template-columns: 1fr 1fr; }
    .pg-grid-3 { grid-template-columns: repeat(3, 1fr); }
    @media (max-width: 1199px) { .pg-grid-2-1, .pg-grid-3 { grid-template-columns: 1fr 1fr; } .pg-grid-2-1 > :first-child { grid-column: 1 / -1; } }
    @media (max-width: 767px) { .pg-grid-2-1, .pg-grid-1-1, .pg-grid-3 { grid-template-columns: 1fr; } .pg-filters .pg-wide { grid-column: auto; }
        .pg-hero { padding: 22px 18px; } .pg-hero-period { margin-inline-start: 0; } }

    .pg-table-wrap { overflow-x: auto; }
    .pg-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: .88rem; }
    .pg-table th { background: var(--pg-soft); color: var(--pg-muted); font-weight: 700; font-size: .76rem; padding: 10px 12px; white-space: nowrap;
        border-bottom: 1px solid var(--pg-line); text-align: start; }
    .pg-table td { padding: 12px; border-bottom: 1px solid var(--pg-line); vertical-align: middle; text-align: start; }
    .pg-table tr:last-child td { border-bottom: 0; }
    .pg-table tbody tr:hover td { background: #fbfcfe; }
    .pg-ref { white-space: nowrap; font-weight: 800; color: var(--pg-ink); text-decoration: none; }
    .pg-ref:hover { color: var(--pg-brand); }
    .pg-sub { color: var(--pg-muted); font-size: .76rem; display: block; }
    .pg-num { font-variant-numeric: tabular-nums; white-space: nowrap; font-weight: 700; }
    .pg-ltr { direction: ltr; unicode-bidi: embed; }
    .pg-pill { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 999px; font-size: .74rem; font-weight: 800; white-space: nowrap;
        background: var(--pg-mute-soft); color: var(--pg-muted); }
    .pg-pill.ok { background: var(--pg-ok-soft); color: var(--pg-ok); }
    .pg-pill.warn { background: var(--pg-warn-soft); color: var(--pg-warn); }
    .pg-pill.bad { background: var(--pg-bad-soft); color: var(--pg-bad); }
    .pg-pill.info { background: var(--pg-info-soft); color: var(--pg-info); }
    .pg-service { display: inline-flex; align-items: center; gap: 6px; font-weight: 700; font-size: .82rem; white-space: nowrap; }
    .pg-service i { width: 26px; height: 26px; border-radius: 8px; display: grid; place-items: center; font-size: .75rem;
        background: var(--pg-brand-soft); color: var(--pg-brand); }
    .pg-progress { height: 6px; border-radius: 999px; background: var(--pg-mute-soft); overflow: hidden; min-width: 90px; margin-top: 5px; }
    .pg-progress span { display: block; height: 100%; background: var(--pg-ok); border-radius: inherit; }
    .pg-empty { text-align: center; color: var(--pg-muted); padding: 36px 12px; }
    .pg-empty i { font-size: 1.8rem; display: block; margin-bottom: 8px; opacity: .6; }
    .pg-bills { background: var(--pg-soft); }
    .pg-bills td { padding: 0 12px 12px; }
    .pg-bills table { width: 100%; font-size: .8rem; background: #fff; border: 1px solid var(--pg-line); border-radius: 12px; overflow: hidden; }
    .pg-bills th, .pg-bills td td { padding: 7px 10px; border-bottom: 1px solid var(--pg-line); text-align: start; }
    .pg-toggle-bills { border: 0; background: none; color: var(--pg-brand); font-weight: 700; font-size: .78rem; padding: 0; }
    .pg-foot { padding: 12px 20px; border-top: 1px solid var(--pg-line); }
    .pg-chart { min-height: 300px; }
</style>
