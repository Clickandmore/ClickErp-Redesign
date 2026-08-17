@extends('admin.layout.indexs.index')


@push('css')
@endpush

@section('content')

@if (@AdminGuardHasPerm(\App\PermKeys::FULL_CONTRACT))

    <div class="mb-4">
        <h4>@lang('ingaz.viewFullOrderContract')</h4>
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

    <form action="{{ route('createFullOrderContract') }}" class="row" method="POST">
        @csrf

        <div>
            <h4>@lang('ingaz.client')</h4>
        </div>

        <div class="col-12 mb-3">
            <label class="form-label">{{helperTrans("ingaz.client_type")}}</label>
            <select name="client_type" id="client_type" class="form-select form-control">
                <option value="ex">@lang('ingaz.ex_client')</option>
                <option value="new">@lang('ingaz.new_client')</option>
            </select>
        </div>

        <div class="row exClientSection">
            <div class="col-4">
                <label class="form-label">{{helperTrans("ingaz.client_id")}}</label>
                <select name="client_id" id="client_id" class="form-select form-control select2">
                    @forelse (@$clientsList ?? [] as $id => $name)
                        <option option value="{{ @$id }}" @if(@$data->client_id == $id) selected @endif>{{@$name}}</option>
                    @empty
                    @endforelse
                </select>
            </div>
        </div>

        <div class="row newClientSection">

            <div class="col-4">
                <label class="form-label">{{helperTrans("ingaz.client_name")}}</label>
                <input type="text" id="client_name" name="client_name" value="{{ @$data->client_name }}"
                    class="form-control">
            </div>

            <div class="col-4">
                <label class="form-label">{{helperTrans("ingaz.phone_code")}}</label>
                <input type="number" id="phone_code" name="phone_code" value="{{ @$data->phone_code }}"
                    class="form-control">
            </div>

            <div class="col-4">
                <label class="form-label">{{helperTrans("ingaz.phone")}}</label>
                <input type="number" id="phone" name="phone" value="{{ @$data->phone }}" class="form-control">
            </div>

            <div class="col-4">
                <label class="form-label">{{helperTrans("ingaz.phone2")}}</label>
                <input type="number" id="phone2" name="phone2" value="{{ @$data->phone2 }}" class="form-control">
            </div>

            <div class="col-4">
                <label class="form-label">{{helperTrans("ingaz.password")}}</label>
                <input type="password" id="password" name="password" value="{{ @$data->password }}" class="form-control">
            </div>

            <div class="col-4">
                <label class="form-label">{{helperTrans("ingaz.nationality_id")}}</label>
                <select name="nationality_id" id="nationality_id" class="form-select form-control select2">
                    <option value="">@lang('ingaz.none')</option>
                    @forelse (@$nationalitiesList ?? [] as $id => $nat)
                        <option option value="{{ @$nat->id }}" @if(@$data->nationality_id == $nat->id) selected
                            @endif>{{@$nat->title}}</option>
                    @empty
                    @endforelse
                </select>
            </div>

            <div class="col-4">
                <label class="form-label">{{helperTrans("ingaz.email")}}</label>
                <input type="email" id="email" name="email" value="{{ @$data->email }}" class="form-control">
            </div>

            <div class="col-4">
                <label class="form-label">{{helperTrans("ingaz.national_num")}}</label>
                <input type="text" id="national_num" name="national_num" value="{{ @$data->national_num }}"
                    class="form-control">
            </div>

            <div class="col-4">
                <label class="form-label">{{helperTrans("ingaz.birthdate")}}</label>
                <input type="date" id="birthdate" name="birthdate" value="{{ @$data->birthdate }}" class="form-control">
            </div>

            <div class="col-4">
                <label class="form-label">{{helperTrans("ingaz.address")}}</label>
                <input type="text" id="address" name="address" value="{{ @$data->address }}" class="form-control">
            </div>

            <div class="col-4">
                <label class="form-label">{{helperTrans("ingaz.city")}}</label>
                <input type="text" id="city" name="city" value="{{ @$data->city }}" class="form-control">
            </div>

            <div class="col-4">
                <label class="form-label">{{helperTrans("ingaz.rooms_num")}}</label>
                <input type="text" id="rooms_num" name="rooms_num" value="{{ @$data->rooms_num }}" class="form-control">
            </div>

            <div class="col-4">
                <label class="form-label">{{helperTrans("ingaz.family_num")}}</label>
                <input type="text" id="family_num" name="family_num" value="{{ @$data->family_num }}" class="form-control">
            </div>

            <div class="col-4">
                <label class="form-label">{{helperTrans("ingaz.children")}}</label>
                <input type="text" id="children" name="children" value="{{ @$data->children }}" class="form-control">
            </div>

            <div class="col-4">
                <label class="form-label">{{helperTrans("ingaz.job")}}</label>
                <input type="text" id="job" name="job" value="{{ @$data->job }}" class="form-control">
            </div>

        </div>

        @push('js')
        <!-- Include jQuery -->
        {{-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> --}}
        <!-- Include Select2 -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

        <script>
            $(document).ready(function() {
                // Initialize Select2
                $('.select2').select2();

                // Initial check on page load
                checkClientType();

                // Function to show/hide client sections based on selected client type
                $('#client_type').change(function() {
                    checkClientType();
                });

                function checkClientType() {
                    var selectedType = $('#client_type').val();

                    if (selectedType === 'ex') {
                        $('.exClientSection').show();
                        $('.newClientSection').hide();
                        removeRequiredAttributes();
                    } else if (selectedType === 'new') {
                        $('.exClientSection').hide();
                        $('.newClientSection').show();
                        addRequiredAttributes();
                    } else {
                        // Default behavior if none selected
                        $('.exClientSection').hide();
                        $('.newClientSection').hide();
                        removeRequiredAttributes();
                    }
                }

                function addRequiredAttributes() {
                    $('#client_name').attr('required', true);
                    $('#phone_code').attr('required', true);
                    $('#phone').attr('required', true);
                    $('#email').attr('required', true);
                    $('#password').attr('required', true);
                }

                function removeRequiredAttributes() {
                    $('#client_name').removeAttr('required');
                    $('#phone_code').removeAttr('required');
                    $('#phone').removeAttr('required');
                    $('#email').removeAttr('required');
                    $('#password').removeAttr('required');
                }
            });
        </script>

        @endpush


        <hr class="my-3">

        <div>
            <h4>@lang('ingaz.worker')</h4>
        </div>

        <div class="col-4">
            <label class="form-label">{{helperTrans("ingaz.admin_id")}}</label>
            <select name="admin_id" id="admin_id" class="form-select form-control select2">
                @forelse (@$adminsList ?? [] as $id => $name)
                <option option value="{{ @$id }}" @if(@$data->admin_id == $id) selected @endif>{{@$name}}</option>
                @empty
                @endforelse
            </select>
        </div>

        <div class="col-4">
            <label class="form-label">{{helperTrans("ingaz.maid_id")}}</label>
            <select name="maid_id" id="maid_id" class="form-select form-control select2">
                @forelse (@$maidsList ?? [] as $id => $name)
                <option option value="{{ @$id }}" @if(@$data->maid_id == $id) selected @endif>{{@$name}}</option>
                @empty
                @endforelse
            </select>
        </div>


        <hr class="my-3">
        <div>
            <h4>@lang('ingaz.contract_info')</h4>
        </div>
        <div class="col-6">
            <label class="form-label">{{helperTrans("ingaz.contract duration")}}</label>
            <input type="number" id="contract_duration" name="contract_duration" value="{{ @$info->contract_duration }}"
                class="form-control" required>
        </div>

        <div class="col-6">
            <label class="form-label">{{helperTrans("ingaz.contract date")}}</label>
            <input type="date" id="contract_date" name="contract_date" value="{{ @$info->contract_date }}"
                class="form-control" required>
        </div>

        <div class="col-6">
            <label class="form-label">{{helperTrans("ingaz.monthly_cost")}}</label>
            <input type="number" id="monthly_cost" name="monthly_cost"
                value="{{ @$info->monthly_cost }}" class="form-control" required>
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
        </div> --}}

        {{-- <div class="col-6">
            <label class="form-label">{{helperTrans("ingaz.visa number")}}</label>
            <input type="text" id="visa_number" name="visa_number" value="{{ @$info->visa_number }}" class="form-control"
                required>
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

@else
    @include('components.no_permissions')
@endif


@endsection

@push('js')
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script>
    $(document).ready(function () {
            $('.select2').select2();
        });
</script>
@endpush
