<form class="row g-3 from-submit-global" method="post" action="{{ $updateRoute }}" enctype="multipart/form-data">
    @method('put')


    <div class="col-md-6">
        <label for="title" class="form-label">{{ __("finance::finance.client_type") }}</label>
        <select name="is_company" class="form-control" id="is_company">
            <option value="0" {{ $obj->is_company == 0 ? 'selected' : '' }}>{{ helperTrans('finance::finance.normal_client') }}</option>
            <option value="1" {{ $obj->is_company == 1 ? 'selected' : '' }}>{{ helperTrans('finance::finance.company_client') }}</option>
        </select>
    </div>

    <div class="col-md-6">
        <label for="title" class="form-label">{{ __("finance::finance.client_category") }}</label>
        <select name="client_category_id" id="client_category_id" class="form-select" data-validation="required">
            {!! showSelectElement($client_categories, 'name', $obj->client_category_id) !!}
        </select>
    </div>

    <div class="col-md-6">
        <label for="title" class="form-label">{{ __("finance::finance.name") }}</label>
        <input type="text" name="name" value="{{ $obj->name }}" id="name" class="form-control" data-validation="required">
    </div>

    <div class="col-md-6">
        <label for="title" class="form-label">{{ __("finance::finance.phone") }}</label>
        <input type="text" name="phone" value="{{ $obj->phone }}" id="phone" class="form-control" data-validation="required">
    </div>

    <div class="col-md-6">
        <label for="title" class="form-label">{{ __("finance::finance.email") }}</label>
        <input type="email" name="email" value="{{ $obj->email }}" id="email" class="form-control">
    </div>


    <div class="col-md-6 finance::finance {{$obj->is_company == 0 ? 'd-none' : ''}}">
        <label for="title" class="form-label">{{ __("finance::finance.tax_number") }} <span class="text-muted"> {{__('finance::finance.needed_by_finance::finance')}}</span></label>
        <input type="text" name="tax_number" value="{{ $obj->tax_number }}" id="tax_number" class="form-control">
    </div>

    <div class="col-md-6  finance::finance {{$obj->is_company == 0 ? 'd-none' : ''}}">
        <label for="title" class="form-label">{{ __("finance::finance.city") }} <span class="text-muted"> {{__('finance::finance.needed_by_finance::finance')}}</span></label>
        <input type="text" name="city" value="{{ $obj->city }}" id="city" class="form-control">
    </div>

    <div class="col-md-6  finance::finance {{$obj->is_company == 0 ? 'd-none' : ''}}">
        <label for="postal_zone" class="form-label">{{ __("finance::finance.postal_zone") }} <span class="text-muted"> {{__('finance::finance.needed_by_finance::finance')}}</span></label>
        <input type="number" name="postal_zone" value="{{ $obj->postal_zone }}" id="postal_zone" class="form-control">
    </div>

    <div class="col-md-6  finance::finance {{$obj->is_company == 0 ? 'd-none' : ''}}">
        <label for="title" class="form-label">{{ __("finance::finance.address") }} <span class="text-muted"> {{__('finance::finance.needed_by_finance::finance')}}</span></label>
        <input type="text" name="address" value="{{ $obj->address }}" id="address" class="form-control">
    </div>

    <div class="col-md-6">
        <label for="title" class="form-label">{{ __("finance::finance.days_credit_period") }}</label>
        <input type="number" name="days_credit_period" data-validation="required" value="{{ $obj->days_credit_period }}" id="days_credit_period" class="form-control">
    </div>

    <div class="col-md-6">
        <label for="title" class="form-label">{{ __("finance::finance.credit_limit") }}</label>
        <input type="number" name="credit_limit" value="{{ $obj->credit_limit }}" id="credit_limit" class="form-control">
    </div>

    <div class="col-md-6">
        <label for="exceeding_minimum_cash_limit" class="form-label">{{ __('finance::finance.bill_not_work_if') }}</label>
        <br>
        <input type="hidden" name="exceeding_minimum_cash_limit" value="0">
        <input type="checkbox" name="exceeding_minimum_cash_limit" class="flat-red" {{ $obj->exceeding_minimum_cash_limit ? 'checked' : '' }} value="1"> @lang('finance::finance.exceeding_minimum_cash_limit')<br>
        <input type="hidden" name="exceeding_minimum_days_limit" value="0">
        <input type="checkbox" name="exceeding_minimum_days_limit" class="flat-red" {{ $obj->exceeding_minimum_days_limit ? 'checked' : '' }} value="1"> @lang('finance::finance.exceeding_minimum_days_limit')
    </div>

    <div class="col-6 finance::finance {{$obj->is_company == 0 ? 'd-none' : ''}}">
        <label for="image" class="form-label">{{ __("finance::finance.national_address") }}</label>
        <input type="file" id="image" name="image" class="dropify" data-default-file="{{ showFile($obj->image) }}">
    </div>

    <div class="col-md-6 finance::finance {{$obj->is_company == 0 ? 'd-none' : ''}}">
        <label for=""
               class="form-label">{{ __("finance::finance.attachments") }} </label>
        <div class="work_samples_grid">
            <label class="img_upload">
                <input type="file" accept="image/*,video/*" multiple name="images[]"
                       id="img-upload" onchange="handleFileSelect(event)">
                <div class="img justify-content-center">
                    <img loading="lazy" src="{{ asset('admin/assets/img/plus.svg') }}" alt="plus">
                </div>
            </label>
            @if ($obj->images->count() > 0)
                @foreach ($obj->images as $image)
                    <div class="uploadedImage">
                        @php
                            $extension = pathinfo($image->image, PATHINFO_EXTENSION);
                            $videoExtensions = ['mp4', 'avi', 'mkv', 'mov', 'wmv', 'flv', 'webm', '3gp', 'mpeg', 'mpg', 'ogv', 'm4v','f4v', 'ts'];
                        @endphp
                        @if (in_array($extension, $videoExtensions))
                            <video playsinline webkit-playsinline autoplay loop muted width="100px" height="100px">
                                <source src="{{ showFile($image->image) }}" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                        @else
                            <img src="{{ showFile($image->image) }}" alt="">
                        @endif
                        <button type="button" style="border: none;background: none;color:red" class="delete deleteImagesButton"
                                delete-route="{{ route('finance::finance.client-files.destroy', $image->id) }}">
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

<script>
    $('#tax_number').on('input', function() {
        var taxNumber = $(this).val().replace(/\D/g, '').substring(0, 15);
        $(this).val(taxNumber);

        var isValid = /^3\d{13}3$/.test(taxNumber);
        $(this).toggleClass('is-valid', isValid || taxNumber === '');
        $(this).toggleClass('is-invalid', !isValid && taxNumber !== '');
    });
</script>
<script>
    $('#is_company').on('change', function() {
        if($(this).val() == '1'){
            $('.finance::finance').removeClass('d-none');
        }
        else{
            $('.finance::finance').addClass('d-none');
        }
    });
</script>
