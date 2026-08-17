@extends('admin.layout.indexs.index')


@push('css')
@endpush

@section('content')

<div class="mb-4">
    <h5>@lang('ingaz.contract_edit')</h5>
</div>

<div class="container my-3">
    <!-- Success Message -->
    @if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <!-- Error Messages -->
    @if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul>
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif
</div>

<form action="{{ route('updateContractInfo') }}" class="row" method="POST">
    @csrf

    <input type="hidden" name="contract_id" value="{{ @$data->id }}">

    <div class="col-6">
        <label class="form-label">{{helperTrans("ingaz.contract duration")}}</label>
        <input type="number" id="contract_duration" name="contract_duration"
            value="{{ @$info->contract_duration }}" class="form-control" required>
    </div>

    <div class="col-6">
        <label class="form-label">{{helperTrans("ingaz.monthly_cost")}}</label>
        <input type="number" id="monthly_cost" name="monthly_cost"
            value="{{ @$info->monthly_cost }}" class="form-control" required>
    </div>

    <div class="col-6">
        <label class="form-label">{{helperTrans("ingaz.contract date")}}</label>
        <input type="date" id="contract_date" name="contract_date" value="{{ @$info->contract_date }}"
            class="form-control" required>
    </div>

    {{-- <div class="col-6">
        <label class="form-label">{{helperTrans("ingaz.msaned number")}}</label>
        <input type="text" id="msaned_number" name="msaned_number" value="{{ @$info->msaned_number }}"
            class="form-control" required>
    </div>

    <div class="col-6">
        <label class="form-label">{{helperTrans("ingaz.tafwed number")}}</label>
        <input type="text" id="tafwed_number" name="tafwed_number" value="{{ @$info->tafwed_number }}"
            class="form-control" required>
    </div>

    <div class="col-6">
        <label class="form-label">{{helperTrans("ingaz.visa number")}}</label>
        <input type="text" id="visa_number" name="visa_number" value="{{ @$info->visa_number }}"
            class="form-control" required>
    </div>

    <div class="col-6">
        <label class="form-label">{{helperTrans("ingaz.visa_date_is")}}</label>
        <input type="date" id="visa_milad_date" name="visa_milad_date" value="{{ @$info->visa_milad_date }}"
            class="form-control" required>
    </div> --}}

    <div class="my-4">
        <button type="submit" class="btn btn-success">@lang('ingaz.submit')</button>
    </div>

</form>

@endsection

@push('js')

@endpush
