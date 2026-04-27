<div class="row">
    <table class="table display dataTable table-hover">
        <thead>
        <tr>
            <th>{{__("finance::finance.code")}}</th>
            <th>{{__("finance::finance.account")}}</th>
        </tr>
        </thead>
        <tbody>
        @foreach($obj->accounts as $account)
            <tr>
                <td>{{$account->code}}</td>
                <td>{{$account->name}}</td>
            </tr>
        @endforeach
    </table>

    <div class="col-12 modal-footer">
        {!! closeButton() !!}
    </div>
</div>


