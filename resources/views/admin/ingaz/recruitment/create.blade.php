@extends('admin.layout.indexs.index')

@section('title', __('ingaz.create_recruitment'))

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ __('ingaz.create_recruitment_request') }}</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.recruitment.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="client_id" value="{{ $client->id }}">
                        <input type="hidden" name="maid_id" value="{{ $maid->id }}">

                        <!-- Selected Maid Details -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <img src="{{ $maid->photo_url ?? asset('images/no-image.png') }}"
                                     class="img-fluid rounded"
                                     alt="{{ $maid->name }}">
                            </div>
                            <div class="col-md-9">
                                <h4>{{ $maid->name }}</h4>
                                <div class="row">
                                    <div class="col-md-4">
                                        <p><strong>{{ __('ingaz.nationality') }}:</strong> {{ $maid->nationality->sub_title ?? '' }}</p>
                                        <p><strong>{{ __('ingaz.religion') }}:</strong> {{ $maid->religion->title ?? '' }}</p>
                                    </div>
                                    <div class="col-md-4">
                                        <p><strong>{{ __('ingaz.passport_num') }}:</strong> {{ $maid->passport_num }}</p>
                                        <p><strong>{{ __('ingaz.experience_years') }}:</strong> {{ $maid->experience_years }}</p>
                                    </div>
                                    <div class="col-md-4">
                                        <p><strong>{{ __('ingaz.salary') }}:</strong> {{ $maid->salary }}</p>
                                        <p><strong>{{ __('ingaz.job_types') }}:</strong> {{ $maid->jobTypes->pluck('title')->implode(', ') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Employee Selection (for admin only) -->
                        @if(Auth::guard('admin')->user()->hasRole('admin'))
                        <div class="form-group">
                            <label for="employee_id">{{ __('ingaz.select_employee') }}</label>
                            <select name="employee_id" id="employee_id" class="form-control select2" required>
                                <option value="">{{ __('ingaz.select_employee') }}</option>
                                @foreach($employees as $employee)
                                    <option value="{{ $employee->id }}" {{ $selectedEmployeeId == $employee->id ? 'selected' : '' }}>
                                        {{ $employee->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        <!-- Job Details -->
                        <div class="form-group">
                            <label for="job_title">{{ __('ingaz.job_title') }}</label>
                            <input type="text" name="job_title" id="job_title" class="form-control" value="{{ old('job_title') }}" required>
                        </div>

                        <div class="form-group">
                            <label for="nationality">{{ __('ingaz.nationality') }}</label>
                            <input type="text" name="nationality" id="nationality" class="form-control" value="{{ $maid->nationality->sub_title ?? old('nationality') }}" required>
                        </div>

                        <div class="form-group">
                            <label for="experience_years">{{ __('ingaz.experience_years') }}</label>
                            <input type="number" name="experience_years" id="experience_years" class="form-control" value="{{ $maid->experience_years ?? old('experience_years') }}" required>
                        </div>

                        <div class="form-group">
                            <label for="salary">{{ __('ingaz.salary') }}</label>
                            <input type="number" name="salary" id="salary" class="form-control" value="{{ $maid->salary ?? old('salary') }}" required>
                        </div>

                        <div class="form-group">
                            <label for="notes">{{ __('ingaz.notes') }}</label>
                            <textarea name="notes" id="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">{{ __('ingaz.create') }}</button>
                            <a href="{{ route('recruitment.select.maid', $client->id) }}" class="btn btn-secondary">{{ __('ingaz.back') }}</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('.select2').select2();
    });
</script>
@endpush
