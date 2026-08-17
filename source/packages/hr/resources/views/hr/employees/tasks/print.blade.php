<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الفيصلي | المهام</title>
    <!-- Bootstrap CSS -->

    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">

</head>
<body style="direction: rtl; text-align: start">

<div class="container">

    <div id="table-details2" class="mt-3">
        <table class="table table-bordered table-striped" id="simpleTable2">
            <thead>
            <tr>
                <td>@lang('fleet.name')</td>
                <td>@lang('fleet.description')</td>
                <td>@lang('fleet.exp_date')</td>
            </tr>
            </thead>
            <tbody>
            @foreach($tasks as $task)
                <tr class="item-row" id="creditor_append">
                    <td>
                        {{ $task->title }}
                    </td>
                    <td>
                        {{ $task->description }}
                    </td>
                    <td>
                        {{ $task->end_date }}
                    </td>
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
