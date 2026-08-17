<form class="row g-3 from-submit-global" method="post" action="{{$storeRoute}}"
      enctype="multipart/form-data">

    <div class="col-3">
        <label for="name" class="form-label">{{ __('finance::finance.name') }}</label>
        <input type="text" name="name" class="form-control" id="name" data-validation="required,length"
               data-validation-length="max255">
    </div>
    <div class="col-6">
        <label class="form-label">{{ __('finance::finance.suppliers_types') }} </label>
        <input type="text" name="supplier_type_name" id="supplier_type_name" class="form-control"
               list="supplier_types_list" data-validation="required,length" data-validation-length="max255"
               placeholder="{{ __('finance::finance.suppliers_types') }}">
        <datalist id="supplier_types_list">
            @foreach(\Nami\Finance\Models\SupplierType::query()->orderBy('name')->get() as $it)
                <option value="{{ $it->name }}"></option>
            @endforeach
        </datalist>
        <small class="text-muted">إذا لم تكن الفئة موجودة، اكتب اسمها وسيتم إضافتها تلقائيًا.</small>
    </div>
    <div class="col-3">
        <label for="TextInput" class="form-label">{{ __('finance::finance.code') }}</label>
        <input type="text" value="{{ @$code }}" name="code" class="form-control" data-validation="required" disabled>
        <input type="hidden" name="code" value="{{ @$code }}">
    </div>

    <div class="col-3">
        <label for="phone" class="form-label">{{ __('finance::finance.phone') }}</label>
        <input type="text" name="phone" class="form-control" id="phone" data-validation="required,length"
               data-validation-length="max20">
    </div>

    <div class="col-3">
        <label for="address" class="form-label">{{ __('finance::finance.address') }}</label>
        <input type="text" name="address" class="form-control" id="address" data-validation="required,length"
               data-validation-length="max255">
    </div>

    <div class="col-3">
        <label for="tax_number" class="form-label">{{ __('finance::finance.tax_number') }}</label>
        <input type="number" name="tax_number" class="form-control" id="tax_number" data-validation="number"
               data-validation-length="max255">
    </div>

    <div class="col-3">
        <label for="commercial_register_number"
               class="form-label">{{ __('finance::finance.commercial_register_number') }}</label>
        <input type="number" name="commercial_register_number" class="form-control" id="commercial_register_number"
               data-validation="required,number" data-validation-length="max255">
    </div>

    <div class="col-3">
        <label for="email" class="form-label">{{ __('finance::finance.email') }}</label>
        <input type="text" name="email" class="form-control" id="email" data-validation="required,length"
               data-validation-length="max255">
    </div>

    <div class="col-3">
        <label for="credit_limit" class="form-label">{{ __('finance::finance.credit_limit') }}</label>
        <input type="number" name="credit_limit" class="form-control" id="credit_limit"
               data-validation="required,number">
    </div>

    <div class="col-md-6">
        <label for="days_number" class="form-label">{{ __('finance::finance.days_credit_period') }}</label>
        <input type="number" name="days_number" class="form-control" id="days_number" data-validation="required">
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

    <div class="col-6">
        <label for="commercial_register_image" class="form-label">{{ __("finance::finance.commercial_register_image") }}</label>
        <input type="file" id="commercial_register_image" name="commercial_register_image" class="dropify">
    </div>

    <div class="col-6">
        <label for="tax_image" class="form-label">{{ __("finance::finance.tax_image") }}</label>
        <input type="file" id="tax_image" name="tax_image" class="dropify">
    </div>

    <div class="col-6">
        <label for="national_address" class="form-label">{{ __("finance::finance.national_address") }}</label>
        <input type="file" id="national_address" name="national_address" class="dropify">
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
    </div>

    <div class="col-12 modal-footer">
        {!! submitButton($storeRoute) !!}
        {!! closeButton() !!}
    </div>

</form>
<script>
    function handleFileSelect(event) {
        const files = event.target.files;
        const videoExtensions = ['mp4', 'avi', 'mkv', 'mov', 'wmv', 'flv', 'webm', '3gp', 'mpeg', 'mpg', 'ogv', 'm4v',
            'f4v', 'ts'];

        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            const fileExtension = file.name.split('.').pop().toLowerCase();
            const reader = new FileReader();

            reader.onload = function(event) {
                const uploadedImageDiv = document.createElement("div");
                uploadedImageDiv.classList.add("uploadedImage");

                // Check if the file is a video based on its extension
                if (videoExtensions.includes(fileExtension)) {
                    const video = document.createElement("video");
                    video.width = 100; // Set the width
                    video.height = 100; // Set the height
                    video.playsInline = true; // Enable playsinline for mobile devices
                    video.autoplay = true; // Enable autoplay
                    video.loop = true; // Enable looping
                    video.muted = true; // Mute the video to allow autoplay

                    // Create the source element
                    const source = document.createElement("source");
                    source.src = event.target.result; // Set the video source
                    source.type = `video/${fileExtension}`; // Set the type of the video

                    // Append the source to the video element
                    video.appendChild(source);
                    video.appendChild(document.createTextNode("Your browser does not support the video tag."));

                    uploadedImageDiv.appendChild(video);
                } else {
                    // Create an image element for non-video files
                    const img = document.createElement("img");
                    img.src = event.target.result;
                    img.alt = file.name;
                    img.width = 150; // Set the width
                    img.height = 150;

                    uploadedImageDiv.appendChild(img);
                }

                // Create the delete icon
                const deleteIcon = document.createElement("div");
                deleteIcon.classList.add("delete");
                deleteIcon.innerHTML =
                    '<img loading="lazy" src="{{ asset('admin') }}/assets/img/trash.svg" alt="trash">';

                // Add event listener to remove the preview on click
                deleteIcon.addEventListener("click", function() {
                    uploadedImageDiv.remove();
                });

                // Append the delete icon to the preview container
                uploadedImageDiv.appendChild(deleteIcon);

                // Append the preview container to the grid
                document.querySelector(".work_samples_grid").appendChild(uploadedImageDiv);
            };

            reader.readAsDataURL(file);
        }
    }
</script>
