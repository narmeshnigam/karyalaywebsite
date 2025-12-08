<?php

namespace Karyalay\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Example unit test to verify PHPUnit is working correctly
 */
class ExampleTest extends TestCase
{
    public function testBasicAssertion(): void
    {
        $this->assertTrue(true);
    }

    public function testPhpVersion(): void
    {
        $this->assertGreaterThanOrEqual(8.0, (float)PHP_VERSION);
    }
}
