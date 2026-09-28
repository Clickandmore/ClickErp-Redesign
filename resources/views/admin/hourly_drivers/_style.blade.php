<style>
    .hd-avatar { width: 38px; height: 38px; flex: 0 0 38px; border-radius: 50%; display: grid; place-items: center; background: #e0f2fe; color: #0369a1; font-weight: 700; }
    .hd-row-off td { background: #fcfcfd; }
    .hd-phone { color: var(--ht-ink); text-decoration: none; display: inline-flex; align-items: center; gap: .4rem; font-weight: 600; }
    .hd-phone i { font-size: .72rem; color: #94a3b8; }
    .hd-phone:hover { color: #0e7490; }
    .hd-row-off .hd-avatar { background: #f1f5f9; color: #94a3b8; }

    .hd-week { display: inline-flex; gap: 2px; }
    .hd-table th, .hd-table td { padding-inline: .55rem; }
    .hd-day { width: 20px; height: 20px; border-radius: 6px; display: grid; place-items: center; font-size: .7rem; font-weight: 700; cursor: default; }
    .hd-day-hours { background: #dbeafe; color: #1e40af; }
    .hd-day-all_day { background: #dcfce7; color: #166534; }
    .hd-day-off { background: #f1f5f9; color: #cbd5e1; }

    .hd-doc { border: 1px solid var(--ht-line); border-radius: 14px; padding: .9rem; background: var(--ht-soft); height: 100%; }
    .hd-doc h6 { font-size: .88rem; font-weight: 700; margin: 0 0 .7rem; color: var(--ht-ink); display: flex; align-items: center; gap: .45rem; }
    .hd-doc h6 i { color: #0e7490; }
    .hd-doc .form-label { font-size: .8rem; }

    .hd-schedule td, .hd-schedule th { vertical-align: middle; }
    .hd-schedule .btn-group .btn { font-size: .78rem; padding: .3rem .6rem; white-space: nowrap; }
    .hd-schedule tr.is-off th { color: #94a3b8; }
    .hd-schedule .hd-times { display: flex; align-items: center; gap: .4rem; }
    .hd-schedule .hd-times input { max-width: 130px; }
    .hd-schedule .hd-times-note { font-size: .8rem; color: var(--ht-muted); }
</style>
