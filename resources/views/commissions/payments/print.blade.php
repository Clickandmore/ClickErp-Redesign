<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>سند صرف عمولة #{{ $payment->id }}</title>
    <style>
        body { font-family: Tahoma, Arial, sans-serif; margin: 40px; color: #1a1a1a; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 16px; margin-bottom: 24px; }
        .header h2 { margin: 0 0 4px; font-size: 1.4rem; }
        .header p { margin: 0; color: #555; font-size: .9rem; }
        .badge-num { display: inline-block; background: #e8f5e9; border: 1px solid #43a047; color: #2e7d32; padding: 4px 14px; border-radius: 20px; font-size: 1rem; font-weight: 900; margin-top: 8px; }
        table.info { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        table.info td { padding: 10px 14px; border: 1px solid #ddd; }
        table.info td:first-child { background: #f5f5f5; font-weight: 700; width: 35%; }
        .amount-box { text-align: center; padding: 20px; border: 2px dashed #2e7d32; border-radius: 12px; margin-bottom: 24px; }
        .amount-box .label { color: #555; font-size: .9rem; }
        .amount-box .val { font-size: 2rem; font-weight: 900; color: #2e7d32; }
        .signatures { display: flex; justify-content: space-between; margin-top: 48px; }
        .sig-box { text-align: center; border-top: 1px solid #555; width: 180px; padding-top: 8px; font-size: .85rem; color: #444; }
        @media print { @page { margin: 20mm; } button { display: none; } }
    </style>
</head>
<body>

<div class="header">
    <h2>سند صرف عمولة</h2>
    <p>نظام العمولات — Click ERP</p>
    <span class="badge-num">سند رقم #{{ $payment->id }}</span>
</div>

<table class="info">
    <tr>
        <td>اسم الموظف</td>
        <td>{{ $admin->name }}</td>
    </tr>
    <tr>
        <td>تاريخ السند</td>
        <td>{{ $payment->payment_date->format('Y-m-d') }}</td>
    </tr>
    <tr>
        <td>الحالة</td>
        <td>{{ $payment->status === 'approved' ? 'معتمد' : 'معلق' }}</td>
    </tr>
    <tr>
        <td>المعتمد من</td>
        <td>{{ optional($payment->approvedBy)->name ?? '-' }}</td>
    </tr>
    <tr>
        <td>تاريخ الاعتماد</td>
        <td>{{ $payment->approved_at ? $payment->approved_at->format('Y-m-d H:i') : '-' }}</td>
    </tr>
    @if($payment->notes)
    <tr>
        <td>الملاحظات</td>
        <td>{{ $payment->notes }}</td>
    </tr>
    @endif
</table>

<div class="amount-box">
    <div class="label">المبلغ المصروف</div>
    <div class="val">{{ number_format($payment->amount, 2) }} ريال</div>
</div>

<div class="signatures">
    <div class="sig-box">الموظف المستلم<br>{{ $admin->name }}</div>
    <div class="sig-box">المعتمد<br>{{ optional($payment->approvedBy)->name ?? '...' }}</div>
    <div class="sig-box">الختم الرسمي</div>
</div>

<div style="margin-top:32px; text-align:center;">
    <button onclick="window.print()" style="padding:10px 28px;background:#1976d2;color:#fff;border:none;border-radius:8px;font-size:1rem;cursor:pointer;">
        طباعة
    </button>
</div>

</body>
</html>
