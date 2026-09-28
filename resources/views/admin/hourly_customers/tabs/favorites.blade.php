{{-- العاملات والفرق المفضلة: تُقترح أولًا عند اختيار العاملات. --}}
@php $c = fn (string $key, array $replace = []) => __('hourly_customers.'.$key, $replace); @endphp
<div class="hc-note mb-3"><i class="fa fa-circle-info mt-1"></i><div>{{ $c('favorites.hint') }}</div></div>

<div class="row g-3">
    @foreach(['maid' => ['maids', 'choose_maid', $maidOptions, $maidNames, 'fa-user'], 'team' => ['teams', 'choose_team', $teamOptions, $teamNames, 'fa-people-group']] as $type => [$title, $choose, $options, $names, $icon])
        @php $rows = $favorites->where('favorable_type', $type); @endphp
        <div class="col-lg-6">
            <div class="hc-group-card h-100">
                <h5 class="m-0" style="font-weight:800"><i class="fa {{ $icon }}"></i> {{ $c('favorites.'.$title) }}</h5>
                @if($canEdit)
                    <form method="POST" action="{{ route('admin.hourly-customers.favorites.store', $customer->id) }}" class="d-flex gap-2">
                        @csrf <input type="hidden" name="favorable_type" value="{{ $type }}">
                        <select class="form-select form-select-sm" name="favorable_id" required aria-label="{{ $c('favorites.'.$choose) }}">
                            <option value="">{{ $c('favorites.'.$choose) }}</option>
                            @foreach($options as $id => $name)@unless($rows->contains('favorable_id', $id))<option value="{{ $id }}">{{ $name }}</option>@endunless @endforeach
                        </select>
                        <button class="hc-btn is-primary is-sm"><i class="fa fa-plus"></i> {{ $c('favorites.add') }}</button>
                    </form>
                @endif
                <div class="hc-chips">
                    @forelse($rows as $row)
                        <span class="hc-chip">
                            @if($type === 'maid')<a href="{{ route('admin.hourly-maids.show', $row->favorable_id) }}">{{ $names[$row->favorable_id] ?? '#'.$row->favorable_id }}</a>@else{{ $names[$row->favorable_id] ?? '#'.$row->favorable_id }}@endif
                            @if($canEdit)
                                <form method="POST" action="{{ route('admin.hourly-customers.favorites.destroy', [$customer->id, $row->id]) }}" class="m-0">@csrf @method('DELETE')
                                    <button title="{{ $c('favorites.remove') }}" aria-label="{{ $c('favorites.remove') }}"><i class="fa fa-xmark"></i></button></form>
                            @endif
                        </span>
                    @empty
                        <span class="hc-muted">{{ $c('favorites.empty') }}</span>
                    @endforelse
                </div>
            </div>
        </div>
    @endforeach
</div>
