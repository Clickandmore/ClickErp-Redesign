@extends('click.layout.indexs.index')
@section('style')
    <link rel="stylesheet" href="{{ asset('admin/assets/vendor/tree_collapse/tree-co.css') }}">
    <style>
        .my-swal {
            background-color: #f8d7da !important;
        }

        .price {
            border: 0 !important;
            font-weight: bold;
            margin-inline-start: 6px;
        }

        .account-tree-actions {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-inline-start: 8px;
        }

        .account-tree-code {
            color: #315a63 !important;
            font-weight: 700;
            text-decoration: none !important;
            border-bottom: 1px dashed #315a63;
            padding: 0 3px;
        }

        .account-tree-code:hover {
            color: #1f434a !important;
            border-bottom-color: #1f434a;
        }

        .account-tree-code--plain {
            border-bottom: 0;
            cursor: default;
        }

        .account-tree-toggle-link,
        .account-tree-toggle-link:hover {
            color: inherit !important;
            text-decoration: none !important;
        }

        .account-tree-filters .form-control {
            height: 40px;
        }

        @media (max-width: 767.98px) {
            .account-tree-filters .col-xs-12 {
                margin-bottom: 10px;
            }
        }
    </style>
@endsection

@section('page-title')
    {{ $bladeTitle }}
@endsection

@section('page-links')
    <li class="breadcrumb-item active" aria-current="page">{{ $bladeTitle }}</li>
@endsection

