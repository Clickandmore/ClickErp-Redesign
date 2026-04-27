@extends('click.layout.indexs.index')
@section('style')
    <link rel="stylesheet" href="{{ asset('admin') }}/assets/cssbundle/dataTables.min.css">
    <style>

    </style>
@endsection
@section('page-title')
    {{ __('crm::crm.show-sent-polls') }}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{ __('crm::crm.show-sent-polls') }}</li>
@endsection
@section('content')

    <div class="row">
        <div class="col-md-12">
            @foreach($polls as $poll)
                <div class="card">
                    <div class="card-header">

                        {{@$poll->poll->title}}
                        @if($poll->status == 'new')
                            <span class="badge bg-warning">
                                جديد
                            </span>
                        @else
                            <span class="badge bg-success">
                                مكتمل <i class=" fa fa-check"></i>
                            </span>
                        @endif
                    </div>
                    <div class="card-body">
                        @if($poll->answers->count() > 0)
                        <table class="table table-bordered table-striped">
                            <thead>
                            <tr>
                                <th>{{__('crm::crm.question')}}</th>
                                <th>{{__('crm::crm.answer')}}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($poll->answers as $row)
                                <tr>
                                    <!-- عرض السؤال -->
                                    <td>{{ @$row->question->name }}</td>

                                    <!-- عرض الجواب بناءً على نوع السؤال -->
                                    @if($row->question->type == 'essay')
                                        <td>{{ @$row->essay_answer }}</td>
                                    @else
                                        <td>{{ @$row->answer->value }}</td>
                                    @endif
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                        @endif
                    </div>
                </div>
                <br>
            @endforeach
        </div>


    </div>
@endsection
@section('js')

@endsection
