<style>
    .bill-attachments {
        direction: rtl;
    }

    .bill-attachments__intro {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 14px;
        padding: 12px 14px;
        border-radius: 10px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
    }

    .bill-attachments__grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 12px;
    }

    .bill-attachment-upload,
    .bill-attachment-item {
        position: relative;
        min-height: 150px;
        border-radius: 11px;
        overflow: hidden;
        border: 1px solid #dbe2e8;
        background: #f8fafc;
    }

    .bill-attachment-upload {
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px dashed #e0a429;
        cursor: pointer;
        color: #8a6817;
        text-align: center;
    }

    .bill-attachment-upload input {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
    }

    .bill-attachment-upload i {
        display: block;
        margin-bottom: 8px;
        font-size: 30px;
    }

    .bill-attachment-item__preview {
        display: flex;
        width: 100%;
        height: 150px;
        align-items: center;
        justify-content: center;
        color: #dc3545;
        text-decoration: none;
    }

    .bill-attachment-item__preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .work_samples_grid .bill-attachment-item .bill-attachment-item__delete {
        position: absolute;
        top: 7px;
        left: 7px;
        right: auto;
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 0;
        border-radius: 50%;
        background: rgba(220, 53, 69, .92);
        color: #fff;
        box-shadow: 0 2px 7px rgba(0, 0, 0, .18);
    }
</style>

<form class="row g-3 from-submit-global bill-attachments" method="post" action="{{ $updateRoute }}"
    enctype="multipart/form-data">
    @method('PUT')

    <input type="hidden" value="{{ $obj->id }}" name="invoice_id">

    <div class="col-12">
        <div class="bill-attachments__intro">
            <div>
                <h5 class="fw-bold mb-1">مرفقات الفاتورة رقم {{ $obj->code }}</h5>
                <small class="text-muted">الصور وملفات PDF، بحد أقصى 10 ميجابايت لكل ملف.</small>
            </div>
            <span class="badge bg-light text-dark border">
                {{ $obj->saleAttachments->count() }} مرفق
            </span>
        </div>

        <div class="bill-attachments__grid work_samples_grid">
            <label class="bill-attachment-upload img_upload">
                <input type="file" accept="image/jpeg,image/png,image/webp,application/pdf" multiple name="file[]"
                    id="img-upload" onchange="handleFileSelect(event)">
                <span>
                    <i class="fa fa-cloud-upload-alt"></i>
                    إضافة مرفقات
                </span>
            </label>

            @foreach ($obj->saleAttachments as $file)
                @php
                    $fileUrl = showFile($file->file);
                    $extension = strtolower(pathinfo((string) $file->file, PATHINFO_EXTENSION));
                    $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
                @endphp
                <div class="bill-attachment-item uploadedImage">
                    <a href="{{ $fileUrl }}" target="_blank" class="bill-attachment-item__preview"
                        title="فتح المرفق">
                        @if ($isImage)
                            <img src="{{ $fileUrl }}" alt="مرفق الفاتورة">
                        @else
                            <span class="text-center">
                                <i class="fa fa-file-pdf fa-3x"></i>
                                <small class="d-block mt-2">فتح ملف PDF</small>
                            </span>
                        @endif
                    </a>
                    <button type="button"
                        class="bill-attachment-item__delete delete deleteImagesButton"
                        delete-route="{{ route('finance.bill-sales-attachments.destroy', $file->id) }}"
                        title="حذف المرفق">
                        <i class="fa fa-trash"></i>
                    </button>
                </div>
            @endforeach
        </div>
    </div>

    <div class="col-12 modal-footer">
        <button type="submit" class="btn btn-primary">
            <i class="fa fa-save me-1"></i>
            حفظ المرفقات
        </button>
        {!! closeButton() !!}
    </div>
</form>
