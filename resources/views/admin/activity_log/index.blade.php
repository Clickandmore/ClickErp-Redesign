@extends('admin.layout.indexs.index')

@section('content')

{{-- @if (Auth::user()->can(\App\PermKeys::ACTIVITY_VIEW)) --}}
@include('components.session-messages')

<div class="row">
    <div class="text-center col-md-12 table-responsive">
        <table class="table border table-striped table-row-bordered table-rounded cu-table-adj">
            <thead>
                <tr class="text-gray-800 fw-bold fs-6">
                    <th>{{ __('admin.name') }}</th>
                    <th>{{ __('admin.log_action') }}</th>
                    <th>{{ __('admin.model_name') }}</th>
                    <th>{{ __('admin.last_modified') }}</th>

                    {{-- <th>{{ __('admin.log_data') }}</th> --}}
                    {{-- <th style="width:10%">{{ __('admin.actions') }}</th> --}}
                </tr>
            </thead>
            <tbody id="table-body">
                @forelse ($data as $k => $singleRow)
                    <tr class="cu-table-line">
                        <td>{{ @$singleRow->causer->name }}</td>
                        <td>
                            @if($singleRow->description == 'created')
                                <span class="badge bg-success">{{ $singleRow->description }}</span>
                            @elseif($singleRow->description == 'deleted')
                                <span class="badge bg-danger">{{ $singleRow->description }}</span>
                            @elseif($singleRow->description == 'updated')
                                <span class="badge bg-warning">{{ $singleRow->description }}</span>
                            @else
                                {{ $singleRow->description }}
                            @endif
                        </td>
                        <td>@lang("models_names.$singleRow->subject_type")</td>
                        <td>{{ @$singleRow->created_at->diffForHumans() }}</td>

                        {{-- <td>{{ @$singleRow->properties }}</td> --}}
{{--                        @if (@AdminGuardHasPerm(\App\PermKeys::REC_CONTRACTS_EDIT))--}}
{{--                        <td>--}}
{{--                            <div class="d-flex justify-content-center">--}}
{{--                                <a class="btn btn-sm" href="{{ route('dash.contracts.update', $singleRow->id) . '/edit' }}">--}}
{{--                                    <img height="16" src="{{ asset('admin/custom/actions/edit.svg') }}" alt="Edit">--}}
{{--                                </a>--}}
{{--                            </div>--}}
{{--                        </td>--}}
{{--                        @endif--}}
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center">{{ __('admin.no_data') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($data->hasPages())
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    {{-- زر الصفحة السابقة --}}
                    @if($data->onFirstPage())
                        <li class="page-item disabled"><span class="page-link">&laquo;</span></li>
                    @else
                        <li class="page-item"><a class="page-link" href="{{ $data->previousPageUrl() }}" rel="prev">&laquo;</a></li>
                    @endif

                    {{-- أرقام الصفحات --}}
                    @foreach ($data->getUrlRange(1, $data->lastPage()) as $page => $url)
                        @if ($page == $data->currentPage())
                            <li class="page-item active"><span class="page-link">{{ $page }}</span></li>
                        @else
                            <li class="page-item"><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach

                    {{-- زر الصفحة التالية --}}
                    @if($data->hasMorePages())
                        <li class="page-item"><a class="page-link" href="{{ $data->nextPageUrl() }}" rel="next">&raquo;</a></li>
                    @else
                        <li class="page-item disabled"><span class="page-link">&raquo;</span></li>
                    @endif
                </ul>
            </nav>
        @endif


    </div>
</div>

{{-- @else
@include('components.no_permissions')
@endif --}}

@endsection


@include('components.sweetalert')
