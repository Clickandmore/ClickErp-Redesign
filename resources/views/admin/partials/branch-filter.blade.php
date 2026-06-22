@php
    $branchFilterData = app(\App\Services\BranchAccessService::class)->selectorData(request());
    $branchFilterId = $id ?? 'branchFilter';
    $branchFilterName = $name ?? 'branch_id';
    $branchFilterLabel = $label ?? 'الفرع';
    $branchFilterContainerClass = $containerClass ?? 'col-md-3';
@endphp

@if (($branchFilterData['enabled'] ?? false) && ($branchFilterData['can_view_all_branches'] ?? false) && $branchFilterData['branches']->isNotEmpty())
    <div class="{{ $branchFilterContainerClass }}">
        <label for="{{ $branchFilterId }}" class="form-label">{{ $branchFilterLabel }}</label>
        <select class="form-select" id="{{ $branchFilterId }}" name="{{ $branchFilterName }}" data-branch-filter>
            <option value="">كل الفروع</option>
            @foreach ($branchFilterData['branches'] as $branch)
                <option value="{{ $branch->id }}" @selected((int) ($branchFilterData['selected_branch_id'] ?? 0) === (int) $branch->id)>
                    {{ $branch->name_ar ?: $branch->name_en }}{{ $branch->code ? ' - ' . $branch->code : '' }}
                </option>
            @endforeach
        </select>
    </div>
@elseif (($branchFilterData['enabled'] ?? false) && ($branchFilterData['forced_branch_id'] ?? null))
    <input type="hidden" name="{{ $branchFilterName }}" value="{{ $branchFilterData['forced_branch_id'] }}">
@endif
