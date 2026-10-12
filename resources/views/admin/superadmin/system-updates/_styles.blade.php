@once
<style>
    .su-toolbar { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; }
    .su-tabs { display: flex; gap: 6px; flex-wrap: wrap; }
    .su-tabs a { padding: 8px 14px; border-radius: 999px; border: 1px solid var(--border); background: #fff; color: var(--text-soft); font-size: .84rem; font-weight: 600; text-decoration: none; }
    .su-tabs a.active { background: var(--accent-soft); border-color: var(--accent-line); color: var(--accent-dark); }
    .su-title { font-weight: 700; color: var(--text); }
    .su-sub { margin-top: 4px; color: var(--muted); font-size: .8rem; line-height: 1.6; }
    .su-cat { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 999px; font-size: .74rem; font-weight: 700; border: 1px solid transparent; white-space: nowrap; }
    .su-cat-feature { color: var(--accent-dark); background: var(--accent-soft); border-color: var(--accent-line); }
    .su-cat-improvement { color: var(--info); background: var(--info-soft); border-color: #b2ddff; }
    .su-cat-fix { color: var(--success); background: var(--success-soft); border-color: var(--success-line); }
    .su-cat-important { color: var(--danger); background: var(--danger-soft); border-color: var(--danger-line); }
    .su-pill { display: inline-flex; align-items: center; padding: 3px 9px; border-radius: 999px; font-size: .72rem; font-weight: 700; background: var(--surface-alt); color: var(--text-soft); border: 1px solid var(--border); white-space: nowrap; }
    .su-pill.is-good { color: var(--success); background: var(--success-soft); border-color: var(--success-line); }
    .su-pill.is-warn { color: var(--warning); background: var(--warning-soft); border-color: var(--warning-line); }
    .su-pill.is-info { color: var(--info); background: var(--info-soft); border-color: #b2ddff; }
    .su-progress { width: 120px; height: 7px; border-radius: 999px; background: var(--surface-alt); overflow: hidden; margin-top: 6px; }
    .su-progress > span { display: block; height: 100%; background: var(--accent); border-radius: inherit; }
    .su-actions { display: flex; gap: 6px; flex-wrap: wrap; }
    .su-actions form { margin: 0; }
    .su-grid-2 { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 18px; align-items: start; }
    .su-token { display: flex; gap: 8px; align-items: center; margin-top: 10px; }
    .su-token code { flex: 1; direction: ltr; text-align: left; padding: 11px 13px; border-radius: var(--r-sm); background: var(--navy-deep); color: #e6f5f2; font-size: .85rem; overflow-wrap: anywhere; }
    .su-state { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; margin-top: 16px; }
    .su-state div { padding: 12px; border-radius: var(--r-sm); background: var(--surface-soft); border: 1px solid var(--border); }
    .su-state span { display: block; color: var(--muted); font-size: .76rem; margin-bottom: 4px; }
    .su-state strong { font-size: .86rem; overflow-wrap: anywhere; }
    .su-error { margin-top: 12px; padding: 11px 13px; border-radius: var(--r-sm); background: var(--danger-soft); border: 1px solid var(--danger-line); color: var(--danger); font-size: .82rem; line-height: 1.7; }
    .su-subhead { margin: 0 0 12px; padding-top: 4px; font-size: .9rem; font-weight: 800; color: var(--navy); }
    .su-field + .su-subhead { margin-top: 24px; padding-top: 18px; border-top: 1px solid var(--border); }
    .su-code { direction: ltr; unicode-bidi: plaintext; font-family: ui-monospace, Consolas, monospace; font-size: .8rem; }
    .su-empty { padding: 34px 16px; color: var(--muted); text-align: center; border: 1px dashed var(--border-strong); border-radius: var(--r-md); background: var(--surface-soft); }
    .su-media-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 12px; }
    .su-media-item { border: 1px solid var(--border); border-radius: var(--r-sm); overflow: hidden; background: #fff; }
    .su-media-item img, .su-media-item video { display: block; width: 100%; height: 120px; object-fit: cover; background: var(--navy-deep); }
    .su-media-item .su-media-embed { height: 120px; display: grid; place-items: center; background: var(--navy-soft); color: var(--navy); font-size: .8rem; padding: 8px; text-align: center; overflow-wrap: anywhere; }
    .su-media-item .su-media-meta { padding: 9px; display: grid; gap: 7px; }
    .su-media-item input[type=text] { width: 100%; min-height: 34px; padding: 0 9px; border: 1px solid var(--border-strong); border-radius: var(--r-xs); font: inherit; font-size: .8rem; }
    .su-media-item label { display: flex; gap: 6px; align-items: center; color: var(--danger); font-size: .78rem; font-weight: 600; cursor: pointer; }
    .su-preview-body { line-height: 1.9; color: var(--text-soft); font-size: .9rem; }
    .su-preview-body ul, .su-preview-body ol { padding-inline-start: 22px; }
    .su-media-item .su-media-pdf { text-decoration: none; font-weight: 800; font-size: 1.4rem; color: var(--danger); background: var(--danger-soft); display: grid; gap: 2px; }
    .su-media-item .su-media-pdf small { font-size: .74rem; font-weight: 600; color: var(--text-soft); }

    /* ============ نموذج التحرير ============ */
    .su-editor-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 12px; flex-wrap: wrap; margin-bottom: 16px; }
    .su-editor-head h2 { margin: 6px 0 4px; font-size: 1.25rem; font-weight: 800; color: var(--text); }
    .su-editor-head p { margin: 0; color: var(--muted); font-size: .85rem; }
    .su-back { color: var(--accent-dark); font-size: .82rem; font-weight: 600; text-decoration: none; }
    .su-editor-grid { display: grid; grid-template-columns: minmax(0, 1fr) 340px; gap: 18px; align-items: start; }
    .su-editor-main, .su-editor-side { display: grid; gap: 16px; min-width: 0; }
    .su-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--r-md); padding: 20px; box-shadow: var(--shadow-xs); }
    .su-card-head { display: flex; gap: 12px; align-items: flex-start; margin-bottom: 18px; padding-bottom: 14px; border-bottom: 1px solid var(--border); }
    .su-card-head.compact { margin-bottom: 14px; padding-bottom: 10px; }
    .su-card-head h3 { margin: 0; font-size: .98rem; font-weight: 800; color: var(--text); }
    .su-card-head p { margin: 4px 0 0; color: var(--muted); font-size: .8rem; line-height: 1.6; }
    .su-step { flex: 0 0 auto; width: 30px; height: 30px; display: grid; place-items: center; border-radius: 9px; background: var(--accent-soft); color: var(--accent-dark); font-weight: 800; }
    .su-field { display: grid; gap: 6px; }
    .su-field + .su-field { margin-top: 18px; }
    .su-field > label:not(.su-dropzone):not(.su-switch) { font-size: .86rem; font-weight: 700; color: var(--text); }
    .su-field .required { color: var(--danger); }
    .su-hint { margin: -2px 0 2px; color: var(--muted); font-size: .77rem; line-height: 1.7; }
    .su-field input[type=text], .su-field input[type=url], .su-field input[type=password], .su-field input[type=number],
    .su-field input[type=datetime-local], .su-field select, .su-field textarea {
        width: 100%; min-height: 44px; padding: 10px 13px; border: 1px solid var(--border-strong); border-radius: var(--r-sm);
        background: #fff; font: inherit; font-size: .88rem; color: var(--text); box-shadow: var(--shadow-xs);
    }
    .su-field textarea { line-height: 1.8; resize: vertical; }
    .su-field input:focus, .su-field select:focus, .su-field textarea:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 4px var(--ring); }
    .su-field-error { margin: 2px 0 0; color: var(--danger); font-size: .78rem; font-weight: 600; }
    .su-row { display: grid; grid-template-columns: minmax(0, 1.4fr) minmax(0, 1fr); gap: 16px; }
    .su-row .su-field + .su-field { margin-top: 0; }
    .su-md-toolbar { display: flex; gap: 6px; flex-wrap: wrap; }
    .su-md-toolbar button { padding: 5px 10px; border: 1px solid var(--border-strong); border-radius: var(--r-xs); background: var(--surface-soft); font: inherit; font-size: .76rem; color: var(--text-soft); cursor: pointer; }
    .su-md-toolbar button:hover { background: var(--accent-soft); border-color: var(--accent-line); color: var(--accent-dark); }
    .su-dropzone { position: relative; display: grid; gap: 4px; place-items: center; padding: 26px 16px; border: 2px dashed var(--border-strong); border-radius: var(--r-md); background: var(--surface-soft); text-align: center; cursor: pointer; transition: border-color .15s ease, background .15s ease; }
    .su-dropzone:hover, .su-dropzone.is-over { border-color: var(--accent); background: var(--accent-soft); }
    .su-dropzone strong { color: var(--text); font-size: .9rem; }
    .su-dropzone span { color: var(--muted); font-size: .74rem; direction: ltr; }
    .su-dropzone input { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
    .su-file-list { list-style: none; margin: 8px 0 0; padding: 0; display: grid; gap: 6px; }
    .su-file-list li { display: flex; align-items: center; gap: 8px; padding: 8px 10px; border: 1px solid var(--border); border-radius: var(--r-xs); background: #fff; font-size: .8rem; }
    .su-file-name { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; direction: ltr; text-align: right; }
    .su-file-kind { padding: 2px 8px; border-radius: 999px; background: var(--navy-soft); color: var(--navy); font-weight: 700; font-size: .7rem; }
    .su-file-list small { color: var(--muted); direction: ltr; }
    .su-choice-list { display: grid; gap: 8px; }
    .su-choice { display: flex; gap: 10px; align-items: flex-start; padding: 11px 12px; border: 1px solid var(--border); border-radius: var(--r-sm); background: #fff; cursor: pointer; transition: border-color .15s ease, background .15s ease; }
    .su-choice:hover { border-color: var(--border-strong); }
    .su-choice input { margin-top: 4px; accent-color: var(--accent); }
    .su-choice span { display: grid; gap: 2px; }
    .su-choice b { font-size: .86rem; color: var(--text); }
    .su-choice small { color: var(--muted); font-size: .75rem; line-height: 1.6; }
    .su-choice:has(input:checked) { border-color: var(--accent); background: var(--accent-soft); }
    .su-choice-important:has(input:checked) { border-color: var(--danger-line); background: var(--danger-soft); }
    .su-submit { width: 100%; margin-top: 18px; min-height: 46px; }
    .su-audience { margin-top: 18px; padding: 14px; border-radius: var(--r-sm); background: var(--info-soft); border: 1px solid #b2ddff; font-size: .8rem; line-height: 1.8; color: var(--text-soft); }
    .su-audience b { color: var(--info); }
    .su-audience ul { margin: 6px 0 0; padding-inline-start: 18px; }
    .su-audience details { margin-top: 6px; }
    .su-audience summary { cursor: pointer; color: var(--info); font-weight: 600; }
    .su-audience details p { margin: 6px 0 0; }

    /* ============ شرح آلية العمل في الصفحة الرئيسية ============ */
    .su-how { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; margin-bottom: 18px; }
    .su-how div { padding: 14px; border-radius: var(--r-md); background: var(--surface); border: 1px solid var(--border); }
    .su-how b { display: flex; align-items: center; gap: 8px; color: var(--text); font-size: .86rem; }
    .su-how b span { width: 24px; height: 24px; display: grid; place-items: center; border-radius: 7px; background: var(--accent-soft); color: var(--accent-dark); font-size: .78rem; }
    .su-how p { margin: 6px 0 0; color: var(--muted); font-size: .77rem; line-height: 1.7; }

    @media (max-width: 1100px) {
        .su-editor-grid { grid-template-columns: minmax(0, 1fr); }
        .su-how { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 900px) {
        .su-grid-2, .su-state, .su-row, .su-how { grid-template-columns: minmax(0, 1fr); }
    }
</style>
@endonce
