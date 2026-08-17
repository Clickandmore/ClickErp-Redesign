@push('css')
@endpush

@push('js')

<script src="https://cdn.ckeditor.com/ckeditor5/34.2.0/classic/ckeditor.js"></script>
@if(app()->getLocale() == 'ar')
    <script src="https://cdn.ckeditor.com/ckeditor5/34.2.0/classic/translations/ar.js"></script>
@endif
<script>
    ClassicEditor
        .create(document.querySelector('#editor'), {
            toolbar: {
                items: [
                    'heading',
                    '|',
                    'bold',
                    'italic',
                    'link',
                    'bulletedList',
                    'numberedList',
                    'blockQuote',
                    'insertTable',
                    'mediaEmbed',
                    'undo',
                    'redo'
                ]
            },
            @if(app()->getLocale() == 'ar')
            language: 'ar',
            contentLanguage: 'ar',
            direction: 'rtl',
            @endif
            height: 400,
        })
        .catch(error => {
            console.error(error);
        });
</script>


<script>
    ClassicEditor
        .create(document.querySelector('#editor2'), {
            toolbar: {
                items: [
                    'heading',
                    '|',
                    'bold',
                    'italic',
                    'link',
                    'bulletedList',
                    'numberedList',
                    'blockQuote',
                    'insertTable',
                    'mediaEmbed',
                    'undo',
                    'redo'
                ]
            },
            @if(app()->getLocale() == 'ar')
            language: 'ar',
            contentLanguage: 'ar',
            direction: 'rtl',
            @endif
            height: 400,
        })
        .catch(error => {
            console.error(error);
        });
</script>
@endpush
