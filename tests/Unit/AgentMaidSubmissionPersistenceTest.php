<?php

namespace Tests\Unit;

use App\Http\Actions\ingaz\MaidAction;
use App\Models\ingaz\Maid;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class AgentMaidSubmissionPersistenceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('job_types', function (Blueprint $table) {
            $table->increments('id');
        });
        Schema::create('job_types_translations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('job_type_id');
            $table->string('title');
        });

        foreach (['nationalities', 'religions'] as $table) {
            Schema::create($table, function (Blueprint $table) {
                $table->increments('id');
            });
            DB::table($table)->insert(['id' => 1]);
        }
        foreach (['nationalities_translations' => 'nationality_id', 'religions_translations' => 'religion_id'] as $table => $foreignKey) {
            Schema::create($table, function (Blueprint $table) use ($foreignKey) {
                $table->increments('id');
                $table->integer($foreignKey);
                $table->string('locale');
            });
        }
        Schema::create('maids', function (Blueprint $table) {
            $table->increments('id');
            $table->string('submission_status')->nullable();
            $table->softDeletes();
        });
    }

    public function test_full_cv_creation_passes_pending_review_metadata_to_persistence(): void
    {
        $action = Mockery::mock(MaidAction::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $action->shouldReceive('createMaidRecord')->once()->with(Mockery::on(function (array $data) {
            $this->assertSame(Maid::SUBMISSION_PENDING, $data['submission_status'] ?? null);
            $this->assertSame(Maid::SUBMISSION_TYPE_CREATE, $data['submission_type'] ?? null);
            $this->assertSame(0, $data['is_show']);
            $this->assertSame(9, $data['office_id']);

            return true;
        }))->andThrow(new \RuntimeException('Persistence boundary reached'));

        $this->expectExceptionMessage('Persistence boundary reached');
        $action->storeMaid([
            'name:en' => 'Test CV',
            'nationality_id' => 1,
            'religion_id' => 1,
            'office_id' => 9,
            'status' => 'available',
            'is_show' => 0,
            'submission_status' => Maid::SUBMISSION_PENDING,
            'submission_type' => Maid::SUBMISSION_TYPE_CREATE,
            'preserve_null_arrival_date' => true,
        ], Request::create('/agent/maids/store', 'POST', ['type_id' => 1]));
    }

    public function test_general_listing_only_includes_approved_and_legacy_cvs(): void
    {
        DB::table('maids')->insert([
            ['id' => 1, 'submission_status' => null],
            ['id' => 2, 'submission_status' => Maid::SUBMISSION_APPROVED],
            ['id' => 3, 'submission_status' => Maid::SUBMISSION_PENDING],
            ['id' => 4, 'submission_status' => Maid::SUBMISSION_REJECTED],
        ]);

        $this->assertSame([1, 2], Maid::query()->approvedSubmission()->orderBy('id')->pluck('id')->all());
        $this->assertSame([3], Maid::query()->pendingSubmission()->pluck('id')->all());

        DB::table('maids')->where('id', 3)->update(['submission_status' => Maid::SUBMISSION_APPROVED]);
        $this->assertSame([1, 2, 3], Maid::query()->approvedSubmission()->orderBy('id')->pluck('id')->all());
    }
}
