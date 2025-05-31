@extends('admin.layout.indexs.index')


@section('page-title')
{{ __('admin.travel') }}
@endsection

@section('content')
@if (@AdminGuardHasPerm(\App\PermKeys::TRAVEL_VIEW))

    <div class="row">
        <div class="col-md-12">
            <form id="" action="{{ route('travel') }}" method="GET" class="row">
                <div class="col-md-3">
                    <input type="text" class="form-control" name="search" value="{{ @$search }}"
                        placeholder="{{ __('admin.search_by_client_maid_order') }}">
                </div>
                <div class="col-md-3">
                    <select name="travel_type" class="form-control select2">
                        <option value="">{{ __('admin.travel_type') }}</option>
                        @forelse (__('travel_types.travel_types') ?? [] as $k => $name)
                            <option value="{{$k}}" @if(request()->get('travel_type') == $k) selected @endif>{{ $name }}</option>
                        @empty
                        @endforelse
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-control select2">
                        <option value="">{{ __('admin.status') }}</option>
                        @forelse (__('travel_types.status') ?? [] as $k => $name)
                            <option value="{{$k}}" @if(request()->get('status') == $k) selected @endif>{{ $name }}</option>
                        @empty
                        @endforelse
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="date" class="form-control" name="arrival_date" value="{{ request()->get('arrival_date') }}"
                        placeholder="{{ __('admin.arrival_date') }}">
                </div>
                <div class="col-md-12 mt-3">
                    <button type="submit" class="btn btn-primary">
                        @lang('admin.filter')
                    </button>
                    <a href="{{ route('travel') }}" class="btn btn-secondary">
                        @lang('admin.cancel_filters')
                    </a>
                </div>
            </form>
        </div>

        <div class="col-md-12 col-sm-12 d-flex justify-content-center justify-content-md-end align-items-center mt-3">
            <a onclick="htmlTableToExcel('xlsx', '{{ __('admin.travel') }}')" class="btn btn-fr-bg mx-1">{{__('admin.export_to_xlxs') }}</a>
            @include('components.table_to_excel')

            {{-- @include('components.print-pdf') --}}

            <a href="{{ route('travel.control') }}" id="add-button" class="btn btn-success" type="button">
                @lang('admin.add')
            </a>

        </div>
    </div>

    @if (session('error'))
    <div class="alert alert-danger mt-5">
        {{ session('error') }}
    </div>
    @endif

    @if (session('success'))
    <div class="alert alert-success mt-5">
        {{ session('success') }}
    </div>
    @endif

    @if (session('status'))
    <div class="alert alert-warning mt-5">
        {{ session('status') }}
    </div>
    @endif

    <div class="row PrintArea">
        <div class="col-md-12 table-responsive my-5 text-center">
            <table id="tblToExcl" class="table table-striped table-row-bordered border table-rounded cu-table-adj">
                <thead>
                    <tr class="fw-bold fs-6 text-gray-800">
                        <th>#</th>
                        <th>{{ __('admin.client_id') }}</th>
                        <th>{{ __('admin.maid_id') }}</th>
                        <th>{{ __('admin.order_id') }}</th>
                        <th>{{ __('admin.travel_type') }}</th>
                        <th>{{ __('admin.travel_with') }}</th>
                        <th>{{ __('admin.arrival_airport') }}</th>
                        <th>{{ __('admin.arrival_date') }}</th>
                        <th>{{ __('admin.arrival_time') }}</th>
                        <th>{{ __('admin.flight_company') }}</th>
                        <th>{{ __('admin.flight_number') }}</th>
                        <th>{{ __('admin.status') }}</th>
                        <th class="PrintHide" style="width:10%">{{ __('admin.actions') }}</th>
                    </tr>
                </thead>
                <tbody id="table-body">
                    @forelse ($data as $k => $singleRow)
                    <tr class="cu-table-line">
                        <td>{{ @$singleRow->id }}</td>
                        <td>{{ @$singleRow->user->name }}</td>
                        <td>{{ @$singleRow->maid->passport_num }}</td>
                        <td>{{ @$singleRow->order_id ?? '--' }}</td>
                        <td>{{ __('travel_types.travel_types.'.@$singleRow->travel_type) }}</td>
                        <td>{{ __('travel_types.travel_with.'.@$singleRow->travel_with) }}</td>
                        <td>{{ @$singleRow->arrival_airport }}</td>
                        <td>{{ @$singleRow->arrival_date }}</td>
                        <td>{{ @$singleRow->arrival_time }}</td>
                        <td>{{ @$singleRow->flight_company }}</td>
                        <td>{{ @$singleRow->flight_number }}</td>
                        <td>{{ __('travel_types.status.'.@$singleRow->status) }}</td>
                        <td class="PrintHide">
                            <div class="d-flex justify-content-center">
                                <a class="btn btn-sm" href="{{ route('travel.control', [
                                    'id' => $singleRow->id,
                                    'userId' => $singleRow->user_id,
                                    'maidId' => $singleRow->maid_id,
                                    'orderId' => $singleRow->order_id
                                ]) }}">
                                    <li class="fa fa-edit"></li>
                                </a>
                                <form action="{{ route('travel.delete', $singleRow->id) }}" method="POST"
                                    id="dataDeleteForm-{{ $singleRow->id }}">
                                    @csrf
                                    @method('DELETE')
                                    <a class="btn btn-sm btnDeleteTrgt" data-id="{{ $singleRow->id }}" href="javascript:{}">
                                        <li class="fa fa-trash"></li>
                                    </a>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="13" class="text-center">{{ __('admin.no_data') }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="d-flex justify-content-center mt-4">
                <div class="pagination-wrapper">
                    {{ $data->appends(request()->query())->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>
    </div>

@else
    @include('components.no_permissions')
@endif

@endsection

@include('components.sweetalert')

@push('css')
<style>
    .pagination-wrapper {
        display: flex;
        justify-content: center;
        width: 100%;
    }
    .pagination {
        margin: 0;
        padding: 0;
    }
    .pagination li {
        display: inline-block;
        margin: 0 2px;
    }
    .pagination li a,
    .pagination li span {
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        color: #333;
        text-decoration: none;
    }
    .pagination li.active span {
        background-color: #007bff;
        border-color: #007bff;
        color: white;
    }
    .pagination li a:hover {
        background-color: #f8f9fa;
    }
</style>
@endpush

@push('js')
    <script>
        $(document).ready(function(){
            $('.PrintTable').on('click', function(e){
                e.preventDefault();

                // Temporarily remove the PrintHide columns
                var printHideElements = $('.PrintHide').remove();

                // Trigger the print dialog
                window.print();

                // Reattach the PrintHide columns after printing
                $(printHideElements).each(function(index, element) {
                    var parentRow = $(element).closest('tr');
                    $(parentRow).append(element);
                });
            });
        });
    </script>
@endpush
