@php
    $complaintTypes = \App\Models\ingaz\ComplaintType::all();
@endphp

<form class="form-wrap" action="{{route('web.complaints.store')}}" method="post" id="complaintForm" enctype="multipart/form-data">
    @csrf
    <div class="row">
        <div class="col-12 p-2">
            <div class="form-group">
                <label for="complaint_type_id">
                    <i class="fa-light fa-tag"></i>
                    {{__('نوع الشكوى')}}
                </label>
                <select name="complaint_type_id" id="complaint_type_id" class="form-control" required>
                    <option value="">{{__('اختر نوع الشكوى')}}</option>
                    @foreach($complaintTypes as $type)
                        <option value="{{$type->id}}">{{$type->name}}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-12 p-2">
            <div class="form-group">
                <label for="locationName">
                    <i class="fa-light fa-clipboard"></i>
                    {{__('عنوان الشكوى')}}
                </label>
                <input id="address" name="address" type="text" placeholder="{{__('مثال: المنزل أو الموقع')}}">
            </div>
        </div>
        <div class="col-lg-12 p-2">
            <div class="form-group">
                <label for="details">
                    <i class="fa-regular fa-comment-dots"></i>
                    {{__('تفاصيل الشكوى')}}
                </label>
                <textarea name="message" id="message" placeholder="{{__('اكتب تفاصيل الشكوى هنا')}} "></textarea>
            </div>
        </div>
        <div class="col-lg-12 p-2">
            <div class="form-group">
                <label for="file">
                    <i class="fa-regular fa-file"></i>
                    {{__('إرفاق ملف (اختياري)')}}
                </label>
                <div class="custom-file-upload-ar" dir="rtl">
                    <input type="file" name="file" id="file" class="file-input-ar" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" onchange="previewFile(this)">
                    <label for="file" class="file-label-ar">
                        <i class="fa-regular fa-cloud-upload"></i>
                        <span class="file-text">{{__('اختيار ملف')}}</span>
                    </label>
                    <div id="filePreview" class="file-preview-ar mt-2" style="display: none;">
                        <div class="preview-content-ar">
                            <i class="fa-regular fa-file"></i>
                            <span class="preview-name"></span>
                            <button type="button" class="remove-file" onclick="removeFile()">
                                <i class="fa-regular fa-times"></i> {{__('إزالة الملف')}}
                            </button>
                        </div>
                    </div>
                </div>
                <small class="text-muted">{{__('الملفات المسموحة')}}: PDF, DOC, DOCX, JPG, JPEG, PNG</small>
            </div>
        </div>
        <div class="col-lg-12 p-2">
            <div class="form-group mb-0">
                <button type="submit">
                    {{__('إرسال الشكوى')}}
                </button>
            </div>
        </div>
    </div>
</form>

<style>
.custom-file-upload-ar {
    position: relative;
    width: 100%;
    direction: rtl;
    text-align: right;
}

.file-input-ar {
    position: absolute !important;
    width: 1px !important;
    height: 1px !important;
    padding: 0 !important;
    margin: -1px !important;
    overflow: hidden !important;
    clip: rect(0,0,0,0) !important;
    border: 0 !important;
}

.file-label-ar {
    display: inline-flex;
    align-items: center;
    justify-content: flex-start;
    padding: 12px 28px;
    background-color: #f8f9fa;
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 1.1em;
    font-weight: 500;
    color: #495057;
    margin-bottom: 0.5rem;
}

.file-label-ar:hover {
    background-color: #e9ecef;
    border-color: #adb5bd;
}

.file-label-ar i {
    margin-left: 8px;
    font-size: 1.3em;
    color: #007bff;
}

.file-preview-ar {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 5px;
    padding: 10px;
    margin-top: 10px;
    text-align: right;
}

.preview-content-ar {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-direction: row-reverse;
}

.preview-content-ar i {
    color: #6c757d;
}

.preview-name {
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    direction: rtl;
    text-align: right;
}

.remove-file {
    background: none;
    border: none;
    color: #dc3545;
    cursor: pointer;
    padding: 0 8px;
    font-size: 1em;
    font-weight: 500;
}

.remove-file:hover {
    color: #c82333;
}
</style>

<script>
function previewFile(input) {
    const preview = document.getElementById('filePreview');
    const previewName = preview.querySelector('.preview-name');
    const fileLabel = document.querySelector('.file-text');

    if (input.files && input.files[0]) {
        const file = input.files[0];
        previewName.textContent = file.name;
        preview.style.display = 'block';
        fileLabel.textContent = 'تغيير الملف';
    }
}

function removeFile() {
    const input = document.getElementById('file');
    const preview = document.getElementById('filePreview');
    const fileLabel = document.querySelector('.file-text');

    input.value = '';
    preview.style.display = 'none';
    fileLabel.textContent = 'اختيار ملف';
}
</script>
