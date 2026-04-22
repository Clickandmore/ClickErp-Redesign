@extends('admin.layout.indexs.index')


@section('page-title')
{{ __('admin.shelter_workers') }}
@endsection


@section('css')
@endsection


@section('content')
@if (
    (!@$data && @AdminGuardHasAnyPerm([\App\PermKeys::SHELTER_WORKERS_CREATE, \App\PermKeys::SHELTER_WORKERS_CONTROL])) ||
    (@$data && @AdminGuardHasAnyPerm([\App\PermKeys::SHELTER_WORKERS_EDIT, \App\PermKeys::SHELTER_WORKERS_CONTROL]))
)

    <h1>
        @if (@$data)
        {{ __('admin.shelter_workers-edit') }}
        @else
        {{ __('admin.shelter_workers-add') }}
        @endif
    </h1>
    <hr>

    <div class="">
        @if (session('error'))
        <div class="alert alert-danger mt-5 card-body">
            {{ session('error') }}
        </div>
        @endif

        @if (session('success'))
        <div class="alert alert-success mt-5 card-body">
            {{ session('success') }}
        </div>
        @endif

        @if (session('status'))
        <div class="alert alert-warning mt-5 card-body">
            {{ session('status') }}
        </div>
        @endif

        @if ($errors->any())
        <div class="alert alert-danger card-body">
            <ul>
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif
    </div>

    <form class="row" method="POST" @if (@$data) action="{{ route('shelter_workers.update', $data->id) }}" @else
        action='{{ route('shelter_workers.store') }}' @endif enctype="multipart/form-data">

        @if (@$data)
        {{-- @method('PATCH') --}}
        @endif
        @csrf

        <input type="hidden" name="id" value="{{ @$data->id }}">

        <div class="mb-3 row">
            <label for="name" class="col-sm-3 col-form-label">{{ __('admin.name') }}</label>
            <div class="col-sm-5">
                <input type="text" class="form-control cu-form-design" id="name" name="name"
                    placeholder="{{ __('admin.name') }}" @if (@$data) value="{{ @$data->name }}" @endif>
                <div>
                    @error('name')
                    <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="mb-3 row">
            <label for="city" class="col-sm-3 col-form-label">{{ __('admin.city') }}</label>
            <div class="col-sm-5">
                <input type="text" class="form-control cu-form-design" id="city" name="city"
                    placeholder="{{ __('admin.city') }}" @if (@$data) value="{{ @$data->city }}" @endif>
                <div>
                    @error('city')
                    <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="mb-3 row">
            <label for="worker_name" class="col-sm-3 col-form-label">{{ __('admin.worker_name') }}</label>
            <div class="col-sm-5">
                <input type="text" class="form-control cu-form-design" id="worker_name" name="worker_name"
                    placeholder="{{ __('admin.worker_name') }}" @if (@$data) value="{{ @$data->worker_name }}" @endif>
                <div>
                    @error('worker_name')
                    <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="mb-3 row">
            <label for="residence_num" class="col-sm-3 col-form-label">{{ __('admin.residence_num') }}</label>
            <div class="col-sm-5">
                <input type="text" class="form-control cu-form-design" id="residence_num" name="residence_num"
                    placeholder="{{ __('admin.residence_num') }}" @if (@$data) value="{{ @$data->residence_num }}" @endif>
                <div>
                    @error('residence_num')
                    <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="mb-3 row">
            <label for="passport_num" class="col-sm-3 col-form-label">{{ __('admin.passport_num') }}</label>
            <div class="col-sm-5">
                <input type="text" class="form-control cu-form-design" id="passport_num" name="passport_num"
                    placeholder="{{ __('admin.passport_num') }}" @if (@$data) value="{{ @$data->passport_num }}" @endif>
                <div>
                    @error('passport_num')
                    <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="mb-3 row">
            <label for="nationality_id" class="col-sm-3 col-form-label">{{ __('admin.nationality_id') }}</label>
            <div class="col-sm-5">
                <select name="nationality_id" id="nationality_id" class="form-select form-control">
                    <option value="">@lang('ingaz.none')</option>
                    @forelse (@$nationalitiesList ?? [] as $id => $nat)
                    <option option value="{{ @$nat->id }}" @if(@$data->nationality_id == $nat->id) selected
                        @endif>{{@$nat->title}}</option>
                    @empty
                    @endforelse
                </select>
                <div>
                    @error('nationality_id')
                    <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="mb-3 row">
            <label for="shelter_status" class="col-sm-3 col-form-label">{{ __('admin.shelter_status') }}</label>
            <div class="col-sm-5">
                <select name="shelter_status" id="shelter_status" class="form-control cu-form-design">
                    @forelse (__('shelter_worker_statuses.status') ?? [] as $k => $name)
                    <option value="{{$k}}" @if (@$data->shelter_status == $k) selected @endif>
                        {{ $name }}
                    </option>
                    @empty
                    @endforelse
                </select>
                <div>
                    @error('shelter_status')
                    <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="mb-3 row">
            <label for="worker_type" class="col-sm-3 col-form-label">{{ __('admin.worker_type') }}</label>
            <div class="col-sm-5">
                <select name="worker_type" id="worker_type" class="form-control cu-form-design">
                    @forelse (__('shelter_worker_statuses.worker_types') ?? [] as $k => $name)
                    <option value="{{$k}}" @if (@$data->worker_type == $k) selected @endif>
                        {{ $name }}
                    </option>
                    @empty
                    @endforelse
                </select>
                <div>
                    @error('worker_type')
                    <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="mb-3 row">
            <label for="received_by" class="col-sm-3 col-form-label">{{ __('admin.received_by') }}</label>
            <div class="col-sm-5">
                <select name="received_by" id="received_by" class="form-control cu-form-design">
                    @forelse (__('shelter_worker_statuses.received_by') ?? [] as $k => $name)
                    <option value="{{$k}}" @if (@$data->received_by == $k) selected @endif>
                        {{ $name }}
                    </option>
                    @empty
                    @endforelse
                </select>
                <div>
                    @error('received_by')
                    <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="mb-3 row">
            <label for="received_by_other" class="col-sm-3 col-form-label">{{ __('admin.received_by_other') }}</label>
            <div class="col-sm-5">
                <input type="text" class="form-control cu-form-design" id="received_by_other" name="received_by_other"
                    placeholder="{{ __('admin.received_by_other') }}" @if (@$data) value="{{ @$data->received_by_other }}"
                    @endif>
                <div>
                    @error('received_by_other')
                    <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="mb-3 row">
            <label for="status" class="col-sm-3 col-form-label">{{ __('admin.sh_status') }}</label>
            <div class="col-sm-5">
                <select name="status" id="status" class="form-control cu-form-design">
                    @forelse (__('shelter_worker_statuses.worker_status') ?? [] as $k => $name)
                    <option value="{{$k}}" @if (@$data->status == $k) selected @endif>
                        {{ $name }}
                    </option>
                    @empty
                    @endforelse
                </select>
                <div>
                    @error('status')
                    <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <hr>

        <div class="mb-3 row">
            <label for="travel_doc" class="col-sm-3 col-form-label">{{ __('admin.travel_doc') }}</label>
            <div class="col-sm-5">
                <input type="file" class="form-control cu-form-design" id="travel_doc" name="travel_doc">
                <div>
                    @error('travel_doc')
                    <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="mb-3 row">
            <label for="travel_doc_notes" class="col-sm-3 col-form-label">{{ __('admin.travel_doc_notes') }}</label>
            <div class="col-sm-5">
                <textarea class="form-control cu-form-design" id="travel_doc_notes" name="travel_doc_notes"
                    placeholder="{{ __('admin.travel_doc_notes') }}">{{ old('travel_doc_notes', @$data->travel_doc_notes) }}</textarea>
                <div>
                    @error('travel_doc_notes')
                    <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="mb-3 row">
            <label for="settlement" class="col-sm-3 col-form-label">{{ __('admin.settlement') }}</label>
            <div class="col-sm-5">
                <input type="file" class="form-control cu-form-design" id="settlement" name="settlement">
                <div>
                    @error('settlement')
                    <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="mb-3 row">
            <label for="settlement_notes" class="col-sm-3 col-form-label">{{ __('admin.settlement_notes') }}</label>
            <div class="col-sm-5">
                <textarea class="form-control cu-form-design" id="settlement_notes" name="settlement_notes"
                    placeholder="{{ __('admin.settlement_notes') }}">{{ old('settlement_notes', @$data->settlement_notes) }}</textarea>
                <div>
                    @error('settlement_notes')
                    <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="mb-3 row">
            <label for="graduation" class="col-sm-3 col-form-label">{{ __('admin.graduation') }}</label>
            <div class="col-sm-5">
                <input type="file" class="form-control cu-form-design" id="graduation" name="graduation">
                <div>
                    @error('graduation')
                    <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="mb-3 row">
            <label for="graduation_notes" class="col-sm-3 col-form-label">{{ __('admin.graduation_notes') }}</label>
            <div class="col-sm-5">
                <textarea class="form-control cu-form-design" id="graduation_notes" name="graduation_notes"
                    placeholder="{{ __('admin.graduation_notes') }}">{{ old('graduation_notes', @$data->graduation_notes) }}</textarea>
                <div>
                    @error('graduation_notes')
                    <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="mb-3 row">
            <label for="reserve" class="col-sm-3 col-form-label">{{ __('admin.reserve') }}</label>
            <div class="col-sm-5">
                <input type="file" class="form-control cu-form-design" id="reserve" name="reserve">
                <div>
                    @error('reserve')
                    <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="mb-3 row">
            <label for="reserve_date" class="col-sm-3 col-form-label">{{ __('admin.reserve_date') }}</label>
            <div class="col-sm-5">
                <input type="date" class="form-control" id="reserve_date" name="reserve_date" value="{{ old('reserve_date', @$data->reserve_date) }}">
                <div>
                    @error('reserve_date')
                    <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="mb-3 row">
            <label for="reserve_notes" class="col-sm-3 col-form-label">{{ __('admin.reserve_notes') }}</label>
            <div class="col-sm-5">
                <textarea class="form-control cu-form-design" id="reserve_notes" name="reserve_notes"
                    placeholder="{{ __('admin.reserve_notes') }}">{{ old('reserve_notes', @$data->reserve_notes) }}</textarea>
                <div>
                    @error('reserve_notes')
                    <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="mb-3 row">
            <label for="travel" class="col-sm-3 col-form-label">{{ __('admin.travel2') }}</label>
            <div class="col-sm-5">
                <input type="file" class="form-control cu-form-design" id="travel" name="travel">
                <div>
                    @error('travel')
                    <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="mb-3 row">
            <label for="travel_notes" class="col-sm-3 col-form-label">{{ __('admin.travel_notes') }}</label>
            <div class="col-sm-5">
                <textarea class="form-control cu-form-design" id="travel_notes" name="travel_notes"
                    placeholder="{{ __('admin.travel_notes') }}">{{ old('travel_notes', @$data->travel_notes) }}</textarea>
                <div>
                    @error('travel_notes')
                    <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>



        <div class="mb-3 row">
            <div class="mt-10 col-sm-12 text-end">
                <a href="{{ route('shelter_workers') }}" class="btn btn-danger">{{ __('admin.back') }}</a>
                <button type="submit" class="btn btn-success">{{ __('admin.confirm') }}</button>
            </div>
        </div>

    </form>

@else
    @include('components.no_permissions')
@endif

@endsection


@push('js')
{{-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> --}}
<script>
    $(document).ready(function() {
        // Function to toggle the visibility of the received_by_other input
        function toggleReceivedByOther() {
            if ($('#received_by').val() === 'other') {
                $('#received_by_other').closest('.row').show();
            } else {
                $('#received_by_other').closest('.row').hide();
            }
        }

        // Initial call to set the correct visibility on page load
        toggleReceivedByOther();

        // Event listener to toggle visibility when the select value changes
        $('#received_by').change(function() {
            toggleReceivedByOther();
        });
    });
</script>
@endpush
