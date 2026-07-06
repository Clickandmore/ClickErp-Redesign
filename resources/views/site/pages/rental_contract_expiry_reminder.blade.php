<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تنبيه انتهاء عقد الإيجار</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Tahoma, Arial, sans-serif;
            background: #f4f7fb;
            color: #172033;
        }

        .page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .panel {
            width: 100%;
            max-width: 780px;
            background: #fff;
            border: 1px solid #e4e9f1;
            border-radius: 8px;
            box-shadow: 0 16px 40px rgba(23, 32, 51, 0.08);
            overflow: hidden;
        }

        .header {
            padding: 28px 32px;
            background: #0f5f63;
            color: #fff;
            text-align: center;
        }

        .header h1 {
            margin: 0 0 8px;
            font-size: 28px;
            font-weight: 700;
            letter-spacing: 0;
        }

        .header p {
            margin: 0;
            color: #d9f0f1;
            font-size: 16px;
            line-height: 1.8;
        }

        .content {
            padding: 28px 32px 32px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .field label {
            display: block;
            margin-bottom: 8px;
            color: #526070;
            font-size: 14px;
            font-weight: 700;
        }

        .value {
            min-height: 50px;
            display: flex;
            align-items: center;
            padding: 12px 14px;
            border: 1px solid #cfd8e3;
            border-radius: 6px;
            background: #f9fbfd;
            font-size: 16px;
            color: #172033;
            word-break: break-word;
        }

        .alert {
            padding: 14px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            line-height: 1.8;
        }

        .alert-success {
            background: #e8f7ee;
            border: 1px solid #bfe8ce;
            color: #145c2f;
        }

        .alert-warning {
            background: #fff6dd;
            border: 1px solid #f4dfa3;
            color: #735317;
        }

        .alert-info {
            background: #e9f5ff;
            border: 1px solid #bfddf7;
            color: #16466f;
        }

        .actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            appearance: none;
            border: 0;
            border-radius: 6px;
            padding: 13px 22px;
            min-width: 210px;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
        }

        .btn-primary {
            background: #0f5f63;
            color: #fff;
        }

        .btn-secondary {
            background: #eef2f6;
            color: #172033;
            border: 1px solid #cfd8e3;
        }

        .footer-note {
            margin: 22px 0 0;
            color: #6d7886;
            text-align: center;
            font-size: 13px;
            line-height: 1.8;
        }

        @media (max-width: 680px) {
            .page {
                padding: 12px;
                align-items: flex-start;
            }

            .header,
            .content {
                padding: 22px 18px;
            }

            .header h1 {
                font-size: 24px;
            }

            .grid {
                grid-template-columns: 1fr;
            }

            .btn {
                width: 100%;
                min-width: 0;
            }
        }
    </style>
</head>
<body>
    <main class="page">
        <section class="panel" aria-labelledby="pageTitle">
            <header class="header">
                <h1 id="pageTitle">تنبيه انتهاء عقد الإيجار</h1>
                <p>يرجى تحديد رغبتكم بخصوص العقد قبل تاريخ الانتهاء.</p>
            </header>

            <div class="content">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                @if(session('warning'))
                    <div class="alert alert-warning">{{ session('warning') }}</div>
                @endif

                <div class="grid">
                    <div class="field">
                        <label>رقم العقد</label>
                        <div class="value">{{ $contract->contract_number ?: $contract->id }}</div>
                    </div>

                    <div class="field">
                        <label>تاريخ الانتهاء</label>
                        <div class="value">{{ optional($contract->info?->ingaz_to)->format('Y-m-d') ?: '-' }}</div>
                    </div>

                    <div class="field">
                        <label>العميل</label>
                        <div class="value">{{ $contract->user?->name ?: '-' }}</div>
                    </div>

                    <div class="field">
                        <label>العاملة</label>
                        <div class="value">{{ $contract->maid?->name ?: '-' }}</div>
                    </div>
                </div>

                @if($reminder->responded_at)
                    <div class="alert alert-info">
                        تم تسجيل اختياركم مسبقاً:
                        <strong>{{ $reminder->response === 'extend' ? 'تمديد العقد' : 'تسليم العاملة' }}</strong>
                        بتاريخ {{ $reminder->responded_at->format('Y-m-d H:i') }}.
                    </div>

                    @if($reminder->response === 'extend')
                        <form method="POST" action="{{ route('rental-contract-reminders.respond', $reminder->token) }}">
                            @csrf
                            <div class="actions">
                                <button type="submit" name="response" value="extend" class="btn btn-primary">
                                    المتابعة إلى صفحة الدفع
                                </button>
                            </div>
                        </form>
                    @endif
                @else
                    <form method="POST" action="{{ route('rental-contract-reminders.respond', $reminder->token) }}">
                        @csrf
                        <div class="actions">
                            <button type="submit" name="response" value="extend" class="btn btn-primary">
                                الموافقة على التمديد والذهاب للدفع
                            </button>

                            <button type="submit" name="response" value="return" class="btn btn-secondary">
                                تسليم العاملة
                            </button>
                        </div>
                    </form>
                @endif

                <p class="footer-note">
                    هذه صفحة مستقلة خاصة بتحديد حالة عقد الإيجار، ولا تتطلب تسجيل دخول.
                </p>
            </div>
        </section>
    </main>
</body>
</html>
