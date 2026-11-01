<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>موافقة على طلب المساعد الذكي</title>
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 16px; background: #f4f6f8; color: #1b2733; font-family: Tahoma, Arial, sans-serif; }
        main { width: 100%; max-width: 520px; padding: 24px; background: #fff; border: 1px solid #dfe5ea; border-radius: 12px; }
        h1 { margin: 0 0 6px; font-size: 1.2rem; }
        p { margin: 0 0 16px; color: #566573; font-size: .9rem; line-height: 1.7; }
        dl { display: grid; grid-template-columns: minmax(90px, auto) 1fr; gap: 8px 14px; margin: 0 0 20px; padding: 14px; background: #f8fafb; border-radius: 8px; font-size: .9rem; }
        dt { color: #566573; }
        dd { margin: 0; overflow-wrap: anywhere; white-space: pre-line; }
        .actions { display: flex; gap: 10px; }
        button { flex: 1; min-height: 44px; border: 0; border-radius: 8px; background: #0f766e; color: #fff; font: inherit; font-weight: 700; cursor: pointer; }
        .ok { color: #0f766e; }
        .bad { color: #b42318; }
    </style>
</head>
<body>
<main>
    @isset($failure)
        <h1 class="bad">لم يُنفذ الطلب</h1>
        <p>{{ $failure }}</p>
    @elseif (isset($result))
        @if (($result['sent'] ?? null) === 0)
            <h1 class="bad">لم تُرسل أي رسالة</h1>
        @else
            <h1 class="ok">تم التنفيذ</h1>
        @endif
        @if (!empty($result['message']))
            <p>{{ $result['message'] }}</p>
        @endif
        @if (!empty($result['failures']))
            <dl>
                @foreach ($result['failures'] as $failure)
                    <dt>{{ $failure['customer'] ?? $failure['row'] }}</dt><dd class="bad">{{ $failure['reason'] }}</dd>
                @endforeach
            </dl>
        @endif
        @if (!empty($result['rejected_rows']))
            <dl>
                <dt>لم تُحفظ</dt><dd class="bad">{{ implode("\n", $result['rejected_rows']) }}</dd>
            </dl>
        @endif
        <p>يمكنك إغلاق هذه الصفحة والعودة إلى المحادثة.</p>
        @php($recordUrl = $result['url'] ?? $result['maid']['url'] ?? $result['customer']['url'] ?? null)
        @if ($recordUrl)
            <p><a href="{{ $recordUrl }}">فتح السجل في لوحة التحكم</a></p>
        @endif
    @else
        <h1>طلب من المساعد الذكي</h1>
        <p>{{ $admin->name }}، راجع البيانات. لن يُحفظ شيء إلا إذا وافقت. إن لم تطلب هذا فأغلق الصفحة.</p>
        @php($labels = [
            'name' => 'الاسم', 'phone' => 'الجوال', 'type' => 'النوع', 'national_id' => 'الهوية', 'nationality' => 'الجنسية',
            'tax_number' => 'الرقم الضريبي', 'license_number' => 'السجل التجاري', 'email' => 'البريد', 'city' => 'المدينة', 'address' => 'العنوان',
            'branch' => 'الفرع', 'will_also' => 'سيتم أيضاً', 'passport_num' => 'رقم الجواز', 'gender' => 'الجنس', 'religion' => 'الديانة',
            'service_type' => 'نوع الخدمة', 'job_titles' => 'المهنة', 'office' => 'المكتب', 'shown_on_site' => 'الظهور في الموقع', 'status_after_save' => 'الحالة بعد الحفظ',
            'total_rows' => 'عدد الصفوف', 'will_save' => 'سيُحفظ', 'will_skip' => 'لن يُحفظ', 'rows' => 'الصفوف الصالحة', 'rejected_rows' => 'الصفوف المرفوضة',
        ])
        <dl>
            <dt>العملية</dt><dd dir="ltr" style="text-align:right">{{ $tool->name() }}</dd>
            @foreach ($preview as $field => $value)
                <dt>{{ $labels[$field] ?? $field }}</dt>
                <dd>{{ is_scalar($value) ? $value : implode("\n", array_map('strval', (array) $value)) }}</dd>
            @endforeach
        </dl>
        <form method="POST" action="{{ $action }}" class="actions">
            @csrf
            <button type="submit">موافقة وتنفيذ</button>
        </form>
    @endisset
</main>
</body>
</html>
