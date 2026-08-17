<div class="row g-2">
    <div class="col-12">
        <table id="myDataTable_no_filter" class="table align-middle card-table my-2">
            <thead>
            <tr>
                <th></th>
                <th>{{ __("hr::hr.file_name") }}</th>
                <th>{{ __("hr::hr.file") }} </th>
                <th>{{ __("hr::hr.actions") }}</th>
            </tr>
            </thead>
            <tbody id="json_array" class="content" data-mfield-options='{"section": ".y-group","btnAdd":"#btnAdd-2","btnRemove":".btnRemove"}'>
                <tr class="y-group">
                    <td> </td>
                        <td>
                            <input type="text" name="title[]" class="form-control">
                            <input type="hidden" name="file_id[]" class="form-control">
                        </td>
                        <td>
                            <input type="file" name="file[]" class="form-control" data-allowed-file-extensions="jpg jpeg png gif"
                                data-max-file-size="2M" data-show-remove="true">
                        </td>
                    <td>
                        <i class="fa fa-plus-circle" id="btnAdd-2"></i>
                        <i class="fa fa-trash-o btnRemove"></i>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
