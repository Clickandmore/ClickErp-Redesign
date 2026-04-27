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

    @if($obj->file)
{{--        @php--}}
{{--            $filePath = \Illuminate\Support\Facades\Storage::url($obj->file);--}}
{{--            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));--}}
{{--            $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp']);--}}
{{--            $isPdf = $extension === 'pdf';--}}
{{--            $isText = in_array($extension, ['txt', 'csv', 'html']);--}}
{{--            $isWord = in_array($extension, ['doc', 'docx']);--}}
{{--        @endphp--}}

{{--        @if($isImage)--}}
            <img src="{{ showFile($obj->file) }}" alt="Image" style="max-width: 100%; height: auto;">
{{--        @elseif($isPdf)--}}
{{--            <embed src="{{ $filePath }}" type="application/pdf" width="100%" height="600px" />--}}
{{--        @elseif($isText)--}}
{{--            <pre style="white-space: pre-wrap;">{{ file_get_contents(\Illuminate\Support\Facades\Storage::path($obj->file)) }}</pre>--}}
{{--        @elseif($isWord)--}}
{{--            <iframe src="https://docs.google.com/gview?url={{ urlencode($filePath) }}&embedded=true" style="width:100%; height:600px;" frameborder="0"></iframe>--}}
{{--        @else--}}
{{--            <p>Cannot display this file type directly.</p>--}}
{{--        @endif--}}
    @else
        <p>No file available.</p>
    @endif
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
