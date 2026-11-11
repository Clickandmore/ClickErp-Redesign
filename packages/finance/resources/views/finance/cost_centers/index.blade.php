@extends('click.layout.indexs.index')
@section('style')
    {{--    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css"> --}}
    {{--    <link rel="stylesheet" href="{{asset("admin")}}/assets/erp/tree_collapse/tree-co.css?v=2"> --}}
    <link rel="stylesheet" href="{{ asset('admin') }}/assets/vendor/tree_collapse/tree-co.css">
    <style>
        .my-swal {
            background-color: #f8d7da !important;
            /* Example background color */
        }

        .price {
            border: 0 !important;
            font-weight: bold;
            display: inline-block;
            padding: 2px 8px;
            margin-inline-start: 7px;
            border-radius: 12px;
            font-size: 12px;
        }

        .ledger-balance {
            color: #176b48;
            background: #e5f6ee;
        }

        .legacy-balance {
            color: #855f08 !important;
            background: #fff3cd;
        }

        .cost-center-statement-link {
            color: inherit;
            text-decoration: none;
        }

        .cost-center-statement-link:hover {
            color: #0d6efd;
            text-decoration: underline;
        }

        .cost-center-toggle-link {
            margin-inline-end: 8px;
            color: inherit;
            text-decoration: none;
        }

        .cost-center-tree-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            margin-bottom: 14px;
        }

        .cost-center-tree-toolbar .form-control {
            min-width: 260px;
            max-width: 480px;
        }

        .cost-center-tree-toolbar .cost-center-nationality-filter {
            min-width: 200px;
            max-width: 260px;
        }

        .cost-center-tree-actions {
            display: inline-flex;
            gap: 4px;
            margin-inline-start: 8px;
            vertical-align: middle;
        }

        .searchTree [data-cost-center-node] {
            margin-block: 2px;
        }

        .searchTree .cost-center-level-1>[data-cost-center-item] {
            color: #183f46;
            font-weight: 700;
        }

        .searchTree .cost-center-level-2>[data-cost-center-item] {
            color: #244b68;
            font-weight: 600;
        }

        .searchTree .cost-center-level-3>[data-cost-center-item] {
            color: #36474f;
        }

        .searchTree .cost-center-level-1>[data-cost-center-item] .fa-folder,
        .searchTree .cost-center-level-1>[data-cost-center-item] .fa-folder-open {
            color: #1f6f78;
        }

        .searchTree .cost-center-level-2>[data-cost-center-item] .fa-folder,
        .searchTree .cost-center-level-2>[data-cost-center-item] .fa-folder-open {
            color: #3977a5;
        }

        .searchTree .cost-center-level-3>[data-cost-center-item]>.fa-database {
            color: #7b8f96;
        }

        .cost-center-tree-branch {
            content-visibility: auto;
            contain-intrinsic-size: 1px 500px;
        }

        @media (max-width: 767.98px) {
            .cost-center-tree-toolbar .form-control {
                min-width: 100%;
            }

        }
    </style>
@endsection
@section('page-title')
    {{ $bladeTitle }}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{ $bladeTitle }}</li>
