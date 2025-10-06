<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>


<!------------------------ main -------------------------------------------------->
<script src="{{asset('admin')}}/assets/js/plugins.js"></script>
<script src="{{asset('admin')}}/assets/js/theme.js"></script>

<!------------------------ bundle -------------------------------------------------->
<script src="{{asset('admin')}}/assets/js/bundle/sweetalert2.bundle.js"></script>
<script src="{{asset('admin')}}/assets/js/bundle/select2.bundle.js"></script>

<!------------------------ vendor -------------------------------------------------->
<script src="{{asset('admin')}}/assets/vendor/toster/js/jquery.toast.js"></script>
<script src="{{asset('admin')}}/assets/vendor/jquery_validator/form_validator/jquery.form-validator.min.js"></script>

<!------------------------ custom -------------------------------------------------->
<script src="{{asset('admin')}}/assets/js/custom.js"></script>

<!------------------------ dropify -------------------------------------------------->
<script src="{{asset('admin')}}/assets/js/bundle/dropify.bundle.js"></script>

<script src="{{asset('admin')}}/assets/js/jquery.multifield.min.js"></script>
<script src="{{asset('admin')}}/assets/js/jquery.multifield.js"></script>


<script src="{{asset('admin')}}/assets/js/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>


<script type="text/javascript" src="https://www.codehim.com/demo/jquery-image-uploader-preview-and-delete/dist/image-uploader.min.js"></script>

<script src="{{asset('admin')}}/assets/js/tinymce.min.js"></script>
<script>
    $(document).ajaxComplete(function () {
        tinymce.init({
            selector: '.mytextarea',
            plugins: 'ai tinycomments mentions anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount checklist mediaembed casechange export formatpainter pageembed permanentpen footnotes advtemplate advtable advcode editimage tableofcontents mergetags powerpaste tinymcespellchecker autocorrect a11ychecker typography inlinecss textcolor colorpicker',
            toolbar: 'undo redo | formatselect | fontselect fontsizeselect | forecolor backcolor | bold italic underline strikethrough | align lineheight | tinycomments | checklist numlist bullist indent outdent | emoticons charmap | removeformat',
        });
    });
</script>

<script>


    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });



    $(function () {
        $('.dropify').dropify();
        var drEvent = $('#dropify-event').dropify();
        drEvent.on('dropify.beforeClear', function (event, element) {
          return confirm("Do you really want to delete \"" + element.file.name + "\" ?");
        });
        drEvent.on('dropify.afterClear', function (event, element) {
          alert('File deleted');
        });
        $('.dropify-fr').dropify({
          messages: {
            default: 'Glissez-dÃ©posez un fichier ici ou cliquez',
            replace: 'Glissez-dÃ©posez un fichier ou cliquez pour remplacer',
            remove: 'Supprimer',
            error: 'DÃ©solÃ©, le fichier trop volumineux'
          }
        });




      });


</script>

<!------------------------ loader -------------------------------------------------->

<script>
    $(window).on('load', function () {
        $('#loading-wrapper').fadeOut();
    });
</script>


<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<!-- DataTables Responsive JS -->
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>

