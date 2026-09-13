<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class AdminSuspensionModalTest extends TestCase
{
    public function test_suspension_modal_requires_reason_and_makes_reassignment_optional(): void
    {
        $view = file_get_contents(
            dirname(__DIR__, 2) . '/resources/views/admin/auth/admin/index.blade.php'
        );

        $this->assertStringContainsString('id="suspendReason"', $view);
        $this->assertStringContainsString('id="suspendReassignNo" value="0" checked', $view);
        $this->assertStringContainsString('id="suspendReassignYes" value="1"', $view);
        $this->assertStringContainsString('id="suspendReplacementAdmin"', $view);
        $this->assertStringContainsString('reassign_work: reassign ? 1 : 0', $view);
        $this->assertStringContainsString('ستظل الطلبات والحجوزات والعقود الحالية والتاريخية مسندة إلى الموظف المعلق', $view);
        $this->assertStringContainsString('سيتم نقل الطلبات والحجوزات والعقود النشطة فقط', $view);
        $this->assertStringContainsString('الأعمال المنتهية والسجلات التاريخية فستبقى مرتبطة بالموظف الأصلي', $view);
    }
}
