@extends('admin.layout.indexs.index')
@section("style")
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
@endsection
@section('page-title')
    {{helperTrans("ingaz.requiremens")}}
@endsection
@section('content')
 
    
        {!! addButton(route("requiremens.create") ,helperTrans("ingaz.requiremens")) !!}

    <p></p>
    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead>
        <tr>
            <th>#</th>
            <th>{{helperTrans("ingaz.image")}}</th>
             <th>{{helperTrans("ingaz.title")}}</th>
            <th>{{helperTrans("ingaz.actions")}}</th>
 
        </tr>
        </thead>
        <tbody>

        </tbody>
    </table>

 
@endsection
@section('js')

    <script src="{{asset("admin")}}/assets/js/bundle/dataTables.bundle.js"></script>
    <script src="{{asset('admin')}}/assets/js/tinymce.min.js"></script>

    <script>
        $(document).ready(function () {
            let columns = [
                {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {"data": "image", orderable: true, searchable: true},
                 {"data": "title", orderable: true, searchable: true},

                 {"data": "actions", orderable: false, searchable: false}
            ];
            showDataTable("{{route("requiremens.index")}}", columns);
        });
    </script>


    <script>
        $(document).ready(function () {
            tinymce.init({
                selector: '.mytextarea',
                plugins: 'ai tinycomments mentions anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount checklist mediaembed casechange export formatpainter pageembed permanentpen footnotes advtemplate advtable advcode editimage tableofcontents mergetags powerpaste tinymcespellchecker autocorrect a11ychecker typography inlinecss textcolor colorpicker',
                toolbar: 'undo redo | formatselect | fontselect fontsizeselect | forecolor backcolor | bold italic underline strikethrough | align lineheight | tinycomments | checklist numlist bullist indent outdent | emoticons charmap | removeformat',
            });
        });
    </script>

@endsection
