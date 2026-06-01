@extends('agent.layout')

@section('page-title', 'Contracts')

@section('content')
    <section class="agent-section">
        <form action="{{ route('agent.contracts.index') }}" method="GET" class="agent-filter">
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Search by contract, client, or worker">
            <select name="status">
                <option value="">All Statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ getContractStatus($status) }}</option>
                @endforeach
            </select>
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
                        <th>Client</th>
                        <th>Worker</th>
                        <th>Passport</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($contracts as $contract)
                        <tr>
                            <td>{{ $contract->id }}</td>
                            <td>{{ $contract->user?->name ?: '-' }}</td>
                            <td>{{ $contract->maid?->name ?: ($contract->info?->manual_maid_name ?: '-') }}</td>
                            <td>{{ $contract->maid?->passport_num ?: ($contract->info?->manual_maid_passport_num ?: '-') }}</td>
                            <td><span class="agent-badge warning">{{ getContractStatus($contract->status) }}</span></td>
                            <td>{{ optional($contract->created_at)->format('Y-m-d') ?: '-' }}</td>
                            <td>
                                <a href="{{ route('agent.contracts.show', $contract) }}" class="agent-button">
                                    <i class="fa fa-eye"></i>
                                    <span>View</span>
                                </a>
                            </td>
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
            {{ $contracts->links() }}
        </div>
    </section>
@endsection
