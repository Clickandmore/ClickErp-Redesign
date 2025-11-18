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

    .toggle-pin,
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
        top: 0 !important;
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
        z-index: unset !important;
    }
    tbody tr:hover td{
        position: relative !important;
        z-index: unset !important;
    }
</style>
