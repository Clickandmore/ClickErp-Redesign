<table class="table table-bordered table-hover">
    <thead>
        <tr>
            <th colspan="2">{{ __("hr.from_date") }}</th>
            <td colspan="2">{{ $obj->from_date }} </td>
        </tr>
        <tr>
            <th colspan="2">{{ __("hr.to_date") }}</th>
            <td colspan="2">{{ $obj->to_date }} </td>
        </tr>
    </thead>
    <thead>
        <tr>
            <th scope="col">#</th>
            <th scope="col">{{ __("hr.date") }}</th>
            <th scope="col">{{ __("hr.value") }} </th>
            <th scope="col">{{ __("hr.status") }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($obj->advanceValues as $key => $advanceValues)
            <tr>
                <th scope="row">{{ $key + 1 }}</th>
                <td>{{ $advanceValues->date }}</td>
                <td>{{ $advanceValues->amount }}</td>
                <td>
                    <div class="btn {{ $advanceValues->status == 'new' ? 'btn-danger' : 'btn-success' }}" style="cursor:default" role="alert">
                        {!! $advanceValues->status->lang() !!}
                    </div>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
</form>
