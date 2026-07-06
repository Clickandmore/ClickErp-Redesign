<!-- DataTables + Bootstrap + Responsive -->
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
<!------------------------ bundle -------------------------------------------------->
<link rel="stylesheet" href="{{ asset('admin') }}/assets/cssbundle/select2.min.css">
<!------------------------ vendor -------------------------------------------------->
<link rel="stylesheet" href="{{ asset('admin') }}/assets/vendor/toster/css/jquery.toast.css">
<!------------------------ Font Awesome -------------------------------------------------->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<!------------------------ main -------------------------------------------------->
<link rel="stylesheet" href="{{ asset('admin') }}/assets/css/luno-style.css">
<!------------------------ dropify -------------------------------------------------->
<link rel="stylesheet" href="{{ url('/') }}/admin/assets/cssbundle/dropify.min.css">
<!------------------------ loader -------------------------------------------------->
@include('admin.layout.loader.loaderCss')
<!------------------------ custom -------------------------------------------------->
<link rel="stylesheet" href="{{ asset('admin') }}/assets/css/custom.css">
<style>
    .tox-notification,
    .tox .tox-statusbar {
        display: none !important;
    }

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

    /* .toggle-pin,
    .btn-edit,
    .btn-view,
    .editButton,
    .deleteButton {
        width: 32px !important;
        height: 32px !important;
        padding: 0 !important;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    } */

    .btn-edit,
    .btn-view,
    .editButton {
        width: 32px !important;
        height: 32px !important;
        padding: 0 !important;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    table.dataTable thead th {
        white-space: nowrap !important;
        /* padding-inline-start: 26px !important; */
    }

    .table * {
        text-align: start !important;
    }

    table.dataTable thead>tr>th.sorting:before,
    table.dataTable thead>tr>th.sorting:after,
    table.dataTable thead>tr>th.sorting_asc:before,
    table.dataTable thead>tr>th.sorting_asc:after,
    table.dataTable thead>tr>th.sorting_desc:before,
    table.dataTable thead>tr>th.sorting_desc:after,
    table.dataTable thead>tr>th.sorting_asc_disabled:before,
    table.dataTable thead>tr>th.sorting_asc_disabled:after,
    table.dataTable thead>tr>th.sorting_desc_disabled:before,
    table.dataTable thead>tr>th.sorting_desc_disabled:after,
    table.dataTable thead>tr>td.sorting:before,
    table.dataTable thead>tr>td.sorting:after,
    table.dataTable thead>tr>td.sorting_asc:before,
    table.dataTable thead>tr>td.sorting_asc:after,
    table.dataTable thead>tr>td.sorting_desc:before,
    table.dataTable thead>tr>td.sorting_desc:after,
    table.dataTable thead>tr>td.sorting_asc_disabled:before,
    table.dataTable thead>tr>td.sorting_asc_disabled:after,
    table.dataTable thead>tr>td.sorting_desc_disabled:before,
    table.dataTable thead>tr>td.sorting_desc_disabled:after {
        right: unset !important;
        left: 10px;
    }

    .table.dataTable.dtr-inline.collapsed>tbody>tr.even>td:first-child::before,
    .table.dataTable.dtr-inline.collapsed>tbody>tr.even>th:first-child::before,
    .table.dataTable.dtr-inline.collapsed>tbody>tr.odd>td:first-child::before,
    .table.dataTable.dtr-inline.collapsed>tbody>tr.odd>th:first-child::before {
        position: relative !important;
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        transform: unset !important;

    }

    .dropdown-menu.dropdown-menu-end {
        right: 50% !important;
        left: unset !important;
        transform: translateX(50%) !important;
        /* top: 0 !important; */
        top: -125px !important;
        position: absolute !important;
        z-index: 99999 !important;
        min-width: 150px !important;
    }

    .dropdown-menu.dropdown-menu-end .toggle-pin {
        padding: 4px 16px !important;
    }

    table.custom-table tr:first-child .dropdown-menu.dropdown-menu-end {
        top: -60px !important;
        right: -90px !important;
    }

    table.rental-table .dropdown-menu.dropdown-menu-end {
        right: -64% !important;
        left: unset !important;
        transform: translateX(50%) !important;
        top: 0 !important;
        position: absolute !important;
        z-index: 99999 !important;
        min-width: 150px !important;
    }
    table.rental-table tr:first-child .dropdown-menu.dropdown-menu-end {
        right: -90px !important;
        left: unset !important;
        transform: translateX(50%) !important;
        top: -20px !important;
        position: absolute !important;
        z-index: 99999 !important;
        min-width: 150px !important;
    }

    tr.child.active-dropdown-row {
        z-index: 99999999999999999999 !important;
        position: relative !important;
    }

    tr.active-dropdown-row {
        z-index: 99999999999999999999 !important;
        position: relative !important;
    }

    @media only screen and (max-width: 1440px) {
        .layout-1 .sidebar {
            width: 300px;
        }


    }
     table {

            box-shadow: none !important;
            overflow: unset !important;
        }
    tbody tr ,  tbody tr:hover , tbody tr:not(:hover) {
        position: relative !important;
        /* z-index: unset !important; */
    }
    tbody tr:hover td{
        position: relative !important;
        /* z-index: unset !important; */
    }
</style>
