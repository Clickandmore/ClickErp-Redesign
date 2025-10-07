@extends('admin.layout.indexs.index')
@section("style")
<style>
.switch {
  position: relative;
  display: inline-block;
  width: 50px;
  height: 25px;
}
.switch input {
  opacity: 0;
  width: 0;
  height: 0;
}
.slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #ccc;
  transition: .4s;
}
.slider:before {
  position: absolute;
  content: "";
  height: 17px;
  width: 17px;
  left: 4px;
  bottom: 4px;
  background-color: white;
  transition: .4s;
}
input:checked + .slider {
  background-color: #28a745;
}
input:checked + .slider:before {
  transform: translateX(24px);
}
.slider.round {
  border-radius: 25px;
}
.slider.round:before {
  border-radius: 50%;
}
</style>
@endsection
@section('page-title')
{{helperTrans("ingaz.professional_application")}}
@endsection
@section('content')



<p></p>
<table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
    <thead>
        <tr>
            <th>#</th>
            <th>{{helperTrans("ingaz.facility_name")}}</th>
            <th>{{helperTrans("ingaz.number_of_workers_required")}}</th>
                  <th>{{helperTrans("ingaz.status")}}</th>

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
    $(document).ready(function() {
        let columns = [{
                "data": 'DT_RowIndex',
                name: 'DT_RowIndex',
                orderable: false,
                searchable: false
            },
            {
                "data": "facility_name",
                orderable: true,
                searchable: true
            },
            {
                "data": "number_of_workers_required",
                orderable: true,
                searchable: true
            },
                {
        "data": "status",
        orderable: false,
        searchable: false
    },


            {
                "data": "actions",
                orderable: false,
                searchable: false
            }
        ];
        showDataTable("{{route("professional_application.index")}}", columns);});
</script>


<script>
    $(document).ready(function() {
        tinymce.init({
            selector: '.mytextarea',
            plugins: 'ai tinycomments mentions anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount checklist mediaembed casechange export formatpainter pageembed permanentpen footnotes advtemplate advtable advcode editimage tableofcontents mergetags powerpaste tinymcespellchecker autocorrect a11ychecker typography inlinecss textcolor colorpicker',
            toolbar: 'undo redo | formatselect | fontselect fontsizeselect | forecolor backcolor | bold italic underline strikethrough | align lineheight | tinycomments | checklist numlist bullist indent outdent | emoticons charmap | removeformat',
        });
    });
</script>
<script>
$(document).on('change', '.toggle-status', function () {
    let checkbox = $(this);
    let id = checkbox.data('id');
    let status = checkbox.is(':checked') ? 'approved' : 'pending';

    $.ajax({
        url: `/admin/professional_application/${id}`,  // أو استخدم route() لو عندك ID جاهز
        type: 'PUT',
        data: {
            _token: '{{ csrf_token() }}',
            _method: 'PUT', // عشان Laravel يعرف إنه PUT مش POST
            status: status
        },
        success: function (res) {
            if (res.success) {
                $('.status-text-' + id).text(status)
                    .removeClass('badge-success badge-secondary')
                    .addClass(status === 'approved' ? 'badge-success' : 'badge-secondary');
            } else {
                alert('فشل في التحديث');
            }
        },
        error: function () {
            alert('حدث خطأ');
            checkbox.prop('checked', !checkbox.is(':checked'));
        }
    });
});

</script>

@endsection