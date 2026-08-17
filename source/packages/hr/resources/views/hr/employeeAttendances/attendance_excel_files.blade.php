@extends('click.layout.indexs.index')
@section("style")
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
@endsection
@section('page-title')
    {{__('hr::hr.employeeAttendances')}}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{__("finance.clients")}}</li>
@endsection
@section('style')
    <style>
        .dropify-font-upload:before,
        .dropify-wrapper .dropify-message span.file-icon:before {
            content: "\f382";
            font-weight: 100;
            color: #000;
            font-size: 26px;
        }

        .dropify-wrapper .dropify-message p {
            text-align: center;
            font-size: 15px;
        }

    </style>

    <style>
        #progressBar {
            height: 20px;
            margin-top: 10px;
        }

        .progress_per {
            font-size: 16px;
            font-weight: bold;
            color: #4CAF50;
            margin-left: 10px;
        }
    </style>

@endsection
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">

                    <div class="col-12 p-2">
                        <div class="form-group">
                            <label for="cv_file" class="my-2">
                                {{ __("hr::hr.Attach the attendance file") }} (.xlsx)
                                 <button type="button" class="btn btn-primary"
                                        onclick="window.location.href='{{ asset('hr-assets/attendance-example.xlsx') }}'">{{ __("hr::hr.Download form") }}
                                    {{ __("hr::hr.file") }}
                                </button>
{{--                                <button type="button" class="btn btn-primary"--}}
{{--                                id="toExcel">{{ __("hr::hr.Download form") }}--}}
{{--                                    {{ __("hr::hr.file") }}--}}
{{--                                </button>--}}
                            </label>
                            <input class="form-control dropify" type="file" id="fileUpload" accept=".xls,.xlsx">
                            <progress id="progress-bar" value="0" max="100" style="width: 100%;"></progress>
                        </div>
                    </div>
                    @php
                        use Carbon\Carbon;

                        $startOfMonth = Carbon::now()->startOfMonth();
                        $endOfMonth = Carbon::now()->endOfMonth();
                        $datesInMonth = [];

                        while ($startOfMonth <= $endOfMonth) {
                            $datesInMonth[] = $startOfMonth->format('m/d/Y');
                            $startOfMonth->addDay();
                        }
                    @endphp

                    <table class="table d-none" id="simpleTable4">
                        <thead>
                            <tr>
                                <th scope="col">employee_code</th>
                                <th scope="col">date</th>
                                <th scope="col">attendance_time</th>
                                <th scope="col">leave_time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($employees as $employee)
                                @foreach ($datesInMonth as $date)
                                    <tr>
                                        <th scope="row">{{ $employee->code }}</th>
                                        <td>{{ $date }}</td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>

                    <div class="col-sm-6">
                        <div class="text-end">
                            <button type="button" id="uploadExcel" class="btn btn-success">
                                {{ __("hr::hr.Upload file") }}
                                &nbsp
                                <i class="fa fa-save"></i>
                            </button>

                        </div>
                    </div> <!-- end col -->


                </div>
            </div>
        </div>
    </div>

@endsection

@section('js')
    {{-- <script type="text/javascript" src="{{asset('upload_exel')}}/jquery.min.js"></script> --}}
    <script type="text/javascript" src="{{asset('upload_exel')}}/xlsx.full.min.js"></script>
    <script src="{{asset("plugins")}}/exelJs/dist/tableToExcel.517b1af9.js"></script>
    <script>
        var button = document.querySelector('#toExcel');
        button.addEventListener('click', function (e) {
            var table = document.querySelector('#simpleTable4');
            TableToExcel.convert(table);
        });
    </script>

    <script>
        var selectedFile;

        document.getElementById("fileUpload").addEventListener("change", function (event) {
            selectedFile = event.target.files[0];
        });

        document.getElementById("uploadExcel").addEventListener("click", function () {
            /* valid_keys => Must be an array */
            let valid_keys = [
                'employee_code',
                'date',
                'attendance_time',
                'leave_time'
            ];
            let checkValid = uploadExcelFile(selectedFile, valid_keys);
        });

        function uploadExcelFile(selectedFile, valid_keys) {
            if (selectedFile) {
                var fileReader = new FileReader();
                fileReader.onload = function (event) {
                    var data = event.target.result;
                    var workbook = XLSX.read(data, { type: "binary" });
                    workbook.SheetNames.forEach(sheet => {
                        let rowObject = XLSX.utils.sheet_to_row_object_array(workbook.Sheets[sheet]);

                        // Check if data is empty
                        if (rowObject.length < 1) {
                            alert('تأكد من ادخال بيانات في الملف');
                            return false;
                        }

                        let valid_keys2 = Array.isArray(valid_keys) ? valid_keys : []; // Ensure valid keys are an array
                        let received_keys = Object.keys(rowObject[0]).map(key => key.trim()); // Get the keys from the first row and trim spaces

                        console.log("Expected Keys: ", valid_keys2);  // Debugging: Log expected keys
                        console.log("Received Keys: ", received_keys); // Debugging: Log received keys

                        let check_continue = true; // Use boolean for logic control

                        // // Validate keys
                        // valid_keys2.forEach(function (expected_key, index) {
                        //     let received_key = received_keys[index] || null; // Handle missing keys
                        //     if (received_key === null || expected_key !== received_key) {
                        //         alert('تأكد من مسمى العمود: ' + (received_key ? received_key : 'غير موجود'));
                        //         check_continue = false;
                        //     }
                        // });

                        // If key validation fails, stop the process
                        if (!check_continue) {
                            return false;
                        }

                        // Handle null or missing 'leave_time'
                        rowObject.forEach(row => {
                            row.leave_time = row.leave_time || null; // Set leave_time to null if missing
                        });

                        // Save the valid data through Ajax
                        saveExcelByAjax(rowObject);
                    });
                };
                fileReader.readAsBinaryString(selectedFile);
            }
        }
        function saveExcelByAjax(rowObject) {
            let excel_rows_length = rowObject.length;
            // console.log(rowObject);
            $.ajax({
                url: '{{ route('employeeExecelFile.store') }}',
                dataType: 'json',
                type: "POST",
                data: {
                    _token: '{{ @csrf_token() }}',
                    excel_rows_length: excel_rows_length,
                    excel_rows: JSON.stringify(rowObject),
                },
                beforeSend: function () {
                    // Show loading indicator or perform any pre-request actions
                    animateProgressBar(excel_rows_length);
                    $('.loader-ajax').show()
                },
                success: function (data) {
                    handleSuccessResponce(data);

                   location.reload();
                }, error(response) {
                    console.log(response)
                }
            });
        }

        function animateProgressBar(rows) {
            let progressBar = $("#progress-bar");
            let targetValue = 85;
            let duration = rows * 1000;
            progressBar.animate({value: targetValue}, {
                duration: duration,
                step: function (now) {
                    progressBar.attr("value", now);
                },
                complete: function () {
                    // Animation complete
                    // Perform any additional actions here
                }
            });
        }


    </script>

@endsection
