@extends('admin.layout.indexs.index')
@section('page-title')
    {{__('countries.manage_countries')}}
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0">{{__('countries.countries_list')}}</h4>
                <a href="{{ route('countries.create') }}" class="btn btn-primary">
                    <i class="fa fa-plus"></i> {{__('countries.add_new')}}
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                    <tr>
                        <th>{{__('countries.name')}}</th>
                        <th>{{__('countries.code')}}</th>
                        <th>{{__('countries.phone_code')}}</th>
                        <th>{{__('countries.currency')}}</th>
                        <th>{{__('countries.status')}}</th>
                        <th>{{__('countries.sort_order')}}</th>
                        <th>{{__('countries.actions')}}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($countries as $country)
                        <tr>
                            <td>{{ $country->title }}</td>
                            <td>{{ $country->code }}</td>
                            <td>{{ $country->phone_code }}</td>
                            <td>{{ $country->currency_symbol }} ({{ $country->currency }})</td>
                            <td>
                                <form action="{{ route('countries.toggle-status', $country) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm {{ $country->is_active ? 'btn-success' : 'btn-danger' }}">
                                        {{ $country->is_active ? __('countries.active') : __('countries.inactive') }}
                                    </button>
                                </form>
                            </td>
                            <td>{{ $country->sort_order }}</td>
                            <td>
                                <a href="{{ route('countries.edit', $country) }}" class="btn btn-sm btn-primary">
                                    <i class="fa fa-edit"></i>
                                </a>
                                <form action="{{ route('countries.destroy', $country) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('{{__('countries.confirm_delete')}}')">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
