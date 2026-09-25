@php
    $branchFilterData = app(\App\Services\BranchAccessService::class)->selectorData(request());
    $branchFilterId = $id ?? 'branchFilter';
    $branchFilterName = $name ?? 'branch_id';
    $branchFilterLabel = $label ?? __('financial_requests.branch');
    $branchFilterContainerClass = $containerClass ?? 'col-md-3';
    // شاشات التقارير المالية تجمع فلاترها بـ jQuery عبر `.search_keys`، فالحقل
    // يحتاج أن يحمل نفس الصنف كي يُرسل مع بقية الفلاتر في طلب الـ ajax.
    $branchFilterSelectClass = $selectClass ?? 'form-select';
    $branchFilterLabelClass = $labelClass ?? 'form-label';
@endphp

@if (($branchFilterData['enabled'] ?? false) && ($branchFilterData['can_view_all_branches'] ?? false) && $branchFilterData['branches']->isNotEmpty())
    <div class="{{ $branchFilterContainerClass }}">
        <label for="{{ $branchFilterId }}" class="{{ $branchFilterLabelClass }}">{{ $branchFilterLabel }}</label>
        <select class="{{ $branchFilterSelectClass }}" id="{{ $branchFilterId }}" name="{{ $branchFilterName }}" data-branch-filter>
            <option value="">{{ __('financial_requests.all_branches') }}</option>
            @foreach ($branchFilterData['branches'] as $branch)
                <option value="{{ $branch->id }}" {{ (int) ($branchFilterData['selected_branch_id'] ?? 0) === (int) $branch->id ? 'selected' : '' }}>
                    {{ $branch->name }}{{ $branch->code ? ' - ' . $branch->code : '' }}
                </option>
            @endforeach
        </select>
    </div>
@elseif (($branchFilterData['enabled'] ?? false) && ($branchFilterData['forced_branch_id'] ?? null))
    <input type="hidden" name="{{ $branchFilterName }}" value="{{ $branchFilterData['forced_branch_id'] }}">
@endif
