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
        }

        .price {
            border: 0 !important;
            font-weight: bold;
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
    
    function addButtonTree($route, $fullText = '')
    {
        return '<button type="button" class="btn btn-success addButton  mx-1" data-bs-toggle="modal" data-bs-target="#createModal"
                                       model-route="' .
            $route .
            '" model-title="' .
            $fullText .
            '">
                                   <i class="fa fa-plus-circle"></i>
                                </button>';
    }
    function costCenterStatementUrl($costCenterId)
    {
        return route('finance.account-cost-centers.index', [
            'cost_center_id' => $costCenterId,
            'type' => 'both',
            'to_date' => now(config('app.timezone', 'Asia/Riyadh'))->format('Y-m-d'),
            'auto_search' => 1,
        ]);
    }
    function createTreeView($array, $createRoute, $constraints)
    {
        foreach ($array as $row) {
            $sumDebtor = 0;
            $sumCreditor = 0;
            $price = '';
            $statementUrl = costCenterStatementUrl($row->id);
            if ($row->allChildrenIDs) {
                // Calculate sum for all children
                foreach ($row->allChildrenIDs as $childId) {
                    if (isset($constraints[$childId])) {
                        $sumDebtor += $constraints[$childId]['debtor_value'] ?? 0;
                        $sumCreditor += $constraints[$childId]['creditor_value'] ?? 0;
                    }
                }
                $finalBalance = abs($sumDebtor - $sumCreditor);
                $price = "<span class='price'> رصيد " . $finalBalance . '</span>';
            }
            if (count($row->childrenRecursive) > 0) {
                echo '<li>';
                echo '   <span>';
                echo '       <a class="cost-center-toggle-link" data-bs-toggle="collapse" href="#Page-' .
                    $row->id .
                    '" aria-expanded="true" aria-controls="Page-' .
                    $row->id .
                    '">
                                                 <i class="collapsed"><i class="fa fa-folder"></i></i>
                                                 <i class="expanded"><i class="fa fa-folder-open"></i></i>
                                                 </a>';
                echo '       <a href="' .
                    $statementUrl .
                    '" target="_blank" rel="noopener" class="cost-center-statement-link">' .
                    $row->id .
                    ' /
                                                       <i class="fa fa-book"></i>  ' .
                    $row->title .
                    $price .
                    ' <i class="fa fa-external-link"></i></a>' .
                    ' ';
                //                echo ($row->status == "debtor") ? "<b style='color: red'> (مدين) </b>" : "<b style='color: blue'> (دائن)  </b>";
                echo addButtonTree($createRoute . '?parent_id=' . $row->id) . editButton(route('finance.cost-centers.edit', $row->id), $row->title) . ' ' . deleteButton(route('finance.cost-centers.destroy', $row->id), $row->children->count() > 0 ? 1 : 0);
                echo '   </span>';
                echo '   <ul>
                                               <div id="Page-' .
                    $row->id .
                    '" class="collapse in show">';
                createTreeView($row->childrenRecursive, $createRoute, $constraints);
                echo '      </div>
                                             </ul>
                                          </li>';
            } else {
                $finalBalance = 0;
                $sumDebtor = 0;
                $sumCreditor = 0;
                if (isset($constraints[$row->id])) {
                    $sumDebtor += $constraints[$row->id]['debtor_value'] ?? 0;
                    $sumCreditor += $constraints[$row->id]['creditor_value'] ?? 0;
                    $finalBalance = abs($sumDebtor - $sumCreditor);
                    $price = "<span class='price'> رصيد " . $finalBalance . '</span>';
                } else {
                    $finalBalance = 0;
                    $sumDebtor = 0;
                    $sumCreditor = 0;
                    $price = "<span class='price'> رصيد " . $finalBalance . '</span>';
                }
                echo '<li>
                                               <span> <a href="' .
                    $statementUrl .
                    '" target="_blank" rel="noopener" class="cost-center-statement-link"><i class="fa fa-database"></i> ' .
                    $row->id .
                    ' / <i class="fa fa-book"></i>  ' .
                    $row->title .
                    $price .
                    ' <i class="fa fa-external-link"></i></a> ';
                //                echo ($row->status == "debtor") ? "<b style='color: red'> (مدين) </b>" : "<b style='color: blue'> (دائن)  </b>";
                echo addButtonTree($createRoute . '?parent_id=' . $row->id) . editButton(route('finance.cost-centers.edit', $row->id), $row->title) . ' ' . deleteButton(route('finance.cost-centers.destroy', $row->id), $row->children->count() > 0 ? 1 : 0);
                echo '</span> ';
                echo ' </li>';
            }
        } // end foreach
    }
    //-------------------------------------------------------------------------------------------
    ?>
    {!! addButton($createRoute, $addButtonText) !!}

    <div class="col-md-12 " id="option_search">

        <div class="col-md-4 col-md-offset-4 col-sm-6 col-xs-12">
            <input type="text" class="form-control" id="filterUl" style="height: 40px;" placeholder="بحــــث" />
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
        $('#filterUl').keyup(function() {
            var filter = jQuery(this).val();
            jQuery(".searchTree li").each(function() {
                if (jQuery(this).text().search(new RegExp(filter, "i")) < 0) {
                    jQuery(this).hide();
                } else {
                    jQuery(this).show();
                    jQuery(this).children().show();
                }
            });
        });
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
