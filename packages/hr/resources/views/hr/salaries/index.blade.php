@extends('click.layout.indexs.index')
@section("style")
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
@endsection
@section('page-title')
    {{__('hr::hr.salaries')}}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{__("finance.clients")}}</li>
@endsection
@section('content')

<form id="search-form" {{-- action="{{ route("salaries.index") }}" --}}>
    <div class="row">
        <div class="col-3 my-2">
            <label for="month_year" class="form-label"> {{ __("hr::hr.Choose the month") }}</label>
            <input class="form-control" type="month" name="month" value="{{ request()->month  }}" id="month" data-validation="required">
        </div>
        <div class="col-3 my-2">
            <label class="form-label">{{__('hr::hr.Select department')}}</label>
            {!! addUpButton(route('departments.create'),'department','' ,'title',true) !!}
            <select name="department" id="department" class="form-control select2">
                <option value="">{{ __("hr::hr.Choose") }}</option>
                @foreach ($departments as $item)
                    <option value="{{ $item->id }}">{{ $item->title }}</option>
                @endforeach
            </select>
        </div>
    </div>
</form>

<form class="from-submit-global" method="POST" action="{{route("salaries.store")}}">
    @csrf
    <div class="col-2 my-2 d-none">
        <label for="month_year" class="form-label">{{ __("hr::hr.Choose the month") }}</label>
        <input class="form-control" type="month" value="{{ request()->month  }}" name="month_year" data-validation="required">
    </div>
    <table class="table" id="table-body">
        <thead>
            <tr>
                <th>
                    <div class="col-12">
                        <input type="checkbox" name="checkbox" value="1">
                    </div>
                </th>
                <th>{{ __('hr::hr.SL') }}</th>
                <th>{{ __("hr::hr.name") }}</th>
                <th>{{ __("hr::hr.department") }}</th>
                <th>{{ __("hr::hr.salary") }}</th>
                <th>{{ __("hr::hr.Number of days of attendance") }}</th>
                <th>{{ __("hr::hr.allowances") }}</th>
                <th>{{ __("hr::hr.sanctions") }}</th>
                <th>{{ __("hr::hr.insurances") }}</th>
                <th>{{ __("hr::hr.Tax") }}</th>
                <th>{{ __("hr::hr.Delays") }}</th>
                <th>{{ __("hr::hr.Net") }}</th>
            </tr>
        </thead>
        <tbody id="search-results">
            @include('hr::hr.salaries.table')
        </tbody>
    </table>
    {{-- @if (Auth::guard('admin')->user()->hasPermission('hr::hr_employee_salary-create')) --}}
        <button type="submit" class="btn btn-primary">{{ __("hr::hr.Disbursement of salaries") }}</button>
    {{-- @endif --}}
</form>


@endsection
@section('js')
<script src="{{asset("admin")}}/assets/js/bundle/dataTables.bundle.js"></script>
<script>
    $(document).ready(function () {
        let columns = [
            {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            {"data": "name", orderable: false, searchable: false},
            {"data": "deparment_id", orderable: false, searchable: false},
            {"data": "salary", orderable: false, searchable: false},
            {"data": "days", orderable: false, searchable: false},
            {"data": "allowances_id", orderable: false, searchable: false},
            {"data": "sanction_id", orderable: false, searchable: false},
            {"data": "delay_id", orderable: false, searchable: false},
            {"data": "net", orderable: false, searchable: false},
        ];
        showDataTable("{{route("salaries.index")}}", columns);
    });
</script>

<script>
    document.querySelector('input[name="checkbox"]').addEventListener('change', function() {
        var checkboxes = document.querySelectorAll('input[name="checkbox[]"]');
        for (var i = 0; i < checkboxes.length; i++) {
            checkboxes[i].checked = this.checked;
        }
    });
</script>

<script>
    window.onload = function() {
        var input = document.getElementsByName('month')[0];
        input.addEventListener('change', function() {
            var selectedMonth = input.value;
            document.getElementsByName('month_year')[0].value = selectedMonth;
        });
    }

    $(document).ready(function() {
        $('#month, #department').on('change', function(event) {
            event.preventDefault();
            var month = $('#month').val();
            var department = $('#department').val();
            $.ajax({
                url: '{{ route("salaries.index") }}',
                type: 'GET',
                data: { month: month, department: department },
                success: function(response) {
                    $('body #search-results').html(response.table);
                },
                error: function(xhr, status, error) {
                    alert('حدث خطأ: ' + error);
                }
            });
        });
    });
</script>
@endsection
