@include('finance::finance.account_statement.partials.report', [
    'data' => $data,
    'firstBalance' => $firstBalance,
    'otherAccount' => $otherAccount,
    'show_other_account' => $show_other_account,
    'accountName' => $accountName ?? null,
    'filters' => $filters ?? [],
    'reportTitle' => $reportTitle ?? null,
    'isPrint' => false,
])
