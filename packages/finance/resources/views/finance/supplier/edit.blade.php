<form class="row g-3 from-submit-update-global" method="post" action="{{$updateRoute}}" enctype="multipart/form-data">
    @method("PUT")


    <div class="col-3">
        <label for="TextInput" class="form-label">{{ __('finance::finance.name') }}</label>
        <input type="text" name="name" class="form-control" data-validation="required,length"
               data-validation-length="max255" value="{{$obj->name}}">
    </div>

    <div class="col-6">
        <label class="form-label">{{ __('finance::finance.suppliers_types') }} </label>
        <input type="text" name="supplier_type_name" id="supplier_type_name" class="form-control"
               list="supplier_types_list" data-validation="required,length" data-validation-length="max255"
               value="{{ $obj->supplierType->name ?? '' }}"
               placeholder="{{ __('finance::finance.suppliers_types') }}">
        <datalist id="supplier_types_list">
            @foreach(\Nami\Finance\Models\SupplierType::query()->orderBy('name')->get() as $it)
                <option value="{{ $it->name }}"></option>
            @endforeach
        </datalist>
        <small class="text-muted">يمكنك اختيار فئة موجودة أو كتابة فئة جديدة ليتم إضافتها تلقائيًا.</small>
    </div>

    <div class="col-3">
        <label for="TextInput" class="form-label">{{ __('finance::finance.code') }}</label>
        <input type="text" value="{{$obj->code }}" name="code" class="form-control" data-validation="required" disabled>
        <input type="hidden" name="code" value="{{ @$obj->code }}">
    </div>

    <div class="col-3">
        <label for="phone" class="form-label">{{ __('finance::finance.phone') }}</label>
        <input type="text" name="phone" class="form-control" id="phone" data-validation="required,length"
               data-validation-length="max20" value="{{$obj->phone}}">
    </div>

    <div class="col-3">
        <label for="address" class="form-label">{{ __('finance::finance.address') }}</label>
        <input type="text" name="address" class="form-control" id="address" data-validation="required,length"
               data-validation-length="max255" value="{{$obj->address}}">
    </div>

    <div class="col-3">
        <label for="tax_number" class="form-label">{{ __('finance::finance.tax_number') }}</label>
        <input type="text" name="tax_number" class="form-control" id="tax_number" data-validation="length"
               data-validation-length="max255" value="{{$obj->tax_number}}">
    </div>

    <div class="col-3">
        <label for="commercial_register_number"
               class="form-label">{{ __('finance::finance.commercial_register_number') }}</label>
        <input type="text" name="commercial_register_number" class="form-control" id="commercial_register_number"
               data-validation="required,length" data-validation-length="max255"
               value="{{$obj->commercial_register_number}}">
    </div>

    <div class="col-3">
        <label for="email" class="form-label">{{ __('finance::finance.email') }}</label>
        <input type="text" name="email" class="form-control" id="email" data-validation="required,length"
               data-validation-length="max255" value="{{$obj->email}}">
    </div>

    <div class="col-3">
        <label for="credit_limit" class="form-label">{{ __('finance::finance.credit_limit') }}</label>
        <input type="number" name="credit_limit" class="form-control" id="credit_limit"
               data-validation="required,number" value="{{$obj->credit_limit}}">
    </div>
    <div class="col-md-6">
        <label for="days_number" class="form-label">{{ __('finance::finance.days_credit_period') }}</label>
        <input type="number" name="days_number" class="form-control" id="days_number" value="{{$obj->days_number}}"
               data-validation="required">
    </div>

    <div class="col-md-6">
        <label for="exceeding_minimum_cash_limit" class="form-label">{{ __('finance::finance.bill_not_work_if') }}</label>
        <br>
        <input type="hidden" name="exceeding_minimum_cash_limit" value="0">
        <input type="checkbox" name="exceeding_minimum_cash_limit" class="flat-red "
               {{  $obj->exceeding_minimum_cash_limit ? 'checked' : '' }}
               value="1"> @lang('finance::finance.exceeding_minimum_cash_limit')<br>
        <input type="hidden" name="exceeding_minimum_days_limit" value="0">
        <input type="checkbox" name="exceeding_minimum_days_limit" class="flat-red "
               {{  $obj->exceeding_minimum_days_limit ? 'checked' : '' }}
               value="1"> @lang('finance::finance.exceeding_minimum_days_limit')

    </div>
    <div class="col-6">
        <label for="commercial_register_image" class="form-label">{{ __("finance::finance.commercial_register_image") }}</label>
        <input type="file" id="commercial_register_image" name="commercial_register_image" class="dropify"
               data-default-file="{{ showFile($obj->commercial_register_image) }}">
    </div>

    <div class="col-6">
        <label for="tax_image" class="form-label">{{ __("finance::finance.tax_image") }}</label>
        <input type="file" id="tax_image" name="tax_image" class="dropify"
               data-default-file="{{ showFile($obj->tax_image) }}">
    </div>

    <div class="col-6">
        <label for="national_address" class="form-label">{{ __("finance::finance.national_address") }}</label>
        <input type="file" id="national_address" name="national_address" class="dropify"
               data-default-file="{{ showFile($obj->national_address) }}">
    </div>
    <div class="col-md-6">
        <label for=""
               class="form-label">{{ __("finance::finance.attachments") }} </label>
        <div class="work_samples_grid">
            <label class="img_upload">
                <input type="file" accept="image/,video/" multiple name="file[]"
                       id="img-upload" onchange="handleFileSelect(event)">
                <div class="img justify-content-center">
                    <img loading="lazy" src="{{ asset('admin/assets/img/plus.svg') }}" alt="plus">
                </div>
            </label>
            @if ($obj->files->count() > 0)
                @foreach ($obj->files as $image)
                    <div class="uploadedImage">
                        @php
                            $extension = pathinfo($image->file, PATHINFO_EXTENSION);
                            $videoExtensions = ['mp4', 'avi', 'mkv', 'mov', 'wmv', 'flv', 'webm', '3gp', 'mpeg', 'mpg', 'ogv', 'm4v','f4v', 'ts'];
                        @endphp
                        @if (in_array($extension, $videoExtensions))
                            <video playsinline webkit-playsinline autoplay loop muted width="100px" height="100px">
                                <source src="{{ showFile($image->file) }}" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                        @else
                            <img src="{{ showFile($image->file) }}" alt="">
                        @endif
                        <button type="button" style="border: none;background: none;color:red" class="delete deleteImagesButton"
                                delete-route="{{ route('finance.supplier-files.destroy', $image->id) }}">
                            <img loading="lazy" src="{{ asset('admin') }}/assets/img/trash.svg" alt="trash">
                        </button>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
{{--
    <div class="col-md-6">
        <h4>{{ __('finance::finance.attachments') }}</h4>
        <div class="work_samples_grid">
            <label class="img_upload">
                <input type="file" accept="image/*" multiple name="file[]"
                       id="img-upload" onchange="handleFileSelect(event)">
                <div class="img justify-content-center">
                    <img loading="lazy" src="{{asset("admin")}}/assets/img/plus.svg" alt="plus">
                </div>
            </label>
            @if ($obj->files->count() > 0)
                @foreach ($obj->files as $file)
                    <div class="uploadedImage">
                        <img src="{{ showFile($file->file) }}" alt="">
                        <button type="button" style="border: none;background: none;color:red" class="delete deleteImagesButton"
                                delete-route="{{ route('finance.supplier-files.destroy', $file->id) }}">
                            <img loading="lazy" src="{{ asset('admin') }}/assets/img/trash.svg" alt="trash">
                        </button>
                    </div>
                @endforeach
            @endif
        </div>
    </div> --}}

    <div class="col-12 modal-footer">
        {!! submitButton( $updateRoute ) !!}
        {!! closeButton() !!}
    </div>

</form>
