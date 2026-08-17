@extends('admin.layout.indexs.index')


@section('page-title')
    {{ __('admin.shelter_workers') }}
@endsection

@section('content')
    @if (@AdminGuardHasPerm(\App\PermKeys::SHELTER_WORKERS_VIEW))
        <div class="row">
            <div class="col-md-9">
                <form id="" action="{{ route('shelter_workers') }}" method="GET" class="row">
                    <div class="col-md-3">
                        <input type="text" class="form-control" name="search" value="{{ @$search }}"
                            placeholder="{{ __('admin.search') }}">
                    </div>
                    <div class="col-md-3">
                        <select name="status" id="status" class="form-control cu-form-design select2">
                            <option value="all">@lang('admin.all')</option>
                            @forelse(__('shelter_worker_statuses.status') as $key => $value)
                                <option value="{{ $key }}" @if (@$status == $key) selected @endif>
                                    {{ $value }}</option>
                            @empty
                            @endforelse
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="wt" id="wt" class="form-control cu-form-design select2">
                            <option value="all">@lang('admin.all')</option>
                            @forelse(__('shelter_worker_statuses.worker_types') as $key => $value)
                                <option value="{{ $key }}" @if (@$worker_type == $key) selected @endif>
                                    {{ $value }}</option>
                            @empty
                            @endforelse
                        </select>
                    </div>

                    <div class="col-md-3 mt-1">
                        <button type="submit" class="btn btn-sm btn-primary">
                            @lang('admin.filter')
                        </button>
                        @if (@$search || @$status || @$worker_type)
                            <a href="{{ route('shelter_workers') }}" class="btn btn-sm btn-danger">@lang('admin.cancel_filters')</a>
                        @endif
                    </div>
                </form>
            </div>

            <div class="col-md-3 col-sm-12 d-flex justify-content-center justify-content-md-end align-items-center">
                @if (@AdminGuardHasAnyPerm([\App\PermKeys::SHELTER_WORKERS_CREATE, \App\PermKeys::SHELTER_WORKERS_CONTROL]))
                    <a href="{{ route('shelter_workers.control') }}" id="add-button" class="btn btn-success"
                        type="button">
                        @lang('admin.add')
                    </a>
                @endif
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

        <table id="tblToExcl" class="table table-striped table-row-bordered border table-rounded cu-table-adj">
            <thead>
                <tr class="fw-bold fs-6 text-gray-800">
                    <th>{{ __('admin.name') }}</th>
                    <th>{{ __('admin.city') }}</th>
                    <th>{{ __('admin.worker_name') }}</th>
                    <th>{{ __('admin.residence_num') }}</th>
                    <th>{{ __('admin.passport_num') }}</th>
                    <th>{{ __('admin.nationality_id') }}</th>
                    <th>{{ __('admin.shelter_status') }}</th>
                    <th>{{ __('admin.received_by') }}</th>
                    <th>{{ __('admin.received_by_other') }}</th>
                    <th>{{ __('admin.sh_status') }}</th>
                    <th>@lang('admin.sh_reserve_date')</th>
                    @if (@AdminGuardHasAnyPerm([\App\PermKeys::SHELTER_WORKERS_EDIT, \App\PermKeys::SHELTER_WORKERS_DELETE, \App\PermKeys::SHELTER_WORKERS_CONTROL]))
                        <th class="" style="width:10%">{{ __('admin.actions') }}</th>
                    @endif
                </tr>
            </thead>
            <tbody id="table-body">
                @forelse ($data as $k => $singleRow)
                    <tr class="cu-table-line">
                        <td>{{ @$singleRow->name }}</td>
                        <td>{{ @$singleRow->city }}</td>
                        <td>{{ @$singleRow->worker_name }}</td>
                        <td>{{ @$singleRow->residence_num }}</td>
                        <td>{{ @$singleRow->passport_num }}</td>
                        <td>{{ @getNationalityName(@$singleRow->nationality_id) }}</td>
                        <td><span
                                class="badge bg-primary">{{ __("shelter_worker_statuses.status.$singleRow->shelter_status") }}</span>
                        </td>
                        <td><span
                                class="badge bg-info">{{ __("shelter_worker_statuses.received_by.$singleRow->received_by") }}</span>
                        </td>
                        <td>{{ @$singleRow->received_by_other ?? '-' }}</td>
                        <td><span
                                class="badge bg-dark">{{ __("shelter_worker_statuses.worker_status.$singleRow->status") }}</span>
                        </td>
                        <td>{{ $singleRow->reserve_date ?? '--' }}</td>
                        @if (@AdminGuardHasAnyPerm([\App\PermKeys::SHELTER_WORKERS_EDIT, \App\PermKeys::SHELTER_WORKERS_DELETE, \App\PermKeys::SHELTER_WORKERS_CONTROL]))
                            <td class="PrintHide">
                                <div class="d-flex justify-content-center">
                                    <a class="btn btn-sm" href="{{ route('shelter_workers.show', $singleRow->id) }}">
                                        <li class="fa fa-eye"></li>
                                    </a>
                                    @if (@AdminGuardHasAnyPerm([\App\PermKeys::SHELTER_WORKERS_EDIT, \App\PermKeys::SHELTER_WORKERS_CONTROL]))
                                        <a class="btn btn-sm"
                                            href="{{ route('shelter_workers.update', $singleRow->id) . '/edit' }}">
                                            <li class="fa fa-edit"></li>
                                        </a>
                                    @endif
                                    @if (@AdminGuardHasAnyPerm([\App\PermKeys::SHELTER_WORKERS_DELETE, \App\PermKeys::SHELTER_WORKERS_CONTROL]))
                                        <form action="{{ route('shelter_workers.delete', $singleRow->id) }}" method="POST"
                                            id="dataDeleteForm-{{ $singleRow->id }}">
                                            @csrf
                                            @method('DELETE')
                                            <a class="btn btn-sm btnDeleteTrgt" data-id="{{ $singleRow->id }}"
                                                href="javascript:{}">
                                                حذف
                                            </a>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="text-center">{{ __('admin.no_data') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{ @$data->links() }}
    @else
        @include('components.no_permissions')
    @endif


@endsection

@include('components.sweetalert')
