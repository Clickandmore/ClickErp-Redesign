<script>

    /*$(document).ready(function(){
        $.fn.modal.Constructor.prototype.enforceFocus = function() {};
    });*/
    var loader = `<div class="linear-background">
                            <div class="inter-crop"></div>
                            <div class="inter-right--top"></div>
                            <div class="inter-right--bottom"></div>
                        </div>`;

    function showDataTable(url, columns) {
        //console.log("showDataTable")
        $("#dataTableObject").DataTable({
            // dom: 'Bfrtip',
            responsive: 1,
            // "lengthChange": false,
            "ordering": true,
            "searching": true,
            'iDisplayLength': 10,
            "lengthMenu": [[5, 10, 25, 50, -1], [5, 10, 25, 50, "الكل"]],
            "processing": true,
            "serverSide": false,
            "sort": true,
            "ajax": url,
            "columns": columns,
            "language": {
                "sProcessing": "{{trans('dataTable.sProcessing')}}",
                "sLengthMenu": "{{trans('dataTable.sLengthMenu')}}",
                "sZeroRecords": "{{trans('dataTable.sZeroRecords')}}",
                "sInfo": "{{trans('dataTable.sInfo')}}",
                "sInfoEmpty": "{{trans('dataTable.sInfoEmpty')}}",
                "sInfoFiltered": "{{trans('dataTable.sInfoFiltered')}}",
                "sInfoPostFix": "",
                "sSearch": "{{trans('dataTable.sSearch')}}:",
                "sUrl": "",
                "oPaginate": {
                    "sFirst": "{{trans('dataTable.sFirst')}}",
                    "sPrevious": "{{trans('dataTable.sPrevious')}}",
                    "sNext": "{{trans('dataTable.sNext')}}",
                    "sLast": "{{trans('dataTable.sLast')}}"
                }
            },
            order: [
                [0, "desc"]
            ]
        })
    }

    // رسالة لكل حالة HTTP. قبل ذلك كان أي رد غير 422/500 يظهر كـ"لا تمتلك الصلاحية"،
    // فيبدو فشل رفع ملف كبير (413/419) وكأنه نقص صلاحيات.
    var ajaxStatusMessages = {
        0: 'تعذر الاتصال بالخادم، تحقق من الاتصال ثم حاول مرة أخرى.',
        401: '{{__("auth.code error")}}',
        403: '{{__("auth.code error")}}',
        404: 'الرابط المطلوب غير موجود.',
        405: 'هذا الإجراء غير مسموح به على هذا الرابط.',
        413: 'حجم الملف المرفوع أكبر من الحد المسموح به على الخادم.',
        419: 'انتهت صلاحية الجلسة أو تجاوز حجم الملف الحد المسموح به، حدّث الصفحة ثم أعد المحاولة.',
        429: 'عدد كبير من المحاولات، انتظر قليلًا ثم حاول مرة أخرى.',
        500: 'حدث خطأ في الخادم',
        502: 'الخادم لا يستجيب حاليًا، حاول بعد قليل.',
        503: 'الخدمة غير متاحة مؤقتًا، حاول بعد قليل.',
        504: 'انتهت مهلة الاتصال بالخادم، حاول مرة أخرى.'
    };

    function handleErrorResponce(errorObj) {
        console.log('handleErrorResponce');
        console.log(errorObj.status);
        let status = errorObj.status;
        let payload = {};
        try {
            payload = $.parseJSON(errorObj.responseText) || {};
        } catch (e) {
            payload = {};
        }

        if (status == 422 || status == 400) {
            let listObject = payload.data || payload.errors || {};
            let testArr = [];
            $.each(listObject, function (errorKey, errorArr) {
                if (Array.isArray(errorArr)) {
                    $.each(errorArr, function (key, value) {
                        testArr.push(value);
                    });
                } else {
                    testArr.push(errorArr);
                }
            });
            // أخطاء abort(422, '...') ترجع message فقط بلا قائمة حقول
            if (testArr.length === 0 && payload.message) {
                testArr.push(payload.message);
            }
            validToster('{{__("auth.an error occurred")}}', testArr.length > 0 ? testArr : ['حدث خطأ في البيانات المدخلة'])
            return;
        }

        let known = ajaxStatusMessages[status];
        let message = (status == 500 && payload.message)
            ? payload.message
            : (known || payload.message || ('حدث خطأ غير متوقع (' + status + ')'));
        errorToster('{{__("auth.an error occurred")}}', message);
    }

    function handleSuccessResponce(result) {
        //console.log("handleSuccessResponce" + result.code)
        console.log(result)
        if (result.code == 200) {
            successToster('{{__("auth.done successfully")}}', result.message);
        } else if (result.code == 201) {
            successToster('{{__("auth.done successfully")}}', result.message);
            // إذا كان هناك URL للتوجيه، استخدمه
            if (result.data && result.data.url) {
                setTimeout(function() {
                    location.href = result.data.url;
                }, 1500);
            } else {
                location.reload();
            }
        } else if (result.code == 401) {
            warningToster('{{__("auth.an error occurred")}}', result.message)
        } else {
            warningToster('{{__("auth.an error occurred")}}', result.message)
        }
    }

    function loadOptionsOnAjax() {
        if ($('.select2').length > 0) {
            $('.select2').select2({
                dropdownParent: $('#createModal .modal-body'),
            });
        }
        $.validate({
            ignore: 'input[type=hidden]',
            modules: 'date, security',
            lang: '{{\App::currentLocale()}}',
            validateOnEvent: true
        });
        if ($('.dropify').length > 0) {
            $('.dropify').dropify();
        }
        if ($('#json_array').length > 0) {
            $('#json_array').multifield();
        }
        if ($('#json_array2').length > 0) {
            $('#json_array2').multifield();
        }
    }

    function reloadDataTable() {
        if ($('#dataTableObject').length > 0) {
            $('#dataTableObject').DataTable().ajax.reload();
        }
    }

    $(document).on('click', '.addButton', function (e) {
        e.preventDefault()
        $('.modal-body').html(loader)
        let buttonObj = $(this);
        let modelRoute = buttonObj.attr("model-route");
        let modelTitle = buttonObj.attr("model-title");
        let modalResult = $("#createModal");
        $(".modal-body", modalResult).html();
        setTimeout(function () {
            $.ajax({
                url: modelRoute,
                type: 'GET',
                beforeSend: function () {
                    // You can add code to handle beforeSend event
                },
                complete: function () {
                    // You can add code to handle complete event
                },
                success: function (result) {
                    $("#exampleModalLgLabel").text(modelTitle);
                    $(".modal-body", modalResult).html(result.data.html);
                    loadOptionsOnAjax();
                },
                error: function (errorObj, errorText, errorThrown) {
                    handleErrorResponce(errorObj);
                }
            });
        }, 500);
    });

    $(document).on('click', '.editButton', function (e) {
        e.preventDefault()
        $('.modal-body').html(loader)
        let buttonObj = $(this);
        let modelRoute = buttonObj.attr("model-route");
        let modelTitle = buttonObj.attr("model-title");
        let modalResult = $("#createModal");
        $(".modal-body", modalResult).html();
        setTimeout(function () {
            $.ajax({
                url: modelRoute,
                type: 'GET',
                beforeSend: function () {
                },
                complete: function () {
                },
                success: function (result) {
                    $("#exampleModalLgLabel").text(modelTitle);
                    $(".modal-body", modalResult).html(result.data.html)
                    loadOptionsOnAjax();
                },
                error: function (errorObj, errorText, errorThrown) {
                    handleErrorResponce(errorObj);
                }
            });
        }, 700);
    });

    $(document).on('click', '.deleteButton', function (e) {
        e.preventDefault()
        let buttonObj = $(this);
        let deleteRoute = buttonObj.attr("delete-route");
        var currentPage = $('#dataTableObject').DataTable().page();
        swal.fire({
            title: "{{__('buttons.Are You Sure')}}",
            text: "{{__('buttons.You Can not to rollback')}}",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "{{__('buttons.accept')}}",
            cancelButtonText: "{{__('buttons.cancel')}}",
        }).then((result) => {
            if (result.isConfirmed) {
                    $.ajax({
                        url: deleteRoute,
                        type: 'DELETE',
                        beforeSend: function () {
                            if (window.cmShowLoader) {
                                window.cmShowLoader();
                            } else {
                                $('#loading-wrapper').show();
                            }
                        },
                        complete: function () {
                            if (window.cmHideLoader) {
                                window.cmHideLoader();
                            } else {
                                $('#loading-wrapper').hide();
                            }
                        },
                        success: function (data) {
                        Swal.fire({
                            icon: 'success',
                            title: "{{__('buttons.Deleted successfully')}}",
                            confirmButtonText: "{{__('buttons.close')}}",
                        });
                        reloadDataTable();
                        $('#dataTableObject').DataTable().page(currentPage).draw('page');
                    },
                    error: function (errorObj, errorText, errorThrown) {
                        handleErrorResponce(errorObj);
                    }
                });
            }
        });
    });

    $(document).on('submit', '.from-submit-global', function (e) {
        e.preventDefault();
        if (window.tinymce && tinymce.editors && tinymce.editors.length) {
            tinymce.triggerSave();
        }
        let form = this;
        let formData = new FormData(this);
        let url = $(this).attr('action');
        let submitButton = $(e.originalEvent.submitter);
        let buttonText = submitButton.html();
        let modalResult = $("#createModal");
        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            cache: false,
            contentType: false,
            processData: false,
            beforeSend: function () {
                $(form).find('[data-field]').text('').closest('.mb-3, .col-md-4, .col-md-6, .col-md-8, .col-12').find('input, select, textarea').removeClass('is-invalid');
                if (window.cmShowLoader) {
                    window.cmShowLoader();
                } else {
                    $('#loading-wrapper').show();
                }
                submitButton.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> جاري الحفظ...')
                submitButton.prop('disabled', true);
            },
            complete: function () {
                if (window.cmHideLoader) {
                    window.cmHideLoader();
                } else {
                    $('#loading-wrapper').hide();
                }
            },
            success: function (result) {
                console.log(200)
                $('.form-alert').html(result.message);
                handleSuccessResponce(result);
                reloadDataTable();
                submitButton.html(buttonText);
                submitButton.prop('disabled', false);
                if (modalResult.is(":visible")) {
                    modalResult.modal('toggle');
                }
                let successUrl = $(form).data('success-url');
                if (successUrl) {
                    window.location.href = successUrl;
                    return;
                }
                console.log(modalResult.is(':visible'));
            },
            error: function (errorObj, errorText, errorThrown) {
                console.log(500)
                console.log(errorObj)
                submitButton.html(buttonText);
                submitButton.prop('disabled', false);
                handleErrorResponce(errorObj);
                if (errorObj.status == 422 || errorObj.status == 400) {
                    try {
                        let errorsObj = $.parseJSON(errorObj.responseText);
                        let listObject = errorsObj.data || errorsObj.errors || {};
                        $.each(listObject, function (fieldName, errorArr) {
                            let firstError = Array.isArray(errorArr) ? errorArr[0] : errorArr;
                            let $errorSpan = $(form).find('[data-field="' + fieldName + '"]');
                            if ($errorSpan.length) {
                                $errorSpan.text(firstError);
                                $(form).find('[name="' + fieldName + '"]').addClass('is-invalid');
                            }
                        });
                    } catch(e) {}
                }
            }
        });

    });

    $(document).on('submit', '.from-submit-update-global', function (e) {
        e.preventDefault();
        if (window.tinymce && tinymce.editors && tinymce.editors.length) {
            tinymce.triggerSave();
        }
        let formData = new FormData(this);
        let url = $(this).attr('action');
        let submitButton = $(e.originalEvent.submitter);
        let buttonText = submitButton.text();
        let modalResult = $("#createModal");
        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            cache: false,
            contentType: false,
            processData: false,
            beforeSend: function () {
                if (window.cmShowLoader) {
                    window.cmShowLoader();
                } else {
                    $('#loading-wrapper').show();
                }
                submitButton.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ' + buttonText)
            },
            complete: function () {
                if (window.cmHideLoader) {
                    window.cmHideLoader();
                } else {
                    $('#loading-wrapper').hide();
                }
            },
            success: function (result) {
                handleSuccessResponce(result);
                reloadDataTable();
                submitButton.html(buttonText)
                modalResult.modal('toggle');
            },
            error: function (errorObj, errorText, errorThrown) {
                handleErrorResponce(errorObj);
            }
        });

    });


    $(document).on('click', '.remove-tr', function (e) {
        e.preventDefault()
        let obj = $(this)
        let totalTr = $(".remove-tr").length;
        if (totalTr == 1) {
            return false;
        }
        let parentTr = obj.closest("tr");
        parentTr.remove();
    });
    function loadJqerySelectors(){
        $.validate({
            ignore: 'input[type=hidden]',
            modules: 'date, security',
            lang: '{{\App::currentLocale()}}',
            validateOnEvent: true
        });
        if ($('.dropify').length > 0) {
            $('.dropify').dropify();
        }
        if ($('#json_array').length > 0) {
            $('#json_array').multifield();
        }
        if ($('#json_array2').length > 0) {
            $('#json_array2').multifield();
        }
    }
    function loadOptionsOnAjax() {
        if ($('.select2').length > 0) {
            $('.select2').select2({
                dropdownParent: $('#createModal .modal-body'),
            });
        }
        loadJqerySelectors();
    }

    function loadOptionsOnAjaxUpModel() {
        if ($('.select2').length > 0) {
            $('.select2').select2({
                dropdownParent: $('#upCreateModal .modal-body'),
            });
        }
        loadJqerySelectors();
    }
</script>
