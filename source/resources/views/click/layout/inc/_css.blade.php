<!------------------------ bundle -------------------------------------------------->
<link rel="stylesheet" href="{{asset('admin')}}/assets/cssbundle/select2.min.css">
<!------------------------ vendor -------------------------------------------------->
<link rel="stylesheet" href="{{asset('admin')}}/assets/vendor/toster/css/jquery.toast.css">
<!------------------------ main -------------------------------------------------->
<link rel="stylesheet" href="{{asset('admin')}}/assets/css/luno-style.css">
<!------------------------ custom -------------------------------------------------->
<link rel="stylesheet" href="{{asset('admin')}}/assets/css/custom.css">
<link rel="stylesheet" href="{{asset('admin')}}/assets/css/fontawesome.min.css">

<!-- fancybox -->
<link rel="stylesheet" href="{{ asset('admin') }}/assets/css/fancybox.css" />
<!------------------------ dropify -------------------------------------------------->
<link rel="stylesheet" href="{{url('/')}}/admin/assets/cssbundle/dropify.min.css">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
    .cm-notifications {
        position: relative;
    }

    .cm-notifications .notification-badge {
        position: absolute;
        top: -4px;
        inset-inline-end: -6px;
        min-width: 18px;
        height: 18px;
        padding: 0 5px;
        border-radius: 999px;
        background: #dc3545;
        color: #fff;
        border: 2px solid #fff;
        font-size: 10px;
        font-weight: 700;
        line-height: 14px;
        text-align: center;
        box-shadow: 0 6px 14px rgba(220, 53, 69, .28);
    }

    .cm-notifications-menu {
        width: min(390px, calc(100vw - 24px));
        overflow: hidden;
        border-radius: 14px !important;
    }

    .cm-notifications-panel {
        background: #fff;
        overflow: hidden;
    }

    .cm-notifications-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        padding: 16px 16px 12px;
        border-bottom: 1px solid #eef1f5;
        background: linear-gradient(180deg, #f8fafc 0%, #fff 100%);
    }

    .cm-notifications-head h6 {
        font-weight: 800;
        color: #172033;
    }

    .cm-notifications-subtitle {
        display: block;
        margin-top: 4px;
        color: #7a8599;
        font-size: 12px;
    }

    .cm-notifications-head .mark-all-read {
        flex: 0 0 auto;
        color: #2563eb;
        background: #eff6ff;
        border: 1px solid #dbeafe;
        border-radius: 999px;
        padding: 5px 10px;
        font-size: 12px;
        font-weight: 700;
    }

    .cm-notifications-head .mark-all-read:hover {
        color: #1d4ed8;
        background: #dbeafe;
    }

    .cm-notifications-list {
        max-height: 430px;
        overflow-y: auto;
        padding: 6px;
    }

    .cm-notification-item {
        position: relative;
        display: flex;
        gap: 12px;
        padding: 12px;
        border-radius: 12px;
        color: #172033;
        text-decoration: none;
        transition: background .15s ease, transform .15s ease;
    }

    .cm-notification-item:hover {
        background: #f8fafc;
        color: #172033;
        transform: translateY(-1px);
    }

    .cm-notification-item.is-unread {
        background: #f0f7ff;
    }

    .cm-notification-item.is-unread:hover {
        background: #e7f1ff;
    }

    .cm-notification-icon {
        width: 36px;
        height: 36px;
        flex: 0 0 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        color: #2563eb;
        background: #dbeafe;
    }

    .cm-notification-body {
        min-width: 0;
        flex: 1 1 auto;
        padding-inline-end: 28px;
    }

    .cm-notification-title {
        display: flex;
        align-items: center;
        gap: 7px;
        margin-bottom: 3px;
        font-weight: 800;
        color: #172033;
        line-height: 1.35;
    }

    .cm-notification-unread-dot {
        width: 8px;
        height: 8px;
        flex: 0 0 8px;
        border-radius: 50%;
        background: #dc3545;
        box-shadow: 0 0 0 3px rgba(220, 53, 69, .12);
    }

    .cm-notification-message {
        margin: 0;
        color: #4b5565;
        font-size: 13px;
        line-height: 1.5;
    }

    .cm-notification-time {
        display: flex;
        align-items: center;
        gap: 5px;
        margin-top: 7px;
        color: #8b95a7;
        font-size: 11px;
    }

    .cm-notification-read-action {
        position: absolute;
        top: 10px;
        inset-inline-end: 10px;
        width: 26px;
        height: 26px;
        border: 0;
        border-radius: 50%;
        color: #2563eb;
        background: #fff;
        box-shadow: 0 4px 12px rgba(15, 23, 42, .08);
    }

    .cm-notification-read-action:hover {
        color: #fff;
        background: #2563eb;
    }

    .cm-notification-state {
        padding: 32px 18px;
        text-align: center;
        color: #7a8599;
    }

    .cm-notification-state i {
        width: 42px;
        height: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 10px;
        border-radius: 14px;
        color: #64748b;
        background: #f1f5f9;
        font-size: 18px;
    }

    .cm-notification-state strong {
        display: block;
        color: #334155;
        margin-bottom: 4px;
    }

    .cropper-container {
        max-width: 100%;
        max-height: 400px;
    }
    .image-preview {
        max-width: 100%;
        max-height: 300px;
        display: block;
        margin: auto;
    }
</style>


<!-- RTL Style For Text Area -->
<style>
    .text-area-ar{
        direction: rtl !important;
    }
    .text-area-en {
        direction: ltr !important;
    }
</style>
<!-- RTL Style For Text Area -->

<!-- RTL Style For table paginate -->
<style>
    ul.pagination {
        direction: ltr !important;
    }
    .paging_simple_numbers{
        justify-content: end;
        display: flex;
    }
</style>
<!-- RTL Style For table paginate -->

<style>
    .sortable-item {
        padding: 10px;
        background-color: #d7d7d7;
        margin-bottom: 3px;
        border-radius: 10px;
        transition: background-color 0.3s ease;
        cursor: move;
        font-weight: bold;
        box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.1);
    }

    .sortable-item:hover,
    .sortable-item:active {
        background-color: #00a650 !important;
        color: white;
    }

    .sortable-item:active {
        transform: scale(1.02) !important;
    }
    .sortable-image {
        width: 50px;
        height: 50px;
        margin-right: 10px;
        border-radius: 5px;
    }
</style>
<style>
    .work_samples_grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: 12px;
    }

    .work_samples_grid .img_upload {
        width: 100%;
        height: 160px;
        border-radius: 8px;
        border: 1px solid #e9e9e9;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }

    .work_samples_grid .img_upload input {
        display: none;
    }

    .work_samples_grid .img_upload img {
        height: 48px;
    }

    .work_samples_grid .uploadedImage {
        width: 100%;
        height: 160px;
        border-radius: 8px;
        border: 1px solid transparent;
        overflow: hidden;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }

    .work_samples_grid .uploadedImage img {
        width: 100%;
        height: 100%;
        -o-object-fit: cover;
        object-fit: cover;
    }

    .work_samples_grid .uploadedImage .delete {
        position: absolute;
        top: 6px;
        right: 6px;
        background: #ffffff;
        border-radius: 50%;
        width: 26px;
        height: 26px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .work_samples_grid .uploadedImage .delete img {
        height: 16px;
        width: 16px;
    }
    tbody tr , tbody td{
        position: relative;
        /* z-index: unset !important; */
    }
</style>
