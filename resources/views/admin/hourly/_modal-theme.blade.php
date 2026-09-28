@once
@push('css')
<style>
    /* ——— هيكل موحّد لمودالات وحدة الساعات: يكفي أن يحمل المودال class="om-modal" ———
       المتغيّرات معرّفة على المودال نفسه لأن بعض السكربتات تنقله إلى body فيخرج من نطاق .om */
    .om-modal {
        --om-brand: #2794eb; --om-brand-ink: #1b6fb8; --om-brand-deep: #14507f; --om-teal: #00ac9a; --om-teal-deep: #068a7d;
        --om-ink: #16212e; --om-muted: #6b7a8c; --om-line: #e6ecf3; --om-soft: #f6f9fc; --om-accent: var(--om-brand);
    }
    .om-modal .modal-content { border: 0; border-radius: 18px; overflow: hidden; box-shadow: 0 24px 60px rgba(20, 80, 127, .22); }

    /* الترويسة: تدرّج الهوية مع أيقونة في مربع شفاف وخيط فيروزي أسفلها */
    .om-modal .modal-header { position: relative; background: linear-gradient(105deg, var(--om-brand-deep) 0%, var(--om-brand-ink) 42%, var(--om-accent) 80%, var(--om-teal) 145%); color: #fff; border: 0; padding: 1.1rem 1.3rem; display: flex; align-items: center; gap: .75rem; }
    .om-modal .modal-header::after { content: ''; position: absolute; inset-inline: 0; bottom: 0; height: 3px; background: linear-gradient(90deg, var(--om-teal), rgba(255, 255, 255, .35)); }
    .om-modal .modal-header h5 { margin: 0; font-size: 1.02rem; font-weight: 700; color: #fff; }
    .om-modal .modal-header p, .om-modal .modal-header .om-m-sub { margin: .1rem 0 0; font-size: .78rem; opacity: .85; }
    .om-modal .om-m-icon { width: 42px; height: 42px; flex: 0 0 42px; border-radius: 13px; display: grid; place-items: center; background: rgba(255, 255, 255, .18); font-size: 1.05rem; color: #fff; }
    .om-modal .btn-close { margin: 0 !important; margin-inline-start: auto !important; flex: 0 0 auto; filter: invert(1) grayscale(1) brightness(2); opacity: .8; }
    .om-modal .btn-close:hover { opacity: 1; }

    .om-modal .modal-body { padding: 1.35rem; }
    .om-modal .modal-footer { border-top: 1px solid var(--om-line); background: var(--om-soft); padding: .9rem 1.35rem; gap: .5rem; }

    /* عناوين الحقول: أيقونة في مربع أزرق فاتح ثم النص */
    .om-modal label, .om-modal legend { font-size: .84rem; font-weight: 700; color: var(--om-ink); margin-bottom: .45rem; display: block; }
    .om-modal legend { float: none; width: auto; padding: 0; }
    .om-modal label > i:first-child, .om-modal legend > i:first-child { display: inline-grid; place-items: center; width: 22px; height: 22px; border-radius: 7px; font-size: .72rem; background: #eaf5fe; color: var(--om-brand-ink); margin-inline-end: .35rem; vertical-align: -.35em; }
    .om-modal label small, .om-modal legend small { font-weight: 500; color: var(--om-muted); }
    .om-modal .form-text, .om-modal .modal-body > small, .om-modal [data-op-fields] small { display: block; color: var(--om-muted); font-size: .77rem; margin-top: .3rem; }

    /* الحقول */
    .om-modal .form-control, .om-modal .form-select { border-radius: 11px; border-color: #dfe7ef; font-size: .88rem; }
    .om-modal .form-select { height: 46px; }
    .om-modal .form-control:focus, .om-modal .form-select:focus { border-color: #9fd0f4; box-shadow: 0 0 0 3px rgba(39, 148, 235, .15); }
    .om-modal .form-select:disabled, .om-modal .form-control:disabled, .om-modal .form-control[readonly] { background: var(--om-soft); color: var(--om-muted); }
    .om-modal textarea.form-control { line-height: 1.7; padding: .7rem .9rem; }
    .om-modal .input-group-text { border-radius: 11px; background: var(--om-soft); border-color: #dfe7ef; color: var(--om-muted); font-weight: 700; font-size: .8rem; }
    .om-modal [data-op-fields] { margin-bottom: 1rem; }

    /* لوحات التنبيه: الأيقونة عبر ::before حتى لا يمحوها تحديث النص من JS */
    .om-modal .alert { position: relative; border-radius: 12px; padding: .8rem 1rem; padding-inline-start: 2.6rem; font-size: .84rem; border: 1px solid transparent; margin-bottom: 1rem; }
    .om-modal .alert::before { font-family: 'Font Awesome 6 Free'; font-weight: 900; position: absolute; inset-inline-start: .95rem; top: .82rem; font-size: .9rem; }
    .om-modal .alert-info { background: #eff8ff; border-color: #c4e2f8; color: var(--om-brand-deep); }
    .om-modal .alert-info::before { content: '\f05a'; color: var(--om-brand); }
    .om-modal .alert-danger { background: #fdeeee; border-color: #f7cdcf; color: #b42a2e; }
    .om-modal .alert-danger::before { content: '\f071'; color: #e5484d; }
    .om-modal .alert-success { background: #e8f7f1; border-color: #bfe9d9; color: #0f7a5a; }
    .om-modal .alert-success::before { content: '\f058'; color: var(--om-teal-deep); }

    /* الأزرار: نفس لغة أزرار الصفحة */
    .om-modal .btn { display: inline-flex; align-items: center; gap: .45rem; border-radius: 11px; padding: .6rem 1.3rem; font-size: .87rem; font-weight: 700; }
    .om-modal .btn-sm { padding: .4rem .8rem; font-size: .8rem; border-radius: 9px; }
    .om-modal .btn-primary, .om-modal .btn-success { background: var(--om-accent); border-color: var(--om-accent); color: #fff; }
    .om-modal .btn-primary:hover, .om-modal .btn-success:hover { filter: brightness(.94); color: #fff; }
    .om-modal .btn-primary:disabled, .om-modal .btn-success:disabled { opacity: .55; }
    .om-modal .btn-light, .om-modal .btn-outline-secondary { background: #fff; color: #5a6b7d; border: 1px solid var(--om-line); }
    .om-modal .btn-light:hover, .om-modal .btn-outline-secondary:hover { border-color: #c3d2e0; color: var(--om-ink); background: #fff; }
    .om-modal .btn-outline-success { background: #fff; color: var(--om-teal-deep); border: 1px solid var(--om-teal); }
    .om-modal .btn-outline-danger { background: #fff; color: #b42a2e; border: 1px solid #f0b6b8; }

    .om-modal [hidden] { display: none !important; }

    /* مودال بخطوتين: شبكة بطاقات لاختيار الإجراء ثم حقوله؛ الخطوة في data-op-step على المودال */
    .om-modal[data-op-step="pick"] [data-op-form], .om-modal[data-op-step="form"] [data-op-picker] { display: none !important; }
    .om-op-section + .om-op-section { margin-top: 1.1rem; }
    .om-op-section h6 { font-size: .76rem; font-weight: 700; color: var(--om-muted); margin: 0 0 .55rem; display: flex; align-items: center; gap: .5rem; }
    .om-op-section h6::after { content: ''; flex: 1; height: 1px; background: var(--om-line); }
    .om-op-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: .6rem; }
    .om-op-tile { display: flex; align-items: center; gap: .7rem; width: 100%; text-align: start; background: #fff; border: 1px solid var(--om-line); border-radius: 12px; padding: .7rem .85rem; font-size: .88rem; font-weight: 600; color: var(--om-ink); transition: border-color .15s, background .15s, box-shadow .15s, transform .15s; }
    .om-op-tile i { width: 36px; height: 36px; flex: 0 0 36px; border-radius: 10px; display: grid; place-items: center; font-size: .95rem; color: var(--tone); background: var(--tint); }
    .om-op-tile small { display: block; font-size: .72rem; font-weight: 500; color: var(--om-muted); margin-top: .1rem; }
    .om-op-tile:hover, .om-op-tile:focus-visible { border-color: var(--tone); background: var(--om-soft); box-shadow: 0 6px 16px rgba(20, 80, 127, .08); transform: translateY(-1px); outline: 0; }
    @media (prefers-reduced-motion: reduce) { .om-op-tile { transition: none; } .om-op-tile:hover { transform: none; } }
    .om-op-indigo { --tone: #3f6ad8; --tint: #ecf1fd; }
    .om-op-violet { --tone: #6b63d6; --tint: #f0effc; }
    .om-op-blue   { --tone: #2794eb; --tint: #eaf5fe; }
    .om-op-amber  { --tone: #e08a17; --tint: #fef5e7; }
    .om-op-teal   { --tone: #068a7d; --tint: #e6f7f5; }
    .om-op-green  { --tone: #17996f; --tint: #e8f7f1; }
    .om-op-red    { --tone: #e5484d; --tint: #fdeeee; }

    /* SweetAlert بنفس هوية الوحدة */
    .swal2-popup.om-swal { border-radius: 18px; font-family: inherit; padding: 1.4rem 1.2rem 1.2rem; }
    .swal2-popup.om-swal .swal2-title { font-size: 1.15rem; color: #16212e; }
    .swal2-popup.om-swal .swal2-html-container { font-size: .92rem; color: #4a5a6b; }
    .swal2-popup.om-swal .swal2-actions { gap: .5rem; }
    .swal2-popup.om-swal .swal2-styled { border-radius: 11px; font-weight: 700; padding: .6rem 1.4rem; margin: 0; box-shadow: none !important; }
    .swal2-popup.om-swal .swal2-confirm { background: #1b6fb8; }
    .swal2-popup.om-swal .swal2-confirm.is-danger { background: #e5484d; }
    .swal2-popup.om-swal .swal2-cancel { background: #fff; color: #5a6b7d; border: 1px solid #e6ecf3; }
</style>
@endpush
@endonce
