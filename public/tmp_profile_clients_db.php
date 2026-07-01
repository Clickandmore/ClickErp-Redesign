<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo 'Database: ' . config('database.connections.mysql.database') . PHP_EOL;
echo 'Users count: ' . DB::table('users')->count() . PHP_EOL;
echo 'Users columns:' . PHP_EOL;

foreach (Schema::getColumnListing('users') as $column) {
    echo '- ' . $column . PHP_EOL;
}

foreach (['nationalities', 'countries', 'admins', 'branches', 'fn_accounts', 'fn_client_categories'] as $table) {
    echo PHP_EOL . $table . ': ';
    if (!Schema::hasTable($table)) {
        echo 'missing' . PHP_EOL;
        continue;
    }

    echo DB::table($table)->count() . ' rows' . PHP_EOL;
    echo 'columns: ' . implode(', ', Schema::getColumnListing($table)) . PHP_EOL;
}

if (Schema::hasTable('nationalities')) {
    echo PHP_EOL . 'Nationalities sample:' . PHP_EOL;
    $rows = DB::table('nationalities')->limit(10)->get();
    foreach ($rows as $row) {
        echo json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    }
}

if (Schema::hasTable('countries')) {
    echo PHP_EOL . 'Countries sample:' . PHP_EOL;
    $rows = DB::table('countries')->limit(10)->get();
    foreach ($rows as $row) {
        echo json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    }
}
