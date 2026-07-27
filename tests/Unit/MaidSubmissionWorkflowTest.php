<?php

namespace Tests\Unit;

use App\Models\ingaz\Maid;
use App\Models\ingaz\MaidStatusChangeRequest;
use PHPUnit\Framework\TestCase;

class MaidSubmissionWorkflowTest extends TestCase
{
    public function test_submission_types_distinguish_new_and_updated_cvs(): void
    {
        $this->assertSame('create', Maid::SUBMISSION_TYPE_CREATE);
        $this->assertSame('update', Maid::SUBMISSION_TYPE_UPDATE);
    }

    public function test_delete_request_is_labelled_as_a_deleted_cv_for_review(): void
    {
        $request = new MaidStatusChangeRequest([
            'type' => MaidStatusChangeRequest::TYPE_DELETE,
        ]);

        $this->assertSame('سيرة محذوفة', $request->type_label);
    }
}
