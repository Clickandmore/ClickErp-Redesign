@if (count($data))
    <div class="row mb-3">
        <div class="col-6">
            <label class="">{{ __('finance::finance.statement') }}</label>
            <textarea rows="3" name="statement" id="statement" class="form-control"
                {{ $data['check_previous_constraint'] ? 'disabled' : '' }}>
                {{ $data['constraint']->statement ?? '' }}
            </textarea>
        </div>
        <div class="col-6 mt-4">
            <label class="">{{ __('finance::finance.date') }}</label>
            <input name="date" id="date" type="date" value="{{ $data['constraint']->date ?? date('Y-m-d') }}"
                class="form-control" {{ $data['check_previous_constraint'] ? 'disabled' : '' }}>
        </div>
        @if (!$data['check_previous_constraint'] && $data['constraint'])
            <div class="col-12 mt-2 text-center">
                <button data-action="{{ route('finance::finance.constraints.destroy', $data['constraint']->id) }}"
                    class="btn btn-danger deleteFirstBalance">
                    {{ __('finance::finance.delete-first-balance') }} <i class="fa fa-trash"></i>
                </button>
            </div>
        @endif
    </div>
    <table class="table table-bordered table-responsive" id="table-to-export" style="width:100%">
        <thead>
            <tr>
                <th>{{ __('finance::finance.Account code') }}</th>
                <th>{{ __('finance::finance.Account Name') }}</th>
                <th>{{ __('finance::finance.debtor') }}</th>
                <th>{{ __('finance::finance.creditor') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data['accounts_with_no_children'] as $row)
                <tr title="">
                    <td>{{ $row->code }}</td>
                    <td>{{ $row->name }}</td>
                    @php
                        $accountData = $data['constraint_details']->where('account_id', $row->id)->first();
                        $debtorValue = $accountData['debtor_value'] ?? null;
                        $creditorValue = $accountData['creditor_value'] ?? null;
                    @endphp
                    @if ($data['constraint'])
                        <td>{{ $debtorValue ?? 0 }}</td>
                        <td>{{ $creditorValue ?? 0 }}</td>
                    @else
                        <td>
                            <input id="debtor_value{{ $row->id }}" data-edited="false"
                                class="form-control debtor-input" type="number" value="0"
                                data-account-id="{{ $row->id }}"
                                data-account-status="{{ @$row->status->value }}">
                            <span class="error-span" hidden
                                style='color:red'>{{ __('finance::finance.Enter Valid Values') }}</span>
                        </td>
                        <td>
                            <input id="creditor_value{{ $row->id }}" data-edited="false"
                                class="form-control creditor-input" type="number" value="0"
                                data-account-id="{{ $row->id }}"
                                data-account-status="{{ @$row->status->value }}">
                            <span class="error-span" hidden
                                style='color:red'>{{ __('finance::finance.Enter Valid Values') }}</span>
                        </td>
                    @endif
                    {{--                @if ($row->can_updated) --}}
                    {{--                    <td> --}}
                    {{--                        <input id="debtor_value{{$row->id}}" data-edited="false" class="form-control debtor-input" --}}
                    {{--                               type="number" value="0" data-account-id="{{$row->id}}" data-account-status="{{$row->status}}"> --}}
                    {{--                    </td> --}}
                    {{--                    <td> --}}
                    {{--                        <input id="creditor_value{{$row->id}}" data-edited="false"  class="form-control creditor-input" --}}
                    {{--                               type="number" value="0" data-account-id="{{$row->id}}" data-account-status="{{$row->status}}"> --}}
                    {{--                    </td> --}}
                    {{--                @else --}}
                    {{--                    <td>{{ $debtorValue }}</td> --}}
                    {{--                    <td>{{ $creditorValue }}</td> --}}
                    {{--                @endif --}}
                </tr>
            @endforeach
        </tbody>
    </table>
    @if (!$data['constraint'])
        <div class="row">
            <form id="formOfFirstBalance">
                <input name="balance" id="balance" type="hidden">
                <input name="year_id" value="{{ $data['year_id'] }}" type="hidden">
                <input name="value" id="total_value" type="hidden">
                <div class="col-3">
                    <button type="submit" class="btn btn-primary mt-2"
                        id="addOrUpdateConstraints">{{ __('finance::finance.Confirm') }}</button>
                </div>
            </form>
        </div>
    @endif
@else
    {{--    @include('click.layout.inc.no-data') --}}
@endif
