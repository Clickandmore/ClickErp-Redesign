@extends('click.layout.indexs.index')
@section("style")
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
    <style>
.container {
    margin: 20px;
}

.card {
    border-radius: 8px;
    box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.card-header {
    font-size: 1rem;
    padding: 8px 15px;
    font-weight: bold;
}

.card-body {
    padding: 10px;
    font-size: 0.9rem;
}

.detail-row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    margin-bottom: 8px;
    padding: 5px 0;
    border-bottom: 1px solid #ddd;
}

.detail-row:last-child {
    border-bottom: none;
}

.detail-row .col-md-6 {
    padding: 5px 10px;
}

.detail-row strong {
    font-weight: 500;
    color: #333;
}

.detail-row span {
    color: #555;
    display: inline-block;
    margin-left: 5px;
}

.card-body strong {
    font-weight: 500;
    color: #333;
}

.card-body span {
    color: #555;
}

.attachments-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.attachment-thumbnail {
    width: 80px;
    height: 80px;
}

.attachment-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 4px;
}


    </style>
@endsection
@section('page-title')
    {{ $bladeTitle }}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{ $bladeTitle }}</li>
@endsection
@section('content')
    {!! addButton($createRoute , $addButtonText) !!}
    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead>
        <tr>
            <th>{{ __('finance::finance.SL') }}</th>
            <th>{{__("finance::finance.name")}}</th>
            <th>{{__("finance::finance.code")}}</th>
            <th>{{__("finance::finance.main_account")}}</th>
            <th>{{__("finance::finance.purchase_date")}}</th>
            <th>{{__("finance::finance.actions")}}</th>
        </tr>
        </thead>
        <tbody>

        </tbody>
    </table>


@endsection
@section('js')
    <script src="{{asset("admin")}}/assets/js/bundle/dataTables.bundle.js"></script>
    <script>
        $(document).ready(function () {
            let columns = [
                {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                {"data": "name", orderable: false, searchable: true},
                {"data": "code", orderable: false, searchable: true},
                {"data": "parent_account_id", orderable: false, searchable: true},
                {"data": "purchase_date", orderable: false, searchable: true},
                {"data": "actions", orderable: false, searchable: false}
            ];
            showDataTable("{{ $dataTableRoute }}", columns);
        });

        $(document).ajaxComplete(function () {
            $('#parent_account_id').on('change', function () {
                let url = '{{route("finance.child-code.show",":id")}}';
                url = url.replace(':id', $(this).val());
                $.ajax({
                    type: "GET",
                    url: url,
                    data: {
                        parent_id: $('#parent_account_id').val()
                    },
                    success: function (data) {
                        $('#code_prin').val(data);
                        let parentStatus = $('#parent_account_id option:selected').data('status');
                        $('#statusCildSelect option').each(function() {
                            // If the option's value matches the parent status
                            if ($(this).val() == parentStatus) {
                                // Select this option
                                $(this).prop('selected', true);
                            }
                        });
                    }
                });
            });
        });

        $(document).ajaxComplete(function () {
            $('#perished_type').on('change', function () {
                var selectedType = $(this).val();

                $('#junk_value_container').hide();
                $('#perished_value_container').hide();
                $('#perished_account_id_container').hide();
                $('#perished_end_date_container').hide();
                $('#period_container').hide();
                $('#expense_account_id_container').hide();
                $('#account_perished_type_container').hide();

                if (selectedType === 'fixed_installment') {
                    $('#perished_value_label').text("قيمة الإهلاك");
                    $('#perished_value_unit').text("$");
                    $('#junk_value_container').show();
                    $('#perished_account_id_container').show();
                    $('#perished_value_container').show();
                    $('#perished_end_date_container').show();
                    $('#period_container').show();
                    $('#expense_account_id_container').show();
                    $('#account_perished_type_container').show();
                } else if (selectedType === 'contradictory_installment') {
                    $('#perished_value_label').text("نسبة الإهلاك");
                    $('#perished_value_unit').text("%");
                    $('#perished_account_id_container').show();
                    $('#perished_end_date_container').show();
                    $('#period_container').show();
                    $('#junk_value_container').show();
                    $('#perished_value_container').show();
                    $('#expense_account_id_container').show();
                    $('#account_perished_type_container').show();
                } else if (selectedType === 'without') {
                }
            });

            $('#account_type').on('change', function () {
                var selectedAccountType = $(this).val();

                if (selectedAccountType === 'private') {
                    $('#perished_account_id_container').hide();
                    $('#expense_account_id_container').hide();
                } else {
                    $('#perished_account_id_container').show();
                    $('#expense_account_id_container').show();
                }
            });

            $('#perished_type').trigger('change');
            $('#account_type').trigger('change');
        });
    </script>

<script>
    $(document).ajaxComplete(function () {
        // عند تغيير نوع التسجيل
        $('#registration_type').on('change', function () {
            var selectedType = $(this).val();

            if (selectedType === 'purchase') {
                // إذا كان نوع التسجيل "شراء"، تعيين قيمة "principle_value" تلقائيًا وجعله غير قابل للتعديل
                $('#principle_value').val($('#purchase_value').val());
                $('#principle_value').prop('readonly', true);

                // عند تغيير قيمة "purchase_value"، يتم تحديث "principle_value"
                $('#purchase_value').on('input', function() {
                    $('#principle_value').val($(this).val());
                });

            } else if (selectedType === 'first_term') {
                // إذا كان نوع التسجيل "أول مدة"، السماح بتعديل كلا الحقلين
                $('#principle_value').prop('readonly', false);

                // السماح للمستخدم بإدخال قيم مستقلة
                $('#purchase_value').off('input'); // إزالة أي ارتباط سابق بتعيين القيم تلقائيًا
            }
        });

        // تفعيل الخيار الافتراضي عند تحميل الصفحة
        $('#registration_type').trigger('change');
    });
</script>
@endsection
