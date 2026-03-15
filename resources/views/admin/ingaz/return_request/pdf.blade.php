<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{helperTrans("ingaz.cancelled_orders")}}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 20px;
            direction: rtl;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            color: #333;
            margin: 0;
        }
        .header p {
            color: #666;
            margin: 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: right;
        }
        th {
            background-color: #f4f4f4;
            font-weight: bold;
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            color: white;
        }
        .status-refused {
            background-color: #dc3545;
        }
        .status-return {
            background-color: #ffc107;
            color: #333;
        }
        .status-deleted {
            background-color: #6c757d;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{helperTrans("ingaz.cancelled_orders")}}</h1>
        <p>{{helperTrans("ingaz.report_generated_at")}}: {{ now()->format('Y-m-d H:i:s') }}</p>
        <p>{{helperTrans("ingaz.total_records")}}: {{ $data->count() }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>{{helperTrans("ingaz.order_code")}}</th>
                <th>{{helperTrans("ingaz.client")}}</th>
                <th>{{helperTrans("ingaz.phone")}}</th>
                <th>{{helperTrans("ingaz.maid")}}</th>
                <th>{{helperTrans("ingaz.status")}}</th>
                <th>{{helperTrans("ingaz.order_date")}}</th>
                <th>{{helperTrans("ingaz.responsible_employee")}}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $order)
            <tr>
                <td>{{ $order->order_code }}</td>
                <td>{{ $order->user ? $order->user->name : '-' }}</td>
                <td>{{ $order->user ? ($order->user->phone_code . $order->user->phone) : '-' }}</td>
                <td>{{ $order->maid ? $order->maid->name : '-' }}</td>
                <td>
                    @if($order->deleted_at)
                        <span class="status-badge status-deleted">محذوفة</span>
                    @elseif($order->status == \App\Enums\ingaz\OrderEnum::Refused->value)
                        <span class="status-badge status-refused">مرفوضة</span>
                    @elseif($order->status == \App\Enums\ingaz\OrderEnum::Return->value)
                        <span class="status-badge status-return">ملغية</span>
                    @else
                        <span class="status-badge status-deleted">{{ getEnumValue('orders_statuses', $order->status) }}</span>
                    @endif
                </td>
                <td>{{ $order->created_at->format('Y-m-d H:i') }}</td>
                <td>{{ $order->resolveResponsibleAdmin()?->name ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>{{helperTrans("ingaz.report_generated_by")}}: {{ Auth::guard('admin')->user()->name ?? 'System' }}</p>
        <p>{{helperTrans("ingaz.page")}}: <span class="page"></span></p>
    </div>
</body>
</html>
