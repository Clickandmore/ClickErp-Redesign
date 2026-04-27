<form class="row g-3 from-submit-global" method="post" action="{{ $updateRoute }}"
      enctype="multipart/form-data">
    @method('PUT')
    <div class="col-3">
        <label for="TextInput" class="form-label"> {{ __("finance::finance.date") }} </label>
        <input type="date" name="date" class="form-control" value="{{$obj->date}}" data-validation="required">
    </div>
    <div class="col-3">
        <label for="TextInput" class="form-label">  {{ __("finance::finance.From Account") }} </label>
        <select class="form-control show-tick ms select2" id="credit_account_id" name="credit_account_id"
                data-validation="required" onchange="updateOptions('#credit_account_id', '#debit_account_id')">
            {!! showSelectElement($accounts, 'name',$obj->credit_account_id) !!}
        </select>
    </div>
    <div class="col-3">
        <label for="TextInput" class="form-label"> {{ __("finance::finance.To Account") }}  </label>
        <select class="form-control show-tick ms select2" id="debit_account_id" name="debit_account_id"
                data-validation="required" onchange="updateOptions('#debit_account_id', '#credit_account_id')">
            {!! showSelectElement($accounts, 'name',$obj->debit_account_id) !!}
        </select>
    </div>
    <div class="col-3">
        <label for="TextInput" class="form-label">{{ __("finance::finance.fn-cost-centers") }} : </label>
        <select class="form-control show-tick ms select2" name="cost_center_id" id="cost_center_id">
            {!! showSelectElement($costCenters, "title",$obj->cost_center_id) !!}
        </select>
    </div>
    <div class="col-3">
        <label for="TextInput" class="form-label">{{ __("finance::finance.value") }} </label>
        <input type="number" min="0.01" step="any" name="value" value="{{$obj->value}}" class="form-control"
               data-validation="required,min_value">
    </div>


    <div class="col-3">
        <label for="TextInput" class="form-label">{{ __("finance::finance.statement") }} </label>
        <textarea type="text" name="statement" class="form-control form-control-lg mb-1" id="bs_maxlength_textarea"
                  rows="5">{{$obj->statement}}</textarea>
    </div>


    <h4>{{ __('finance::finance.attachments') }}</h4>
    <div class="col-12">
        <div class="work_samples_grid">
            <label class="img_upload">
                <input type="file" accept="image/*" multiple name="file[]"
                       id="img-upload" onchange="handleFileSelect(event)">
                <div class="img justify-content-center">
                    <img loading="lazy"
                         src="{{ asset('admin') }}/assets/img/plus.svg"
                         alt="plus"
                         style="width: 50px; height: 50px;">
                </div>
            </label>

            @if ($obj->files->count() > 0)
                @foreach ($obj->files as $file)
                    <div class="uploadedImage">
                        <img src="{{ showFile($file->file) }}" alt="">
                        <button type="button" style="border: none;background: none;color:red"
                                class="delete deleteImagesButton"
                                delete-route="{{ route('finance.account-transfer-files.destroy', $file->id) }}">
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
