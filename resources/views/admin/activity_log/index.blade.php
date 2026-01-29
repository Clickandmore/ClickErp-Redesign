@extends('admin.layout.indexs.index')

@section('content')

{{-- @if (Auth::user()->can(\App\PermKeys::ACTIVITY_VIEW)) --}}
@include('components.session-messages')

<style>
    .activity-log-wrap {
        --ink: #1b2522;
        --muted: #6a6f6b;
        --card: #fffaf2;
        --edge: #e5dfd2;
        --accent: #2a9d8f;
        --accent-2: #e07a5f;
        --bg: #f3efe6;
        font-family: "Cairo", "Tajawal", "IBM Plex Sans", "Noto Sans Arabic", "Segoe UI", sans-serif;
    }
    .activity-log-card {
        background:
            radial-gradient(1200px 400px at 100% -20%, #fff1d8 0%, transparent 60%),
            radial-gradient(900px 360px at -10% 0%, #e7f5f2 0%, transparent 55%),
            var(--card);
        border: 1px solid var(--edge);
        border-radius: 18px;
        box-shadow: 0 14px 30px rgba(24, 35, 30, 0.08);
        padding: 14px;
    }
    .activity-log-table {
        color: var(--ink);
        margin-bottom: 0;
        border-collapse: separate;
        border-spacing: 0 10px;
    }
    .activity-log-table thead th {
        font-size: 12px;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: var(--muted);
        border: 0;
        padding: 12px 14px;
    }
    .activity-log-table tbody tr {
        background: #ffffff;
        box-shadow: 0 6px 14px rgba(16, 24, 20, 0.06);
        border-radius: 12px;
    }
    .activity-log-table tbody td {
        border: 0;
        padding: 14px;
        vertical-align: middle;
        font-size: 14px;
    }
    .activity-log-table tbody tr td:first-child {
        border-top-left-radius: 12px;
        border-bottom-left-radius: 12px;
        font-weight: 600;
    }
    .activity-log-table tbody tr td:last-child {
        border-top-right-radius: 12px;
        border-bottom-right-radius: 12px;
    }
    .activity-log-table tbody tr:hover {
        transform: translateY(-1px);
        transition: transform 0.15s ease;
    }
    .activity-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }
    .activity-badge.is-created {
        background: rgba(42, 157, 143, 0.14);
        color: #1c6b62;
    }
    .activity-badge.is-updated {
        background: rgba(224, 122, 95, 0.16);
        color: #a24a33;
    }
    .activity-badge.is-deleted {
        background: rgba(184, 25, 25, 0.12);
        color: #8b1111;
    }
    .activity-pagination .page-item .page-link {
        border: 1px solid var(--edge);
        color: var(--ink);
        background: #fffdf7;
        border-radius: 999px;
        margin: 0;
        min-width: 36px;
        height: 36px;
        text-align: center;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.15s ease;
    }
    .activity-pagination .page-item.active .page-link {
        background: var(--accent);
        border-color: var(--accent);
        color: #ffffff;
        box-shadow: 0 8px 18px rgba(42, 157, 143, 0.25);
    }
    .activity-pagination .page-item .page-link:hover {
        border-color: var(--accent-2);
        color: var(--accent-2);
        transform: translateY(-1px);
    }
    .activity-pagination .page-item.disabled .page-link {
        color: var(--muted);
        background: #f4efe6;
    }
    .activity-pagination {
        gap: 6px;
        flex-wrap: wrap;
        max-width: 100%;
    }
    .activity-pagination .page-item {
        margin: 0;
    }
    .activity-pagination .page-ellipsis .page-link {
        border: 0;
        background: transparent;
        color: var(--muted);
        min-width: auto;
        height: 36px;
        padding: 0 4px;
    }
    @media (max-width: 768px) {
        .activity-log-card {
            padding: 10px;
        }
        .activity-log-table thead th {
            font-size: 11px;
            padding: 8px 10px;
        }
        .activity-log-table tbody td {
            padding: 10px;
            font-size: 13px;
        }
    }
</style>

<div class="row activity-log-wrap">
    <div class="text-center col-md-12">
        <div class="activity-log-card table-responsive">
        <table class="table activity-log-table cu-table-adj">
            <thead>
                <tr class="text-gray-800 fw-bold fs-6">
                    <th>{{ __('admin.name') }}</th>
                    <th>{{ __('admin.log_action') }}</th>
                    <th>{{ __('admin.model_name') }}</th>
                    <th>{{ __('admin.last_modified') }}</th>

                    {{-- <th>{{ __('admin.log_data') }}</th> --}}
                    {{-- <th style="width:10%">{{ __('admin.actions') }}</th> --}}
                </tr>
            </thead>
            <tbody id="table-body">
                @forelse ($data as $k => $singleRow)
                    <tr class="cu-table-line">
                        <td>{{ @$singleRow->causer->name }}</td>
                        <td>
                            @if($singleRow->description == 'created')
                                <span class="activity-badge is-created">{{ $singleRow->description }}</span>
                            @elseif($singleRow->description == 'deleted')
                                <span class="activity-badge is-deleted">{{ $singleRow->description }}</span>
                            @elseif($singleRow->description == 'updated')
                                <span class="activity-badge is-updated">{{ $singleRow->description }}</span>
                            @else
                                {{ $singleRow->description }}
                            @endif
                        </td>
                        <td>@lang("models_names.$singleRow->subject_type")</td>
                        <td>{{ @$singleRow->created_at->diffForHumans() }}</td>

                        {{-- <td>{{ @$singleRow->properties }}</td> --}}
{{--                        @if (@AdminGuardHasPerm(\App\PermKeys::REC_CONTRACTS_EDIT))--}}
{{--                        <td>--}}
{{--                            <div class="d-flex justify-content-center">--}}
{{--                                <a class="btn btn-sm" href="{{ route('dash.contracts.update', $singleRow->id) . '/edit' }}">--}}
{{--                                    <img height="16" src="{{ asset('admin/custom/actions/edit.svg') }}" alt="Edit">--}}
{{--                                </a>--}}
{{--                            </div>--}}
{{--                        </td>--}}
{{--                        @endif--}}
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center">{{ __('admin.no_data') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>

        @if($data->hasPages())
            <nav aria-label="Page navigation" class="mt-3">
                <ul class="pagination justify-content-center activity-pagination">
                    {{-- زر الصفحة السابقة --}}
                    @if($data->onFirstPage())
                        <li class="page-item disabled"><span class="page-link">&laquo;</span></li>
                    @else
                        <li class="page-item"><a class="page-link" href="{{ $data->previousPageUrl() }}" rel="prev">&laquo;</a></li>
                    @endif

                    @php
                        $current = $data->currentPage();
                        $last = $data->lastPage();
                        $start = max(1, $current - 2);
                        $end = min($last, $current + 2);
                    @endphp

                    {{-- أرقام الصفحات --}}
                    @if ($start > 1)
                        <li class="page-item"><a class="page-link" href="{{ $data->url(1) }}">1</a></li>
                        @if ($start > 2)
                            <li class="page-item page-ellipsis"><span class="page-link">…</span></li>
                        @endif
                    @endif

                    @foreach ($data->getUrlRange($start, $end) as $page => $url)
                        @if ($page == $current)
                            <li class="page-item active"><span class="page-link">{{ $page }}</span></li>
                        @else
                            <li class="page-item"><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach

                    @if ($end < $last)
                        @if ($end < $last - 1)
                            <li class="page-item page-ellipsis"><span class="page-link">…</span></li>
                        @endif
                        <li class="page-item"><a class="page-link" href="{{ $data->url($last) }}">{{ $last }}</a></li>
                    @endif

                    {{-- زر الصفحة التالية --}}
                    @if($data->hasMorePages())
                        <li class="page-item"><a class="page-link" href="{{ $data->nextPageUrl() }}" rel="next">&raquo;</a></li>
                    @else
                        <li class="page-item disabled"><span class="page-link">&raquo;</span></li>
                    @endif
                </ul>
            </nav>
        @endif


    </div>
</div>

{{-- @else
@include('components.no_permissions')
@endif --}}

@endsection


@include('components.sweetalert')
