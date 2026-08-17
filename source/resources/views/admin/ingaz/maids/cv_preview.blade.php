<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>معاينة سيرة {{ $maid->name }}</title>
    <style>
        html, body { margin: 0; min-height: 100%; background: #eef1f5; font-family: Tahoma, Arial, sans-serif; }
        .toolbar { position: sticky; top: 0; z-index: 10; display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 10px 16px; background: #fff; border-bottom: 1px solid #dfe4ea; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
        .title { font-weight: 700; color: #25324b; }
        .status { color: #667085; font-size: 13px; }
        #pages { display: flex; flex-direction: column; align-items: center; gap: 18px; padding: 18px; direction: ltr; }
        .pdf-page { display: block; max-width: 100%; height: auto; background: #fff; box-shadow: 0 4px 20px rgba(0,0,0,.16); }
        .error { margin: 40px auto; max-width: 620px; padding: 18px; background: #fff3f3; color: #a61b1b; border: 1px solid #ffc9c9; border-radius: 8px; text-align: center; }
    </style>
</head>
<body>
    <div class="toolbar">
        <div class="title">{{ $maid->name }} — {{ $maid->passport_num }}</div>
        <div class="status" id="pdfStatus">جارٍ تحميل ملف السيرة…</div>
    </div>
    <main id="pages"></main>

    <script src="{{ asset('admin/assets/vendor/pdfjs/pdf.min.js') }}"></script>
    <script>
        pdfjsLib.GlobalWorkerOptions.workerSrc = @json(asset('admin/assets/vendor/pdfjs/pdf.worker.min.js'));

        const pagesContainer = document.getElementById('pages');
        const statusElement = document.getElementById('pdfStatus');

        async function renderPdf() {
            try {
                const pdf = await pdfjsLib.getDocument({ url: @json($pdfUrl), withCredentials: true }).promise;
                statusElement.textContent = `عدد الصفحات: ${pdf.numPages}`;

                for (let pageNumber = 1; pageNumber <= pdf.numPages; pageNumber++) {
                    const page = await pdf.getPage(pageNumber);
                    const baseViewport = page.getViewport({ scale: 1 });
                    const availableWidth = Math.max(300, window.innerWidth - 42);
                    const scale = Math.min(1.7, availableWidth / baseViewport.width);
                    const viewport = page.getViewport({ scale });
                    const outputScale = Math.min(window.devicePixelRatio || 1, 2);
                    const canvas = document.createElement('canvas');
                    const context = canvas.getContext('2d', { alpha: false });

                    canvas.className = 'pdf-page';
                    canvas.width = Math.floor(viewport.width * outputScale);
                    canvas.height = Math.floor(viewport.height * outputScale);
                    canvas.style.width = Math.floor(viewport.width) + 'px';
                    canvas.style.height = Math.floor(viewport.height) + 'px';
                    pagesContainer.appendChild(canvas);

                    await page.render({
                        canvasContext: context,
                        viewport,
                        transform: outputScale !== 1 ? [outputScale, 0, 0, outputScale, 0, 0] : null
                    }).promise;
                }
            } catch (error) {
                statusElement.textContent = 'تعذر عرض الملف';
                pagesContainer.innerHTML = '<div class="error">تعذر تحميل ملف السيرة داخل العارض. تحقق من صلاحية ملف PDF ثم أعد المحاولة.</div>';
            }
        }

        renderPdf();
    </script>
</body>
</html>
