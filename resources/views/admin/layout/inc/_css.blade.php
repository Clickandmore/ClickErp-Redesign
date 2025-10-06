<!-- DataTables CSS -->
  <!-- DataTables + Bootstrap + Responsive -->
  <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">

<link rel="stylesheet" type="text/css"
    href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
<!-- DataTables Responsive CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
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

    .table tbody tr,
    tr,
    td {
        position: unset !important;
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
    
</style>
