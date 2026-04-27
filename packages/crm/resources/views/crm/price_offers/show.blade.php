<form class="row g-3 from-submit-global">
    <div class="col-md-4">
        <label for="TextInput" class="form-label">{{ __('crm::crm.clients') }}</label>
        <input type="text" value="{{ $obj->client ? @$obj->client->name :@$obj->user->name   }}" readonly class="form-control">
    </div>
    <div class="col-md-4">
        <label for="TextInput" class="form-label">{{ __('crm::crm.date') }}</label>
        <input type="date" value="{{ $obj->date }}" readonly class="form-control">
    </div>
    <div class="col-md-4">
        <label for="TextInput" class="form-label">{{ __('crm::crm.total') }}</label>
        <input type="number" value="{{ $obj->total }}" readonly class="form-control">
    </div>

    <div class="col-md-12">
        <label for="TextInput" class="form-label">{{ __("crm::crm.notes") }}</label>
        <textarea class="form-control" cols="15" rows="3" readonly>{{ $obj->notes }}</textarea>
    </div>
    <div class="col-md-12 mt-2 mb-2">
        <label for="descOfferView" class="form-label">{{ __("crm::crm.desc_offer") }}</label>
        <textarea id="descOfferView" class="form-control" cols="15" rows="5" readonly>{!! $obj->desc !!}</textarea>
    </div>
    <div class="col-12 modal-footer">
       {!! closeButton() !!}
    </div>

</form>
<script src="https://cdn.ckeditor.com/4.16.2/standard/ckeditor.js"></script>
<script>
    $(document).ready(function () {
        // Initialize CKEditor
        initializeCKEditor();
    });

    function initializeCKEditor() {
        if (CKEDITOR.instances.descOfferView) {
            CKEDITOR.instances.descOfferView.destroy(true);
        }
        CKEDITOR.replace('descOfferView', {
            readOnly: true, // Make CKEditor read-only
            language: 'ar', // Set language to Arabic
            contentsLangDirection: 'rtl', // Set content direction to RTL
            on: {
                instanceReady: function() {
                    console.log('CKEditor instance is ready');
                }
            }
        });
    }
</script>
