<style>
#item-modal,.si-editor{--si-accent:#0f6b6e;--si-accent-2:#15959a;--si-accent-soft:#e8f4f4;--si-accent-ring:rgba(15,107,110,.16);--si-ink:#172033;--si-text:#50535d;--si-muted:#7c8091;--si-line:#eceef5;--si-soft:#f7f8fc}
#item-modal .modal-dialog{max-width:1040px}#item-modal .modal-content{border:0;border-radius:18px;overflow:hidden;box-shadow:0 24px 70px rgba(16,24,40,.22)}
#item-modal .modal-header{direction:rtl;align-items:center;gap:14px;padding:20px 28px;border-bottom:1px solid var(--si-line);background:linear-gradient(180deg,var(--si-accent-soft),#fff)}
#item-modal .si-modal-icon{flex:0 0 46px;height:46px;border-radius:14px;display:grid;place-items:center;color:#fff;font-size:18px;background:linear-gradient(135deg,var(--si-accent),var(--si-accent-2));box-shadow:0 8px 18px var(--si-accent-ring)}
#item-modal .modal-header h5{font-size:18px;font-weight:700;color:var(--si-ink);margin:0}#item-modal .si-modal-sub{font-size:12.5px;color:var(--si-muted);margin:3px 0 0}
#item-modal .modal-header .btn-close{margin:0 auto 0 0;padding:10px;border-radius:10px;background-color:#fff;box-shadow:0 0 0 1px var(--si-line);opacity:.7}#item-modal .modal-header .btn-close:hover{opacity:1}
#item-modal .modal-body{padding:0;background:#fff}#item-modal .item-errors{margin:16px 28px 0;border-radius:12px}

