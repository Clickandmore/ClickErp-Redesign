<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class AgentMaidMediaFieldsTest extends TestCase
{
    public function test_agent_form_keeps_personal_photo_and_cv_document_separate(): void
    {
        $createView = file_get_contents(
            dirname(__DIR__, 2) . '/resources/views/agent/maids/create.blade.php'
        );
        $editView = file_get_contents(
            dirname(__DIR__, 2) . '/resources/views/agent/maids/edit.blade.php'
        );

        $this->assertStringContainsString('name="image" id="maid_image"', $createView);
        $this->assertStringContainsString('name="maid_cv_file" id="cv_file"', $createView);
        $this->assertStringContainsString('name="maid_cv_file"', $editView);
        $this->assertStringNotContainsString('name="cv_file"', $editView);
    }
}
