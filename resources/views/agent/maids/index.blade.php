@extends('agent.layout')

@section('page-title', 'CVs')

@section('content')
    <section class="agent-section">
        <form action="{{ route('agent.maids.index') }}" method="GET" class="agent-filter">
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Search by name or passport">
            <input type="text" name="status" value="{{ request('status') }}" placeholder="Status">
            <button type="submit" class="agent-button">
                <i class="fa fa-magnifying-glass"></i>
                <span>Search</span>
            </button>
        </form>

        <div class="agent-table-wrap">
            <table class="agent-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Nationality</th>
                        <th>Passport</th>
                        <th>Age</th>
                        <th>Price</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($maids as $maid)
                        <tr>
                            <td>{{ $maid->id }}</td>
                            <td>{{ $maid->name ?: '-' }}</td>
                            <td>{{ $maid->nationality?->sub_title ?: ($maid->nationality?->title ?: '-') }}</td>
                            <td>{{ $maid->passport_num ?: '-' }}</td>
                            <td>{{ $maid->age ?: '-' }}</td>
                            <td>{{ $maid->resolved_price ? formatMoney($maid->resolved_price) : '-' }}</td>
                            <td><span class="agent-badge">{{ $maid->status ?: '-' }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">No matching records were found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="agent-pagination">
            {{ $maids->links() }}
        </div>
    </section>
@endsection
