<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Clients List</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Clients List</h1>
        <p>Generated on: {{ date('Y-m-d H:i:s') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Phone</th>
                <th>Email</th>
                <th>Nationality</th>
                <th>National Number</th>
                <th>Birthdate</th>
                <th>Address</th>
                <th>City</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($clients as $client)
                <tr>
                    <td>{{ $client->id }}</td>
                    <td>{{ $client->name }}</td>
                    <td>{{ $client->phone_code . $client->phone }}</td>
                    <td>{{ $client->email }}</td>
                    <td>{{ $client->nationality->title ?? '' }}</td>
                    <td>{{ $client->national_num }}</td>
                    <td>{{ $client->birthdate }}</td>
                    <td>{{ $client->address }}</td>
                    <td>{{ $client->city }}</td>
                    <td>{{ $client->is_blocked ? 'Blocked' : 'Active' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
