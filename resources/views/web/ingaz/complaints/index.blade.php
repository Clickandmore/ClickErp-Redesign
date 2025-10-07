@extends('web.layout.app')

@section('content')
<main>
    <section class="page_header">
        <h1>{{helperTrans('web.all_complaints')}}</h1>
        <h6><a href="{{route('web.home')}}">{{helperTrans('web.Home')}}</a> / {{helperTrans('web.all_complaints')}}</h6>
    </section>

    <section class="setting_setion">
        <div class="container">
            <div class="row">

                    @include('web.layout.inc.profile_aside')

                <div class="col-lg-8 col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4>{{helperTrans('web.all_complaints')}}</h4>
                            <button class="complain" data-bs-toggle="modal" data-bs-target="#complain">
                                <i class="fa-light fa-plus"></i> {{helperTrans('web.add_complaint')}}
                            </button>
                        </div>
                        <div class="card-body">

                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>{{helperTrans('web.complaint_type')}}</th>
                                            <th>{{helperTrans('web.complaint_title')}}</th>
                                            <th>{{helperTrans('web.complaint_details')}}</th>
                                            <th>{{helperTrans('web.status')}}</th>
                                            <th>{{helperTrans('web.response')}}</th>
                                            <th>{{helperTrans('web.attachment')}}</th>
                                            <th>{{helperTrans('web.date')}}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($complaints as $complaint)
                                        <tr>
                                            <td>{{$loop->iteration}}</td>
                                            <td>{{$complaint->complaintType->name ?? '-'}}</td>
                                            <td>{{$complaint->address}}</td>
                                            <td>{{$complaint->message}}</td>
                                            <td>
                                                @php
                                                    $statusClass = '';
                                                    switch($complaint->status) {
                                                        case 'pending':
                                                            $statusClass = 'text-warning';
                                                            break;
                                                        case 'resolved':
                                                            $statusClass = 'text-success';
                                                            break;
                                                        case 'rejected':
                                                            $statusClass = 'text-danger';
                                                            break;
                                                    }
                                                @endphp
                                                <span class="{{$statusClass}}">
                                                    {{helperTrans('web.complaint_status_' . $complaint->status)}}
                                                </span>
                                            </td>
                                            <td>{{$complaint->response ?? '-'}}</td>
                                            <td>
                                                @if($complaint->file)
                                                    <a href="{{ Storage::url($complaint->file) }}" target="_blank" class="btn btn-sm btn-primary">
                                                        <i class="fa-regular fa-file"></i> {{helperTrans('web.view_attachment')}}
                                                    </a>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>{{$complaint->created_at->format('Y-m-d')}}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>


                        </div>
                    </div>
                </div>


            </div>
        </div>
    </section>
</main>

<div class="modal fade add_location" id="complain" tabindex="-1" aria-labelledby="complainLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @include('web.ingaz.complaints._form')
            </div>
        </div>
    </div>
</div>

@section('css')
<link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet">
@endsection

@section('js')
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>
    $(document).ready(function() {
        // تكوين toastr
        toastr.options = {
            "closeButton": true,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "showDuration": "300",
            "hideDuration": "1000",
            "timeOut": "2000",
            "extendedTimeOut": "1000",
            "showEasing": "swing",
            "hideEasing": "linear",
            "showMethod": "fadeIn",
            "hideMethod": "fadeOut"
        };

        // إزالة أي معالجات سابقة للنموذج
        $('#complaintForm').off('submit');

        // إضافة معالج جديد للنموذج
        $('#complaintForm').on('submit', function(e) {
            e.preventDefault();
            e.stopPropagation();

            var form = $(this);
            var submitButton = form.find('button[type="submit"]');

            // تعطيل الزر
            submitButton.prop('disabled', true);

            // إنشاء FormData object
            var formData = new FormData(this);

            // إرسال النموذج
            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if(response.code === 200) {
                        // إغلاق النافذة المنبثقة
                        var modalElement = document.getElementById('complain');
                        if (modalElement) {
                            var modal = bootstrap.Modal.getInstance(modalElement);
                            if (modal) {
                                modal.hide();
                            } else {
                                $(modalElement).modal('hide');
                            }
                        }

                        // إظهار رسالة النجاح
                        toastr.success(response.message);

                        // إعادة تحميل الصفحة بعد فترة قصيرة
                        setTimeout(function() {
                            window.location.reload();
                        }, 2000);
                    } else {
                        toastr.error(response.message || 'حدث خطأ أثناء إرسال الشكوى');
                    }
                },
                error: function(xhr) {
                    toastr.error(xhr.responseJSON.message || 'حدث خطأ أثناء إرسال الشكوى');
                },
                complete: function() {
                    // إعادة تفعيل الزر
                    submitButton.prop('disabled', false);
                }
            });

            return false;
        });

        // إعادة تعيين النموذج عند إغلاق النافذة
        $('#complain').on('hidden.bs.modal', function () {
            $('#complaintForm')[0].reset();
        });

        // معالجة حدث التمرير بشكل آمن
        window.onscroll = function() {
            try {
                var element = document.querySelector('.page_header');
                if (element) {
                    var offsetTop = element.offsetTop;
                    // يمكنك إضافة المنطق الخاص بك هنا
                }
            } catch (error) {
                console.warn('Error in scroll handler:', error);
            }
        };
    });
</script>
@endsection
