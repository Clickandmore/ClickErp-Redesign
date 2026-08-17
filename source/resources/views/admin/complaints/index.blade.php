@extends('admin.layout.indexs.index')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">
                        الشكاوى
                        @if($unreadCount > 0)
                            <span class="badge bg-danger">{{ $unreadCount }}</span>
                        @endif
                    </h4>
                </div>
                <div class="card-body">
                    <style>
                        .truncate-text {
                            max-width: 200px;
                            white-space: nowrap;
                            overflow: hidden;
                            text-overflow: ellipsis;
                        }
                        .show-all-btn {
                            background: none;
                            border: none;
                            color: #007bff;
                            cursor: pointer;
                            padding: 0;
                            font-size: 12px;
                        }
                        .show-all-btn:hover {
                            text-decoration: underline;
                        }
                        .modal-content {
                            max-height: 80vh;
                            overflow-y: auto;
                        }
                        .full-text {
                            white-space: pre-wrap;
                            word-wrap: break-word;
                        }
                    </style>

                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>الاسم</th>
                                <th>الموضوع</th>
                                <th>الرسالة</th>
                                <th>الحالة</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($complaints as $complaint)
                            <tr class="{{ !$complaint->is_read ? 'table-warning' : '' }}">
                                <td>{{ $complaint->id }}</td>
                                <td>{{ $complaint->name }}</td>
                                <td class="truncate-text">
                                    {{ \Illuminate\Support\Str::limit($complaint->subject, 30) }}
                                    @if(strlen($complaint->subject) > 30)
                                        <button type="button" class="show-all-btn" data-toggle="modal" data-target="#subjectModal{{ $complaint->id }}">
                                            عرض الكل
                                        </button>
                                    @endif
                                </td>
                                <td class="truncate-text">
                                    {{ \Illuminate\Support\Str::limit($complaint->message, 30) }}
                                    @if(strlen($complaint->message) > 30)
                                        <button type="button" class="show-all-btn" data-toggle="modal" data-target="#messageModal{{ $complaint->id }}">
                                            عرض الكل
                                        </button>
                                    @endif
                                </td>
                                <td>
                                    @if(!$complaint->is_read)
                                        <span class="badge bg-warning">جديدة</span>
                                    @else
                                        <span class="badge bg-success">مقروءة</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>

                    <!-- Subject Modal -->
                    @foreach($complaints as $complaint)
                    <div class="modal fade" id="subjectModal{{ $complaint->id }}" tabindex="-1" role="dialog" aria-labelledby="subjectModalLabel{{ $complaint->id }}" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="subjectModalLabel{{ $complaint->id }}">موضوع الشكوى</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="full-text">{{ $complaint->subject }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Message Modal -->
                    <div class="modal fade" id="messageModal{{ $complaint->id }}" tabindex="-1" role="dialog" aria-labelledby="messageModalLabel{{ $complaint->id }}" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="messageModalLabel{{ $complaint->id }}">محتوى الشكوى</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="full-text">{{ $complaint->message }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
