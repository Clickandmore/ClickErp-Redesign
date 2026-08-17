<form class="row g-3 from-submit-global" method="post" action="{{ $storeRoute }}" enctype="multipart/form-data">

    <div class="col-md-6">
        <label for="title" class="form-label">{{ __("finance::finance.client_type") }}<span class="text-danger">*</span></label>
        <select name="is_company" id="is_company" class="form-control">
            <option value="0">{{__('finance::finance.normal_client')}}</option>
            <option value="1">{{__('finance::finance.company_client')}}</option>
        </select>
    </div>

    <div class="col-md-6">
        {!! addUpButton(route('finance.client-categories.create'),'client_category_id' ,false, 'name') !!}
        <label for="title" class="form-label">{{ __("finance::finance.client_category") }}<span class="text-danger">*</span></label>
        <select name="client_category_id" id="client_category_id" class="form-select" data-validation="required">
            {!! showSelectElement($client_categories,'name') !!}
        </select>
    </div>


    <div class="col-md-6">
        <label for="title" class="form-label">{{ __("finance::finance.name") }}<span class="text-danger">*</span></label>
        <input type="text" name="name" id="name" class="form-control" data-validation="required">
    </div>


    <div class="col-md-6">
        <label for="title" class="form-label">{{ __("finance::finance.phone") }}<span class="text-danger">*</span></label>
        <input type="text" name="phone" id="phone" class="form-control" data-validation="required">
    </div>


    <div class="col-md-6">
        <label for="title" class="form-label">{{ __("finance::finance.email") }}</label>
        <input type="email" name="email" id="email" class="form-control">
    </div>



    <div class="col-md-6 zatca d-none">
        <label for="title" class="form-label">{{ __("finance::finance.tax_number") }} <span class="text-muted"> {{__('finance::finance.needed_by_zatca')}}</span></label>
        <input type="text" name="tax_number" id="tax_number" class="form-control" placeholder="3*************3">
    </div>


    <div class="col-md-6 zatca d-none">
        <label for="city" class="form-label">{{ __("finance::finance.city") }} <span class="text-muted"> {{__('finance::finance.needed_by_zatca')}}</span></label>
        <input type="text" name="city" id="city" class="form-control">
    </div>


    <div class="col-md-6 zatca d-none">
        <label for="postal_zone" class="form-label">{{ __("finance::finance.postal_zone") }} <span class="text-muted"> {{__('finance::finance.needed_by_zatca')}}</span></label>
        <input type="number" name="postal_zone" id="postal_zone" class="form-control" placeholder="23443" value="23443">
    </div>


    <div class="col-md-6 zatca d-none">
        <label for="title" class="form-label">{{ __("finance::finance.address") }} <span class="text-muted"> {{__('finance::finance.needed_by_zatca')}}</span></label>
        <input type="text" name="address" id="address" class="form-control">
    </div>

    <div class="col-md-6">
        <label for="title" class="form-label">{{ __("finance::finance.days_credit_period") }}<span class="text-danger">*</span></label>
        <input type="number" data-validation="required" name="days_credit_period" id="days_credit_period" class="form-control">
    </div>

    <div class="col-md-6">
        <label for="title" class="form-label">{{ __("finance::finance.credit_limit") }}</label>
        <input type="number" name="credit_limit" id="credit_limit" class="form-control">
    </div>


    <div class="col-md-6">
        <label for="exceeding_minimum_cash_limit" class="form-label">{{ __('finance::finance.bill_not_work_if') }}</label>
        <br>
        <input type="hidden" name="exceeding_minimum_cash_limit" value="0">
        <input type="checkbox" name="exceeding_minimum_cash_limit" class="flat-red "
               value="1"> @lang('finance::finance.exceeding_minimum_cash_limit')<br>
        <input type="hidden" name="exceeding_minimum_days_limit" value="0">
        <input type="checkbox" name="exceeding_minimum_days_limit" class="flat-red "
               value="1"> @lang('finance::finance.exceeding_minimum_days_limit')

    </div>

    <div class="col-6 zatca d-none">
        <label for="image" class="form-label">{{ __("finance::finance.national_address") }}</label>
        <input type="file" id="image" name="image" class="dropify" data-validation="required">
    </div>

    <div class="col-6 zatca d-none">
        <label for="" class="form-label">{{ __('finance::finance.attachments') }}</label>
        <div class="work_samples_grid">
            <label class="img_upload">
                <input type="file" accept="image/*,video/*" multiple name="images[]" id="img-upload"
                    onchange="handleFileSelect(event)">
                <div class="img justify-content-center">
                    <img loading="lazy" src="{{ asset('admin/assets/img/plus.svg') }}" alt="plus">
                </div>
            </label>
        </div>
    </div>
    <div class="col-md-6">
        <label for="" class="form-label">{{ __('finance::finance.attachments') }}</label>
        <div class="work_samples_grid">
            <label class="img_upload">
                <input type="file" accept="image/,video/" multiple name="file[]" id="img-upload"
                       onchange="handleFileSelect(event)">
                <div class="img justify-content-center">
                    <img loading="lazy" src="{{ asset('admin/assets/img/plus.svg') }}" alt="plus">
                </div>
            </label>
        </div>
        <div id="file-names-container" class="mt-2"></div>
    </div>

    <div class="col-12 modal-footer">
        {!! submitButton($storeRoute) !!}
        {!! closeButton() !!}
    </div>

</form>
<script>
    $('#tax_number').on('input', function() {
        // Keep only numeric characters
        var taxNumber = $(this).val().replace(/\D/g, '').substring(0, 15); // Limit length to 15 characters
        $(this).val(taxNumber);

        // Validate: starts with '3', followed by 13 digits, and ends with '3'
        var isValid = /^3\d{13}3$/.test(taxNumber);

        // Toggle validation classes
        $(this).toggleClass('is-valid', isValid || taxNumber === '');
        $(this).toggleClass('is-invalid', !isValid && taxNumber !== '');
    });

    function handleFileSelect(event) {
        const files = event.target.files;
        const container = document.getElementById('file-names-container');
        container.innerHTML = '';

        if (files.length > 0) {
            const fileList = document.createElement('div');
            fileList.className = 'selected-files-list';

            for (let i = 0; i < files.length; i++) {
                const fileItem = document.createElement('div');
                fileItem.className = 'file-item d-flex align-items-center mb-1';

                const icon = document.createElement('i');
                icon.className = files[i].type.startsWith('image/') ?
                    'fas fa-file-image me-2' :
                    'fas fa-file-video me-2';
                const fileName = document.createElement('span');
                fileName.textContent = files[i].name;

                fileItem.appendChild(icon);
                fileItem.appendChild(fileName);
                fileList.appendChild(fileItem);
            }

            container.appendChild(fileList);
        } else {
            container.innerHTML = '<div class="text-muted">No files selected</div>';
        }
    }
</script>

<script>
    $('#is_company').on('change', function() {
        if($(this).val() == '1'){
            $('.zatca').removeClass('d-none');
        }
        else{
            $('.zatca').addClass('d-none');
        }
    });
</script>
