

                <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
                <thead class="thead-inverse">
                    <tr>
                        <th>{{ __('crm.SL') }}</th>
                        <th>{{ __("hr.Employee") }} </th>
                        <th>{{ __("crm.title") }} </th>
                        <th>{{ __("crm.description") }} </th>
                        <th>{{ __("crm.attach") }} </th>
                    </tr>
                </thead>
                <tbody>
                @foreach($obj->replies as $key => $reply )
                    <tr>
                        <td>{{ $key+1 }}</td>
                        <td>{{ $reply->employee->name }}</td>
                        <td>{{ $reply->title }}</td>
                        <td>{{ $reply->description }}</td>
                        <td>{!! getImgTag($reply->file) !!}   </td>

                    </tr>
                @endforeach
                </tbody>

                </table>





    </script>

