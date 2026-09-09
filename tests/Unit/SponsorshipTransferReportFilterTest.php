<?php

namespace Tests\Unit;

use App\Services\ingaz\ReportExportService;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Http\Request;
use PDO;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class SponsorshipTransferReportFilterTest extends TestCase
{
    /** @dataProvider dateFilters */
    public function test_filters_use_the_selected_date_and_include_boundary_days(array $filters, array $expected): void
    {
        $connection = new SQLiteConnection(new PDO('sqlite::memory:'));
        $connection->statement('CREATE TABLE transfers (id INTEGER, created_at TEXT, transferred_at TEXT)');
        $connection->table('transfers')->insert([
            ['id' => 1, 'created_at' => '2026-07-15 12:00:00', 'transferred_at' => '2026-08-01 00:00:00'],
            ['id' => 2, 'created_at' => '2026-08-15 12:00:00', 'transferred_at' => '2026-08-31 23:59:59'],
            ['id' => 3, 'created_at' => '2026-08-20 12:00:00', 'transferred_at' => '2026-09-01 00:00:00'],
            ['id' => 4, 'created_at' => '2026-08-25 12:00:00', 'transferred_at' => null],
        ]);

        $query = $connection->table('transfers')->orderBy('id');
        $method = new ReflectionMethod(ReportExportService::class, 'applySponsorshipTransferFilters');
        $method->setAccessible(true);
        $method->invoke(new ReportExportService(), new Request($filters), $query);

        $this->assertSame($expected, $query->pluck('id')->all());
    }

    public static function dateFilters(): array
    {
        return [
            'transfer period excludes September and pending transfers' => [
                ['transfer_date_from' => '2026-08-01', 'transfer_date_to' => '2026-08-31'], [1, 2],
            ],
            'transfer start only' => [['transfer_date_from' => '2026-08-31'], [2, 3]],
            'transfer end only' => [['transfer_date_to' => '2026-08-31'], [1, 2]],
            'creation period still works' => [['date_from' => '2026-08-01', 'date_to' => '2026-08-31'], [2, 3, 4]],
            'both periods intersect' => [
                ['date_from' => '2026-08-01', 'transfer_date_from' => '2026-08-01', 'transfer_date_to' => '2026-08-31'], [2],
            ],
            'empty dates do not filter' => [['transfer_date_from' => '', 'transfer_date_to' => ''], [1, 2, 3, 4]],
        ];
    }
}
