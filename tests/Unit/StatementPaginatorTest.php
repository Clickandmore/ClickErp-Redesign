<?php

namespace Tests\Unit;

use Illuminate\Database\SQLiteConnection;
use Illuminate\Pagination\Paginator;
use Nami\Finance\Services\StatementPaginator;
use PDO;
use PHPUnit\Framework\TestCase;

class StatementPaginatorTest extends TestCase
{
    private SQLiteConnection $connection;
    private int $page = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->connection = new SQLiteConnection(new PDO('sqlite::memory:'));
        $this->connection->statement('CREATE TABLE movements (id INTEGER PRIMARY KEY, date TEXT, debtor_value DECIMAL(12,2), creditor_value DECIMAL(12,2), status TEXT, account_id INTEGER)');
        for ($id = 1; $id <= 105; $id++) {
            $this->connection->table('movements')->insert([
                'id' => $id, 'date' => '2026-09-01', 'debtor_value' => 10,
                'creditor_value' => 2, 'status' => $id % 2 ? 'debtor' : 'creditor', 'account_id' => 1,
            ]);
        }
        $this->connection->table('movements')->insert([
            'id' => 106, 'date' => '2026-09-02', 'debtor_value' => 999,
            'creditor_value' => 0, 'status' => 'debtor', 'account_id' => 2,
        ]);
        Paginator::currentPageResolver(fn () => $this->page);
        Paginator::currentPathResolver(fn () => '/finance/account-statement-suppliers');
        Paginator::queryStringResolver(fn () => ['account_id' => 1, 'type' => 'both']);
    }

    protected function tearDown(): void
    {
        Paginator::currentPageResolver(fn () => 1);
        Paginator::currentPathResolver(fn () => '/');
        Paginator::queryStringResolver(fn () => []);
        parent::tearDown();
    }

    public function test_pages_are_bounded_and_keep_totals_filters_and_deterministic_order(): void
    {
        $query = $this->connection->table('movements')->where('account_id', 1)->orderBy('date')->orderBy('id');
        $allIds = [];
        foreach ([1 => 50, 2 => 50, 3 => 5] as $page => $count) {
            $this->page = $page;
            $result = StatementPaginator::fromQuery($query);
            $this->assertCount($count, $result);
            $this->assertSame(105, $result->total());
            $this->assertEquals(1050, $result->totals->debtor);
            $this->assertEquals(210, $result->totals->creditor);
            $this->assertEquals(8, $result->totals->balance);
            $this->assertEquals(($page - 1) * 500, $result->previous->debtor);
            $this->assertEquals(0, $result->previous->balance);
            $this->assertStringContainsString('account_id=1', $result->url(2));
            $this->assertStringContainsString('type=both', $result->url(2));
            $allIds = array_merge($allIds, $result->pluck('id')->all());
        }
        $this->assertSame(range(1, 105), $allIds);
        $this->assertNull($query->limit);
        $this->assertNull($query->offset);
    }

    public function test_double_account_balance_carries_forward_without_account_direction(): void
    {
        $this->page = 2;
        $result = StatementPaginator::fromQuery($this->connection->table('movements')->where('account_id', 1)->orderBy('id'), false);
        $this->assertEquals(400, $result->previous->balance);
        $this->assertEquals(840, $result->totals->balance);
        $this->assertSame(51, $result->firstItem());
        $this->assertEquals(408, $result->previous->balance + $result->first()->debtor_value - $result->first()->creditor_value);
    }

    public function test_filtered_and_empty_results_and_out_of_range_pages(): void
    {
        $this->page = 999;
        $result = StatementPaginator::fromQuery($this->connection->table('movements')->where('account_id', 1)->where('status', 'creditor')->orderBy('id'));
        $this->assertSame(2, $result->currentPage());
        $this->assertCount(2, $result);
        $this->assertEquals(-400, $result->previous->balance);
        $this->assertEquals(-416, $result->totals->balance);

        $empty = StatementPaginator::fromQuery($this->connection->table('movements')->where('account_id', 999)->orderBy('id'));
        $this->assertSame(1, $empty->currentPage());
        $this->assertSame(0, $empty->total());
        $this->assertEquals(0, $empty->totals->balance);
        $this->assertEquals(0, $empty->previous->balance);
    }

    public function test_only_the_page_rows_are_loaded_while_aggregates_stay_in_sql(): void
    {
        $this->page = 2;
        $this->connection->enableQueryLog();
        StatementPaginator::fromQuery($this->connection->table('movements')->orderBy('id'));
        $queries = $this->connection->getQueryLog();
        $this->assertCount(3, $queries);
        $this->assertStringContainsString('COUNT(*)', $queries[0]['query']);
        $this->assertStringContainsString('COUNT(*)', $queries[1]['query']);
        $this->assertStringContainsString('limit 50 offset 50', $queries[2]['query']);
    }
}
