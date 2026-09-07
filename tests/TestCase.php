<?php

namespace Tests;

use App\Support\BranchQueryContext;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // BranchQueryContext يحتفظ بفرع المستخدم الحالي في خاصية static،
        // وهي تعيش لعمر عملية PHPUnit كلها لا لعمر الاختبار الواحد.
        BranchQueryContext::reset();
    }

    protected function tearDown(): void
    {
        BranchQueryContext::reset();

        parent::tearDown();
    }
}
