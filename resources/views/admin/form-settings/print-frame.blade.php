<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>طباعة {{ $title }}</title>
    <style>
        html, body {
            margin: 0;
            width: 100%;
            height: 100%;
            background: #f8fafc;
            font-family: Tahoma, Arial, sans-serif;
        }

        .loader {
            position: fixed;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0f172a;
            font-size: 14px;
        }

        iframe {
            width: 100%;
            height: 100%;
            border: 0;
            background: #fff;
        }
    </style>
</head>
<body>
    <div class="loader" id="loader">جاري تجهيز الطباعة...</div>
    <iframe id="preview-frame" src="{{ $src }}"></iframe>

    <script>
        const frame = document.getElementById('preview-frame');
        const loader = document.getElementById('loader');

        frame.addEventListener('load', function () {
            loader.style.display = 'none';

            setTimeout(function () {
                try {
                    frame.contentWindow.focus();
                    frame.contentWindow.print();
                } catch (error) {
                    console.error('Print preview error:', error);
                }
            }, 350);
        });
    </script>
</body>
</html>
