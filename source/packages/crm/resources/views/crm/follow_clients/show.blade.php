<table class="table table-bordered mt-2">

    <tbody>
    <tr>
        <th scope="row">{{ __("crm::crm.content") }}</th>
        <td>{{ $obj->content ?? '' }}</td>
    </tr>
    <tr>
        <th scope="row">{{ __("crm::crm.notes") }}</th>
        <td>{{ $obj->notes ?? '' }}</td>
    </tr>
    <tr>
        <th scope="row">{{ __("crm::crm.result") }}</th>
        <td>{{ $obj->result ?? '' }}</td>
    </tr>
    </tbody>
</table>
