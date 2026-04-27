<form class="row g-3 from-submit-global" method="post" action="{{ $updateRoute }}" enctype="multipart/form-data">
    @method("PUT")

    <input type="hidden" value="{{ $obj->id }}" name="invoice_id">

    <h4>{{ __('finance::finance.attachments') }}</h4>
    <div class="col-12">
        <div class="work_samples_grid">
            <label class="img_upload">
                <input type="file" accept="image/*" multiple name="file[]"
                       id="img-upload" onchange="handleFileSelect(event)">
                <div class="img justify-content-center">
                    <img loading="lazy" src="{{asset("admin")}}/assets/img/plus.svg" alt="plus">
                </div>
            </label>
            @if ($obj->saleAttachments->count() > 0)
                @foreach ($obj->saleAttachments as $file)
                    <div class="uploadedImage">
                        <img src="{{ showFile($file->file) }}" alt="">
                        <button type="button" style="border: none;background: none;color:red" class="delete deleteImagesButton"
                                delete-route="{{ route('finance.bill-sales-attachments.destroy', $file->id) }}">
                            <img loading="lazy" src="{{ asset('admin') }}/assets/img/trash.svg" alt="trash">
                        </button>
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    <div class="col-12 modal-footer">
        {!! submitButton($updateRoute) !!}
        {!! closeButton() !!}
    </div>
</form>
