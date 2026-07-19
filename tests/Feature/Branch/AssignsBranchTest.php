<?php

namespace Tests\Feature\Branch;

use App\Models\Concerns\AssignsBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\InteractsWithBranches;
use Tests\TestCase;

/**
 * Write-side branch scoping: the AssignsBranch trait stamps branch_id on create so a
 * branch admin's records land in their own branch without every controller remembering to.
 *
 * `admins` is used as the host table because it carries branch_id and is safe to write to.
 */
class AssignsBranchTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithBranches;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpBranches();
        $this->enableBranchesModule();
    }

    private function newRecord(array $attributes = []): BranchStampedRecord
    {
        return new BranchStampedRecord(array_merge([
            'name' => 'Trait subject',
            'username' => 'subject_' . uniqid(),
            'admin_type' => 'admin',
            'status' => 'active',
            'whatsapp' => '0500000000',
        ], $attributes));
    }

    /** @test */
    public function it_stamps_the_branch_of_the_logged_in_admin_on_create(): void
    {
        $branch = $this->makeBranch();
        $this->actingAsAdmin($this->branchAdmin($branch));

        $record = $this->newRecord();
        $record->save();

        $this->assertSame($branch->id, (int) $record->branch_id);
    }

    /** @test */
    public function it_leaves_an_explicitly_set_branch_alone(): void
    {
        $adminBranch = $this->makeBranch();
        $explicitBranch = $this->makeBranch();
        $this->actingAsAdmin($this->branchAdmin($adminBranch));

        $record = $this->newRecord(['branch_id' => $explicitBranch->id]);
        $record->save();

        $this->assertSame($explicitBranch->id, (int) $record->branch_id);
    }

    /** @test */
    public function it_stamps_nothing_when_the_acting_admin_has_no_branch(): void
    {
        $this->actingAsAdmin($this->globalAdmin());

        $record = $this->newRecord();
        $record->save();

        $this->assertNull($record->branch_id);
    }

    /** @test */
    public function it_stamps_nothing_when_nobody_is_logged_in(): void
    {
        $record = $this->newRecord();
        $record->save();

        $this->assertNull($record->branch_id);
    }

    /** @test */
    public function it_stamps_nothing_when_the_branches_module_is_disabled(): void
    {
        $branch = $this->makeBranch();
        $this->actingAsAdmin($this->branchAdmin($branch));
        $this->enableBranchesModule(false);

        $record = $this->newRecord();
        $record->save();

        $this->assertNull($record->branch_id);
    }

    /** @test */
    public function it_does_not_re_stamp_an_existing_record_on_update(): void
    {
        $originalBranch = $this->makeBranch();
        $this->actingAsAdmin($this->branchAdmin($originalBranch));

        $record = $this->newRecord();
        $record->save();

        // A different branch admin later edits the same record.
        $otherBranch = $this->makeBranch();
        $this->actingAsAdmin($this->branchAdmin($otherBranch));

        $record->name = 'Renamed';
        $record->save();

        $this->assertSame(
            $originalBranch->id,
            (int) $record->fresh()->branch_id,
            'Editing must not move a record between branches.'
        );
    }

    /** @test */
    public function it_backfills_a_record_whose_branch_was_left_null(): void
    {
        $this->actingAsAdmin($this->globalAdmin());

        $record = $this->newRecord();
        $record->save();
        $this->assertNull($record->branch_id);

        $branch = $this->makeBranch();
        $this->actingAsAdmin($this->branchAdmin($branch));

        $record->name = 'Touched by a branch admin';
        $record->save();

        $this->assertSame(
            $branch->id,
            (int) $record->fresh()->branch_id,
            'An empty branch_id is treated as unassigned and gets filled on the next save.'
        );
    }

    /** @test */
    public function the_branch_relation_resolves_to_the_stamped_branch(): void
    {
        $branch = $this->makeBranch();
        $this->actingAsAdmin($this->branchAdmin($branch));

        $record = $this->newRecord();
        $record->save();

        $this->assertTrue($record->branch->is($branch));
    }

    /** @test */
    public function every_table_referenced_by_assigns_branch_models_actually_has_a_branch_column(): void
    {
        $missing = [];

        foreach ($this->modelsUsingAssignsBranch() as $class) {
            $table = (new $class)->getTable();

            if (! DB::getSchemaBuilder()->hasColumn($table, 'branch_id')) {
                $missing[] = "{$class} => {$table}";
            }
        }

        $this->assertSame(
            [],
            $missing,
            "These models use AssignsBranch but their table has no branch_id, so scoping silently does nothing:\n"
                . implode("\n", $missing)
        );
    }

    /**
     * @return list<class-string<Model>>
     */
    private function modelsUsingAssignsBranch(): array
    {
        $models = [];
        $root = app_path('Models');

        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            if (! str_contains(file_get_contents($file->getPathname()), 'AssignsBranch')) {
                continue;
            }

            $relative = str_replace([$root . DIRECTORY_SEPARATOR, '.php'], '', $file->getPathname());
            $class = 'App\\Models\\' . str_replace(DIRECTORY_SEPARATOR, '\\', $relative);

            if (! class_exists($class)) {
                continue;
            }

            $reflection = new \ReflectionClass($class);
            if ($reflection->isAbstract() || ! $reflection->isSubclassOf(Model::class)) {
                continue;
            }

            if (in_array(AssignsBranch::class, class_uses_recursive($class), true)) {
                $models[] = $class;
            }
        }

        sort($models);

        return $models;
    }
}

/**
 * Minimal Eloquent model that exercises the trait against a real branch_id column.
 */
class BranchStampedRecord extends Model
{
    use AssignsBranch;

    protected $table = 'admins';

    protected $guarded = [];
}
