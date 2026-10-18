{{-- لوحات الإجراءات القابلة للطي فوق صفحة إدارة الطلب (بلاغ تعذّر التنفيذ، استبدال عاملة). --}}
@once
@push('css')
<style>
    .ap { margin: 0 0 .9rem; background: #fff; border: 1px solid #e6ecf3; border-inline-start: 4px solid var(--ap-tone, #e08a17); border-radius: 14px; box-shadow: 0 4px 14px rgba(20, 80, 127, .05); direction: rtl; }
    .ap.is-replace { --ap-tone: #6b63d6; --ap-tint: #f0effc; --ap-ink: #4f47b8; }
    .ap.is-incident { --ap-tone: #e08a17; --ap-tint: #fef5e7; --ap-ink: #9a6206; }
    .ap > summary { display: flex; align-items: center; gap: .75rem; padding: .85rem 1.1rem; cursor: pointer; list-style: none; }
    .ap > summary::-webkit-details-marker { display: none; }
    .ap-icon { width: 38px; height: 38px; flex: 0 0 38px; border-radius: 11px; display: grid; place-items: center; background: var(--ap-tint); color: var(--ap-tone); }
    .ap-title { flex: 1; min-width: 0; }
    .ap-title b { display: block; font-size: .95rem; color: #16212e; }
    .ap-title small { display: block; font-size: .78rem; color: #6b7a8c; }
    .ap-chip { flex: 0 0 auto; padding: .15rem .65rem; border-radius: 99px; background: var(--ap-tint); color: var(--ap-ink); font-size: .76rem; font-weight: 700; white-space: nowrap; }
    .ap-caret { flex: 0 0 auto; color: #9fb0c2; transition: transform .2s; }
    .ap[open] .ap-caret { transform: rotate(180deg); }
    .ap[open] > summary { border-bottom: 1px solid #eef2f6; }
    .ap-body { padding: 1rem 1.1rem 1.1rem; }
    .ap-intro { display: flex; gap: .55rem; margin: 0 0 1rem; padding: .7rem .9rem; border-radius: 10px; background: #f6f9fc; color: #4b5b6b; font-size: .82rem; line-height: 1.8; }
    .ap-intro i { color: var(--ap-tone); margin-top: .3rem; }
    .ap-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: .9rem 1rem; }
    .ap-field { display: grid; gap: .35rem; margin: 0; min-width: 0; }
    .ap-field.is-wide { grid-column: 1 / -1; }
    .ap-field > span { font-size: .8rem; font-weight: 700; color: #16212e; }
    .ap-field > span em { font-style: normal; font-weight: 500; color: #6b7a8c; margin-inline-start: .3rem; }
    .ap-field .form-control, .ap-field .form-select { min-height: 42px; border-radius: 10px; border-color: #dfe7ef; font-size: .88rem; }
    .ap-field textarea.form-control { min-height: 90px; resize: vertical; }
    .ap-field .form-control:focus, .ap-field .form-select:focus { border-color: #9fd0f4; box-shadow: 0 0 0 3px rgba(39, 148, 235, .15); }
    .ap-foot { display: flex; justify-content: flex-end; align-items: center; gap: .75rem; flex-wrap: wrap; margin-top: 1rem; padding-top: .9rem; border-top: 1px dashed #e6ecf3; }
    .ap-foot small { margin-inline-end: auto; color: #6b7a8c; font-size: .78rem; }
    .ap-submit { display: inline-flex; align-items: center; gap: .45rem; height: 42px; padding: 0 1.2rem; border: 0; border-radius: 10px; background: var(--ap-tone); color: #fff; font-weight: 700; font-size: .86rem; }
    .ap-submit:hover { filter: brightness(.94); color: #fff; }
</style>
@endpush
@endonce
