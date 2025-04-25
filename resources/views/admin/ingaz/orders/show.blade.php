@extends('admin.layout.indexs.index')
@section("style")
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
@endsection
@section('page-title')
    {{helperTrans("ingaz.orders")}}
@endsection

@section('content')
@if (@AdminGuardHasPerm(\App\PermKeys::ORDERS_VIEW))

    <h5>@lang('ingaz.order_files')</h5>

    @if ($data && $data->transfers->isNotEmpty())
        <table class="table">
            <thead>
                <tr>
                    <th>@lang('ingaz.file_name')</th>
                    <th>@lang('ingaz.notes')</th>
                    <th>@lang('ingaz.uploaded_at')</th>
                    <th>@lang('ingaz.actions')</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($data->transfers as $transfer)
                    <tr>
                        <td>{{ basename($transfer->file) }}</td>
                        <td>{{ $transfer->notes }}</td>
                        <td>{{ $transfer->created_at->format('Y-m-d H:i:s') }}</td>
                        <td>
                            <a href="{{ route('order_files.download', $transfer->id) }}" class="btn btn-primary">@lang('ingaz.download')</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p>@lang('ingaz.no_files_found')</p>
    @endif

@else
    @include('components.no_permissions')
@endif

@endsection


@section('js')
@endsection
