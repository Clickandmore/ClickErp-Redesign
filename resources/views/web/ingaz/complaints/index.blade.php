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
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>{{helperTrans('web.complaint_title')}}</th>
                                            <th>{{helperTrans('web.complaint_details')}}</th>
                                            <th>{{helperTrans('web.complaint_status')}}</th>
                                            <th>{{helperTrans('web.response')}}</th>
                                            <th>{{helperTrans('web.date')}}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($complaints as $complaint)
                                        <tr>
                                            <td>{{$loop->iteration}}</td>
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
                <form class="form-wrap from-submit-global" action="{{route('web.complaints.store')}}" method="post" id="complaintForm">
                    @csrf
                    <div class="row">
                        <div class="col-12 p-2">
                            <div class="form-group">
                                <label for="locationName">
                                    <i class="fa-light fa-clipboard"></i>
                                    {{helperTrans('web.complaint_title')}}
                                </label>
                                <input id="address" name="address" type="text" placeholder="{{helperTrans('web.house_example')}}">
                            </div>
                        </div>
                        <div class="col-lg-12 p-2">
                            <div class="form-group">
                                <label for="details">
                                    <i class="fa-regular fa-comment-dots"></i>
                                    {{helperTrans('web.complaint_details')}}
                                </label>
                                <textarea name="message" id="message" placeholder="{{helperTrans('web.complaint_details')}} "></textarea>
                            </div>
                        </div>
                        <div class="col-lg-12 p-2">
                            <div class="form-group mb-0">
                                <button type="submit">
                                    {{helperTrans('web.save')}}
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@section('js')
<script>
    $(document).ready(function() {
        $('#complaintForm').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: form.serialize(),
                success: function(response) {
                    if(response.success) {
                        toastr.success(response.message);
                        $('#complain').modal('hide');
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    }
                },
                error: function(xhr) {
                    toastr.error(xhr.responseJSON.message);
                }
            });
        });
    });
</script>
@endsection
