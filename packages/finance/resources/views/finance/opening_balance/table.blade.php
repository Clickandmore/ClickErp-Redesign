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
                <th>الحساب الرئيسي</th>
                <th>المستوى</th>
                <th>{{ __('finance::finance.debtor') }}</th>
                <th>{{ __('finance::finance.creditor') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data['accounts_with_no_children']->groupBy('parent_id') as $parentId => $accounts)
                @php
                    $parent = $accounts->first()->parent;
                    $groupId = 'opening-balance-group-' . ($parentId ?: 'root');
                    $groupTitle = $parent ? $parent->code . ' - ' . $parent->name : 'بدون حساب رئيسي';
                    $isClientsGroup = $parent && (int) $parent->getRawOriginal('type') === \Nami\Finance\Enums\AccountTypesEnum::Clients->value;
                @endphp
                <tr class="opening-balance-group-row" data-group-target="{{ $groupId }}">
                    <td colspan="6">
                        <i class="fa {{ $isClientsGroup ? 'fa-folder' : 'fa-folder-open' }}"></i>
                        {{ $groupTitle }}
                        <span class="badge bg-secondary ms-2">{{ $accounts->count() }}</span>
                    </td>
                </tr>
                @foreach ($accounts as $row)
                    <tr title="" class="opening-balance-account-row {{ $groupId }}"
                        @if ($isClientsGroup) style="display: none;" @endif>
                        <td>{{ $row->code }}</td>
                        <td>{{ $row->name }}</td>
                        <td>{{ $groupTitle }}</td>
                        <td>{{ $row->level }}</td>
                        @php
                            $accountData = $data['constraint_details']->where('account_id', $row->id)->first();
                            $debtorValue = $accountData['debtor_value'] ?? null;
                            $creditorValue = $accountData['creditor_value'] ?? null;
                        @endphp
                        <td>
                            <input id="debtor_value{{ $row->id }}" data-edited="false"
                                data-last-value="{{ $debtorValue ?? 0 }}" class="form-control debtor-input" type="number"
                                min="0" step="0.01" value="{{ $debtorValue ?? 0 }}" data-account-id="{{ $row->id }}"
                                data-account-status="{{ @$row->status->value }}">
                            <span class="error-span" hidden
                                style='color:red'>{{ __('finance::finance.Enter Valid Values') }}</span>
                        </td>
                        <td>
                            <input id="creditor_value{{ $row->id }}" data-edited="false"
                                data-last-value="{{ $creditorValue ?? 0 }}" class="form-control creditor-input" type="number"
                                min="0" step="0.01" value="{{ $creditorValue ?? 0 }}" data-account-id="{{ $row->id }}"
                                data-account-status="{{ @$row->status->value }}">
                            <span class="error-span" hidden
                                style='color:red'>{{ __('finance::finance.Enter Valid Values') }}</span>
                        </td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>
    <div class="row">
        <form id="formOfFirstBalance">
            <input name="balance" id="balance" type="hidden">
            <input name="year_id" value="{{ $data['year_id'] }}" type="hidden">
            <input name="value" id="total_value" type="hidden">
            <div class="col-9 mt-2">
                <div id="openingBalanceSummary" class="alert alert-info py-2 mb-0">
                    إجمالي المدين: <strong data-total-debtor>0.00</strong>
                    | إجمالي الدائن: <strong data-total-creditor>0.00</strong>
                    | الفرق: <strong data-total-difference>0.00</strong>
                </div>
            </div>
            <div class="col-3">
                <button type="submit" class="btn btn-primary mt-2"
                    id="addOrUpdateConstraints">{{ __('finance::finance.Confirm') }}</button>
            </div>
        </form>
    </div>
@else
    {{--    @include('click.layout.inc.no-data') --}}
@endif
