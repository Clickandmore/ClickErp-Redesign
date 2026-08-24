<?php

namespace Tests\Unit;

use App\Models\IngazDelegation;
use App\Support\IngazDelegationTimeline;
use Tests\TestCase;

class IngazDelegationTimelineTest extends TestCase
{
    public function test_it_builds_readable_change_details_from_a_delegation_update(): void
    {
        $delegation = new IngazDelegation();
        $delegation->setRawAttributes([
            'ingaz_number' => '100',
            'notes' => null,
        ], true);

        $before = $delegation->getAttributes();
        $delegation->forceFill([
            'ingaz_number' => '200',
            'notes' => 'تم استكمال المستندات',
        ]);
        $delegation->syncChanges();

        $changes = IngazDelegationTimeline::changes($delegation, $before);

        $this->assertSame([
            [
                'field' => 'رقم إنجاز',
                'old' => '100',
                'new' => '200',
            ],
            [
                'field' => 'الملاحظات',
                'old' => '-',
                'new' => 'تم استكمال المستندات',
            ],
        ], $changes);
    }
}
