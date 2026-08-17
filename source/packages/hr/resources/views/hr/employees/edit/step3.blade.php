<div class="row g-2">
    <div class="col-12">
        <table id="myDataTable_no_filter" class="table align-middle card-table my-2">
            <thead>
            <tr>
                <th></th>
                    <th>{{ __("hr::hr.name") }}</th>
                    <th>{{ __("hr::hr.Options") }} </th>
                <th>{{ __("hr::hr.actions") }}</th>
            </tr>
            </thead>
            <tbody id="json_array" class="content" data-mfield-options='{"section": ".y-group","btnAdd":"#btnAdd-2","btnRemove":".btnRemove"}'>
                @if (count($obj->files) > 0)
                    @foreach ($obj->files as $key=>$files)
                        <tr class="y-group">
                            <td> </td>
                                <td>
                                    <input type="text" name="title[]" value="{{$files->title}}" class="form-control">
                                    <input type="hidden" name="attachment_id[]" value="{{$files->id}}" class="form-control">
                                </td>
                                <td>
                                    <input type="file" name="file[]" class="dropify" data-allowed-file-extensions="jpg jpeg png gif"
                                        data-max-file-size="2M" data-show-remove="true" data-default-file="{{showFile($files->file)}}">
                                </td>
                            <td>
                                <i class="fa fa-plus-circle" id="btnAdd-2"></i>
                                {{-- @if ($key > 0)
                                    <button type="button" class="btn btn-danger deleteButtonTable" delete-route="{{route("projectAttachment.destroy", $projectAttachment->id)}}">
                                        <i class="fa fa-trash-o"></i>
                                    </button>
                                @else --}}
                                    <i class="fa fa-trash-o btnRemove"></i>
                                {{-- @endif --}}
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr class="y-group">
                        <td> </td>
                            <td>
                                <input type="text" name="title[]" class="form-control">
                                <input type="hidden" name="attachment_id[]" class="form-control">
                            </td>
                            <td>
                                <input type="file" name="file[]" class="dropify" data-allowed-file-extensions="jpg jpeg png gif"
                                    data-max-file-size="2M" data-show-remove="true">
                            </td>
                        <td>
                            <i class="fa fa-plus-circle" id="btnAdd-2"></i>
                            <i class="fa fa-trash-o btnRemove"></i>
                        </td>
                    </tr>
                @endif

            </tbody>
        </table>
    </div>
</div>
