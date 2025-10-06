@extends('admin.layout.indexs.index')
@section("style")
@endsection
@section('page-title')
    {{helperTrans("ingaz.partenrs")}}
@endsection
@section('content')
 
         {!! addButton(route("partenrs.create") ,helperTrans("ingaz.partenrs")) !!}
 
    <p></p>
    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead>
        <tr>
            <th>#</th>
            <th>{{helperTrans("ingaz.image")}}</th>
            <th>{{helperTrans("ingaz.actions")}}</th>
 
        </tr>
        </thead>
        <tbody>

        </tbody>
    </table>

 
@endsection
@section('js')

    <script src="{{asset('admin')}}/assets/js/tinymce.min.js"></script>

    <script>
        $(document).ready(function () {
            let columns = [
                {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {"data": "image", orderable: true, searchable: true},
                 {"data": "actions", orderable: false, searchable: false}
            ];
            showDataTable("{{route("partenrs.index")}}", columns);
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
