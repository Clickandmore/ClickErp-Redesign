<div class="ht-card sc-presentation" id="cs-presentation">
    <div class="ht-head"><div class="ht-head-icon"><i class="fa fa-palette"></i></div><div><h5>{{ __('hourly_category_ui.presentation') }}</h5><p>{{ __('hourly_category_ui.presentation_hint') }}</p></div></div>
    <form method="POST" action="{{ route('admin.hourly-service-categories.update', $category) }}" enctype="multipart/form-data" data-presentation-form>
        @csrf @method('PUT')
        <input type="hidden" name="return_to_settings" value="1">
        <fieldset class="ht-body" @unless($canEdit) disabled @endunless>
            @include('admin.hourly_service_categories._fields')
            <div class="row g-3 mt-3">
                {{-- اختيار الأيقونة يتم من المنتقي المرسوم داخل _fields --}}
                <div class="col-md-4">
                    <label class="form-label">{{ __('hourly_category_ui.preview') }}</label>
                    <div class="d-flex gap-3 align-items-center flex-wrap">
                        <span class="sc-icon" data-brand-preview style="background: {{ $category->color ?: '#6861ee' }}"><img data-upload-preview src="{{ $category->icon_url }}" alt="" @unless($category->icon_url) hidden @endunless><i data-library-preview class="fa {{ $category->icon_type==='library' ? $category->icon : 'fa-broom' }}"></i></span>
                        <img data-image-preview src="{{ $category->image ? \Illuminate\Support\Facades\Storage::disk('public')->url($category->image) : '' }}" alt="{{ __('hourly.category_image') }}" @unless($category->image) hidden @endunless style="max-width:100%;max-height:180px;border-radius:12px">
                    </div>
                </div>
            </div>
        </fieldset>
        @if($canEdit)<div class="ht-foot"><button class="btn btn-primary">{{ __('hourly.save') }}</button></div>@endif
    </form>
</div>
@push('category-js')
<script>
(() => {
    const form=document.querySelector('[data-presentation-form]'); if(!form) return;
    const type=form.querySelector('[name=icon_type]'), icon=form.querySelector('[name=icon]'), color=form.querySelector('[name=color]');
    const sync=()=>{
        form.querySelectorAll('[data-icon-pane]').forEach(p=>p.hidden=p.dataset.iconPane!==type.value);
        form.querySelector('[data-library-preview]').hidden=type.value!=='library';
        form.querySelector('[data-upload-preview]').hidden=type.value!=='upload';
        [form.querySelector('[data-icon-preview]'),form.querySelector('[data-library-preview]')].forEach(p=>p.className='fa '+(/^fa-[a-z0-9-]+$/.test(icon.value)?icon.value:'fa-broom'));
        form.querySelector('[data-brand-preview]').style.backgroundColor=color.value;
    };
    [type,icon,color].forEach(el=>{el.addEventListener('input',sync);el.addEventListener('change',sync);});
    [['icon_file','[data-upload-preview]'],['image','[data-image-preview]']].forEach(([name,selector])=>{
        let url; form.querySelector('[name='+name+']').addEventListener('change',e=>{
            if(!e.target.files[0]) return; if(url) URL.revokeObjectURL(url);url=URL.createObjectURL(e.target.files[0]);
            const preview=form.querySelector(selector);preview.src=url;preview.hidden=false;
        });
    });
    form.querySelector('[data-remove-image]').classList.toggle('d-none',!form.querySelector('[data-image-preview]').getAttribute('src'));
    sync();
})();
</script>
@endpush
