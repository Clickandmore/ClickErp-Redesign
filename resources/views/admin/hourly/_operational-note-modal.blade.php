@include('admin.hourly._modal-theme')
@push('css')
<style>
    /* خاص ببطاقات نوع المذكرة — بقية الهيكل يأتي من admin.hourly._modal-theme */
    .on-types { display: grid; grid-template-columns: repeat(auto-fit, minmax(128px, 1fr)); gap: .65rem; }
    .om-modal .on-option { position: relative; cursor: pointer; margin: 0; }
    .on-option input { position: absolute; opacity: 0; width: 0; height: 0; }
    .on-option > span { display: flex; flex-direction: column; align-items: center; gap: .5rem; border: 1px solid var(--om-line); border-radius: 14px; padding: .95rem .5rem; text-align: center; background: #fff; font-size: .84rem; font-weight: 600; color: var(--om-ink); transition: border-color .15s, background .15s, box-shadow .15s; }
    .on-option > span i { width: 38px; height: 38px; border-radius: 12px; display: grid; place-items: center; font-size: 1rem; background: var(--om-soft); color: var(--om-muted); transition: background .15s, color .15s; }
    .on-option:hover > span { border-color: #bfe0f8; background: #fbfdff; }
    .on-option input:checked + span { border-color: var(--om-brand); background: #eff8ff; box-shadow: 0 0 0 3px rgba(39, 148, 235, .16); color: var(--om-brand-deep); }
    .on-option input:checked + span i { background: var(--om-brand); color: #fff; }
    .on-option input:focus-visible + span { outline: 2px solid var(--om-brand); outline-offset: 2px; }
    /* علامة الاختيار تظهر على البطاقة المحددة فقط */
    .on-option > span::after { content: '\f058'; font-family: 'Font Awesome 6 Free'; font-weight: 900; position: absolute; top: .5rem; inset-inline-end: .55rem; font-size: .8rem; color: var(--om-brand); opacity: 0; transition: opacity .15s; }
    .on-option input:checked + span::after { opacity: 1; }

    /* الحقول الخاصة بهذا المودال */
    fieldset.on-field { border: 0; padding: 0; margin: 0 0 1.1rem; }
    .on-field { margin-bottom: 1.1rem; }
    .on-control { position: relative; }
    .on-control > i { position: absolute; top: 50%; transform: translateY(-50%); inset-inline-start: .9rem; color: #9fb0c2; font-size: .82rem; pointer-events: none; z-index: 2; }
    .on-control .form-select { padding-inline-start: 2.5rem; }
    #operational-note-modal textarea { min-height: 150px; }
    /* لوحة التوضيح: الأيقونة عبر ::before حتى لا يمحوها تحديث النص من JS */
    #on-hint { position: relative; border-radius: 12px; padding: .8rem 1rem; padding-inline-start: 2.6rem; font-size: .84rem; margin: 0 0 1.1rem; background: #eff8ff; border: 1px solid #c4e2f8; color: var(--om-brand-deep); }
    #on-hint::before { content: '\f05a'; font-family: 'Font Awesome 6 Free'; font-weight: 900; position: absolute; inset-inline-start: .95rem; top: .82rem; font-size: .9rem; color: var(--om-brand); }
    #on-error { margin: 1.1rem 0 0; }
</style>
@endpush
<div class="modal fade om-modal" id="operational-note-modal" tabindex="-1" aria-labelledby="on-title" aria-hidden="true" dir="{{ app()->getLocale()==='ar'?'rtl':'ltr' }}">
<div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content">
    <div class="modal-header">
        <span class="om-m-icon"><i class="fa fa-pen-to-square"></i></span>
        <div>
            <h5 id="on-title">{{ __('operational_notes.title') }}</h5>
            <p><i class="fa fa-hashtag"></i> {{ $order->order_number }}</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('operational_notes.close') }}"></button>
    </div>
    <form id="operational-note-form" action="{{ route('admin.hourly-orders.note',$order) }}" method="POST">@csrf
        <input name="note_token" type="hidden">
        <div class="modal-body">
            <fieldset class="on-field">
                <legend><i class="fa fa-eye"></i> {{ __('operational_notes.type') }}</legend>
                <div class="on-types">
                    @foreach(['admin'=>'lock','worker'=>'user-nurse','driver'=>'truck','all'=>'users','address'=>'location-dot'] as $type=>$icon)
                        <label class="on-option">
                            <input type="radio" name="audience" value="{{ $type }}" data-hint="{{ __('operational_notes.'.$type.'_hint') }}" @if($type==='admin') checked @endif>
                            <span><i class="fa fa-{{ $icon }}"></i>{{ __('operational_notes.'.$type) }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <p id="on-hint" aria-live="polite"></p>

            <div class="on-field">
                <label for="on-visit"><i class="fa fa-calendar-day"></i> {{ __('operational_notes.visit') }}</label>
                <div class="on-control">
                    <i class="fa fa-calendar-check"></i>
                    <select name="visit_id" id="on-visit" class="form-select">
                        <option value="">{{ __('operational_notes.all_visits') }}</option>
                        @foreach($order->visits as $noteVisit)
                            <option value="{{ $noteVisit->id }}">#{{ $noteVisit->id }} — {{ $noteVisit->scheduled_start_at?->format('Y-m-d H:i') }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="on-field" style="margin-bottom:0">
                <label for="on-text"><i class="fa fa-note-sticky"></i> {{ __('operational_notes.note') }} <small>({{ __('operational_notes.max_chars') }})</small></label>
                <textarea id="on-text" name="note" class="form-control" maxlength="5000" required placeholder="{{ __('operational_notes.placeholder') }}"></textarea>
            </div>

            <div id="on-error" class="alert alert-danger" role="alert" hidden></div>
        </div>
        <div class="modal-footer">
            <button id="on-save" class="btn btn-primary"><i class="fa fa-floppy-disk"></i> {{ __('operational_notes.save') }}</button>
            <button type="button" class="btn btn-light" data-bs-dismiss="modal"><i class="fa fa-xmark"></i> {{ __('operational_notes.close') }}</button>
        </div>
    </form>
</div></div></div>
@push('js')
<script src="{{ asset('admin/assets/js/hourly-operational-notes.js') }}?v={{ filemtime(public_path('admin/assets/js/hourly-operational-notes.js')) }}" data-error="{{ __('operational_notes.error') }}"></script>
@endpush
