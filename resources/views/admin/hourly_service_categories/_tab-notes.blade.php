@php($notesLocale=app()->getLocale()==='en' ? 'en' : 'ar')
<div class="ht-card"><div class="ht-body">
    <p class="text-muted mb-4">{{ __('hourly_category_ui.notes_hint') }}</p>
    @if($canEdit)
        <button type="button" class="btn btn-outline-primary mb-3" data-bs-toggle="collapse" data-bs-target="#category-add-note"><i class="fa fa-plus"></i> {{ __('hourly.note_add') }}</button>
        <div class="collapse {{ $errors->has('text_ar') || $errors->has('text_en') ? 'show' : '' }}" id="category-add-note">
            <form method="POST" action="{{ route('admin.hourly-category-notes.store',$category) }}" class="border rounded p-3 mb-3" data-note-form>@csrf
                <div class="row g-3"><div class="col-md-6"><label for="note-ar">{{ __('hourly.note_text_ar') }}</label><textarea id="note-ar" class="form-control" name="text_ar" required maxlength="1000" dir="rtl">{{ old('text_ar') }}</textarea></div><div class="col-md-6"><label for="note-en">{{ __('hourly.note_text_en') }}</label><textarea id="note-en" class="form-control" name="text_en" maxlength="1000" dir="ltr">{{ old('text_en') }}</textarea></div></div>
                <button class="btn btn-primary mt-3">{{ __('hourly.save') }}</button>
            </form>
        </div>
    @endif
    <div id="category-notes-list">
        @foreach($customNotes as $note)
            <div class="border rounded p-3 mb-2 d-flex justify-content-between align-items-center gap-3" data-note-id="{{ $note->id }}">
                <p class="mb-0" data-note-text style="white-space:pre-wrap">{{ $note->text($notesLocale) }}</p>
                @if($canEdit)<div class="d-flex gap-1"><button type="button" class="btn btn-sm btn-outline-primary" data-note-move="up" aria-label="{{ __('hourly.move_up') }}">↑</button><button type="button" class="btn btn-sm btn-outline-primary" data-note-move="down" aria-label="{{ __('hourly.move_down') }}">↓</button><form method="POST" action="{{ route('admin.hourly-category-notes.destroy',[$category,$note]) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">{{ __('hourly.note_delete') }}</button></form></div>@endif
            </div>
        @endforeach
    </div>
    <div class="rounded p-3 my-4" style="border:1px dashed #d6daeb">
        <h6 class="text-muted">{{ __('hourly.note_preview') }}</h6>
        <div data-note-preview></div>
        <p data-note-draft class="mb-0" style="white-space:pre-wrap"></p>
    </div>
    <div class="bg-light border rounded p-3">
        <h6 class="text-muted"><i class="fa fa-lock"></i> {{ __('hourly.note_automatic') }}</h6>
        @forelse($automaticNotes[$notesLocale] as $text)<p class="mb-1">{{ $text }}</p>@empty<p class="text-muted mb-0">{{ __('hourly_category_ui.no_notes') }}</p>@endforelse
    </div>
</div></div>
@push('category-js')
<script>
(() => {
    const list=document.getElementById('category-notes-list'),preview=document.querySelector('[data-note-preview]'),form=document.querySelector('[data-note-form]');
    const render=()=>{
        preview.replaceChildren();
        list.querySelectorAll('[data-note-text]').forEach(text=>{const p=document.createElement('p');p.textContent=text.textContent;p.style.whiteSpace='pre-wrap';preview.append(p);});
        if(!preview.children.length) preview.textContent=@json(__('hourly_category_ui.no_notes'));
    };
    form?.addEventListener('input',()=>{document.querySelector('[data-note-draft]').textContent=form.elements[@json('text_'.$notesLocale)].value || form.elements.text_ar.value;});
    let saving=false;
    list.addEventListener('click',async e=>{
        const button=e.target.closest('[data-note-move]');if(!button || saving)return;
        const row=button.closest('[data-note-id]'),other=button.dataset.noteMove==='up'?row.previousElementSibling:row.nextElementSibling;if(!other)return;
        const old=[...list.children];button.dataset.noteMove==='up'?list.insertBefore(row,other):list.insertBefore(other,row);render();saving=true;
        try {await categoryUi.request(@json(route('admin.hourly-category-notes.reorder',$category)),'PUT',{ids:[...list.children].map(n=>Number(n.dataset.noteId))});}
        catch(error){old.forEach(n=>list.append(n));render();categoryUi.toast(@json(__('hourly.note_order_failed')),false);}finally{saving=false;}
    });
    render();
})();
</script>
@endpush
