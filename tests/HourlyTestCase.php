<?php
namespace Tests;

/** Hourly tests build small isolated schemas; cached guardable columns must not leak between schemas. */
abstract class HourlyTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $columns = new \ReflectionProperty(\Illuminate\Database\Eloquent\Model::class, 'guardableColumns');
        $columns->setAccessible(true);
        $columns->setValue(null, []);
    }
}
