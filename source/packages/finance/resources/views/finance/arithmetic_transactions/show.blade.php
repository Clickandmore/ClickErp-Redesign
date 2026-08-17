<div class="row g-3">
    <div class="col-md-6 col-4">
        <label>{{ __('finance.code') }}</label>
        <span class="form-control">{{ $obj->code }}</span>
    </div><div class="col-md-6 col-4">
        <label>{{ __('finance.date') }}</label>
        <span class="form-control">{{ $obj->date }}</span>
    </div>
    <div class="col-md-6 col-4">
        <label>{{ __('finance.statement') }}</label>
        <span class="form-control form-control-lg mb-1">{{ $obj->statement }}</span>
    </div>
    <div class="debtor__value"></div>
    <table id="myDataTable_no_filter" class="table align-middle card-table">
        <thead>
        <tr>
            <td>{{ __('finance.debtor') }}</td>
            <td>{{ __('finance.creditor') }}</td>
            <td>{{ __('finance.Account Name') }}</td>
            <td>{{ __("finance.fn-cost-centers") }}</td>
            <td>{{ __('finance.Account Statement') }}</td>
        </tr>
        </thead>
        <tbody class="table-body">
        @foreach ($obj->constraintDetails as $constraintDetails)
            @if ($constraintDetails->type == 'debtor')
                <tr class="item-row debtor_append" id="debtor_append">
                    <td>
                        <span class="form-control">{{ $constraintDetails->debtor_value }}</span>
                    </td>
                    <td>
                        <span class="form-control">0</span>
                    </td>
                    <td>
                        <span class="form-control">{{ @$accounts->firstWhere('id', $constraintDetails->account_id)->name }}</span>
                    </td>
                    <td>
                        <span class="form-control">{{ @$cost_centers->firstWhere('id', $constraintDetails->cost_center_id)->title }}</span>
                    </td>
                    <td>
                        <span class="form-control">{{ $constraintDetails->statement }}</span>
                    </td>
                </tr>
            @endif
        @endforeach
        @foreach ($obj->constraintDetails as $constraintDetails)
            @if ($constraintDetails->type == 'creditor')
                <tr class="item-row" id="creditor_append">
                    <td>
                        <span class="form-control">0</span>
                    </td>
                    <td>
                        <span class="form-control">{{ $constraintDetails->creditor_value }}</span>
                    </td>
                    <td>
                        <span class="form-control">{{ $accounts->firstWhere('id', $constraintDetails->account_id)->name }}</span>
                    </td>
                    <td>
                        <span class="form-control">{{ @$cost_centers->firstWhere('id', $constraintDetails->cost_center_id)->title }}</span>
                    </td>
                    <td>
                        <span class="form-control">{{ $constraintDetails->statement }}</span>
                    </td>
                </tr>
            @endif
        @endforeach
        </tbody>
        <tfoot>
        <tr>
            <td><span class="form-control">{{$obj->value}}</span></td>
            <td><span class="form-control">{{$obj->value}}</span></td>
            <td>{{ __("finance.Total") }}</td>
            <td></td>
            <td></td>
        </tr>
        </tfoot>
    </table>

    <hr>
    <h4>{{ __('finance.attachments') }}</h4>
    <div class="col-12">
        <div class="work_samples_grid">
            @if ($obj->files->count() > 0)
                @foreach ($obj->files as $file)
                    @php
                        $extension = strtolower(pathinfo($file->file, PATHINFO_EXTENSION));
                        $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'], true);
                    @endphp
                    <div class="uploadedImage">
                        @if ($isImage)
                            <img src="{{ showFile($file->file) }}" alt="">
                        @else
                            <a href="{{ showFile($file->file) }}" target="_blank" class="constraint-file-preview" title="{{ basename($file->file) }}">
                                <i class="fa fa-file-text-o"></i>
                                <span>{{ strtoupper($extension ?: 'FILE') }}</span>
                            </a>
                        @endif
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    <div class="col-12 modal-footer">
        {!! closeButton() !!}
    </div>
</div>

