<table class="table table-bordered">
    <tr>
        <th>{{ __("finance::finance.date") }}</th>
        <td>{{ $obj->date }}</td>
    </tr>
    <tr>
        <th>{{ __("finance::finance.From Account") }}</th>
        <td>{{ $obj->creditAccount->name }}</td>
    </tr>
    <tr>
        <th>{{ __("finance::finance.To Account") }}</th>
        <td>{{ $obj->debitAccount->name }}</td>

    </tr>
    <tr>
        <th>{{ __("finance::finance.value") }}</th>
        <td>{{ $obj->value }}</td>
    </tr>
    <tr>
        <th>{{ __("finance::finance.statement") }}</th>
        <td>{{ $obj->statement }}</td>
    </tr>
    <tr>
        <th>{{ __('finance::finance.attachments') }}</th>
        <td>
            <div class="work_samples_grid">
                @if ($obj->files->count() > 0)
                    @foreach ($obj->files as $file)
                        <div class="uploadedImage">
                            <img src="{{ showFile($file->file) }}" alt="">
                        </div>
                    @endforeach
                @else
                    <p>{{ __('No attachments available') }}</p>
                @endif
            </div>
        </td>
    </tr>
</table>
<div class="col-12 modal-footer">
    {!! closeButton() !!}
</div>