@section('content')
    @php
        function addButtonTree($route, $fullText = '')
        {
            return '<button type="button" class="btn btn-sm btn-success addButton ms-1" data-bs-toggle="modal" data-bs-target="#createModal"
                            model-route="' .
                $route .
                '" model-title="' .
                $fullText .
                '">
                        <i class="fa fa-plus-circle"></i>
                    </button>';
        }

        function deleteIconButton($route)
        {
            return '<button type="button" class="btn btn-sm btn-danger btn-delete deleteButton" delete-route="' .
                $route .
                '" title="' .
                e(__('finance::finance.Delete')) .
                '">
                        <i class="fa fa-trash-o"></i>
                    </button>';
        }

        function collectAccountLevels($accounts, &$levels)
        {
            foreach ($accounts as $account) {
                $levels[] = (int) $account->level;

                if ($account->childrenRecursive->isNotEmpty()) {
                    collectAccountLevels($account->childrenRecursive, $levels);
                }
            }
        }

        function createTreeView($accounts, $createRoute, $constraints, $canOpenStatementPage, $canOpenPrintedStatement)
        {
            foreach ($accounts as $account) {
                $sumDebtor = $constraints[$account->id]['debtor_value'] ?? 0;
                $sumCreditor = $constraints[$account->id]['creditor_value'] ?? 0;

                foreach ($account->all_children_ids as $childId) {
                    if (isset($constraints[$childId])) {
                        $sumDebtor += $constraints[$childId]['debtor_value'] ?? 0;
                        $sumCreditor += $constraints[$childId]['creditor_value'] ?? 0;
                    }
                }

                if ($account->status->value === 'debtor') {
                    $balance = $sumDebtor - $sumCreditor;
                } else {
                    $balance = $sumCreditor - $sumDebtor;
                }

                $buttons = '';
                if ($account->operation_type != 2) {
                    $buttons .= addButtonTree($createRoute . '?parent_id=' . $account->id);
                    if (
                        ($account->operation_type == 3 ||
                            auth('admin')->user()->admin_type == \App\Enums\AdminTypeisEnum::Developer->value) &&
                        $account->constraintDetails->isEmpty()
                    ) {
                        $buttons .= editButton(route('finance.accounts.edit', $account->id), $account->name);
                        if ($account->operation_type == 3 && $account->constraintDetails->isEmpty()) {
                            $buttons .= deleteIconButton(route('finance.accounts.destroy', $account->id));
                        }
                    }
                }
                if ($buttons !== '') {
                    $buttons = '<span class="account-tree-actions">' . $buttons . '</span>';
                }

                $statementRoute = route('finance.account-statement.index', [
                    'account_id' => $account->id,
                    'to_date' => date('Y-m-d'),
                    'type' => 'both',
                    'other_account' => 'yes',
                ]);

                $printStatementRoute = route('finance.account-statement-print.index', [
                    'account_id' => $account->id,
                    'from_date' => '',
                    'to_date' => date('Y-m-d'),
                    'type' => 'both',
                    'other_account' => 'yes',
                ]);

                $codeLabel =
                    '<a href="' .
                    $statementRoute .
                    '" class="account-tree-code" target="_blank" rel="noopener" title="' .
                    e(__('finance::finance.open_account_statement')) .
                    '">' .
                    e((string) $account->code) .
                    '</a>';

                $status =
                    $account->status->value === 'debtor'
                        ? '<b style="color: red"> (' . e(__('finance::finance.Debtor')) . ') </b>'
                        : '<b style="color: blue"> (' . e(__('finance::finance.Creditor')) . ') </b>';
                $price = "<span class='price'>" . e(__('finance::finance.Balance')) . ' ' . number_format((float) $balance, 2) . '</span>';
                $name = e($account->name);
                $isParent = $account->childrenRecursive->isNotEmpty();
                $startsCollapsed = $account->type?->value === \Nami\Finance\Enums\AccountTypesEnum::Clients->value;
                $branchExpanded = $startsCollapsed ? 'false' : 'true';
                $branchClass = $startsCollapsed ? 'collapse account-tree-branch' : 'collapse in show account-tree-branch';

                echo '<li data-account-id="' . (int) $account->id . '" data-account-level="' . (int) $account->level . '">';
                if ($isParent) {
                    echo '<span data-account-item>
                            ' .
                        (int) $account->level .
                        ' ' .
                        $codeLabel .
                        ' /
                            <a class="account-tree-toggle-link" data-bs-toggle="collapse" href="#Page-' .
                        $account->id .
                        '" aria-expanded="' .
                        $branchExpanded .
                        '" aria-controls="Page-' .
                        $account->id .
                        '">
                                <i class="collapsed"><i class="fa fa-folder"></i></i>
                                <i class="expanded"><i class="fa fa-folder-open"></i></i>
                                <i class="fa fa-book"></i> ' .
                        $name .
                        '
                            </a>
                            ' .
                        $status .
                        $price .
                        $buttons .
                        '
                          </span>
                          <ul id="Page-' .
                        $account->id .
                        '" class="' .
                        $branchClass .
                        '">';
                    createTreeView(
                        $account->childrenRecursive,
                        $createRoute,
                        $constraints,
                        $canOpenStatementPage,
                        $canOpenPrintedStatement,
                    );
                    echo '</ul>';
                } else {
                    echo '<span data-account-item>' .
                        (int) $account->level .
                        ' <i class="fa fa-database"></i> ' .
                        $codeLabel .
                        ' / <i class="fa fa-book"></i> ' .
                        $name .
                        ' ' .
                        $status .
                        $price .
                        $buttons .
                        '</span>';
                }
                echo '</li>';
            }
        }

        $statementPermissions = getUserPermissions();
        $isDeveloper = auth('admin')->user()->admin_type == \App\Enums\AdminTypeisEnum::Developer->value;
        $canOpenStatementPage =
            $isDeveloper ||
            count(
                array_intersect(
                    ['finance.account-statement-read', 'finance.account-statement.index-read'],
                    $statementPermissions,
                ),
            ) > 0;
        $canOpenPrintedStatement =
            $isDeveloper ||
            count(
                array_intersect(
                    ['finance.account-statement-print-read', 'finance::finance.account-statement-print-read'],
                    $statementPermissions,
                ),
            ) > 0;

        $accountLevels = [];
        collectAccountLevels($accountTree, $accountLevels);
        $accountLevels = array_values(array_unique($accountLevels));
        sort($accountLevels);

        $accountFilterOptions = $accountTree;
    @endphp

    {!! addButton($createRoute, $addButtonText) !!}

    <div class="col-md-12 account-tree-filters" id="option_search">
        <div class="row">
            <div class="col-md-4 col-sm-6 col-xs-12">
                <input type="text" class="form-control" id="filterUl" placeholder="{{ __('finance::finance.Search') }}">
            </div>
            <div class="col-md-2 col-sm-4 col-xs-12">
                <select id="levelFilter" class="form-control">
                    <option value="">{{ __('finance::finance.all_levels') }}</option>
                    @foreach ($accountLevels as $level)
                        <option value="{{ $level }}">{{ __('finance::finance.level_number', ['level' => $level]) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 col-sm-6 col-xs-12">
                <select id="accountFilter" class="form-control">
                    <option value="">{{ __('finance::finance.all_accounts') }}</option>
                    @foreach ($accountFilterOptions as $account)
                        <option value="{{ $account->id }}">{{ $account->code }} / {{ $account->name }}</option>
                    @endforeach
                </select>
            </div>
            {{-- فلتر الفرع يعيد تحميل الصفحة: الشجرة وأرصدتها تُبنى في الخادم،
                 بخلاف فلاتر البحث والمستوى التي تعمل على الشجرة المعروضة. --}}
            @include('admin.partials.branch-filter', [
                'name' => 'branch_id',
                'label' => __('financial_requests.branch'),
                'id' => 'accountBranchFilter',
                'containerClass' => 'col-md-2 col-sm-4 col-xs-12',
                'selectClass' => 'form-control',
                'labelClass' => 'sr-only visually-hidden',
            ])
        </div>
        @include('admin.partials.branch-report-scope', ['scopeTag' => 'div', 'scopeClass' => 'account-tree-branch-scope mt-2'])
        <div class="clearfix"></div>
        <div class="tree searchTree" id="account-tree-container">
            <ul>
                <li>
                    <span data-account-item>
                        <a data-bs-toggle="collapse" href="#Web" aria-expanded="true" aria-controls="Web">
                            <i class="collapsed"><i class="fa fa-folder"></i></i>
                            <i class="expanded"><i class="fa fa-folder-open"></i></i> {{ $bladeTitle }}
                        </a>
                    </span>
                    <ul id="Web" class="collapse in show account-tree-branch">
                        <?php createTreeView($accountTree, $createRoute, $constraints, $canOpenStatementPage, $canOpenPrintedStatement); ?>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
@endsection

@section('js')
    <script>
        window.accountingGuideReloadOnCreate = false;

        document.addEventListener('click', function(event) {
            const accountCodeLink = event.target.closest('.account-tree-code[href]');

            if (!accountCodeLink) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();
            window.open(accountCodeLink.href, '_blank', 'noopener');
        }, true);

        function applyAccountTreeFilters() {
            const filter = ($('#filterUl').val() || '').trim().toLowerCase();
            const level = $('#levelFilter').val();
            const accountId = $('#accountFilter').val();
            const rootNodes = $('#account-tree-container > ul > li');

            if (!filter.length && !level.length && !accountId.length) {
                $('#account-tree-container li').show();
                return;
            }

            let branchAccountIds = null;
            if (accountId.length) {
                branchAccountIds = new Set();
                const selectedNode = $('#account-tree-container li[data-account-id="' + accountId + '"]').first();

                selectedNode.add(selectedNode.find('li[data-account-id]')).each(function() {
                    branchAccountIds.add(String($(this).data('accountId')));
                });
            }

            const evaluateNode = function($node) {
                const ownText = ($node.children('[data-account-item]').first().text() || '').toLowerCase();
                const ownLevel = String($node.data('accountLevel') || '');
                const ownAccountId = String($node.data('accountId') || '');
                let childMatch = false;

                $node.children('ul.account-tree-branch').children('li').each(function() {
                    if (evaluateNode($(this))) {
                        childMatch = true;
                    }
                });

                const matchesText = !filter.length || ownText.includes(filter);
                const matchesLevel = !level.length || ownLevel === level;
                const matchesBranch = branchAccountIds === null || branchAccountIds.has(ownAccountId);
                const ownMatch = matchesText && matchesLevel && matchesBranch;
                const isMatch = ownMatch || childMatch;

                $node.toggle(isMatch);

                if (isMatch && childMatch) {
                    $node.children('ul.account-tree-branch').addClass('show');
                    $node.children('[data-account-item]').find('[data-bs-toggle="collapse"]').attr('aria-expanded',
                        'true');
                }

                return isMatch;
            };

            rootNodes.each(function() {
                evaluateNode($(this));
            });
        }

        window.reloadAccountTree = function() {
            const container = $('#account-tree-container');
            if (container.length === 0) {
                return;
            }

            $.get("{{ route('finance.accounts.index') }}", function(html) {
                const parsed = $.parseHTML(html);
                const newTree = $(parsed).find('#account-tree-container').html();
                const newLevels = $(parsed).find('#levelFilter').html();
                const newAccounts = $(parsed).find('#accountFilter').html();
                const selectedAccount = $('#accountFilter').val();

                if (newTree) {
                    container.html(newTree);
                }

                if (newLevels) {
                    $('#levelFilter').html(newLevels);
                }

                if (newAccounts) {
                    $('#accountFilter').html(newAccounts).val(selectedAccount);
                }

                applyAccountTreeFilters();
            });
        };

        $('#filterUl').on('input', function() {
            applyAccountTreeFilters();
        });

        $('#levelFilter').on('change', function() {
            applyAccountTreeFilters();
        });

        $('#accountFilter').on('change', function() {
            applyAccountTreeFilters();
        });

        $('#accountBranchFilter').on('change', function() {
            const url = new URL(window.location.href);
            const branchId = $(this).val();

            if (branchId) {
                url.searchParams.set('branch_id', branchId);
            } else {
                url.searchParams.delete('branch_id');
            }

            window.location.assign(url.toString());
        });
    </script>
@endsection
