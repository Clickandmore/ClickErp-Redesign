@php
    use App\Support\ShelterDocumentFields as DocFields;

    $fields = DocFields::fields($kind);
    $sections = DocFields::sections($kind);
    $company = DocFields::labelParts($template['company_name'] ?? '');
    $title = DocFields::labelParts($template['title'] ?? '');
    $inlineOthers = array_filter(array_map([DocFields::class, 'otherField'], array_keys($fields)));
    $value = function (string $key) use ($data, $fields) {
        $v = $data[$key] ?? '';
        if (($fields[$key][1] ?? '') === 'date' && $v) {
            try { return \Illuminate\Support\Carbon::parse($v)->format('Y/m/d'); } catch (\Throwable $e) {}
        }
        return is_array($v) ? '' : (string) $v;
    };
    $partyKey = $kind === 'entry' ? 'client_name' : 'recipient_name';
    $signatures = [
        [$kind === 'entry' ? 'العميل' : 'المستلم', $kind === 'entry' ? 'Client' : 'Recipient', $partyKey, 'التوقيع', 'Signature'],
        ['العاملة', 'Worker', 'worker_name', 'التوقيع / البصمة', 'Signature / Fingerprint'],
        [$kind === 'entry' ? 'موظف الشركة' : 'الموظف المسؤول عن خروج العاملة', $kind === 'entry' ? 'Company Employee' : 'Responsible Employee', 'employee_name', 'التوقيع', 'Signature'],
        ['مسؤول الإيواء (الإدارة)', 'Accommodation Supervisor', 'supervisor_name', 'التوقيع', 'Signature'],
    ];
