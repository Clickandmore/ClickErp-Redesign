@once
    @push('css')
        <style>
            .dispatch-attachments__zone {
                position: relative; display: flex; flex-direction: column; align-items: center; gap: 6px;
                padding: 22px 16px; text-align: center; border: 2px dashed #cfd8e6; border-radius: 14px;
                background: #f8fafc; transition: border-color .15s, background-color .15s;
            }
            .dispatch-attachments__zone:hover, .dispatch-attachments__zone.is-dragover { border-color: var(--bs-primary, #2f67b2); background: #eef5ff; }
            .dispatch-attachments__zone.is-invalid { border-color: #dc3545; background: #fff5f5; }
            .dispatch-attachments__zone input[type="file"] { position: absolute; inset: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; z-index: 2; }
            .dispatch-attachments__zone input[type="file"]:focus-visible + .dispatch-attachments__icon { outline: 2px solid var(--bs-primary, #2f67b2); outline-offset: 3px; }
            .dispatch-attachments__icon {
                width: 52px; height: 52px; display: grid; place-items: center; border-radius: 50%;
                background: #fff; color: var(--bs-primary, #2f67b2); font-size: 1.4rem; box-shadow: 0 4px 12px rgba(31, 45, 61, .08);
            }
            .dispatch-attachments__title { font-weight: 700; color: #344054; }
            .dispatch-attachments__title span { color: var(--bs-primary, #2f67b2); text-decoration: underline; text-underline-offset: 3px; }
            .dispatch-attachments__types { display: flex; flex-wrap: wrap; justify-content: center; gap: 6px; }
            .dispatch-attachments__types span { padding: 2px 10px; border-radius: 999px; background: #fff; border: 1px solid #e4e7ec; font-size: .75rem; color: #475467; }
            .dispatch-attachments__limits { font-size: .75rem; color: #98a2b3; }
            .dispatch-attachments__list { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 8px; }
            .dispatch-attachments__item, .dispatch-attachments__saved {
                display: flex; align-items: center; gap: 10px; padding: 8px 10px;
                border: 1px solid #e4e7ec; border-radius: 10px; background: #fff; min-width: 0;
            }
            .dispatch-attachments__item.is-invalid { border-color: #f5c2c7; background: #fff5f5; }
            .dispatch-attachments__file-icon { width: 36px; height: 36px; flex-shrink: 0; display: grid; place-items: center; border-radius: 8px; font-size: 1.05rem; }
            .dispatch-attachments__file-icon.is-image { background: #ecfdf3; color: #039855; }
            .dispatch-attachments__file-icon.is-pdf { background: #fef3f2; color: #d92d20; }
            .dispatch-attachments__file-icon.is-word { background: #eff8ff; color: #1570ef; }
            .dispatch-attachments__file-icon.is-excel { background: #ecfdf3; color: #067647; }
            .dispatch-attachments__file-icon.is-other { background: #f2f4f7; color: #667085; }
            .dispatch-attachments__file-body { flex: 1; min-width: 0; }
            .dispatch-attachments__file-name { display: block; font-size: .85rem; font-weight: 600; color: #344054; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            .dispatch-attachments__file-size { font-size: .72rem; color: #98a2b3; }
            .dispatch-attachments__item.is-invalid .dispatch-attachments__file-size { color: #d92d20; }
            .dispatch-attachments .btn.dispatch-attachments__remove {
                flex: 0 0 30px; display: inline-flex; align-items: center; justify-content: center;
                width: 30px; min-width: 30px; height: 30px; padding: 0; margin: 0; line-height: 1; border-radius: 8px;
            }
            .dispatch-attachments .btn.dispatch-attachments__remove i { margin: 0; font-size: .85rem; line-height: 1; }
            a.dispatch-attachments__saved { text-decoration: none; transition: border-color .15s, box-shadow .15s; }
            a.dispatch-attachments__saved:hover { border-color: var(--bs-primary, #2f67b2); box-shadow: 0 4px 12px rgba(31, 45, 61, .08); }
        </style>
    @endpush
@endonce
