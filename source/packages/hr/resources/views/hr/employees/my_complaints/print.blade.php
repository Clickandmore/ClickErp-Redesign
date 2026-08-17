<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bootstrap Table Example</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="direction: rtl; text-align: start">

<div class="container">

    <div id="table-details2 " class="mt-3">

        <h3>
            {{ __("hr.complaint") }}
        </h3>
        <table class="table table-bordered table-striped" id="simpleTable2">
            <tbody class="thead-inverse">
            <tr>
                <th scope="row">{{ __("hr.employees") }}</th>
                <td>{{ $obj->employee->name }}</td>
            </tr>
            <tr>
                <th scope="row">{{ __("hr.date") }}</th>
                <td>{{ $obj->date}}</td>
            </tr>
            <tr>
                <th scope="row">{{ __("hr.notes") }}</th>
                <td>{{ $obj->description }}</td>
            </tr>


            </tbody>
        </table>


        <h3>
            {{ __("hr.replies") }}
        </h3>
        <table class="table table-bordered table-striped" id="simpleTable2">
            <thead class="thead-inverse">
                <tr>
                    <th>{{ __('finance.SL') }}</th>
                    <th>{{ __("hr.added_by") }}</th>
                    <th>{{ __("hr.notes") }} </th>
                    <th> {{ __("hr.date") }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach($obj->replies as $reply)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $reply->user->name }}</td>
                    <td>{{ $reply->text }}</td>
                    <td>{{ $reply->date }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>


    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
    window.onload = function () {
        window.print();
    };
</script>
</body>
</html>
