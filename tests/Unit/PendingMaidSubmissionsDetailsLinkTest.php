<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PendingMaidSubmissionsDetailsLinkTest extends TestCase
{
    public function test_pending_submission_links_open_the_details_page_instead_of_toggling_visibility(): void
    {
        $view = file_get_contents(
            dirname(__DIR__, 2) . '/resources/views/admin/ingaz/maids/pending_submissions.blade.php'
        );

        $this->assertStringContainsString("route('showMaidDetails', \$maid->id)", $view);
        $this->assertStringContainsString("route('showMaidDetails', \$statusRequest->maid_id)", $view);
        $this->assertStringNotContainsString("route('maids.show'", $view);
    }

    public function test_agent_import_review_link_opens_the_details_page(): void
    {
        $view = file_get_contents(
            dirname(__DIR__, 2) . '/resources/views/admin/ingaz/maids/agent_imports/show.blade.php'
        );

        $this->assertStringContainsString("route('showMaidDetails', \$row->maid_id)", $view);
        $this->assertStringNotContainsString("route('maids.show'", $view);
    }
}