@endphp
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $template['title'] }}</title>
    <style>
        * { box-sizing: border-box; }
        :root { --ink: #1b1b1b; --accent: #1f3a5f; --line: #9aa4b1; --band: #e8edf3; --label: #f5f7fa; }
        body { font-family: Tahoma, Arial, sans-serif; color: var(--ink); margin: 0; background: #e9e9e9; font-size: 11.5px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        main { width: 210mm; min-height: 297mm; margin: 16px auto; padding: 11mm 12mm; background: #fff; box-shadow: 0 2px 10px rgba(0,0,0,.12); display: flex; flex-direction: column; }
        .en { direction: ltr; unicode-bidi: isolate; font-size: 9.5px; color: #4a5563; }

        header { text-align: center; border: 2px solid var(--accent); border-radius: 4px; overflow: hidden; }
        .company { padding: 8px 10px 6px; }
        .company .ar { font-size: 18px; font-weight: bold; color: var(--accent); }
        .company .en { font-size: 11px; letter-spacing: .6px; font-weight: bold; }
        .title { background: var(--accent); color: #fff; padding: 6px 10px; }
        .title .ar { font-size: 15px; font-weight: bold; }
        .title .en { color: #dbe4ef; font-size: 10.5px; letter-spacing: .5px; }

        .meta { display: flex; gap: 8px; margin: 8px 0 0; }
        .meta div { flex: 1; border: 1px solid var(--line); border-radius: 3px; display: flex; align-items: stretch; }
        .meta b { background: var(--label); padding: 5px 7px; border-left: 1px solid var(--line); font-weight: bold; white-space: nowrap; }
        .meta span.v { padding: 5px 7px; flex: 1; font-weight: bold; }

        section { margin-top: 7px; break-inside: avoid; }
        h3 { margin: 0; background: var(--band); border: 1px solid var(--line); border-bottom: 0; border-right: 4px solid var(--accent);
             padding: 5px 8px; font-size: 12.5px; display: flex; justify-content: space-between; align-items: baseline; }
        h3 .en { font-size: 10px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        td, th { border: 1px solid var(--line); padding: 4px 7px; vertical-align: middle; }
        th { background: var(--label); font-weight: bold; text-align: right; width: 19%; line-height: 1.35; }
        th .en { display: block; font-weight: normal; }
        td { height: 26px; overflow-wrap: anywhere; font-weight: bold; }
        td.text { white-space: pre-wrap; height: 38px; font-weight: normal; }
        .choices { display: flex; flex-wrap: wrap; gap: 6px 18px; font-weight: normal; }
        .choice { display: inline-flex; align-items: center; gap: 5px; }
        .box { display: inline-block; border: 1.2px solid var(--ink); width: 12px; height: 12px; line-height: 10px; text-align: center; font-size: 11px; font-weight: bold; }
        .other-text { display: inline-block; min-width: 120px; border-bottom: 1px dotted var(--ink); font-weight: bold; padding: 0 4px; }

        .declaration { border: 1px solid var(--line); border-top: 0; padding: 8px 10px; line-height: 1.9; white-space: pre-wrap; overflow-wrap: anywhere; }
        .sign th { text-align: center; width: auto; }
        .sign td { height: 34px; }
        .sign .hint { color: #8a929c; font-weight: normal; font-size: 9.5px; }
        .sign td.role { background: var(--label); line-height: 1.35; }
        .sign td.role .en { display: block; font-weight: normal; }
        .sign td.stamp-cell { text-align: center; }
        .stamp { margin: auto; border: 1.5px dashed var(--line); border-radius: 50%; width: 30mm; height: 30mm; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #6b7480; }
        footer { margin-top: auto; padding-top: 8px; border-top: 1px solid var(--line); text-align: center; font-size: 10px; color: #4a5563; white-space: pre-wrap; overflow-wrap: anywhere; }

        .toolbar { text-align: center; padding: 12px; font-family: Tahoma, Arial, sans-serif; }
        .toolbar p { margin: 0 0 8px; color: #8a4b00; }
        button { padding: 9px 26px; cursor: pointer; background: var(--accent); color: #fff; border: 0; border-radius: 4px; font-size: 13px; }
        @page { size: A4; margin: 8mm; }
        @media print {
            body { background: #fff; }
            main { width: auto; min-height: calc(297mm - 16mm); margin: 0; padding: 0; box-shadow: none; }
            .toolbar { display: none; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        @if ($preview ?? false)<p>معاينة تجريبية — ليست مستندًا لعقد فعلي</p>@endif
        <button onclick="window.print()">طباعة / حفظ PDF</button>
    </div>
    <main>
        <header>
            <div class="company">
                <div class="ar">{{ $company[0] }}</div>
                @if ($company[1])<div class="en">{{ $company[1] }}</div>@endif
            </div>
            <div class="title">
                <div class="ar">{{ $title[0] }}</div>
                @if ($title[1])<div class="en">{{ $title[1] }}</div>@endif
            </div>
        </header>

        <div class="meta">
            @foreach ($sections['meta'][1] as $key)
                @php [$ar, $en] = DocFields::labelParts($fields[$key][0]); @endphp
                <div><b>{{ $ar }} <span class="en">{{ $en }}</span></b><span class="v">{{ $value($key) }}</span></div>
            @endforeach
        </div>

        @foreach ($sections as $sectionKey => [$sectionTitle, $keys])
            @continue(in_array($sectionKey, ['meta', 'signatures'], true))
            @php
                [$sAr, $sEn] = DocFields::labelParts($sectionTitle);
                $wide = []; $pairs = [];
                foreach ($keys as $key) {
                    if (in_array($key, $inlineOthers, true)) continue;
                    if (in_array($fields[$key][1], ['choices', 'textarea'], true)) $wide[] = $key;
                    else $pairs[] = $key;
                }
            @endphp
            <section>
                <h3><span>{{ $sAr }}</span><span class="en">{{ $sEn }}</span></h3>
                <table>
                    @foreach (array_chunk($pairs, 2) as $row)
                        <tr>
                            @foreach ($row as $key)
                                @php [$ar, $en] = DocFields::labelParts($fields[$key][0]); @endphp
                                <th>{{ $ar }}<span class="en">{{ $en }}</span></th>
                                <td @if (count($row) === 1) colspan="3" @endif>{{ $value($key) }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                    @foreach ($wide as $key)
                        @php [$ar, $en] = DocFields::labelParts($fields[$key][0]); $field = $fields[$key]; @endphp
                        <tr>
                            <th>{{ $ar }}<span class="en">{{ $en }}</span></th>
                            @if ($field[1] === 'choices')
                                <td colspan="3">
                                    <div class="choices">
                                        @foreach ($field[2] as $choice => $choiceLabel)
                                            @php [$cAr, $cEn] = DocFields::labelParts($choiceLabel); @endphp
                                            <span class="choice">
                                                <span class="box">{{ in_array($choice, $data[$key] ?? [], true) ? '✓' : '' }}</span>
                                                {{ $cAr }} <span class="en">{{ $cEn }}</span>
                                                @if ($choice === 'other' && ($otherKey = DocFields::otherField($key)) && isset($fields[$otherKey]))
                                                    : <span class="other-text">{{ $value($otherKey) }}</span>
                                                @endif
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                            @else
                                <td colspan="3" class="text">{{ $value($key) }}</td>
                            @endif
                        </tr>
                    @endforeach
                </table>
            </section>
        @endforeach

        <section>
            <h3><span>الإقرار</span><span class="en">Declaration</span></h3>
            <div class="declaration">{{ $template['declaration'] }}</div>
        </section>

        <section>
            <h3><span>التوقيعات</span><span class="en">Signatures</span></h3>
            <table class="sign">
                <tr>
                    <th style="width:25%">الصفة <span class="en">Capacity</span></th>
                    <th style="width:29%">الاسم <span class="en">Name</span></th>
                    <th>التوقيع <span class="en">Signature</span></th>
                    <th style="width:20%">ختم الشركة <span class="en">Company Stamp</span></th>
                </tr>
                @foreach ($signatures as [$roleAr, $roleEn, $nameKey, $signAr, $signEn])
                    <tr>
                        <td class="role">{{ $roleAr }}<span class="en">{{ $roleEn }}</span></td>
                        <td>{{ $value($nameKey) }}</td>
                        <td>@if ($signAr !== 'التوقيع')<span class="hint">{{ $signAr }} / {{ $signEn }}</span>@endif</td>
                        @if ($loop->first)<td rowspan="{{ count($signatures) }}" class="stamp-cell"><div class="stamp"></div></td>@endif
                    </tr>
                @endforeach
            </table>
        </section>

        @if (filled($template['footer'] ?? ''))
            <footer>{{ $template['footer'] }}</footer>
        @endif
    </main>
</body>
</html>
