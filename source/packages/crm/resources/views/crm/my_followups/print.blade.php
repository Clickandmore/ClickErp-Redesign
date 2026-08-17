<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{$bladeTitle}}</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="direction: rtl; text-align: start">

<div class="container">
    @include('print-header')
    <div id="table-details2 " class="mt-3">


        <table class="table table-bordered table-striped rounded-0" id="simpleTable2">

            <h3 class="card-title">{{$bladeTitle}} &nbsp;</h3>
            <tbody>
            <tr>
                <td >{{__('crm::crm.client')}}</td>
                <td >{{ $obj->client->name }}</td>
            </tr>
             <tr>
                <td >{{__('crm::crm.user')}}</td>
                <td >{{ $obj->user->name }}</td>
            </tr>
             <tr>
                <td >{{__('crm::crm.type')}}</td>
                <td >{{ $obj->category->title }}</td>
            </tr>
             <tr>
                <td >{{__('crm::crm.date')}}</td>
                <td >{{ $obj->date }}</td>
            </tr>
              <tr>
                <td >{{__('crm::crm.content')}}</td>
                <td >{{ $obj->content }}</td>
            </tr>
             <tr>
                <td >{{__('crm::crm.notes')}}</td>
                <td >{{ $obj->notes }}</td>
            </tr>
             <tr>
                <td >{{__('crm::crm.result')}}</td>
                <td >{{ $obj->result }}</td>
            </tr>
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