@endsection
@section('content')
    <?php
    
    function costCenterStatementUrl($costCenterId)
    {
        return route('finance.account-cost-centers.index', [
            'cost_center_id' => $costCenterId,
            'type' => 'both',
            'to_date' => now(config('app.timezone', 'Asia/Riyadh'))->format('Y-m-d'),
            'auto_search' => 1,
        ]);
    }
    function createTreeView($array, $createRoute, $constraints, $level = 1)
    {
        foreach ($array as $row) {
            $visualLevel = min(3, max(1, (int) ($row->legacy_level ?: $level)));
            $hasChildren = $row->childrenRecursive->isNotEmpty();
            $sumDebtor = (float) ($row->aggregate_debtor_value ?? 0);
            $sumCreditor = (float) ($row->aggregate_creditor_value ?? 0);
            $price = "<span class='price ledger-balance'>رصيد القيود " . number_format(abs($sumDebtor - $sumCreditor), 2) . '</span>';
            $legacyPrice = '';
            if ($row->legacy_balance !== null) {
                $legacyBalanceType = $row->legacy_balance_type === 'creditor' ? 'دائن' : ($row->legacy_balance_type === 'debtor' ? 'مدين' : '');
                $legacyTitle = 'إجمالي  — مدين: ' . number_format((float) ($row->legacy_debtor_total ?? 0), 2) . '، دائن: ' . number_format((float) ($row->legacy_creditor_total ?? 0), 2);
                $legacyPrice = " <span class='price legacy-balance' title='" . e($legacyTitle) . "'>رصيد  " . number_format((float) $row->legacy_balance, 2) . ' ' . $legacyBalanceType . '</span>';
            }
            $statementUrl = costCenterStatementUrl($row->id);
            $actions = '<span class="cost-center-tree-actions">' . '<button type="button" class="btn btn-sm btn-success addButton" data-cost-center-action="add" data-bs-toggle="modal" data-bs-target="#createModal" title="إضافة فرع"><i class="fa fa-plus-circle"></i></button>' . '<button type="button" class="btn btn-sm btn-primary editButton" data-cost-center-action="edit" data-bs-toggle="modal" data-bs-target="#createModal" title="تعديل"><i class="fa fa-pencil"></i></button>' . '<button type="button" class="btn btn-sm btn-danger deleteButton" data-cost-center-action="delete" title="حذف"><i class="fa fa-trash-o"></i></button>' . '</span>';
    
            if ($hasChildren) {
                echo '<li data-cost-center-node class="cost-center-level-' . $visualLevel . '">';
                echo '   <span data-cost-center-item data-center-id="' . $row->id . '" data-center-title="' . e($row->title) . '">';
                echo '       <a class="cost-center-toggle-link" data-bs-toggle="collapse" href="#Page-' .
                    $row->id .
                    '" aria-expanded="false" aria-controls="Page-' .
                    $row->id .
                    '">
                                                     <i class="collapsed"><i class="fa fa-folder"></i></i>
                                                     <i class="expanded"><i class="fa fa-folder-open"></i></i>
                                                     </a>';
                echo '       <a href="' .
                    $statementUrl .
                    '" target="_blank" rel="noopener" class="cost-center-statement-link">' .
                    ($row->legacy_source_code ?: $row->id) .
                    ' /
                                                           <i class="fa fa-book"></i>  ' .
                    $row->title .
                    $price .
                    $legacyPrice .
                    ' <i class="fa fa-external-link"></i></a>' .
                    $actions;
                echo '   </span>';
                echo '   <ul id="Page-' . $row->id . '" class="collapse cost-center-tree-branch">';
                createTreeView($row->childrenRecursive, $createRoute, $constraints, $level + 1);
                echo '   </ul></li>';
            } else {
                echo '<li data-cost-center-node class="cost-center-level-' .
                    $visualLevel .
                    '">
                                                   <span data-cost-center-item data-center-id="' .
                    $row->id .
                    '" data-center-title="' .
                    e($row->title) .
                    '"> <a href="' .
                    $statementUrl .
                    '" target="_blank" rel="noopener" class="cost-center-statement-link"><i class="fa fa-database"></i> ' .
                    ($row->legacy_source_code ?: $row->id) .
                    ' / <i class="fa fa-book"></i>  ' .
                    $row->title .
                    $price .
                    $legacyPrice .
                    ' <i class="fa fa-external-link"></i></a> ' .
                    $actions .
                    '</span></li>';
            }
        } // end foreach
    }
    //-------------------------------------------------------------------------------------------
    ?>
    {!! addButton($createRoute, $addButtonText) !!}

    <div class="col-md-12 " id="option_search">

        <div class="cost-center-tree-toolbar">
            <input type="search" class="form-control" id="filterUl" placeholder="ابحث برقم أو اسم مركز التكلفة"
                autocomplete="off" />
            @if (!empty($nationalityNodes))
                <select class="form-control cost-center-nationality-filter" id="nationalityFilter"
                    aria-label="فلتر الجنسية">
                    <option value="">كل الجنسيات</option>
                    @foreach ($nationalityNodes as $node)
                        <option value="{{ $node['id'] }}">{{ $node['name'] }} ({{ $node['children'] }} عقد)</option>
                    @endforeach
                </select>
            @endif
            <button type="button" class="btn btn-outline-primary" id="expandCostCenters">
                <i class="fa fa-folder-open"></i> فتح الكل
            </button>
            <button type="button" class="btn btn-outline-secondary" id="collapseCostCenters">
                <i class="fa fa-folder"></i> طي الكل
            </button>
        </div>
        <div class="clearfix"></div>
        <div class="tree searchTree">
            <ul>
                <li><span><a data-bs-toggle="collapse" href="#Web" aria-expanded="true" aria-controls="Web">
                            <i class="collapsed"><i class="fa fa-folder"></i></i>
                            <i class="expanded"><i class="fa fa-folder-open"></i></i> {{ $bladeTitle }} </a>
                    </span>
                    <div id="Web" class="collapse in show">
                        <!--------------------------------------------->
                        <ul>
                            <?php createTreeView($accountTree, $createRoute, $constraints); ?>
                        </ul>
                    </div>
                </li>
            </ul>
        </div>

    </div>

    {{--    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%"> --}}
    {{--        <thead class="thead-inverse"> --}}
    {{--        <tr> --}}
    {{--            <th>{{ __('finance.SL') }}</th> --}}
    {{--            <th>{{ __('finance.name') }}</th> --}}
    {{--            <th>{{ __('finance.cost-centers') }}</th> --}}
    {{--            <th>{{ __('finance.actions') }}</th> --}}
    {{--        </tr> --}}
    {{--        </thead> --}}
    {{--        <tbody> --}}

    {{--        </tbody> --}}
    {{--    </table> --}}
@endsection
@section('js')
    {{--    <script src="{{asset("admin")}}/assets/js/bundle/dataTables.bundle.js"></script> --}}

    {{--    <script> --}}
    {{--        $(document).ready(function () { --}}
    {{--            let columns = [ --}}
    {{--                {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false}, --}}
    {{--                {"data": "title", orderable: false, searchable: true}, --}}
    {{--                {"data": "parent_id", orderable: false, searchable: false}, --}}
    {{--                {"data": "actions", orderable: false, searchable: false} --}}
    {{--            ]; --}}
    {{--            showDataTable("{{ $dataTableRoute }}", columns); --}}
    {{--        }); --}}
    {{--    </script> --}}
    {{-- <script src="{{ asset('admin/assets/js/moment.js') }}"></script> --}}
    <!-- bootstrap datepicker -->
    {{-- <script src="{{asset('assets/js/bootstrap-datepicker.min.js')}}"></script> --}}

    <script>
        {{-- $(document).ajaxComplete(function () { --}}
        {{--    $('#parent_id').select2({placeholder:"@lang('fleet.type')"}); --}}
        {{-- }); --}}
    </script>
    <script>
        (function() {
            const tree = $('.searchTree');
            let filterTimer = null;
            const createRoute = @json($createRoute);
            const editRoute = @json(route('finance.cost-centers.edit', ['cost_center' => '__CENTER__']));
            const deleteRoute = @json(route('finance.cost-centers.destroy', ['cost_center' => '__CENTER__']));

            document.addEventListener('click', function(event) {
                const button = event.target.closest('[data-cost-center-action]');
                if (!button) {
                    return;
                }

                const item = button.closest('[data-cost-center-item]');
                const centerId = item.dataset.centerId;
                const centerTitle = item.dataset.centerTitle;
                const action = button.dataset.costCenterAction;

                if (action === 'add') {
                    button.setAttribute('model-route', createRoute + '?parent_id=' + encodeURIComponent(
                        centerId));
                    button.setAttribute('model-title', 'إضافة مركز فرعي إلى ' + centerTitle);
                } else if (action === 'edit') {
                    button.setAttribute('model-route', editRoute.replace('__CENTER__', centerId));
                    button.setAttribute('model-title', 'تعديل ' + centerTitle);
                } else if (action === 'delete') {
                    button.setAttribute('delete-route', deleteRoute.replace('__CENTER__', centerId));
                }
            }, true);

            function setBranches(open) {
                tree.find('.cost-center-tree-branch').toggleClass('show', open);
                tree.find('[data-cost-center-item] [data-bs-toggle="collapse"]').attr('aria-expanded', open ? 'true' :
                    'false');
            }

            function applyFilter() {
                const filter = ($('#filterUl').val() || '').trim().toLocaleLowerCase('ar');

                if (!filter) {
                    applyNationality();
                    return;
                }
                $('#nationalityFilter').val('');

                function evaluateNode(node) {
                    const $node = $(node);
                    const ownText = ($node.children('[data-cost-center-item]').first().text() || '').toLocaleLowerCase(
                        'ar');
                    let childMatch = false;

                    $node.children('ul.cost-center-tree-branch').children('[data-cost-center-node]').each(function() {
                        childMatch = evaluateNode(this) || childMatch;
                    });

                    const matched = ownText.includes(filter) || childMatch;
                    $node.toggle(matched);
                    if (childMatch) {
                        $node.children('ul.cost-center-tree-branch').addClass('show');
                        $node.children('[data-cost-center-item]').find('[data-bs-toggle="collapse"]').attr(
                            'aria-expanded', 'true');
                    }

                    return matched;
                }

                tree.find('#Web > ul > [data-cost-center-node]').each(function() {
                    evaluateNode(this);
                });
            }

            // فلتر الجنسية: يُظهر عقدة الدولة المختارة وعقودها فقط، مع الطريق إليها مفتوحاً.
            function applyNationality() {
                const centerId = $('#nationalityFilter').val();
                tree.find('[data-cost-center-node]').show();
                setBranches(false);
                if (!centerId) {
                    return;
                }

                const $node = tree.find('[data-cost-center-item][data-center-id="' + centerId + '"]')
                    .closest('[data-cost-center-node]');
                if (!$node.length) {
                    return;
                }

                tree.find('[data-cost-center-node]').hide();
                $node.show().find('[data-cost-center-node]').show();
                $node.parents('[data-cost-center-node]').show();
                $node.parents('ul.cost-center-tree-branch').addClass('show');
                $node.children('ul.cost-center-tree-branch').addClass('show');
                $node.parents('[data-cost-center-node]').addBack()
                    .children('[data-cost-center-item]').find('[data-bs-toggle="collapse"]').attr('aria-expanded', 'true');
                $node[0].scrollIntoView({ block: 'start', behavior: 'smooth' });
            }

            $('#nationalityFilter').on('change', function() {
                $('#filterUl').val('');
                applyNationality();
            });

            $('#filterUl').on('input', function() {
                clearTimeout(filterTimer);
                filterTimer = setTimeout(applyFilter, 120);
            });

            $('#expandCostCenters').on('click', function() {
                setBranches(true);
            });

            $('#collapseCostCenters').on('click', function() {
                setBranches(false);
            });
        })();
    </script>
    <script>
        function checkAndResetSelect() {
            var checkbox = document.getElementById('checkboxInput');
            if (!checkbox.checked) {
                document.getElementById('parent_id').selectedIndex = null;
            }
        }
    </script>
@endsection
