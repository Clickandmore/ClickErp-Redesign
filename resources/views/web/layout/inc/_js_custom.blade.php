<script>
    /*$(document).ready(function(){
        $.fn.modal.Constructor.prototype.enforceFocus = function() {};
    });*/

    function getViewWithAjax(url, divClassToRender) {
        $.ajax({
            url: url,
            type: 'GET',
            success: function (response) {
                // Update a DOM element with the received HTML
                $('.' + divClassToRender).html(response.data.html);
            },
            error: function (error) {
                console.log('Error:', error);
            }
        });
    }



    function showDataTable(url, columns) {
        //console.log("showDataTable")
        var myTable = $("#dataTableObject").DataTable({
            // dom: 'Bfrtip',
            responsive: 1,
            // "lengthChange": false,
            "ordering": true,
            "searching": true,
            'iDisplayLength': 10,
            "lengthMenu": [[5, 10, 25, 50, -1], [5, 10, 25, 50, "الكل"]],
            "processing": true,
            "serverSide": true,
            "sort": false,
            "ajax": {
                "url": "" + url,
                'type': "GET",
                "data": function (d) {
                    d.filter = $('.filter_search').val();
                }
            },

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
                [2, "desc"]
            ]
        })

        $(".filter_search").change(function () {
            myTable.draw();
        });
    }

    function handleErrorResponce(errorObj) {
        console.log('handleErrorResponce');
        console.log(errorObj.status);
        if (errorObj.status == 422) {
            let errorsObj = $.parseJSON(errorObj.responseText);
            let listObject = errorsObj.data;
            let testArr = [];
            $.each(listObject, function (errorKey, errorArr) {
                $.each(errorArr, function (key, value) {
                    testArr.push(value);
                });
            });
            validToster('{{__("auth.an error occurred")}}', testArr)
        } else {
            errorToster('{{__("auth.an error occurred")}}', "{{__("auth.code error")}}");
        }
    }

    function handleSuccessResponce(result) {
        console.log("handleSuccessResponce" + result.code)
        console.log(result)
        if (result.code === 200) {
            successToster('{{__("auth.done successfully")}}', result.message);
        } else if (result.code == 201) {
            successToster('{{__("auth.done successfully")}}', result.message);
            location.reload();
            //location.href = result.data.url;
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
        let buttonObj = $(this);
        let modelRoute = buttonObj.attr("model-route");
        let modelTitle = buttonObj.attr("model-title");
        let modalResult = $("#" + $(this).attr('model-name-id'));
        $(".modal-body", modalResult).html();
        $.ajax({
            url: modelRoute,
            type: 'GET',
            beforeSend: function () {
            },
            complete: function () {
            },
            success: function (result) {
                $("#exampleModalLgLabel").text(modelTitle);
                // modalResult.innerHTML =result.data.html;
                $(".modal-body", modalResult).html(result.data.html)
                loadOptionsOnAjax();
            },
            error: function (errorObj, errorText, errorThrown) {
                handleErrorResponce(errorObj);
            }
        });
    });

    $(document).on('click', '.editButton', function (e) {
        e.preventDefault()
        let buttonObj = $(this);
        let modelRoute = buttonObj.attr("model-route");
        let modelTitle = buttonObj.attr("model-title");
        let modalResult = $("#createModal");
        $(".modal-body", modalResult).html();
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
    });

    $(document).on('click', '.deleteButton', function (e) {
        e.preventDefault()
        let buttonObj = $(this);
        let deleteRoute = buttonObj.attr("delete-route");
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
                    },
                    complete: function () {
                    },
                    success: function (data) {
                        Swal.fire({
                            icon: 'success',
                            title: "{{__('buttons.Deleted successfully')}}",
                            confirmButtonText: "{{__('buttons.close')}}",
                        });
                        reloadDataTable();
                    },
                    error: function (errorObj, errorText, errorThrown) {
                        handleErrorResponce(errorObj);
                    }
                });
            }
        });
    });

    $(document).on('submit', '.from-submit-global', function (e) {
        e.preventDefault(); // Prevent default form submission
        let formData = new FormData(this);
        let url = $(this).attr('action');
        let submitButton = $(e.originalEvent.submitter);
        let buttonText = submitButton.text();
        let modalResult = $("#" + $(this).attr('model-name-id'));

        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            cache: false,
            contentType: false,
            processData: false,
            beforeSend: function () {
                submitButton.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ' + buttonText);
            },
            complete: function () {
                // Can be used to reset loading states if needed
            },
            success: function (result) {
                console.log(200);
                handleSuccessResponce(result);
                reloadDataTable(); // This function should handle updating your table

                // Reset button text after request completes
                submitButton.html(buttonText);

                // Hide modal if it's visible
                $('[data-bs-dismiss="modal"]').trigger('click');

                $('#addLocation').modal('hide');

                // Check if there is an address and update the view accordingly
                if (result.data && result.data.address) {
                    getViewWithAjax('{{route('web.addresses.index')}}', 'addresses_div');
                }
            },
            error: function (errorObj, errorText, errorThrown) {
                console.log(500);
                console.log(errorObj);
                handleErrorResponce(errorObj);
            }
        });
    });

    $(document).on('submit', '.from-submit-update-global', function (e) {
        e.preventDefault();
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
                submitButton.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ' + buttonText)
            },
            complete: function () {
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


    // $(document).ajaxComplete(function () {
    //     $(".trim_items").each(function () {
    //         var text = $(this).text();
    //         if (text.length > 20) {
    //             var truncatedText =
    //                 $.trim(text).substring(0, 20).split(" ").slice(0, -1).join(" ") + ".";
    //             var readMoreLink = '...';
    //             $(this).text(truncatedText).append(readMoreLink);
    //         }
    //     });
    // });
</script>