.si-editor{direction:rtl;color:var(--si-text);background:#fff}.si-editor .si-editor-body{padding:0 28px 28px}
.si-editor .nav-tabs{position:sticky;top:0;z-index:3;display:flex;flex-wrap:nowrap;gap:6px;padding:16px 0 14px;margin:0 0 6px;border:0;background:#fff;overflow-x:auto}
.si-editor .nav-tabs::after{content:"";position:absolute;inset:auto 0 0;height:1px;background:var(--si-line)}
.si-editor .nav-tabs .nav-item{flex:1 1 0;min-width:max-content}
.si-editor .nav-tabs .nav-link{width:100%;display:flex;align-items:center;justify-content:center;gap:8px;border:1px solid var(--si-line);border-radius:12px;background:var(--si-soft);padding:11px 16px;color:var(--si-muted);font-size:14px;font-weight:600;transition:all .15s}
.si-editor .nav-tabs .nav-link:hover{color:var(--si-accent);border-color:var(--si-accent-ring)}
.si-editor .nav-tabs .nav-link.active{color:#fff;border-color:transparent;background:linear-gradient(135deg,var(--si-accent),var(--si-accent-2));box-shadow:0 6px 16px var(--si-accent-ring)}
.si-editor .nav-link i{margin:0;font-size:13px}

.si-editor .si-section{display:flex;align-items:center;gap:10px;color:var(--si-ink);font-size:15px;font-weight:700;padding:0;margin:26px 0 6px;border:0}
.si-editor .si-section>i{width:32px;height:32px;border-radius:10px;display:grid;place-items:center;font-size:13px;color:var(--si-accent);background:var(--si-accent-soft)}
.si-editor .si-section::after{content:"";flex:1;height:1px;background:var(--si-line);margin-inline-start:6px}
.si-editor .si-line{display:grid;grid-template-columns:minmax(200px,34%) 1fr;gap:28px;align-items:start;margin:0;padding:16px 0;border-bottom:1px dashed var(--si-line)}
.si-editor .si-line:last-of-type{border-bottom:0}
.si-editor .si-line>label{font-weight:600;font-size:14px;color:var(--si-ink);margin:0;padding-top:10px}
.si-editor .si-help{font-size:12px;font-weight:400;color:var(--si-muted);margin:5px 0 0;line-height:1.75}
.si-editor label.si-help{display:flex;flex-wrap:wrap;align-items:center;gap:8px;margin-top:14px;padding:10px 12px;border-radius:10px;background:var(--si-soft);color:var(--si-text);font-size:13px;font-weight:600;cursor:pointer}
.si-editor label.si-help input{accent-color:var(--si-accent);width:16px;height:16px}.si-editor label.si-help small{flex-basis:100%;margin:0;font-weight:400}

.si-editor .form-control,.si-editor .form-select{min-height:46px;border:1px solid #e3e6ef;border-radius:10px;background-color:#fff;box-shadow:none;font-weight:500;color:var(--si-ink);transition:border-color .15s,box-shadow .15s}
.si-editor .form-select{background-position:left .9rem center;padding-left:2.4rem}
.si-editor textarea.form-control{min-height:96px}
.si-editor .form-control:hover,.si-editor .form-select:hover{border-color:#cfd3e1}
.si-editor .form-control:focus,.si-editor .form-select:focus{border-color:var(--si-accent);box-shadow:0 0 0 4px var(--si-accent-ring)}
.si-editor .form-control::placeholder{color:#a5a9b8;font-weight:400}
.si-editor input[readonly]{background:var(--si-soft);color:var(--si-muted)}
.si-editor .input-group{flex-wrap:nowrap}.si-editor .input-group-text{background:var(--si-soft);font-size:12px;font-weight:600;color:var(--si-muted);border-color:#e3e6ef;border-radius:10px}
.si-editor .input-group>.form-control:not(:last-child){border-top-left-radius:0;border-bottom-left-radius:0}.si-editor .input-group>.input-group-text:last-child{border-top-right-radius:0;border-bottom-right-radius:0}
.si-editor .si-duration{display:grid;grid-template-columns:1fr 1fr;gap:12px}.si-editor .si-duration .input-group{direction:rtl}

.si-editor .si-switch{display:flex;justify-content:flex-start;padding-top:8px}.si-editor .si-switch.form-switch{padding:8px 0 0;margin:0;min-height:0}
.si-editor .si-switch .form-check-input{float:none;width:50px;height:27px;margin:0!important;flex:0 0 auto;cursor:pointer;border-color:#d5d8e3}
.si-editor .si-switch .form-check-input:focus{box-shadow:0 0 0 4px var(--si-accent-ring)}
.si-editor .si-switch .form-check-input:checked{background-color:var(--si-accent);border-color:var(--si-accent)}

.si-editor .si-extra{border:1px solid var(--si-line);border-radius:14px;padding:0;margin-top:24px;background:#fff;overflow:hidden}
.si-editor .si-extra>summary{list-style:none;display:flex;align-items:center;gap:10px;padding:14px 18px;font-size:14px;font-weight:600;color:var(--si-ink);background:var(--si-soft);cursor:pointer;user-select:none}
.si-editor .si-extra>summary:hover{background:var(--si-accent-soft)}
.si-editor .si-extra>summary::-webkit-details-marker{display:none}
.si-editor .si-extra>summary>i:first-child{width:28px;height:28px;border-radius:8px;display:grid;place-items:center;font-size:12px;color:var(--si-accent);background:#fff;box-shadow:0 0 0 1px var(--si-line)}
.si-editor .si-extra .si-extra-caret{margin-inline-start:auto;font-size:11px;color:var(--si-muted);transition:transform .2s}
.si-editor .si-extra[open]>summary{border-bottom:1px solid var(--si-line)}.si-editor .si-extra[open] .si-extra-caret{transform:rotate(180deg)}
.si-editor .si-extra>:not(summary){margin-inline:18px}.si-editor .si-extra>.row{padding-bottom:20px}
.si-editor .si-extra>.row>[class*=col]>label{font-weight:600;font-size:13.5px;color:var(--si-ink);margin-bottom:8px}.si-editor .si-extra>.row .si-help{margin:2px 0 0}
.si-editor .si-extra>.si-section:first-of-type{margin-top:18px}.si-editor .si-extra>.si-line:last-child{margin-bottom:6px}

.si-editor .si-time-grid{display:grid;grid-template-columns:repeat(8,minmax(0,1fr));gap:6px;direction:rtl;padding:12px;border:1px solid var(--si-line);border-radius:12px;background:var(--si-soft)}
.si-editor .si-time-grid>div{min-width:0!important;width:auto!important;padding:0}
.si-editor .si-time-grid button{font-size:11.5px;font-weight:600;padding:7px 2px;border-radius:8px;font-variant-numeric:tabular-nums}
.si-editor .si-time-grid .btn-outline-secondary{background:#fff;border-color:#e3e6ef;color:var(--si-muted)}.si-editor .si-time-grid .btn-outline-secondary:hover{border-color:var(--si-accent);color:var(--si-accent);background:#fff}
.si-editor .si-legend{display:flex;gap:16px;justify-content:flex-start;flex-wrap:wrap;font-size:12px;color:var(--si-muted);margin-top:10px}
.si-editor .si-legend span{display:inline-flex;align-items:center;gap:6px}.si-editor .si-dot{display:inline-block;width:12px;height:12px;border-radius:4px;margin:0}

.si-editor .si-actions{display:flex;direction:ltr;align-items:center;gap:10px;padding:16px 28px;border-top:1px solid var(--si-line);position:sticky;bottom:0;background:rgba(255,255,255,.96);backdrop-filter:blur(6px);z-index:4}
.si-editor .si-actions .btn{min-width:110px;padding:11px 22px;border-radius:10px;font-weight:600}
.si-editor .si-actions .btn i{margin-right:6px}
.si-editor .si-actions .btn-primary{border:0;background:linear-gradient(135deg,var(--si-accent),var(--si-accent-2));box-shadow:0 8px 18px var(--si-accent-ring)}
.si-editor .si-actions .btn-primary:hover{filter:brightness(1.06)}.si-editor .si-actions .btn-primary:disabled{opacity:.6}
.si-editor .si-actions .btn-light{background:#fff;color:var(--si-text)}

@media(max-width:768px){#item-modal .modal-header{padding:16px}.si-editor .si-editor-body{padding:0 16px 16px}.si-editor .si-line{grid-template-columns:1fr;gap:8px}.si-editor .si-line>label{padding-top:0}.si-editor .nav-tabs .nav-link{padding:9px 12px;font-size:13px}.si-editor .si-time-grid{grid-template-columns:repeat(4,minmax(0,1fr))}.si-editor .si-actions{padding:14px 16px}.si-editor .si-actions .btn{flex:1}}
</style>
