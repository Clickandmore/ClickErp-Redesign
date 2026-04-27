<form class="row g-3 from-submit-global" method="post" action="{{ route('crm.price-offers.store') }}"
      enctype="multipart/form-data">
    <!-- Choose between Clients or Customers -->

    <input type="hidden" name="typeSelect" value="client">

    <!-- Clients Dropdown -->
    <div class="col-md-4" id="clientSelectDiv">
        <label for="clientSelect" class="form-label">{{ __("crm::crm.clients") }}
            {!! addUpButton(route('crm.clients.create'),'clientSelect' ,false, 'name') !!}
        </label>
        <select class="form-control show-tick select2" id="clientSelect" name="client_id">
            <option value="">{{ __("crm::crm.Choose") }}</option>
            @foreach ($clients as $item)
                <option value="{{ $item->id }}">{{ $item->name }}</option>
            @endforeach
        </select>
    </div>

    <!-- Customers Dropdown -->

    <!-- Other form fields -->
    <div class="col-md-4">
        <label for="dateInput" class="form-label">{{ __("crm::crm.date") }}</label>
        <input type="date" id="dateInput" name="date" class="form-control" data-validation="required">
    </div>

    <div class="col-md-4">
        <label for="totalInput" class="form-label">{{ __("crm::crm.total") }}</label>
        <input type="text" id="totalInput" name="total" class="form-control total">
    </div>

    <div class="col-md-12">
        <label for="notesTextarea" class="form-label">{{ __("crm::crm.notes") }}</label>
        <textarea id="notesTextarea" name="notes" class="form-control" cols="15" rows="3"></textarea>
    </div>

    <div class="col-md-12 mt-2 mb-2">
        <label for="descOffer" class="form-label">{{ __("crm::crm.desc_offer") }}</label>
        <textarea name="desc_offer" id="descOffer" class="form-control" cols="15" rows="5"></textarea>

        <!-- Word Upload Button -->
        <div class="mt-2">
            <label for="wordUpload" class="form-label"> قراءة من ملف WORD</label>
            <input type="file" id="wordUpload" class="form-control-file" accept=".docx" style="display: none;">
            <button type="button" class="btn btn-secondary" id="uploadIconBtn">
                <i class="fas fa-file-upload"></i> اضغط للرفع
            </button>
        </div>
    </div>

    <div class="col-md-12 mt-2 mb-2">
        <div class="mt-2">
            <label class="form-label"> رفع ملف جاهز لعرض السعر</label>
            <input type="file" name="file" class="form-control-file dropify" accept="image/*">
        </div>
    </div>

    <div class="col-12 modal-footer">
        {!! submitButton( route('crm.price-offers.store')) !!}
        {!! closeButton() !!}
    </div>
</form>

<script src="https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.4.2/mammoth.browser.min.js"></script>
<script src="https://cdn.ckeditor.com/4.16.2/standard/ckeditor.js"></script>
<script>
    $(document).ready(function () {
        $('#typeSelect').change(function () {
            var selectedType = $(this).val();
            if (selectedType === 'client') {
                $('#clientSelectDiv').show();
                $('#customerSelectDiv').hide();
            } else if (selectedType === 'customer') {
                $('#customerSelectDiv').show();
                $('#clientSelectDiv').hide();
            } else {
                $('#clientSelectDiv').hide();
                $('#customerSelectDiv').hide();
            }
        });

        // Initialize CKEditor
        initializeCKEditor();

        // Handle file upload button click
        $('#uploadIconBtn').click(function () {
            $('#wordUpload').click();
        });

        // Handle file selection
        $('#wordUpload').change(function (event) {
            var file = event.target.files[0];
            if (file) {
                var reader = new FileReader();
                reader.onload = function (e) {
                    var arrayBuffer = e.target.result;
                    mammoth.convertToHtml({arrayBuffer: arrayBuffer})
                        .then(function (result) {
                            var html = result.value;
                            if (editor) {
                                editor.setData(html);
                            } else {
                                console.error("CKEditor instance not found for 'descOffer'");
                            }
                        })
                        .catch(function (err) {
                            console.error("Error converting document: ", err);
                        });
                };
                reader.readAsArrayBuffer(file);
            }
        });
    });

    function initializeCKEditor() {
        if (CKEDITOR.instances.descOffer) {
            CKEDITOR.instances.descOffer.destroy(true);
        }
        editor = CKEDITOR.replace('descOffer', {
            language: 'ar', // Set language to Arabic
            contentsLangDirection: 'rtl', // Set content direction to RTL
            on: {
                instanceReady: function () {
                    console.log('CKEditor instance is ready');
                }
            }
        });
    }
</script>

