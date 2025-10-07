@extends('admin.layout.indexs.index')

@section('page-title')
    {{ __('admin.shelter_workers') }}
@endsection

@section('content')
    @if (@AdminGuardHasPerm(\App\PermKeys::SHELTER_WORKERS_VIEW))
        <div class="row">
            <!-- Info Card -->
            <div class="col-md-12">
                <div class="card my-4">
                    <div class="card-header">
                        <h4>{{ __('admin.worker_info') }}</h4>
                        <a href="{{ url()->previous() }}" class="btn btn-secondary">{{ __('admin.back') }}</a>

                    </div>
                    <div class="card-body">
                        <p><strong>{{ __('admin.name') }}:</strong> {{ $shelterWorker->name }}</p>
                        <p><strong>{{ __('admin.city') }}:</strong> {{ $shelterWorker->city }}</p>
                        <p><strong>{{ __('admin.worker_name') }}:</strong> {{ $shelterWorker->worker_name }}</p>
                        <!-- Add more fields as needed -->
                    </div>
                </div>
            </div>

            <!-- Table for Uploaded Files -->
            <div class="card-header">
                <h5>{{ __('admin.files') }}</h5>
            </div>
            <table class="table table-bordered text-center">
                <thead>
                    <tr>
                        <th>{{ __('admin.file') }}</th>
                        <th>{{ __('admin.notes') }}</th>
                        <th>{{ __('admin.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $fileFields = [
                            'travel_doc' => __('admin.travel_doc'),
                            'settlement' => __('admin.settlement'),
                            'graduation' => __('admin.graduation'),
                            'reserve' => __('admin.reserve'),
                            'travel' => __('admin.travel'),
                        ];

                        $filesExist = false; // Flag to check if any files exist
                    @endphp

                    @foreach ($fileFields as $field => $label)
                        @if ($shelterWorker->$field)
                            @php $filesExist = true; @endphp
                            <tr>
                                <td>{{ $label }}</td>
                                <td>{{ $shelterWorker->{$field . '_notes'} }}</td>
                                <td>
                                    <!-- Download Button -->
                                    <a href="{{ route('shelter_workers.download', ['field' => $field, 'id' => $shelterWorker->id]) }}"
                                        class="btn btn-sm btn-primary">{{ __('admin.download') }}</a>

                                    <!-- Delete Button -->
                                    @if (@AdminGuardHasPerm(\App\PermKeys::SHELTER_WORKERS_CONTROL))
                                        <form
                                            action="{{ route('shelter_workers.delete_file', ['field' => $field, 'id' => $shelterWorker->id]) }}"
                                            method="POST" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="btn btn-sm btn-danger">{{ __('admin.delete') }}</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endif
                    @endforeach

                    <!-- Check if no files exist -->
                    @if (!$filesExist)
                        <tr>
                            <td colspan="3">{{ __('admin.no_data') }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    @else
        @include('components.no_permissions')
    @endif


@endsection

@include('components.sweetalert')
